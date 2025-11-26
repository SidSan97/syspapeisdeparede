<?php

namespace App\Http\Requests\CollectionArtSubcategories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollectionArtSubcategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $subcategoryId = $this->route('collection_art_subcategory')?->id ?? $this->route('collection_art_subcategory');
        $collectionArtId = $this->input('collection_art_id');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                Rule::unique('collection_arts_subcategories', 'name')
                    ->where('collection_art_id', $collectionArtId)
                    ->ignore($subcategoryId),
            ],
            'collection_art_id' => ['required', 'integer', 'exists:collection_arts,id'],
            'sub_collection_image_cover' => ['nullable', 'image', 'max:5120'], // 5MB max
        ];
    }
}

