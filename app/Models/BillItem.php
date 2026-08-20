<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BillItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'bill_id',
        'menu_item_id',
        'item_name',
        'quantity',
        'unit_price',
        'subtotal',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'subtotal' => 'decimal:2',
    ];

    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }
}
