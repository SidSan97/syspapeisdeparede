<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class IndexOrderLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'is_completed' => ['nullable', 'boolean'],
            'delivery_date' => ['nullable', 'date_format:Y-m-d'],
            'order_number' => ['nullable', 'integer'],
            'quote_name' => ['nullable', 'string', 'max:255'],
            'unassigned' => ['nullable', 'boolean'],
            'assigned_to_me' => ['nullable', 'boolean'],
            'member_ids' => ['nullable', 'array'],
            'member_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}
