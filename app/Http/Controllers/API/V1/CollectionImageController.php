<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\CollectionImages\StoreCollectionImageRequest;
use App\Http\Resources\CollectionImageResource;
use App\Models\CollectionArt;
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
        $this->middleware('auth:api');
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
                ->map(fn (CollectionArt $art) => [
                    'id' => $art->id,
                    'name' => $art->name,
                    'images' => CollectionImageResource::collection($art->images),
                ]);

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
            'collection_arts_id' => $request->input('collection_arts_id'),
            'images_count' => count($request->file('images', [])),
        ]);

        try {
            $collectionArt = CollectionArt::findOrFail($request->input('collection_arts_id'));

            $this->repository->storeMany($collectionArt, $request->file('images', []));
            $collectionArt->load('images');

            Log::info('[CollectionImage] Store request succeeded', [
                'user_id' => optional(Auth::user())->id,
                'collection_id' => $collectionArt->id,
                'total_images' => $collectionArt->images->count(),
            ]);

            return $this->sendResponse(
                [
                    [
                        'id' => $collectionArt->id,
                        'name' => $collectionArt->name,
                        'images' => CollectionImageResource::collection($collectionArt->images),
                    ],
                ],
                'Imagens adicionadas com sucesso'
            );
        } catch (\Throwable $exception) {
            Log::error('[CollectionImage] Store request failed', [
                'user_id' => optional(Auth::user())->id,
                'collection_arts_id' => $request->input('collection_arts_id'),
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

