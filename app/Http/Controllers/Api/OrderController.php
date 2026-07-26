<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    /**
     * Get table details and available menu items.
     */
    public function create(RestaurantTable $table): JsonResponse
    {
        try {

            $menuItems = MenuItem::where('is_available', true)
                ->orderBy('category')
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'table' => $table,
                'menu_items' => $menuItems
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch order data.',
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Store a new order.
     */
    public function store(Request $request): JsonResponse
    {
        try {

            $validated = $request->validate([
                'restaurant_table_id' => 'required|exists:restaurant_tables,id',
                'items' => 'required|array|min:1',
                'items.*.menu_item_id' => 'required|exists:menu_items,id',
                'items.*.quantity' => 'required|integer|min:1|max:50',
            ]);

            $order = Order::create([
                'restaurant_table_id' => $validated['restaurant_table_id'],
                'status' => 'open',
            ]);

            foreach ($validated['items'] as $item) {

                $menuItem = MenuItem::findOrFail($item['menu_item_id']);

                $order->items()->create([
                    'menu_item_id' => $menuItem->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $menuItem->price,
                ]);
            }

            $order->table->update([
                'status' => 'occupied'
            ]);

            $order->load([
                'table',
                'items.menuItem'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order.',
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Show a single order.
     */
    public function show(Order $order): JsonResponse
    {
        try {

            $order->load([
                'table',
                'items.menuItem'
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'total' => $order->total()
                ]
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, Order $order): JsonResponse
    {
        try {

            $validated = $request->validate([
                'status' => 'required|in:open,preparing,served,paid,cancelled',
            ]);

            if ($validated['status'] === 'paid') {

                $order->markPaid();

            } else {

                $order->update([
                    'status' => $validated['status']
                ]);

                if ($validated['status'] === 'cancelled') {
                    $order->table->update([
                        'status' => 'available'
                    ]);
                }
            }

            $order->load([
                'table',
                'items.menuItem'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => $order
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order.',
                'error' => $e->getMessage()
            ], 500);

        }
    }

    /**
     * Delete an order.
     */
    public function destroy(Order $order): JsonResponse
    {
        try {

            $order->items()->delete();

            $order->table->update([
                'status' => 'available'
            ]);

            $order->delete();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully.'
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order.',
                'error' => $e->getMessage()
            ], 500);

        }
    }
}