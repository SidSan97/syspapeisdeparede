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
}

