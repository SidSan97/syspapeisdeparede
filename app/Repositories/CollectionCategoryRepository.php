<?php

namespace App\Repositories;

use App\Models\CollectionCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CollectionCategoryRepository
{
    public function paginate(): LengthAwarePaginator
    {
        return CollectionCategory::query()
            ->whereNull('parent_id') 
            ->with(['children' => function ($query) {
                $query->withCount('images');
            }])
            ->orderByDesc('created_at')
            ->paginate();
    }

    public function all(): Collection
    {
        return CollectionCategory::query()
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->withCount('images');
            }])
            ->orderBy('name')
            ->get();
    }

    public function getTree(): Collection
    {
        return CollectionCategory::query()
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->withCount('images');
            }])
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): ?CollectionCategory
    {
        return CollectionCategory::find($id);
    }

    public function findByParent(?int $parentId = null): Collection
    {
        return CollectionCategory::query()
            ->where('parent_id', $parentId)
            ->withCount('images')
            ->orderBy('name')
            ->get();
    }

    /**
     * Busca categorias cujo nome contenha o termo (parcial ou total).
     */
    public function search(string $query): Collection
    {
        $term = trim($query);
        if ($term === '') {
            return collect();
        }

        return CollectionCategory::query()
            ->where('name', 'like', '%' . $term . '%')
            ->withCount('images')
            ->with('parent:id,name')
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    public function create(array $attributes): CollectionCategory
    {
        if (isset($attributes['image_cover']) && $attributes['image_cover'] instanceof UploadedFile) {
            $attributes['image_cover'] = $this->storeImage($attributes['image_cover']);
        }

        return CollectionCategory::create($attributes);
    }

    public function update(CollectionCategory $category, array $attributes): CollectionCategory
    {
        if (isset($attributes['image_cover']) && $attributes['image_cover'] instanceof UploadedFile) {
            // Deletar imagem antiga se existir
            if ($category->image_cover) {
                $this->deleteImage($category->image_cover);
            }
            $attributes['image_cover'] = $this->storeImage($attributes['image_cover']);
        }

        $category->update($attributes);

        return $category->refresh();
    }

    protected function storeImage(UploadedFile $file): string
    {
        return $file->store('collection-categories/covers', ['disk' => 'public']);
    }

    protected function deleteImage(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    public function delete(CollectionCategory $category): bool
    {
        return (bool) $category->delete();
    }
}

