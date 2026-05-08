<?php

namespace App\Repositories;

use App\Models\CollectionCategory;
use App\Models\CollectionImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CollectionImageRepository
{
    /**
     * Pagina imagens agregadas da categoria, na mesma ordem do resource de categoria
     * (filhos em sequência ordenados por id, depois imagens diretas na própria categoria).
     */
    public function paginateForCategory(
        CollectionCategory $collectionCategory,
        int $perPage,
        int $page,
    ): LengthAwarePaginator {
        $collectionCategory->load([
            'children' => function ($query) {
                $query->orderBy('id');
            },
        ]);

        $orderedCategoryIds = $collectionCategory->children->pluck('id')->all();
        $orderedCategoryIds[] = $collectionCategory->id;

        $baseQuery = CollectionImage::query()
            ->whereIn('collection_category_id', $orderedCategoryIds);

        if (count($orderedCategoryIds) > 1) {
            $caseParts = [];
            foreach (array_values($orderedCategoryIds) as $index => $catId) {
                $caseParts[] = sprintf('WHEN %d THEN %d', (int) $catId, (int) $index);
            }
            $baseQuery->orderByRaw(
                'CASE collection_category_id '.implode(' ', $caseParts).' ELSE 999999 END',
            );
        }

        $baseQuery->orderBy('id');

        return $baseQuery->paginate($perPage, ['*'], 'page', $page);
    }

    public function listGroupedByCollection(): Collection
    {
        return CollectionCategory::query()
            ->whereNull('parent_id')
            ->with(['children.images', 'images'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<int, UploadedFile>  $files
     * @param  array<int, string>  $names
     * @return Collection<int, CollectionImage>
     */
    public function storeMany(CollectionCategory $category, array $files, array $names = []): Collection
    {
        $stored = collect();

        foreach ($files as $index => $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('collection-images', ['disk' => 'public']);

            if (! $path) {
                continue;
            }

            // Usar o nome fornecido ou extrair do nome do arquivo
            $name = isset($names[$index]) && ! empty(trim($names[$index]))
                ? trim($names[$index])
                : pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

            $stored->push(
                $category->images()->create([
                    'name' => $name,
                    'path_name' => $path,
                ])
            );
        }

        return $stored;
    }

    public function storeManyForCategories($categories, array $files, array $names = []): Collection
    {
        $categoriesCollection = $categories instanceof Collection ? $categories : collect($categories);
        $stored = collect();

        foreach ($categoriesCollection as $category) {
            if (! $category instanceof CollectionCategory) {
                continue;
            }

            $storedForCategory = $this->storeMany($category, $files, $names);
            $stored = $stored->merge($storedForCategory);
        }

        return $stored;
    }

    public function delete(CollectionImage $collectionImage): bool
    {
        $this->deletePhysicalFile($collectionImage->path_name);

        return (bool) $collectionImage->delete();
    }

    protected function deletePhysicalFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
