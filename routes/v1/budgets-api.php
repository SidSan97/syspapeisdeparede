<?php

use App\Http\Controllers\API\V1\BudgetController;
use App\Http\Controllers\API\V1\BudgetOrderController;
use App\Http\Controllers\API\V1\BudgetPdfController;
use App\Http\Controllers\API\V1\OrderBudgetController;
use Illuminate\Support\Facades\Route;

// Orçamentos

Route::post('budgets/{budget}/copies', [BudgetController::class, 'duplicate']);
Route::patch('budgets/{budget}/status', [BudgetController::class, 'updateStatus']);
Route::post('budgets/{budget}/pdf', [BudgetPdfController::class, 'store']);
Route::post('budgets/{budget}/orders', [BudgetOrderController::class, 'store']);

Route::get('budgets/request-layout-arts', [BudgetController::class, 'getRequestLayoutArts']);
Route::patch('budgets/request-layout-arts/status', [BudgetController::class, 'updateRequestLayoutArtStatus']);
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
