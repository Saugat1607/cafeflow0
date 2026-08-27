<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Services\BillService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Exception;

class BillController extends Controller
{
    protected BillService $billService;

    public function __construct(BillService $billService)
    {
        $this->billService = $billService;
    }

    /**
     * Display bills.
     */
    public function index(Request $request): JsonResponse
    {
        try {

            $filters = [
                'date' =>
                    $request->date,

                'table_id' =>
                    $request->table_id,

                'search' =>
                    $request->search,

                'from_date' =>
                    $request->from_date,

                'to_date' =>
                    $request->to_date,

                'per_page' =>
                    $request->integer(
                        'per_page',
                        20
                    ),
            ];

            $bills =
                $this->billService->getBills(
                    $filters
                );

            return response()->json([
                'success' => true,
                'message' => 'Bills retrieved successfully.',
                'data' => $bills,
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Failed to retrieve bills.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display a single bill.
     */
    public function show(Bill $bill): JsonResponse
    {
        try {

            $bill =
                $this->billService->getBill(
                    $bill
                );

            return response()->json([
                'success' => true,
                'message' => 'Bill retrieved successfully.',
                'data' => $bill,
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    'Failed to retrieve bill.',
                'error' =>
                    $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create a new bill.
     */
    public function store(Request $request): JsonResponse
    {
        try {

            $validated = $request->validate([
                'order_id' => [
                    'required',
                    'integer',
                    'exists:orders,id',
                ],

                'discount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'tax' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'service_charge' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'payment_method' => [
                    'nullable',
                    'string',
                    'in:cash,card,esewa,khalti',
                ],

                'paid_amount' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'notes' => [
                    'nullable',
                    'string',
                ],
            ]);

            $bill =
                $this->billService->createBill(
                    $validated
                );

            return response()->json([
                'success' => true,
                'message' =>
                    'Bill created successfully.',
                'data' =>
                    $bill->load([
                        'table',
                        'items',
                        'order',
                    ]),
            ], 201);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Pay an existing bill.
     */
    public function pay(
        Request $request,
        Bill $bill
    ): JsonResponse {
        try {

            $validated = $request->validate([
                'payment_method' => [
                    'required',
                    'string',
                    'in:cash,card,esewa,khalti',
                ],

                'paid_amount' => [
                    'required',
                    'numeric',
                    'min:0',
                ],
            ]);

            $bill =
                $this->billService->payBill(
                    $bill,
                    $validated
                );

            return response()->json([
                'success' => true,
                'message' =>
                    'Payment completed successfully.',
                'data' => $bill,
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Delete a bill.
     */
    public function destroy(Bill $bill): JsonResponse
    {
        try {

            $this->billService->deleteBill(
                $bill
            );

            return response()->json([
                'success' => true,
                'message' =>
                    'Bill deleted successfully.',
            ]);

        } catch (Exception $e) {

            return response()->json([
                'success' => false,
                'message' =>
                    $e->getMessage(),
            ], 422);
        }
    }

public function recentOrders()    {
        return response()->json([
            'success' => true,
            'data' =>$this->billService->getUnbillRecentOrders(10),
        ]);
    }
}
