<?php

use App\Http\Controllers\HomeController;
use App\Http\Controllers\PublicCatalogController;
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

// Rotas públicas para o catálogo de coleções/imagens (sem exigir login)
Route::get('colecao-arts/{vue_capture?}', PublicCatalogController::class)
    ->where('vue_capture', '[\/\w\.-]*');

// Demais rotas SPA protegidas por autenticação
Route::get('{vue_capture?}', HomeController::class)
    ->where('vue_capture', '[\/\w\.-]*')
    ->middleware('auth', 'verified');
