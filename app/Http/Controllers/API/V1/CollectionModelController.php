<?php

namespace App\Http\Controllers\API\V1;

use App\Actions\CollectionModel\UpdateCollectionModelAction;
use App\Http\Requests\Api\V1\StoreUpdateCollectionModelRequest;
use App\Http\Resources\CollectionModelResource;
use App\Models\CollectionModel;
use App\Services\CollectionModelFileService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class CollectionModelController extends BaseController
{
    public function __construct(
        protected CollectionModelFileService $service,
    ) {
        $this->middleware('auth:sanctum');
    }

    public function index(): JsonResponse
    {
        $models = CollectionModel::with('files')->latest()->limit(15)->get();

        return CollectionModelResource::collection($models)->response();
    }

    public function store(StoreUpdateCollectionModelRequest $request): JsonResponse
    {
        $collectionModel = CollectionModel::create($request->validated());

        if (! empty($files)) {
            $this->service->addFiles($collectionModel, $files);
        }

        return (new CollectionModelResource($collectionModel))
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }

    public function show(CollectionModel $collectionModel): CollectionModelResource
    {
        return new CollectionModelResource($collectionModel->load('files'));
    }

    public function update(
        StoreUpdateCollectionModelRequest $request,
        CollectionModel $collectionModel,
        UpdateCollectionModelAction $action
    ) {
        $model = $action->execute(
            $collectionModel,
            $request->validated(),
            $request->file('reference_files'),
            $request->input('files_to_delete', [])
        );

        return new CollectionModelResource($model);
    }

    public function destroy(CollectionModel $collectionModel): Response
    {
        $collectionModel->delete();

        return response()->noContent();
    }
}
