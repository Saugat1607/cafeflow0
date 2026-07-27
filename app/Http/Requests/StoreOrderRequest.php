<?php
// app/Http/Requests/StoreOrderRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Anyone hitting this endpoint is allowed to place an order.
     * Tighten this up with real auth/policy checks if orders
     * become tied to logged-in staff or customer accounts.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'restaurant_table_id' => ['required', 'exists:restaurant_tables,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.menu_item_id' => ['required', 'exists:menu_items,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:50'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'restaurant_table_id.required' => 'A table must be selected to place an order.',
            'restaurant_table_id.exists' => 'The selected table does not exist.',
            'items.required' => 'An order must contain at least one item.',
            'items.min' => 'An order must contain at least one item.',
            'items.*.menu_item_id.exists' => 'One or more selected menu items do not exist.',
            'items.*.quantity.max' => 'Quantity per item cannot exceed 50.',
        ];
    }
}
