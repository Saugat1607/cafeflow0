<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InventoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'category' => [
                'nullable',
                'string',
                'max:100',
            ],

            'unit' => [
                'required',
                'string',
                'max:50',
            ],

            'current_stock' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'minimum_stock' => [
                'required',
                'numeric',
                'min:0',
            ],

            'cost_per_unit' => [
                'required',
                'numeric',
                'min:0',
            ],

            'supplier' => [
                'nullable',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Inventory item name is required.',

            'unit.required' => 'Please specify the inventory unit.',

            'minimum_stock.required' => 'Minimum stock level is required.',

            'minimum_stock.min' => 'Minimum stock cannot be negative.',

            'current_stock.min' => 'Current stock cannot be negative.',

            'cost_per_unit.required' => 'Cost per unit is required.',

            'cost_per_unit.min' => 'Cost per unit cannot be negative.',
        ];
    }
}
