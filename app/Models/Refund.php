<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Refund extends Model
{
    protected $fillable = [
        'order_id',
        'customer_id',
        'refund_number',
        'refund_amount',
        'original_amount',
        'type',
        'status',
        'reason',
        'notes',
        'refund_data',
        'transaction_id',
        'processed_at',
        'completed_at',
        'processed_by'
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'original_amount' => 'decimal:2',
        'refund_data' => 'array',
        'processed_at' => 'datetime',
        'completed_at' => 'datetime'
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public static function createRefund(array $data): self
    {
        return static::create(array_merge($data, [
            'refund_number' => 'REF-' . strtoupper(Str::random(8))
        ]));
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => 'processing',
            'processed_at' => now()
        ]);
    }

    public function markAsCompleted(string $transactionId = null, array $refundData = []): void
    {
        $this->update([
            'status' => 'completed',
            'transaction_id' => $transactionId,
            'refund_data' => $refundData,
            'completed_at' => now()
        ]);
    }

    public function markAsFailed(string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason ? $this->notes . "\nFailed: " . $reason : $this->notes
        ]);
    }

    public function isFullRefund(): bool
    {
        return $this->type === 'full';
    }

    public function isPartialRefund(): bool
    {
        return $this->type === 'partial';
    }

    public function getRefundPercentage(): float
    {
        return $this->original_amount > 0 
            ? ($this->refund_amount / $this->original_amount) * 100 
            : 0;
    }
}
