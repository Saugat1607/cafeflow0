<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    /**
     * Display a list of invoices.
     */
    public function index(Request $request)
    {
        $query = Invoice::with(['items', 'order'])
            ->latest('invoice_date')
            ->latest('id');

        // Search
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('customer_name', 'like', "%{$search}%")
                    ->orWhere('customer_phone', 'like', "%{$search}%");
            });
        }

        // Filter by date
        if ($request->filled('date')) {
            $query->whereDate('invoice_date', $request->date);
        }

        // Filter by payment status
        if ($request->filled('payment_status')) {
            $query->where(
                'payment_status',
                $request->payment_status
            );
        }

        // Filter by payment method
        if ($request->filled('payment_method')) {
            $query->where(
                'payment_method',
                $request->payment_method
            );
        }

        return InvoiceResource::collection(
            $query->get()
        );
    }

    /**
     * Store a new invoice.
     */
    public function store(StoreInvoiceRequest $request)
    {
        $validated = $request->validated();

        $invoice = DB::transaction(function () use ($validated) {

            // Generate invoice number if not provided
            $invoiceNumber = $validated['invoice_number']
                ?? $this->generateInvoiceNumber();

            // Calculate subtotal
            $subtotal = 0;

            foreach ($validated['items'] as $item) {
                $subtotal +=
                    $item['quantity'] * $item['unit_price'];
            }

            $discount = $validated['discount'] ?? 0;
            $tax = $validated['tax'] ?? 0;

            $total = $subtotal - $discount + $tax;

            // Prevent negative total
            $total = max(0, $total);

            // Create invoice
            $invoice = Invoice::create([
                'invoice_number' => $invoiceNumber,

                'invoice_date' =>
                    $validated['invoice_date'] ?? now(),

                'order_id' =>
                    $validated['order_id'] ?? null,

                'table_number' =>
                    $validated['table_number'] ?? null,

                'customer_name' =>
                    $validated['customer_name'] ?? null,

                'customer_phone' =>
                    $validated['customer_phone'] ?? null,

                'subtotal' => $subtotal,
                'discount' => $discount,
                'tax' => $tax,
                'total' => $total,

                'payment_method' =>
                    $validated['payment_method'],

                'payment_status' =>
                    $validated['payment_status'] ?? 'paid',

                'notes' =>
                    $validated['notes'] ?? null,
            ]);

            // Create invoice items
            foreach ($validated['items'] as $item) {

                $itemSubtotal =
                    $item['quantity'] * $item['unit_price'];

                InvoiceItem::create([
                    'invoice_id' => $invoice->id,

                    'menu_item_id' =>
                        $item['menu_item_id'] ?? null,

                    'item_name' =>
                        $item['item_name'],

                    'quantity' =>
                        $item['quantity'],

                    'unit_price' =>
                        $item['unit_price'],

                    'subtotal' =>
                        $itemSubtotal,
                ]);
            }

            return $invoice;
        });

        $invoice->load([
            'items',
            'order'
        ]);

        return (new InvoiceResource($invoice))
            ->additional([
                'message' => 'Invoice created successfully.',
            ])
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display a specific invoice.
     */
    public function show(Invoice $invoice)
    {
        $invoice->load([
            'items',
            'order'
        ]);

        return new InvoiceResource($invoice);
    }

    /**
     * Update invoice.
     */
    public function update(Request $request, Invoice $invoice)
    {
        $validated = $request->validate([
            'customer_name' => [
                'nullable',
                'string',
                'max:255'
            ],

            'customer_phone' => [
                'nullable',
                'string',
                'max:50'
            ],

            'table_number' => [
                'nullable',
                'string',
                'max:50'
            ],

            'discount' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'tax' => [
                'nullable',
                'numeric',
                'min:0'
            ],

            'payment_method' => [
                'nullable',
                'in:cash,card,online'
            ],

            'payment_status' => [
                'nullable',
                'in:paid,pending,cancelled'
            ],

            'notes' => [
                'nullable',
                'string'
            ],
        ]);

        $invoice->update($validated);

        return (new InvoiceResource(
            $invoice->load('items')
        ))->additional([
            'message' => 'Invoice updated successfully.',
        ]);
    }

    /**
     * Delete invoice.
     */
    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return response()->json([
            'success' => true,
            'message' => 'Invoice deleted successfully.',
        ]);
    }

    /**
     * Generate unique invoice number.
     */
    private function generateInvoiceNumber(): string
    {
        $date = now()->format('Ymd');

        $lastInvoice = Invoice::whereDate(
            'invoice_date',
            now()->toDateString()
        )
            ->latest('id')
            ->first();

        $number = $lastInvoice
            ? ((int) substr($lastInvoice->invoice_number, -4)) + 1
            : 1;

        return 'INV-' . $date . '-' . str_pad(
            $number,
            4,
            '0',
            STR_PAD_LEFT
        );
    }
}
