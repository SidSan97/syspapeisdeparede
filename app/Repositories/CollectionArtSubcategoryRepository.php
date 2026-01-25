<?php

namespace App\Repositories;

use App\Models\CollectionArtSubcategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CollectionArtSubcategoryRepository
{
    public function paginate(): LengthAwarePaginator
    {
        return CollectionArtSubcategory::query()
            ->with(['collectionArt', 'images'])
            ->withCount('images')
            ->orderByDesc('created_at')
            ->paginate();
    }

    public function all(): Collection
    {
        return CollectionArtSubcategory::query()
            ->with(['collectionArt', 'images'])
            ->withCount('images')
            ->orderBy('name')
            ->get();
    }

    public function findByCollectionArt(int $collectionArtId): Collection
    {
        return CollectionArtSubcategory::query()
            ->where('collection_art_id', $collectionArtId)
            ->with('images')
            ->withCount('images')
            ->orderBy('name')
            ->get();
    }

    public function create(array $attributes): CollectionArtSubcategory
    {
        if (isset($attributes['sub_collection_image_cover']) && $attributes['sub_collection_image_cover'] instanceof UploadedFile) {
            $attributes['sub_collection_image_cover'] = $this->storeImage($attributes['sub_collection_image_cover']);
        }

        return CollectionArtSubcategory::create($attributes);
    }

    public function update(CollectionArtSubcategory $subcategory, array $attributes): CollectionArtSubcategory
    {
        if (isset($attributes['sub_collection_image_cover']) && $attributes['sub_collection_image_cover'] instanceof UploadedFile) {
            // Deletar imagem antiga se existir
            if ($subcategory->sub_collection_image_cover) {
                $this->deleteImage($subcategory->sub_collection_image_cover);
            }
            $attributes['sub_collection_image_cover'] = $this->storeImage($attributes['sub_collection_image_cover']);
        }

        $subcategory->update($attributes);

        return $subcategory->refresh();
    }

    protected function storeImage(UploadedFile $file): string
    {
        return $file->store('collection-art-subcategories/covers', ['disk' => 'public']);
    }

    protected function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function delete(CollectionArtSubcategory $subcategory): bool
    {
        return (bool) $subcategory->delete();
    }
}

