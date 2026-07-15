<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Resources\CollectionImageResource;
use App\Models\CollectionImage;
use App\Models\MyFavoriteCollectionImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class MyFavoriteCollectionImageController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Toggle favorite status for a collection image.
     * If the image is already favorited, it will be removed from favorites.
     * If not, it will be added to favorites.
     */
    public function toggle(CollectionImage $collectionImage): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->sendError('Usuário não autenticado.', [], 401);
            }

            $existingFavorite = MyFavoriteCollectionImage::where('user_id', $user->id)
                ->where('collection_image_id', $collectionImage->id)
                ->first();

            if ($existingFavorite) {
                // Remove from favorites
                $existingFavorite->delete();
                $isFavorited = false;
                $message = 'Imagem removida dos favoritos.';
            } else {
                // Add to favorites
                MyFavoriteCollectionImage::create([
                    'user_id' => $user->id,
                    'collection_image_id' => $collectionImage->id,
                ]);
                $isFavorited = true;
                $message = 'Imagem adicionada aos favoritos.';
            }

            Log::info('[MyFavoriteCollectionImage] Toggle request succeeded', [
                'user_id' => $user->id,
                'collection_image_id' => $collectionImage->id,
                'is_favorited' => $isFavorited,
            ]);

            return $this->sendResponse([
                'is_favorited' => $isFavorited,
            ], $message);
        } catch (\Throwable $exception) {
            Log::error('[MyFavoriteCollectionImage] Toggle request failed', [
                'user_id' => optional(Auth::user())->id,
                'collection_image_id' => $collectionImage->id ?? null,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError(
                'Não foi possível atualizar o favorito. Tente novamente.',
                [],
                500
            );
        }
    }

    /**
     * Check if a collection image is favorited by the authenticated user.
     */
    public function check(CollectionImage $collectionImage): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->sendError('Usuário não autenticado.', [], 401);
            }

            $isFavorited = MyFavoriteCollectionImage::where('user_id', $user->id)
                ->where('collection_image_id', $collectionImage->id)
                ->exists();

            return $this->sendResponse([
                'is_favorited' => $isFavorited,
            ], 'Status do favorito recuperado com sucesso');
        } catch (\Throwable $exception) {
            Log::error('[MyFavoriteCollectionImage] Check request failed', [
                'user_id' => optional(Auth::user())->id,
                'collection_image_id' => $collectionImage->id ?? null,
                'error' => $exception->getMessage(),
            ]);

            return $this->sendError(
                'Não foi possível verificar o favorito. Tente novamente.',
                [],
                500
            );
        }
    }

    /**
     * Get all favorite collection images for the authenticated user.
     */
    public function index(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return $this->sendError('Usuário não autenticado.', [], 401);
            }

            $favoriteImages = $user->favoriteCollectionImages()
                ->with(['category.parent'])
                ->get();

            Log::info('[MyFavoriteCollectionImage] Index request succeeded', [
                'user_id' => $user->id,
                'total_favorites' => $favoriteImages->count(),
            ]);

            return $this->sendResponse(
                CollectionImageResource::collection($favoriteImages),
                'Favoritos recuperados com sucesso'
            );
        } catch (\Throwable $exception) {
            Log::error('[MyFavoriteCollectionImage] Index request failed', [
                'user_id' => optional(Auth::user())->id,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return $this->sendError(
                'Não foi possível carregar os favoritos. Tente novamente.',
                [],
                500
            );
        }
    }
}

