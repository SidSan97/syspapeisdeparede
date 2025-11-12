<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\CollectionModels\CollectionModelRequest;
use App\Http\Resources\CollectionModelResource;
use App\Models\CollectionModel;
use App\Repositories\CollectionModelRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionModelController extends BaseController
{
    public function __construct(
        protected CollectionModelRepository $repository
    ) {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        $models = $this->repository->paginate($perPage);

        return $this->sendResponse(
            [
                'items' => CollectionModelResource::collection($models),
                'meta' => [
                    'current_page' => $models->currentPage(),
                    'per_page' => $models->perPage(),
                    'total' => $models->total(),
                    'last_page' => $models->lastPage(),
                ],
            ],
            'Modelos da coleção recuperados com sucesso'
        );
    }

    public function store(CollectionModelRequest $request): JsonResponse
    {
        $payload = $this->extractPayload($request);

        $model = $this->repository->create($payload);

        if ($request->hasFile('reference_files')) {
            $this->repository->addFiles($model, $request->file('reference_files'));
            $model->load('files');
        }

        return $this->sendResponse(
            new CollectionModelResource($model),
            'Modelo criado com sucesso'
        );
    }

    public function show(CollectionModel $collectionModel): JsonResponse
    {
        return $this->sendResponse(
            new CollectionModelResource($collectionModel->load('files')),
            'Modelo recuperado com sucesso'
        );
    }

    public function update(CollectionModelRequest $request, CollectionModel $collectionModel): JsonResponse
    {
        $payload = $this->extractPayload($request);

        if ($payload['request_file'] === false) {
            $this->repository->removeAllFiles($collectionModel);
        } else {
            $filesToDelete = $request->input('files_to_delete', []);
            if (!empty($filesToDelete)) {
                $this->repository->removeFiles($collectionModel, $filesToDelete);
            }

            if ($request->hasFile('reference_files')) {
                $this->repository->addFiles($collectionModel, $request->file('reference_files'));
            }
        }

        $model = $this->repository->update($collectionModel, $payload)->refresh()->load('files');

        return $this->sendResponse(
            new CollectionModelResource($model),
            'Modelo atualizado com sucesso'
        );
    }

    public function destroy(CollectionModel $collectionModel): JsonResponse
    {
        $this->repository->delete($collectionModel);

        return $this->sendResponse(null, 'Modelo excluído com sucesso');
    }

    protected function extractPayload(CollectionModelRequest $request): array
    {
        $requests = $request->input('requests', []);

        $shouldRequestLink = (bool) ($requests['link'] ?? false);
        $shouldRequestComment = (bool) ($requests['comment'] ?? false);
        $shouldRequestFile = (bool) ($requests['file'] ?? false);

        return [
            'name' => (string) $request->input('name'),
            'value' => (float) $request->input('value'),
            'deadline' => (int) $request->input('deadline'),
            'request_link' => $shouldRequestLink,
            'request_comment' => $shouldRequestComment,
            'request_file' => $shouldRequestFile,
        ];
    }
}

