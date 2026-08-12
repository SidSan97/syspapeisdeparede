<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GenerateSeparationLabelsPdfRequest;
use App\Repositories\DropshippingRepository;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\OrderRepository;
use App\Services\ExpeditionService;
use App\Services\GeneratePdfService;
use App\Services\TinyErpService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

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
    ) {
        $this->middleware('auth:sanctum');
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
     */
    public function generateSeparationLabel(int $orderBudgetId): JsonResponse
    {
        $orderBudgets = $this->orderBudgetRepository->show($orderBudgetId);
        $order = $this->orderRepository->find($orderBudgets->order_id);

        $label = $this->expeditionService->generateLabelSeparation($orderBudgets, $order);

        return response()->json($label);
    }

    /**
     * Gera PDF da etiqueta de separação
     *
     * @return Response
     */
    public function generateSeparationLabelPdf(int $orderBudgetId)
    {
        $orderBudgets = $this->orderBudgetRepository->show($orderBudgetId);
        $order = $this->orderRepository->find($orderBudgets->order_id);

        $label = $this->expeditionService->generateLabelSeparation($orderBudgets, $order);

        return $this->generatePdfService->generateSeparationLabelPdf($label);
    }

    /**
     * Gera PDF com etiquetas de separação para vários order budgets.
     */
    public function generateSeparationLabelsPdf(GenerateSeparationLabelsPdfRequest $request)
    {
        $orderBudgetIds = $request->validated('order_budget_ids');
        $labels = $this->expeditionService->generateLabelsSeparation($orderBudgetIds);

        return $this->generatePdfService->generateSeparationLabelsPdf($labels);
    }

    public function generateInvoice(int $orderId): JsonResponse
    {
        $order = $this->orderRepository->find($orderId);
        $dropshipping = $this->dropshippingRepository->findDropshippingByOrderId($order->id);

        $invoiceData = $this->tinyErpService->sendInvoice($order->toArray(), $dropshipping->toArray());

        if ($invoiceData['status'] === 'Erro') {
            $statusCode = $invoiceData['status_processamento'];
            $errors = $statusCode == 2 ? $invoiceData['registros']['registro']['erros'] : $invoiceData['erros'];
            throw new \RuntimeException(is_string($errors) ? $errors : json_encode($errors));
        }

        $nfData = [
            'nf_id' => $invoiceData['registros']['registro']['id'],
            'nf_number' => $invoiceData['registros']['registro']['numero'],
            'nf_serie' => $invoiceData['registros']['registro']['serie'],
        ];

        $issueInvoice = $this->tinyErpService->issueInvoice($nfData);

        if ($issueInvoice['status'] === 'Erro') {
            throw new \RuntimeException(json_encode($issueInvoice['erros']));
        }

        $this->orderRepository->updateNfSent($orderId);
        $this->orderRepository->updateNfId($orderId, $issueInvoice['nota_fiscal']['id']);

        Cache::forget('tiny_erp_invoices');

        return response()->json(null, 204);
    }

    public function searchInvoices(): JsonResponse
    {
        $cacheKey = 'tiny_erp_invoices';
        $cachedData = Cache::get($cacheKey);

        if ($cachedData !== null) {
            return response()->json($cachedData);
        }

        $invoices = $this->tinyErpService->searchInvoices();
        $dropshippings = $this->dropshippingRepository->dropshippingFiltered();

        $filteredInvoices = $this->expeditionService->filterInvoices($invoices, $dropshippings->toArray());

        Cache::put($cacheKey, $filteredInvoices, now()->addHours(24));

        return response()->json($filteredInvoices);
    }

    public function searchGroupings(string $carrier): JsonResponse
    {
        $groupings = $this->tinyErpService->searchGroupings($carrier);

        if ($groupings instanceof JsonResponse) {
            return $groupings;
        }

        return response()->json($groupings);
    }

    public function generateDanfe(string $id): JsonResponse
    {
        $danfe = $this->tinyErpService->generateDanfe($id);

        if ($danfe['status'] === 'Erro') {
            throw new \RuntimeException(json_encode($danfe['erros']));
        }

        return response()->json($danfe);
    }

    public function printCarrierLabels(int|string $groupingId): JsonResponse
    {
        $label = $this->tinyErpService->printCarrierLabels($groupingId);

        if ($label['status'] === 'Erro') {
            throw new \RuntimeException(json_encode($label['erros']));
        }

        return response()->json($label);
    }

    public function sendInvoiceToExpedition(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'invoice_ids' => 'required|array|min:1',
                'invoice_ids.*' => 'required|integer|min:1',
                'carrier' => 'required|string|max:255',
                'order_ids' => 'required|array|min:1',
                'order_ids.*' => 'required|integer|min:1',
            ]);

            $invoiceIds = $validated['invoice_ids'];
            $carrier = $validated['carrier'];

            // Validar se todas as notas fiscais têm o mesmo transportador
            $this->expeditionService->validateSameCarrier($invoiceIds, $carrier);

            $invoiceIdsString = implode(',', $invoiceIds);

            // Enviar notas fiscais para expedição
            $sendExpedition = $this->tinyErpService->sendInvoiceToExpedition($invoiceIdsString, 'notafiscal');
            // dd($sendExpedition);

            if ($sendExpedition['status'] === 'Erro') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao enviar nota fiscal para expedição. Tente novamente mais tarde.',
                    'error' => $sendExpedition['erros'],
                ], 500);
            }

            // Alterar transportador das notas fiscais e add info das embalagens
            $this->tinyErpService->changeExpedition($sendExpedition['objetos'][0]['objeto']['idExpedicao'], $carrier);

            // Incluir agrupamento de notas fiscais
            $includeGroupingInvoices = $this->tinyErpService->includeGroupingInvoices($sendExpedition['objetos'][0]['objeto']['idExpedicao']);

            if ($includeGroupingInvoices['status'] === 'Erro') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao incluir agrupamento de notas fiscais. Tente novamente mais tarde.',
                    'error' => $includeGroupingInvoices['erros'],
                ], 500);
            }

            // Concluir agrupamento de notas fiscais
            $completeGrouping = $this->tinyErpService->completeGroupingInvoices($includeGroupingInvoices['idAgrupamento']);

            if ($completeGrouping['status'] === 'Erro') {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao concluir agrupamento de notas fiscais. Tente novamente mais tarde.',
                    'error' => $completeGrouping['erros'],
                ], 500);
            }

            // Alterar status dos pedidos
            foreach ($validated['order_ids'] as $orderId) {
                $this->orderRepository->changeStatusOrder((int) $orderId, 'Enviado');
            }

            return response()->json([
                'idAgrupamento' => $includeGroupingInvoices['idAgrupamento'] ?? null,
                'raw' => $includeGroupingInvoices,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Erro ao enviar nota fiscal para expedição: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Erro ao enviar notas fiscais para expedição',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
