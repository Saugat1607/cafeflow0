<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Exception;

class BillService
{
    /**
     * Get all bills with filters.
     */
    public function getBills(array $filters = [])
    {
        $query = Bill::with([
            'table',
            'items',
        ])->latest('bill_date')->latest('id');

        // Filter by date
        if (!empty($filters['date'])) {
            $query->whereDate(
                'bill_date',
                $filters['date']
            );
        }

        // Filter by table
        if (!empty($filters['table_id'])) {
            $query->where(
                'restaurant_table_id',
                $filters['table_id']
            );
        }

        // Search bill number
        if (!empty($filters['search'])) {
            $query->where(
                'bill_number',
                'ILIKE',
                '%' . $filters['search'] . '%'
            );
        }

        // From date
        if (!empty($filters['from_date'])) {
            $query->whereDate(
                'bill_date',
                '>=',
                $filters['from_date']
            );
        }

        // To date
        if (!empty($filters['to_date'])) {
            $query->whereDate(
                'bill_date',
                '<=',
                $filters['to_date']
            );
        }

        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    /**
     * Get single bill.
     */
    public function getBill(Bill $bill): Bill
    {
        return $bill->load([
            'table',
            'order',
            'items',
        ]);
    }

    /**
     * Create a bill from an order.
     */
    public function createBill(array $data): Bill
    {
        $order = Order::with([
            'items.menuItem',
            'table',
        ])->findOrFail($data['order_id']);

        // Prevent duplicate bill
        if ($order->bill()->exists()) {
            throw new Exception(
                'This order already has a bill.'
            );
        }

        // Prevent cancelled order billing
        if ($order->status === 'cancelled') {
            throw new Exception(
                'Cancelled orders cannot be billed.'
            );
        }

        // Prevent empty order billing
        if ($order->items->isEmpty()) {
            throw new Exception(
                'This order has no items.'
            );
        }

        $discount = (float) ($data['discount'] ?? 0);
        $tax = (float) ($data['tax'] ?? 0);
        $serviceCharge = (float) (
            $data['service_charge'] ?? 0
        );

        /*
        |--------------------------------------------------------------------------
        | Calculate subtotal
        |--------------------------------------------------------------------------
        */

        $subtotal = $order->items->sum(function ($item) {
            return (float) $item->quantity *
                (float) $item->unit_price;
        });

        /*
        |--------------------------------------------------------------------------
        | Calculate total
        |--------------------------------------------------------------------------
        */

        $total = $subtotal
            - $discount
            + $tax
            + $serviceCharge;

        // Prevent negative total
        $total = max(0, $total);

        /*
        |--------------------------------------------------------------------------
        | Payment
        |--------------------------------------------------------------------------
        */

        $paidAmount = (float) (
            $data['paid_amount'] ?? 0
        );

        if ($paidAmount > 0 && $paidAmount < $total) {
            $paymentStatus = 'partial';
        } elseif (
            $paidAmount >= $total &&
            $total > 0
        ) {
            $paymentStatus = 'paid';
        } else {
            $paymentStatus = 'pending';
        }

        $changeAmount = max(
            0,
            $paidAmount - $total
        );

        /*
        |--------------------------------------------------------------------------
        | Create Bill
        |--------------------------------------------------------------------------
        */

        return DB::transaction(function () use (
            $order,
            $discount,
            $tax,
            $serviceCharge,
            $subtotal,
            $total,
            $paidAmount,
            $changeAmount,
            $paymentStatus,
            $data
        ) {

            $bill = Bill::create([
                'bill_number' =>
                    $this->generateBillNumber(),

                'order_id' =>
                    $order->id,

                'restaurant_table_id' =>
                    $order->restaurant_table_id,

                'bill_date' =>
                    now()->toDateString(),

                'subtotal' =>
                    $subtotal,

                'discount' =>
                    $discount,

                'tax' =>
                    $tax,

                'service_charge' =>
                    $serviceCharge,

                'total' =>
                    $total,

                'payment_method' =>
                    $data['payment_method'] ?? null,

                'payment_status' =>
                    $paymentStatus,

                'paid_amount' =>
                    $paidAmount,

                'change_amount' =>
                    $changeAmount,

                'notes' =>
                    $data['notes'] ?? null,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create Bill Items
            |--------------------------------------------------------------------------
            */

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
                    'bill_id' =>
                        $bill->id,

                    'menu_item_id' =>
                        $item->menu_item_id,

                    'item_name' =>
                        $itemName,

                    'quantity' =>
                        $item->quantity,

                    'unit_price' =>
                        $item->unit_price,

                    'subtotal' =>
                        $itemSubtotal,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Mark Order Paid
            |--------------------------------------------------------------------------
            */

            if ($paymentStatus === 'paid') {
                $order->markPaid();
            }

            return $bill;
        });
    }

    /**
     * Pay an existing bill.
     */
    public function payBill(
        Bill $bill,
        array $data
    ): Bill {

        $paidAmount =
            (float) $data['paid_amount'];

        $total =
            (float) $bill->total;

        if ($paidAmount < $total) {
            throw new Exception(
                'Paid amount cannot be less than the bill total.'
            );
        }

        $changeAmount =
            $paidAmount - $total;

        DB::transaction(function () use (
            $bill,
            $data,
            $paidAmount,
            $changeAmount
        ) {

            $bill->update([
                'payment_method' =>
                    $data['payment_method'],

                'paid_amount' =>
                    $paidAmount,

                'change_amount' =>
                    $changeAmount,

                'payment_status' =>
                    'paid',
            ]);

            $bill->order->markPaid();
        });

        return $bill->load([
            'table',
            'items',
            'order',
        ]);
    }

    /**
     * Delete bill.
     */
    public function deleteBill(Bill $bill): void
    {
        if ($bill->payment_status === 'paid') {
            throw new Exception(
                'Paid bills cannot be deleted.'
            );
        }

        $bill->delete();
    }

    /**
     * Generate unique bill number.
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

            $number =
                ((int) end($parts)) + 1;
        }

        return sprintf(
            '%s-%04d',
            $prefix,
            $number
        );
    }

    public function getUnbillRecentOrders(int $limit = 10)
    {
        return Order::with([
            'items.menuItem',
            'table',
        ])
        ->whereDoesntHave('bill')
        ->where('status', '!=', 'cancelled')
        ->latest('created_at')
        ->limit($limit)
        ->get();
    }
}
