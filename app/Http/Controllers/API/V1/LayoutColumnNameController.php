<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Controllers\Controller;
use App\Models\LayoutColumnName;
use App\Models\OrderBudget;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class LayoutColumnNameController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $column = LayoutColumnName::create($validated);

        return response()->json($column, 201);
    }

    public function index(): JsonResponse
    {
        // FIXME: Usar resource collection.
        $columns = LayoutColumnName::orderBy('order')->orderBy('id')->get();

        return response()->json([
            'data' => $columns,
        ]);
    }

    public function reorder(Request $request): Response
    {
        $validated = $request->validate([
            'columns' => ['required', 'array'],
            'columns.*.id' => ['required', 'integer', 'exists:layout_column_names,id'],
            'columns.*.order' => ['required', 'integer', 'min:0'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['columns'] as $data) {
                LayoutColumnName::where('id', $data['id'])
                    ->update(['order' => $data['order']]);
            }
        });

        return response()->noContent();
    }

    public function update(Request $request, LayoutColumnName $layoutColumnName): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $layoutColumnName->update($validated);

        return response()->json($layoutColumnName->fresh());
    }

    public function destroy(Request $request, LayoutColumnName $column): Response
    {
        $request->validate([
            'target_column_id' => [
                'required',
                'exists:layout_column_names,id',
                function ($attribute, $value, $fail) use ($column) {
                    if ($value == $column->id) {
                        $fail('The target column cannot be the same as the column being deleted.');
                    }
                },
            ],
        ]);

        DB::transaction(function () use ($column, $request) {

            OrderBudget::where('layout_column_names_id', $column->id)->update([
                'layout_column_names_id' => $request->target_column_id,
            ]);

            $column->delete();
        });

        return response()->noContent();
    }
}
