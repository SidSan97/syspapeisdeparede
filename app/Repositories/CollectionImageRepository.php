<?php

namespace App\Repositories;

use App\Models\CollectionCategory;
use App\Models\CollectionImage;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CollectionImageRepository
{
    public function listGroupedByCollection(): Collection
    {
        return CollectionCategory::query()
            ->whereNull('parent_id')
            ->with(['children.images', 'images'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param CollectionCategory $category
     * @param array<int, UploadedFile> $files
     * @param array<int, string> $names
     * @return Collection<int, CollectionImage>
     */
    public function storeMany(CollectionCategory $category, array $files, array $names = []): Collection
    {
        $stored = collect();

        foreach ($files as $index => $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('collection-images', ['disk' => 'public']);

            if (!$path) {
                continue;
            }

            // Usar o nome fornecido ou extrair do nome do arquivo
            $name = isset($names[$index]) && !empty(trim($names[$index]))
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

