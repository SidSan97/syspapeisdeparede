<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class CollectionModelResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $files = $this->whenLoaded('files', function () {
            return $this->files->map(function ($file) {
                return [
                    'id' => $file->id,
                    'name' => $file->file_name,
                    'url' => Storage::url($file->file_path),
                ];
            });
        }, collect());

        $firstFile = $files->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'value' => (float) $this->value,
            'deadline' => (int) $this->deadline,
            'type_model_id' => (int) $this->type_model_id,
            'requests' => [
                'link' => (bool) $this->request_link,
                'comment' => (bool) $this->request_comment,
                'file' => (bool) $this->request_file,
            ],
            'link' => null,
            'comment' => null,
            'files' => $files,
            'fileName' => $firstFile['name'] ?? null,
            'fileUrl' => $firstFile['url'] ?? null,
            'type' => $this->whenLoaded('modelType', function ($type) {
                return [
                    'id' => $type->id,
                    'name' => $type->name,
                ];
            }, function () {
                if ($this->relationLoaded('modelType')) {
                    return [
                        'id' => optional($this->modelType)->id,
                        'name' => optional($this->modelType)->name,
                    ];
                }

                return null;
            }),
            'type_name' => optional($this->modelType)->name,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}

