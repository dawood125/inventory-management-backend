<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Product\Controllers\ProductController;

/*
|--------------------------------------------------------------------------
| Product Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    // Alert routes (must be before {id} route)
    Route::get('/products/alerts/low-stock', [ProductController::class, 'lowStock']);
    Route::get('/products/alerts/out-of-stock', [ProductController::class, 'outOfStock']);

    // CRUD routes
    Route::get('/products', [ProductController::class, 'index']);
    Route::post('/products', [ProductController::class, 'store']);
    Route::get('/products/{id}', [ProductController::class, 'show']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
});