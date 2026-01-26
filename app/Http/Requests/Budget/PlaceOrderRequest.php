<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class PlaceOrderRequest extends FormRequest
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
            'id' => ['required', 'integer', 'exists:budgets,id'],
            'wall_referring_model_data' => ['nullable', 'string'], // JSON com mapeamento wall_id -> {comment, link}
            'wall_files' => ['nullable', 'array'], // Arquivos por parede: wall_files[wall_id][]
            'wall_files.*' => ['nullable', 'array'],
            'wall_files.*.*' => ['file', 'image', 'max:5120'],
            'collection_referring_model' => ['nullable', 'string'], // JSON com mapeamento wall_id -> image_id
            'terms_accepted' => ['required', 'accepted'],
        ];
    }
}

