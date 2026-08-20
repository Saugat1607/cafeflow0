<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BillController;
use App\Http\Controllers\Api\DashboardController;
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
Route::post('/bills', [BillController::class, 'store']);
Route::get('/bills/{bill}', [BillController::class, 'show']);
Route::post('/bills/{bill}/pay', [BillController::class, 'pay']);
Route::delete('/bills/{bill}', [BillController::class, 'destroy']);
});
