<?php

use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
});

Auth::routes(['verify' => true]);

Route::get('/home', HomeController::class)->name('home');

Route::get('home', function () {
    return redirect('/dashboard');
});

Route::get('{vue_capture?}', HomeController::class)->where('vue_capture', '[\/\w\.-]*')->middleware('auth', 'verified');
