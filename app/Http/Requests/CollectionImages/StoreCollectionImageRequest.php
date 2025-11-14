<?php

namespace App\Http\Requests\CollectionImages;

use Illuminate\Foundation\Http\FormRequest;

class StoreCollectionImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'collection_arts_id' => ['required', 'integer', 'exists:collection_arts_subcategories,id'],
            'images' => ['required', 'array', 'min:1'],
            'images.*' => ['file', 'image', 'max:5120'],
            'names' => ['nullable', 'array'],
            'names.*' => ['nullable', 'string', 'max:100'],
        ];
    }
}

