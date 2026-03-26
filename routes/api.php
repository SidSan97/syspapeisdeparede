<?php

use App\Http\Controllers\AppVersionController;
use App\Http\Controllers\WebhookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// App version
// ----------------------------------

Route::get('version', AppVersionController::class);

// Webhook Pagar.me (público, sem autenticação)
Route::post('webhook/pagarme', [WebhookController::class, 'handlePagarme']);

// Rota para obter dados completos do usuário autenticado (sessão web)
Route::middleware('auth:web')->get('/user', function (Request $request) {
    $user = $request->user();

    if (!$user) {
        return response()->json(['error' => 'Não autenticado'], 401);
    }

    return [
        'user' => $user,
        'roles' => $user->getRoleNames()->toArray(),
        'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        'direct_permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
    ];
});

// Rota para API tokens (Passport) - mantida para compatibilidade
Route::middleware('auth:api')->get('/user-api', function (Request $request) {
    $user = $request->user();

    return [
        'user' => $user,
        'roles' => $user->getRoleNames()->toArray(),
        'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        'direct_permissions' => $user->getDirectPermissions()->pluck('name')->toArray(),
    ];
});

// Version 1 endpoints
// --------------------------------------

Route::prefix('v1')->group(function () {
    // Rotas públicas
    require __DIR__ . '/v1/collections-api.php';

    // Demais rotas autenticadas
    Route::middleware('auth:api')->group(function () {
        require __DIR__ . '/v1/profile-api.php';
        require __DIR__ . '/v1/users-api.php';
        require __DIR__ . '/v1/budgets-api.php';
        require __DIR__ . '/v1/orders-api.php';
        require __DIR__ . '/v1/layout-column-names-api.php';
        require __DIR__ . '/v1/production-column-names-api.php';
        require __DIR__ . '/v1/expedition-api.php';
        require __DIR__ . '/v1/frenet-api.php';
        require __DIR__ . '/v1/tiny-erp-api.php';
    });
});
