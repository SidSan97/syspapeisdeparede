<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\CollectionArtSubcategories\CollectionArtSubcategoryRequest;
use App\Http\Resources\CollectionArtSubcategoryResource;
use App\Models\CollectionArtSubcategory;
use App\Repositories\CollectionArtSubcategoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionArtSubcategoryController extends BaseController
{
    public function __construct(
        protected CollectionArtSubcategoryRepository $repository
    ) {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        $collectionArtId = $request->get('collection_art_id');

        if ($collectionArtId) {
            $subcategories = $this->repository->findByCollectionArt((int) $collectionArtId);
            return $this->sendResponse(
                CollectionArtSubcategoryResource::collection($subcategories),
                'Subcategorias recuperadas com sucesso'
            );
        }

        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        $collection = $this->repository->paginate($perPage);

        return $this->sendResponse(
            [
                'items' => CollectionArtSubcategoryResource::collection($collection),
                'meta' => [
                    'current_page' => $collection->currentPage(),
                    'per_page' => $collection->perPage(),
                    'total' => $collection->total(),
                    'last_page' => $collection->lastPage(),
                ],
            ],
            'Subcategorias recuperadas com sucesso'
        );
    }

    public function store(CollectionArtSubcategoryRequest $request): JsonResponse
    {
        $subcategory = $this->repository->create($request->validated())->load(['collectionArt'])->loadCount('images');

        return $this->sendResponse(
            new CollectionArtSubcategoryResource($subcategory),
            'Subcategoria criada com sucesso'
        );
    }

    public function show(CollectionArtSubcategory $collectionArtSubcategory): JsonResponse
    {
        return $this->sendResponse(
            new CollectionArtSubcategoryResource($collectionArtSubcategory->load(['collectionArt', 'images'])->loadCount('images')),
            'Subcategoria recuperada com sucesso'
        );
    }

    public function update(CollectionArtSubcategoryRequest $request, CollectionArtSubcategory $collectionArtSubcategory): JsonResponse
    {
        $updated = $this->repository->update($collectionArtSubcategory, $request->validated())->load(['collectionArt'])->loadCount('images');

        return $this->sendResponse(
            new CollectionArtSubcategoryResource($updated),
            'Subcategoria atualizada com sucesso'
        );
    }

    public function destroy(CollectionArtSubcategory $collectionArtSubcategory): JsonResponse
    {
        $this->repository->delete($collectionArtSubcategory);

        return $this->sendResponse(null, 'Subcategoria excluída com sucesso');
    }
}

