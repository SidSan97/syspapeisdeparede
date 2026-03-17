<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\CollectionImages\StoreCollectionImageRequest;
use App\Http\Resources\CollectionImageResource;
use App\Models\CollectionCategory;
use App\Models\CollectionImage;
use App\Repositories\CollectionImageRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CollectionImageController extends BaseController
{
    public function __construct(
        protected CollectionImageRepository $repository
    ) {
        // Deixar listagem pública para uso externo (catálogo),
        // mantendo autenticação para criação/remoção.
        $this->middleware('auth:api')->except(['index']);
    }

    public function index(): JsonResponse
    {
        Log::info('[CollectionImage] List request received', [
            'user_id' => optional(Auth::user())->id,
            'request_id' => request()->headers->get('X-Request-ID'),
        ]);

        try {
            $collection = $this->repository
                ->listGroupedByCollection()
                ->map(function (CollectionCategory $category) {
                    $allImages = collect();

                    // Imagens diretas da categoria
                    if ($category->images) {
                        $allImages = $allImages->merge($category->images);
                    }

                    // Imagens dos filhos
                    if ($category->children) {
                        foreach ($category->children as $child) {
                            if ($child->images) {
                                $allImages = $allImages->merge($child->images);
                            }
                        }
                    }

                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'images' => CollectionImageResource::collection($allImages),
                    ];
                });

            Log::info('[CollectionImage] List request succeeded', [
                'user_id' => optional(Auth::user())->id,
                'collections' => $collection->count(),
            ]);

            return $this->sendResponse($collection, 'Catálogo de imagens recuperado com sucesso');
        } catch (\Throwable $exception) {
            Log::error('[CollectionImage] List request failed', [
                'user_id' => optional(Auth::user())->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError(
                'Não foi possível carregar o catálogo de imagens.',
                [],
                500
            );
        }
    }

    public function store(StoreCollectionImageRequest $request): JsonResponse
    {
        Log::info('[CollectionImage] Store request received', [
            'user_id' => optional(Auth::user())->id,
            'collection_category_id' => $request->input('collection_category_id'),
            'collection_category_ids' => $request->input('collection_category_ids', []),
            'images_count' => count($request->file('images', [])),
        ]);

        try {
            $files = $request->file('images', []);
            $names = $request->input('names', []);

            $singleCategoryId = $request->input('collection_category_id');
            $multipleCategoryIds = $request->input('collection_category_ids', []);

            if ($singleCategoryId && empty($multipleCategoryIds)) {
                $category = CollectionCategory::findOrFail($singleCategoryId);

                $this->repository->storeMany($category, $files, $names);
                $category->load(['images', 'parent']);

                $allImages = $category->images;

                Log::info('[CollectionImage] Store request succeeded (single)', [
                    'user_id' => optional(Auth::user())->id,
                    'category_id' => $category->id,
                    'parent_id' => $category->parent_id,
                    'total_images' => $allImages->count(),
                ]);

                $rootCategory = $category->getRoot();

                return $this->sendResponse(
                    [
                        [
                            'id' => $rootCategory->id,
                            'name' => $rootCategory->name,
                            'images' => CollectionImageResource::collection($allImages),
                        ],
                    ],
                    'Imagens adicionadas com sucesso'
                );
            }

            if (!empty($multipleCategoryIds)) {
                $categories = CollectionCategory::query()
                    ->whereIn('id', $multipleCategoryIds)
                    ->get();

                $storedImages = $this->repository->storeManyForCategories($categories, $files, $names);

                Log::info('[CollectionImage] Store request succeeded (multiple)', [
                    'user_id' => optional(Auth::user())->id,
                    'category_ids' => $multipleCategoryIds,
                    'stored_images' => $storedImages->count(),
                ]);

                return $this->sendResponse(
                    CollectionImageResource::collection($storedImages),
                    'Imagens adicionadas com sucesso'
                );
            }

            return $this->sendError(
                'Nenhuma categoria válida informada para o envio das imagens.',
                [],
                422
            );
        } catch (\Throwable $exception) {
            Log::error('[CollectionImage] Store request failed', [
                'user_id' => optional(Auth::user())->id,
                'collection_category_id' => $request->input('collection_category_id'),
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError(
                'Não foi possível adicionar as imagens. Tente novamente.',
                [],
                500
            );
        }
    }

    public function destroy(CollectionImage $collectionImage): JsonResponse
    {
        Log::info('[CollectionImage] Destroy request received', [
            'user_id' => optional(Auth::user())->id,
            'collection_image_id' => $collectionImage->id,
        ]);

        try {
            $this->repository->delete($collectionImage);

            Log::info('[CollectionImage] Destroy request succeeded', [
                'user_id' => optional(Auth::user())->id,
                'collection_image_id' => $collectionImage->id,
            ]);

            return $this->sendResponse(null, 'Imagem removida com sucesso');
        } catch (\Throwable $exception) {
            Log::error('[CollectionImage] Destroy request failed', [
                'user_id' => optional(Auth::user())->id,
                'collection_image_id' => $collectionImage->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError(
                'Não foi possível remover a imagem. Tente novamente.',
                [],
                500
            );
        }
    }
}

