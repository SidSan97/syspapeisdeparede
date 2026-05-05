<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicCatalogItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
            'url'  => $this->resolveUrl(),
            'category' => $this->whenLoaded('category', fn () => [
                'id'     => $this->category->id,
                'name'   => $this->category->name,
                'parent' => $this->category->parent ? [
                    'id'   => $this->category->parent->id,
                    'name' => $this->category->parent->name,
                ] : null,
            ]),
        ];
    }

    private function resolveUrl(): ?string
    {
        $path = ltrim((string) ($this->path_name ?? ''), '/');

        if ($path === '') {
            return null;
        }

        $baseUrl          = rtrim(config('app.url') ?: url('/'), '/');
        $normalizedPath   = ltrim(preg_replace('#^storage/#', '', $path), '/');

        return "{$baseUrl}/storage/{$normalizedPath}";
    }
}
