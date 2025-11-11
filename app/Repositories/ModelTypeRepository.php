<?php

namespace App\Repositories;

use App\Models\ModelType;

class ModelTypeRepository
{
    public function all()
    {
        return ModelType::orderBy('name')->get(['id', 'name']);
    }

    public function create(array $attributes): ModelType
    {
        return ModelType::create($attributes);
    }

    public function update(int $id, array $request): ModelType
    {
        $modelType = ModelType::findOrFail($id);
        $modelType->update($request);

        return $modelType;
    }

    public function delete(int $id): bool
    {
        $modelType = ModelType::findOrFail($id);

        return $modelType->delete();
    }
}
