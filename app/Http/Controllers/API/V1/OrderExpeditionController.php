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
        $perPage = (int) config('pagination.per_page', 15);
        $search = $request->input('search');

        $paginatedBudgets = $this->orderBudgetRepository->paginateReadyForPicking($perPage, $search);

        // Retorna a estrutura padrão de paginação do Laravel
        return response()->json($paginatedBudgets);
    }

    public function readyForInvoice(): JsonResponse
    {
        $orders = $this->repository->getReadyForInvoice();

        // Retorna diretamente a coleção de pedidos prontos para faturar
        return response()->json($orders);
    }
}
