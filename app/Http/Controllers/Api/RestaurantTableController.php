<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RestaurantTableController extends Controller
{
    /**
     * Display all restaurant tables.
     */
    public function index(): JsonResponse
    {
        try {

            $tables = RestaurantTable::orderBy('name')->get();

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
    public function store(Request $request): JsonResponse
    {
        try {

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:restaurant_tables,name',
                'seats' => 'required|integer|min:1|max:50',
            ]);

            $validated['status'] = 'available';

            $table = RestaurantTable::create($validated);

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

            $table->load([
                'orders.items.menuItem'
            ]);

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
    public function update(Request $request, RestaurantTable $table): JsonResponse
    {
        try {

            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:restaurant_tables,name,' . $table->id,
                'seats' => 'required|integer|min:1|max:50',
                'status' => 'required|in:available,occupied,reserved',
            ]);

            $table->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Table updated successfully.',
                'data' => $table->fresh()
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

            if ($table->orders()->whereNotIn('status', ['paid', 'cancelled'])->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete a table with an active order.'
                ], 409);
            }

            $table->delete();

            return response()->json([
                'success' => true,
                'message' => 'Table deleted successfully.'
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete table.',
                'error' => $e->getMessage()
            ], 500);

        }
    }
}