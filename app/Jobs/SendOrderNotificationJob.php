<?php

namespace App\Jobs;

use App\Models\Notification;
use App\Models\Order;
use App\Models\Customer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendOrderNotificationJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 60; // 1 minute
    public $tries = 3;

    private int $orderId;
    private string $type;
    private string $channel;

    /**
     * Create a new job instance.
     */
    public function __construct(int $orderId, string $type, string $channel = 'log')
    {
        $this->orderId = $orderId;
        $this->type = $type;
        $this->channel = $channel;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $order = Order::with(['customer', 'orderItems.product'])->find($this->orderId);
        
        if (!$order) {
            Log::error("Order not found for notification: {$this->orderId}");
            return;
        }

        // Create notification record
        $notification = $this->createNotificationRecord($order);
        
        try {
            // Send notification based on channel
            switch ($this->channel) {
                case 'email':
                    $this->sendEmailNotification($order, $notification);
                    break;
                case 'log':
                default:
                    $this->sendLogNotification($order, $notification);
                    break;
            }
            
            $notification->markAsSent();
            
        } catch (\Exception $e) {
            $notification->markAsFailed($e->getMessage());
            Log::error("Notification failed for order {$this->orderId}: " . $e->getMessage());
            throw $e;
        }
    }

    private function createNotificationRecord(Order $order): Notification
    {
        $message = $this->generateMessage($order);
        $metadata = $this->generateMetadata($order);

        return Notification::create([
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => $this->type,
            'channel' => $this->channel,
            'status' => 'pending',
            'message' => $message,
            'metadata' => $metadata
        ]);
    }

    private function generateMessage(Order $order): string
    {
        $customerName = $order->customer->name;
        $orderNumber = $order->order_number;
        $totalAmount = number_format($order->total_amount, 2);
        
        switch ($this->type) {
            case 'success':
                return "Order #{$orderNumber} has been processed successfully! Total: $" . $totalAmount;
            case 'failure':
                return "Order #{$orderNumber} processing failed. Please contact support.";
            case 'processing':
                return "Order #{$orderNumber} is being processed. We'll notify you when it's complete.";
            default:
                return "Order #{$orderNumber} status update.";
        }
    }

    private function generateMetadata(Order $order): array
    {
        return [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'order_number' => $order->order_number,
            'status' => $order->status,
            'payment_status' => $order->payment_status,
            'total_amount' => $order->total_amount,
            'customer_name' => $order->customer->name,
            'customer_email' => $order->customer->email,
            'items_count' => $order->orderItems->count(),
            'processed_at' => now()->toISOString()
        ];
    }

    private function sendEmailNotification(Order $order, Notification $notification): void
    {
        // For now, we'll log the email instead of actually sending
        // In production, you would use Laravel Mail here
        Log::info("EMAIL NOTIFICATION", [
            'to' => $order->customer->email,
            'subject' => "Order {$order->order_number} - {$this->type}",
            'message' => $notification->message,
            'metadata' => $notification->metadata
        ]);
    }

    private function sendLogNotification(Order $order, Notification $notification): void
    {
        Log::info("ORDER NOTIFICATION", [
            'order_id' => $order->id,
            'customer_id' => $order->customer_id,
            'type' => $this->type,
            'channel' => $this->channel,
            'message' => $notification->message,
            'metadata' => $notification->metadata
        ]);
    }
}
