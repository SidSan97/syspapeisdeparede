<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\CollectionCategories\CollectionCategoryRequest;
use App\Http\Resources\CollectionCategoryResource;
use App\Models\CollectionCategory;
use App\Repositories\CollectionCategoryRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionCategoryController extends BaseController
{
    public function __construct(
        protected CollectionCategoryRepository $repository
    ) {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->get('per_page', 15);
        $perPage = $perPage > 0 ? $perPage : 15;

        $tree = $request->get('tree', false);

        if ($tree) {
            $collection = $this->repository->getTree();
            return $this->sendResponse(
                CollectionCategoryResource::collection($collection),
                'Árvore de categorias recuperada com sucesso'
            );
        }

        $collection = $this->repository->paginate($perPage);

        return $this->sendResponse(
            [
                'items' => CollectionCategoryResource::collection($collection),
                'meta' => [
                    'current_page' => $collection->currentPage(),
                    'per_page' => $collection->perPage(),
                    'total' => $collection->total(),
                    'last_page' => $collection->lastPage(),
                ],
            ],
            'Categorias recuperadas com sucesso'
        );
    }

    public function store(CollectionCategoryRequest $request): JsonResponse
    {
        $category = $this->repository->create($request->validated())->load(['children' => function ($query) {
            $query->withCount('images');
        }, 'parent']);

        return $this->sendResponse(
            new CollectionCategoryResource($category),
            'Categoria criada com sucesso'
        );
    }

    public function show(CollectionCategory $collectionCategory): JsonResponse
    {
        return $this->sendResponse(
            new CollectionCategoryResource($collectionCategory->load([
                'children' => function ($query) {
                    $query->with('images')->withCount('images');
                },
                'parent',
                'images'
            ])),
            'Categoria recuperada com sucesso'
        );
    }

    public function update(CollectionCategoryRequest $request, CollectionCategory $collectionCategory): JsonResponse
    {
        $updated = $this->repository->update($collectionCategory, $request->validated())->load([
            'children' => function ($query) {
                $query->withCount('images');
            },
            'parent'
        ]);

        return $this->sendResponse(
            new CollectionCategoryResource($updated),
            'Categoria atualizada com sucesso'
        );
    }

    public function destroy(CollectionCategory $collectionCategory): JsonResponse
    {
        $this->repository->delete($collectionCategory);

        return $this->sendResponse(null, 'Categoria excluída com sucesso');
    }

    public function children(Request $request, ?int $parentId = null): JsonResponse
    {
        $parentId = $parentId ?? $request->get('parent_id');
        $categories = $this->repository->findByParent($parentId);

        return $this->sendResponse(
            CollectionCategoryResource::collection($categories),
            'Subcategorias recuperadas com sucesso'
        );
    }
}

