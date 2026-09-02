<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     */
    public function rules(): array
    {
        return [
            'invoice_number' => [
                'nullable',
                'string',
                'max:100',
                'unique:invoices,invoice_number',
            ],

            'invoice_date' => [
                'nullable',
                'date',
            ],

            'order_id' => [
                'nullable',
                'exists:orders,id',
            ],

            'table_number' => [
                'nullable',
                'string',
                'max:50',
            ],

            'customer_name' => [
                'nullable',
                'string',
                'max:255',
            ],

            'customer_phone' => [
                'nullable',
                'string',
                'max:50',
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

            'payment_method' => [
                'required',
                'in:cash,card,online',
            ],

            'payment_status' => [
                'nullable',
                'in:paid,pending,cancelled',
            ],

            'notes' => [
                'nullable',
                'string',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.menu_item_id' => [
                'nullable',
                'exists:menu_items,id',
            ],

            'items.*.item_name' => [
                'required',
                'string',
                'max:255',
            ],

            'items.*.quantity' => [
                'required',
                'numeric',
                'min:0.01',
            ],

            'items.*.unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'payment_method.required' =>
                'Payment method is required.',

            'payment_method.in' =>
                'Payment method must be cash, card, or online.',

            'items.required' =>
                'At least one invoice item is required.',

            'items.min' =>
                'Invoice must contain at least one item.',

            'items.*.item_name.required' =>
                'Item name is required.',

            'items.*.quantity.required' =>
                'Item quantity is required.',

            'items.*.quantity.min' =>
                'Item quantity must be greater than zero.',

            'items.*.unit_price.required' =>
                'Item price is required.',
        ];
    }
}
