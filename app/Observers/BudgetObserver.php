<?php

namespace App\Observers;

use App\Models\Budget;
use App\Models\BudgetRoom;

class BudgetObserver
{
    /**
     * Antes de excluir o orçamento: remove budget_rooms que pertencem somente a ele
     * (order_id é null ou 0). Os demais terão budget_id anulado pelo nullOnDelete da FK.
     */
    public function deleting(Budget $budget): void
    {
        BudgetRoom::query()
            ->where('budget_id', $budget->id)
            ->where(function ($query) {
                $query->whereNull('order_id')->orWhere('order_id', 0);
            })
            ->delete();
    }
}
