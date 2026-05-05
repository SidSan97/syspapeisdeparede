<?php

use App\Http\Controllers\API\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('profile')
    ->middleware('auth:sanctum')
    ->group(function () {
        Route::get('/', [ProfileController::class, 'show']);
        Route::put('/', [ProfileController::class, 'update']);
        Route::put('password', [ProfileController::class, 'updatePassword']);
        Route::put('avatar', [ProfileController::class, 'updateAvatar']);
    });
