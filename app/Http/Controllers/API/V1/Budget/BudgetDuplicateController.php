<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Budget;

use App\Actions\Budget\DuplicateBudgetAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;

class BudgetDuplicateController extends Controller
{
    public function store(
        Budget $budget,
        DuplicateBudgetAction $action
    ): BudgetResource {

        $duplicated = $action->execute($budget);

        return new BudgetResource($duplicated);
    }
}
