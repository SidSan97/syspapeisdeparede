<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAvatarRequest extends FormRequest
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
            'image' => [
                'required',
                'string',
                function ($attribute, $value, $fail) {
                    if (! preg_match('/^data:image\/(\w+);base64,/', $value)) {
                        $fail('Formato de imagem inválido.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'image.required' => 'O campo de imagem é obrigatório.',
        ];
    }
}
