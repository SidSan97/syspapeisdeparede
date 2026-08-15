<?php

namespace App\Repositories;

use App\Models\OrderBudget;
use App\Models\RequestLayoutArt;
use App\Models\RequestLayoutArtInteraction;
use App\Support\OrderBudgetStatus;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestLayoutArtRepository
{
    public function show(int $id)
    {
        return RequestLayoutArt::findOrFail($id);
    }

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
                'status' => OrderBudgetStatus::PENDING_REVIEW,
            ]);

            // Atualizar status do orçamento
            if ($orderBudget->budget) {
                $orderBudget->budget->update([
                    'status' => OrderBudgetStatus::PENDING_REVIEW,
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

            $path = trim((string) ($requestLayoutArt->path_file ?? ''));
            if ($path === '') {
                throw ValidationException::withMessages([
                    'approval_status' => 'A arte aprovada precisa possuir arquivo válido.',
                ]);
            }
        }

        DB::transaction(function () use ($requestLayoutArt, $approvalStatus) {
            $requestLayoutArt->update([
                'approval_status' => $approvalStatus,
            ]);

            if ($approvalStatus !== 'approved') {
                return;
            }

            $orderBudget = OrderBudget::with('wall')->find($requestLayoutArt->order_budget_id);
            if (!$orderBudget || !$orderBudget->wall) {
                return;
            }

            // A parede passa a usar a arte aprovada no request_layouts_art
            $orderBudget->wall->update([
                'files_referring_model' => [$requestLayoutArt->path_file],
            ]);

            //Atualiza o status do card (order_budget) para layout aprovado
            $orderBudget->update([
                'status' => OrderBudgetStatus::LAYOUT_APPROVED,
            ]);
        });

        return $requestLayoutArt->fresh();
    }
}

