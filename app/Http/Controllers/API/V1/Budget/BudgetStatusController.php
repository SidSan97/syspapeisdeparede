<?php

declare(strict_types=1);

namespace App\Http\Controllers\API\V1\Budget;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateBudgetStatusRequest;
use App\Http\Resources\BudgetResource;
use App\Models\Budget;

class BudgetStatusController extends Controller
{
    public function update(
        UpdateBudgetStatusRequest $request,
        Budget $budget
    ) {
        $translatedStatus = $request->getTranslatedStatus();

        $budget->update([
            'status' => $translatedStatus,
        ]);

        $budget->fresh(['rooms.walls.collectionModel']);

        return new BudgetResource($budget);
    }
}
