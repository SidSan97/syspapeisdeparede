<?php

namespace App\Repositories;

use App\Models\CollectionArt;
use App\Models\CollectionArtSubcategory;
use App\Models\CollectionImage;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CollectionImageRepository
{
    public function listGroupedByCollection(): Collection
    {
        return CollectionArt::query()
            ->with(['subcategories.images'])
            ->orderBy('name')
            ->get();
    }

    /**
     * @param CollectionArtSubcategory $subcategory
     * @param array<int, UploadedFile> $files
     * @param array<int, string> $names
     * @return Collection<int, CollectionImage>
     */
    public function storeMany(CollectionArtSubcategory $subcategory, array $files, array $names = []): Collection
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
                $subcategory->images()->create([
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

