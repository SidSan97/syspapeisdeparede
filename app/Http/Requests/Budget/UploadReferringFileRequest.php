<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UploadReferringFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'image', 'max:10240'], // 10MB max
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Selecione uma imagem.',
            'file.image' => 'O arquivo deve ser uma imagem (JPG, PNG, WEBP).',
            'file.max' => 'O arquivo não pode ser maior que 10MB.',
        ];
    }
}
