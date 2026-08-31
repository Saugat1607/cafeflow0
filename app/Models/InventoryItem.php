<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category',
        'unit',
        'current_stock',
        'minimum_stock',
        'cost_per_unit',
        'supplier',
        'description',
        'is_active',
    ];

    protected $casts = [
        'current_stock' => 'decimal:3',
        'minimum_stock' => 'decimal:3',
        'cost_per_unit' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * All stock transactions for this item.
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(InventoryTransaction::class);
    }

    /**
     * Check whether the item is low in stock.
     */
    public function isLowStock(): bool
    {
        return $this->current_stock > 0
            && $this->current_stock <= $this->minimum_stock;
    }

    /**
     * Check whether the item is out of stock.
     */
    public function isOutOfStock(): bool
    {
        return $this->current_stock <= 0;
    }

    /**
     * Get the total value of the current stock.
     */
    public function getStockValueAttribute(): float
    {
        return (float) $this->current_stock * (float) $this->cost_per_unit;
    }
}
