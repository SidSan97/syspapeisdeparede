<?php

namespace Database\Seeders;

use App\Models\CollectionCategory;
use App\Models\CollectionImage;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use SplFileInfo;

/**
 * Lê a estrutura física de catalogo/<categoria>/<subcategoria>/*.jpg, copia
 * os arquivos para storage/app/public/collection-images/... e popula:
 *
 *  - collection_categories (parent_id = null)        → categoria raiz
 *  - collection_categories (parent_id = raiz.id)     → subcategorias
 *  - collection_images     (collection_category_id)  → imagens da subcategoria
 *
 * Regras de execução:
 *  - Reaproveita categorias já cadastradas via match por slug do nome.
 *  - Não sobrescreve image_cover já preenchido.
 *  - Cada arquivo .jpg/.png/.webp/.jpeg vira um CollectionImage (variantes
 *    como (STILL)/(WIDE)/(2) entram como registros separados).
 *  - O campo `name` da imagem é o nome do arquivo sem extensão e sem os
 *    sufixos entre parênteses (ex.: "3D Cubic Azul (STILL).jpg" → "3D Cubic Azul").
 *  - O campo `path_name` é o caminho relativo a storage/app/public,
 *    pronto para ser servido por asset('storage/' . $pathName).
 */
class CatalogImagesSeeder extends Seeder
{
    private const SOURCE_DIR_NAME = 'catalogo';

    private const STORAGE_RELATIVE_DIR = 'collection-images';

    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    private const COVER_FILENAME = 'capa.jpg';

    public function run(): void
    {
        $sourceBase = base_path(self::SOURCE_DIR_NAME);

        if (! File::isDirectory($sourceBase)) {
            $this->command?->warn("Pasta '".self::SOURCE_DIR_NAME."/' não encontrada em ".$sourceBase);

            return;
        }

        $targetBase = storage_path('app/public/'.self::STORAGE_RELATIVE_DIR);
        File::ensureDirectoryExists($targetBase);

        $rootDirs = File::directories($sourceBase);

        $totalImages = 0;
        $createdCategories = 0;

        foreach ($rootDirs as $rootDir) {
            $rootSlug = basename($rootDir);

            $rootCategory = $this->resolveCategory($rootSlug, null, $createdCategories);

            $this->maybeAttachCover($rootCategory, $rootDir, $rootSlug);

            foreach (File::directories($rootDir) as $subDir) {
                $subSlug = basename($subDir);
                $relativeFolder = $rootSlug.'/'.$subSlug;

                $subCategory = $this->resolveCategory($subSlug, $rootCategory->id, $createdCategories);
                $this->maybeAttachCover($subCategory, $subDir, $relativeFolder);

                foreach ($this->listImages($subDir) as $imageFile) {
                    $this->importImage($subCategory->id, $imageFile, $relativeFolder);
                    $totalImages++;
                }
            }
        }

        $this->command?->info("Categorias criadas: {$createdCategories}");
        $this->command?->info("Imagens importadas/atualizadas: {$totalImages}");
    }

    /**
     * Encontra a categoria pelo slug do nome dentro do mesmo parent_id;
     * cria com Title Case se não existir.
     */
    private function resolveCategory(string $folderSlug, ?int $parentId, int &$createdCounter): CollectionCategory
    {
        $existing = CollectionCategory::query()
            ->where('parent_id', $parentId)
            ->get()
            ->first(fn (CollectionCategory $category) => Str::slug($category->name) === $folderSlug);

        if ($existing) {
            return $existing;
        }

        $name = $this->slugToTitle($folderSlug);

        $category = CollectionCategory::firstOrCreate([
            'name' => $name,
            'parent_id' => $parentId,
        ]);

        if ($category->wasRecentlyCreated) {
            $createdCounter++;
        }

        return $category;
    }

    private function slugToTitle(string $slug): string
    {
        $normalized = str_replace(['-', '_'], ' ', $slug);

        return Str::title($normalized);
    }

    /**
     * Define image_cover apenas se ainda estiver vazio (skip_existing).
     */
    private function maybeAttachCover(CollectionCategory $category, string $folderPath, string $relativeFolder): void
    {
        if (filled($category->image_cover)) {
            return;
        }

        $coverPath = $folderPath.DIRECTORY_SEPARATOR.self::COVER_FILENAME;
        if (! File::exists($coverPath)) {
            return;
        }

        $relativeStored = $this->copyToStorage($coverPath, $relativeFolder, self::COVER_FILENAME);

        $category->update(['image_cover' => $relativeStored]);
    }

    /**
     * @return array<int, SplFileInfo>
     */
    private function listImages(string $directory): array
    {
        return collect(File::files($directory))
            ->filter(function (SplFileInfo $file) {
                $extension = strtolower($file->getExtension());

                if (! in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
                    return false;
                }

                return strtolower($file->getFilename()) !== self::COVER_FILENAME;
            })
            ->values()
            ->all();
    }

    private function importImage(int $categoryId, SplFileInfo $file, string $relativeFolder): void
    {
        $relativeStored = $this->copyToStorage(
            $file->getPathname(),
            $relativeFolder,
            $file->getFilename()
        );

        $cleanName = $this->cleanImageName($file->getFilename());

        CollectionImage::firstOrCreate(
            ['path_name' => $relativeStored],
            [
                'collection_category_id' => $categoryId,
                'name' => $cleanName,
            ]
        );
    }

    /**
     * Remove a extensão e os sufixos entre parênteses.
     * "3D Cubic Azul (STILL).jpg" → "3D Cubic Azul".
     */
    private function cleanImageName(string $filename): string
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $clean = preg_replace('/\s*\([^)]*\)\s*/u', ' ', $base) ?? $base;

        return trim((string) preg_replace('/\s+/u', ' ', $clean));
    }

    /**
     * Copia o arquivo (se ainda não existir no destino) e devolve o caminho
     * relativo a storage/app/public, ex.: "collection-images/adulto/3d/3D Cubic.jpg".
     */
    private function copyToStorage(string $sourcePath, string $relativeFolder, string $filename): string
    {
        $absoluteTargetDir = storage_path('app/public/'.self::STORAGE_RELATIVE_DIR.'/'.$relativeFolder);
        File::ensureDirectoryExists($absoluteTargetDir);

        $absoluteTargetPath = $absoluteTargetDir.DIRECTORY_SEPARATOR.$filename;

        if (! File::exists($absoluteTargetPath)) {
            File::copy($sourcePath, $absoluteTargetPath);
        }

        return self::STORAGE_RELATIVE_DIR.'/'.$relativeFolder.'/'.$filename;
    }
}
