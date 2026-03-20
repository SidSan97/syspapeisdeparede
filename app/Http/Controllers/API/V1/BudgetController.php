<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\GeneratePdfRequest;
use App\Http\Requests\Budget\GetRequestLayoutArtsRequest;
use App\Http\Requests\Budget\PlaceOrderRequest;
use App\Http\Requests\Budget\RegisterPaymentRequest;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Http\Requests\Budget\UpdateBudgetRequest;
use App\Http\Requests\Budget\UpdateLayoutColumnRequest;
use App\Http\Requests\Budget\UploadArtRequest;
use App\Http\Requests\Common\ListRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Models\Order;
use App\Models\RequestLayoutArt;
use App\Repositories\BudgetRepository;
use App\Repositories\BudgetWallRepository;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\OrderRepository;
use App\Repositories\RequestLayoutArtRepository;
use App\Repositories\DropshippingRepository;
use App\Services\GeneratePdfService;
use App\Services\GeneratePaymentService;
use App\Services\LayoutService;
use App\Support\DocumentValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\TinyErpService;

class BudgetController extends Controller
{
    protected $repository;
    protected $budgetWallRepository;
    protected $generatePdfService;
    protected $generatePaymentService;
    protected $layoutService;
    protected $orderBudgetRepository;
    protected $orderRepository;
    protected $requestLayoutArtRepository;
    protected $dropshippingRepository;
    protected $tinyErpService;

    public function __construct(
        BudgetRepository $repository,
        BudgetWallRepository $budgetWallRepository,
        GeneratePdfService $generatePdfService,
        GeneratePaymentService $generatePaymentService,
        LayoutService $layoutService,
        OrderBudgetRepository $orderBudgetRepository,
        OrderRepository $orderRepository,
        RequestLayoutArtRepository $requestLayoutArtRepository,
        DropshippingRepository $dropshippingRepository,
        TinyErpService $tinyErpService
    ) {
        $this->repository = $repository;
        $this->budgetWallRepository = $budgetWallRepository;
        $this->generatePdfService = $generatePdfService;
        $this->generatePaymentService = $generatePaymentService;
        $this->layoutService = $layoutService;
        $this->orderBudgetRepository = $orderBudgetRepository;
        $this->orderRepository = $orderRepository;
        $this->requestLayoutArtRepository = $requestLayoutArtRepository;
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

        $paginatedBudgets = $this->repository->paginate($filters);

        return BudgetResource::collection($paginatedBudgets)->response();
    }

    public function show(Budget $budget): JsonResponse
    {
        $budget->load(['rooms.walls.collectionModel.files', 'dropshippingData']);

        return (new BudgetResource($budget))->response();
    }

    public function pendingReview(): JsonResponse
    {
        $budgets = $this->repository->getPendingReview();
        return BudgetResource::collection($budgets)->response();
    }

    public function orders(): JsonResponse
    {
        $budgets = $this->repository->getAll();
        return BudgetResource::collection($budgets)->response();
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $data = $request->validated();

        $budget = DB::transaction(function () use ($data) {
            // Criar o orçamento
            $budget = $this->repository->create($data);

            // Criar dados de dropshipping se fornecidos
            if (!empty($data['dropshipping_data']) && $data['dropshipping_budget'] === 1) {
                if (!DocumentValidator::validateCPFCNPJ($data['dropshipping_data']['cpf_cnpj'])) {
                    abort(422, 'CPF/CNPJ inválido');
                }

                $this->dropshippingRepository->create(
                    $data['dropshipping_data'],
                    $budget->id,
                    null,
                    Auth::id()
                );
            }

            return $budget;
        });

        return (new BudgetResource($budget))->response()->setStatusCode(201);
    }

    public function update(UpdateBudgetRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        $budget = DB::transaction(function () use ($id, $data) {
            $budget = \App\Models\Budget::findOrFail($id);
            $budget = $this->repository->update($budget, $data);

            if (!empty($data['dropshipping_data']) && $data['dropshipping_budget'] === 1) {
                $existingDropshipping = $budget->dropshippingData;

                if ($existingDropshipping) {
                    $this->dropshippingRepository->update(
                        $data['dropshipping_data'],
                        $existingDropshipping->id
                    );
                } else {
                    $this->dropshippingRepository->create(
                        $data['dropshipping_data'],
                        $budget->id,
                        null,
                        Auth::id()
                    );
                }
            } elseif (isset($data['dropshipping_budget']) && $data['dropshipping_budget'] === 0) {
                $budget->dropshippingData()->delete();
            }

            return $budget->fresh(['dropshippingData']);
        });

        return (new BudgetResource($budget))->response();
    }

    public function cancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:budgets,id'],
        ]);

        $budget = Budget::findOrFail($validated['id']);
        $budgetUpdated = $this->repository->cancel($budget);

        return (new BudgetResource($budgetUpdated))->response();
    }

    public function destroy(Budget $budget): JsonResponse
    {
        try {
            DB::beginTransaction();

            $hasOrders = $budget->rooms()
                ->whereNotNull('order_id')
                ->exists();

            if ($hasOrders) {
                $budget->delete();
            } else {
                $budget->delete();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Orçamento excluído com sucesso.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir orçamento: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        $budget = $this->repository->getAllById($data['id']);
        $budgetRoom = $budget->primaryRoom;

        // Buscar walls do budget room
        $walls = $this->budgetWallRepository->getByBudgetRoom($budgetRoom);

        // Atualizar walls com os dados da requisição
        if (isset($data['walls']) && is_array($data['walls'])) {
            $this->budgetWallRepository->updateFromRequestData($walls, $data['walls']);
        }

        // Criar Order a partir do Budget
        $order = $this->orderRepository->createFromBudget($budget, $data);

        if($budget->dropshipping_budget === 1) {
            $this->dropshippingRepository->updateOrderId($budget->id, $order->id);
        }

        // Atualizar order_id em todas as rooms do budget
        $budget->rooms()->update([
            'order_id' => $order->id,
        ]);

        // Criar OrderBudgets usando o Order criado
        $this->createLayoutOrder($order, $budget);

        $budgetResource = new BudgetResource($budget);
        $response = $budgetResource->response();
        $responseData = $response->getData(true);
        $responseData['order_id'] = $order->id;

        if (isset($responseData['data']) && is_array($responseData['data'])) {
            $responseData['data']['order_id'] = $order->id;
        }

        return response()->json($responseData);
    }

    public function createLayoutOrder(Order $order, Budget $budget)
    {
        // Atualizar status do orçamento
        $budget->update(['status' => 'Aprovado']);

        // Buscar a primeira coluna de layout disponível (padrão: Desenhista)
        $firstColumn = \App\Models\LayoutColumnName::orderBy('id')->first();

        if (!$firstColumn) {
           throw new \Exception('Nenhuma coluna de layout configurada. Configure pelo menos uma coluna antes de aprovar orçamentos.');
        }

        // Criar um OrderBudget para cada parede do orçamento usando dados do Order
        $orderBudgets = [];
        $tenantId = $order->tenant_id ?? $budget->tenant_id;
        $orderIdx = 1;

        foreach ($budget->rooms as $room) {
            foreach ($room->walls as $wall) {
                $description = $wall->comment_referring_model ?? $order->comment_referring_model ?? null;

                $orderBudgets[] = \App\Models\OrderBudget::create([
                    'order_id' => $order->id,
                    'tenant_id' => $tenantId,
                    'budget_wall_id' => $wall->id,
                    'status' => 'Aprovar Layout',
                    'layout_column_names_id' => $firstColumn->id,
                    'description' => $description,
                    'order_index' => $orderIdx++,
                ]);
            }
        }

        return $orderBudgets;
    }

    public function generatePdf(GeneratePdfRequest $request): \Symfony\Component\HttpFoundation\Response
    {
        $validated = $request->validated();

        $budget = Budget::with(['rooms.walls.collectionModel', 'dropshippingData'])->findOrFail($validated['id']);

        // Aceitar tanto cash_value quanto total_amount (para compatibilidade)
        $cashValue = $validated['total_amount'] ?? $validated['cash_value'] ?? null;
        $installmentValue = $validated['total_amount_installments'] ?? $validated['installment_value'] ?? null;
        $mockupPercentage = $validated['mockup_percentage'] ?? $validated['percentage'] ?? null;

        $this->repository->updateMarkup($budget, $mockupPercentage);

        return $this->generatePdfService->generateBudgetPdf(
            $budget,
            $mockupPercentage,
            $cashValue,
            $installmentValue
        );
    }

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

    /**
     * Get request layout arts for a budget and order budget
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRequestLayoutArts(GetRequestLayoutArtsRequest $request): JsonResponse
    {
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Usuário não autenticado');
        }

        // Obter valores validados do request
        $validated = $request->validated();
        $orderId = $validated['order_id'] ?? null;
        $budgetId = $validated['budget_id'] ?? null;
        $dealerId = $validated['dealer_id'] ?? null;

        $isAdmin = $user->isAdmin();
        $isDesigner = $user->isDesigner();
        $isReseller = $user->isReseller();

        // Buscar diretamente na tabela request_layouts_art
        $query = RequestLayoutArt::with([
            'designer:id,name',
            'dealer:id,name',
            'orderBudget.wall.room',
        ]);

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
        if (!$isAdmin) {
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
                'wall_info' => $wallInfo
            ];
        });

        return response()->json($formattedArts->values());
    }

    public function registerPayment(RegisterPaymentRequest $request): JsonResponse
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        if (!$user || !$user->isAdmin()) {
            abort(403, 'Você não tem permissão para registrar pagamentos.');
        }

        $data = $request->validated();
        $order = Order::findOrFail($data['order_id']);
        $file = $request->file('payment_file');

        if($order->dropshipping_budget) {
            $dropshippingBudget = $this->dropshippingRepository->findDropshippingByOrderId($order->id);
            $accountPayable = $this->tinyErpService->sendAccountPayable($order->toArray(), $dropshippingBudget->toArray());
            $orderTiny = $this->tinyErpService->sendOrder($order->toArray(), $dropshippingBudget->toArray());

            if($orderTiny['status'] == "Erro") {
                $errors = $orderTiny['registros']['registro']['erros'] ?? 'Erro desconhecido';
                abort(403, is_string($errors) ? $errors : json_encode($errors));
            }

            $this->orderBudgetRepository->updateTinyErpOrderId($order->id, $orderTiny['registros']['registro']['id']);
        }

        $orderUpdated = $this->repository->registerPayment($order, $file);

        return (new BudgetResource($orderUpdated))->response();
    }
}
