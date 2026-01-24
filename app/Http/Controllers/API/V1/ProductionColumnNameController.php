<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\ProductionColumnName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductionColumnNameController extends Controller
{
    /**
     * Cria uma nova coluna de produção
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:155'],
        ]);

        $column = ProductionColumnName::create($validated);

        return response()->json($column, 201);
    }

    /**
     * Lista todas as colunas de produção
     */
    public function index(): JsonResponse
    {
        $columns = ProductionColumnName::orderBy('id')->get();

        return response()->json($columns);
    }

    /**
     * Atualiza o nome de uma coluna
     */
    public function update(Request $request, ProductionColumnName $productionColumnName): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:155'],
        ]);

        $productionColumnName->update($validated);

        return response()->json($productionColumnName->fresh());
    }

    /**
     * Remove uma coluna permanentemente do banco de dados
     */
    public function destroy(ProductionColumnName $productionColumnName): JsonResponse
    {
        $productionColumnName->delete();

        return response()->noContent();
    }
}

