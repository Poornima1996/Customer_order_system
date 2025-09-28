<?php

namespace App\Console\Commands;

use App\Models\Refund;
use App\Models\Order;
use Illuminate\Console\Command;

class RefundStatusCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refund:status {--order-id=} {--refund-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check refund status and history';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->option('order-id');
        $refundId = $this->option('refund-id');

        if ($refundId) {
            $this->showRefundDetails($refundId);
        } elseif ($orderId) {
            $this->showOrderRefunds($orderId);
        } else {
            $this->showAllRefunds();
        }

        return 0;
    }

    private function showRefundDetails($refundId): void
    {
        $refund = Refund::with(['order', 'customer'])->find($refundId);
        
        if (!$refund) {
            $this->error("Refund not found: {$refundId}");
            return;
        }

        $this->info("Refund Details:");
        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $refund->id],
                ['Refund Number', $refund->refund_number],
                ['Order ID', $refund->order_id],
                ['Order Number', $refund->order->order_number ?? 'N/A'],
                ['Customer', $refund->customer->name ?? 'N/A'],
                ['Amount', '$' . number_format($refund->refund_amount, 2)],
                ['Original Amount', '$' . number_format($refund->original_amount, 2)],
                ['Type', $refund->type],
                ['Status', $refund->status],
                ['Reason', $refund->reason ?? 'N/A'],
                ['Transaction ID', $refund->transaction_id ?? 'N/A'],
                ['Created', $refund->created_at->format('Y-m-d H:i:s')],
                ['Processed', $refund->processed_at ? $refund->processed_at->format('Y-m-d H:i:s') : 'Not processed'],
                ['Completed', $refund->completed_at ? $refund->completed_at->format('Y-m-d H:i:s') : 'Not completed']
            ]
        );
    }

    private function showOrderRefunds($orderId): void
    {
        $order = Order::with(['customer', 'refunds'])->find($orderId);
        
        if (!$order) {
            $this->error("Order not found: {$orderId}");
            return;
        }

        $this->info("Order #{$order->order_number} - Refunds:");
        $this->info("Customer: {$order->customer->name}");
        $this->info("Order Total: $" . number_format($order->total_amount, 2));

        $refunds = $order->refunds;
        
        if ($refunds->isEmpty()) {
            $this->info("No refunds found for this order.");
            return;
        }

        $totalRefunded = $refunds->where('status', 'completed')->sum('refund_amount');
        $this->info("Total Refunded: $" . number_format($totalRefunded, 2));

        $this->table(
            ['ID', 'Refund Number', 'Amount', 'Type', 'Status', 'Created'],
            $refunds->map(function ($refund) {
                return [
                    $refund->id,
                    $refund->refund_number,
                    '$' . number_format($refund->refund_amount, 2),
                    $refund->type,
                    $refund->status,
                    $refund->created_at->format('Y-m-d H:i:s')
                ];
            })
        );
    }

    private function showAllRefunds(): void
    {
        $refunds = Refund::with(['order', 'customer'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        if ($refunds->isEmpty()) {
            $this->info("No refunds found.");
            return;
        }

        $this->info("Recent Refunds (Last 20):");
        $this->table(
            ['ID', 'Refund Number', 'Order ID', 'Customer', 'Amount', 'Type', 'Status', 'Created'],
            $refunds->map(function ($refund) {
                return [
                    $refund->id,
                    $refund->refund_number,
                    $refund->order_id,
                    $refund->customer->name ?? 'N/A',
                    '$' . number_format($refund->refund_amount, 2),
                    $refund->type,
                    $refund->status,
                    $refund->created_at->format('Y-m-d H:i:s')
                ];
            })
        );

        // Show summary
        $totalRefunds = $refunds->count();
        $completedRefunds = $refunds->where('status', 'completed')->count();
        $totalAmount = $refunds->where('status', 'completed')->sum('refund_amount');

        $this->info("\nSummary:");
        $this->info("Total Refunds: {$totalRefunds}");
        $this->info("Completed Refunds: {$completedRefunds}");
        $this->info("Total Refunded Amount: $" . number_format($totalAmount, 2));
    }
}
