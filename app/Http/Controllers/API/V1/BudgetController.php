<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\GetRequestLayoutArtsRequest;
use App\Http\Requests\Budget\PlaceOrderRequest;
use App\Http\Requests\Budget\RegisterPaymentRequest;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Http\Requests\Budget\UploadArtRequest;
use App\Http\Requests\Common\ListRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Models\BudgetRoom;
use App\Models\Order;
use App\Models\OrderBudget;
use App\Models\RequestLayoutArt;
use App\Repositories\BudgetRepository;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use App\Services\TinyErpService;

class BudgetController extends Controller
{
    protected $repository;
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
        try {
            $validated = $request->validated();

            $filters = [
                'search' => $validated['search'] ?? null,
                'status' => $validated['status'] ?? 'all',
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
                'user_id' => $validated['user_id'] ?? null,
            ];

            $perPage = (int) config('pagination.per_page', 15);
            $paginatedBudgets = $this->repository->paginate($filters, $perPage);

            $data = BudgetResource::collection($paginatedBudgets->items())->toArray(request());

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $data,
                    'current_page' => $paginatedBudgets->currentPage(),
                    'last_page' => $paginatedBudgets->lastPage(),
                    'per_page' => $paginatedBudgets->perPage(),
                    'total' => $paginatedBudgets->total(),
                    'from' => $paginatedBudgets->firstItem(),
                    'to' => $paginatedBudgets->lastItem(),
                ],
                'message' => 'Lista de orçamentos',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar orçamentos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $budget = Budget::with(['rooms.walls.collectionModel.files', 'dropshippingData'])
                ->findOrFail($id);

            $data = (new BudgetResource($budget))->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Orçamento encontrado',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Orçamento não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar orçamento',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function pendingReview(): JsonResponse
    {
        try {
            $budgets = $this->repository->getPendingReview();
            $data = BudgetResource::collection($budgets)->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lista de pedidos pendentes de revisão',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos pendentes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function orders(): JsonResponse
    {
        try {
            $budgets = $this->repository->getAll();
            $data = BudgetResource::collection($budgets)->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lista de pedidos',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $budget = DB::transaction(function () use ($data) {
                // Criar o orçamento
                $budget = $this->repository->create($data);

                // Criar dados de dropshipping se fornecidos
                if (!empty($data['dropshipping_data']) && $data['dropshipping_budget'] === 1) {
                    if (!DocumentValidator::validateCPFCNPJ($data['dropshipping_data']['cpf_cnpj'])) {
                        return response()->json([
                            'success' => false,
                            'message' => 'CPF/CNPJ inválido',
                        ], 422);
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

            $transformed = (new BudgetResource($budget))->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'message' => 'Orçamento criado com sucesso',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar orçamento: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(StoreBudgetRequest $request, int $id): JsonResponse
    {
        $data = $request->validated();

        try {
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

            $transformed = (new BudgetResource($budget))->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'message' => 'Orçamento atualizado com sucesso',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Orçamento não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar orçamento: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function cancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:budgets,id'],
        ]);

        try {
            $budget = Budget::findOrFail($validated['id']);
            $budgetUpdated = $this->repository->cancel($budget);
            $transformed = (new BudgetResource($budgetUpdated))->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'message' => 'Orçamento cancelado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao cancelar orçamento',
            ], 500);
        }
    }

    public function placeOrder(PlaceOrderRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $budget = Budget::findOrFail($data['id']);

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

            $transformed = (new BudgetResource($budget))->toArray(request());
            $transformed['order_id'] = $order->id;

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'message' => 'Pedido registrado com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar pedido',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createLayoutOrder(Order $order, Budget $budget)
    {
        // Atualizar status do orçamento
        $budget->update(['status' => 'Aprovar Layout']);

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
                $orderBudgets[] = \App\Models\OrderBudget::create([
                    'order_id' => $order->id,
                    'tenant_id' => $tenantId,
                    'budget_wall_id' => $wall->id,
                    'status' => 'Aprovar Layout',
                    'layout_column_names_id' => $firstColumn->id,
                    'description' => $order->comment_referring_model ?? null,
                    'order_index' => $orderIdx++,
                ]);
            }
        }

        return $orderBudgets;
    }

    public function generatePdf(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:budgets,id'],
            'percentage' => ['nullable', 'numeric', 'min:0'],
            'mockup_percentage' => ['nullable', 'numeric', 'min:0'],
            'cash_value' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'installment_value' => ['nullable', 'numeric', 'min:0'],
            'total_amount_installments' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $budget = Budget::with(['rooms.walls.collectionModel', 'dropshippingData'])->findOrFail($validated['id']);

            // Aceitar tanto cash_value quanto total_amount (para compatibilidade)
            $cashValue = $validated['total_amount'] ?? $validated['cash_value'] ?? null;
            $installmentValue = $validated['total_amount_installments'] ?? $validated['installment_value'] ?? null;
            $mockupPercentage = $validated['mockup_percentage'] ?? $validated['percentage'] ?? null;

            return $this->generatePdfService->generateBudgetPdf(
                $budget,
                $mockupPercentage,
                $cashValue,
                $installmentValue
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF do orçamento',
            ], 500);
        }
    }

    public function updateLayoutColumn(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_budget_id' => ['required', 'integer', 'exists:order_budgets,id'],
                'layout_column_names_id' => ['required', 'integer'],
                'type_page' => ['nullable', 'string', 'in:layout,product'],
            ]);

            $user = $request->user();
            $typePage = $validated['type_page'] ?? 'layout'; // Default para layout
            $columnId = $validated['layout_column_names_id'];

            // Validar se a coluna existe na tabela correta baseado no type_page
            if ($typePage === 'product') {
                $request->validate([
                    'layout_column_names_id' => ['exists:production_column_names,id'],
                ]);
            } else {
                $request->validate([
                    'layout_column_names_id' => ['exists:layout_column_names,id'],
                ]);
            }

            $orderBudget = $this->orderBudgetRepository->editLayoutColumn(
                $validated['order_budget_id'],
                $columnId,
                $user,
                $typePage
            );

            return response()->json([
                'success' => true,
                'data' => $orderBudget,
                'message' => 'Coluna do layout atualizada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar coluna do layout: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateOrderBudgetDescription(Request $request, int $orderBudgetId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'description' => ['nullable', 'string', 'max:500'],
                'type_page' => ['nullable', 'string', 'in:layout,product'],
            ]);

            $user = $request->user();
            $typePage = $validated['type_page'] ?? null;
            $orderBudget = $this->orderBudgetRepository->updateOrderBudgetDescription(
                $orderBudgetId,
                $validated['description'],
                $user,
                $typePage
            );

            return response()->json([
                'success' => true,
                'data' => $orderBudget,
                'message' => 'Descrição atualizada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar descrição: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function addComment(Request $request, int $orderBudgetId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'comment' => ['required', 'string', 'max:500'],
            ]);

            $orderBudget = \App\Models\OrderBudget::findOrFail($orderBudgetId);
            $user = $request->user();

            $comment = $orderBudget->commentAsUser($user, $validated['comment']);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'user_name' => $user->name,
                    'user_id' => $user->id,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ],
                'message' => 'Comentário adicionado com sucesso',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar comentário: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateComment(Request $request, int $orderBudgetId, int $commentId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'comment' => ['required', 'string', 'max:500'],
            ]);

            $orderBudget = \App\Models\OrderBudget::findOrFail($orderBudgetId);
            $comment = \BeyondCode\Comments\Comment::findOrFail($commentId);

            // Verificar se o comentário pertence ao order_budget
            if ($comment->commentable_id !== $orderBudget->id || $comment->commentable_type !== \App\Models\OrderBudget::class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comentário não encontrado',
                ], 404);
            }

            $user = $request->user();
            $comment->update([
                'comment' => $validated['comment'],
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'user_name' => $user->name,
                    'user_id' => $user->id,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ],
                'message' => 'Comentário atualizado com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar comentário: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteComment(Request $request, int $orderBudgetId, int $commentId): JsonResponse
    {
        try {
            $orderBudget = \App\Models\OrderBudget::findOrFail($orderBudgetId);
            $comment = \BeyondCode\Comments\Comment::findOrFail($commentId);

            // Verificar se o comentário pertence ao order_budget
            if ($comment->commentable_id !== $orderBudget->id || $comment->commentable_type !== \App\Models\OrderBudget::class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Comentário não encontrado',
                ], 404);
            }

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comentário excluído com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir comentário: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function addMember(Request $request, int $orderBudgetId): JsonResponse
    {
        try {
            $validated = $request->validate([
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'type_page' => ['nullable', 'string', 'in:layout,product'],
            ]);

            $user = $request->user();
            $typePage = $validated['type_page'] ?? null;
            $data = $this->orderBudgetRepository->addMember($orderBudgetId, $validated['user_id'], $user, $typePage);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Membro adicionado com sucesso',
            ], 201);
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'já está adicionado') ? 400 : 500;

            return response()->json([
                'success' => false,
                'message' => 'Erro ao adicionar membro: ' . $e->getMessage(),
            ], $statusCode);
        }
    }

    public function removeMember(Request $request, int $orderBudgetId, ?int $memberId = null): JsonResponse
    {
        try {
            $input = [
                'user_id' => $memberId ?? $request->input('user_id'),
                'type_page' => $request->input('type_page'),
            ];

            $validated = Validator::make($input, [
                'user_id' => ['required', 'integer', 'exists:users,id'],
                'type_page' => ['nullable', 'string', 'in:layout,product'],
            ])->validate();

            $user = $request->user();
            $typePage = $validated['type_page'] ?? null;
            $data = $this->orderBudgetRepository->removeMember($orderBudgetId, $validated['user_id'], $user, $typePage);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Membro removido com sucesso',
            ], 200);
        } catch (\Exception $e) {
            $statusCode = str_contains($e->getMessage(), 'não está adicionado') ? 400 : 500;

            return response()->json([
                'success' => false,
                'message' => 'Erro ao remover membro: ' . $e->getMessage(),
            ], $statusCode);
        }
    }

    protected function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }



    public function uploadArt(UploadArtRequest $request): JsonResponse
    {
        try {
            $file = $request->file('art_file');

            $data = $this->requestLayoutArtRepository->uploadArt(
                $file,
                $request->order_budget_id,
                $request->dealer_id,
                $request->designer_id,
                $request->order_id,
                $request->comment ?? null,
            );

            return response()->json([
                'success' => true,
                'message' => 'Arte carregada com sucesso',
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao carregar arte: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get request layout arts for a budget and order budget
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getRequestLayoutArts(GetRequestLayoutArtsRequest $request): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Usuário não autenticado',
                ], 401);
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

            return response()->json([
                'success' => true,
                'data' => $formattedArts->values(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao buscar solicitações de artes: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function registerPayment(RegisterPaymentRequest $request): JsonResponse
    {
        try {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            if (!$user || !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para registrar pagamentos.',
                ], 403);
            }

            $data = $request->validated();
            $order = Order::findOrFail($data['order_id']);
            $file = $request->file('payment_file');

            if($order->dropshipping_budget) {
                $dropshippingBudget = $this->dropshippingRepository->findDropshippingByOrderId($order->id);
                $accountPayable = $this->tinyErpService->sendAccountPayable($order->toArray(), $dropshippingBudget->toArray());
                $orderTiny = $this->tinyErpService->sendOrder($order->toArray(), $dropshippingBudget->toArray());

                if($orderTiny['status'] == "Erro") {
                    return response()->json([
                        'success' => false,
                        'message' => 'Houve um erro ao cadastrar o produto no ERP. Tente novamente mais tarde!',
                        'error' => $orderTiny['registros']['registro']['erros']
                    ], 403);
                }

                $this->orderBudgetRepository->updateTinyErpOrderId($order->id, $orderTiny['registros']['registro']['id']);
            }

            $orderUpdated = $this->repository->registerPayment($order, $file);
            $transformed = (new BudgetResource($orderUpdated))->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'message' => 'Pagamento registrado com sucesso. O pedido foi aprovado.',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar pagamento: ' . $e->getMessage(),
            ], 500);
        }
    }
}
