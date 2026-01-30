<?php

namespace App\Http\Requests\CollectionImages;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCollectionImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'collection_category_id' => [
                'required',
                'integer',
                Rule::exists('collection_categories', 'id')->whereNotNull('parent_id'),
            ],
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'image', 'max:5120'],
            'names' => ['nullable', 'array'],
            'names.*' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'collection_category_id.exists' => 'A categoria informada deve ser uma subcategoria. Selecione uma subcategoria da lista.',
        ];
    }
}

