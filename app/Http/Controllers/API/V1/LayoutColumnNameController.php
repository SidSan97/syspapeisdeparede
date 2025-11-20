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
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            $column = LayoutColumnName::create($validated);

            return response()->json([
                'success' => true,
                'data' => $column,
                'message' => 'Coluna criada com sucesso',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao criar coluna: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Lista todas as colunas de layout
     */
    public function index(): JsonResponse
    {
        try {
            $columns = LayoutColumnName::orderBy('id')->get();

            return response()->json([
                'success' => true,
                'data' => $columns,
                'message' => 'Lista de colunas de layout',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao listar colunas: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Atualiza o nome de uma coluna
     */
    public function update(Request $request, LayoutColumnName $layoutColumnName): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
            ]);

            $layoutColumnName->update($validated);

            return response()->json([
                'success' => true,
                'data' => $layoutColumnName->fresh(),
                'message' => 'Coluna atualizada com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao atualizar coluna: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove uma coluna permanentemente do banco de dados
     */
    public function destroy(LayoutColumnName $layoutColumnName): JsonResponse
    {
        try {
            $layoutColumnName->delete();

            return response()->json([
                'success' => true,
                'message' => 'Coluna excluída com sucesso',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao excluir coluna: ' . $e->getMessage(),
            ], 500);
        }
    }
}

