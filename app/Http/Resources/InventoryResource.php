<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,
            'category' => $this->category,
            'unit' => $this->unit,

            'current_stock' => (float) $this->current_stock,
            'minimum_stock' => (float) $this->minimum_stock,

            'cost_per_unit' => (float) $this->cost_per_unit,

            'stock_value' => $this->stock_value,

            'supplier' => $this->supplier,
            'description' => $this->description,

            'is_active' => (bool) $this->is_active,

            'status' => $this->getStockStatus(),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    /**
     * Get current inventory status.
     */
    private function getStockStatus(): string
    {
        if ($this->current_stock <= 0) {
            return 'out_of_stock';
        }

        if ($this->current_stock <= $this->minimum_stock) {
            return 'low_stock';
        }

        return 'in_stock';
    }
}
