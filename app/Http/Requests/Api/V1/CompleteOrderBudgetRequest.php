<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CompleteOrderBudgetRequest extends FormRequest
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
            'accepted_terms_of_use' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'accepted_terms_of_use.required' => 'É necessário aceitar o Termo de aprovação para concluir o card.',
            'accepted_terms_of_use.accepted' => 'É necessário aceitar o Termo de aprovação para concluir o card.',
        ];
    }
}
