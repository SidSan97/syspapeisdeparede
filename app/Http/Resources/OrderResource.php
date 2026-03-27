<?php

namespace App\Http\Resources;

use App\Services\OrderPaymentCompositionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'rooms.walls.collectionModel.files',
            'user',
            'tenant',
            'primaryRoom',
            'dropshippingData',
            'paymentLinks',
            'orderBudgets',
        ]);

        $data = $this->resource->toArray();

        // Transformar arquivos de referência
        if (!empty($data['files_referring_model']) && is_array($data['files_referring_model'])) {
            $data['files_referring_model'] = array_map(function ($fileItem) {
                // Extrair o path do item (pode ser string ou array)
                $path = null;

                if (is_string($fileItem)) {
                    // Se é uma string simples, é o path
                    $path = $fileItem;
                } elseif (is_array($fileItem)) {
                    // Se é array, pode ter a chave 'path' ou ser indexado numericamente
                    if (isset($fileItem['path']) && is_string($fileItem['path'])) {
                        $path = $fileItem['path'];
                    } elseif (isset($fileItem[0]) && is_string($fileItem[0])) {
                        $path = $fileItem[0];
                    }
                }

                // Se não conseguiu extrair o path, retorna como está
                if (!$path || !is_string($path)) {
                    return $fileItem;
                }

                // Se já tem estrutura completa, preservar
                if (is_array($fileItem) && isset($fileItem['path'])) {
                    return [
                        'path' => $path,
                        'url' => $fileItem['url'] ?? $this->makePublicUrl($path),
                        'name' => $fileItem['name'] ?? basename($path),
                    ];
                }

                // Criar estrutura a partir do path
                return [
                    'path' => $path,
                    'url' => $this->makePublicUrl($path),
                    'name' => basename($path),
                ];
            }, $data['files_referring_model']);
        }

        // Normalizar método de pagamento
        $data['payment_method'] = $this->normalizePaymentMethod($data['payment_method'] ?? null);
        $data['reseller_name'] = $this->resource->tenant?->name ?? null;

        // Incluir informações do usuário
        if ($this->resource->relationLoaded('user') && $this->resource->user) {
            $data['user'] = [
                'id' => $this->resource->user->id,
                'name' => $this->resource->user->name,
                'email' => $this->resource->user->email,
            ];
        }

        // Incluir informações do tenant
        if ($this->resource->relationLoaded('tenant') && $this->resource->tenant) {
            $data['tenant'] = [
                'id' => $this->resource->tenant->id,
                'name' => $this->resource->tenant->name,
                'email' => $this->resource->tenant->email,
            ];
        }

        // Incluir informações da room primária
        if ($this->resource->relationLoaded('primaryRoom') && $this->resource->primaryRoom) {
            $primaryRoom = $this->resource->primaryRoom;
            $data['primary_room'] = [
                'id' => $primaryRoom->id,
                'name' => $primaryRoom->name,
                'raw_payload' => $primaryRoom->raw_payload ?? null,
            ];
        }

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

        // Transformar rooms e walls
        if (empty($data['rooms']) && !empty($data['primary_room']['raw_payload'])) {
            // Construir rooms a partir do raw_payload do primary_room
            $rawPayload = $data['primary_room']['raw_payload'];

            if (!empty($rawPayload['walls']) && is_array($rawPayload['walls'])) {
                $modelIds = array_filter(array_unique(array_column($rawPayload['walls'], 'model')));

                // Carregar os CollectionModels com seus files
                $collectionModels = \App\Models\CollectionModel::with('files')
                    ->whereIn('id', $modelIds)
                    ->get()
                    ->keyBy('id');

                $walls = [];
                foreach ($rawPayload['walls'] as $index => $wallData) {
                    $modelId = $wallData['model'] ?? null;
                    $collectionModel = $modelId ? ($collectionModels[$modelId] ?? null) : null;

                    $wall = [
                        'id' => null,
                        'name' => $wallData['name'] ?? null,
                        'width' => $wallData['width'] ?? null,
                        'height' => $wallData['height'] ?? null,
                        'position' => $index,
                        'continue_same_art' => $wallData['continueSameArt'] ?? false,
                        'continuations' => $wallData['continuations'] ?? [],
                    ];

                    // Adicionar dados do collection model
                    if ($collectionModel) {
                        $wall['collection_model'] = [
                            'id' => $collectionModel->id,
                            'name' => $collectionModel->name,
                            'files' => $collectionModel->files->map(function ($file) {
                                return [
                                    'id' => $file->id,
                                    'name' => $file->file_name,
                                    'file_name' => $file->file_name,
                                    'file_path' => $file->file_path,
                                    'url' => $this->makePublicUrl($file->file_path),
                                ];
                            })->toArray(),
                        ];
                        $wall['collection_model_name'] = $collectionModel->name;
                        $wall['collection_model_id'] = $collectionModel->id;
                    }

                    $walls[] = $wall;
                }

                // Criar a estrutura de room
                $data['rooms'] = [
                    [
                        'id' => $data['primary_room']['id'] ?? null,
                        'name' => $rawPayload['name'] ?? null,
                        'position' => 0,
                        'walls' => $walls,
                    ]
                ];
            }
        } elseif (!empty($data['rooms']) && is_array($data['rooms'])) {
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

        $data['payment_links'] = collect($this->resource->paymentLinks ?? [])
            ->map(function ($link) {
                return [
                    'id' => $link->id,
                    'components' => $link->components ?? [],
                    'payment_method' => $link->payment_method,
                    'installments' => $link->installments,
                    'amount_artes' => (float) $link->amount_artes,
                    'amount_produtos' => (float) $link->amount_produtos,
                    'amount_frete' => (float) $link->amount_frete,
                    'amount_total' => (float) $link->amount_total,
                    'status' => $link->status,
                    'payment_url' => $link->payment_url,
                    'expires_at' => $link->expires_at,
                    'paid_at' => $link->paid_at,
                    'created_at' => $link->created_at,
                ];
            })
            ->values()
            ->toArray();

        $data['order_budgets'] = collect($this->resource->orderBudgets ?? [])
            ->map(function ($card) {
                return [
                    'id' => $card->id,
                    'budget_wall_id' => $card->budget_wall_id,
                    'order_index' => $card->order_index,
                    'status' => $card->status,
                ];
            })
            ->sortBy('order_index')
            ->values()
            ->toArray();

        $composition = app(OrderPaymentCompositionService::class)->getOrderComposition($this->resource);
        $paidLinks = collect($this->resource->paymentLinks ?? [])->where('status', 'paid');
        $paidByComponent = [
            'ARTES' => (float) $paidLinks->sum('amount_artes'),
            'PRODUTOS' => (float) $paidLinks->sum('amount_produtos'),
            'FRETE' => (float) $paidLinks->sum('amount_frete'),
        ];
        $remainingByComponent = [
            'ARTES' => round(max(0, (float) $composition['ARTES'] - $paidByComponent['ARTES']), 2),
            'PRODUTOS_PIX' => round(max(0, (float) $composition['PRODUTOS_PIX'] - $paidByComponent['PRODUTOS']), 2),
            'PRODUTOS_CREDIT_CARD' => round(max(0, (float) $composition['PRODUTOS_CREDIT_CARD'] - $paidByComponent['PRODUTOS']), 2),
            'FRETE' => round(max(0, (float) $composition['FRETE'] - $paidByComponent['FRETE']), 2),
        ];

        $data['payment_breakdown'] = [
            'base' => $composition,
            'paid' => $paidByComponent,
            'remaining' => $remainingByComponent,
        ];

        return $data;
    }

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

    protected function normalizePaymentMethod(?string $method): ?string
    {
        if ($method === null) {
            return null;
        }

        return $method === 'installment' ? 'credit_card' : $method;
    }
}

