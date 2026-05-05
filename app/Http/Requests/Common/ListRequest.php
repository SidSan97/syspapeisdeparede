<?php

namespace App\Http\Requests\Common;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListRequest extends FormRequest
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
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Sanitizar o campo de busca removendo caracteres perigosos
        if ($this->has('search')) {
            $this->merge([
                'search' => strip_tags($this->input('search')),
            ]);
        }

        // Garantir que per_page seja um inteiro válido
        if ($this->has('per_page')) {
            $perPage = (int) $this->input('per_page');
            if ($perPage < 1 || $perPage > 100) {
                $perPage = 15;
            }
            $this->merge(['per_page' => $perPage]);
        }
    }
}
