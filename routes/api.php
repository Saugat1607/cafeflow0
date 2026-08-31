<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\InventoryTransactionController;
use App\Http\Controllers\Api\MenuItemController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\RestaurantTableController;
use Illuminate\Support\Facades\Route;


// Public
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);

// Protected (requires Bearer token from Sanctum)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::get('/dashboard', [DashboardController::class, 'index']);

    Route::apiResource('tables', RestaurantTableController::class);
    //menu
    Route::apiResource('menu', MenuItemController::class);
Route::get('/tables/{table}/order/create', [OrderController::class, 'create']);

Route::get('/orders', [OrderController::class, 'index']);
Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{order}', [OrderController::class, 'show']);
Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);
Route::delete('/orders/{order}', [OrderController::class, 'destroy']);


//bill
Route::get('/bills', [BillController::class, 'index']);
Route::get('\bills\recent-orders', [BillController::class, 'recentorders'] );
Route::post('/bills', [BillController::class, 'store']);
Route::get('/bills/{bill}', [BillController::class, 'show']);
Route::post('/bills/{bill}/pay', [BillController::class, 'pay']);
Route::delete('/bills/{bill}', [BillController::class, 'destroy']);

//expense
Route::apiResource('expenses' , ExpenseController::class);

Route::get('expense-report/daily-total', [ExpenseController::class, 'dailyTotal']);
Route::get('expense-report/category-totals', [ExpenseController::class, 'categoryTotals']);

//inventory

Route::get('/inventory/statistics', [
        InventoryController::class,
        'statistics'
    ]);

    Route::get('/inventory/low-stock', [
        InventoryController::class,
        'lowStock'
    ]);

    Route::get('/inventory/out-of-stock', [
        InventoryController::class,
        'outOfStock'
    ]);

    Route::get('/inventory/categories', [
        InventoryController::class,
        'categories'
    ]);

    Route::patch('/inventory/{id}/restore', [
        InventoryController::class,
        'restore'
    ]);

    Route::apiResource(
        'inventory',
        InventoryController::class
    );


    // Inventory Transactions
    Route::get('/inventory-transactions/statistics', [
        InventoryTransactionController::class,
        'statistics'
    ]);

    Route::get('/inventory-transactions/item/{itemId}', [
        InventoryTransactionController::class,
        'itemHistory'
    ]);

    Route::get('/inventory-transactions', [
        InventoryTransactionController::class,
        'index'
    ]);

    Route::get('/inventory-transactions/{id}', [
        InventoryTransactionController::class,
        'show'
    ]);

    Route::post('/inventory-transactions/stock-in', [
        InventoryTransactionController::class,
        'stockIn'
    ]);

    Route::post('/inventory-transactions/stock-out', [
        InventoryTransactionController::class,
        'stockOut'
    ]);

    Route::post('/inventory-transactions/adjust', [
        InventoryTransactionController::class,
        'adjust'
    ]);

    Route::delete('/inventory-transactions/{id}', [
        InventoryTransactionController::class,
        'destroy'
    ]);
});
