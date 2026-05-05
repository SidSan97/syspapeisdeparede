<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBudgetPdfRequest;
use App\Models\Budget;
use App\Repositories\BudgetRepository;
use App\Services\GeneratePdfService;
use Illuminate\Http\Response;

class BudgetPdfController extends Controller
{
    public function __construct(
        protected BudgetRepository $repository,
        protected GeneratePdfService $generatePdfService,
    ) {}

    public function store(Budget $budget, StoreBudgetPdfRequest $request): Response
    {
        $budget->load(['rooms.walls.collectionModel', 'dropshippingData']);

        $validated = $request->validated();

        // Aceitar tanto cash_value quanto total_amount (para compatibilidade)
        $cashValue = $validated['total_amount'] ?? $validated['cash_value'] ?? null;
        $installmentValue = $validated['total_amount_installments'] ?? $validated['installment_value'] ?? null;
        $mockupPercentage = $validated['mockup_percentage'] ?? $validated['percentage'] ?? null;

        $this->repository->updateMarkup($budget, $mockupPercentage);

        return $this->generatePdfService->generateBudgetPdf(
            $budget,
            $mockupPercentage,
            $cashValue,
            $installmentValue
        );
    }
}
