<?php

namespace App\Http\Requests\CollectionCategories;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollectionCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('collection_category')?->id ?? $this->route('collection_category');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('collection_categories', 'name')
                    ->where('parent_id', $this->input('parent_id'))
                    ->ignore($categoryId),
            ],
            'image_cover' => ['nullable', 'image', 'max:5120'], // 5MB max
            'parent_id' => ['nullable', 'integer', 'exists:collection_categories,id'],
        ];
    }
}

