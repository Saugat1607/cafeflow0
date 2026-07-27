<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestaurantTableRequest;
use App\Models\RestaurantTable;
use App\Services\RestaurantTableService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class RestaurantTableController extends Controller
{
    public function __construct(
        protected RestaurantTableService $restaurantTableService
    ) {}

    /**
     * Display all restaurant tables.
     */
    public function index(): JsonResponse
    {
        try {

            $tables = $this->restaurantTableService->getAllTables();

            return response()->json([
                'success' => true,
                'message' => 'Tables fetched successfully.',
                'data' => $tables
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tables.',
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Store a new table.
     */
    public function store(RestaurantTableRequest $request): JsonResponse
    {
        try {

            $table = $this->restaurantTableService->createTable($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Table created successfully.',
                'data' => $table
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to create table.',
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Display a single table.
     */
    public function show(RestaurantTable $table): JsonResponse
    {
        try {

            $table = $this->restaurantTableService->getTableWithOrders($table);

            return response()->json([
                'success' => true,
                'data' => $table
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Table not found.',
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Update a table.
     */
    public function update(RestaurantTableRequest $request, RestaurantTable $table): JsonResponse
    {
        try {

            $table = $this->restaurantTableService->updateTable($table, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Table updated successfully.',
                'data' => $table
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update table.',
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Delete a table.
     */
    public function destroy(RestaurantTable $table): JsonResponse
    {
        try {

            $this->restaurantTableService->deleteTable($table);

            return response()->json([
                'success' => true,
                'message' => 'Table deleted successfully.'
            ], 200);

        } catch (RuntimeException $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 409);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete table.',
                'error' => $e->getMessage()
            ], 500);

        }
    }
}
