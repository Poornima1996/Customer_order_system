<?php

namespace App\Console\Commands;

use App\Models\Refund;
use App\Models\Order;
use App\Jobs\ProcessRefundJob;
use Illuminate\Console\Command;

class ProcessRefundCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'refund:process {order_id} {amount} {--type=full} {--reason=} {--notes=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process refund for an order (full or partial)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->argument('order_id');
        $amount = (float) $this->argument('amount');
        $type = $this->option('type');
        $reason = $this->option('reason') ?? 'Customer request';
        $notes = $this->option('notes') ?? '';

        // Find the order
        $order = Order::with(['customer', 'orderItems.product'])->find($orderId);
        
        if (!$order) {
            $this->error("Order not found: {$orderId}");
            return 1;
        }

        $this->info("Processing refund for Order #{$order->order_number}");
        $this->info("Customer: {$order->customer->name}");
        $this->info("Order Total: $" . number_format($order->total_amount, 2));
        $this->info("Refund Amount: $" . number_format($amount, 2));
        $this->info("Refund Type: {$type}");

        // Validate refund amount
        if ($amount <= 0) {
            $this->error("Refund amount must be greater than 0");
            return 1;
        }

        if ($amount > $order->total_amount) {
            $this->error("Refund amount cannot exceed order total");
            return 1;
        }

        // Check for existing refunds
        $totalRefunded = Refund::where('order_id', $orderId)
            ->where('status', 'completed')
            ->sum('refund_amount');

        if (($totalRefunded + $amount) > $order->total_amount) {
            $this->error("Total refund amount would exceed order total");
            $this->info("Already refunded: $" . number_format($totalRefunded, 2));
            return 1;
        }

        // Create refund record
        $refund = Refund::createRefund([
            'order_id' => $orderId,
            'customer_id' => $order->customer_id,
            'refund_amount' => $amount,
            'original_amount' => $order->total_amount,
            'type' => $type,
            'reason' => $reason,
            'notes' => $notes,
            'status' => 'pending'
        ]);

        $this->info("Refund created: {$refund->refund_number}");

        // Queue the refund processing job
        ProcessRefundJob::dispatch($refund->id);

        $this->info("Refund job queued for processing");
        $this->info("Use 'php artisan queue:work' to process the refund");

        return 0;
    }
}
