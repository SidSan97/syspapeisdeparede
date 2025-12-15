<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UploadArtRequest extends FormRequest
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
            'comment' => ['required', 'string', 'max:500'],
            'art_file' => ['nullable', 'image', 'max:10240'], // 10MB max
            'order_budget_id' => ['required', 'integer', 'exists:order_budgets,id'],
            'dealer_id' => ['required', 'integer', 'exists:users,id'],
            'designer_id' => ['required', 'integer', 'exists:users,id'],
            'order_id' => ['required', 'integer', 'exists:orders,id'],
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
            'comment.required' => 'O comentário é obrigatório.',
            'art_file.image' => 'O arquivo deve ser uma imagem.',
            'art_file.max' => 'O arquivo não pode ser maior que 10MB.',
            'order_budget_id.required' => 'O ID do orçamento do pedido é obrigatório.',
            'order_budget_id.exists' => 'O orçamento do pedido selecionado não existe.',
            'dealer_id.required' => 'O ID do revendedor é obrigatório.',
            'dealer_id.exists' => 'O revendedor selecionado não existe.',
            'designer_id.required' => 'O ID do designer é obrigatório.',
            'designer_id.exists' => 'O designer selecionado não existe.',
            'order_id.required' => 'O ID do pedido é obrigatório.',
            'order_id.exists' => 'O pedido selecionado não existe.',
        ];
    }
}

