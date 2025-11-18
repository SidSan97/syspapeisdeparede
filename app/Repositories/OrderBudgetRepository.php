<?php

namespace App\Repositories;
use App\Models\OrderBudget;

class OrderBudgetRepository {

    protected $orderBudget;

    public function __construct(OrderBudget $orderBudget)
    {
        $this->orderBudget = $orderBudget;
    }

    public function editLayoutColumn(int $orderBudgetId, int $layoutColumnNameId)
    {
        $this->orderBudget::findOrFail($orderBudgetId)->update([
            'layout_column_names_id' => $layoutColumnNameId,
        ]);

        return $this->orderBudget->fresh();
    }

    public function updateOrderBudgetDescription(int $orderBudgetId, string $description)
    {
        $this->orderBudget::findOrFail($orderBudgetId)->update([
            'description' => $description ?? null,
        ]);

        return $this->orderBudget->fresh();
    }
}
