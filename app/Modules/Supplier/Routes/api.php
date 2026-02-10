<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Supplier\Controllers\SupplierController;

/*
|--------------------------------------------------------------------------
| Supplier Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::put('/suppliers/{id}', [SupplierController::class, 'update']);
    Route::patch('/suppliers/{id}/status', [SupplierController::class, 'updateStatus']);
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);
});