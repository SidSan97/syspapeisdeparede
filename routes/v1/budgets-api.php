<?php

use App\Http\Controllers\API\V1\Budget\BudgetDuplicateController;
use App\Http\Controllers\API\V1\Budget\BudgetStatusController;
use App\Http\Controllers\API\V1\BudgetController;
use App\Http\Controllers\API\V1\BudgetOrderController;
use App\Http\Controllers\API\V1\OrderBudgetController;
use App\Http\Controllers\BudgetPreviewController;
use Illuminate\Support\Facades\Route;

// Orçamentos

Route::get('budgets/request-layout-arts', [BudgetController::class, 'getRequestLayoutArts']);
Route::patch('budgets/request-layout-arts/status', [BudgetController::class, 'updateRequestLayoutArtStatus']);

Route::post('budgets/{budget}/copies', [BudgetDuplicateController::class, 'store']);
Route::patch('budgets/{budget}/status', [BudgetStatusController::class, 'update']);
Route::post('budgets/{budget}/orders', [BudgetOrderController::class, 'store']);
Route::post('budgets/{budget}/pdf', [BudgetPreviewController::class, 'download']);
Route::get('budgets/{budget}/preview-items', [BudgetPreviewController::class, 'items']);

Route::post('budgets/register-payment', [BudgetController::class, 'registerPayment']);
Route::post('budgets/layouts/update-column', [BudgetController::class, 'updateLayoutColumn']);
Route::post('budgets/upload-referring-file', [BudgetController::class, 'uploadReferringFile']);

// Order Budgets
Route::put('budgets/order-budgets/{orderBudget}/description', [OrderBudgetController::class, 'updateDescription']);
Route::post('budgets/order-budgets/{orderBudget}/comments', [OrderBudgetController::class, 'addComment']);
Route::put('budgets/order-budgets/{orderBudget}/comments/{comment}', [OrderBudgetController::class, 'updateComment']);
Route::delete('budgets/order-budgets/{orderBudget}/comments/{comment}', [OrderBudgetController::class, 'deleteComment']);
Route::post('budgets/order-budgets/{orderBudget}/members', [OrderBudgetController::class, 'addMember']);
Route::delete('budgets/order-budgets/{orderBudget}/members', [OrderBudgetController::class, 'removeMember']);
Route::delete('budgets/order-budgets/{orderBudget}/members/{member}', [OrderBudgetController::class, 'removeMember']);
Route::post('budgets/order-budgets/{orderBudget}/activity/start', [OrderBudgetController::class, 'startActivity']);
Route::post('budgets/order-budgets/{orderBudget}/activity/pause', [OrderBudgetController::class, 'pauseActivity']);
Route::post('budgets/order-budgets/{orderBudget}/complete', [OrderBudgetController::class, 'complete']);
Route::post('budgets/order-budgets/upload-art', [BudgetController::class, 'uploadArt']);

Route::apiResource('budgets', BudgetController::class);
