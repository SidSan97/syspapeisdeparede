<?php

namespace App\Http\Requests\TinyErp;

use Illuminate\Foundation\Http\FormRequest;

class TinyErpSettingsRequest extends FormRequest
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
            'gtin' => ['nullable', 'string', 'max:255'],
            'cep' => ['nullable', 'string', 'max:9', 'regex:/^\d{5}-?\d{3}$/'],
            'ncm' => ['nullable', 'string', 'max:10'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gtin.max' => 'O GTIN não pode ter mais de 255 caracteres.',
            'cep.max' => 'O CEP não pode ter mais de 9 caracteres.',
            'cep.regex' => 'O CEP deve estar no formato 00000-000 ou 00000000.',
            'ncm.max' => 'O NCM não pode ter mais de 10 caracteres.',
        ];
    }
}

