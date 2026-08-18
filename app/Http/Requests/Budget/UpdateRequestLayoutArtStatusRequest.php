<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'accepted_terms_of_use' => Rule::when(
                fn (): bool => $this->input('approval_status') === 'approved',
                ['required', 'accepted'],
                ['nullable', 'boolean'],
            ),
        ];
    }

    public function messages(): array
    {
        return [
            'request_layout_art_id.required' => 'A iteração da arte é obrigatória.',
            'request_layout_art_id.exists' => 'A iteração selecionada não existe.',
            'approval_status.required' => 'O status de aprovação é obrigatório.',
            'approval_status.in' => 'O status deve ser approved ou rejected.',
            'accepted_terms_of_use.required' => 'É necessário aceitar o Termo de aprovação para aprovar a arte.',
            'accepted_terms_of_use.accepted' => 'É necessário aceitar o Termo de aprovação para aprovar a arte.',
        ];
    }
}
