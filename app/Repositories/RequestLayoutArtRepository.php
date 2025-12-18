<?php

namespace App\Repositories;

use App\Models\OrderBudget;
use App\Models\RequestLayoutArt;
use App\Models\RequestLayoutArtInteraction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class RequestLayoutArtRepository
{
    public function uploadArt(
        ?UploadedFile $file,
        int $orderBudgetId,
        int $dealerId,
        int $designerId,
        int $orderId,
        string $comment = null
    ): array {
        return DB::transaction(function () use ($file, $orderBudgetId, $dealerId, $designerId, $orderId, $comment) {
            $interaction = RequestLayoutArtInteraction::firstOrCreate(
                ['card_id' => $orderBudgetId],
                ['card_id' => $orderBudgetId]
            );

            $path = '';
            if ($file) {
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('request_layouts_art', $filename, 'public');
            }

            $requestLayoutArt = RequestLayoutArt::create([
                'dealer_id' => $dealerId,
                'designer_id' => $designerId,
                'path_file' => $path,
                'order_id' => $orderId,
                'order_budget_id' => $orderBudgetId,
                'interactions_card_id' => $interaction->id,
                'comment' => $comment,
            ]);

            $orderBudget = OrderBudget::findOrFail($orderBudgetId);

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
                'url' => $path ? asset('storage/' . $path) : null,
                'interaction_id' => $interaction->id,
            ];
        });
    }
}

