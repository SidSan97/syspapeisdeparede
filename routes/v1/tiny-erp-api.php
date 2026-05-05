<?php

use App\Http\Controllers\TinyErpCarrierController;
use App\Http\Controllers\TinyErpInvoiceController;
use App\Http\Controllers\TinyErpSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('tiny-erp')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('settings/{key}', [TinyErpSettingsController::class, 'show']);
        Route::get('settings', [TinyErpSettingsController::class, 'index']);
        Route::post('settings', [TinyErpSettingsController::class, 'store']);

        // FIXME: mover para /products
        Route::get('all', [TinyErpSettingsController::class, 'all']);

        Route::get('carriers', [TinyErpCarrierController::class, 'index']);
        Route::get('invoices/{order}', [TinyErpInvoiceController::class, 'show']);
    });
