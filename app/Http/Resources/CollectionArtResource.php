<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CollectionImageResource;
use App\Http\Resources\CollectionArtSubcategoryResource;

class CollectionArtResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        $allImages = collect();
        $totalImagesCount = 0;

        if ($this->relationLoaded('subcategories')) {
            foreach ($this->subcategories as $subcategory) {
                if ($subcategory->relationLoaded('images')) {
                    $allImages = $allImages->merge($subcategory->images);
                }
                if (isset($subcategory->images_count)) {
                    $totalImagesCount += $subcategory->images_count;
                }
            }
        }

        return [
            'id' => $this->id,
            'name' => $this->name,
            'images_count' => $totalImagesCount > 0 ? $totalImagesCount : ($this->whenCounted('images_count', $this->images_count ?? 0)),
            'subcategories' => CollectionArtSubcategoryResource::collection($this->whenLoaded('subcategories')),
            'images' => CollectionImageResource::collection($allImages->isEmpty() ? $this->whenLoaded('images') : $allImages),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

