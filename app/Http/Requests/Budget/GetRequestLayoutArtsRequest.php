<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetRequestLayoutArtsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_id' => [
                'nullable',
                'integer',
                'exists:orders,id',
            ],
            'budget_id' => [
                'nullable',
                'integer',
                'exists:budgets,id',
            ],
            'order_budget_id' => [
                'nullable',
                'integer',
                'exists:order_budgets,id',
            ],
            'dealer_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation()
    {
        // Remover valores vazios, null ou strings vazias dos inputs
        $inputs = $this->all();
        
        foreach (['order_id', 'budget_id', 'order_budget_id', 'dealer_id'] as $field) {
            if (isset($inputs[$field]) && ($inputs[$field] === '' || $inputs[$field] === null || $inputs[$field] === 'null')) {
                unset($inputs[$field]);
            } elseif (isset($inputs[$field])) {
                // Converter para inteiro se for uma string numérica
                $inputs[$field] = is_numeric($inputs[$field]) ? (int) $inputs[$field] : $inputs[$field];
            }
        }
        
        $this->merge($inputs);
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $orderId = $this->input('order_id');
            $budgetId = $this->input('budget_id');
            $orderBudgetId = $this->input('order_budget_id');

            // Garantir que pelo menos um dos campos obrigatórios seja fornecido
            if (empty($orderId) && empty($budgetId) && empty($orderBudgetId)) {
                $validator->errors()->add(
                    'order_id',
                    'É necessário fornecer pelo menos um dos seguintes campos: order_id, budget_id ou order_budget_id.'
                );
            }
        });
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_id.integer' => 'O ID do pedido deve ser um número inteiro.',
            'order_id.exists' => 'O pedido selecionado não existe.',
            'order_id.required_without_all' => 'É necessário fornecer pelo menos um dos seguintes campos: order_id, budget_id ou order_budget_id.',
            'budget_id.integer' => 'O ID do orçamento deve ser um número inteiro.',
            'budget_id.exists' => 'O orçamento selecionado não existe.',
            'budget_id.required_without_all' => 'É necessário fornecer pelo menos um dos seguintes campos: order_id, budget_id ou order_budget_id.',
            'order_budget_id.integer' => 'O ID do orçamento do pedido deve ser um número inteiro.',
            'order_budget_id.exists' => 'O orçamento do pedido selecionado não existe.',
            'order_budget_id.required_without_all' => 'É necessário fornecer pelo menos um dos seguintes campos: order_id, budget_id ou order_budget_id.',
            'dealer_id.integer' => 'O ID do revendedor deve ser um número inteiro.',
            'dealer_id.exists' => 'O revendedor selecionado não existe.',
        ];
    }
}

