<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\CollectionCategories\CollectionCategoryRequest;
use App\Http\Resources\CollectionCategoryResource;
use App\Http\Resources\CollectionImageResource;
use App\Models\CollectionCategory;
use App\Repositories\CollectionCategoryRepository;
use App\Repositories\CollectionImageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CollectionCategoryController extends BaseController
{
    public function __construct(
        protected CollectionCategoryRepository $repository,
        protected CollectionImageRepository $collectionImageRepository,
    ) {
        // Deixar listagem e visualização públicas para uso externo (catálogo),
        // mantendo autenticação para operações de escrita.
        $this->middleware('auth:sanctum')->except(['index', 'show', 'children', 'categoryImages']);
    }

    public function index(Request $request): JsonResponse
    {
        $query = trim((string) $request->get('q', ''));
        $tree = $request->get('tree', false);

        if ($query !== '') {
            $collection = $this->repository->search($query);

            return $this->sendResponse(
                CollectionCategoryResource::collection($collection),
                'Categorias encontradas'
            );
        }

        if ($tree) {
            $collection = $this->repository->getTree();

            return $this->sendResponse(
                CollectionCategoryResource::collection($collection),
                'Árvore de categorias recuperada com sucesso'
            );
        }

        $collection = $this->repository->paginate();

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
                'images',
            ])),
            'Categoria recuperada com sucesso'
        );
    }

    /**
     * Lista paginada de imagens da categoria (mesma ordem do resource: filhos em sequência, depois imagens diretas).
     */
    public function categoryImages(Request $request, CollectionCategory $collectionCategory): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 6), 1), 50);
        $page = max((int) $request->integer('page', 1), 1);

        $paginator = $this->collectionImageRepository->paginateForCategory(
            $collectionCategory,
            $perPage,
            $page,
        );

        return $this->sendResponse([
            'items' => CollectionImageResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ], 'Imagens da categoria recuperadas com sucesso');
    }

    public function update(CollectionCategoryRequest $request, CollectionCategory $collectionCategory): JsonResponse
    {
        $updated = $this->repository->update($collectionCategory, $request->validated())->load([
            'children' => function ($query) {
                $query->withCount('images');
            },
            'parent',
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
