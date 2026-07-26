<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MenuItemController extends Controller
{
    /**
     * Display all menu items.
     */
    public function index(): JsonResponse
    {
        try {
            $menuItems = MenuItem::orderBy('category')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Menu items fetched successfully.',
                'data' => $menuItems,
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch menu items.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a new menu item.
     */
    public function store(Request $request): JsonResponse
    {
        try {

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:255',
                'price' => 'required|numeric|min:0|max:9999.99',
                'is_available' => 'nullable|boolean',
            ]);

            if (!isset($validated['is_available'])) {
                $validated['is_available'] = true;
            }

            $menuItem = MenuItem::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Menu item created successfully.',
                'data' => $menuItem,
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to create menu item.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a single menu item.
     */
    public function show(MenuItem $menu): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $menu,
        ], 200);
    }

    /**
     * Update a menu item.
     */
    public function update(Request $request, MenuItem $menu): JsonResponse
    {
        try {

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'category' => 'required|string|max:255',
                'price' => 'required|numeric|min:0|max:9999.99',
                'is_available' => 'nullable|boolean',
            ]);

            if (isset($validated['is_available'])) {
                $validated['is_available'] = $request->boolean('is_available');
            }

            $menu->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Menu item updated successfully.',
                'data' => $menu->fresh(),
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update menu item.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a menu item.
     */
    public function destroy(MenuItem $menu): JsonResponse
    {
        try {

            $menu->delete();

            return response()->json([
                'success' => true,
                'message' => 'Menu item deleted successfully.',
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu item.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
