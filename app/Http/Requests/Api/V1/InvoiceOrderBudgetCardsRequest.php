<?php

namespace App\Http\Requests\Api\V1;

use App\Models\OrderBudget;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InvoiceOrderBudgetCardsRequest extends FormRequest
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
            'packer_name' => ['required', 'string', 'max:255'],
            'quantidade_volumes' => ['required', 'integer', 'min:1', 'max:9999'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_budget_ids.required' => 'Selecione ao menos um card para faturar.',
            'order_budget_ids.min' => 'Selecione ao menos um card para faturar.',
            'order_budget_ids.*.exists' => 'Um ou mais cards selecionados não foram encontrados.',
            'packer_name.required' => 'Informe o nome do embalador.',
            'quantidade_volumes.required' => 'Informe a quantidade de volumes.',
            'quantidade_volumes.min' => 'A quantidade de volumes deve ser no mínimo 1.',
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $ids = $this->input('order_budget_ids', []);
                if (! is_array($ids) || $ids === []) {
                    return;
                }

                $orderIds = OrderBudget::query()
                    ->whereIn('id', $ids)
                    ->pluck('order_id')
                    ->unique()
                    ->all();

                $pendingLabel = OrderBudget::query()
                    ->whereIn('order_id', $orderIds)
                    ->where('picking_label_generated', '!=', 1)
                    ->exists();

                if ($pendingLabel) {
                    $validator->errors()->add(
                        'order_budget_ids',
                        'Só é possível faturar quando todos os cards do pedido tiverem etiqueta gerada.'
                    );
                }
            },
        ];
    }
}
