<?php

use App\Http\Controllers\API\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('wallet', [WalletController::class, 'show']);
