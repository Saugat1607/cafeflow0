<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_number',
        'order_id',
        'restaurant_table_id',
        'bill_date',
        'subtotal',
        'discount',
        'tax',
        'service_charge',
        'total',
        'payment_method',
        'payment_status',
        'paid_amount',
        'change_amount',
        'notes',
    ];

    protected $casts = [
        'bill_date' => 'date',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'service_charge' => 'decimal:2',
        'total' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'change_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(
            RestaurantTable::class,
            'restaurant_table_id'
        );
    }

    public function items(): HasMany
    {
        return $this->hasMany(BillItem::class);
    }
}
