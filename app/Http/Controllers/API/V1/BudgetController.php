<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\PlaceOrderRequest;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Models\Budget;
use App\Repositories\BudgetRepository;
use App\Services\GeneratePdfService;
use App\Services\GeneratePaymentService;
use App\Services\LayoutService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Barryvdh\DomPDF\Facade\Pdf;

class BudgetController extends Controller
{
    protected $repository;
    protected $generatePdfService;
    protected $generatePaymentService;
    protected $layoutService;

    public function __construct(
        BudgetRepository $repository,
        GeneratePdfService $generatePdfService,
        GeneratePaymentService $generatePaymentService,
        LayoutService $layoutService
    ) {
        $this->repository = $repository;
        $this->generatePdfService = $generatePdfService;
        $this->generatePaymentService = $generatePaymentService;
        $this->layoutService = $layoutService;
    }

    public function index(): JsonResponse
    {
        try {
            $budgets = $this->repository->all();
            $data = $this->transformBudgetCollection($budgets);

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
            $data = $this->transformBudgetCollection($budgets);

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
            $budgets = $this->repository->getPendingReviewAndApproved();
            $data = $this->transformBudgetCollection($budgets);

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
            $budget = $this->repository->create($data);
            $transformed = $this->transformBudget($budget);

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'message' => 'Orçamento criado com sucesso',
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar orçamento',
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
            $transformed = $this->transformBudget($budgetUpdated);

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
            $budgetUpdated = $this->repository->placeOrder($budget, $data);
            $transformed = $this->transformBudget($budgetUpdated);

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'message' => 'Pedido registrado com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar pedido',
            ], 500);
        }
    }

    public function approve(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'exists:budgets,id'],
        ]);

        try {
            $budget = Budget::findOrFail($validated['id']);

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

            // Criar registro em order_budgets
            $orderBudget = \App\Models\OrderBudget::create([
                'budget_id' => $budget->id,
                'status' => 'Liberado para produção',
                'layout_column_names_id' => $firstColumn->id,
            ]);

            // Gerar link de pagamento
            $paymentLinkResponse = $this->generatePaymentService->generateLinkPayment();
            $paymentLinkData = json_decode($paymentLinkResponse->getContent(), true);

            $transformed = $this->transformBudget($budget->refresh());

            return response()->json([
                'success' => true,
                'data' => $transformed,
                'order_budget' => $orderBudget,
                'payment_link' => $paymentLinkData,
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
        ]);

        try {
            $budget = Budget::with(['rooms.walls.collectionModel'])->findOrFail($validated['id']);

            return $this->generatePdfService->generateBudgetPdf($budget, $validated['percentage'] ?? null);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF do orçamento',
            ], 500);
        }
    }

    public function layouts(): JsonResponse
    {
        try {
            $orderBudgets = $this->repository->getLayoutsForProduction();
            $data = $this->layoutService->transformLayouts($orderBudgets);

            return response()->json([
                'success' => true,
                'data' => $data,
                'message' => 'Lista de layouts',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar layouts: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function updateLayoutColumn(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'order_budget_id' => ['required', 'integer', 'exists:order_budgets,id'],
                'layout_column_names_id' => ['required', 'integer', 'exists:layout_column_names,id'],
            ]);

            $orderBudget = \App\Models\OrderBudget::findOrFail($validated['order_budget_id']);
            $orderBudget->update([
                'layout_column_names_id' => $validated['layout_column_names_id'],
            ]);

            return response()->json([
                'success' => true,
                'data' => $orderBudget->fresh(),
                'message' => 'Coluna do layout atualizada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar coluna do layout: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }

    protected function makePublicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $rawUrl = \Illuminate\Support\Facades\Storage::url($path);

        $appUrl = config('app.url') ?: url('/');
        $appUrl = rtrim($appUrl, '/');

        $parsedPath = parse_url($rawUrl, PHP_URL_PATH) ?: $rawUrl;
        $parsedQuery = parse_url($rawUrl, PHP_URL_QUERY);

        $finalUrl = $appUrl . $parsedPath;

        if ($parsedQuery) {
            $finalUrl .= '?' . $parsedQuery;
        }

        return $finalUrl;
    }

    /**
     * @param \Illuminate\Support\Collection<int, Budget> $budgets
     */
    protected function transformBudgetCollection(Collection $budgets): array
    {
        return $budgets->map(function (Budget $budget) {
            return $this->transformBudget($budget);
        })->all();
    }

    protected function transformBudget(Budget $budget): array
    {
        $budget->loadMissing(['rooms.walls.collectionModel.files']);

        $data = $budget->toArray();

        if (!empty($data['rooms']) && is_array($data['rooms'])) {
            foreach ($data['rooms'] as &$room) {
                if (!empty($room['walls']) && is_array($room['walls'])) {
                    foreach ($room['walls'] as &$wall) {
                        $wall['collection_model_name'] = $wall['collection_model']['name'] ?? null;

                        // Transformar arquivos do modelo de coleção
                        if (!empty($wall['collection_model']['files']) && is_array($wall['collection_model']['files'])) {
                            $wall['collection_model']['files'] = array_map(function ($file) {
                                return [
                                    'id' => $file['id'] ?? null,
                                    'name' => $file['file_name'] ?? null,
                                    'file_name' => $file['file_name'] ?? null,
                                    'file_path' => $file['file_path'] ?? null,
                                    'url' => $this->makePublicUrl($file['file_path'] ?? null),
                                ];
                            }, $wall['collection_model']['files']);
                        }
                    }
                    unset($wall);
                }
            }
            unset($room);
        }

        return $data;
    }
}
