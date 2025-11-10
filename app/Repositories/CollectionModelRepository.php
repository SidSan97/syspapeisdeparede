<?php

namespace App\Repositories;

use App\Models\CollectionModel;
use App\Models\CollectionModelFile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CollectionModelRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return CollectionModel::with('files')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return CollectionModel::with('files')
            ->orderBy('value')
            ->get();
    }

    public function create(array $attributes): CollectionModel
    {
        return CollectionModel::create($attributes)->load('files');
    }

    public function update(CollectionModel $model, array $attributes): CollectionModel
    {
        $model->update($attributes);

        return $model->load('files');
    }

    public function delete(CollectionModel $model): bool
    {
        $this->removeFiles($model);

        return (bool) $model->delete();
    }

    public function addFiles(CollectionModel $model, array $files): void
    {
        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $stored = $this->storeFile($file);

            if ($stored) {
                $model->files()->create($stored);
            }
        }
    }

    public function removeFiles(CollectionModel $model, ?array $fileIds = null): void
    {
        $query = $model->files();

        if ($fileIds !== null) {
            if (empty($fileIds)) {
                return;
            }

            $query->whereIn('id', $fileIds);
        }

        /** @var Collection<int, CollectionModelFile> $files */
        $files = $query->get();

        foreach ($files as $file) {
            $this->deletePhysicalFile($file->file_path);
            $file->delete();
        }
    }

    public function removeAllFiles(CollectionModel $model): void
    {
        $this->removeFiles($model);
    }

    protected function storeFile(UploadedFile $file): ?array
    {
        $path = $file->store('collection-models', ['disk' => 'public']);

        if (!$path) {
            return null;
        }

        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => $path,
        ];
    }

    protected function deletePhysicalFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}

