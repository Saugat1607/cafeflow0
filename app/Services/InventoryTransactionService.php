<?php

namespace App\Services;

use App\Models\InventoryItem;
use App\Models\InventoryTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InventoryTransactionService
{
    /**
     * Get inventory transactions with filters and pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = InventoryTransaction::query()
            ->with([
                'inventoryItem:id,name,category,unit',
                'creator:id,name,email',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('reason', 'ILIKE', "%{$search}%")
                    ->orWhere('reference', 'ILIKE', "%{$search}%")
                    ->orWhereHas('inventoryItem', function ($itemQuery) use ($search) {
                        $itemQuery
                            ->where('name', 'ILIKE', "%{$search}%")
                            ->orWhere('category', 'ILIKE', "%{$search}%");
                    });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Inventory Item
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['inventory_item_id'])) {
            $query->where(
                'inventory_item_id',
                $filters['inventory_item_id']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction Type
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        /*
        |--------------------------------------------------------------------------
        | Date Filters
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['date'])) {
            $query->whereDate(
                'transaction_date',
                $filters['date']
            );
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate(
                'transaction_date',
                '>=',
                $filters['from_date']
            );
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate(
                'transaction_date',
                '<=',
                $filters['to_date']
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'transaction_date',
            'quantity',
            'unit_cost',
            'total_cost',
            'created_at',
        ];

        $sortBy = in_array(
            $filters['sort_by'] ?? '',
            $allowedSorts,
            true
        )
            ? $filters['sort_by']
            : 'transaction_date';

        $sortDirection = strtolower(
            $filters['sort_direction'] ?? 'desc'
        );

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection)
            ->orderBy('id', 'desc');

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $perPage = min(
            max((int) ($filters['per_page'] ?? 15), 1),
            100
        );

        return $query->paginate($perPage);
    }

    /**
     * Get a single transaction.
     */
    public function find(int $id): InventoryTransaction
    {
        return InventoryTransaction::with([
            'inventoryItem',
            'creator',
        ])->findOrFail($id);
    }

    /**
     * Stock In.
     *
     * Adds stock to the inventory item and creates
     * an inventory transaction.
     */
    public function stockIn(
        array $data,
        ?int $userId = null
    ): InventoryTransaction {
        return DB::transaction(function () use ($data, $userId) {
            $item = InventoryItem::query()
                ->lockForUpdate()
                ->findOrFail($data['inventory_item_id']);

            $quantity = (float) $data['quantity'];

            $unitCost = isset($data['unit_cost'])
                ? (float) $data['unit_cost']
                : (float) $item->cost_per_unit;

            $totalCost = $quantity * $unitCost;

            /*
            |--------------------------------------------------------------------------
            | Update average cost
            |--------------------------------------------------------------------------
            |
            | When new stock comes in, calculate weighted average cost.
            |
            */

            $oldStock = (float) $item->current_stock;
            $oldCost = (float) $item->cost_per_unit;

            $newStock = $oldStock + $quantity;

            if ($newStock > 0) {
                $averageCost = (
                    ($oldStock * $oldCost)
                    + ($quantity * $unitCost)
                ) / $newStock;
            } else {
                $averageCost = $unitCost;
            }

            $item->update([
                'current_stock' => $newStock,
                'cost_per_unit' => $averageCost,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create transaction
            |--------------------------------------------------------------------------
            */

            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'type' => 'in',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'created_by' => $userId,
            ]);

            return $transaction->load([
                'inventoryItem',
                'creator',
            ]);
        });
    }

    /**
     * Stock Out.
     *
     * Removes stock from the inventory item.
     */
    public function stockOut(
        array $data,
        ?int $userId = null
    ): InventoryTransaction {
        return DB::transaction(function () use ($data, $userId) {
            $item = InventoryItem::query()
                ->lockForUpdate()
                ->findOrFail($data['inventory_item_id']);

            $quantity = (float) $data['quantity'];

            $currentStock = (float) $item->current_stock;

            /*
            |--------------------------------------------------------------------------
            | Prevent negative inventory
            |--------------------------------------------------------------------------
            */

            if ($quantity > $currentStock) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        "Insufficient stock. Available stock is {$currentStock} {$item->unit}.",
                    ],
                ]);
            }

            $unitCost = isset($data['unit_cost'])
                ? (float) $data['unit_cost']
                : (float) $item->cost_per_unit;

            $totalCost = $quantity * $unitCost;

            $item->update([
                'current_stock' => $currentStock - $quantity,
            ]);

            /*
            |--------------------------------------------------------------------------
            | Create transaction
            |--------------------------------------------------------------------------
            */

            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'type' => 'out',
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reason' => $data['reason'] ?? null,
                'reference' => $data['reference'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'created_by' => $userId,
            ]);

            return $transaction->load([
                'inventoryItem',
                'creator',
            ]);
        });
    }

    /**
     * Adjust inventory stock.
     *
     * This sets the stock to a specific quantity instead
     * of simply adding/subtracting from it.
     */
    public function adjust(
        array $data,
        ?int $userId = null
    ): InventoryTransaction {
        return DB::transaction(function () use ($data, $userId) {
            $item = InventoryItem::query()
                ->lockForUpdate()
                ->findOrFail($data['inventory_item_id']);

            $newStock = (float) $data['quantity'];
            $oldStock = (float) $item->current_stock;

            if ($newStock < 0) {
                throw ValidationException::withMessages([
                    'quantity' => [
                        'Adjusted stock cannot be negative.',
                    ],
                ]);
            }

            $difference = $newStock - $oldStock;

            $unitCost = isset($data['unit_cost'])
                ? (float) $data['unit_cost']
                : (float) $item->cost_per_unit;

            $totalCost = abs($difference) * $unitCost;

            $item->update([
                'current_stock' => $newStock,
            ]);

            $transaction = InventoryTransaction::create([
                'inventory_item_id' => $item->id,
                'type' => 'adjustment',
                'quantity' => abs($difference),
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'reason' => $data['reason'] ?? 'Inventory adjustment',
                'reference' => $data['reference'] ?? null,
                'transaction_date' => $data['transaction_date'],
                'created_by' => $userId,
            ]);

            return $transaction->load([
                'inventoryItem',
                'creator',
            ]);
        });
    }

    /**
     * Delete a transaction and reverse its stock movement.
     *
     * This should be used carefully because inventory history
     * is normally considered important.
     */
    public function delete(
        InventoryTransaction $transaction
    ): bool {
        return DB::transaction(function () use ($transaction) {
            $item = InventoryItem::query()
                ->lockForUpdate()
                ->findOrFail($transaction->inventory_item_id);

            $quantity = (float) $transaction->quantity;
            $currentStock = (float) $item->current_stock;

            /*
            |--------------------------------------------------------------------------
            | Reverse the original transaction
            |--------------------------------------------------------------------------
            */

            if ($transaction->type === 'in') {
                $newStock = $currentStock - $quantity;

                if ($newStock < 0) {
                    throw ValidationException::withMessages([
                        'transaction' => [
                            'This transaction cannot be deleted because reversing it would make the inventory negative.',
                        ],
                    ]);
                }

                $item->update([
                    'current_stock' => $newStock,
                ]);
            }

            if ($transaction->type === 'out') {
                $item->update([
                    'current_stock' => $currentStock + $quantity,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Adjustment reversal
            |--------------------------------------------------------------------------
            |
            | An adjustment represents a correction to a specific stock
            | level. It should normally not be deleted because we don't
            | have the original stock level stored in the transaction.
            |
            */

            if ($transaction->type === 'adjustment') {
                throw ValidationException::withMessages([
                    'transaction' => [
                        'Inventory adjustment transactions cannot be deleted. Create a new adjustment instead.',
                    ],
                ]);
            }

            return (bool) $transaction->delete();
        });
    }

    /**
     * Get transactions for a specific inventory item.
     */
    public function getItemHistory(
        InventoryItem $item,
        array $filters = []
    ): LengthAwarePaginator {
        $filters['inventory_item_id'] = $item->id;

        return $this->getAll($filters);
    }

    /**
     * Get transaction statistics.
     */
    public function getStatistics(array $filters = []): array
    {
        $query = InventoryTransaction::query();

        if (!empty($filters['inventory_item_id'])) {
            $query->where(
                'inventory_item_id',
                $filters['inventory_item_id']
            );
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate(
                'transaction_date',
                '>=',
                $filters['from_date']
            );
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate(
                'transaction_date',
                '<=',
                $filters['to_date']
            );
        }

        $stockIn = (clone $query)
            ->where('type', 'in')
            ->sum('quantity');

        $stockOut = (clone $query)
            ->where('type', 'out')
            ->sum('quantity');

        $stockInCost = (clone $query)
            ->where('type', 'in')
            ->sum('total_cost');

        $stockOutCost = (clone $query)
            ->where('type', 'out')
            ->sum('total_cost');

        $adjustments = (clone $query)
            ->where('type', 'adjustment')
            ->count();

        return [
            'total_stock_in' => (float) $stockIn,
            'total_stock_out' => (float) $stockOut,
            'total_stock_in_cost' => (float) $stockInCost,
            'total_stock_out_cost' => (float) $stockOutCost,
            'total_adjustments' => $adjustments,
        ];
    }
}
