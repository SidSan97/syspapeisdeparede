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
            'value' => (float) $this->value,
            'deadline' => (int) $this->deadline,
            'requests' => [
                'link' => (bool) $this->request_link,
                'comment' => (bool) $this->request_comment,
                'file' => (bool) $this->request_file,
            ],
            'link' => $this->link,
            'comment' => $this->comment,
            'files' => $files,
            'fileName' => $firstFile['name'] ?? null,
            'fileUrl' => $firstFile['url'] ?? null,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}

