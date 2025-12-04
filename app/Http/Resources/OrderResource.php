<?php

namespace App\Http\Resources;

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
        $this->resource->loadMissing(['user', 'tenant', 'primaryRoom']);

        $data = $this->resource->toArray();

        // Transformar arquivos de referência
        if (!empty($data['files_referring_model']) && is_array($data['files_referring_model'])) {
            $data['files_referring_model'] = array_map(function ($filePath) {
                return [
                    'path' => $filePath,
                    'url' => $this->makePublicUrl($filePath),
                    'name' => basename($filePath),
                ];
            }, $data['files_referring_model']);
        }

        // Normalizar método de pagamento
        $data['payment_method'] = $this->normalizePaymentMethod($data['payment_method'] ?? null);

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
            $data['primary_room'] = [
                'id' => $this->resource->primaryRoom->id,
                'name' => $this->resource->primaryRoom->name,
            ];
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

    /**
     * Normalize payment method.
     */
    protected function normalizePaymentMethod(?string $method): ?string
    {
        if ($method === null) {
            return null;
        }

        return $method === 'installment' ? 'credit_card' : $method;
    }
}

