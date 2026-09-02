<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date,

            'order_id' => $this->order_id,
            'table_number' => $this->table_number,

            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,

            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'tax' => $this->tax,
            'total' => $this->total,

            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,

            'notes' => $this->notes,

            'items' => InvoiceItemResource::collection(
                $this->whenLoaded('items')
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
