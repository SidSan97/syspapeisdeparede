<?php

namespace App\Http\Requests\CollectionArts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CollectionArtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $artId = $this->route('collection_art')?->id ?? $this->route('collection_art');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('collection_arts', 'name')->ignore($artId),
            ],
        ];
    }
}

