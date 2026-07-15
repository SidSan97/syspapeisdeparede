<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BudgetResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing(['rooms.walls.collectionModel.files', 'dropshippingData']);

        $data = $this->resource->toArray();

        // Incluir dados de dropshipping se existirem
        if ($this->resource->relationLoaded('dropshippingData') && $this->resource->dropshippingData) {
            $dropshippingData = $this->resource->dropshippingData;
            $data['dropshipping_data'] = [
                'id' => $dropshippingData->id,
                'name' => $dropshippingData->name,
                'person_type' => $dropshippingData->person_type,
                'cpf_cnpj' => $dropshippingData->cpf_cnpj,
                'IE' => $dropshippingData->IE,
                'email' => $dropshippingData->email,
                'phone' => $dropshippingData->phone,
                'cep' => $dropshippingData->cep,
                'uf' => $dropshippingData->uf,
                'state' => $dropshippingData->state,
                'city' => $dropshippingData->city,
                'neighborhood' => $dropshippingData->neighborhood,
                'public_space' => $dropshippingData->public_space,
                'number' => $dropshippingData->number,
                'complement' => $dropshippingData->complement,
                'dealer_id' => $dropshippingData->dealer_id,
            ];
        } else {
            $data['dropshipping_data'] = null;
        }

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

        $data['reseller_name'] = $this->resource->tenant?->name ?? null;

        return $data;
    }

    /**
     * Make a public URL from a storage path.
     */
    protected function makePublicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $rawUrl = Storage::url($path);

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

    protected static function normalizePaymentMethod(?string $method): ?string
    {
        if ($method === null) {
            return null;
        }

        return $method === 'installment' ? 'credit_card' : $method;
    }

    public static function getBudgetPaymentData(array $budget)
    {
        // Buscar comment_referring_model da primeira wall da primeira room ou primary room
        $comment = '';
        if (!empty($budget['rooms']) && is_array($budget['rooms'])) {
            $primaryRoomId = $budget['primary_budget_room_id'] ?? null;
            $targetRoom = null;

            if ($primaryRoomId) {
                foreach ($budget['rooms'] as $room) {
                    if (($room['id'] ?? null) == $primaryRoomId) {
                        $targetRoom = $room;
                        break;
                    }
                }
            }
            // Se não encontrou primary room, usar a primeira
            if (!$targetRoom && !empty($budget['rooms'][0])) {
                $targetRoom = $budget['rooms'][0];
            }

            // Buscar da primeira wall da room encontrada
            if ($targetRoom && !empty($targetRoom['walls']) && is_array($targetRoom['walls']) && !empty($targetRoom['walls'][0])) {
                $comment = $targetRoom['walls'][0]['comment_referring_model'] ?? '';
            }
        }

        $data = [
            'id' => $budget['id'],
            'name' => $budget['name'],
            'comments' => $comment,
            'total_amount' => (float)($budget['total_amount'] ?? 0),
            'total_amount_installments' => (float)($budget['total_amount_installments'] ?? 0),
            'carrier_price' => (float)($budget['selected_carrier_price'] ?? 0),
        ];

        return $data;
    }
}

