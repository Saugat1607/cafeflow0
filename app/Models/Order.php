<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = ['restaurant_table_id', 'status', 'notes', 'paid_at'];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function table(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class, 'restaurant_table_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /** Grand total for the bill. */
    public function total(): float
    {
        return $this->items->sum(fn (OrderItem $item) => $item->quantity * $item->unit_price);
    }

    public function markPaid(): void
    {
        $this->update(['status' => 'paid', 'paid_at' => now()]);
        $this->table->update(['status' => 'available']);
    }

    public function bill(): HasOne
{
    return $this->hasOne(Bill::class);
}
}
