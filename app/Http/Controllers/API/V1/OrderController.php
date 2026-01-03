<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Http\Resources\BudgetResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderBudget;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\LayoutService;
use App\Services\GeneratePaymentService;
use App\Repositories\DropshippingRepository;
use App\Services\TinyErpService;
use App\Services\GeneratePdfService;
use App\Models\ProductionReport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    protected $repository;
    protected $layoutService;
    protected $generatePaymentService;
    protected $orderBudget;
    protected $orderBudgetRepository;
    protected $dropshippingRepository;
    protected $tinyErpService;

    public function __construct(OrderRepository $repository,
        LayoutService $layoutService,
        GeneratePaymentService $generatePaymentService,
        OrderBudgetRepository $orderBudgetRepository,
        OrderBudget $orderBudget,
        DropshippingRepository $dropshippingRepository,
        TinyErpService $tinyErpService
    )
    {
        $this->middleware('auth:api');
        $this->repository = $repository;
        $this->layoutService = $layoutService;
        $this->generatePaymentService = $generatePaymentService;
        $this->orderBudget = $orderBudget;
        $this->orderBudgetRepository = $orderBudgetRepository;
        $this->dropshippingRepository = $dropshippingRepository;
        $this->tinyErpService = $tinyErpService;
    }

    public function index(): JsonResponse
    {
        try {
            $orders = $this->repository->all();
            $data = BudgetResource::collection($orders)->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lista de pedidos recuperada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function all(): JsonResponse
    {
        try {
            $orders = $this->repository->all();
            $data = OrderResource::collection($orders)->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lista de pedidos',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos',
            ], 500);
        }
    }

    public function layouts(): JsonResponse
    {
        try {
            $orderBudgets = $this->repository->getLayoutsForApprove();
            $data = $this->layoutService->transformLayouts($orderBudgets, 'layout');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lista de layouts',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar layouts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $order = $this->repository->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $transformed = new OrderResource($order);

            return response()->json([
                'success' => true,
                'data' => $transformed->toArray(request()),
                'message' => 'Pedido recuperado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar pedido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->repository->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $validated = $request->validated();
            $order = $this->repository->update($order, $validated);

            if (!empty($validated['dropshipping_data']) && $validated['dropshipping_budget'] === 1) {
                $existingDropshipping = $order->dropshippingData;

                if ($existingDropshipping) {
                    $this->dropshippingRepository->update(
                        $validated['dropshipping_data'],
                        $existingDropshipping->id
                    );
                } else {
                    $this->dropshippingRepository->create(
                        $validated['dropshipping_data'],
                        $order->id,
                        Auth::id()
                    );
                }
            } elseif (isset($validated['dropshipping_budget']) && $validated['dropshipping_budget'] === 0) {
                $order->dropshippingData()->delete();
            }

            $transformed = new OrderResource($order);

            return response()->json([
                'success' => true,
                'data' => $transformed->toArray(request()),
                'message' => 'Pedido atualizado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar pedido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $order = $this->repository->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $this->repository->delete($order);

            return response()->json([
                'success' => true,
                'message' => 'Pedido excluído com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir pedido',
            ], 500);
        }
    }

    public function getByStatus(Request $request, string $status): JsonResponse
    {
        try {
            $orders = $this->repository->getByStatus($status);
            $data = OrderResource::collection($orders)->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => "Lista de pedidos com status: {$status}",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos por status',
            ], 500);
        }
    }

    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        try {
            $order = Order::with(['rooms.walls'])->findOrFail($validated['id']);
            if($order->dropshipping_budget) {
                $dropshippingBudget = $this->dropshippingRepository->findDropshippingByOrderId($order->id);
                $accountPayable = $this->tinyErpService->sendAccountPayable($order->toArray(), $dropshippingBudget->toArray());
                $orderTiny = $this->tinyErpService->sendOrder($order->toArray(), $dropshippingBudget->toArray());

                if($orderTiny['status'] == "Erro") {
                    return response()->json([
                        'success' => false,
                        'message' => 'Houve um erro ao cadastrar o produto no ERP. Tente novamente mais tarde!',
                        'error' => $orderTiny['registros']['registro']['erros']
                    ], 403);
                }

                $this->orderBudgetRepository->updateTinyErpOrderId($order->id, $orderTiny['registros']['registro']['id']);
            }

            $order->update(['status' => 'Aprovado', 'paid' => 1]);

            // Buscar a primeira coluna de layout disponível (padrão: Desenhista)
            $firstColumn = \App\Models\LayoutColumnName::orderBy('id')->first();

            if (!$firstColumn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma coluna de layout configurada. Configure pelo menos uma coluna antes de aprovar orçamentos.',
                ], 400);
            }

            $this->orderBudget->where('order_id', $order->id)
                ->update(['status' => 'Liberado para produção']);

            $orderBudgets = $this->orderBudget->where('order_id', $order->id)->get();

            // Gerar link de pagamento
            $paymentLinkResponse = $this->generatePaymentService->generateLinkPayment($order->toArray());
            $paymentLinkData = json_decode($paymentLinkResponse->getContent(), true);

            // Extrair URL do link de pagamento
            $paymentUrl = null;
            if ($paymentLinkData['success'] ?? false) {
                // A API do Pagar.me retorna a URL em diferentes estruturas possíveis
                $apiResponse = $paymentLinkData['data'] ?? [];
                $paymentUrl = $apiResponse['url'] ?? $apiResponse['checkout_url'] ?? $apiResponse['public_url'] ?? null;
            }

            $transformed = (new OrderResource($order->refresh()))->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'order_budgets' => $orderBudgets,
                'payment_link' => [
                    'success' => $paymentLinkData['success'] ?? false,
                    'url' => $paymentUrl,
                    'data' => $paymentLinkData['data'] ?? null,
                ],
                'message' => 'Orçamento aprovado com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao aprovar orçamento: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function markAsProduced(Request $request, int $orderBudgetId): JsonResponse
    {
        try {
            $user = $request->user();
            $orderBudget = $this->orderBudgetRepository->markAsProduced(
                $orderBudgetId,
                $user,
                'product'
            );

            // Gerar relatório de produção
            $productionReportService = app(\App\Services\ProductionReportService::class);
            $productionReportService->generateMarkAsProducedReport($orderBudget, $user);

            return response()->json([
                'success' => true,
                'data' => $orderBudget,
                'message' => 'Data de produção atualizada com sucesso',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar data de produção: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateProductionPercentage(Request $request, int $orderBudgetId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'production_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
            ]);

            $user = $request->user();
            $orderBudget = $this->orderBudgetRepository->updateProductionPercentage(
                $orderBudgetId,
                $validated['production_percentage'],
                $user,
                'product'
            );

            if($validated['production_percentage'] == 100) {
                // Gerar relatório de produção quando atinge 100%
                $productionReportService = app(\App\Services\ProductionReportService::class);
                $productionReportService->generateProductionPercentageReport(
                    $orderBudget,
                    $user,
                    $validated['production_percentage']
                );
            }

            return response()->json([
                'success' => true,
                'data' => $orderBudget,
                'message' => 'Porcentagem de produção atualizada com sucesso',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
                'error' => $e->getMessage(),
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar porcentagem de produção: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function productionLayouts(): JsonResponse
    {
        try {
            $orderBudgets = $this->repository->getLayoutsForProduction();
            $data = $this->layoutService->transformLayouts($orderBudgets, 'product');

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lista de layouts de produção',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar layouts de produção: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        try {
            $order = $this->repository->find($validated['id']);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $orderUpdated = $this->repository->cancel($order);
            $transformed = (new OrderResource($orderUpdated))->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'message' => 'Pedido cancelado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar pedido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function expedition(): JsonResponse
    {
        try {
            $orderBudgets = $this->orderBudgetRepository->getReadyForPicking();

            return response()->json([
                'success' => true,
                'data' => $orderBudgets,
                'message' => 'Lista de separações recuperada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar separações',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function readyForInvoice(): JsonResponse
    {
        try {
            $orders = $this->repository->getReadyForInvoice();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Lista de pedidos prontos para faturar recuperada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos prontos para faturar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista os relatórios de produção de um card de produção
     */
    public function getProductionReports(Request $request, int $orderBudgetId): JsonResponse
    {
        try {
            $reports = ProductionReport::where('order_budget_id', $orderBudgetId)
                ->with(['user:id,name', 'orderBudget:id,description'])
                ->orderBy('action_date', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $reports,
                'message' => 'Relatórios de produção recuperados com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao recuperar relatórios de produção',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Gera e retorna PDF do relatório de produção
     */
    public function downloadProductionReportPdf(int $reportId)
    {
        try {
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Relatório não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF do relatório',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

