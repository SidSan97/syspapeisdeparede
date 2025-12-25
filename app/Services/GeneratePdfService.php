<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class GeneratePdfService
{
    /**
     * @param  \App\Models\Budget  $budget
     * @param  float|null  $percentageValidated
     * @param  float|null  $cashValue
     * @param  float|null  $installmentValue
     */
    public function generateBudgetPdf(
        object $budget,
        ?float $percentageValidated = null,
        ?float $cashValue = null,
        ?float $installmentValue = null
    ): Response {
        $originalCashTotal = (float) $budget->total_amount;
        $originalInstallmentTotal = (float) ($budget->total_amount_installments ?? 0);

        // Se valores editados foram fornecidos, usar eles
        if ($cashValue !== null) {
            $updatedCashTotal = round((float) $cashValue, 2);
        } else {
            // Caso contrário, calcular com porcentagem se fornecida
            $percentage = isset($percentageValidated)
                ? (float) $percentageValidated
                : 0.0;
            $surcharge = round($originalCashTotal * ($percentage / 100), 2);
            $updatedCashTotal = round($originalCashTotal + $surcharge, 2);
        }

        // Calcular valor a prazo
        $updatedInstallmentTotal = null;
        if ($installmentValue !== null) {
            $updatedInstallmentTotal = round((float) $installmentValue, 2);
        } elseif ($originalInstallmentTotal > 0) {
            // Se não foi fornecido valor editado, calcular com porcentagem se fornecida
            $percentage = isset($percentageValidated)
                ? (float) $percentageValidated
                : 0.0;
            $surchargeInstallment = round($originalInstallmentTotal * ($percentage / 100), 2);
            $updatedInstallmentTotal = round($originalInstallmentTotal + $surchargeInstallment, 2);
        }

        $filename = sprintf('orcamento-%s.pdf', $budget->id);

        $financial = [
            'cash_total' => $updatedCashTotal,
            'cash_total_formatted' => $this->formatMoney($updatedCashTotal),
            'installment_total' => $updatedInstallmentTotal,
            'installment_total_formatted' => $updatedInstallmentTotal !== null
                ? $this->formatMoney($updatedInstallmentTotal)
                : null,
            'mockup_percentage' => $percentageValidated ?? 0,
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

    /**
     * Gera PDF da etiqueta de separação
     *
     * @param  string  $title
     * @param  string  $status
     * @return Response
     */
    public function generateSeparationLabelPdf(string $title, string $status): Response
    {
        $filename = 'etiqueta-separacao.pdf';

        $pdf = Pdf::loadView('pdf.expedition.separation-label', [
            'title' => $title,
            'status' => strtoupper($status),
        ])->setPaper('a4', 'portrait');

        $domPdf = $pdf->getDomPDF();
        $domPdf->set_option('isHtml5ParserEnabled', true);
        $domPdf->set_option('isPhpEnabled', true);
        $domPdf->set_option('defaultFont', 'DejaVu Sans');

        return $pdf->stream($filename);
    }

    protected function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}
