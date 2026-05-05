<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBudgetRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                'string',
                // FIXME: Usar enum.
                Rule::in([
                    'all',
                    'em aberto',
                    'Em aberto',
                    'aprovado',
                    'cancelado',
                    'Em produção',
                    'Enviado',
                ]),
            ],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'user_id' => ['nullable', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // if ($this->has('status')) {
        //     $this->merge([
        //         'status' => strtolower($this->input('status')),
        //     ]);
        // }
    }
}
