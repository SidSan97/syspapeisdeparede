<?php

use App\Http\Controllers\API\V1\ExpeditionController;
use Illuminate\Support\Facades\Route;

// Expedição
Route::get('generate-separation-label/{orderBudgetId}', [ExpeditionController::class, 'generateSeparationLabel']);
Route::get('generate-separation-label-pdf/{orderBudgetId}', [ExpeditionController::class, 'generateSeparationLabelPdf']);
Route::post('generate-invoice/{orderId}', [ExpeditionController::class, 'generateInvoice']);
Route::get('search-invoices', [ExpeditionController::class, 'searchInvoices']);
Route::get('generate-danfe/{id}', [ExpeditionController::class, 'generateDanfe']);
Route::post('send-invoice-to-expedition', [ExpeditionController::class, 'sendInvoiceToExpedition']);
Route::get('search-groupings/{carrier}', [ExpeditionController::class, 'searchGroupings']);
Route::get('generate-grouping-print-label/{groupingId}', [ExpeditionController::class, 'printCarrierLabels']);
