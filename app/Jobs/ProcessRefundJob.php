<?php

namespace App\Jobs;

use App\Models\Refund;
use App\Models\Order;
use App\Models\Customer;
use App\Jobs\SendOrderNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessRefundJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    private int $refundId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $refundId)
    {
        $this->refundId = $refundId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            $refund = Refund::with(['order', 'customer'])->find($this->refundId);
            
            if (!$refund) {
                Log::error("Refund not found: {$this->refundId}");
                return;
            }

            // Check if already processed (idempotency)
            if ($refund->status === 'completed') {
                Log::info("Refund already completed: {$refund->refund_number}");
                return;
            }

            $refund->markAsProcessing();

            try {
                // Step 1: Validate refund eligibility
                if (!$this->validateRefundEligibility($refund)) {
                    throw new \Exception('Refund not eligible');
                }

                // Step 2: Process refund with payment gateway
                $refundResult = $this->processRefundPayment($refund);

                if ($refundResult['success']) {
                    // Step 3: Update order status
                    $this->updateOrderStatus($refund);
                    
                    // Step 4: Update stock (if applicable)
                    $this->updateStock($refund);
                    
                    // Step 5: Update customer stats
                    $this->updateCustomerStats($refund);
                    
                    // Step 6: Update KPIs in real-time
                    $this->updateKPIs($refund);
                    
                    // Step 7: Mark refund as completed
                    $refund->markAsCompleted(
                        $refundResult['transaction_id'],
                        $refundResult['gateway_data']
                    );
                    
                    // Step 8: Send notification
                    SendOrderNotificationJob::dispatch(
                        $refund->order_id, 
                        'refund_completed', 
                        'log'
                    );
                    
                    Log::info("Refund processed successfully", [
                        'refund_id' => $refund->id,
                        'refund_number' => $refund->refund_number,
                        'amount' => $refund->refund_amount
                    ]);
                    
                } else {
                    throw new \Exception($refundResult['error']);
                }
                
            } catch (\Exception $e) {
                $refund->markAsFailed($e->getMessage());
                Log::error('Refund processing failed', [
                    'refund_id' => $refund->id,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    private function validateRefundEligibility(Refund $refund): bool
    {
        $order = $refund->order;
        
        // Check if order is eligible for refund
        if (!in_array($order->status, ['paid', 'shipped', 'delivered'])) {
            return false;
        }
        
        // Check if refund amount is valid
        if ($refund->refund_amount <= 0 || $refund->refund_amount > $refund->original_amount) {
            return false;
        }
        
        // Check if order hasn't been fully refunded already
        $totalRefunded = Refund::where('order_id', $order->id)
            ->where('status', 'completed')
            ->sum('refund_amount');
            
        if (($totalRefunded + $refund->refund_amount) > $refund->original_amount) {
            return false;
        }
        
        return true;
    }

    private function processRefundPayment(Refund $refund): array
    {
        // Simulate payment gateway refund processing
        $success = rand(1, 10) > 1; // 90% success rate
        
        if ($success) {
            return [
                'success' => true,
                'transaction_id' => 'REF-TXN-' . strtoupper(\Illuminate\Support\Str::random(12)),
                'gateway_data' => [
                    'gateway' => 'stripe',
                    'refund_id' => 're_' . strtolower(\Illuminate\Support\Str::random(24)),
                    'processed_at' => now()->toISOString(),
                    'fee' => 0.00
                ]
            ];
        }
        
        return [
            'success' => false,
            'error' => 'Payment gateway declined refund'
        ];
    }

    private function updateOrderStatus(Refund $refund): void
    {
        $order = $refund->order;
        
        if ($refund->isFullRefund()) {
            $order->update([
                'status' => 'cancelled',
                'payment_status' => 'refunded'
            ]);
        } else {
            // Partial refund - keep order active but mark as partially refunded
            $order->update([
                'payment_status' => 'partially_refunded'
            ]);
        }
    }

    private function updateStock(Refund $refund): void
    {
        if ($refund->isFullRefund()) {
            // Restore stock for full refunds
            foreach ($refund->order->orderItems as $item) {
                $item->product->releaseStock($item->quantity);
            }
        }
        // For partial refunds, we might not restore stock
    }

    private function updateCustomerStats(Refund $refund): void
    {
        $customer = $refund->customer;
        
        // Update customer total spent (subtract refund amount)
        $customer->decrement('total_spent', $refund->refund_amount);
        
        // Recalculate stats
        $customer->updateStats();
    }

    private function updateKPIs(Refund $refund): void
    {
        $refundDate = $refund->created_at->format('Y-m-d');
        $refundYear = $refund->created_at->year;
        $refundMonth = $refund->created_at->month;
        
        // Update daily KPIs (subtract refund amount)
        $dailyKey = "kpis:daily:{$refundDate}";
        $dailyData = Cache::get($dailyKey, ['revenue' => 0, 'order_count' => 0]);
        $dailyData['revenue'] = max(0, $dailyData['revenue'] - $refund->refund_amount);
        Cache::put($dailyKey, $dailyData, 365 * 24 * 60);
        
        // Update yearly KPIs
        $yearlyKey = "kpis:yearly:{$refundYear}";
        $yearlyData = Cache::get($yearlyKey, ['revenue' => 0, 'order_count' => 0]);
        $yearlyData['revenue'] = max(0, $yearlyData['revenue'] - $refund->refund_amount);
        Cache::put($yearlyKey, $yearlyData, 365 * 24 * 60);
        
        // Update monthly KPIs
        $monthlyKey = "kpis:monthly:{$refundYear}-{$refundMonth}";
        $monthlyData = Cache::get($monthlyKey, ['revenue' => 0, 'order_count' => 0]);
        $monthlyData['revenue'] = max(0, $monthlyData['revenue'] - $refund->refund_amount);
        Cache::put($monthlyKey, $monthlyData, 365 * 24 * 60);
        
        // Update overall KPIs
        $overallData = Cache::get("kpis:overall", ['total_revenue' => 0, 'total_orders' => 0]);
        $overallData['total_revenue'] = max(0, $overallData['total_revenue'] - $refund->refund_amount);
        Cache::put("kpis:overall", $overallData, 365 * 24 * 60);
        
        // Update leaderboard (customer stats will be recalculated)
        $this->updateLeaderboard();
    }

    private function updateLeaderboard(): void
    {
        // Trigger leaderboard update
        \App\Console\Commands\UpdateLeaderboardCommand::class;
    }
}
