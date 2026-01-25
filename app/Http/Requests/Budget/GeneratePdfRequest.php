<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePdfRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:budgets,id'],
            'percentage' => ['nullable', 'numeric', 'min:0'],
            'mockup_percentage' => ['nullable', 'numeric', 'min:0'],
            'cash_value' => ['nullable', 'numeric', 'min:0'],
            'total_amount' => ['nullable', 'numeric', 'min:0'],
            'installment_value' => ['nullable', 'numeric', 'min:0'],
            'total_amount_installments' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}

