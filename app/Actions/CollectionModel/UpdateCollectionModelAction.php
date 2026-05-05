<?php

namespace App\Actions\CollectionModel;

use App\Models\CollectionModel;
use App\Services\CollectionModelFileService;
use Illuminate\Support\Facades\DB;

class UpdateCollectionModelAction
{
    public function __construct(
        protected CollectionModelFileService $service,
    ) {}

    public function execute(
        CollectionModel $model,
        array $data,
        ?array $newFiles = null,
        array $filesToDelete = []
    ): CollectionModel {
        return DB::transaction(function () use ($model, $data, $newFiles, $filesToDelete) {

            $model->update($data);

            if (($data['request_file'] ?? true) === false) {
                $this->service->deleteFiles($model);

                return $model;
            }

            if ($filesToDelete) {
                $this->service->deleteFiles($model, $filesToDelete);
            }

            if ($newFiles) {
                $this->service->addFiles($model, $newFiles);
            }

            return $model->fresh('files');
        });
    }
}
