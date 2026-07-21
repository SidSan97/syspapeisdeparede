<?php

namespace App\Http\Requests\Api\V1;

use App\Support\OrderStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexOrderRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'status' => [
                'nullable',
                'string',
                Rule::in(array_merge(['all'], OrderStatus::all())),
            ],
            'date_from' => ['nullable', 'date_format:Y-m-d'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'user_id' => ['nullable', 'integer'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // if ($this->has('status')) {
        //     $this->merge([
        //         'status' => strtolower($this->input('status')),
        //     ]);
        // }
    }
}
