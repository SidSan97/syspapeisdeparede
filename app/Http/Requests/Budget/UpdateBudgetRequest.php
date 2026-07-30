<?php

namespace App\Http\Requests\Budget;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'string', Rule::in([
                'em aberto',
                'Em aberto',
                'aprovado',
                'Aprovado',
                'cancelado',
                'Cancelado',
                'Em produção',
                'Enviado',
            ])],
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.name' => ['nullable', 'string', 'max:255'],
            'rooms.*.walls' => ['required', 'array', 'min:1'],
            'rooms.*.walls.*.name' => ['nullable', 'string', 'max:255'],
            'rooms.*.walls.*.direction' => [
                'required',
                'string',
                Rule::in(['left-to-right', 'right-to-left']),
            ],
            'rooms.*.walls.*.width' => ['required', 'numeric', 'min:0.01'],
            'rooms.*.walls.*.height' => ['required', 'numeric', 'min:0.01'],
            'rooms.*.walls.*.model' => ['nullable', 'integer', 'exists:collection_models,id'],
            'rooms.*.walls.*.comment_referring_model' => ['nullable', 'string', 'max:500'],
            'rooms.*.walls.*.link_referring_model' => ['nullable', 'string', 'url', 'max:500'],
            'rooms.*.walls.*.files_referring_model' => ['nullable', 'array'],
            'rooms.*.walls.*.files_referring_model.*' => ['nullable', 'string', 'max:2048'],
            'rooms.*.walls.*.collection_referring_model' => ['nullable', 'string', 'max:255'],
            'rooms.*.walls.*.continueSameArt' => ['required', 'boolean'],
            'rooms.*.walls.*.continuations' => [
                'nullable',
                'array',
                'required_if:rooms.*.walls.*.continueSameArt,true',
            ],
            'rooms.*.walls.*.continuations.*.name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'rooms.*.walls.*.continuations.*.width' => [
                'required_with:rooms.*.walls.*.continuations',
                'numeric',
                'min:0.01',
            ],
            'rooms.*.walls.*.continuations.*.height' => [
                'required_with:rooms.*.walls.*.continuations',
                'numeric',
                'min:0.01',
            ],
            'rooms.*.walls.*.continuations.*.fit' => [
                'nullable',
                'string',
                Rule::in(['Inicial', 'Superior', 'Inferior', 'Central']),
            ],
            'deliveryTime' => ['nullable', 'integer', 'min:0'],
            'cep' => ['nullable', 'string', 'max:9'],
            'selectedCarrier' => ['nullable', 'array'],
            'selectedCarrier.name' => ['required_with:selectedCarrier', 'string', 'max:255'],
            'selectedCarrier.price' => ['required_with:selectedCarrier', 'numeric', 'min:0'],
            'selectedCarrier.deliveryTime' => ['required_with:selectedCarrier', 'integer', 'min:0'],
            'dropshipping_budget' => ['nullable', 'boolean'],
            'dropshipping_data' => ['nullable', 'array'],
            'dropshipping_data.name' => ['required_with:dropshipping_data', 'string', 'max:255'],
            'dropshipping_data.person_type' => ['required_with:dropshipping_data', 'string', Rule::in(['PF', 'PJ'])],
            'dropshipping_data.cpf_cnpj' => ['required_with:dropshipping_data', 'string', 'max:18'],
            'dropshipping_data.IE' => [
                'required_if:dropshipping_data.person_type,PJ',
                'nullable',
                'string',
                'max:18',
            ],
            'dropshipping_data.email' => ['required_with:dropshipping_data', 'email', 'max:255'],
            'dropshipping_data.phone' => ['required_with:dropshipping_data', 'string', 'max:15'],
            'dropshipping_data.cep' => ['required_with:dropshipping_data', 'string', 'max:9'],
            'dropshipping_data.uf' => ['required_with:dropshipping_data', 'string', 'size:2'],
            'dropshipping_data.state' => ['required_with:dropshipping_data', 'string', 'max:30'],
            'dropshipping_data.city' => ['required_with:dropshipping_data', 'string', 'max:255'],
            'dropshipping_data.neighborhood' => ['required_with:dropshipping_data', 'string', 'max:255'],
            'dropshipping_data.public_space' => ['nullable', 'string', 'max:255'],
            'dropshipping_data.number' => ['required_with:dropshipping_data', 'string', 'max:20'],
            'dropshipping_data.complement' => ['nullable', 'string', 'max:255'],
        ];
    }
}
