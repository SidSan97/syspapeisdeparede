<?php

namespace App\Repositories;
use App\Models\OrderBudget;
use App\Services\LayoutCardHistoryService;

class OrderBudgetRepository {

    protected $orderBudget;
    protected $historyService;

    public function __construct(OrderBudget $orderBudget, LayoutCardHistoryService $historyService)
    {
        $this->orderBudget = $orderBudget;
        $this->historyService = $historyService;
    }

    public function editLayoutColumn(int $orderBudgetId, int $layoutColumnNameId, $user = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);
        $orderBudget->update([
            'layout_column_names_id' => $layoutColumnNameId,
        ]);

        // Registrar no histórico se houver usuário
        if ($user) {
            $newColumn = \App\Models\LayoutColumnName::findOrFail($layoutColumnNameId);
            $this->historyService->logColumnChange($orderBudgetId, $user, $newColumn);
        }

        return $orderBudget->fresh();
    }

    public function updateOrderBudgetDescription(int $orderBudgetId, string $description, $user = null)
    {
        $this->orderBudget::findOrFail($orderBudgetId)->update([
            'description' => $description ?? null,
        ]);

        // Registrar no histórico se houver usuário
        if ($user) {
            $this->historyService->logDescriptionChange($orderBudgetId, $user);
        }

        return $this->orderBudget->fresh();
    }

    public function addMember(int $orderBudgetId, int $userId, $actionUser = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);
        $user = \App\Models\User::findOrFail($userId);

        // Verificar se o relacionamento já existe
        if ($orderBudget->users()->where('users.id', $user->id)->exists()) {
            throw new \Exception('Usuário já está adicionado a este card');
        }

        // Adicionar o usuário ao card
        $orderBudget->users()->attach($user->id);

        // Registrar no histórico
        if ($actionUser) {
            // Se o usuário que está adicionando é o mesmo que está sendo adicionado, é um "ingresso"
            if ($actionUser->id === $user->id) {
                $this->historyService->logMemberJoin($orderBudgetId, $actionUser, $user);
            } else {
                // Caso contrário, é uma adição de membro
                $this->historyService->logMemberJoin($orderBudgetId, $actionUser, $user);
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    public function removeMember(int $orderBudgetId, int $userId, $actionUser = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);
        $user = \App\Models\User::findOrFail($userId);

        // Verificar se o relacionamento existe
        if (!$orderBudget->users()->where('users.id', $user->id)->exists()) {
            throw new \Exception('Usuário não está adicionado a este card');
        }

        // Remover o usuário do card
        $orderBudget->users()->detach($user->id);

        // Registrar no histórico
        if ($actionUser) {
            // Se o usuário que está removendo é o mesmo que está sendo removido, é uma "saída"
            if ($actionUser->id === $user->id) {
                $this->historyService->logMemberLeave($orderBudgetId, $user);
            } else {
                // Caso contrário, é uma remoção de membro
                $this->historyService->logMemberRemoval($orderBudgetId, $actionUser, $user);
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }
}
