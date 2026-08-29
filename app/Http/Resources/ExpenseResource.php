<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


use Js;

use function Laravel\Prompts\form;

class ExpenseResource extends JsonResource
{
    public function toArray(Request $request): array    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'amount' => (float) $this->amount,
            'category' => $this->category,
            'expense_date' => $this->expense_date?->format('Y-m-d'),
            'payment_method' => $this->payment_method,
            'created_by' => $this->created_by,
            'created_by_name' => $this->whenLoaded('creator', function () {
                return $this->creator?->name;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
