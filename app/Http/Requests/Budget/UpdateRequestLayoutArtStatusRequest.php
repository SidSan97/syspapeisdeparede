<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequestLayoutArtStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'request_layout_art_id' => ['required', 'integer', 'exists:request_layouts_art,id'],
            'approval_status' => ['required', 'string', 'in:approved,rejected'],
        ];
    }

    public function messages(): array
    {
        return [
            'request_layout_art_id.required' => 'A iteração da arte é obrigatória.',
            'request_layout_art_id.exists' => 'A iteração selecionada não existe.',
            'approval_status.required' => 'O status de aprovação é obrigatório.',
            'approval_status.in' => 'O status deve ser approved ou rejected.',
        ];
    }
}
