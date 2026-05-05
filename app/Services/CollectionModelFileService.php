<?php

namespace App\Services;

use App\Models\CollectionModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CollectionModelFileService
{
    public function addFiles(CollectionModel $model, array $files): void
    {
        foreach ($files as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('collection-models', 'public');

            $model->files()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
            ]);
        }
    }

    public function deleteFiles(CollectionModel $model, ?array $ids = null): void
    {
        $files = $model->files()
            ->when($ids, fn ($q) => $q->whereIn('id', $ids))
            ->get();

        foreach ($files as $file) {
            Storage::disk('public')->delete($file->file_path);
            $file->delete();
        }
    }
}
