<?php

use App\Http\Controllers\API\V1\BudgetController;
use App\Http\Controllers\API\V1\OrderBudgetController;
use Illuminate\Support\Facades\Route;

// Orçamentos
Route::post('budgets', [BudgetController::class, 'store']);
Route::get('budgets', [BudgetController::class, 'index']);
Route::get('budgets/pending-review', [BudgetController::class, 'pendingReview']);
Route::get('budgets/orders', [BudgetController::class, 'orders']);
Route::get('budgets/request-layout-arts', [BudgetController::class, 'getRequestLayoutArts']);
Route::patch('budgets/request-layout-arts/status', [BudgetController::class, 'updateRequestLayoutArtStatus']);
Route::post('budgets/cancel', [BudgetController::class, 'cancel']);
Route::post('budgets/generate-pdf', [BudgetController::class, 'generatePdf']);
Route::post('budgets/place-order', [BudgetController::class, 'placeOrder']);
Route::post('budgets/register-payment', [BudgetController::class, 'registerPayment']);
Route::post('budgets/layouts/update-column', [BudgetController::class, 'updateLayoutColumn']);
Route::post('budgets/upload-referring-file', [BudgetController::class, 'uploadReferringFile']);
Route::put('budgets/{id}', [BudgetController::class, 'update']);
Route::get('budgets/{budget}', [BudgetController::class, 'show']);
Route::delete('budgets/{budget}', [BudgetController::class, 'destroy']);

// Order Budgets
Route::put('budgets/order-budgets/{orderBudget}/description', [OrderBudgetController::class, 'updateDescription']);
Route::post('budgets/order-budgets/{orderBudget}/comments', [OrderBudgetController::class, 'addComment']);
Route::put('budgets/order-budgets/{orderBudget}/comments/{comment}', [OrderBudgetController::class, 'updateComment']);
Route::delete('budgets/order-budgets/{orderBudget}/comments/{comment}', [OrderBudgetController::class, 'deleteComment']);
Route::post('budgets/order-budgets/{orderBudget}/members', [OrderBudgetController::class, 'addMember']);
Route::delete('budgets/order-budgets/{orderBudget}/members', [OrderBudgetController::class, 'removeMember']);
Route::delete('budgets/order-budgets/{orderBudget}/members/{member}', [OrderBudgetController::class, 'removeMember']);
Route::post('budgets/order-budgets/upload-art', [BudgetController::class, 'uploadArt']);
