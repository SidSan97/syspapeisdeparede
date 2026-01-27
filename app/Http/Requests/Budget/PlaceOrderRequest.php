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
            'walls' => ['nullable', 'array'],
            'walls.*' => ['nullable', 'array'],
            'walls.*.comment_referring_model' => ['nullable', 'string', 'max:500'],
            'walls.*.link_referring_model' => ['nullable', 'string', 'url'],
            'walls.*.files_referring_model' => ['nullable', 'array'],
            'walls.*.files_referring_model.*' => ['file', 'image', 'max:5120'],
            'walls.*.collection_referring_model' => ['nullable', 'string'],
            'terms_accepted' => ['required', 'accepted'],
        ];
    }
}

