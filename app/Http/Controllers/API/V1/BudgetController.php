<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\PlaceOrderRequest;
use App\Http\Requests\Budget\RegisterPaymentRequest;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Http\Requests\Budget\UploadArtRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

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

    public function __construct(
        BudgetRepository $repository,
        GeneratePdfService $generatePdfService,
        GeneratePaymentService $generatePaymentService,
        LayoutService $layoutService,
        OrderBudgetRepository $orderBudgetRepository,
        OrderRepository $orderRepository,
        RequestLayoutArtRepository $requestLayoutArtRepository,
        DropshippingRepository $dropshippingRepository
    ) {
        $this->repository = $repository;
        $this->generatePdfService = $generatePdfService;
        $this->generatePaymentService = $generatePaymentService;
        $this->layoutService = $layoutService;
        $this->orderBudgetRepository = $orderBudgetRepository;
        $this->orderRepository = $orderRepository;
        $this->requestLayoutArtRepository = $requestLayoutArtRepository;
        $this->dropshippingRepository = $dropshippingRepository;
    }

    public function index(): JsonResponse
    {
        try {
            $budgets = $this->repository->all();
            $data = BudgetResource::collection($budgets)->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lista de orçamentos',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar orçamentos',
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

                // Gerenciar dados de dropshipping
                if (!empty($data['dropshipping_data']) && $data['dropshipping_budget'] === 1) {
                    // Verificar se já existe dropshipping_data para este budget
                    $existingDropshipping = $budget->dropshippingData;

                    if ($existingDropshipping) {
                        // Atualizar dados existentes
                        $this->dropshippingRepository->update(
                            $data['dropshipping_data'],
                            $existingDropshipping->id
                        );
                    } else {
                        // Criar novos dados
                        $this->dropshippingRepository->create(
                            $data['dropshipping_data'],
                            $budget->id,
                            Auth::id()
                        );
                    }
                } elseif (isset($data['dropshipping_budget']) && $data['dropshipping_budget'] === 0) {
                    // Se dropshipping foi desabilitado, remover dados existentes
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
            ]);
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
            $budget = Budget::with(['rooms.walls.collectionModel'])->findOrFail($data['id']);

            // Atualizar o Budget com os dados do pedido
            $budgetUpdated = $this->repository->placeOrder($budget, $data);

            // Criar Order a partir do Budget
            $order = $this->orderRepository->createFromBudget($budgetUpdated, $data);

            // Criar OrderBudgets usando o Order criado
            $this->createLayoutOrder($order, $budgetUpdated);

            $transformed = (new BudgetResource($budgetUpdated))->toArray(request());

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

        foreach ($budget->rooms as $room) {
            foreach ($room->walls as $wall) {
                $orderBudgets[] = \App\Models\OrderBudget::create([
                    'budget_id' => $budget->id,
                    'tenant_id' => $tenantId,
                    'budget_wall_id' => $wall->id,
                    'status' => 'Aprovar Layout',
                    'layout_column_names_id' => $firstColumn->id,
                    'description' => $order->comment_referring_model ?? null,
                ]);
            }
        }

        return $orderBudgets;
    }

    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:budgets,id'],
        ]);

        try {
            $budget = Budget::with(['rooms.walls'])->findOrFail($validated['id']);
            $budgetPaymentData = BudgetResource::getBudgetPaymentData($budget->toArray());

            // Atualizar status do orçamento para 'Aprovado'
            $budget->update(['status' => 'Aprovado']);

            // Buscar a primeira coluna de layout disponível (padrão: Desenhista)
            $firstColumn = \App\Models\LayoutColumnName::orderBy('id')->first();

            if (!$firstColumn) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nenhuma coluna de layout configurada. Configure pelo menos uma coluna antes de aprovar orçamentos.',
                ], 400);
            }

            // Atualizar status dos OrderBudget existentes para 'Liberado para produção'
            \App\Models\OrderBudget::where('budget_id', $budget->id)
                ->update(['status' => 'Liberado para produção']);

            // Buscar os OrderBudget atualizados para retornar na resposta
            $orderBudgets = \App\Models\OrderBudget::where('budget_id', $budget->id)->get();

            // Gerar link de pagamento
            $paymentLinkResponse = $this->generatePaymentService->generateLinkPayment($budgetPaymentData);
            $paymentLinkData = json_decode($paymentLinkResponse->getContent(), true);

            // Extrair URL do link de pagamento
            $paymentUrl = null;
            if ($paymentLinkData['success'] ?? false) {
                // A API do Pagar.me retorna a URL em diferentes estruturas possíveis
                $apiResponse = $paymentLinkData['data'] ?? [];
                $paymentUrl = $apiResponse['url'] ?? $apiResponse['checkout_url'] ?? $apiResponse['public_url'] ?? null;
            }

            $transformed = (new BudgetResource($budget->refresh()))->toArray(request());

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

    public function generatePdf(Request $request)
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:budgets,id'],
            'percentage' => ['nullable', 'numeric', 'min:0'],
            'cash_value' => ['nullable', 'numeric', 'min:0'],
            'installment_value' => ['nullable', 'numeric', 'min:0'],
        ]);

        try {
            $budget = Budget::with(['rooms.walls.collectionModel'])->findOrFail($validated['id']);

            return $this->generatePdfService->generateBudgetPdf(
                $budget,
                $validated['percentage'] ?? null,
                $validated['cash_value'] ?? null,
                $validated['installment_value'] ?? null
            );
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF do orçamento',
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

    public function markAsProduced(Request $request, int $orderBudgetId): JsonResponse
    {
        try {
            $user = $request->user();
            $orderBudget = $this->orderBudgetRepository->markAsProduced(
                $orderBudgetId,
                $user,
                'product'
            );

            return response()->json([
                'success' => true,
                'data' => $orderBudget,
                'message' => 'Data de produção atualizada com sucesso',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar data de produção: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateProductionPercentage(Request $request, int $orderBudgetId): JsonResponse
    {
        try {
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

            return response()->json([
                'success' => true,
                'data' => $orderBudget,
                'message' => 'Porcentagem de produção atualizada com sucesso',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Pedido não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar porcentagem de produção: ' . $e->getMessage(),
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
                $request->budget_id,
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
    public function getRequestLayoutArts(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'budget_id' => ['required', 'integer', 'exists:budgets,id'],
                'dealer_id' => ['required', 'integer', 'exists:users,id'],
                'order_budget_id' => ['nullable', 'integer', 'exists:order_budgets,id'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Dados inválidos',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $budgetId = $request->input('budget_id');
            $orderBudgetId = $request->input('order_budget_id'); // Opcional
            $dealerId = $request->input('dealer_id');

            // Buscar request_layouts_art - se order_budget_id for fornecido, filtrar por ele também
            $query = RequestLayoutArt::where('budget_id', $budgetId)
                ->where('dealer_id', $dealerId);

            if ($orderBudgetId) {
                $query->where('order_budget_id', $orderBudgetId);
            }

            $requestLayoutArts = $query->with(['designer:id,name', 'dealer:id,name', 'orderBudget.wall.room'])
                ->orderBy('created_at', 'desc')
                ->get();

            // Formatar dados com informações da parede
            $formattedArts = $requestLayoutArts->map(function ($art) {
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

                $imageUrl = null;
                if ($art->path_file) {
                    $imageUrl = Storage::disk('public')->exists($art->path_file)
                        ? asset('storage/' . $art->path_file)
                        : null;
                }

                return [
                    'id' => $art->id,
                    'budget_id' => $art->budget_id,
                    'order_budget_id' => $art->order_budget_id,
                    'dealer_id' => $art->dealer_id,
                    'designer_id' => $art->designer_id,
                    'comment' => $art->comment,
                    'path_file' => $art->path_file,
                    'image_url' => $imageUrl,
                    'created_at' => $art->created_at?->toIso8601String(),
                    'designer' => $art->designer ? [
                        'id' => $art->designer->id,
                        'name' => $art->designer->name,
                    ] : null,
                    'designer_name' => $art->designer?->name,
                    'dealer' => $art->dealer ? [
                        'id' => $art->dealer->id,
                        'name' => $art->dealer->name,
                    ] : null,
                    'dealer_name' => $art->dealer?->name,
                    'wall_info' => $wallInfo,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $formattedArts,
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
            // Validar se o usuário tem permissão de admin
            $user = Auth::user();
            if (!$user || !$user->isAdmin()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Você não tem permissão para registrar pagamentos.',
                ], 403);
            }

            $data = $request->validated();
            $budget = Budget::findOrFail($data['budget_id']);
            $file = $request->file('payment_file');

            $budgetUpdated = $this->repository->registerPayment($budget, $file);
            $transformed = (new BudgetResource($budgetUpdated))->toArray(request());

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'message' => 'Pagamento registrado com sucesso. O pedido foi aprovado.',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Orçamento não encontrado',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar pagamento: ' . $e->getMessage(),
            ], 500);
        }
    }
}
