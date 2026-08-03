<?php

namespace App\Http\Controllers\API\V1;

use App\Actions\Order\MergeOrderAction;
use App\Actions\Order\UpdateOrderAction;
use App\Filters\DateFromFilter;
use App\Filters\DateToFilter;
use App\Filters\OrderSearchFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexOrderLayoutRequest;
use App\Http\Requests\Api\V1\IndexOrderRequest;
use App\Http\Requests\Api\V1\UpdateOrderRequest;
use App\Http\Requests\Orders\GenerateOrderPaymentLinkRequest;
use App\Http\Requests\Orders\MergeOrdersRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderBudget;
use App\Models\OrderPaymentLink;
use App\Repositories\DropshippingRepository;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\OrderRepository;
use App\Services\GeneratePaymentService;
use App\Services\LayoutService;
use App\Services\OrderBoletoWalletPaymentService;
use App\Services\OrderPaymentCompositionService;
use App\Services\TinyErpService;
use App\Support\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class OrderController extends Controller
{
    public function __construct(
        protected OrderRepository $repository,
        protected LayoutService $layoutService,
        protected GeneratePaymentService $generatePaymentService,
        protected OrderPaymentCompositionService $paymentCompositionService,
        protected OrderBudgetRepository $orderBudgetRepository,
        protected OrderBudget $orderBudget,
        protected DropshippingRepository $dropshippingRepository,
        protected TinyErpService $tinyErpService
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(IndexOrderRequest $request): JsonResponse
    {
        $orders = QueryBuilder::for(Order::class)
            ->allowedFilters(
                'status',
                'user_id',

                AllowedFilter::custom(
                    'search',
                    new OrderSearchFilter
                ),

                AllowedFilter::custom(
                    'created_from',
                    new DateFromFilter
                ),

                AllowedFilter::custom(
                    'created_to',
                    new DateToFilter
                ),
            )
            ->select(['id', 'created_at', 'name', 'total_amount', 'status'])
            // ->with(['user', 'tenant', 'primaryRoom', 'paymentLinks'])
            ->forUser($request->user())
            ->latest()
            ->paginate();

        return OrderResource::collection($orders)->response();
    }

    public function show(Order $order): JsonResponse
    {
        return (new OrderResource($order))->response();
    }

    public function update(
        Order $order,
        UpdateOrderRequest $request,
        UpdateOrderAction $action
    ): JsonResponse {
        $order = $action->execute($order, $request->validated());

        return (new OrderResource($order->fresh()))->response();
    }

    public function destroy(Order $order): Response
    {
        $this->authorize('delete', $order);

        DB::transaction(fn () => $order->delete());

        return response()->noContent();
    }

    public function layouts(IndexOrderLayoutRequest $request, ?int $orderId = null)
    {
        $filters = $request->validated();

        $orderBudgets = $this->repository->getLayoutsForApprove($orderId, $filters);
        $data = $this->layoutService->transformLayouts($orderBudgets, 'layout', $filters);

        // TODO: Migrar para resource collection.
        // return OrderLayoutCardResource::collection($orderBudgets);
        return response()->json($data);
    }

    public function getByStatus(Request $request, string $status): JsonResponse
    {
        $orders = $this->repository->getByStatus($status);

        return OrderResource::collection($orders)->response();
    }

    public function generatePaymentLink(
        GenerateOrderPaymentLinkRequest $request,
        Order $order,
        OrderBoletoWalletPaymentService $boletoWalletPayment
    ): JsonResponse {
        $validated = $request->validated();

        $paymentMethod = $validated['payment_method'];

        if ($paymentMethod === 'boleto') {
            $boletoWalletPayment->payFromWallet(
                $order,
                $validated['components'],
                $request->user()
            );
        } else {
            $installments = $paymentMethod === 'credit_card'
                ? (int) ($validated['installments'] ?? ($order->installments ?? 1))
                : null;

            $this->createPaymentLinkForOrder($order, $validated['components'], $paymentMethod, $installments);
        }

        return (new OrderResource($order->refresh()))->response();
    }

    public function generateLegacyPaymentLink(Order $order): JsonResponse
    {
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
            'item_name' => 'Pedido #'.$order->id.' - '.implode(' + ', $calculation['components']),
            'item_description' => 'Componentes: '.implode(', ', $calculation['components']),
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

        if (! ($paymentLinkData['success'] ?? false)) {
            abort(500, 'Erro ao gerar link de pagamento: '.($paymentLinkData['message'] ?? 'Erro desconhecido'));
        }

        $apiResponse = $paymentLinkData['data'] ?? [];
        $paymentUrl = $apiResponse['url'] ?? null;
        $expirationDate = $apiResponse['expiration_date'] ?? $apiResponse['expires_at'] ?? null;
        $pagarmeOrderId = $apiResponse['id'] ?? null;
        $paymentLinkExternalId = $apiResponse['payment_link']['id'] ?? ($apiResponse['id'] ?? null);

        if (! $paymentUrl) {
            abort(500, 'URL de pagamento não encontrada na resposta');
        }

        $storedPaymentMethod = $paymentMethod === 'credit_card' ? 'installment' : $paymentMethod;

        $order->update([
            'link_payment' => $paymentUrl,
            'payment_expiration_date' => $expirationDate,
            'payment_status' => $order->payment_status ?: 'unpaid',
            'payment_method' => $storedPaymentMethod,
            'installments' => $installments,
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

    public function productionLayouts(IndexOrderLayoutRequest $request, ?int $orderId = null): JsonResponse
    {
        $filters = $request->validated();

        $orderBudgets = $this->repository->getLayoutsForProduction($orderId, $filters);

        $data = $this->layoutService->transformLayouts($orderBudgets, 'product', $filters);

        return response()->json($data);
    }

    public function merge(MergeOrdersRequest $request, MergeOrderAction $action): JsonResponse
    {
        $data = $request->validated();

        $order = $action->execute($data['order_ids'], $data['name']);

        return (new OrderResource($order))->response()->setStatusCode(201);
    }

    public function cancel(Order $order): JsonResponse
    {
        $order->update([
            'status' => OrderStatus::CANCELED,
        ]);

        return (new OrderResource($order))->response();
    }
}
