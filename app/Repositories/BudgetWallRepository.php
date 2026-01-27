<?php

namespace App\Repositories;

use App\Models\BudgetRoom;
use App\Models\BudgetWall;
use Illuminate\Database\Eloquent\Collection;

class BudgetWallRepository
{
    /**
     * Buscar todas as walls de um budget room ordenadas por posição
     */
    public function getByBudgetRoom(BudgetRoom $budgetRoom): Collection
    {
        return BudgetWall::with('collectionModel')
            ->where('budget_room_id', $budgetRoom->id)
            ->orderBy('position')
            ->get();
    }

    public function updateFromRequestData(Collection $walls, array $wallsData): void
    {
        if (empty($wallsData)) {
            return;
        }

        foreach ($walls as $wall) {
            // Buscar os dados da wall pelo ID (chave na requisição)
            $wallId = (int) $wall->id; // Converter para string para garantir match

            // Tentar buscar pelo ID como string ou inteiro
            $wallData = $wallsData[$wall->id] ?? $wallsData[$wallId] ?? null;

            if ($wallData && is_array($wallData)) {
                $updateData = [];

                // Campos permitidos para atualização
                $allowedFields = [
                    'comment_referring_model',
                    'link_referring_model',
                    'files_referring_model',
                    'collection_referring_model',
                ];

                foreach ($allowedFields as $field) {
                    if (isset($wallData[$field])) {
                        $value = $wallData[$field];
                        $updateData[$field] = ($value !== null && $value !== '') ? $value : null;
                    }
                }

                // Atualizar a wall se houver dados para atualizar
                if (!empty($updateData)) {
                    $wall->update($updateData);
                }
            }
        }
    }
}
