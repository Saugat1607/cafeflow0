<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillController extends Controller
{
    /**
     * List bills.
     *
     * Filters:
     * date
     * table
     * search
     */
    public function index(Request $request): JsonResponse
    {
        $query = Bill::with([
            'table',
            'items',
        ])->latest('bill_date')->latest('id');

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('bill_date', $request->date);
        }

        // Filter by table
        if ($request->filled('table_id')) {
            $query->where(
                'restaurant_table_id',
                $request->table_id
            );
        }

        // Search bill number
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(
                'bill_number',
                'ILIKE',
                "%{$search}%"
            );
        }

        // Date range
        if ($request->filled('from_date')) {
            $query->whereDate(
                'bill_date',
                '>=',
                $request->from_date
            );
        }

        if ($request->filled('to_date')) {
            $query->whereDate(
                'bill_date',
                '<=',
                $request->to_date
            );
        }

        $bills = $query->paginate(
            $request->integer('per_page', 20)
        );

        return response()->json($bills);
    }

    /**
     * Show a single bill.
     */
    public function show(Bill $bill): JsonResponse
    {
        $bill->load([
            'table',
            'order',
            'items',
        ]);

        return response()->json([
            'bill' => $bill,
        ]);
    }

    /**
     * Create a bill from an order.
     */
    public function store(Request $request): JsonResponse
    {
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

        $order = Order::with([
            'items.menuItem',
            'table',
        ])->findOrFail($validated['order_id']);

        // Don't create another bill for the same order
        if ($order->bill()->exists()) {
            return response()->json([
                'message' => 'This order already has a bill.',
            ], 422);
        }

        if ($order->status === 'cancelled') {
            return response()->json([
                'message' => 'Cancelled orders cannot be billed.',
            ], 422);
        }

        if ($order->items->isEmpty()) {
            return response()->json([
                'message' => 'This order has no items.',
            ], 422);
        }

        $discount = (float) ($validated['discount'] ?? 0);
        $tax = (float) ($validated['tax'] ?? 0);
        $serviceCharge = (float) (
            $validated['service_charge'] ?? 0
        );

        $subtotal = $order->items->sum(function ($item) {
            return (float) $item->quantity *
                (float) $item->unit_price;
        });

        $total = $subtotal
            - $discount
            + $tax
            + $serviceCharge;

        if ($total < 0) {
            $total = 0;
        }

        $paidAmount = (float) (
            $validated['paid_amount'] ?? 0
        );

        if ($paidAmount > 0 && $paidAmount < $total) {
            $paymentStatus = 'partial';
        } elseif ($paidAmount >= $total && $total > 0) {
            $paymentStatus = 'paid';
        } else {
            $paymentStatus = 'pending';
        }

        $changeAmount = max(
            0,
            $paidAmount - $total
        );

        $bill = DB::transaction(function () use (
            $order,
            $discount,
            $tax,
            $serviceCharge,
            $subtotal,
            $total,
            $paidAmount,
            $changeAmount,
            $paymentStatus,
            $validated
        ) {
            $bill = Bill::create([
                'bill_number' => $this->generateBillNumber(),

                'order_id' => $order->id,

                'restaurant_table_id' =>
                    $order->restaurant_table_id,

                'bill_date' => now()->toDateString(),

                'subtotal' => $subtotal,

                'discount' => $discount,

                'tax' => $tax,

                'service_charge' => $serviceCharge,

                'total' => $total,

                'payment_method' =>
                    $validated['payment_method'] ?? null,

                'payment_status' => $paymentStatus,

                'paid_amount' => $paidAmount,

                'change_amount' => $changeAmount,

                'notes' =>
                    $validated['notes'] ?? null,
            ]);

            foreach ($order->items as $item) {
                $itemName = 'Menu Item';

                if ($item->menuItem) {
                    $itemName =
                        $item->menuItem->name;
                }

                $itemSubtotal =
                    (float) $item->quantity *
                    (float) $item->unit_price;

                BillItem::create([
                    'bill_id' => $bill->id,

                    'menu_item_id' =>
                        $item->menu_item_id,

                    'item_name' => $itemName,

                    'quantity' =>
                        $item->quantity,

                    'unit_price' =>
                        $item->unit_price,

                    'subtotal' =>
                        $itemSubtotal,
                ]);
            }

            // Only mark order paid if payment completed
            if ($paymentStatus === 'paid') {
                $order->markPaid();
            }

            return $bill;
        });

        $bill->load([
            'table',
            'items',
            'order',
        ]);

        return response()->json([
            'message' => 'Bill created successfully.',
            'bill' => $bill,
        ], 201);
    }

    /**
     * Pay an existing pending/partial bill.
     */
    public function pay(
        Request $request,
        Bill $bill
    ): JsonResponse {
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

        $paidAmount =
            (float) $validated['paid_amount'];

        $total =
            (float) $bill->total;

        if ($paidAmount < $total) {
            return response()->json([
                'message' =>
                    'Paid amount cannot be less than the bill total.',
            ], 422);
        }

        $changeAmount =
            $paidAmount - $total;

        DB::transaction(function () use (
            $bill,
            $validated,
            $paidAmount,
            $changeAmount
        ) {
            $bill->update([
                'payment_method' =>
                    $validated['payment_method'],

                'paid_amount' =>
                    $paidAmount,

                'change_amount' =>
                    $changeAmount,

                'payment_status' =>
                    'paid',
            ]);

            $bill->order->markPaid();
        });

        $bill->load([
            'table',
            'items',
            'order',
        ]);

        return response()->json([
            'message' => 'Payment completed successfully.',
            'bill' => $bill,
        ]);
    }

    /**
     * Delete a bill.
     */
    public function destroy(Bill $bill): JsonResponse
    {
        if ($bill->payment_status === 'paid') {
            return response()->json([
                'message' =>
                    'Paid bills cannot be deleted.',
            ], 422);
        }

        $bill->delete();

        return response()->json([
            'message' => 'Bill deleted successfully.',
        ]);
    }

    /**
     * Generate bill number.
     */
    private function generateBillNumber(): string
    {
        $prefix = 'INV-' . now()->format('Ymd');

        $lastBill = Bill::where(
            'bill_number',
            'like',
            $prefix . '-%'
        )
            ->latest('id')
            ->first();

        if (!$lastBill) {
            $number = 1;
        } else {
            $parts = explode(
                '-',
                $lastBill->bill_number
            );

            $number = ((int) end($parts)) + 1;
        }

        return sprintf(
            '%s-%04d',
            $prefix,
            $number
        );
    }
}
