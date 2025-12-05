<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetResource;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderBudget;
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

    public function __construct(OrderRepository $repository,
        LayoutService $layoutService,
        GeneratePaymentService $generatePaymentService,
        OrderBudget $orderBudget
    )
    {
        $this->middleware('auth:api');
        $this->repository = $repository;
        $this->layoutService = $layoutService;
        $this->generatePaymentService = $generatePaymentService;
        $this->orderBudget = $orderBudget;
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

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->repository->find($id);

            if (!$order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pedido não encontrado',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'user_id' => 'sometimes|nullable|exists:users,id',
                'tenant_id' => 'sometimes|nullable|exists:users,id',
                'primary_budget_room_id' => 'sometimes|nullable|exists:budget_rooms,id',
                'total_area' => 'sometimes|nullable|numeric|min:0',
                'total_amount' => 'sometimes|nullable|numeric|min:0',
                'total_amount_installments' => 'sometimes|nullable|numeric|min:0',
                'delivery_time' => 'sometimes|nullable|integer|min:0',
                'payment_method' => 'sometimes|nullable|string',
                'installment_limit' => 'sometimes|nullable|integer|min:1',
                'installments' => 'sometimes|nullable|integer|min:1',
                'cep' => 'sometimes|nullable|string|max:9',
                'selected_carrier_name' => 'sometimes|nullable|string',
                'selected_carrier_price' => 'sometimes|nullable|numeric|min:0',
                'selected_carrier_delivery_time' => 'sometimes|nullable|integer|min:0',
                'carriers_snapshot' => 'sometimes|nullable|array',
                'status' => 'sometimes|nullable|string|max:50',
                'payment_file' => 'sometimes|nullable|string',
                'comment_referring_model' => 'sometimes|nullable|string|max:500',
                'link_referring_model' => 'sometimes|nullable|string|max:150',
                'files_referring_model' => 'sometimes|nullable|array',
                'collection_referring_model' => 'sometimes|nullable|string',
                'dropshipping_budget' => 'sometimes|nullable|boolean',
            ]);

            $order = $this->repository->update($order, $validated);
            $transformed = new OrderResource($order);

            return response()->json([
                'success' => true,
                'data' => $transformed->toArray(request()),
                'message' => 'Pedido atualizado com sucesso',
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação',
                'data' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar pedido',
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
            'id' => ['required', 'integer', 'exists:budgets,id'],
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
}

