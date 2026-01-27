<?php

use App\Http\Controllers\API\V1\LayoutColumnNameController;
use Illuminate\Support\Facades\Route;

// Layout 'trello'
Route::get('layout-column-names', [LayoutColumnNameController::class, 'index']);
Route::post('layout-column-names', [LayoutColumnNameController::class, 'store']);
Route::put('layout-column-names/{layoutColumnName}', [LayoutColumnNameController::class, 'update']);
Route::delete('layout-column-names/{layoutColumnName}', [LayoutColumnNameController::class, 'destroy']);
