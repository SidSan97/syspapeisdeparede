<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\OrderBudget;
use App\Models\ProductionColumnName;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class ProductionColumnNameController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:155'],
        ]);

        $column = ProductionColumnName::create($validated);

        return response()->json($column, 201);
    }

    public function index(): JsonResponse
    {
        // FIXME: Usar resource collection.
        $columns = ProductionColumnName::orderBy('id')->get();

        return response()->json([
            'data' => $columns
        ]);
    }

    public function update(Request $request, ProductionColumnName $productionColumnName): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:155'],
        ]);

        $productionColumnName->update($validated);

        return response()->json($productionColumnName->fresh());
    }

    public function destroy(Request $request, ProductionColumnName $column): Response
    {
        $request->validate([
            'target_column_id' => [
                'required',
                'exists:production_column_names,id',
                function ($attribute, $value, $fail) use ($column) {
                    if ($value == $column->id) {
                        $fail('The target column cannot be the same as the column being deleted.');
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($column, $request) {

            OrderBudget::where('layout_column_names_id', $column->id)->update([
                'production_column_names_id' => $request->target_column_id,
            ]);

            $column->delete();
        });

        return response()->noContent();
    }
}
