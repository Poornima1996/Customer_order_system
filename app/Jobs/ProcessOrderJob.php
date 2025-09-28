<?php

namespace App\Jobs;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Jobs\SendOrderNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProcessOrderJob implements ShouldQueue
{
    use Queueable;

    public $timeout = 300; // 5 minutes
    public $tries = 3;

    private array $orderData;

    /**
     * Create a new job instance.
     */
    public function __construct(array $orderData)
    {
        $this->orderData = $orderData;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            try {
                // Step 1: Create or find customer
                $customer = $this->createOrFindCustomer();
                
                // Step 2: Create order
                $order = $this->createOrder($customer);
                
                // Send processing notification
                SendOrderNotificationJob::dispatch($order->id, 'processing', 'log');
                
                // Step 3: Reserve stock
                if (!$this->reserveStock($order)) {
                    throw new \Exception('Insufficient stock for order');
                }
                
                // Step 4: Simulate payment
                $paymentResult = $this->simulatePayment($order);
                
                if ($paymentResult['success']) {
                    // Step 5: Finalize order
                    $this->finalizeOrder($order, $paymentResult);
                    
                    // Send success notification
                    SendOrderNotificationJob::dispatch($order->id, 'success', 'log');
                } else {
                    // Step 6: Rollback order
                    $this->rollbackOrder($order);
                    
                    // Send failure notification
                    SendOrderNotificationJob::dispatch($order->id, 'failure', 'log');
                }
                
            } catch (\Exception $e) {
                Log::error('Order processing failed', [
                    'order_data' => $this->orderData,
                    'error' => $e->getMessage()
                ]);
                throw $e;
            }
        });
    }

    private function createOrFindCustomer(): Customer
    {
        $email = $this->orderData['customer_email'] ?? $this->orderData['email'];
        $name = $this->orderData['customer_name'] ?? $this->orderData['name'];
        
        return Customer::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'phone' => $this->orderData['phone'] ?? null,
                'address' => $this->orderData['address'] ?? null,
            ]
        );
    }

    private function createOrder(Customer $customer): Order
    {
        $orderNumber = 'ORD-' . strtoupper(Str::random(8));
        
        $order = Order::create([
            'customer_id' => $customer->id,
            'order_number' => $orderNumber,
            'total_amount' => $this->calculateTotalAmount(),
            'status' => 'pending',
            'payment_status' => 'pending',
        ]);

        // Create order items
        $this->createOrderItems($order);
        
        return $order;
    }

    private function createOrderItems(Order $order): void
    {
        $products = json_decode($this->orderData['products'] ?? '[]', true);
        
        foreach ($products as $productData) {
            $product = Product::where('sku', $productData['sku'])->first();
            
            if ($product) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $productData['quantity'],
                    'unit_price' => $product->price,
                    'total_price' => $product->price * $productData['quantity'],
                ]);
            }
        }
    }

    private function calculateTotalAmount(): float
    {
        $total = 0;
        $products = json_decode($this->orderData['products'] ?? '[]', true);
        
        foreach ($products as $productData) {
            $product = Product::where('sku', $productData['sku'])->first();
            if ($product) {
                $total += $product->price * $productData['quantity'];
            }
        }
        
        return $total;
    }

    private function reserveStock(Order $order): bool
    {
        foreach ($order->orderItems as $item) {
            if (!$item->product->reserveStock($item->quantity)) {
                return false;
            }
        }
        return true;
    }

    private function simulatePayment(Order $order): array
    {
        // Simulate payment processing
        $success = rand(1, 10) > 2; // 80% success rate
        
        return [
            'success' => $success,
            'transaction_id' => $success ? 'TXN-' . Str::random(12) : null,
            'payment_method' => 'credit_card',
            'processed_at' => now(),
        ];
    }

    private function finalizeOrder(Order $order, array $paymentResult): void
    {
        $order->update([
            'status' => 'paid',
            'payment_status' => 'paid',
            'payment_data' => $paymentResult,
            'paid_at' => now(),
        ]);

        // Update customer stats
        $order->customer->updateStats();
        
        Log::info('Order finalized successfully', ['order_id' => $order->id]);
    }

    private function rollbackOrder(Order $order): void
    {
        // Release reserved stock
        $order->releaseStock();
        
        $order->update([
            'status' => 'cancelled',
            'payment_status' => 'failed',
        ]);
        
        Log::info('Order rolled back', ['order_id' => $order->id]);
    }
}
