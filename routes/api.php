<?php

use App\Http\Controllers\API\V1\{
    BudgetController,
    CollectionArtController,
    CollectionArtSubcategoryController,
    CollectionImageController,
    CollectionModelController,
    LayoutColumnNameController,
    MyFavoriteCollectionImageController,
    OrderController,
    ProductionColumnNameController,
    ProfileController,
    TypeUserController,
    UserController,
};
use App\Http\Controllers\API\V1\RoleController;
use App\Http\Controllers\AppVersionController;
use App\Http\Controllers\GeneratePaymentController;
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
    Route::get('type-users/list', [TypeUserController::class, 'list']);

    Route::apiResources([
        'users' => UserController::class,
        'type-users' => TypeUserController::class,
        'collection-models' => CollectionModelController::class,
        'collection-arts' => CollectionArtController::class,
        'collection-art-subcategories' => CollectionArtSubcategoryController::class,
        'collection-images' => CollectionImageController::class,
    ]);

    // My Favorite Collection Images
    //----------------------------------
    Route::get('my-favorite-collection-images', [MyFavoriteCollectionImageController::class, 'index'])->middleware('auth:api');
    Route::post('collection-images/{collectionImage}/toggle-favorite', [MyFavoriteCollectionImageController::class, 'toggle'])->middleware('auth:api');
    Route::get('collection-images/{collectionImage}/check-favorite', [MyFavoriteCollectionImageController::class, 'check'])->middleware('auth:api');

    // Orçamentos
    Route::post('budgets', [BudgetController::class, 'store'])->middleware('auth:api');
    Route::put('budgets/{id}', [BudgetController::class, 'update'])->middleware('auth:api');
    Route::get('budgets', [BudgetController::class, 'index'])->middleware('auth:api');
    Route::get('budgets/pending-review', [BudgetController::class, 'pendingReview'])->middleware('auth:api');
    Route::get('budgets/orders', [BudgetController::class, 'orders'])->middleware('auth:api');
    Route::post('budgets/cancel', [BudgetController::class, 'cancel'])->middleware('auth:api');
    //Route::post('budgets/approve', [BudgetController::class, 'approve'])->middleware('auth:api');
    Route::post('budgets/generate-pdf', [BudgetController::class, 'generatePdf'])->middleware('auth:api');
    Route::post('budgets/place-order', [BudgetController::class, 'placeOrder'])->middleware('auth:api');
    //Route::get('budgets/production-layouts', [BudgetController::class, 'productionLayouts'])->middleware('auth:api');
    Route::post('budgets/layouts/update-column', [BudgetController::class, 'updateLayoutColumn'])->middleware('auth:api');
    Route::put('budgets/order-budgets/{orderBudget}/description', [BudgetController::class, 'updateOrderBudgetDescription'])->middleware('auth:api');
    Route::post('budgets/order-budgets/{orderBudget}/mark-as-produced', [BudgetController::class, 'markAsProduced'])->middleware('auth:api');
    Route::put('budgets/order-budgets/{orderBudget}/production-percentage', [BudgetController::class, 'updateProductionPercentage'])->middleware('auth:api');
    Route::post('budgets/order-budgets/{orderBudget}/comments', [BudgetController::class, 'addComment'])->middleware('auth:api');
    Route::put('budgets/order-budgets/{orderBudget}/comments/{comment}', [BudgetController::class, 'updateComment'])->middleware('auth:api');
    Route::delete('budgets/order-budgets/{orderBudget}/comments/{comment}', [BudgetController::class, 'deleteComment'])->middleware('auth:api');
    Route::post('budgets/order-budgets/{orderBudget}/members', [BudgetController::class, 'addMember'])->middleware('auth:api');
    Route::delete('budgets/order-budgets/{orderBudget}/members', [BudgetController::class, 'removeMember'])->middleware('auth:api');
    Route::delete('budgets/order-budgets/{orderBudget}/members/{member}', [BudgetController::class, 'removeMember'])->middleware('auth:api');
    Route::post('budgets/order-budgets/upload-art', [BudgetController::class, 'uploadArt'])->middleware('auth:api');
    Route::get('budgets/request-layout-arts', [BudgetController::class, 'getRequestLayoutArts'])->middleware('auth:api');
    Route::post('budgets/register-payment', [BudgetController::class, 'registerPayment'])->middleware('auth:api');

    // Pedidos
    Route::get('orders', [OrderController::class, 'index'])->middleware('auth:api');
    Route::get('orders/layouts', [OrderController::class, 'layouts'])->middleware('auth:api');
    Route::get('orders/production-layouts', [OrderController::class, 'productionLayouts'])->middleware('auth:api');
    Route::get('orders/{id}', [OrderController::class, 'show'])->middleware('auth:api');

    // Layout 'trello'
    Route::get('layout-column-names', [LayoutColumnNameController::class, 'index'])->middleware('auth:api');
    Route::post('layout-column-names', [LayoutColumnNameController::class, 'store'])->middleware('auth:api');
    Route::put('layout-column-names/{layoutColumnName}', [LayoutColumnNameController::class, 'update'])->middleware('auth:api');
    Route::delete('layout-column-names/{layoutColumnName}', [LayoutColumnNameController::class, 'destroy'])->middleware('auth:api');

    // Production 'trello'
    Route::get('production-column-names', [ProductionColumnNameController::class, 'index'])->middleware('auth:api');
    Route::post('production-column-names', [ProductionColumnNameController::class, 'store'])->middleware('auth:api');
    Route::put('production-column-names/{productionColumnName}', [ProductionColumnNameController::class, 'update'])->middleware('auth:api');
    Route::delete('production-column-names/{productionColumnName}', [ProductionColumnNameController::class, 'destroy'])->middleware('auth:api');

    //Pagamentos
    Route::post('create-link-payment', [GeneratePaymentController::class, 'createLinkPayment'])->middleware('auth:api');
    Route::get('get-link-payment', [GeneratePaymentController::class, 'getLinkPayment'])->middleware('auth:api');
});
