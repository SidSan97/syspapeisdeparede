<?php

namespace App\Observers;

use App\Models\Budget;
use App\Models\BudgetRoom;
use App\Models\Order;

class OrderObserver
{
    /**
     * Antes de excluir o pedido: remove budget_rooms órfãos (só do pedido) e
     * desvincula order_id nos demais (budgets e budget_rooms).
     */
    public function deleting(Order $order): void
    {
        $orderId = $order->id;

        // Remove quartos que pertencem somente ao pedido (sem budget)
        BudgetRoom::query()
            ->where('order_id', $orderId)
            ->where(function ($query) {
                $query->whereNull('budget_id')->orWhere('budget_id', 0);
            })
            ->delete();

        // Desvincula order_id nos budget_rooms restantes
        BudgetRoom::query()
            ->where('order_id', $orderId)
            ->update(['order_id' => null]);

        // Desvincula order_id nos budgets
        Budget::query()
            ->where('order_id', $orderId)
            ->update(['order_id' => null]);
    }
}
