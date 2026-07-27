<?php

namespace App\Services;

use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Collection;
use RuntimeException;

class RestaurantTableService
{
    /**
     * Get all restaurant tables ordered by name.
     *
     * @return Collection<int, RestaurantTable>
     */
    public function getAllTables(): Collection
    {
        return RestaurantTable::orderBy('name')->get();
    }

    /**
     * Create a new restaurant table with a default 'available' status.
     *
     * @param array<string, mixed> $data
     */
    public function createTable(array $data): RestaurantTable
    {
        $data['status'] = 'available';

        return RestaurantTable::create($data);
    }

    /**
     * Get a single table with its orders, order items, and menu items loaded.
     */
    public function getTableWithOrders(RestaurantTable $table): RestaurantTable
    {
        $table->load(['orders.items.menuItem']);

        return $table;
    }

    /**
     * Update an existing table.
     *
     * @param array<string, mixed> $data
     */
    public function updateTable(RestaurantTable $table, array $data): RestaurantTable
    {
        $table->update($data);

        return $table->fresh();
    }

    /**
     * Delete a table, unless it has an active (unpaid/uncancelled) order.
     *
     * @throws RuntimeException if the table has an active order.
     */
    public function deleteTable(RestaurantTable $table): bool
    {
        if ($this->hasActiveOrder($table)) {
            throw new RuntimeException('Cannot delete a table with an active order.');
        }

        return $table->delete();
    }

    /**
     * Determine whether a table has any active (unpaid/uncancelled) order.
     */
    public function hasActiveOrder(RestaurantTable $table): bool
    {
        return $table->orders()
            ->whereNotIn('status', ['paid', 'cancelled'])
            ->exists();
    }
}
