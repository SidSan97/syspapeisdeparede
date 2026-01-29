<?php

use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\OrderExpeditionController;
use App\Http\Controllers\API\V1\OrderProductionController;
use App\Http\Controllers\API\V1\OrderReportController;
use Illuminate\Support\Facades\Route;

// Pedidos
Route::get('orders', [OrderController::class, 'index']);
Route::get('orders/layouts', [OrderController::class, 'layouts']);
Route::get('orders/production-layouts', [OrderController::class, 'productionLayouts']);
Route::get('orders/expedition', [OrderExpeditionController::class, 'expedition']);
Route::get('orders/ready-for-invoice', [OrderExpeditionController::class, 'readyForInvoice']);
Route::get('orders/{id}', [OrderController::class, 'show']);
Route::post('orders/approve', [OrderProductionController::class, 'approve']);
Route::post('orders/cancel', [OrderController::class, 'cancel']);
Route::post('orders/{id}/generate-payment-link', [OrderController::class, 'generatePaymentLink']);
Route::put('orders/{id}', [OrderController::class, 'update']);
Route::delete('orders/{order}', [OrderController::class, 'destroy']);

// Order Budgets
Route::post('orders/order-budgets/{orderBudget}/mark-as-produced', [OrderProductionController::class, 'markAsProduced']);
Route::put('orders/order-budgets/{orderBudget}/production-percentage', [OrderProductionController::class, 'updateProductionPercentage']);
Route::get('orders/order-budgets/{orderBudget}/production-reports', [OrderReportController::class, 'getProductionReports']);
Route::get('orders/production-reports/{report}/download-pdf', [OrderReportController::class, 'downloadProductionReportPdf']);
