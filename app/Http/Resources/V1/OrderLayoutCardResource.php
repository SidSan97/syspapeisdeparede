<?php

namespace App\Http\Resources\V1;

use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\LayoutService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderLayoutCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $order = $this->order;
        $wall = $this->wall;

        if (!$order || !$wall) {
            return [];
        }

        $deliveryDates = $this->calculateDeliveryDates($order);

        $roomName = $wall->room->name ?? 'Ambiente';
        $wallName = $wall->name ?? 'Parede';

        $cardName = $order->name . ' - ' . $roomName . ' - ' . $wallName;

        return [
            'id' => $this->id,
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

            'status' => $this->status,
            'layout_column_names_id' => $this->layout_column_names_id,
            'production_column_names_id' => $this->production_column_names_id,
            'production_date' => $this->production_date,
            'production_percentage' => $this->production_percentage,
            'tinyErp_order_id' => $this->tinyErp_order_id,
            'description' => $this->description,
            'started_at' => $this->started_at?->toIso8601String(),
            'finished_at' => $this->finished_at?->toIso8601String(),

            'comments' => $this->comments->map(function ($comment) {
                return [
                    'id' => $comment->id,
                    'comment' => $comment->comment,
                    'user_name' => $comment->commentator->name ?? 'Anônimo',
                    'user_id' => $comment->commentator_id,
                    'created_at' => $comment->created_at,
                    'updated_at' => $comment->updated_at,
                ];
            })->values(),

            'members' => $this->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                ];
            })->values(),

            'history' => $this->history->map(function ($historyItem) {
                return [
                    'id' => $historyItem->id,
                    'description' => $historyItem->description,
                    'type_page' => $historyItem->type_page,
                    'created_at' => $historyItem->created_at,
                    'updated_at' => $historyItem->updated_at,
                ];
            })->values(),

            'image' => $this->collectionModel?->files?->first()
                ? $this->makePublicUrl($this->collectionModel->files->first()->file_path)
                : null,

            'order' => new OrderResource($order),
            'wall' => new WallResource($wall),

            'uploaded_files' => $this->transformUploadedFiles(
                $wall->files_referring_model ?? []
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

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

