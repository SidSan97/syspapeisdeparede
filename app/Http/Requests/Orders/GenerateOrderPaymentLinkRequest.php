<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'payment_method' => ['required', Rule::in(['pix', 'credit_card'])],
            'installments' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }
}

