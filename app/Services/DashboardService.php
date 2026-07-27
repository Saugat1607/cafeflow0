<?php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getDashboardData()
    {
        $tables = RestaurantTable::with([
            'orders' => function ($query) {
                $query->whereNotIn('status', ['paid', 'cancelled'])
                    ->with('items.menuItem');
            }
        ])->orderBy('name')->get();

        $occupiedTables = RestaurantTable::whereHas('orders', function ($query) {
            $query->whereNotIn('status', ['paid', 'cancelled']);
        })->count();

        $todayRevenue = OrderItem::whereHas('order', function ($query) {
            $query->whereDate('created_at', today())
                  ->where('status', 'paid');
        })->selectRaw('SUM(quantity * unit_price) as total')
          ->value('total') ?? 0;

        return [
            'stats' => [
                'total_tables'      => RestaurantTable::count(),
                'occupied_tables'   => $occupiedTables,
                'available_tables'  => RestaurantTable::count() - $occupiedTables,

                'today_orders'      => Order::whereDate('created_at', today())->count(),

                'pending_orders'    => Order::where('status', 'pending')->count(),

                'preparing_orders'  => Order::where('status', 'preparing')->count(),

                'ready_orders'      => Order::where('status', 'ready')->count(),

                'paid_orders'       => Order::where('status', 'paid')->count(),

                'menu_items'        => MenuItem::count(),

                'today_revenue'     => $todayRevenue,
            ],

            'tables' => $tables,

            'recent_orders' => Order::with(['table','items.menuItem'])
                ->latest()
                ->take(10)
                ->get(),

            'best_selling' => OrderItem::select(
                    'menu_item_id',
                    DB::raw('SUM(quantity) as sold')
                )
                ->with('menuItem')
                ->groupBy('menu_item_id')
                ->orderByDesc('sold')
                ->take(5)
                ->get(),
        ];
    }
}
