<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{

    public function index(): JsonResponse
    {
        try {

            $tables = RestaurantTable::with([
                'orders' => function ($query) {
                    $query->whereNotIn('status', ['paid', 'cancelled'])
                          ->with('items.menuItem');
                }
            ])
            ->orderBy('name')
            ->get();

            return response()->json([
                'success' => true,
                'message' => 'Dashboard data fetched successfully.',
                'data' => $tables
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch dashboard data.',
                'error' => $e->getMessage()
            ], 500);

        }
    }
}
