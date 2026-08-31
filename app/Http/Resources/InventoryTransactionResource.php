<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryTransactionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'inventory_item_id' => $this->inventory_item_id,

            'type' => $this->type,

            'quantity' => (float) $this->quantity,

            'unit_cost' => (float) $this->unit_cost,

            'total_cost' => (float) $this->total_cost,

            'reason' => $this->reason,

            'reference' => $this->reference,

            'transaction_date' => $this->transaction_date?->format('Y-m-d'),

            'item' => $this->whenLoaded('inventoryItem', function () {
                return [
                    'id' => $this->inventoryItem->id,
                    'name' => $this->inventoryItem->name,
                    'category' => $this->inventoryItem->category,
                    'unit' => $this->inventoryItem->unit,
                ];
            }),

            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator?->id,
                    'name' => $this->creator?->name,
                    'email' => $this->creator?->email,
                ];
            }),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
