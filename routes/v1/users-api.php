<?php

use App\Http\Controllers\API\V1\RoleController;
use App\Http\Controllers\API\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')
    ->group(function () {
        Route::get('roles/list', [RoleController::class, 'list']);
        Route::get('users/list', [UserController::class, 'list']);
        Route::put('users/{user}/avatar', [UserController::class, 'updateAvatar']);

        Route::apiResources([
            'users' => UserController::class,
        ]);
    });
