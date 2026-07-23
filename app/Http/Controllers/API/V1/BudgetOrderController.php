<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\PlaceBudgetOrderRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Repositories\BudgetRepository;
use Illuminate\Http\JsonResponse;

class BudgetOrderController extends Controller
{
    public function __construct(
        protected BudgetRepository $repository,
    ) {}

    public function store(Budget $budget, PlaceBudgetOrderRequest $request): JsonResponse
    {
        $budget->load(['rooms.walls.collectionModel', 'primaryRoom.walls.collectionModel']);

        $budget = $this->repository->placeOrder($budget, $request->validated());

        return (new BudgetResource($budget))->response();
    }
}
