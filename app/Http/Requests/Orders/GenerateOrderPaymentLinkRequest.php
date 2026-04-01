<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateOrderPaymentLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'components' => ['required', 'array', 'min:1'],
            'components.*' => ['required', Rule::in(['ARTES', 'PRODUTOS', 'FRETE'])],
            'payment_method' => ['required', Rule::in(['pix', 'credit_card', 'boleto'])],
            'installments' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $method = $this->input('payment_method');

            if ($method === 'credit_card') {
                $installments = $this->input('installments');
                if (! $installments || $installments < 1 || $installments > 12) {
                    $validator->errors()->add('installments', 'Informe entre 1 e 12 parcelas para cartão.');
                }
            }

            if ($method === 'boleto' && $this->filled('installments')) {
                $validator->errors()->add('installments', 'Boleto é sempre à vista; não utilize parcelas.');
            }
        });
    }
}
