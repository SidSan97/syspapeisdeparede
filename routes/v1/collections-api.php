<?php

use App\Http\Controllers\API\V1\CollectionArtController;
use App\Http\Controllers\API\V1\CollectionArtSubcategoryController;
use App\Http\Controllers\API\V1\CollectionImageController;
use App\Http\Controllers\API\V1\CollectionModelController;
use App\Http\Controllers\API\V1\MyFavoriteCollectionImageController;
use Illuminate\Support\Facades\Route;

Route::apiResources([
    'collection-models' => CollectionModelController::class,
    'collection-arts' => CollectionArtController::class,
    'collection-art-subcategories' => CollectionArtSubcategoryController::class,
    'collection-images' => CollectionImageController::class,
    'collection-categories' => \App\Http\Controllers\API\V1\CollectionCategoryController::class,
]);

// Rotas adicionais para collection-categories
Route::get('collection-categories/children/{parentId?}', [\App\Http\Controllers\API\V1\CollectionCategoryController::class, 'children']);

// My Favorite Collection Images
Route::get('my-favorite-collection-images', [MyFavoriteCollectionImageController::class, 'index']);
Route::post('collection-images/{collectionImage}/toggle-favorite', [MyFavoriteCollectionImageController::class, 'toggle']);
Route::get('collection-images/{collectionImage}/check-favorite', [MyFavoriteCollectionImageController::class, 'check']);
