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
            'paymentMethod' => ['nullable', 'string', Rule::in(['pix', 'installment'])],
            'installments' => ['nullable', 'integer', 'min:1'],
            'installmentLimit' => ['nullable', 'integer', 'min:1'],
            'dropshipping_budget' => ['nullable', 'boolean'],
        ];
    }
}
