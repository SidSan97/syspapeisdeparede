<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

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
                'required_without:budget_id',
            ],
            'budget_id' => [
                'nullable',
                'integer',
                'exists:budgets,id',
                'required_without:order_id',
            ],
            'dealer_id' => [
                'nullable',
                'integer',
                'exists:users,id',
            ],
            'card_id' => [
                'nullable',
                'integer',
                'exists:order_budgets,id',
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

        foreach (['order_id', 'budget_id', 'dealer_id', 'card_id'] as $field) {
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
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'order_id.integer' => 'O ID do pedido deve ser um número inteiro.',
            'order_id.exists' => 'O pedido selecionado não existe.',
            'order_id.required_without' => 'É necessário fornecer order_id ou budget_id.',
            'budget_id.integer' => 'O ID do orçamento deve ser um número inteiro.',
            'budget_id.exists' => 'O orçamento selecionado não existe.',
            'budget_id.required_without' => 'É necessário fornecer order_id ou budget_id.',
            'dealer_id.integer' => 'O ID do revendedor deve ser um número inteiro.',
            'dealer_id.exists' => 'O revendedor selecionado não existe.',
            'card_id.integer' => 'O ID do card deve ser um número inteiro.',
            'card_id.exists' => 'O card selecionado não existe.',
        ];
    }
}

