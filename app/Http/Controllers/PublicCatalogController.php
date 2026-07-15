<?php

namespace App\Http\Controllers;

use App\Http\Resources\PublicCatalogItemResource;
use App\Models\CollectionImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicCatalogController extends Controller
{
    public function __invoke(): View
    {
        $scriptVariables = [
            'appName' => config('app.name'),
            'user' => null,
            'appUrl' => config('app.url'),
            'baseUrl' => url(''),
            'assetUrl' => asset(''),
        ];

        return view('application', compact('scriptVariables'));
    }

    public function index(): View
    {
        return view('catalog-public', [
            'appName' => config('app.name'),
            'apiBaseUrl' => url('/api'),
        ]);
    }

    public function items(Request $request): JsonResponse
    {
        $categoryId = $request->integer('categoria') ?: null;
        $query = trim((string) $request->get('q', ''));

        $images = CollectionImage::with(['category.parent'])
            ->when($categoryId, fn($q) => $q->where('collection_category_id', $categoryId))
            ->when($query, fn($q) => $q->where(function ($sq) use ($query) {
                $sq->where('name', 'like', "%{$query}%")
                    ->orWhere('path_name', 'like', "%{$query}%");
            }))
            ->paginate(24);

        return response()->json([
            'data' => PublicCatalogItemResource::collection($images),
            'meta' => [
                'current_page' => $images->currentPage(),
                'per_page' => $images->perPage(),
                'total' => $images->total(),
                'last_page' => $images->lastPage(),
            ],
        ]);
    }
}
