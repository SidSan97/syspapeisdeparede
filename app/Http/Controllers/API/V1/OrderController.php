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

class OrderController extends Controller
{
    protected $repository;
    protected $layoutService;
    protected $generatePaymentService;
    protected $orderBudget;
    protected $orderBudgetRepository;

    public function __construct(OrderRepository $repository,
        LayoutService $layoutService,
        GeneratePaymentService $generatePaymentService,
        OrderBudgetRepository $orderBudgetRepository,
        OrderBudget $orderBudget
    )
    {
        $this->middleware('auth:api');
        $this->repository = $repository;
        $this->layoutService = $layoutService;
        $this->generatePaymentService = $generatePaymentService;
        $this->orderBudget = $orderBudget;
        $this->orderBudgetRepository = $orderBudgetRepository;
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
            $budgetPaymentData = BudgetResource::getBudgetPaymentData($order->toArray());

            $order->update(['status' => 'Aprovado']);
            $order->update(['paid' => 1]);

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
            ], 500);
        }
    }

    public function expedition(): JsonResponse
    {
        try {
            $orderBudgets = $this->orderBudgetRepository->getReadyForExpedition();

            return response()->json([
                'success' => true,
                'data' => $orderBudgets,
                'message' => 'Lista de expedições recuperada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar expedições',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

