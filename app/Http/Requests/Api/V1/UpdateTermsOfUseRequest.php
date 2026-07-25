<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTermsOfUseRequest extends FormRequest
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
            'terms' => ['required', 'array', 'min:1'],
            'terms.*.id' => ['nullable', 'string', 'max:100'],
            'terms.*.title' => ['required', 'string', 'max:255'],
            'terms.*.body' => ['nullable', 'string', 'max:50000'],
        ];
    }
}
