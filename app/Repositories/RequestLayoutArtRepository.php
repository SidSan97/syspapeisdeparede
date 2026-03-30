<?php

namespace App\Repositories;

use App\Models\OrderBudget;
use App\Models\RequestLayoutArt;
use App\Models\RequestLayoutArtInteraction;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
                'approval_status' => 'pending',
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

    public function updateApprovalStatus(int $requestLayoutArtId, string $approvalStatus): RequestLayoutArt
    {
        $requestLayoutArt = RequestLayoutArt::findOrFail($requestLayoutArtId);

        if ($approvalStatus === 'approved') {
            $approvedArtExistsForWall = RequestLayoutArt::where('order_budget_id', $requestLayoutArt->order_budget_id)
                ->where('id', '!=', $requestLayoutArt->id)
                ->where('approval_status', 'approved')
                ->exists();

            if ($approvedArtExistsForWall) {
                throw ValidationException::withMessages([
                    'approval_status' => 'Já existe uma arte aprovada para esta parede.',
                ]);
            }
        }

        $requestLayoutArt->update([
            'approval_status' => $approvalStatus,
        ]);

        return $requestLayoutArt->fresh();
    }
}

