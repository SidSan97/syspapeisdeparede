<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPaymentRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'payment_file' => ['required', 'file', 'mimes:jpeg,jpg,pdf', 'max:10240'], // 10MB
            'budget_id' => ['required', 'integer', 'exists:budgets,id'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'payment_file.required' => 'O arquivo de pagamento é obrigatório.',
            'payment_file.file' => 'O arquivo deve ser um arquivo válido.',
            'payment_file.mimes' => 'O arquivo deve ser do tipo JPEG, JPG ou PDF.',
            'payment_file.max' => 'O arquivo não pode ser maior que 10MB.',
            'budget_id.required' => 'O ID do orçamento é obrigatório.',
            'budget_id.exists' => 'O orçamento informado não existe.',
        ];
    }
}

