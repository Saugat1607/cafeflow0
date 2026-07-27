<?php
// app/Http/Controllers/Api/OrderController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\RestaurantTable;
use App\Services\OrderService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService)
    {
    }

    /**
     * Get table details and available menu items.
     * GET /api/tables/{table}/order/create
     */
    public function create(RestaurantTable $table): JsonResponse
    {
        try {

            $data = $this->orderService->getOrderCreationData($table);

            return response()->json([
                'success' => true,
                'table' => $data['table'],
                'menu_items' => $data['menu_items'],
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch order data.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * List orders. Supports optional ?status= and ?table_id= filters,
     * handy for a live "kitchen board" or orders dashboard on the frontend.
     * GET /api/orders
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $orders = $this->orderService->listOrders(
                status: $request->query('status'),
                tableId: $request->query('table_id') ? (int) $request->query('table_id') : null,
            );

            return response()->json([
                'success' => true,
                'data' => $orders,
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to fetch orders.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Store a new order.
     * POST /api/orders
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {

            $order = $this->orderService->createOrder($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Order placed successfully.',
                'data' => $order,
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Show a single order.
     * GET /api/orders/{order}
     */
    public function show(Order $order): JsonResponse
    {
        try {

            $details = $this->orderService->getOrderDetails($order);

            return response()->json([
                'success' => true,
                'data' => $details,
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Order not found.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Update order status.
     * PATCH /api/orders/{order}/status
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): JsonResponse
    {
        try {

            $order = $this->orderService->updateStatus($order, $request->validated('status'));

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => $order,
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update order.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }

    /**
     * Delete an order.
     * DELETE /api/orders/{order}
     */
    public function destroy(Order $order): JsonResponse
    {
        try {

            $this->orderService->deleteOrder($order);

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully.',
            ], 200);

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order.',
                'error' => $e->getMessage(),
            ], 500);

        }
    }
}
