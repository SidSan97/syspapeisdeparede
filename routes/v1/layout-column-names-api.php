<?php

use App\Http\Controllers\API\V1\LayoutColumnNameController;
use Illuminate\Support\Facades\Route;

// Layout 'trello'
Route::get('layout-column-names', [LayoutColumnNameController::class, 'index']);
Route::post('layout-column-names', [LayoutColumnNameController::class, 'store']);
Route::post('layout-column-names/reorder', [LayoutColumnNameController::class, 'reorder']);
Route::put('layout-column-names/{layoutColumnName}', [LayoutColumnNameController::class, 'update']);
Route::delete('layout-column-names/{column}', [LayoutColumnNameController::class, 'destroy']);
