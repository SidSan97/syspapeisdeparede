<?php

namespace App\Repositories;

use App\Models\Budget;
use App\Support\Budget\BudgetCalculator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BudgetRepository {

    public function all()
    {
        return Budget::with('rooms.walls')->get();
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

            return $budget->load(['rooms.walls']);
        });

        return $budget;
    }

    public function cancel(Budget $budget): Budget
    {
        $budget->update([
            'status' => 'cancelado',
        ]);

        return $budget->fresh(['rooms.walls']);
    }
}
