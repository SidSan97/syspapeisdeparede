<?php

use App\Http\Controllers\API\V1\RoleController;
use App\Http\Controllers\API\V1\TypeUserController;
use App\Http\Controllers\API\V1\UserController;
use Illuminate\Support\Facades\Route;

// Roles
Route::get('roles/list', [RoleController::class, 'list']);

// Users
Route::get('users/list', [UserController::class, 'list']);
Route::get('users/list-resellers', [UserController::class, 'listResellers']);
Route::get('users/list-designers', [UserController::class, 'listDesigners']);

Route::apiResources([
    'users' => UserController::class,
    'type-users' => TypeUserController::class,
]);

Route::get('type-users/list', [TypeUserController::class, 'list']);
