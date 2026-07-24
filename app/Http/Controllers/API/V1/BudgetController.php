<?php

namespace App\Http\Controllers\API\V1;

use App\Actions\Budget\CreateBudgetAction;
use App\Actions\Budget\DuplicateBudgetAction;
use App\Actions\Budget\UpdateBudgetAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\IndexBudgetRequest;
use App\Http\Requests\Api\V1\StoreBudgetRequest;
use App\Http\Requests\Api\V1\UpdateBudgetRequest;
use App\Http\Requests\Api\V1\UpdateBudgetStatusRequest;
use App\Http\Requests\Budget\GetRequestLayoutArtsRequest;
use App\Http\Requests\Budget\RegisterPaymentRequest;
use App\Http\Requests\Budget\UpdateLayoutColumnRequest;
use App\Http\Requests\Budget\UpdateRequestLayoutArtStatusRequest;
use App\Http\Requests\Budget\UploadArtRequest;
use App\Http\Requests\Budget\UploadReferringFileRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Models\LayoutColumnName;
use App\Models\Order;
use App\Models\RequestLayoutArt;
use App\Repositories\BudgetRepository;
use App\Repositories\BudgetWallRepository;
use App\Repositories\DropshippingRepository;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\OrderRepository;
use App\Repositories\RequestLayoutArtRepository;
use App\Services\GeneratePaymentService;
use App\Services\LayoutService;
use App\Services\TinyErpService;
use App\Support\DocumentValidator;
use App\Support\OrderBudgetStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BudgetController extends Controller
{
    public function __construct(
        protected BudgetRepository $repository,
        protected BudgetWallRepository $budgetWallRepository,
        protected GeneratePaymentService $generatePaymentService,
        protected LayoutService $layoutService,
        protected OrderBudgetRepository $orderBudgetRepository,
        protected OrderRepository $orderRepository,
        protected RequestLayoutArtRepository $requestLayoutArtRepository,
        protected DropshippingRepository $dropshippingRepository,
        protected TinyErpService $tinyErpService
    ) {}

    public function index(IndexBudgetRequest $request): JsonResponse
    {
        $budgets = Budget::with(['user', 'tenant', 'primaryRoom'])
            ->forUser($request->user())
            ->search($request->search)
            ->byStatus($request->status)
            ->byDateRange($request->date_from, $request->date_to)
            ->byUserId($request->user_id)
            ->latest()
            ->paginate();

        return BudgetResource::collection($budgets)->response();
    }

    public function show(Budget $budget): JsonResponse
    {
        $budget->load(['rooms.walls.collectionModel.files', 'dropshippingData']);

        return (new BudgetResource($budget))->response();
    }

    public function duplicate(Budget $budget, DuplicateBudgetAction $action): JsonResponse
    {
        $duplicated = $action->execute($budget);

        return (new BudgetResource($duplicated))->response();
    }

    public function store(StoreBudgetRequest $request, CreateBudgetAction $action): JsonResponse
    {
        $budget = $action->execute($request->validated());

        return (new BudgetResource($budget))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function update(Budget $budget, UpdateBudgetRequest $request, UpdateBudgetAction $action): JsonResponse
    {
        if ($budget->isApproved() && $budget->order_id) {
            abort(422, 'Orçamento aprovado com pedido vinculado não pode ser editado.');
        }

        $budget = $action->execute($budget, $request->validated());

        return (new BudgetResource($budget))->response();
    }

    public function updateStatus(UpdateBudgetStatusRequest $request, Budget $budget)
    {
        $translatedStatus = $request->getTranslatedStatus();

        $budget->update([
            'status' => $translatedStatus,
        ]);

        $budget->fresh(['rooms.walls.collectionModel']);

        return new BudgetResource($budget);
    }

    public function destroy(Budget $budget): Response
    {
        $budget->delete();

        return response()->noContent();
    }

    public function createLayoutOrder(Order $order, Budget $budget)
    {
        $budget->loadMissing(['rooms.walls.collectionModel']);

        // Atualizar status do orçamento
        $budget->update(['status' => 'Aprovado']);

        // Buscar coluna padrão e a coluna "Novos Layouts" para roteamento inicial dos cards
        $firstColumn = LayoutColumnName::orderBy('id')->first();
        $newLayoutsColumn = LayoutColumnName::query()
            ->where('name', 'Novos Layouts')
            ->first();

        if (! $firstColumn) {
            throw new \Exception('Nenhuma coluna de layout configurada. Configure pelo menos uma coluna antes de aprovar orçamentos.');
        }

        // Criar um OrderBudget para cada parede do orçamento usando dados do Order
        $orderBudgets = [];
        $tenantId = $order->tenant_id ?? $budget->tenant_id;
        $orderIdx = 1;

        foreach ($budget->rooms as $room) {
            foreach ($room->walls as $wall) {
                $description = $wall->comment_referring_model ?? $order->comment_referring_model ?? null;

                $modelName = mb_strtolower(trim((string) ($wall->collectionModel?->name ?? '')));
                $shouldStartOnNewLayouts = in_array($modelName, [
                    'arte do shutterstock',
                    'coleção arts',
                    'colecao arts',
                ], true);
                $targetColumnId = $shouldStartOnNewLayouts && $newLayoutsColumn
                    ? $newLayoutsColumn->id
                    : $firstColumn->id;

                $orderBudgets[] = \App\Models\OrderBudget::create([
                    'order_id' => $order->id,
                    'tenant_id' => $tenantId,
                    'budget_wall_id' => $wall->id,
                    'status' => OrderBudgetStatus::APPROVE_LAYOUT,
                    'layout_column_names_id' => $targetColumnId,
                    'description' => $description,
                    'order_index' => $orderIdx++,
                ]);
            }
        }

        return $orderBudgets;
    }

    /** @deprecated */
    public function updateLayoutColumn(UpdateLayoutColumnRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $request->user();
        $typePage = $validated['type_page'] ?? 'layout'; // Default para layout
        $columnId = $validated['layout_column_names_id'];

        $orderBudget = $this->orderBudgetRepository->editLayoutColumn(
            $validated['order_budget_id'],
            $columnId,
            $user,
            $typePage
        );

        return response()->json($orderBudget);
    }

    protected function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    public function uploadArt(UploadArtRequest $request): JsonResponse
    {
        $file = $request->file('art_file');

        $data = $this->requestLayoutArtRepository->uploadArt(
            $file,
            $request->order_budget_id,
            $request->dealer_id,
            $request->designer_id,
            $request->order_id,
            $request->comment ?? null,
        );

        return response()->json($data, 201);
    }

    public function uploadReferringFile(UploadReferringFileRequest $request): JsonResponse
    {
        $file = $request->file('file');
        $path = Storage::disk('public')->putFile('budgets/referring-models', $file);

        return response()->json(['path' => $path], 201);
    }

    /**
     * Get request layout arts for a budget and order budget
     *
     * @param  Request  $request
     */
    public function getRequestLayoutArts(GetRequestLayoutArtsRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (! $user) {
            abort(401, 'Usuário não autenticado');
        }

        // Obter valores validados do request
        $validated = $request->validated();
        $orderId = $validated['order_id'] ?? null;
        $budgetId = $validated['budget_id'] ?? null;
        $dealerId = $validated['dealer_id'] ?? null;
        $cardId = $validated['card_id'] ?? null;

        $isAdmin = $user->isAdmin();
        $isDesigner = $user->isDesigner();
        $isReseller = $user->isReseller();

        // Buscar diretamente na tabela request_layouts_art
        $query = RequestLayoutArt::with([
            'designer:id,name',
            'dealer:id,name',
            'orderBudget.wall.room',
        ]);

        if ($cardId) {
            $query->where('order_budget_id', $cardId);
        }

        // Aplicar filtro por order_id ou budget_id
        if ($orderId) {
            $query->where('order_id', $orderId);
        } elseif ($budgetId) {
            // Buscar através do relacionamento order_budget -> wall -> room -> budget_id
            $query->whereHas('orderBudget.wall.room', function ($q) use ($budgetId) {
                $q->where('budget_id', $budgetId);
            });
        }

        // Aplicar filtros por tipo de usuário
        if (! $isAdmin) {
            if ($isDesigner) {
                $query->where('designer_id', $user->id);
            } elseif ($isReseller) {
                $query->where('dealer_id', $user->id);
            } elseif ($dealerId) {
                $query->where('dealer_id', $dealerId);
            }
        } elseif ($dealerId) {
            $query->where('dealer_id', $dealerId);
        }

        // Buscar e formatar os dados
        $arts = $query->orderBy('created_at', 'desc')->get();

        $formattedArts = $arts->map(function ($art) {
            $imageUrl = null;
            if ($art->path_file) {
                // Remover 'storage/' do início se já existir
                $path = ltrim($art->path_file, '/');
                $path = str_replace('storage/', '', $path);
                $imageUrl = asset('storage/' . $path);
            }

            // Obter informações da parede se disponível
            $wallInfo = null;
            if ($art->orderBudget && $art->orderBudget->wall) {
                $wall = $art->orderBudget->wall;
                $room = $wall->room;

                $wallInfo = [
                    'wall_name' => $wall->name ?: 'Parede sem nome',
                    'room_name' => $room ? ($room->name ?: 'Ambiente sem nome') : 'N/A',
                    'width' => $wall->width,
                    'height' => $wall->height,
                    'total_area' => $wall->total_area,
                ];
            }

            return [
                'id' => $art->id,
                'order_id' => $art->order_id,
                'order_budget_id' => $art->order_budget_id,
                'comment' => $art->comment,
                'approval_status' => $art->approval_status ?? 'pending',
                'path_file' => $art->path_file,
                'image_url' => $imageUrl,
                'created_at' => $art->created_at?->toIso8601String(),
                'designer' => $art->designer ? [
                    'id' => $art->designer->id,
                    'name' => $art->designer->name,
                ] : null,
                'dealer' => $art->dealer ? [
                    'id' => $art->dealer->id,
                    'name' => $art->dealer->name,
                ] : null,
                'wall_info' => $wallInfo,
            ];
        });

        return response()->json($formattedArts->values());
    }

    public function updateRequestLayoutArtStatus(UpdateRequestLayoutArtStatusRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $updatedArt = $this->requestLayoutArtRepository->updateApprovalStatus(
            $validated['request_layout_art_id'],
            $validated['approval_status']
        );

        return response()->json([
            'id' => $updatedArt->id,
            'approval_status' => $updatedArt->approval_status,
            'message' => 'Status da iteração atualizado com sucesso.',
        ]);
    }

    public function registerPayment(RegisterPaymentRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (! $user || ! $user->isAdmin()) {
            abort(403, 'Você não tem permissão para registrar pagamentos.');
        }

        $data = $request->validated();
        $order = Order::findOrFail($data['order_id']);
        $file = $request->file('payment_file');

        if ($order->dropshipping_budget) {
            $dropshippingBudget = $this->dropshippingRepository->findDropshippingByOrderId($order->id);
            $accountPayable = $this->tinyErpService->sendAccountPayable($order->toArray(), $dropshippingBudget->toArray());
            $orderTiny = $this->tinyErpService->sendOrder($order->toArray(), $dropshippingBudget->toArray());

            if ($orderTiny['status'] == 'Erro') {
                $errors = $orderTiny['registros']['registro']['erros'] ?? 'Erro desconhecido';
                abort(403, is_string($errors) ? $errors : json_encode($errors));
            }

            $this->orderBudgetRepository->updateTinyErpOrderId($order->id, $orderTiny['registros']['registro']['id']);
        }

        $orderUpdated = $this->repository->registerPayment($order, $file);

        return (new BudgetResource($orderUpdated))->response();
    }
}
