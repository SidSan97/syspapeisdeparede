<?php

use App\Http\Controllers\API\V1\OrderController;
use App\Http\Controllers\API\V1\OrderExpeditionController;
use App\Http\Controllers\API\V1\OrderProductionController;
use App\Http\Controllers\API\V1\OrderReportController;
use Illuminate\Support\Facades\Route;

// Pedidos
Route::get('orders', [OrderController::class, 'index']);
Route::post('orders/merge', [OrderController::class, 'merge']);
Route::get('orders/layouts/{orderId?}', [OrderController::class, 'layouts']);

Route::get('orders/production-layouts/{orderId?}', [OrderController::class, 'productionLayouts']);
Route::get('orders/expedition', [OrderExpeditionController::class, 'expedition']);
Route::get('orders/ready-for-invoice', [OrderExpeditionController::class, 'readyForInvoice']);
Route::post('orders/order-budgets/ready-to-expedition', [OrderExpeditionController::class, 'invoiceOrderCards']);
Route::post('orders/{order}/approve', [OrderProductionController::class, 'approve']);
Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);
Route::post('orders/{order}/payment-links', [OrderController::class, 'generatePaymentLink']);
Route::post('orders/{order}/generate-payment-link', [OrderController::class, 'generateLegacyPaymentLink']);

// Order Budgets
Route::post('orders/order-budgets/{orderBudget}/mark-as-produced', [OrderProductionController::class, 'markAsProduced']);
Route::put('orders/order-budgets/{orderBudget}/production-percentage', [OrderProductionController::class, 'updateProductionPercentage']);
Route::get('orders/order-budgets/{orderBudget}/production-reports', [OrderReportController::class, 'getProductionReports']);
Route::get('orders/production-reports/{report}/download-pdf', [OrderReportController::class, 'downloadProductionReportPdf']);

Route::put('orders/{order}', [OrderController::class, 'update']);
Route::get('orders/{order}', [OrderController::class, 'show']);
Route::delete('orders/{order}', [OrderController::class, 'destroy']);
