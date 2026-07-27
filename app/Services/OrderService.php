<?php
// app/Services/OrderService.php

namespace App\Services;

use App\Models\MenuItem;
use App\Models\Order;
use App\Models\RestaurantTable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class OrderService
{

    public function getOrderCreationData(RestaurantTable $table): array
    {
        $menuItems = MenuItem::where('is_available', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        return [
            'table' => $table,
            'menu_items' => $menuItems,
        ];
    }

     public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {

            $order = Order::create([
                'restaurant_table_id' => $data['restaurant_table_id'],
                'status' => 'open',
                'notes' => $data['notes'] ?? null,
            ]);

            $this->syncOrderItems($order, $data['items']);

            $order->table->update([
                'status' => 'occupied',
            ]);

            return $order->load(['table', 'items.menuItem']);
        });
    }

    protected function syncOrderItems(Order $order, array $items): void
    {
        $menuItemIds = collect($items)->pluck('menu_item_id')->unique();

        /** @var Collection<int, MenuItem> $menuItems */
        $menuItems = MenuItem::whereIn('id', $menuItemIds)->get()->keyBy('id');

        foreach ($items as $item) {
            $menuItem = $menuItems->get($item['menu_item_id']);

            if (! $menuItem) {
                continue; // already guarded by validation, but stay defensive
            }

            $order->items()->create([
                'menu_item_id' => $menuItem->id,
                'quantity' => $item['quantity'],
                'unit_price' => $menuItem->price,
            ]);
        }
    }

    public function getOrderDetails(Order $order): array
    {
        $order->load(['table', 'items.menuItem']);

        return [
            'order' => $order,
            'total' => $order->total(),
        ];
    }


    public function listOrders(?string $status = null, ?int $tableId = null): Collection
    {
        return Order::query()
            ->with(['table', 'items.menuItem'])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($tableId, fn ($query) => $query->where('restaurant_table_id', $tableId))
            ->latest()
            ->get();
    }


    public function updateStatus(Order $order, string $status): Order
    {
        return DB::transaction(function () use ($order, $status) {

            if ($status === 'paid') {
                $order->markPaid();
            } else {
                $order->update(['status' => $status]);

                if ($status === 'cancelled') {
                    $order->table->update(['status' => 'available']);
                }
            }

            return $order->load(['table', 'items.menuItem']);
        });
    }


    public function deleteOrder(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->items()->delete();

            $order->table->update(['status' => 'available']);

            $order->delete();
        });
    }
}
