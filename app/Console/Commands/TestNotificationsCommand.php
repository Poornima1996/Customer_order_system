<?php

namespace App\Console\Commands;

use App\Jobs\SendOrderNotificationJob;
use App\Models\Notification;
use App\Models\Order;
use Illuminate\Console\Command;

class TestNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:test {--order-id=} {--type=success} {--channel=log}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test notification system with sample data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $orderId = $this->option('order-id');
        $type = $this->option('type');
        $channel = $this->option('channel');

        if ($orderId) {
            // Test with specific order
            $order = Order::find($orderId);
            if (!$order) {
                $this->error("Order not found: {$orderId}");
                return 1;
            }
        } else {
            // Create a test order
            $order = $this->createTestOrder();
            $this->info("Created test order: {$order->id}");
        }

        $this->info("Testing notification for order {$order->id}");
        $this->info("Type: {$type}, Channel: {$channel}");

        // Dispatch notification job
        SendOrderNotificationJob::dispatch($order->id, $type, $channel);

        $this->info("Notification job dispatched successfully!");
        $this->info("Check the logs and notifications table for results.");

        // Show recent notifications
        $this->showRecentNotifications();

        return 0;
    }

    private function createTestOrder()
    {
        // Create test customer
        $customer = \App\Models\Customer::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test Customer',
                'phone' => '555-0123',
                'address' => '123 Test Street'
            ]
        );

        // Create test order
        $order = \App\Models\Order::create([
            'customer_id' => $customer->id,
            'order_number' => 'TEST-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'total_amount' => 99.99,
            'status' => 'pending',
            'payment_status' => 'pending'
        ]);

        // Create test order item
        $product = \App\Models\Product::first();
        if ($product) {
            \App\Models\OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'quantity' => 1,
                'unit_price' => $product->price,
                'total_price' => $product->price
            ]);
        }

        return $order;
    }

    private function showRecentNotifications(): void
    {
        $notifications = Notification::with(['order', 'customer'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        if ($notifications->isEmpty()) {
            $this->info("No notifications found.");
            return;
        }

        $this->info("\nRecent Notifications:");
        $this->table(
            ['ID', 'Order ID', 'Type', 'Channel', 'Status', 'Created'],
            $notifications->map(function ($notification) {
                return [
                    $notification->id,
                    $notification->order_id,
                    $notification->type,
                    $notification->channel,
                    $notification->status,
                    $notification->created_at->format('Y-m-d H:i:s')
                ];
            })
        );
    }
}
