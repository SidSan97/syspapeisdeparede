<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use App\Services\ExpeditionService;
use App\Services\GeneratePdfService;
use Illuminate\Support\Facades\Log;
use App\Services\TinyErpService;
use App\Repositories\DropshippingRepository;

class ExpeditionController extends Controller
{
    protected $OrderRepository;
    protected $expeditionService;
    protected $orderBudgetRepository;
    protected $orderRepository;
    protected $generatePdfService;
    protected $tinyErpService;
    protected $dropshippingRepository;

    public function __construct(
        OrderRepository $OrderRepository,
        ExpeditionService $expeditionService,
        OrderBudgetRepository $orderBudgetRepository,
        OrderRepository $orderRepository,
        GeneratePdfService $generatePdfService,
        TinyErpService $tinyErpService,
        DropshippingRepository $dropshippingRepository
    )
    {
        $this->middleware('auth:api');
        $this->OrderRepository = $OrderRepository;
        $this->expeditionService = $expeditionService;
        $this->orderBudgetRepository = $orderBudgetRepository;
        $this->orderRepository = $orderRepository;
        $this->generatePdfService = $generatePdfService;
        $this->tinyErpService = $tinyErpService;
        $this->dropshippingRepository = $dropshippingRepository;
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
            $order = $this->orderRepository->find($orderBudgets->order_id);

            $label = $this->expeditionService->generateLabelSeparation($orderBudgets, $order);

            return $this->generatePdfService->generateSeparationLabelPdf($label);
        } catch (\Exception $e) {
            Log::error('Erro ao gerar PDF da etiqueta de separação: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF da etiqueta de separação',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateInvoice(int $orderId): JsonResponse
    {
        try {
            $order = $this->orderRepository->find($orderId);
            $dropshipping = $this->dropshippingRepository->findDropshippingByOrderId($order->id);

            $invoiceData = $this->tinyErpService->sendInvoice($order->toArray(), $dropshipping->toArray());

            if($invoiceData['status'] === 'Erro') {
                $statusCode = $invoiceData['status_processamento'];

                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao enviar nota fiscal. Tente novamente mais tarde.',
                    'error' => $statusCode == 2 ? $invoiceData['registros']['registro']['erros']
                               : $invoiceData['erros'],
                ], 500);
            }

            $nfData = [
                'nf_id' => $invoiceData['registros']['registro']['id'],
                'nf_number' => $invoiceData['registros']['registro']['numero'],
                'nf_serie' => $invoiceData['registros']['registro']['serie'],
            ];

            $issueInvoice = $this->tinyErpService->issueInvoice($nfData);

            if($issueInvoice['status'] === 'Erro') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao emitir nota fiscal. Tente novamente mais tarde.',
                    'error' => $issueInvoice['erros'],
                ], 500);
            }

            $this->orderRepository->updateNfSent($orderId);
            $this->orderRepository->updateNfId($orderId, $issueInvoice['nota_fiscal']['id']);

            return response()->json([
                'success' => true,
                'message' => 'Nota fiscal gerada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Erro ao gerar nota fiscal: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar nota fiscal',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function searchInvoices(): JsonResponse
    {
        try {
            $invoices = $this->tinyErpService->searchInvoices();

            return response()->json([
                'success' => true,
                'data' => $invoices,
                'message' => 'Notas fiscais encontradas com sucesso',
            ], 200);
        }
        catch (\Exception $e) {
            Log::error('Erro ao buscar notas fiscais: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar notas fiscais',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function generateDanfe(string $invoiceId): JsonResponse
    {
        try {
            $danfe = $this->tinyErpService->generateDanfe($invoiceId);

            if($danfe['status'] === 'Erro') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao obter link da DANFE. Tente novamente mais tarde.',
                    'error' => $danfe['erros'],
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => $danfe,
                'message' => 'DANFE gerada com sucesso',
            ], 200);
        }
        catch (\Exception $e) {
            Log::error('Erro ao gerar DANFE: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar DANFE',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
