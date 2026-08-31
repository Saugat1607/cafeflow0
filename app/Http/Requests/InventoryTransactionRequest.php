<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryTransactionRequest extends FormRequest
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
            'inventory_item_id' => [
                'required',
                'integer',
                'exists:inventory_items,id',
            ],

            'quantity' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'unit_cost' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'reason' => [
                'nullable',
                'string',
                'max:255',
            ],

            'reference' => [
                'nullable',
                'string',
                'max:255',
            ],

            'transaction_date' => [
                'required',
                'date',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'inventory_item_id.required' =>
                'Inventory item is required.',

            'inventory_item_id.exists' =>
                'The selected inventory item does not exist.',

            'type.required' =>
                'Transaction type is required.',

            'type.in' =>
                'Transaction type must be in, out, or adjustment.',

            'quantity.required' =>
                'Transaction quantity is required.',

            'quantity.gt' =>
                'Transaction quantity must be greater than zero.',

            'transaction_date.required' =>
                'Transaction date is required.',
        ];
    }
}
