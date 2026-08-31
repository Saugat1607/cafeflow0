<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryRequest;
use App\Http\Resources\InventoryResource;
use App\Services\InventoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public function __construct(
        private InventoryService $inventoryService
    ) {
    }

    /**
     * Display a paginated list of inventory items.
     */
    public function index(Request $request)
    {
        $items = $this->inventoryService->getAll(
            $request->all()
        );

        return InventoryResource::collection($items);
    }

    /**
     * Store a new inventory item.
     */
    public function store(InventoryRequest $request)
    {
        $item = $this->inventoryService->create(
            $request->validated()
        );

        return (new InventoryResource($item))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display a single inventory item.
     */
    public function show(int $id)
    {
        $item = $this->inventoryService->find($id);

        return new InventoryResource($item);
    }

    /**
     * Update an inventory item.
     */
    public function update(
        InventoryRequest $request,
        int $id
    ) {
        $item = $this->inventoryService->find($id);

        $item = $this->inventoryService->update(
            $item,
            $request->validated()
        );

        return new InventoryResource($item);
    }

    /**
     * Deactivate an inventory item.
     */
    public function destroy(int $id): JsonResponse
    {
        $item = $this->inventoryService->find($id);

        $this->inventoryService->delete($item);

        return response()->json([
            'message' => 'Inventory item deactivated successfully.',
        ]);
    }

    /**
     * Restore a deactivated inventory item.
     */
    public function restore(int $id)
    {
        $item = $this->inventoryService->find($id);

        $item = $this->inventoryService->restore($item);

        return new InventoryResource($item);
    }

    /**
     * Get low-stock items.
     */
    public function lowStock()
    {
        $items = $this->inventoryService->getLowStockItems();

        return InventoryResource::collection($items);
    }

    /**
     * Get out-of-stock items.
     */
    public function outOfStock()
    {
        $items = $this->inventoryService->getOutOfStockItems();

        return InventoryResource::collection($items);
    }

    /**
     * Get inventory statistics.
     */
    public function statistics(): JsonResponse
    {
        return response()->json(
            $this->inventoryService->getStatistics()
        );
    }

    /**
     * Get available inventory categories.
     */
    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => $this->inventoryService->getCategories(),
        ]);
    }
}
