<?php

namespace App\Repositories;

use App\Models\Budget;
use App\Support\Budget\BudgetCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Models\OrderBudget;

class BudgetRepository {

    public function all()
    {
        return Budget::with('rooms.walls.collectionModel')->get();
    }

    public function getPendingReview()
    {
        return Budget::with('rooms.walls.collectionModel')
            ->where('status', 'Pendente de Revisão')
            ->get();
    }

    public function getPendingReviewAndApproved()
    {
        return Budget::with('rooms.walls.collectionModel')
            ->whereIn('status', ['Pendente de Revisão', 'Aprovado'])
            ->get();
    }

    public function create(array $data)
    {
        $rooms = $data['rooms'];

        $selectedCarrier = $data['selectedCarrier'] ?? null;

        $totalArea = BudgetCalculator::calculateTotalArea($rooms);
        $totalAmount = BudgetCalculator::calculateTotalAmount(
            $totalArea,
            $rooms,
            $selectedCarrier,
            $data['paymentMethod'] ?? null
        );
        $deliveryTime = BudgetCalculator::calculateDeliveryTime($rooms, $selectedCarrier);

        /** @var \App\Models\Budget $budget */
        $budget = DB::transaction(function () use (
            $data,
            $rooms,
            $selectedCarrier,
            $totalArea,
            $totalAmount,
            $deliveryTime
        ) {
            $budget = Budget::create([
                'user_id' => Auth::id(),
                'name' => $data['name'],
                'total_area' => $totalArea,
                'total_amount' => $totalAmount,
                'delivery_time' => $deliveryTime,
                'payment_method' => $data['paymentMethod'] ?? null,
                'installment_limit' => ($data['paymentMethod'] ?? null) === 'installment'
                    ? ($data['installmentLimit'] ?? null)
                    : null,
                'installments' => ($data['paymentMethod'] ?? null) === 'installment'
                    ? (int) ($data['installments'] ?? 1)
                    : null,
                'cep' => $data['cep'] ?? null,
                'selected_carrier_name' => $selectedCarrier['name'] ?? null,
                'selected_carrier_price' => $selectedCarrier['price'] ?? null,
                'selected_carrier_delivery_time' => $selectedCarrier['deliveryTime'] ?? null,
                'carriers_snapshot' => null,
                'comment_referring_model' => $data['commentReferringModel'] ?? null,
                'link_referring_model' => $data['linkReferringModel'] ?? null,
                'files_referring_model' => isset($data['filesReferringModel'])
                    ? (array) $data['filesReferringModel']
                    : null,
                'collection_referring_model' => $this->formatCollectionReferringModel(
                    $data['collectionReferringModel'] ?? null
                ),
                'raw_payload' => $data,
                'status' => null,
            ]);

            foreach ($rooms as $roomIndex => $roomData) {
                $room = $budget->rooms()->create([
                    'name' => $roomData['name'] ?? null,
                    'position' => $roomIndex,
                    'raw_payload' => $roomData,
                ]);

                foreach ($roomData['walls'] as $wallIndex => $wallData) {
                    $collectionModelId = $wallData['model'] ?? null;
                    $totalAreaWall = BudgetCalculator::calculateWallArea($wallData);
                    $stripCount = BudgetCalculator::calculateStripCount($wallData);
                    $stripHeight = BudgetCalculator::calculateStripHeight($wallData);

                    $room->walls()->create([
                        'name' => $wallData['name'] ?? null,
                        'position' => $wallIndex,
                        'width' => $wallData['width'] ?? null,
                        'height' => $wallData['height'] ?? null,
                        'continue_same_art' => (bool) ($wallData['continueSameArt'] ?? false),
                        'continuations' => $wallData['continuations'] ?? [],
                        'collection_model_id' => $collectionModelId,
                        'total_area' => $totalAreaWall,
                        'strip_height' => $stripHeight,
                        'strip_count' => $stripCount,
                    ]);
                }
            }

            $primaryRoomId = $budget->rooms()->orderBy('position')->value('id');

            if ($primaryRoomId) {
                $budget->update(['primary_budget_room_id' => $primaryRoomId]);
            }

            return $budget->load(['rooms.walls.collectionModel']);
        });

        return $budget;
    }

    public function cancel(Budget $budget): Budget
    {
        $budget->update([
            'status' => 'cancelado',
        ]);

        return $budget->fresh(['rooms.walls.collectionModel']);
    }

    public function placeOrder(Budget $budget, array $data): Budget
    {
        $budget->loadMissing(['rooms.walls.collectionModel']);

        $existingFiles = is_array($budget->files_referring_model)
            ? $budget->files_referring_model
            : [];

        $uploadedFiles = [];

        if (!empty($data['files_referring_model'])) {
            foreach ($data['files_referring_model'] as $file) {
                if ($file instanceof UploadedFile) {
                    $uploadedFiles[] = Storage::disk('public')->putFile('budgets/referring-models', $file);
                }
            }
        }

        $mergedFiles = array_values(array_filter(array_unique(array_merge($existingFiles, $uploadedFiles))));

        $updatePayload = [
            'status' => 'Pendente de Revisão',
        ];

        if (array_key_exists('comment_referring_model', $data)) {
            $comment = $data['comment_referring_model'];
            $updatePayload['comment_referring_model'] = $comment !== null && $comment !== '' ? $comment : null;
        }

        if (array_key_exists('link_referring_model', $data)) {
            $link = $data['link_referring_model'];
            $updatePayload['link_referring_model'] = $link !== null && $link !== '' ? $link : null;
        }

        if (!empty($mergedFiles)) {
            $updatePayload['files_referring_model'] = $mergedFiles;
        } elseif (array_key_exists('files_referring_model', $data)) {
            $updatePayload['files_referring_model'] = null;
        }

        if (array_key_exists('collection_referring_model', $data)) {
            $collection = $data['collection_referring_model'];
            $updatePayload['collection_referring_model'] = $collection !== null && $collection !== '' ? $collection : null;
        }

        $budget->update($updatePayload);

        return $budget->fresh(['rooms.walls.collectionModel']);
    }

    public function getLayoutsForProduction()
    {
        return OrderBudget::where('status', 'Liberado para produção')
            ->with([
                'budget' => function ($query) {
                    $query->with([
                        'rooms.walls.collectionModel.files',
                        'user'
                    ]);
                }
            ])
            ->get();
    }

    protected function formatCollectionReferringModel($value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (is_array($value)) {
            $normalized = array_map(
                static fn ($item) => trim((string) $item),
                $value
            );

            $filtered = array_values(
                array_filter($normalized, static fn ($item) => $item !== '')
            );

            return $filtered ? implode(',', array_unique($filtered)) : null;
        }

        $stringValue = trim((string) $value);

        return $stringValue !== '' ? $stringValue : null;
    }
}
