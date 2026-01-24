<?php

namespace App\Http\Controllers\API\V1;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\Order;
use App\Models\OrderBudget;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\DropshippingRepository;
use App\Services\TinyErpService;
use App\Http\Resources\OrderResource;
use App\Http\Controllers\Controller;

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
    )
    {
        $this->middleware('auth:api');
        $this->orderBudgetRepository = $orderBudgetRepository;
        $this->orderBudget = $orderBudget;
        $this->dropshippingRepository = $dropshippingRepository;
        $this->tinyErpService = $tinyErpService;
    }

    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:orders,id'],
        ]);

        $order = Order::with(['rooms.walls'])->findOrFail($validated['id']);

        if ($order->dropshipping_budget) {
            $dropshippingBudget = $this->dropshippingRepository->findDropshippingByOrderId($order->id);
            $this->tinyErpService->sendAccountPayable($order->toArray(), $dropshippingBudget->toArray());
            $orderTiny = $this->tinyErpService->sendOrder($order->toArray(), $dropshippingBudget->toArray());

            if ($orderTiny['status'] === 'Erro') {
                abort(403, 'Houve um erro ao cadastrar o produto no ERP. Tente novamente mais tarde!');
            }

            $this->orderBudgetRepository->updateTinyErpOrderId($order->id, $orderTiny['registros']['registro']['id']);
        }

        $order->update(['status' => 'Aprovado']);

        // Buscar a primeira coluna de layout disponível (padrão: Desenhista)
        $firstColumn = \App\Models\LayoutColumnName::orderBy('id')->first();

        if (!$firstColumn) {
            abort(400, 'Nenhuma coluna de layout configurada. Configure pelo menos uma coluna antes de aprovar orçamentos.');
        }

        $this->orderBudget->where('order_id', $order->id)
            ->update(['status' => 'Liberado para produção']);

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

        // Gerar relatório de produção
        $productionReportService = app(\App\Services\ProductionReportService::class);
        $productionReportService->generateMarkAsProducedReport($orderBudget, $user);

        return response()->json($orderBudget);
    }

    public function updateProductionPercentage(Request $request, int $orderBudgetId): JsonResponse
    {
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

        if ($validated['production_percentage'] == 100) {
            // Gerar relatório de produção quando atinge 100%
            $productionReportService = app(\App\Services\ProductionReportService::class);
            $productionReportService->generateProductionPercentageReport(
                $orderBudget,
                $user,
                $validated['production_percentage']
            );
        }

        return response()->json($orderBudget);
    }
}
