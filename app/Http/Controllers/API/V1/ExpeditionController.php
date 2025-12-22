<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use App\Services\ExpeditionService;
use App\Services\GeneratePdfService;
use Illuminate\Support\Facades\Log;

class ExpeditionController extends Controller
{
    protected $OrderRepository;
    protected $expeditionService;
    protected $orderBudgetRepository;
    protected $orderRepository;
    protected $generatePdfService;

    public function __construct(
        OrderRepository $OrderRepository,
        ExpeditionService $expeditionService,
        OrderBudgetRepository $orderBudgetRepository,
        OrderRepository $orderRepository,
        GeneratePdfService $generatePdfService
    )
    {
        $this->middleware('auth:api');
        $this->OrderRepository = $OrderRepository;
        $this->expeditionService = $expeditionService;
        $this->orderBudgetRepository = $orderBudgetRepository;
        $this->orderRepository = $orderRepository;
        $this->generatePdfService = $generatePdfService;
    }

    /**
     * Gera etiqueta de separação para um pedido (retorna JSON)
     *
     * @param  int  $orderBudgetId
     * @return JsonResponse
     */
    public function generateSeparationLabel(int $orderBudgetId): JsonResponse
    {
        try {
            $orderBudgets = $this->orderBudgetRepository->show($orderBudgetId);
            $order = $this->orderRepository->find($orderBudgets->order_id);

            $label = $this->expeditionService->generateLabelSeparation($orderBudgets, $order);

            return response()->json([
                'success' => true,
                'data' => $label,
                'message' => 'Etiqueta gerada com sucesso!',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao gerar etiqueta de separação: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar etiqueta de separação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gera PDF da etiqueta de separação
     *
     * @param  int  $orderBudgetId
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function generateSeparationLabelPdf(int $orderBudgetId)
    {
        try {
            $orderBudgets = $this->orderBudgetRepository->show($orderBudgetId);
            $label = $this->expeditionService->generateLabelSeparation($orderBudgets);

            return $this->generatePdfService->generateSeparationLabelPdf(
                $label['title'],
                $label['status']
            );
        } catch (\Exception $e) {
            Log::error('Erro ao gerar PDF da etiqueta de separação: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF da etiqueta de separação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
