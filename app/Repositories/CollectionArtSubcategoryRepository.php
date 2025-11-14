<?php

namespace App\Repositories;

use App\Models\CollectionArtSubcategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CollectionArtSubcategoryRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return CollectionArtSubcategory::query()
            ->with(['collectionArt', 'images'])
            ->withCount('images')
            ->orderByDesc('created_at')
            ->paginate($perPage);
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
        return CollectionArtSubcategory::create($attributes);
    }

    public function update(CollectionArtSubcategory $subcategory, array $attributes): CollectionArtSubcategory
    {
        $subcategory->update($attributes);

        return $subcategory->refresh();
    }

    public function delete(CollectionArtSubcategory $subcategory): bool
    {
        return (bool) $subcategory->delete();
    }
}

