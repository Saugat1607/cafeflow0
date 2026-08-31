<?php

namespace App\Services;

use App\Models\InventoryItem;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class InventoryService
{
    /**
     * Get inventory items with optional filtering and pagination.
     */
    public function getAll(array $filters = []): LengthAwarePaginator
    {
        $query = InventoryItem::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['search'])) {
            $search = trim($filters['search']);

            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('category', 'ILIKE', "%{$search}%")
                    ->orWhere('supplier', 'ILIKE', "%{$search}%");
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Category
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        /*
        |--------------------------------------------------------------------------
        | Active / Inactive
        |--------------------------------------------------------------------------
        */

        if (isset($filters['is_active'])) {
            $query->where(
                'is_active',
                filter_var(
                    $filters['is_active'],
                    FILTER_VALIDATE_BOOLEAN,
                    FILTER_NULL_ON_FAILURE
                )
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Stock Status
        |--------------------------------------------------------------------------
        */

        if (!empty($filters['status'])) {
            match ($filters['status']) {
                'in_stock' => $query
                    ->where('current_stock', '>', 0)
                    ->whereColumn(
                        'current_stock',
                        '>',
                        'minimum_stock'
                    ),

                'low_stock' => $query
                    ->where('current_stock', '>', 0)
                    ->whereColumn(
                        'current_stock',
                        '<=',
                        'minimum_stock'
                    ),

                'out_of_stock' => $query
                    ->where('current_stock', '<=', 0),

                default => null,
            };
        }

        /*
        |--------------------------------------------------------------------------
        | Sorting
        |--------------------------------------------------------------------------
        */

        $allowedSorts = [
            'name',
            'category',
            'current_stock',
            'minimum_stock',
            'cost_per_unit',
            'created_at',
        ];

        $sortBy = in_array(
            $filters['sort_by'] ?? '',
            $allowedSorts,
            true
        )
            ? $filters['sort_by']
            : 'created_at';

        $sortDirection = strtolower(
            $filters['sort_direction'] ?? 'desc'
        );

        if (!in_array($sortDirection, ['asc', 'desc'], true)) {
            $sortDirection = 'desc';
        }

        $query->orderBy($sortBy, $sortDirection);

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
     * Get a single inventory item.
     */
    public function find(int $id): InventoryItem
    {
        return InventoryItem::with([
            'transactions' => function ($query) {
                $query->latest('transaction_date')
                    ->latest('id')
                    ->limit(20);
            },
        ])->findOrFail($id);
    }

    /**
     * Create a new inventory item.
     */
    public function create(array $data): InventoryItem
    {
        return DB::transaction(function () use ($data) {
            $data['category'] = $data['category'] ?? 'Other';
            $data['unit'] = $data['unit'] ?? 'pcs';
            $data['current_stock'] = $data['current_stock'] ?? 0;
            $data['minimum_stock'] = $data['minimum_stock'] ?? 0;
            $data['cost_per_unit'] = $data['cost_per_unit'] ?? 0;
            $data['is_active'] = $data['is_active'] ?? true;

            $item = InventoryItem::create($data);

            return $item->fresh();
        });
    }

    /**
     * Update an inventory item.
     */
    public function update(
        InventoryItem $item,
        array $data
    ): InventoryItem {
        return DB::transaction(function () use ($item, $data) {
            /*
            |--------------------------------------------------------------------------
            | Do not modify current_stock here
            |--------------------------------------------------------------------------
            |
            | Stock should be changed through InventoryTransactionService.
            | This keeps the stock history accurate.
            |
            */

            unset($data['current_stock']);

            $item->update($data);

            return $item->fresh();
        });
    }

    /**
     * Delete an inventory item.
     *
     * We deactivate the item instead of physically deleting it.
     * This preserves inventory history.
     */
    public function delete(InventoryItem $item): bool
    {
        return DB::transaction(function () use ($item) {
            $item->update([
                'is_active' => false,
            ]);

            return true;
        });
    }

    /**
     * Permanently delete an inventory item.
     *
     * Use this only when there are no important transactions.
     */
    public function forceDelete(InventoryItem $item): bool
    {
        return DB::transaction(function () use ($item) {
            if ($item->transactions()->exists()) {
                throw ValidationException::withMessages([
                    'inventory' => [
                        'This inventory item cannot be permanently deleted because it has transaction history.',
                    ],
                ]);
            }

            return (bool) $item->delete();
        });
    }

    /**
     * Restore a previously deactivated item.
     */
    public function restore(InventoryItem $item): InventoryItem
    {
        $item->update([
            'is_active' => true,
        ]);

        return $item->fresh();
    }

    /**
     * Get all low-stock items.
     */
    public function getLowStockItems(): Collection
    {
        return InventoryItem::query()
            ->where('is_active', true)
            ->where('current_stock', '>', 0)
            ->whereColumn(
                'current_stock',
                '<=',
                'minimum_stock'
            )
            ->orderBy('current_stock')
            ->get();
    }

    /**
     * Get all out-of-stock items.
     */
    public function getOutOfStockItems(): Collection
    {
        return InventoryItem::query()
            ->where('is_active', true)
            ->where('current_stock', '<=', 0)
            ->orderBy('name')
            ->get();
    }

    /**
     * Get inventory statistics.
     */
    public function getStatistics(): array
    {
        $query = InventoryItem::query()
            ->where('is_active', true);

        $totalItems = (clone $query)->count();

        $inStock = (clone $query)
            ->where('current_stock', '>', 0)
            ->whereColumn(
                'current_stock',
                '>',
                'minimum_stock'
            )
            ->count();

        $lowStock = (clone $query)
            ->where('current_stock', '>', 0)
            ->whereColumn(
                'current_stock',
                '<=',
                'minimum_stock'
            )
            ->count();

        $outOfStock = (clone $query)
            ->where('current_stock', '<=', 0)
            ->count();

        $stockValue = (clone $query)
            ->selectRaw(
                'COALESCE(SUM(current_stock * cost_per_unit), 0) as total'
            )
            ->value('total');

        return [
            'total_items' => $totalItems,
            'in_stock' => $inStock,
            'low_stock' => $lowStock,
            'out_of_stock' => $outOfStock,
            'total_stock_value' => (float) $stockValue,
        ];
    }

    /**
     * Get inventory categories.
     */
    public function getCategories(): array
    {
        return InventoryItem::query()
            ->where('is_active', true)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->values()
            ->toArray();
    }

    /**
     * Check whether an item has sufficient stock.
     */
    public function hasEnoughStock(
        InventoryItem $item,
        float $quantity
    ): bool {
        return (float) $item->current_stock >= $quantity;
    }

    /**
     * Get the current stock value of an item.
     */
    public function getStockValue(
        InventoryItem $item
    ): float {
        return (float) $item->current_stock
            * (float) $item->cost_per_unit;
    }
}
