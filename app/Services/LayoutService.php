<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\OrderBudget;
use Illuminate\Support\Collection;

class LayoutService
{
    /**
     * Transforma uma coleção de OrderBudgets em dados formatados para layouts
     *
     * @param Collection<int, OrderBudget> $orderBudgets
     * @return array
     */
    public function transformLayouts(Collection $orderBudgets): array
    {
        return $orderBudgets->map(function (OrderBudget $orderBudget) {
            $budget = $orderBudget->budget;
            if (!$budget) {
                return null;
            }

            $firstImage = $this->getFirstImage($budget);
            $deliveryDates = $this->calculateDeliveryDates($budget);

            return [
                'id' => $orderBudget->id,
                'budget_id' => $budget->id,
                'name' => $budget->name,
                'total_amount' => (float) $budget->total_amount,
                'delivery_time' => $budget->delivery_time ?? 0,
                'delivery_date_start' => $deliveryDates['start'],
                'delivery_date_end' => $deliveryDates['end'],
                'delivery_date_start_full' => $deliveryDates['start_full'],
                'delivery_date_end_full' => $deliveryDates['end_full'],
                'status' => $orderBudget->status,
                'image' => $firstImage,
                'budget' => $this->transformBudget($budget),
                'created_at' => $orderBudget->created_at,
                'updated_at' => $orderBudget->updated_at,
            ];
        })->filter()->values()->all();
    }

    /**
     * Busca a primeira imagem do primeiro modelo de coleção encontrado
     *
     * @param Budget $budget
     * @return string|null
     */
    protected function getFirstImage(Budget $budget): ?string
    {
        if (!$budget->rooms) {
            return null;
        }

        foreach ($budget->rooms as $room) {
            if ($room->walls) {
                foreach ($room->walls as $wall) {
                    if ($wall->collectionModel && $wall->collectionModel->files) {
                        $firstFile = $wall->collectionModel->files->first();
                        if ($firstFile) {
                            return $this->makePublicUrl($firstFile->file_path);
                        }
                    }
                }
            }
        }

        return null;
    }

    /**
     * Calcula as datas de entrega baseadas no prazo
     *
     * @param Budget $budget
     * @return array
     */
    protected function calculateDeliveryDates(Budget $budget): array
    {
        $deliveryTime = $budget->delivery_time ?? 0;
        $startDate = \Carbon\Carbon::now();
        $endDate = $startDate->copy()->addDays($deliveryTime);

        $months = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];
        $startFormatted = $startDate->day . ' de ' . $months[$startDate->month - 1];
        $endFormatted = $endDate->day . ' de ' . $months[$endDate->month - 1];

        return [
            'start' => $startFormatted,
            'end' => $endFormatted,
            'start_full' => $startDate->format('Y-m-d'),
            'end_full' => $endDate->format('Y-m-d'),
        ];
    }

    /**
     * Transforma um Budget em array com dados formatados
     *
     * @param Budget $budget
     * @return array
     */
    protected function transformBudget(Budget $budget): array
    {
        $budget->loadMissing(['rooms.walls.collectionModel.files']);

        $data = $budget->toArray();

        if (!empty($data['rooms']) && is_array($data['rooms'])) {
            foreach ($data['rooms'] as &$room) {
                if (!empty($room['walls']) && is_array($room['walls'])) {
                    foreach ($room['walls'] as &$wall) {
                        $wall['collection_model_name'] = $wall['collection_model']['name'] ?? null;

                        // Transformar arquivos do modelo de coleção
                        if (!empty($wall['collection_model']['files']) && is_array($wall['collection_model']['files'])) {
                            $wall['collection_model']['files'] = array_map(function ($file) {
                                return [
                                    'id' => $file['id'] ?? null,
                                    'name' => $file['file_name'] ?? null,
                                    'file_name' => $file['file_name'] ?? null,
                                    'file_path' => $file['file_path'] ?? null,
                                    'url' => $this->makePublicUrl($file['file_path'] ?? null),
                                ];
                            }, $wall['collection_model']['files']);
                        }
                    }
                    unset($wall);
                }
            }
            unset($room);
        }

        return $data;
    }

    /**
     * Gera URL pública para um arquivo
     *
     * @param string|null $path
     * @return string|null
     */
    protected function makePublicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $rawUrl = \Illuminate\Support\Facades\Storage::url($path);

        $appUrl = config('app.url') ?: url('/');
        $appUrl = rtrim($appUrl, '/');

        $parsedPath = parse_url($rawUrl, PHP_URL_PATH) ?: $rawUrl;
        $parsedQuery = parse_url($rawUrl, PHP_URL_QUERY);

        $finalUrl = $appUrl . $parsedPath;

        if ($parsedQuery) {
            $finalUrl .= '?' . $parsedQuery;
        }

        return $finalUrl;
    }
}

