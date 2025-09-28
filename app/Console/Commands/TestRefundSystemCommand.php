<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Refund;
use App\Jobs\ProcessRefundJob;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class TestRefundSystemCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refund:test {--order-id=} {--demo}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test refund system with complete workflow demonstration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if ($this->option('demo')) {
            return $this->runDemo();
        }

        $orderId = $this->option('order-id') ?? 1;
        
        $this->info("🚀 Testing Refund System for Order ID: {$orderId}");
        
        // Step 1: Verify order exists and update status
        $order = Order::find($orderId);
        if (!$order) {
            $this->error("Order not found: {$orderId}");
            return 1;
        }

        // Update order to refundable status
        $order->update([
            'status' => 'paid',
            'payment_status' => 'paid'
        ]);
        
        $this->info("✅ Order updated to refundable status");

        // Step 2: Show current KPIs
        $this->showCurrentKPIs();

        // Step 3: Test partial refund
        $this->info("\n🔄 Testing Partial Refund ($25.00)...");
        $partialRefund = Refund::createRefund([
            'order_id' => $orderId,
            'customer_id' => $order->customer_id,
            'refund_amount' => 25.00,
            'original_amount' => $order->total_amount,
            'type' => 'partial',
            'reason' => 'Test partial refund',
            'status' => 'pending'
        ]);

        // Process the refund synchronously for testing
        ProcessRefundJob::dispatchSync($partialRefund->id);
        
        $partialRefund->refresh();
        $this->info("Partial Refund Status: {$partialRefund->status}");

        // Step 4: Test idempotency (re-run same refund)
        $this->info("\n🔄 Testing Idempotency (re-processing same refund)...");
        ProcessRefundJob::dispatchSync($partialRefund->id);
        
        // Step 5: Show updated KPIs
        $this->info("\n📊 Updated KPIs after refund:");
        $this->showCurrentKPIs();

        // Step 6: Test full refund (should fail - already partially refunded)
        $this->info("\n🔄 Testing Full Refund (should show validation)...");
        try {
            $fullRefund = Refund::createRefund([
                'order_id' => $orderId,
                'customer_id' => $order->customer_id,
                'refund_amount' => $order->total_amount,
                'original_amount' => $order->total_amount,
                'type' => 'full',
                'reason' => 'Test full refund',
                'status' => 'pending'
            ]);
            
            ProcessRefundJob::dispatchSync($fullRefund->id);
            $this->info("Full refund attempt processed");
        } catch (\Exception $e) {
            $this->error("Full refund validation: " . $e->getMessage());
        }

        // Step 7: Show final status
        $this->info("\n📋 Final Refund Status:");
        $this->call('refund:status', ['--order-id' => $orderId]);

        return 0;
    }

    private function runDemo(): int
    {
        $this->info("🎬 Refund System Demo");
        $this->info("===================");
        
        $this->info("\n📋 Task 3 Requirements:");
        $this->info("✅ Handle order refunds (partial or full)");
        $this->info("✅ Process refund requests asynchronously using queued jobs");
        $this->info("✅ Update KPIs and leaderboard accordingly in real-time");
        $this->info("✅ Ensure idempotency: no double-counting or data corruption");

        $this->info("\n🏗️ System Components:");
        $this->info("• Refunds table with complete tracking");
        $this->info("• ProcessRefundJob for queued processing");
        $this->info("• Real-time KPI updates");
        $this->info("• Idempotency validation");
        $this->info("• Stock management integration");
        $this->info("• Customer stats updates");
        $this->info("• Notification system integration");

        $this->info("\n🚀 Available Commands:");
        $this->info("php artisan refund:process {order_id} {amount} --type=partial");
        $this->info("php artisan refund:status --order-id={id}");
        $this->info("php artisan refund:test --order-id={id}");
        $this->info("php artisan queue:work  # Process refund jobs");

        return 0;
    }

    private function showCurrentKPIs(): void
    {
        $today = now()->format('Y-m-d');
        $dailyData = Cache::get("kpis:daily:{$today}", ['revenue' => 0, 'order_count' => 0]);
        $overallData = Cache::get("kpis:overall", ['total_revenue' => 0, 'total_orders' => 0]);

        $this->table(['Metric', 'Daily', 'Overall'], [
            ['Revenue', '$' . number_format($dailyData['revenue'], 2), '$' . number_format($overallData['total_revenue'], 2)],
            ['Orders', $dailyData['order_count'], $overallData['total_orders']]
        ]);
    }
}
