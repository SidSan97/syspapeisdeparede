<?php

namespace App\Http\Controllers\API\V1;

use App\Jobs\ImportCollectionZipJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class CollectionImportController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Recebe um chunk do arquivo ZIP e armazena temporariamente.
     */
    public function chunk(Request $request): JsonResponse
    {
        $request->validate([
            'chunk'        => 'required|file',
            'upload_id'    => ['required', 'string', 'regex:/^[a-zA-Z0-9\-]{8,64}$/'],
            'chunk_index'  => 'required|integer|min:0|max:9999',
            'total_chunks' => 'required|integer|min:1|max:10000',
        ]);

        $uploadId   = $request->input('upload_id');
        $chunkIndex = (int) $request->input('chunk_index');

        $chunkDir = $this->chunkDir($uploadId);

        if (! is_dir($chunkDir)) {
            mkdir($chunkDir, 0755, true);
        }

        $request->file('chunk')->move($chunkDir, "chunk_{$chunkIndex}");

        return $this->sendResponse(
            ['upload_id' => $uploadId, 'chunk_index' => $chunkIndex],
            'Chunk recebido.'
        );
    }

    /**
     * Monta os chunks em um único ZIP e dispara o job de importação.
     */
    public function process(Request $request): JsonResponse
    {
        $request->validate([
            'upload_id'    => ['required', 'string', 'regex:/^[a-zA-Z0-9\-]{8,64}$/'],
            'total_chunks' => 'required|integer|min:1|max:10000',
        ]);

        $uploadId    = $request->input('upload_id');
        $totalChunks = (int) $request->input('total_chunks');
        $chunkDir    = $this->chunkDir($uploadId);

        for ($i = 0; $i < $totalChunks; $i++) {
            if (! file_exists("{$chunkDir}/chunk_{$i}")) {
                return $this->sendError("Chunk {$i} não encontrado. Faça o upload novamente.", [], 422);
            }
        }

        $zipPath = storage_path("app/temp/{$uploadId}.zip");

        $out = fopen($zipPath, 'wb');
        for ($i = 0; $i < $totalChunks; $i++) {
            $in = fopen("{$chunkDir}/chunk_{$i}", 'rb');
            while (! feof($in)) {
                fwrite($out, fread($in, 65536));
            }
            fclose($in);
        }
        fclose($out);

        $this->removeDirectory($chunkDir);

        Cache::put("import_job:{$uploadId}", ['state' => 'pending'], now()->addHours(2));

        ImportCollectionZipJob::dispatch($zipPath, $uploadId);

        return $this->sendResponse(
            ['import_id' => $uploadId],
            'Processamento iniciado.'
        );
    }

    /**
     * Retorna o estado atual do job de importação.
     */
    public function status(string $importId): JsonResponse
    {
        if (! preg_match('/^[a-zA-Z0-9\-]{8,64}$/', $importId)) {
            return $this->sendError('ID de importação inválido.', [], 422);
        }

        $data = Cache::get("import_job:{$importId}");

        if ($data === null) {
            return $this->sendError('Importação não encontrada.', [], 404);
        }

        return $this->sendResponse($data, 'Status da importação.');
    }

    private function chunkDir(string $uploadId): string
    {
        return storage_path("app/temp/chunks/{$uploadId}");
    }

    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (scandir($dir) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }
}
