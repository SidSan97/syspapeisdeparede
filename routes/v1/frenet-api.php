<?php

use App\Http\Controllers\FrenetController;
use Illuminate\Support\Facades\Route;

// Frenet
Route::post('frenet/calculate-shipping', [FrenetController::class, 'calculateShipping']);
