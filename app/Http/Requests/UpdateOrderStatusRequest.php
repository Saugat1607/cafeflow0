<?php
// app/Http/Requests/UpdateOrderStatusRequest.php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'in:open,preparing,served,paid,cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status must be one of: open, preparing, served, paid, cancelled.',
        ];
    }
}
