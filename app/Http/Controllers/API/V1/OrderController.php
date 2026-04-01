<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Common\ListRequest;
use App\Http\Requests\Orders\GenerateOrderPaymentLinkRequest;
use App\Http\Requests\Orders\UpdateOrderRequest;
use App\Http\Resources\BudgetResource;
use App\Http\Resources\OrderResource;
use App\Models\Budget;
use App\Models\BudgetRoom;
use App\Models\Order;
use App\Models\OrderBudget;
use App\Models\OrderPaymentLink;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Services\LayoutService;
use App\Services\GeneratePaymentService;
use App\Services\OrderPaymentCompositionService;
use App\Repositories\DropshippingRepository;
use App\Services\TinyErpService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    protected $repository;
    protected $layoutService;
    protected $generatePaymentService;
    protected $orderBudget;
    protected $orderBudgetRepository;
    protected $dropshippingRepository;
    protected $tinyErpService;
    protected $paymentCompositionService;

    public function __construct(OrderRepository $repository,
        LayoutService $layoutService,
        GeneratePaymentService $generatePaymentService,
        OrderPaymentCompositionService $paymentCompositionService,
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
        $this->paymentCompositionService = $paymentCompositionService;
        $this->orderBudget = $orderBudget;
        $this->orderBudgetRepository = $orderBudgetRepository;
        $this->dropshippingRepository = $dropshippingRepository;
        $this->tinyErpService = $tinyErpService;
    }

    public function index(ListRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $filters = [
            'search' => $validated['search'] ?? null,
            'status' => $validated['status'] ?? 'all',
            'date_from' => $validated['date_from'] ?? null,
            'date_to' => $validated['date_to'] ?? null,
            'user_id' => $validated['user_id'] ?? null,
        ];

        $paginatedOrders = $this->repository->paginate($filters);

        return BudgetResource::collection($paginatedOrders)->response(); 
    }

    public function all(): JsonResponse
    {
        $orders = $this->repository->all();
        return OrderResource::collection($orders)->response();
    }

    public function layouts(): JsonResponse
    {
        $orderBudgets = $this->repository->getLayoutsForApprove();
        $data = $this->layoutService->transformLayouts($orderBudgets, 'layout');

        return response()->json($data);
    }

    public function show(int $id): JsonResponse
    {
        $order = $this->repository->find($id);

        if (!$order) {
            abort(404, 'Pedido não encontrado');
        }

        return (new OrderResource($order))->response();
    }

    public function update(UpdateOrderRequest $request, int $id): JsonResponse
    {
        $order = $this->repository->find($id);

        if (!$order) {
            abort(404, 'Pedido não encontrado');
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

        return (new OrderResource($order))->response();
    }

    public function getByStatus(Request $request, string $status): JsonResponse
    {
        $orders = $this->repository->getByStatus($status);
        return OrderResource::collection($orders)->response();
    }

    public function generatePaymentLink(GenerateOrderPaymentLinkRequest $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $validated = $request->validated();

        $paymentMethod = $validated['payment_method'];
        $installments = $paymentMethod === 'credit_card'
            ? (int) ($validated['installments'] ?? ($order->installments ?? 1))
            : null;

        $this->createPaymentLinkForOrder($order, $validated['components'], $paymentMethod, $installments);

        return (new OrderResource($order->refresh()))->response();
    }

    public function generateLegacyPaymentLink(int $id): JsonResponse
    {
        $order = Order::findOrFail($id);
        $paymentMethod = $order->payment_method === 'pix' ? 'pix' : 'credit_card';
        $installments = $paymentMethod === 'credit_card' ? (int) ($order->installments ?? 1) : null;
        $this->createPaymentLinkForOrder($order, ['ARTES', 'PRODUTOS', 'FRETE'], $paymentMethod, $installments);

        return (new OrderResource($order->refresh()))->response();
    }

    protected function createPaymentLinkForOrder(
        Order $order,
        array $components,
        string $paymentMethod,
        ?int $installments
    ): void {
        $calculation = $this->paymentCompositionService->calculateSelectedAmount(
            $order,
            $components,
            $paymentMethod
        );

        if (($calculation['amount_total'] ?? 0) <= 0) {
            abort(422, 'O valor total do link deve ser maior que zero.');
        }

        $payload = [
            'id' => $order->id,
            'name' => $order->name,
            'payment_method' => $paymentMethod,
            'installments' => $installments,
            'item_name' => 'Pedido #' . $order->id . ' - ' . implode(' + ', $calculation['components']),
            'item_description' => 'Componentes: ' . implode(', ', $calculation['components']),
            'item_amount' => (int) round($calculation['amount_total'] * 100),
            'installment_total' => (int) round($calculation['amount_total'] * 100),
            'shipping_cost' => 0,
            'metadata' => [
                'order_id' => (string) $order->id,
                'components' => implode(',', $calculation['components']),
                'adjustment_components' => implode(',', $this->resolveAdjustmentComponents($calculation, $paymentMethod)),
            ],
        ];

        $paymentLinkResponse = $this->generatePaymentService->generateLinkPayment($payload);
        $paymentLinkData = json_decode($paymentLinkResponse->getContent(), true);

        if (!($paymentLinkData['success'] ?? false)) {
            abort(500, 'Erro ao gerar link de pagamento: ' . ($paymentLinkData['message'] ?? 'Erro desconhecido'));
        }

        $apiResponse = $paymentLinkData['data'] ?? [];
        $paymentUrl = $apiResponse['url'] ?? null;
        $expirationDate = $apiResponse['expiration_date'] ?? $apiResponse['expires_at'] ?? null;
        $pagarmeOrderId = $apiResponse['id'] ?? null;
        $paymentLinkExternalId = $apiResponse['payment_link']['id'] ?? ($apiResponse['id'] ?? null);

        if (!$paymentUrl) {
            abort(500, 'URL de pagamento não encontrada na resposta');
        }

        $order->update([
            'link_payment' => $paymentUrl,
            'payment_expiration_date' => $expirationDate,
            'payment_status' => $order->payment_status ?: 'unpaid',
        ]);

        OrderPaymentLink::create([
            'order_id' => $order->id,
            'components' => $calculation['components'],
            'payment_method' => $paymentMethod,
            'installments' => $installments,
            'amount_artes' => $calculation['amount_artes'],
            'amount_produtos' => $calculation['amount_produtos'],
            'amount_frete' => $calculation['amount_frete'],
            'amount_total' => $calculation['amount_total'],
            'external_payment_link_id' => $paymentLinkExternalId,
            'external_order_id' => $pagarmeOrderId,
            'payment_url' => $paymentUrl,
            'status' => 'pending',
            'expires_at' => $expirationDate,
            'provider_payload' => [
                'api' => $apiResponse,
                'local' => [
                    'adjustment_components' => $this->resolveAdjustmentComponents($calculation, $paymentMethod),
                ],
            ],
        ]);
    }

    protected function resolveAdjustmentComponents(array $calculation, string $paymentMethod): array
    {
        $components = $calculation['components'] ?? [];
        $paid = $calculation['paid'] ?? [];
        $remaining = $calculation['remaining'] ?? [];

        $adjustments = [];
        foreach ($components as $component) {
            if ($component === 'ARTES') {
                if ((float) ($paid['ARTES'] ?? 0) > 0 && (float) ($remaining['ARTES'] ?? 0) > 0) {
                    $adjustments[] = 'ARTES';
                }
                continue;
            }

            if ($component === 'FRETE') {
                if ((float) ($paid['FRETE'] ?? 0) > 0 && (float) ($remaining['FRETE'] ?? 0) > 0) {
                    $adjustments[] = 'FRETE';
                }
                continue;
            }

            if ($component === 'PRODUTOS') {
                $remainingProdutos = $paymentMethod === 'pix'
                    ? (float) ($remaining['PRODUTOS_PIX'] ?? 0)
                    : (float) ($remaining['PRODUTOS_CREDIT_CARD'] ?? 0);
                if ((float) ($paid['PRODUTOS'] ?? 0) > 0 && $remainingProdutos > 0) {
                    $adjustments[] = 'PRODUTOS';
                }
            }
        }

        return array_values(array_unique($adjustments));
    }

    public function productionLayouts(): JsonResponse
    {
        $orderBudgets = $this->repository->getLayoutsForProduction();
        $data = $this->layoutService->transformLayouts($orderBudgets, 'product');

        return response()->json($data);
    }

    public function cancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = $this->repository->find($validated['id']);

        if (!$order) {
            abort(404, 'Pedido não encontrado');
        }

        $orderUpdated = $this->repository->cancel($order);

        return (new OrderResource($orderUpdated))->response();
    }

    public function destroy(Order $order): JsonResponse
    {
        try {
            DB::beginTransaction();

            $order->delete(); // OrderObserver cuida da limpeza de budget_rooms e budgets

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido excluído com sucesso.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir pedido: ' . $e->getMessage(),
            ], 500);
        }
    }
}

