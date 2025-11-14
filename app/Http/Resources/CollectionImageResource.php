<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CollectionArtSubcategoryResource;

class CollectionImageResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'collection_arts_id' => $this->collection_arts_id,
            'name' => $this->name,
            'path_name' => $this->path_name,
            'url' => $this->getUrl(),
            'subcategory' => new CollectionArtSubcategoryResource($this->whenLoaded('subcategory')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    protected function getUrl(): ?string
    {
        if (!$this->path_name) {
            return null;
        }

        $path = ltrim($this->path_name ?? '', '/');

        if ($path === '') {
            return null;
        }

        $baseUrl = config('app.url') ?: url('/');

        $normalizedPath = ltrim(preg_replace('#^storage/#', '', $path), '/');

        return rtrim($baseUrl, '/') . '/storage/' . $normalizedPath;
    }
}

