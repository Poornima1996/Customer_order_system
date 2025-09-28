<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'total_spent',
        'total_orders'
    ];

    protected $casts = [
        'total_spent' => 'decimal:2'
    ];

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function updateStats(): void
    {
        $this->update([
            'total_spent' => $this->orders()->where('status', '!=', 'cancelled')->sum('total_amount'),
            'total_orders' => $this->orders()->where('status', '!=', 'cancelled')->count()
        ]);
    }
}
