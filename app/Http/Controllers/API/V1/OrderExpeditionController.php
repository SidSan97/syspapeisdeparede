<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\OrderRepository;

class OrderExpeditionController extends Controller
{
    protected $orderBudgetRepository;
    protected $repository;

    public function __construct(OrderBudgetRepository $orderBudgetRepository, OrderRepository $orderRepository)
    {
        $this->middleware('auth:api');
        $this->orderBudgetRepository = $orderBudgetRepository;
        $this->repository = $orderRepository;
    }

    public function expedition(Request $request): JsonResponse
    {
        try {
            $perPage = (int) config('pagination.per_page', 15);
            $search = $request->input('search');
            $page = $request->input('page', 1);

            $paginatedBudgets = $this->orderBudgetRepository->paginateReadyForPicking($perPage, $search);

            return response()->json([
                'success' => true,
                'data' => [
                    'data' => $paginatedBudgets->items(),
                    'current_page' => $paginatedBudgets->currentPage(),
                    'last_page' => $paginatedBudgets->lastPage(),
                    'per_page' => $paginatedBudgets->perPage(),
                    'total' => $paginatedBudgets->total(),
                    'from' => $paginatedBudgets->firstItem(),
                    'to' => $paginatedBudgets->lastItem(),
                ],
                'message' => 'Lista de separações recuperada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar separações',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function readyForInvoice(): JsonResponse
    {
        try {
            $orders = $this->repository->getReadyForInvoice();

            return response()->json([
                'success' => true,
                'data' => $orders,
                'message' => 'Lista de pedidos prontos para faturar recuperada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar pedidos prontos para faturar',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
