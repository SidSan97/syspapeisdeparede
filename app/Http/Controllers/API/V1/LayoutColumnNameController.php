<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\LayoutColumnName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LayoutColumnNameController extends Controller
{
    /**
     * Cria uma nova coluna de layout
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $column = LayoutColumnName::create($validated);

        return response()->json($column, 201);
    }

    /**
     * Lista todas as colunas de layout
     */
    public function index(): JsonResponse
    {
        $columns = LayoutColumnName::orderBy('id')->get();

        return response()->json($columns);
    }

    /**
     * Atualiza o nome de uma coluna
     */
    public function update(Request $request, LayoutColumnName $layoutColumnName): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $layoutColumnName->update($validated);

        return response()->json($layoutColumnName->fresh());
    }

    /**
     * Remove uma coluna permanentemente do banco de dados
     */
    public function destroy(LayoutColumnName $layoutColumnName): JsonResponse
    {
        $layoutColumnName->delete();

        return response()->noContent();
    }
}

