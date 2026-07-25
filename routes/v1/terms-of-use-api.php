<?php

use App\Http\Controllers\API\V1\TermsOfUseController;
use Illuminate\Support\Facades\Route;

Route::get('terms-of-use', [TermsOfUseController::class, 'index']);
Route::put('terms-of-use', [TermsOfUseController::class, 'store']);
