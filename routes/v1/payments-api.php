<?php

use App\Http\Controllers\GeneratePaymentController;
use Illuminate\Support\Facades\Route;

// Pagamentos
Route::post('create-link-payment', [GeneratePaymentController::class, 'createLinkPayment']);
Route::get('get-link-payment', [GeneratePaymentController::class, 'getLinkPayment']);
