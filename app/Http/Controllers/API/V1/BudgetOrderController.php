<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;
use App\Repositories\BudgetRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BudgetOrderController extends Controller
{
    public function __construct(
        protected BudgetRepository $repository,
    ) {}

    public function store(Budget $budget, Request $request): JsonResponse
    {
        $budget->load(['rooms.walls.collectionModel', 'primaryRoom.walls.collectionModel']);

        $budget = $this->repository->placeOrder($budget, $request->all());

        return (new BudgetResource($budget))->response();
    }
}
