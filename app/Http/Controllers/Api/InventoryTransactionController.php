<?php

namespace App\Http\Controllers;

use App\Http\Requests\InventoryTransactionRequest;
use App\Http\Resources\InventoryTransactionResource;
use App\Models\InventoryItem;
use App\Services\InventoryTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InventoryTransactionController extends Controller
{
    public function __construct(
        private InventoryTransactionService $transactionService
    ) {
    }

    /**
     * Display inventory transactions.
     */
    public function index(Request $request)
    {
        $transactions = $this->transactionService->getAll(
            $request->all()
        );

        return InventoryTransactionResource::collection(
            $transactions
        );
    }

    /**
     * Display a single transaction.
     */
    public function show(int $id)
    {
        $transaction = $this->transactionService->find($id);

        return new InventoryTransactionResource(
            $transaction
        );
    }

    /**
     * Add stock.
     */
    public function stockIn(
        InventoryTransactionRequest $request
    ) {
        $transaction = $this->transactionService->stockIn(
            $request->validated(),
            auth()->id()
        );

        return (new InventoryTransactionResource($transaction))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Remove stock.
     */
    public function stockOut(
        InventoryTransactionRequest $request
    ) {
        $transaction = $this->transactionService->stockOut(
            $request->validated(),
            auth()->id()
        );

        return (new InventoryTransactionResource($transaction))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Adjust stock.
     */
    public function adjust(
        InventoryTransactionRequest $request
    ) {
        $transaction = $this->transactionService->adjust(
            $request->validated(),
            auth()->id()
        );

        return (new InventoryTransactionResource($transaction))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Delete a transaction.
     */
    public function destroy(int $id): JsonResponse
    {
        $transaction = $this->transactionService->find($id);

        $this->transactionService->delete($transaction);

        return response()->json([
            'message' => 'Inventory transaction deleted successfully.',
        ]);
    }

    /**
     * Get transaction history for an inventory item.
     */
    public function itemHistory(
        Request $request,
        int $itemId
    ) {
        $item = InventoryItem::findOrFail($itemId);

        $transactions = $this->transactionService->getItemHistory(
            $item,
            $request->all()
        );

        return InventoryTransactionResource::collection(
            $transactions
        );
    }

    /**
     * Get transaction statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        return response()->json(
            $this->transactionService->getStatistics(
                $request->all()
            )
        );
    }
}
