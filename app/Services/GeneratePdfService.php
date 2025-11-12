<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class GeneratePdfService
{
    /**
     * @param  \App\Models\Budget  $budget
     * @param  float|null  $percentageValidated
     */
    public function generateBudgetPdf(object $budget, ?float $percentageValidated = null): Response
    {
        $percentage = isset($percentageValidated)
            ? (float) $percentageValidated
            : 0.0;

        $originalTotal = (float) $budget->total_amount;
        $surcharge = round($originalTotal * ($percentage / 100), 2);
        $updatedTotal = round($originalTotal + $surcharge, 2);

        $filename = sprintf('orcamento-%s.pdf', $budget->id);

        $financial = [
            'total' => $updatedTotal,
            'total_formatted' => $this->formatMoney($updatedTotal),
        ];

        $pdf = Pdf::loadView('pdf.budgets.budget', [
            'budget' => $budget,
            'financial' => $financial,
        ])->setPaper('a4');

        $domPdf = $pdf->getDomPDF();
        $domPdf->set_option('isHtml5ParserEnabled', true);
        $domPdf->set_option('isPhpEnabled', true);
        $domPdf->set_option('defaultFont', 'DejaVu Sans');

        return $pdf->download($filename);
    }

    protected function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}
