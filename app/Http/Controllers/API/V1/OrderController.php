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

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'search' => $request->input('search'),
                'status' => $request->input('status', 'all'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
                'user_id' => $request->input('user_id'),
            ];

            $perPage = $request->input('per_page', 15);
            $paginatedOrders = $this->repository->paginate($filters, $perPage);

            $data = BudgetResource::collection($paginatedOrders->items())->toArray(request());

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $data,
                    'current_page' => $paginatedOrders->currentPage(),
                    'last_page' => $paginatedOrders->lastPage(),
                    'per_page' => $paginatedOrders->perPage(),
                    'total' => $paginatedOrders->total(),
                    'from' => $paginatedOrders->firstItem(),
                    'to' => $paginatedOrders->lastItem(),
                ],
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
                        null,
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

    public function generatePaymentLink(Request $request, int $id): JsonResponse
    {
        try {
            $order = Order::findOrFail($id);

            // Gerar link de pagamento
            $paymentLinkResponse = $this->generatePaymentService->generateLinkPayment($order->toArray());
            $paymentLinkData = json_decode($paymentLinkResponse->getContent(), true);

            if (!($paymentLinkData['success'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao gerar link de pagamento: ' . ($paymentLinkData['message'] ?? 'Erro desconhecido'),
                ], 500);
            }

            // Extrair URL e data de expiração
            $apiResponse = $paymentLinkData['data'] ?? [];
            $paymentUrl = $apiResponse['url'] ?? null;
            $expirationDate = $apiResponse['expiration_date'] ?? $apiResponse['expires_at'] ?? null;

            if (!$paymentUrl) {
                return response()->json([
                    'success' => false,
                    'message' => 'URL de pagamento não encontrada na resposta',
                ], 500);
            }

            // Salvar URL e data de expiração no pedido
            $order->update([
                'link_payment' => $paymentUrl,
                'payment_expiration_date' => $expirationDate,
            ]);

            $transformed = (new OrderResource($order->refresh()))->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'message' => 'Link de pagamento gerado com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar link de pagamento: ' . $e->getMessage(),
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
}

