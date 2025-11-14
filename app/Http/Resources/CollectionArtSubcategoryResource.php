<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CollectionArtSubcategoryResource extends JsonResource
{
    /**
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'collection_art_id' => $this->collection_art_id,
            'collection_art' => new CollectionArtResource($this->whenLoaded('collectionArt')),
            'images_count' => $this->whenCounted('images', $this->images_count ?? 0),
            'images' => CollectionImageResource::collection($this->whenLoaded('images')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}

