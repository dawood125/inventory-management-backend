<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Stock\Controllers\StockController;



Route::middleware('auth:sanctum')->group(function () {
    // Stats route (must be before {id} route)
    Route::get('/stock-movements/stats', [StockController::class, 'stats']);

    // Product history route
    Route::get('/stock-movements/product/{productId}/history', [StockController::class, 'productHistory']);

    // CRUD routes
    Route::get('/stock-movements', [StockController::class, 'index']);
    Route::post('/stock-movements', [StockController::class, 'store']);
    Route::get('/stock-movements/{id}', [StockController::class, 'show']);
});
