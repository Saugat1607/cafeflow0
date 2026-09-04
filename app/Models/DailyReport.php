<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_date',
        'total_sales',
        'total_expenses',
        'net_profit',
        'total_bills',
        'total_orders',
        'cash_sales',
        'card_sales',
        'esewa_sales',
        'other_sales',
    ];

    protected $casts = [
        'report_date' => 'date',
        'total_sales' => 'decimal:2',
        'total_expenses' => 'decimal:2',
        'net_profit' => 'decimal:2',
        'cash_sales' => 'decimal:2',
        'card_sales' => 'decimal:2',
        'esewa_sales' => 'decimal:2',
        'other_sales' => 'decimal:2',
    ];
}
