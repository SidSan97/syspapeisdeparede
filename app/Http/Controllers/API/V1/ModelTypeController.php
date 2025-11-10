<?php

namespace App\Http\Controllers\API\V1;

use App\Models\ModelType;
use Illuminate\Http\JsonResponse;

class ModelTypeController extends BaseController
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function index(): JsonResponse
    {
        $types = ModelType::orderBy('name')->get(['id', 'name']);

        return $this->sendResponse($types, 'Tipos de modelos recuperados com sucesso');
    }
}

