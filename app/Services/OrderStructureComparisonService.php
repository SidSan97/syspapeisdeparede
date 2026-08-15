<?php

namespace App\Services;

use App\Models\BudgetWall;
use App\Models\Order;

class OrderStructureComparisonService
{
    /**
     * Indica se ambientes/paredes mudaram de forma que afeta composição/pagamento:
     * medidas (faixas), modelo, inclusão/remoção de paredes ou ambientes.
     *
     * @param  array<int, array<string, mixed>>  $rooms
     */
    public function hasStructuralRoomChanges(Order $order, array $rooms): bool
    {
        $order->loadMissing('rooms.walls');

        $current = $order->rooms->sortBy('position')->values();
        $incoming = array_values($rooms);

        if ($current->count() !== count($incoming)) {
            return true;
        }

        foreach ($incoming as $roomIndex => $roomData) {
            $existingRoom = $current->get($roomIndex);
            if (! $existingRoom) {
                return true;
            }

            $currentWalls = $existingRoom->walls->sortBy('position')->values();
            $incomingWalls = array_values($roomData['walls'] ?? []);

            if ($currentWalls->count() !== count($incomingWalls)) {
                return true;
            }

            foreach ($incomingWalls as $wallIndex => $wallData) {
                $existingWall = $currentWalls->get($wallIndex);
                if (! $existingWall) {
                    return true;
                }

                if ($this->wallStructureDiffers($existingWall, $wallData)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $wallData
     */
    protected function wallStructureDiffers(BudgetWall $existingWall, array $wallData): bool
    {
        $incomingModelId = isset($wallData['model']) && $wallData['model'] !== '' && $wallData['model'] !== null
            ? (int) $wallData['model']
            : null;
        $existingModelId = $existingWall->collection_model_id !== null
            ? (int) $existingWall->collection_model_id
            : null;

        if ($incomingModelId !== $existingModelId) {
            return true;
        }

        if (! $this->sameDecimal($existingWall->width, $wallData['width'] ?? null)) {
            return true;
        }

        if (! $this->sameDecimal($existingWall->height, $wallData['height'] ?? null)) {
            return true;
        }

        $incomingContinuations = array_values($wallData['continuations'] ?? []);
        $existingContinuations = array_values($existingWall->continuations ?? []);

        if (count($incomingContinuations) !== count($existingContinuations)) {
            return true;
        }

        foreach ($incomingContinuations as $index => $continuation) {
            $existingContinuation = $existingContinuations[$index] ?? null;
            if (! is_array($existingContinuation) || ! is_array($continuation)) {
                return true;
            }

            if (! $this->sameDecimal($existingContinuation['width'] ?? null, $continuation['width'] ?? null)) {
                return true;
            }

            if (! $this->sameDecimal($existingContinuation['height'] ?? null, $continuation['height'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    protected function sameDecimal(mixed $left, mixed $right): bool
    {
        if ($left === null && ($right === null || $right === '')) {
            return true;
        }

        if ($left === null || $right === null || $right === '') {
            return false;
        }

        return abs((float) $left - (float) $right) < 0.0001;
    }
}
