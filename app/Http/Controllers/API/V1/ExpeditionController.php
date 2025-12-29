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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

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

            Cache::forget('tiny_erp_invoices');

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
            $cacheKey = 'tiny_erp_invoices';
            $cachedData = Cache::get($cacheKey);

            if ($cachedData !== null) {
                return response()->json([
                    'success' => true,
                    'data' => $cachedData,
                    'message' => 'Notas fiscais encontradas com sucesso',
                ], 200);
            }

            $invoices = $this->tinyErpService->searchInvoices();

            Cache::put($cacheKey, $invoices, now()->addHours(24));

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

    public function sendInvoiceToExpedition(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'invoice_ids' => 'required|array|min:1',
                'invoice_ids.*' => 'required|integer|min:1',
                'carrier' => 'required|string|max:255',
            ]);

            $invoiceIds = $validated['invoice_ids'];
            $carrier = $validated['carrier'];

            // Validar se todas as notas fiscais têm o mesmo transportador
            $this->expeditionService->validateSameCarrier($invoiceIds, $carrier);

            $invoiceIdsString = implode(',', $invoiceIds);

            $sendExpedition = $this->tinyErpService->sendInvoiceToExpedition($invoiceIdsString, 'notafiscal');

            if($sendExpedition['status'] === 'Erro') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao enviar nota fiscal para expedição. Tente novamente mais tarde.',
                    'error' => $sendExpedition['erros'],
                ], 500);
            }

            $this->tinyErpService->changeExpedition($sendExpedition['objetos']['objeto']['idExpedicao'], $carrier);

            $includeGroupingInvoices = $this->tinyErpService->includeGroupingInvoices($sendExpedition['objetos']['objeto']['idExpedicao']);

            if($includeGroupingInvoices['status'] === 'Erro') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao incluir agrupamento de notas fiscais. Tente novamente mais tarde.',
                    'error' => $includeGroupingInvoices['erros'],
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Notas fiscais enviadas para expedição com sucesso',
                'data' => $includeGroupingInvoices,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao enviar nota fiscal para expedição: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar notas fiscais para expedição',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
