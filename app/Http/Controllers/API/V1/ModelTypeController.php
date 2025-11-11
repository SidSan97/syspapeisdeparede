<?php

namespace App\Http\Controllers\API\V1;

use App\Repositories\ModelTypeRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ModelTypeController extends BaseController
{
    protected $modelTypeRepository;

    protected function withDatabaseHandling(string $action, callable $callback)
    {
        try {
            Log::info('[ModelType] Iniciando operação', [
                'action' => $action,
                'user_id' => optional(Auth::user())->id,
                'request_id' => request()->headers->get('X-Request-ID'),
            ]);

            $result = $callback();

            Log::info('[ModelType] Operação concluída', [
                'action' => $action,
                'user_id' => optional(Auth::user())->id,
                'request_id' => request()->headers->get('X-Request-ID'),
            ]);

            return $result;
        } catch (\Throwable $exception) {
            Log::error('[ModelType] Erro durante operação', [
                'action' => $action,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'user_id' => optional(Auth::user())->id,
                'request_id' => request()->headers->get('X-Request-ID'),
            ]);

            throw $exception;
        }
    }

    public function __construct(ModelTypeRepository $modelTypeRepository)
    {
        $this->middleware('auth:api');
        $this->modelTypeRepository = $modelTypeRepository;
    }

    public function index(): JsonResponse
    {
        return $this->withDatabaseHandling('listar tipos de modelo', function () {
            $types = $this->modelTypeRepository->all();

            return $this->sendResponse($types, 'Tipos de modelos recuperados com sucesso');
        });
    }

    public function store(Request $request): JsonResponse
    {
        return $this->withDatabaseHandling('criar tipo de modelo', function () use ($request) {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:models_types,name',
            ]);

            $type = $this->modelTypeRepository->create($validated);

            return $this->sendResponse($type, 'Tipo de modelo criado com sucesso');
        });
    }

    public function update(Request $request, $id): JsonResponse
    {
        return $this->withDatabaseHandling('atualizar tipo de modelo', function () use ($request, $id) {
            $validated = $request->validate([
                'name' => 'required|string|max:255|unique:models_types,name,' . $id,
            ]);

            $type = $this->modelTypeRepository->update($id, $validated);

            return $this->sendResponse($type, 'Tipo de modelo atualizado com sucesso');
        });
    }

    public function destroy($id): JsonResponse
    {
        return $this->withDatabaseHandling('excluir tipo de modelo', function () use ($id) {
            $this->modelTypeRepository->delete($id);

            return $this->sendResponse(null, 'Tipo de modelo excluído com sucesso');
        });
    }
}

