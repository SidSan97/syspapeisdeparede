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

    public function addMember(int $orderBudgetId, int $userId)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);
        $user = \App\Models\User::findOrFail($userId);

        // Verificar se o relacionamento já existe
        if ($orderBudget->users()->where('users.id', $user->id)->exists()) {
            throw new \Exception('Usuário já está adicionado a este card');
        }

        // Adicionar o usuário ao card
        $orderBudget->users()->attach($user->id);

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }
}
