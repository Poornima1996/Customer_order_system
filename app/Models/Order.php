<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'order_number',
        'total_amount',
        'status',
        'payment_status',
        'payment_data',
        'paid_at'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payment_data' => 'array',
        'paid_at' => 'datetime'
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function reserveStock(): bool
    {
        foreach ($this->orderItems as $item) {
            if (!$item->product->reserveStock($item->quantity)) {
                return false;
            }
        }
        return true;
    }

    public function releaseStock(): void
    {
        foreach ($this->orderItems as $item) {
            $item->product->releaseStock($item->quantity);
        }
    }
}
