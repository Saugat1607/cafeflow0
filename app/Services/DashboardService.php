<?php

namespace App\Services;

use App\Models\RestaurantTable;


class DashboardService
{
    public function getDashboardData()
    {
        return RestaurantTable::with([
            'orders' => function ($query) {
                $query->whereNotIn('status', ['paid', 'cancelled'])
                      ->with('items.menuItem');
            }
        ])
        ->orderBy('name')
        ->get();
    }
}
