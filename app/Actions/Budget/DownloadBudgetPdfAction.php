<?php

declare(strict_types=1);

namespace App\Actions\Budget;

use App\Actions\Pdf\DownloadPdfAction;
use App\ViewModels\BudgetViewModel;
use Symfony\Component\HttpFoundation\Response;

class DownloadBudgetPdfAction
{
    public function __construct(
        protected DownloadPdfAction $downloadPdfAction
    ) {}

    public function execute(BudgetViewModel $viewModel): Response
    {
        $filename = sprintf('orcamento-%s.pdf', $viewModel->budget->id);

        return $this->downloadPdfAction->execute(
            'documents.budgets.budget',
            compact('viewModel'),
            $filename
        );
    }
}
