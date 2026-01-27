<?php

use App\Http\Controllers\TinyErpController;
use Illuminate\Support\Facades\Route;

// Tiny ERP
Route::get('tiny-erp/all', [TinyErpController::class, 'all']);
Route::get('tiny-erp/settings', [TinyErpController::class, 'loadSettings']);
Route::post('tiny-erp/settings', [TinyErpController::class, 'store']);
Route::get('tiny-erp/carriers-types', [TinyErpController::class, 'loadCarriersTypes']);
