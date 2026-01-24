<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\ProductionReport;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderReportController extends Controller
{
    /**
     * Lista os relatórios de produção de um card de produção
     */
    public function getProductionReports(Request $request, int $orderBudgetId): JsonResponse
    {
        $reports = ProductionReport::where('order_budget_id', $orderBudgetId)
            ->with(['user:id,name', 'orderBudget:id,description'])
            ->orderBy('action_date', 'desc')
            ->get();

        return response()->json($reports);
    }

    /**
     * Gera e retorna PDF do relatório de produção
     */
    public function downloadProductionReportPdf(int $reportId)
    {
        $report = ProductionReport::with([
            'user:id,name',
            'orderBudget:id,description',
            'orderBudget.wall:id,name,width,height,total_area,strip_height,strip_count',
            'orderBudget.wall.room:id,name',
            'orderBudget.wall.collectionModel:id,name',
        ])->findOrFail($reportId);

        $filename = sprintf('relatorio-producao-%s-%s.pdf', $report->order_budget_id, $report->id);

        $pdf = Pdf::loadView('pdf.production.report', [
            'report' => $report,
        ])->setPaper('a4', 'portrait');

        $domPdf = $pdf->getDomPDF();
        $domPdf->set_option('isHtml5ParserEnabled', true);
        $domPdf->set_option('isPhpEnabled', true);
        $domPdf->set_option('defaultFont', 'DejaVu Sans');

        return $pdf->download($filename);
    }
}
