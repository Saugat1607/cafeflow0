<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RestaurantTableRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // On update, the route model binding gives us the current table so the
        // unique rule can ignore it. On store, there's no bound table yet.
        $table = $this->route('table');

        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('restaurant_tables', 'name')->ignore($table?->id),
            ],
            'seats' => 'required|integer|min:1|max:50',
        ];

        if ($this->isMethod('put') || $this->isMethod('patch')) {
            $rules['status'] = 'required|in:available,occupied,reserved';
        }

        return $rules;
    }
}
