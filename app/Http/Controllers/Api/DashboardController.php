<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(
        protected DashboardService $dashboardService
     ) {
    }

    public function index(): JsonResponse
    {
        try {
            $tables = $this->dashboardService->getDashboardData();


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
