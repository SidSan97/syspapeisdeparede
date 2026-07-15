<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionImageResource extends JsonResource
{
    /**
     * @param  Request  $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'collection_category_id' => $this->collection_category_id,
            'name' => $this->name,
            'path_name' => $this->path_name,
            'url' => $this->resolveStorageUrl($this->path_name),
            'still_path_name' => $this->still_path_name,
            'still_url' => $this->resolveStorageUrl($this->still_path_name),
            'category' => new CollectionCategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function resolveStorageUrl(?string $path): ?string
    {
        $path = ltrim((string) ($path ?? ''), '/');

        if ($path === '') {
            return null;
        }

        $baseUrl = rtrim(config('app.url') ?: url('/'), '/');
        $normalizedPath = ltrim(preg_replace('#^storage/#', '', $path), '/');

        return "{$baseUrl}/storage/{$normalizedPath}";
    }
}
