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
                'state' => 'completed',
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
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        } finally {
            if (file_exists($this->zipPath)) {
                unlink($this->zipPath);
            }
        }
    }

    private function processZip(): array
    {
        $zip = new ZipArchive;

        if ($zip->open($this->zipPath) !== true) {
            throw new \RuntimeException('Não foi possível abrir o arquivo ZIP.');
        }

        try {
            $offset = $this->detectOffset($zip);

            // Pass 1: Construir mapas separados — capas de categoria e imagens normais
            [$entryMap, $coverMap] = $this->buildEntryMap($zip, $offset);

            $stats = [
                'categories_created' => 0,
                'categories_found' => 0,
                'subcategories_created' => 0,
                'subcategories_found' => 0,
                'covers_set' => 0,
                'images_imported' => 0,
                'images_skipped' => 0,
            ];

            $categoryCache = [];
            $subcategoryCache = [];

            // Pass 2a: Definir image_cover nas subcategorias
            foreach ($coverMap as $item) {
                [, $subcategory] = $this->resolveCategories(
                    $item['categoryName'],
                    $item['subcategoryName'],
                    $categoryCache,
                    $subcategoryCache,
                    $stats,
                );

                $path = $this->storeEntry($zip, $item['entry'], $item['ext']);

                if ($path === null) {
                    $stats['images_skipped']++;

                    continue;
                }

                $subcategory->update(['image_cover' => $path]);
                $stats['covers_set']++;
            }

            // Pass 2b: Persistir cada produto (um registro por nome base)
            foreach ($entryMap as $item) {
                [, $subcategory] = $this->resolveCategories(
                    $item['categoryName'],
                    $item['subcategoryName'],
                    $categoryCache,
                    $subcategoryCache,
                    $stats,
                );

                $coverPath = $this->storeEntry($zip, $item['coverEntry'], $item['coverExt']);
                $stillPath = $this->storeEntry($zip, $item['stillEntry'], $item['stillExt']);

                if ($coverPath === null && $stillPath === null) {
                    $stats['images_skipped']++;

                    continue;
                }

                CollectionImage::updateOrCreate(
                    [
                        'collection_category_id' => $subcategory->id,
                        'name' => $item['baseName'],
                    ],
                    array_filter([
                        'path_name' => $coverPath,
                        'still_path_name' => $stillPath,
                    ], fn ($v) => $v !== null),
                );

                $stats['images_imported']++;
            }

            return $stats;
        } finally {
            $zip->close();
        }
    }

    /**
     * Primeira passagem: percorre todas as entradas do ZIP e separa em dois mapas.
     *
     * - $coverMap : arquivos cujo nome base é "capa" → definem image_cover da subcategoria.
     * - $entryMap : demais arquivos → geram registros CollectionImage (agrupados por nome base).
     *
     * @return array{
     *   0: array<string, array{categoryName: string, subcategoryName: string, baseName: string, coverEntry: ?string, coverExt: ?string, stillEntry: ?string, stillExt: ?string}>,
     *   1: array<string, array{categoryName: string, subcategoryName: string, entry: string, ext: string}>
     * }
     */
    private function buildEntryMap(ZipArchive $zip, int $offset): array
    {
        $entryMap = [];
        $coverMap = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);

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
                continue;
            }

            ['baseName' => $baseName, 'isStill' => $isStill] = $this->parseFilename($filename);

            // Arquivo "capa" → imagem de capa da subcategoria, não um produto da coleção
            if (mb_strtolower($baseName) === 'capa') {
                $coverKey = mb_strtolower("{$categoryName}\x00{$subcategoryName}");
                $coverMap[$coverKey] = [
                    'categoryName' => $categoryName,
                    'subcategoryName' => $subcategoryName,
                    'entry' => $entryName,
                    'ext' => $ext,
                ];

                continue;
            }

            $mapKey = mb_strtolower("{$categoryName}\x00{$subcategoryName}\x00{$baseName}");

            if (! isset($entryMap[$mapKey])) {
                $entryMap[$mapKey] = [
                    'categoryName' => $categoryName,
                    'subcategoryName' => $subcategoryName,
                    'baseName' => $baseName,
                    'coverEntry' => null,
                    'coverExt' => null,
                    'stillEntry' => null,
                    'stillExt' => null,
                ];
            }

            if ($isStill) {
                $entryMap[$mapKey]['stillEntry'] = $entryName;
                $entryMap[$mapKey]['stillExt'] = $ext;
            } else {
                $entryMap[$mapKey]['coverEntry'] = $entryName;
                $entryMap[$mapKey]['coverExt'] = $ext;
            }
        }

        return [$entryMap, $coverMap];
    }

    /**
     * Garante existência de categoria e subcategoria, atualiza os caches e os stats.
     *
     * @param  array<string, CollectionCategory>  $categoryCache
     * @param  array<string, CollectionCategory>  $subcategoryCache
     * @param  array<string, int>  $stats
     * @return array{0: CollectionCategory, 1: CollectionCategory}
     */
    private function resolveCategories(
        string $categoryName,
        string $subcategoryName,
        array &$categoryCache,
        array &$subcategoryCache,
        array &$stats,
    ): array {
        $catKey = mb_strtolower($categoryName);
        if (! isset($categoryCache[$catKey])) {
            [$cat, $created] = $this->firstOrCreateCategory($categoryName, null);
            $categoryCache[$catKey] = $cat;
            $created ? $stats['categories_created']++ : $stats['categories_found']++;
        }

        $subKey = mb_strtolower("{$categoryName}/{$subcategoryName}");
        if (! isset($subcategoryCache[$subKey])) {
            [$sub, $created] = $this->firstOrCreateCategory($subcategoryName, $categoryCache[$catKey]->id);
            $subcategoryCache[$subKey] = $sub;
            $created ? $stats['subcategories_created']++ : $stats['subcategories_found']++;
        }

        return [$categoryCache[$catKey], $subcategoryCache[$subKey]];
    }

    /**
     * Extrai o nome base e detecta se é arquivo STILL.
     *
     * @return array{baseName: string, isStill: bool}
     */
    private function parseFilename(string $filename): array
    {
        $nameWithoutExt = pathinfo($filename, PATHINFO_FILENAME);

        if (preg_match('/^(.+?)\s*\(still\)\s*$/i', $nameWithoutExt, $matches)) {
            return [
                'baseName' => trim($matches[1]),
                'isStill' => true,
            ];
        }

        return [
            'baseName' => $nameWithoutExt,
            'isStill' => false,
        ];
    }

    /**
     * Grava uma entrada do ZIP no disco e retorna o caminho ou null em caso de falha.
     */
    private function storeEntry(ZipArchive $zip, ?string $entryName, ?string $ext): ?string
    {
        if ($entryName === null || $ext === null) {
            return null;
        }

        $stream = $zip->getStream($entryName);

        if ($stream === false) {
            Log::warning('[ImportCollectionZipJob] Entrada ilegível', ['entry' => $entryName]);

            return null;
        }

        $storagePath = 'collection-images/'.Str::uuid().'.'.$ext;

        Storage::disk('public')->writeStream($storagePath, $stream);

        fclose($stream);

        return $storagePath;
    }

    /**
     * Detecta se o ZIP possui uma pasta wrapper (ex: criado no macOS/Windows com pasta raiz).
     * Retorna o número de níveis a ignorar no início do caminho.
     */
    private function detectOffset(ZipArchive $zip): int
    {
        $topLevelDirs = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            $parts = $this->splitPath($name);

            if (empty($parts)) {
                continue;
            }

            $top = $parts[0];

            if ($top === '__MACOSX') {
                continue;
            }

            $topLevelDirs[$top] = true;
        }

        return count($topLevelDirs) === 1 ? 1 : 0;
    }

    /**
     * Extrai [categoryName, subcategoryName, filename] ou null se não for relevante.
     */
    private function parseParts(string $entryName, int $offset): ?array
    {
        $parts = $this->splitPath($entryName);

        $parts = array_slice($parts, $offset);

        if (empty($parts) || $parts[0] === '__MACOSX') {
            return null;
        }

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
            fn (string $p): bool => $p !== '' && ! str_starts_with($p, '.')
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
