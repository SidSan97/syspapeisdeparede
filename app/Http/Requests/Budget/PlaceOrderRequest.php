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
            'comment_referring_model' => ['nullable', 'string', 'max:500'],
            'link_referring_model' => ['nullable', 'string', 'max:150'],
            'collection_referring_model' => ['nullable', 'string'],
            'files_referring_model' => ['nullable', 'array'],
            'files_referring_model.*' => ['file', 'image', 'max:5120'],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }
}

