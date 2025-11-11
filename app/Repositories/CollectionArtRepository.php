<?php

namespace App\Repositories;

use App\Models\CollectionArt;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class CollectionArtRepository
{
    public function paginate(int $perPage = 15): LengthAwarePaginator
    {
        return CollectionArt::query()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function all(): Collection
    {
        return CollectionArt::query()
            ->orderBy('name')
            ->get();
    }

    public function create(array $attributes): CollectionArt
    {
        return CollectionArt::create($attributes);
    }

    public function update(CollectionArt $collectionArt, array $attributes): CollectionArt
    {
        $collectionArt->update($attributes);

        return $collectionArt->refresh();
    }

    public function delete(CollectionArt $collectionArt): bool
    {
        return (bool) $collectionArt->delete();
    }
}

