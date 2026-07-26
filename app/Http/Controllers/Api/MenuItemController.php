<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreMenuItemRequest;
use App\Http\Requests\updateMenuItemRequest;
use App\Models\MenuItem;
use App\Services\MenuItemService;
use Illuminate\Http\JsonResponse;


class MenuItemController extends Controller
{
    public function __construct(
        protected MenuItemService $menuItemService
    ) {}

    public function index(): JsonResponse

    {
        try {
            $menuItems = $this->menuItemService->index();

            return response() ->json([
                'success' => true,
                'message' => 'Menu items fetched successfully.',
                'data' => $menuItems
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success'=> false,
                'message' => 'Failed to fetch menu items.',
                'error' => $e->getMessage()
                ],500);
        }

    }

    // store a new item
    public function store(StoreMenuItemRequest $request): JsonResponse
    {
        try {
 $menuItem = $this->menuItemService->create($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Menu item created successfully.',
                'data' => $menuItem
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create menu item.',
                'error' => $e->getMessage()
            ], 500);
        }

    }
    //show a specific item
    public function show(MenuItem $menu): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $menu,
        ], 200);
    }

    //update a menu

    public function update(updateMenuItemRequest $request, MenuItem $menuItem): JsonResponse
    {
        try {
            $menuItem = $this->menuItemService->update($menuItem, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'Menu item updated successfully.',
                'data' => $menuItem
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update menu item.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    //delete a menu item

    public function destroy(MenuItem $menuItem): JsonResponse
    {
        try {
            $this->menuItemService->delete($menuItem);
            return response()->json([
                'success' => true,
                'message' => 'Menu item deleted successfully.'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete menu item.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
