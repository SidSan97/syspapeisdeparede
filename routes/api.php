<?php

use App\Http\Controllers\API\V1\{
    BudgetController,
    CollectionArtController,
    CollectionImageController,
    CollectionModelController,
    ProfileController,
    UserController,
};
use App\Http\Controllers\API\V1\RoleController;
use App\Http\Controllers\AppVersionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    // Profile
    //----------------------------------

    Route::get('profile', [ProfileController::class, 'index']);
    Route::put('profile', [ProfileController::class, 'update']);
    Route::post('change-password', [ProfileController::class, 'changePassword']);
    Route::post('profile/avatar', [ProfileController::class, 'uploadAvatar']);

    Route::get('roles/list', [RoleController::class, 'list']);

    // Users
    //----------------------------------

    Route::get('users/search', [UserController::class, 'search']);
    Route::get('users/list', [UserController::class, 'list']);

    Route::apiResources([
        'users' => UserController::class,
        'collection-models' => CollectionModelController::class,
        'collection-arts' => CollectionArtController::class,
        'collection-images' => CollectionImageController::class,
    ]);
    Route::post('budgets', [BudgetController::class, 'store'])->middleware('auth:api');
    Route::get('budgets', [BudgetController::class, 'index'])->middleware('auth:api');
    Route::get('budgets/pending-review', [BudgetController::class, 'pendingReview'])->middleware('auth:api');
    Route::post('budgets/cancel', [BudgetController::class, 'cancel'])->middleware('auth:api');
    Route::post('budgets/generate-pdf', [BudgetController::class, 'generatePdf'])->middleware('auth:api');
    Route::post('budgets/place-order', [BudgetController::class, 'placeOrder'])->middleware('auth:api');
});
