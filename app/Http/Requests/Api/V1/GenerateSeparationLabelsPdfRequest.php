<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class GenerateSeparationLabelsPdfRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_budget_ids' => ['required', 'array', 'min:1'],
            'order_budget_ids.*' => ['required', 'integer', 'distinct', 'exists:order_budgets,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_budget_ids.required' => 'Selecione ao menos um item para imprimir.',
            'order_budget_ids.min' => 'Selecione ao menos um item para imprimir.',
            'order_budget_ids.*.exists' => 'Um ou mais itens selecionados não foram encontrados.',
        ];
    }
}
