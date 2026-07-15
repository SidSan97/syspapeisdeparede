<?php

namespace App\Http\Requests\Orders;

use Illuminate\Foundation\Http\FormRequest;

class MergeOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && $this->user()->isAdmin();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'order_ids' => ['required', 'array', 'min:2'],
            'order_ids.*' => ['required', 'integer', 'distinct', 'exists:orders,id'],
            'name' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'order_ids.required' => 'Informe os pedidos a juntar.',
            'order_ids.min' => 'Selecione pelo menos dois pedidos.',
            'name.required' => 'Informe o nome do novo pedido.',
        ];
    }
}
