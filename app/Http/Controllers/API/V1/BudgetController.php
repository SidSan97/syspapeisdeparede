<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Budget\PlaceOrderRequest;
use App\Http\Requests\Budget\StoreBudgetRequest;
use App\Models\Budget;
use App\Repositories\BudgetRepository;
use App\Services\GeneratePdfService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class BudgetController extends Controller
{
    protected $repository;
    protected $generatePdfService;

    public function __construct(BudgetRepository $repository, GeneratePdfService $generatePdfService)
    {
        $this->repository = $repository;
        $this->generatePdfService = $generatePdfService;
    }

    public function index(): JsonResponse
    {
        $budgets = $this->repository->all();

        try {
            return response()->json([
                'success' => true,
                'data' => $budgets,
                'message' => 'Lista de orçamentos',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar orçamentos',
            ], 500);
        }
    }

    public function store(StoreBudgetRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $budget = $this->repository->create($data);

            return response()->json([
                'success' => true,
                'data' => $budget,
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

            return response()->json([
                'success' => true,
                'data' => $budgetUpdated,
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
            $budget = Budget::with(['rooms.walls'])->findOrFail($data['id']);
            $budgetUpdated = $this->repository->placeOrder($budget, $data);

            return response()->json([
                'success' => true,
                'data' => $budgetUpdated,
                'message' => 'Pedido registrado com sucesso',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao registrar pedido',
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
            $budget = Budget::with(['rooms.walls'])->findOrFail($validated['id']);

            return $this->generatePdfService->generateBudgetPdf($budget, $validated['percentage'] ?? null);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar PDF do orçamento',
            ], 500);
        }
    }

    protected function formatMoney(float $value): string
    {
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
}
