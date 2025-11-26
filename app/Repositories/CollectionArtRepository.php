<?php

namespace App\Repositories;

use App\Models\CollectionArt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CollectionArtRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return CollectionArt::query()
            ->with(['subcategories' => function ($query) {
                $query->withCount('images');
            }])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return CollectionArt::query()
            ->with(['subcategories' => function ($query) {
                $query->withCount('images');
            }])
            ->orderBy('name')
            ->get();
    }

    public function create(array $attributes): CollectionArt
    {
        if (isset($attributes['image_cover']) && $attributes['image_cover'] instanceof UploadedFile) {
            $attributes['image_cover'] = $this->storeImage($attributes['image_cover']);
        }

        return CollectionArt::create($attributes);
    }

    public function update(CollectionArt $collectionArt, array $attributes): CollectionArt
    {
        if (isset($attributes['image_cover']) && $attributes['image_cover'] instanceof UploadedFile) {
            // Deletar imagem antiga se existir
            if ($collectionArt->image_cover) {
                $this->deleteImage($collectionArt->image_cover);
            }
            $attributes['image_cover'] = $this->storeImage($attributes['image_cover']);
        }

        $collectionArt->update($attributes);

        return $collectionArt->refresh();
    }

    protected function storeImage(UploadedFile $file): string
    {
        return $file->store('collection-arts/covers', ['disk' => 'public']);
    }

    protected function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function delete(CollectionArt $collectionArt): bool
    {
        return (bool) $collectionArt->delete();
    }
}

