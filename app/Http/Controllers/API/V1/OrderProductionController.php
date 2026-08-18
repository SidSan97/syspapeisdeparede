<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\LayoutColumnName;
use App\Models\Order;
use App\Models\OrderBudget;
use App\Repositories\DropshippingRepository;
use App\Repositories\OrderBudgetRepository;
use App\Services\ProductionReportService;
use App\Services\TinyErpService;
use App\Support\OrderBudgetStatus;
use App\Support\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderProductionController extends Controller
{
    protected $orderBudgetRepository;

    protected $orderBudget;

    protected $dropshippingRepository;

    protected $tinyErpService;

    public function __construct(
        OrderBudgetRepository $orderBudgetRepository,
        DropshippingRepository $dropshippingRepository,
        TinyErpService $tinyErpService,
        OrderBudget $orderBudget
    ) {
        $this->middleware('auth:sanctum');
        $this->orderBudgetRepository = $orderBudgetRepository;
        $this->orderBudget = $orderBudget;
        $this->dropshippingRepository = $dropshippingRepository;
        $this->tinyErpService = $tinyErpService;
    }

    public function approve(Order $order): JsonResponse
    {
        $order->load(['rooms.walls']);

        if ($order->dropshipping_budget) {
            $dropshippingBudget = $this->dropshippingRepository->findDropshippingByOrderId($order->id);
            $this->tinyErpService->sendAccountPayable($order->toArray(), $dropshippingBudget->toArray());
            $orderTiny = $this->tinyErpService->sendOrder($order->toArray(), $dropshippingBudget->toArray());

            if ($orderTiny['status'] === 'Erro') {
                abort(403, 'Houve um erro ao cadastrar o produto no ERP. Tente novamente mais tarde!');
            }

            $this->orderBudgetRepository->updateTinyErpOrderId($order->id, $orderTiny['registros']['registro']['id']);
        }

        $textFlag = $order->paid ? 'Pagamento recebido' : 'Aguardando pagamento';
        $order->update(['status' => OrderStatus::APPROVED, 'flags' => $textFlag]);

        // Buscar a primeira coluna de layout disponível (padrão: Desenhista)
        $firstColumn = LayoutColumnName::orderBy('id')->first();

        if (! $firstColumn) {
            abort(400, 'Nenhuma coluna de layout configurada. Configure pelo menos uma coluna antes de aprovar orçamentos.');
        }

        /*$this->orderBudget->where('order_id', $order->id)
            ->update(['status' => OrderBudgetStatus::RELEASED_FOR_PRODUCTION]);*/

        return (new OrderResource($order->refresh()))->response();
    }

    public function markAsProduced(Request $request, int $orderBudgetId): JsonResponse
    {
        $user = $request->user();
        $orderBudget = $this->orderBudgetRepository->markAsProduced(
            $orderBudgetId,
            $user,
            'product'
        );

        $order = Order::findOrFail($orderBudget->order_id);
        $order->update(['flags' => 'Produção concluída', 'status' => 'Enviado']);

        // Gerar relatório de produção
        $productionReportService = app(ProductionReportService::class);
        $productionReportService->generateMarkAsProducedReport($orderBudget, $user);

        return response()->json($orderBudget);
    }

    public function updateProductionPercentage(Request $request, OrderBudget $orderBudget): JsonResponse
    {
        $validated = $request->validate([
            'production_percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $user = $request->user();
        $updatedOrderBudget = $this->orderBudgetRepository->updateProductionPercentage(
            $orderBudget->id,
            $validated['production_percentage'],
            $user,
            'product'
        );

        $order = $orderBudget->order()->firstOrFail();

        if ($validated['production_percentage'] == 100) {
            $productionReportService = app(ProductionReportService::class);
            $productionReportService->generateProductionPercentageReport(
                $updatedOrderBudget,
                $user,
                $validated['production_percentage']
            );
            $order->update(['flags' => 'Produção concluída', 'status' => OrderStatus::SENT]);
        } else {
            $order->update(['flags' => 'Produção em andamento', 'status' => OrderStatus::IN_PRODUCTION]);
        }

        return response()->json($updatedOrderBudget);
    }
}
