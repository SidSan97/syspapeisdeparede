<?php

namespace App\Http\Requests\Frenet;

use Illuminate\Foundation\Http\FormRequest;

class CalculateShippingRequest extends FormRequest
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
            'cep' => ['required', 'string', 'regex:/^\d{5}-?\d{3}$/'],
            'productData' => ['required', 'array'],
            'productData.produto_nome' => ['required', 'string', 'max:255'],
            'productData.peso_liquido' => ['required', 'numeric', 'min:0'],
            'productData.peso_bruto' => ['required', 'numeric', 'min:0'],
            'productData.alturaEmbalagem' => ['required', 'numeric', 'min:0'],
            'productData.comprimentoEmbalagem' => ['required', 'numeric', 'min:0'],
            'productData.larguraEmbalagem' => ['required', 'numeric', 'min:0'],
            'productData.diametroEmbalagem' => ['required', 'numeric', 'min:0'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cep.required' => 'O CEP é obrigatório.',
            'cep.regex' => 'O CEP deve estar no formato válido (ex: 12345-678 ou 12345678).',
            'productData.required' => 'Os dados do produto são obrigatórios.',
            'productData.array' => 'Os dados do produto devem ser um array.',
            'productData.produto_nome.required' => 'O nome do produto é obrigatório.',
            'productData.peso_liquido.required' => 'O peso líquido é obrigatório.',
            'productData.peso_liquido.numeric' => 'O peso líquido deve ser um número.',
            'productData.peso_liquido.min' => 'O peso líquido deve ser maior ou igual a zero.',
            'productData.peso_bruto.required' => 'O peso bruto é obrigatório.',
            'productData.peso_bruto.numeric' => 'O peso bruto deve ser um número.',
            'productData.peso_bruto.min' => 'O peso bruto deve ser maior ou igual a zero.',
            'productData.alturaEmbalagem.required' => 'A altura da embalagem é obrigatória.',
            'productData.alturaEmbalagem.numeric' => 'A altura da embalagem deve ser um número.',
            'productData.alturaEmbalagem.min' => 'A altura da embalagem deve ser maior ou igual a zero.',
            'productData.comprimentoEmbalagem.required' => 'O comprimento da embalagem é obrigatório.',
            'productData.comprimentoEmbalagem.numeric' => 'O comprimento da embalagem deve ser um número.',
            'productData.comprimentoEmbalagem.min' => 'O comprimento da embalagem deve ser maior ou igual a zero.',
            'productData.larguraEmbalagem.required' => 'A largura da embalagem é obrigatória.',
            'productData.larguraEmbalagem.numeric' => 'A largura da embalagem deve ser um número.',
            'productData.larguraEmbalagem.min' => 'A largura da embalagem deve ser maior ou igual a zero.',
            'productData.diametroEmbalagem.required' => 'O diâmetro da embalagem é obrigatório.',
            'productData.diametroEmbalagem.numeric' => 'O diâmetro da embalagem deve ser um número.',
            'productData.diametroEmbalagem.min' => 'O diâmetro da embalagem deve ser maior ou igual a zero.',
        ];
    }
}

