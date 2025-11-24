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
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:155'],
            ]);

            $column = ProductionColumnName::create($validated);

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
     * Lista todas as colunas de produção
     */
    public function index(): JsonResponse
    {
        try {
            $columns = ProductionColumnName::orderBy('id')->get();

            return response()->json([
                'success' => true,
                'data' => $columns,
                'message' => 'Lista de colunas de produção',
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
    public function update(Request $request, ProductionColumnName $productionColumnName): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:155'],
            ]);

            $productionColumnName->update($validated);

            return response()->json([
                'success' => true,
                'data' => $productionColumnName->fresh(),
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
    public function destroy(ProductionColumnName $productionColumnName): JsonResponse
    {
        try {
            $productionColumnName->delete();

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

