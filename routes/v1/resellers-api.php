<?php

use App\Http\Controllers\API\V1\ResellerController;
use Illuminate\Support\Facades\Route;

Route::get('resellers/list', [ResellerController::class, 'list']);
