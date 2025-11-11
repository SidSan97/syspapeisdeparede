<?php

namespace App\Repositories;

use App\Models\CollectionArt;
use App\Models\CollectionImage;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class CollectionImageRepository
{
    public function listGroupedByCollection(): Collection
    {
        return CollectionArt::query()
            ->with('images')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param CollectionArt $collectionArt
     * @param array<int, UploadedFile> $files
     * @return Collection<int, CollectionImage>
     */
    public function storeMany(CollectionArt $collectionArt, array $files): Collection
    {
        $stored = collect();

        foreach ($files as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('collection-images', ['disk' => 'public']);

            if (!$path) {
                continue;
            }

            $stored->push(
                $collectionArt->images()->create([
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

