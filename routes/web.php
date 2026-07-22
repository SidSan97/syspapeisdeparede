<?php

use App\Http\Controllers\BudgetPreviewController;
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

Route::get('/budgets/{budget}/preview', [BudgetPreviewController::class, 'index']);
Route::post('/budgets/{budget}/pdf', [BudgetPreviewController::class, 'download']);

// Rotas públicas para o catálogo de coleções/imagens (sem exigir login)
// Route::get('colecao-arts/{vue_capture?}', PublicCatalogController::class)
//     ->where('vue_capture', '[\/\w\.-]*');

// Catálogo público standalone (blade + Vue separados do SPA admin)
Route::get('catalogo/{vue_capture?}', [PublicCatalogController::class, 'index'])
    ->where('vue_capture', '[\/\w\.-]*');

// Demais rotas SPA protegidas por autenticação
Route::get('{vue_capture?}', HomeController::class)
    ->where('vue_capture', '[\/\w\.-]*')
    ->middleware('auth', 'verified');
