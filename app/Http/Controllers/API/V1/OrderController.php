<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Common\ListRequest;
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

        $perPage = (int) config('pagination.per_page', 15);
        $paginatedOrders = $this->repository->paginate($filters, $perPage);

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

    public function generatePaymentLink(Request $request, int $id): JsonResponse
    {
        $order = Order::findOrFail($id);

        // Gerar link de pagamento
        $paymentLinkResponse = $this->generatePaymentService->generateLinkPayment($order->toArray());
        $paymentLinkData = json_decode($paymentLinkResponse->getContent(), true);

        if (!($paymentLinkData['success'] ?? false)) {
            abort(500, 'Erro ao gerar link de pagamento: ' . ($paymentLinkData['message'] ?? 'Erro desconhecido'));
        }

        // Extrair URL e data de expiração
        $apiResponse = $paymentLinkData['data'] ?? [];
        $paymentUrl = $apiResponse['url'] ?? null;
        $expirationDate = $apiResponse['expiration_date'] ?? $apiResponse['expires_at'] ?? null;

        if (!$paymentUrl) {
            abort(500, 'URL de pagamento não encontrada na resposta');
        }

        // Salvar URL e data de expiração no pedido
        $order->update([
            'link_payment' => $paymentUrl,
            'payment_expiration_date' => $expirationDate,
        ]);

        return (new OrderResource($order->refresh()))->response();
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
}

