<?php

namespace App\Services;

use App\Models\Budget;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class GeneratePdfService
{
    /**
     * @param  Budget  $budget
     */
    public function generateBudgetPdf(
        object $budget,
        ?float $percentageValidated = null,
        ?float $cashValue = null,
        ?float $installmentValue = null,
        ?string $observations = null
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
            'observations' => $observations,
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
     * @param  array<string, mixed>|array<int, array<string, mixed>>  $labelOrLabels
     */
    public function generateSeparationLabelPdf(array $labelOrLabels): Response
    {
        $labels = array_is_list($labelOrLabels) && isset($labelOrLabels[0]) && is_array($labelOrLabels[0])
            ? $labelOrLabels
            : [$labelOrLabels];

        return $this->generateSeparationLabelsPdf($labels);
    }

    /**
     * Gera PDF com uma ou mais etiquetas de separação (grid quando houver mais de uma).
     *
     * @param  array<int, array<string, mixed>>  $labels
     */
    public function generateSeparationLabelsPdf(array $labels): Response
    {
        $filename = count($labels) > 1
            ? 'etiquetas-separacao.pdf'
            : 'etiqueta-separacao.pdf';

        $normalizedLabels = array_map(
            fn (array $label): array => $this->normalizeSeparationLabel($label),
            $labels
        );

        $pdf = Pdf::loadView('pdf.expedition.separation-label', [
            'labels' => $normalizedLabels,
        ])->setPaper('a4', 'portrait');

        $domPdf = $pdf->getDomPDF();
        $domPdf->set_option('isHtml5ParserEnabled', true);
        $domPdf->set_option('isPhpEnabled', true);
        $domPdf->set_option('defaultFont', 'DejaVu Sans');

        return $pdf->stream($filename);
    }

    /**
     * @param  array<string, mixed>  $label
     * @return array<string, mixed>
     */
    protected function normalizeSeparationLabel(array $label): array
    {
        return [
            'title' => $label['title'] ?? '',
            'status' => strtoupper((string) ($label['status'] ?? '')),
            'card_name' => $label['card_name'] ?? null,
            'model_name' => $label['model_name'] ?? null,
            'model_art_name' => $label['model_art_name'] ?? null,
            'observation' => $label['observation'] ?? null,
            'layout_quantity' => $label['layout_quantity'] ?? null,
            'strip_groups' => $label['strip_groups'] ?? [],
            'carrier_name' => $label['carrier_name'] ?? null,
        ];
    }

    protected function formatMoney(float $value): string
    {
        return 'R$ '.number_format($value, 2, ',', '.');
    }
}
