<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\InvoiceOrderBudgetCardsRequest;
use App\Repositories\OrderBudgetRepository;
use App\Repositories\OrderRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderExpeditionController extends Controller
{
    protected $orderBudgetRepository;
    protected $repository;

    public function __construct(OrderBudgetRepository $orderBudgetRepository, OrderRepository $orderRepository)
    {
        $this->middleware('auth:sanctum');
        $this->orderBudgetRepository = $orderBudgetRepository;
        $this->repository = $orderRepository;
    }

    public function expedition(Request $request): JsonResponse
    {
        $search = $request->input('search');
        $inSeparation = $request->input('stage') === 'in_separation';

        $paginatedBudgets = $this->orderBudgetRepository->paginateReadyForPicking($search, $inSeparation);

        return response()->json($paginatedBudgets);
    }

    public function invoiceOrderCards(InvoiceOrderBudgetCardsRequest $request): JsonResponse
    {
        $updated = $this->orderBudgetRepository->updateReadyToExpedition(
            $request->validated('order_budget_ids')
        );

        return response()->json([
            'updated' => $updated,
            'message' => 'Cards enviados para faturamento.',
        ]);
    }

    public function readyForInvoice(): JsonResponse
    {
        $orders = $this->repository->getReadyForInvoice();

        return response()->json($orders);
    }
}
