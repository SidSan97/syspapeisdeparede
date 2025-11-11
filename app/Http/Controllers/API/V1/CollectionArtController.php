<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\CollectionArts\CollectionArtRequest;
use App\Http\Resources\CollectionArtResource;
use App\Models\CollectionArt;
use App\Repositories\CollectionArtRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionArtController extends BaseController
{
    public function __construct(
        protected CollectionArtRepository $repository
    ) {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        $collection = $this->repository->paginate($perPage);

        return $this->sendResponse(
            [
                'items' => CollectionArtResource::collection($collection),
                'meta' => [
                    'current_page' => $collection->currentPage(),
                    'per_page' => $collection->perPage(),
                    'total' => $collection->total(),
                    'last_page' => $collection->lastPage(),
                ],
            ],
            'Tipos de arte recuperados com sucesso'
        );
    }

    public function store(CollectionArtRequest $request): JsonResponse
    {
        $collectionArt = $this->repository->create($request->validated())->loadCount('images');

        return $this->sendResponse(
            new CollectionArtResource($collectionArt),
            'Tipo de arte criado com sucesso'
        );
    }

    public function show(CollectionArt $collectionArt): JsonResponse
    {
        return $this->sendResponse(
            new CollectionArtResource($collectionArt->load(['images'])->loadCount('images')),
            'Tipo de arte recuperado com sucesso'
        );
    }

    public function update(CollectionArtRequest $request, CollectionArt $collectionArt): JsonResponse
    {
        $updated = $this->repository->update($collectionArt, $request->validated())->loadCount('images');

        return $this->sendResponse(
            new CollectionArtResource($updated),
            'Tipo de arte atualizado com sucesso'
        );
    }

    public function destroy(CollectionArt $collectionArt): JsonResponse
    {
        $this->repository->delete($collectionArt);

        return $this->sendResponse(null, 'Tipo de arte excluído com sucesso');
    }
}

