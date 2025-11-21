<?php

namespace App\Repositories;

use App\Models\OrderBudget;
use App\Models\RequestLayoutArt;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RequestLayoutArtRepository
{
    /**
     * Upload art file and create request layout art record.
     *
     * @param UploadedFile $file
     * @param int $orderBudgetId
     * @param int $dealerId
     * @param int $designerId
     * @param int $budgetId
     * @return array
     */
    public function uploadArt(
        UploadedFile $file,
        int $orderBudgetId,
        int $dealerId,
        int $designerId,
        int $budgetId
    ): array {
        return DB::transaction(function () use ($file, $orderBudgetId, $dealerId, $designerId, $budgetId) {
            // Upload do arquivo
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('request_layouts_art', $filename, 'public');

            // Salvar na tabela request_layouts_art
            $requestLayoutArt = RequestLayoutArt::create([
                'dealer_id' => $dealerId,
                'designer_id' => $designerId,
                'path_file' => $path,
                'budget_id' => $budgetId,
            ]);

            // Buscar o pedido
            $orderBudget = OrderBudget::findOrFail($orderBudgetId);

            // Atualizar status do pedido
            $orderBudget->update([
                'status' => 'Pendente de Revisão',
            ]);

            // Atualizar status do orçamento
            if ($orderBudget->budget) {
                $orderBudget->budget->update([
                    'status' => 'Pendente de Revisão',
                ]);
            }

            return [
                'id' => $requestLayoutArt->id,
                'path_file' => $path,
                'url' => asset('storage/' . $path),
            ];
        });
    }
}

