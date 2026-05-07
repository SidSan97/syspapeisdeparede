<?php

namespace App\Services;

use App\Models\BudgetWall;
use App\Models\OrderBudget;
use App\Models\Order;
use Illuminate\Support\Collection;

class LayoutService
{
    /**
     * Transforma uma coleção de OrderBudgets em dados formatados para layouts
     *
     * @param Collection<int, OrderBudget> $orderBudgets
     * @param string|null $typePage Filtro de tipo de página ('layout' ou 'product')
     * @return array
     */
    public function transformLayouts(Collection $orderBudgets, ?string $typePage = null): array
    {
        return $orderBudgets->map(function (OrderBudget $orderBudget) use ($typePage) {
            $order = $orderBudget->order;
            $wall = $orderBudget->wall;

            if (!$order || !$wall) {
                return null;
            }

            // Obter imagem da parede específica
            $wallImage = $this->getWallImage($wall);
            $deliveryDates = $this->calculateDeliveryDates($order);

            // Criar nome do card baseado na parede
            $roomName = $wall->room->name ?? 'Ambiente';
            $wallName = $wall->name ?? 'Parede';
            $cardName = $order->name . ' - ' . $roomName . ' - ' . $wallName;

            // Carregar comentários aprovados
            $orderBudget->load(['comments' => function ($query) {
                $query->approved()->with('commentator')->orderBy('created_at', 'desc');
            }]);

            // Carregar membros do card
            // Busca na tabela pivot layout_card_user usando card_id (id do OrderBudget)
            // e com base no user_id busca os dados do usuário na tabela users
            $orderBudget->load('users');

            // Carregar histórico do card (filtrar por type_page se fornecido)
            if ($typePage) {
                $orderBudget->load(['history' => function ($query) use ($typePage) {
                    $query->where('type_page', $typePage);
                }]);
            } else {
                $orderBudget->load('history');
            }

            $comments = $orderBudget->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'user_name' => $comment->commentator->name ?? 'Anônimo',
                    'user_id' => $comment->commentator_id ?? null,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ];
            })->toArray();

            // Mapear membros: busca na layout_card_user por card_id e pega o nome do usuário
            $members = $orderBudget->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            })->toArray();

            // Mapear histórico do card
            $history = $orderBudget->history->map(function ($historyItem) {
                return [
                    'id' => $historyItem->id,
                    'description' => $historyItem->description,
                    'type_page' => $historyItem->type_page,
                    'created_at' => $historyItem->created_at,
                    'updated_at' => $historyItem->updated_at,
                ];
            })->toArray();

            return [
                'id' => $orderBudget->id,
                'order_id' => $order->id,
                'budget_wall_id' => $wall->id,
                'name' => $cardName,
                'total_amount' => (float) $order->total_amount,
                'total_amount_installments' => (float) $order->total_amount_installments,
                'delivery_time' => $order->delivery_time ?? 0,
                'delivery_date_start' => $deliveryDates['start'],
                'delivery_date_end' => $deliveryDates['end'],
                'delivery_date_start_full' => $deliveryDates['start_full'],
                'delivery_date_end_full' => $deliveryDates['end_full'],
                'status' => $orderBudget->status,
                'layout_column_names_id' => $orderBudget->layout_column_names_id,
                'production_column_names_id' => $orderBudget->production_column_names_id,
                'production_date' => $orderBudget->production_date,
                'production_percentage' => $orderBudget->production_percentage,
                'tinyErp_order_id' => $orderBudget->tinyErp_order_id,
                'description' => $orderBudget->description,
                'activity_running_since' => $orderBudget->activity_running_since?->toIso8601String(),
                'activity_elapsed_seconds' => (int) $orderBudget->activity_elapsed_seconds,
                'activity_total_seconds' => (int) $orderBudget->activity_total_seconds,
                'activity_is_running' => (bool) $orderBudget->activity_is_running,
                'comments' => $comments,
                'members' => $members,
                'history' => $history,
                'image' => $wallImage,
                'order' => $this->transformOrder($order),
                'wall' => $this->transformWall($wall),
                'uploaded_files' => $this->transformUploadedFiles($wall->files_referring_model ?? []),
                'created_at' => $orderBudget->created_at,
                'updated_at' => $orderBudget->updated_at,
            ];
        })->filter()->values()->all();
    }

    /**
     * Busca a primeira imagem do modelo de coleção da parede
     *
     * @param BudgetWall $wall
     * @return string|null
     */
    protected function getWallImage(BudgetWall $wall): ?string
    {
        if ($wall->collectionModel && $wall->collectionModel->files) {
            $firstFile = $wall->collectionModel->files->first();
            if ($firstFile) {
                return $this->makePublicUrl($firstFile->file_path);
            }
        }

        return null;
    }

    /**
     * Calcula as datas de entrega baseadas no prazo
     *
     * @param Order $order
     * @return array
     */
    protected function calculateDeliveryDates(Order $order): array
    {
        $deliveryTime = $order->delivery_time ?? 0;
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
     * Transforma um Order em array com dados formatados
     *
     * @param Order $order
     * @return array
     */
    protected function transformOrder(Order $order): array
    {
        $order->loadMissing([
            'rooms.walls.collectionModel.files',
            'dropshippingData',
        ]);

        $data = $order->toArray();

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
     * Transforma uma parede em array com dados formatados
     *
     * @param BudgetWall $wall
     * @return array
     */
    protected function transformWall(BudgetWall $wall): array
    {
        $wall->loadMissing(['collectionModel.files', 'room']);

        $data = $wall->toArray();

        if (!empty($data['collection_model']) && is_array($data['collection_model'])) {
            $data['collection_model']['name'] = $data['collection_model']['name'] ?? null;

            // Transformar arquivos do modelo de coleção
            if (!empty($data['collection_model']['files']) && is_array($data['collection_model']['files'])) {
                $data['collection_model']['files'] = array_map(function ($file) {
                    return [
                        'id' => $file['id'] ?? null,
                        'name' => $file['file_name'] ?? null,
                        'file_name' => $file['file_name'] ?? null,
                        'file_path' => $file['file_path'] ?? null,
                        'url' => $this->makePublicUrl($file['file_path'] ?? null),
                    ];
                }, $data['collection_model']['files']);
            }
        }

        return $data;
    }

    /**
     * Transforma arquivos de upload em array com URLs formatadas
     *
     * @param array|null $files
     * @return array
     */
    protected function transformUploadedFiles(?array $files): array
    {
        if (!is_array($files) || empty($files)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($filePath) {
            if (is_string($filePath) && !empty($filePath)) {
                return [
                    'file_path' => $filePath,
                    'url' => $this->makePublicUrl($filePath),
                    'name' => basename($filePath),
                ];
            }
            return null;
        }, $files)));
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

