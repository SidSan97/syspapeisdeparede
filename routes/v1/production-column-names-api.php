<?php

use App\Http\Controllers\API\V1\ProductionColumnNameController;
use Illuminate\Support\Facades\Route;

// Production 'trello'
Route::get('production-column-names', [ProductionColumnNameController::class, 'index']);
Route::post('production-column-names', [ProductionColumnNameController::class, 'store']);
Route::put('production-column-names/{productionColumnName}', [ProductionColumnNameController::class, 'update']);
Route::delete('production-column-names/{column}', [ProductionColumnNameController::class, 'destroy']);
