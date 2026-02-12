<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Order\Controllers\OrderController;

/*
|--------------------------------------------------------------------------
| Order Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // Stats route (must be before {id} route)
    Route::get('/orders/stats', [OrderController::class, 'stats']);

    // CRUD routes
    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{id}', [OrderController::class, 'show']);
    Route::patch('/orders/{id}/status', [OrderController::class, 'updateStatus']);
    Route::delete('/orders/{id}', [OrderController::class, 'destroy']);
});