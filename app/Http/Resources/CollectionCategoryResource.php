<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CollectionCategoryResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $allImages = collect();
        $totalImagesCount = 0;

        // Se tiver filhos carregados, coletar imagens de todos
        if ($this->relationLoaded('children')) {
            foreach ($this->children as $child) {
                if ($child->relationLoaded('images')) {
                    $allImages = $allImages->merge($child->images);
                }
                if (isset($child->images_count)) {
                    $totalImagesCount += $child->images_count;
                }
            }
        }

        // Se tiver imagens diretas na categoria
        if ($this->relationLoaded('images')) {
            $allImages = $allImages->merge($this->images);
            $totalImagesCount += $this->images->count();
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'image_cover' => $this->image_cover,
            'image_cover_url' => $this->image_cover ? Storage::url($this->image_cover) : null,
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function () {
                return new CollectionCategoryResource($this->parent);
            }),
            'children' => CollectionCategoryResource::collection($this->whenLoaded('children')),
            'images_count' => $totalImagesCount > 0 ? $totalImagesCount : ($this->whenCounted('images_count', $this->images_count ?? 0)),
            'images' => CollectionImageResource::collection($allImages->isEmpty() ? $this->whenLoaded('images') : $allImages),
            'is_root' => $this->parent_id === null,
            'depth' => $this->getDepth(),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

