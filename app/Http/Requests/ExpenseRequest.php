<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized.
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
            'title' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:0.01',
                'max:999999999.99',
            ],

            'category' => [
                'required',
                'string',
                Rule::in([
                    'Rent',
                    'Electricity',
                    'Water',
                    'Gas',
                    'Raw Materials',
                    'Salary',
                    'Maintenance',
                    'Marketing',
                    'Transportation',
                    'Other',
                ]),
            ],

            'expense_date' => [
                'required',
                'date',
            ],

            'payment_method' => [
                'nullable',
                'string',
                Rule::in([
                    'Cash',
                    'Card',
                    'eSewa',
                    'Khalti',
                    'Bank Transfer',
                    'Other',
                ]),
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' =>
                'Expense title is required.',

            'amount.required' =>
                'Expense amount is required.',

            'amount.numeric' =>
                'Expense amount must be a number.',

            'amount.min' =>
                'Expense amount must be greater than zero.',

            'category.required' =>
                'Expense category is required.',

            'category.in' =>
                'Please select a valid expense category.',

            'expense_date.required' =>
                'Expense date is required.',

            'expense_date.date' =>
                'Please provide a valid expense date.',

            'payment_method.in' =>
                'Please select a valid payment method.',
        ];
    }
}
