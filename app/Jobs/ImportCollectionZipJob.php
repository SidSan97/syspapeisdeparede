<?php

namespace App\Jobs;

use App\Models\CollectionCategory;
use App\Models\CollectionImage;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ImportCollectionZipJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;
    public int $tries = 1;

    public function __construct(
        private readonly string $zipPath,
        private readonly string $importId,
    ) {}

    public function handle(): void
    {
        Cache::put("import_job:{$this->importId}", ['state' => 'processing'], now()->addHours(2));

        try {
            $result = $this->processZip();

            Cache::put("import_job:{$this->importId}", [
                'state'  => 'completed',
                'result' => $result,
            ], now()->addHours(2));

            Log::info('[ImportCollectionZipJob] Concluído', ['import_id' => $this->importId, ...$result]);
        } catch (\Throwable $e) {
            Cache::put("import_job:{$this->importId}", [
                'state' => 'failed',
                'error' => $e->getMessage(),
            ], now()->addHours(2));

            Log::error('[ImportCollectionZipJob] Falhou', [
                'import_id' => $this->importId,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ]);
        } finally {
            if (file_exists($this->zipPath)) {
                unlink($this->zipPath);
            }
        }
    }

    private function processZip(): array
    {
        $zip = new ZipArchive();

        if ($zip->open($this->zipPath) !== true) {
            throw new \RuntimeException('Não foi possível abrir o arquivo ZIP.');
        }

        try {
            $offset = $this->detectOffset($zip);

            $stats = [
                'categories_created'    => 0,
                'categories_found'      => 0,
                'subcategories_created' => 0,
                'subcategories_found'   => 0,
                'images_imported'       => 0,
                'images_skipped'        => 0,
            ];

            // Caches para evitar queries repetidas por nome
            $categoryCache    = [];
            $subcategoryCache = [];

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);

                // Entradas de diretório
                if (str_ends_with($entryName, '/')) {
                    continue;
                }

                $parts = $this->parseParts($entryName, $offset);

                if ($parts === null) {
                    continue;
                }

                [$categoryName, $subcategoryName, $filename] = $parts;

                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
                    $stats['images_skipped']++;
                    continue;
                }

                // Categoria raiz
                $catKey = mb_strtolower($categoryName);
                if (! isset($categoryCache[$catKey])) {
                    [$cat, $created] = $this->firstOrCreateCategory($categoryName, null);
                    $categoryCache[$catKey] = $cat;
                    $created ? $stats['categories_created']++ : $stats['categories_found']++;
                }
                $category = $categoryCache[$catKey];

                // Subcategoria
                $subKey = mb_strtolower("{$categoryName}/{$subcategoryName}");
                if (! isset($subcategoryCache[$subKey])) {
                    [$sub, $created] = $this->firstOrCreateCategory($subcategoryName, $category->id);
                    $subcategoryCache[$subKey] = $sub;
                    $created ? $stats['subcategories_created']++ : $stats['subcategories_found']++;
                }
                $subcategory = $subcategoryCache[$subKey];

                // Stream direto da entrada do ZIP para o disco — sem extrair
                $stream = $zip->getStream($entryName);

                if ($stream === false) {
                    Log::warning('[ImportCollectionZipJob] Entrada ilegível', ['entry' => $entryName]);
                    $stats['images_skipped']++;
                    continue;
                }

                $storagePath = 'collection-images/' . Str::uuid() . '.' . $ext;

                Storage::disk('public')->writeStream($storagePath, $stream);

                fclose($stream);

                CollectionImage::create([
                    'collection_category_id' => $subcategory->id,
                    'name'                   => pathinfo($filename, PATHINFO_FILENAME),
                    'path_name'              => $storagePath,
                ]);

                $stats['images_imported']++;
            }

            return $stats;
        } finally {
            $zip->close();
        }
    }

    /**
     * Detecta se o ZIP possui uma pasta wrapper (ex: criado no macOS/Windows com pasta raiz).
     * Retorna o número de níveis a ignorar no início do caminho.
     */
    private function detectOffset(ZipArchive $zip): int
    {
        $topLevelDirs = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name  = $zip->getNameIndex($i);
            $parts = $this->splitPath($name);

            if (empty($parts)) {
                continue;
            }

            $top = $parts[0];

            // Ignora metadados do macOS
            if ($top === '__MACOSX') {
                continue;
            }

            $topLevelDirs[$top] = true;
        }

        // Se todos os arquivos estão sob um único diretório raiz → é wrapper
        return count($topLevelDirs) === 1 ? 1 : 0;
    }

    /**
     * Extrai [categoryName, subcategoryName, filename] ou null se não for relevante.
     */
    private function parseParts(string $entryName, int $offset): ?array
    {
        $parts = $this->splitPath($entryName);

        // Remove o wrapper
        $parts = array_slice($parts, $offset);

        // Rejeita metadados do macOS e arquivos ocultos
        if (empty($parts) || $parts[0] === '__MACOSX') {
            return null;
        }

        // Esperamos exatamente 3 partes: categoria / subcategoria / arquivo
        if (count($parts) !== 3) {
            return null;
        }

        return $parts;
    }

    /** @return string[] */
    private function splitPath(string $path): array
    {
        return array_values(array_filter(
            explode('/', $path),
            fn(string $p): bool => $p !== '' && ! str_starts_with($p, '.')
        ));
    }

    /** @return array{0: CollectionCategory, 1: bool} */
    private function firstOrCreateCategory(string $name, ?int $parentId): array
    {
        $existing = CollectionCategory::query()
            ->where('name', $name)
            ->where('parent_id', $parentId)
            ->first();

        if ($existing) {
            return [$existing, false];
        }

        return [CollectionCategory::create(['name' => $name, 'parent_id' => $parentId]), true];
    }
}
