<?php

use Illuminate\Support\Facades\Route;
use App\Modules\Report\Controllers\ReportController;

/*
|--------------------------------------------------------------------------
| Report Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/reports/dashboard', [ReportController::class, 'dashboard']);
    Route::get('/reports/inventory', [ReportController::class, 'inventory']);
    Route::get('/reports/sales', [ReportController::class, 'sales']);
    Route::get('/reports/purchases', [ReportController::class, 'purchases']);
    Route::get('/reports/low-stock', [ReportController::class, 'lowStock']);
    Route::get('/reports/categories', [ReportController::class, 'categories']);
});