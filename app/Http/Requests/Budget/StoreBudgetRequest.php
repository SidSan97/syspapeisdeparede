<?php

namespace App\Http\Requests\Budget;

use App\Support\Budget\BudgetCalculator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBudgetRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'rooms' => ['required', 'array', 'min:1'],
            'rooms.*.name' => ['nullable', 'string', 'max:255'],
            'rooms.*.walls' => ['required', 'array', 'min:1'],
            'rooms.*.walls.*.name' => ['nullable', 'string', 'max:255'],
            'rooms.*.walls.*.width' => ['required', 'numeric', 'min:0.01'],
            'rooms.*.walls.*.height' => ['required', 'numeric', 'min:0.01'],
            'rooms.*.walls.*.model' => ['required', 'integer', 'exists:collection_models,id'],
            'rooms.*.walls.*.continueSameArt' => ['required', 'boolean'],
            'rooms.*.walls.*.continuations' => [
                'nullable',
                'array',
                'required_if:rooms.*.walls.*.continueSameArt,true',
            ],
            'rooms.*.walls.*.continuations.*.direction' => [
                'required_with:rooms.*.walls.*.continuations',
                'string',
                Rule::in(['left-to-right', 'right-to-left']),
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
            'deliveryTime' => ['nullable', 'integer', 'min:0'],
            'cep' => ['nullable', 'string', 'max:9'],
            'selectedCarrier' => ['nullable', 'array'],
            'selectedCarrier.name' => ['required_with:selectedCarrier', 'string', 'max:255'],
            'selectedCarrier.price' => ['required_with:selectedCarrier', 'numeric', 'min:0'],
            'selectedCarrier.deliveryTime' => ['required_with:selectedCarrier', 'integer', 'min:0'],
            'paymentMethod' => ['nullable', 'string', Rule::in(['pix', 'credit_card'])],
            'installments' => ['nullable', 'integer', 'min:1'],
            'installmentLimit' => ['nullable', 'integer', 'min:1'],
            'dropshipping_budget' => ['nullable', 'boolean'],
            'dropshipping_data' => ['nullable', 'array'],
            'dropshipping_data.name' => ['required_with:dropshipping_data', 'string', 'max:255'],
            'dropshipping_data.person_type' => ['required_with:dropshipping_data', 'string', Rule::in(['PF', 'PJ'])],
            'dropshipping_data.cpf_cnpj' => ['required_with:dropshipping_data', 'string', 'max:18'],
            'dropshipping_data.IE' => [
                'required_if:dropshipping_data.person_type,PJ',
                'nullable',
                'string',
                'max:18'
            ],
            'dropshipping_data.email' => ['required_with:dropshipping_data', 'email', 'max:255'],
            'dropshipping_data.phone' => ['required_with:dropshipping_data', 'string', 'max:15'],
            'dropshipping_data.cep' => ['required_with:dropshipping_data', 'string', 'max:9'],
            'dropshipping_data.uf' => ['required_with:dropshipping_data', 'string', 'size:2'],
            'dropshipping_data.state' => ['required_with:dropshipping_data', 'string', 'max:30'],
            'dropshipping_data.city' => ['required_with:dropshipping_data', 'string', 'max:255'],
            'dropshipping_data.neighborhood' => ['required_with:dropshipping_data', 'string', 'max:255'],
            'dropshipping_data.public_space' => ['nullable', 'string', 'max:255'],
            'dropshipping_data.complement' => ['nullable', 'string', 'max:255'],
        ];
    }
}
