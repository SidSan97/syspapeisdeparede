<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WallResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,

            'name' => $this->name,

            'room' => [
                'id' => $this->room?->id,
                'name' => $this->room?->name,
            ],

            'collection_model' => $this->whenLoaded('collectionModel', function () {
                return [
                    'id' => $this->collectionModel->id,
                    'name' => $this->collectionModel->name,

                    'files' => $this->whenLoaded('collectionModel.files', function () {
                        return $this->collectionModel->files->map(function ($file) {
                            return [
                                'id' => $file->id,
                                'name' => $file->file_name,
                                'file_name' => $file->file_name,
                                'file_path' => $file->file_path,
                                'url' => $this->makePublicUrl($file->file_path),
                            ];
                        })->values();
                    }),
                ];
            }),
        ];
    }

        protected function makePublicUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $rawUrl = \Illuminate\Support\Facades\Storage::url($path);

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
}
