<?php

use App\Http\Controllers\API\V1\ProfileController;
use Illuminate\Support\Facades\Route;

// Profile
Route::get('profile', [ProfileController::class, 'index']);
Route::put('profile', [ProfileController::class, 'update']);
Route::post('change-password', [ProfileController::class, 'changePassword']);
Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);
