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

    public function editLayoutColumn(int $orderBudgetId, int $columnId, $user = null, ?string $typePage = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);

        $updateData = [];
        if ($typePage === 'product') {
            $updateData['production_column_names_id'] = $columnId;
        } else {
            $updateData['layout_column_names_id'] = $columnId;
        }

        $orderBudget->update($updateData);

        // Registrar no histórico se houver usuário
        if ($user) {
            $this->historyService->logColumnChange($orderBudgetId, $user, $columnId, $typePage);
        }

        return $orderBudget->fresh();
    }

    public function updateOrderBudgetDescription(int $orderBudgetId, string $description, $user = null, ?string $typePage = null)
    {
        $this->orderBudget::findOrFail($orderBudgetId)->update([
            'description' => $description ?? null,
        ]);

        if ($user) {
            $this->historyService->logDescriptionChange($orderBudgetId, $user, $typePage);
        }

        return $this->orderBudget->fresh();
    }

    public function addMember(int $orderBudgetId, int $userId, $actionUser = null, ?string $typePage = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);
        $user = \App\Models\User::findOrFail($userId);

        if ($orderBudget->users()->where('users.id', $user->id)->exists()) {
            throw new \Exception('Usuário já está adicionado a este card');
        }

        $orderBudget->users()->attach($user->id);

        if ($actionUser) {
            if ($actionUser->id === $user->id) {
                $this->historyService->logMemberJoin($orderBudgetId, $actionUser, $user, $typePage);
            } else {
                $this->historyService->logMemberJoin($orderBudgetId, $actionUser, $user, $typePage);
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    public function removeMember(int $orderBudgetId, int $userId, $actionUser = null, ?string $typePage = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);
        $user = \App\Models\User::findOrFail($userId);

        if (!$orderBudget->users()->where('users.id', $user->id)->exists()) {
            throw new \Exception('Usuário não está adicionado a este card');
        }

        $orderBudget->users()->detach($user->id);

        if ($actionUser) {
            if ($actionUser->id === $user->id) {
                $this->historyService->logMemberLeave($orderBudgetId, $user, $typePage);
            } else {
                $this->historyService->logMemberRemoval($orderBudgetId, $actionUser, $user, $typePage);
            }
        }

        return [
            'id' => $user->id,
            'name' => $user->name,
        ];
    }

    public function markAsProduced(int $orderBudgetId, $user = null, ?string $typePage = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);

        $orderBudget->update([
            'production_date' => now()->toDateString(),
            'production_column_names_id' => 2,
            'production_percentage' => 100,
        ]);

        if ($user) {
            $this->historyService->logProductionDateUpdate($orderBudgetId, $user, $typePage);
        }

        return $orderBudget->fresh();
    }

    public function updateProductionPercentage(int $orderBudgetId, float $percentage, $user = null, ?string $typePage = null)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);

        // Validar porcentagem (0 a 100)
        $percentage = max(0, min(100, $percentage));

        $orderBudget->update([
            'production_percentage' => $percentage,
        ]);

        if ($user) {
            $this->historyService->logProductionPercentageUpdate($orderBudgetId, $user, $percentage, $typePage);
        }

        return $orderBudget->fresh();
    }

    public function updateTinyErpOrderExpeditionId(int $orderBudgetId, int $tinyErpOrderExpeditionId)
    {
        $orderBudget = $this->orderBudget::findOrFail($orderBudgetId);
        $orderBudget->update([
            'tinyErp_order_expedition_id' => $tinyErpOrderExpeditionId,
        ]);

        return $orderBudget->fresh();
    }

    public function getReadyForExpedition()
    {
        return $this->orderBudget::where('production_percentage', 100)
            ->with('order')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($orderBudget) {
                return [
                    'id' => $orderBudget->id,
                    'order_id' => $orderBudget->order_id,
                    'description' => $orderBudget->description,
                    'production_percentage' => $orderBudget->production_percentage,
                    'production_date' => $orderBudget->production_date,
                    'created_at' => $orderBudget->created_at,
                    'tinyErp_order_id' => $orderBudget->tinyErp_order_id,
                    'tinyErp_order_expedition_id' => $orderBudget->tinyErp_expedition_id,
                    'order' => $orderBudget->order ? [
                        'id' => $orderBudget->order->id,
                        'name' => $orderBudget->order->name,
                        'total_amount' => $orderBudget->order->total_amount,
                        'status' => $orderBudget->order->status,
                        'created_at' => $orderBudget->order->created_at,
                    ] : null,
                ];
            });
    }

    public function updateTinyErpOrderId(int $orderId, string $tinyErpOrderId)
    {
        $this->orderBudget::where('order_id', $orderId)->update([
            'tinyErp_order_id' => $tinyErpOrderId,
        ]);

        return $this->orderBudget->fresh();
    }
}
