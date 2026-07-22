<?php

namespace App\Http\Controllers;

use App\Actions\Budget\DownloadBudgetPdfAction;
use App\Http\Requests\DownloadBudgetPreviewRequest;
use App\Http\Requests\IndexBudgetPreviewRequest;
use App\Models\Budget;
use App\Repositories\BudgetRepository;
use App\Services\GeneratePdfService;
use App\ViewModels\BudgetViewModel;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class BudgetPreviewController extends Controller
{
    public function __construct(
        protected BudgetRepository $repository,
        protected GeneratePdfService $generatePdfService,
    ) {}

    public function index(
        Budget $budget,
        IndexBudgetPreviewRequest $request
    ): View {
        $budget->load([
            'rooms.walls.collectionModel',
            'tenant.reseller',
        ]);

        $validated = $request->validated();

        $viewModel = new BudgetViewModel(
            budget: $budget,
            overrides: $validated
        );

        $mockupPercentage = (float) ($validated['mockup_percentage'] ?? $validated['percentage'] ?? 1);

        $this->repository->updateMarkup($budget, $mockupPercentage > 0 ? $mockupPercentage : 1.0);

        return view('documents.budgets.budget', compact('viewModel'));
    }

    public function download(
        Budget $budget,
        DownloadBudgetPreviewRequest $request,
        DownloadBudgetPdfAction $action,
    ): Response {
        $budget->load(['rooms.walls.collectionModel', 'dropshippingData']);

        $validated = $request->validated();

        $viewModel = new BudgetViewModel(
            budget: $budget,
            overrides: $validated
        );

        $mockupPercentage = (float) ($validated['mockup_percentage'] ?? $validated['percentage'] ?? 1);

        $this->repository->updateMarkup($budget, $mockupPercentage > 0 ? $mockupPercentage : 1.0);

        return $action->execute($viewModel);
    }
}
