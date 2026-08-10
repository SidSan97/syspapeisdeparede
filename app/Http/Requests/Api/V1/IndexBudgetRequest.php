<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\BudgetStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexBudgetRequest extends FormRequest
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
                Rule::enum(BudgetStatus::class),
            ],
            'created_from' => ['nullable', 'date_format:Y-m-d'],
            'created_to' => [
                'nullable',
                'date_format:Y-m-d',
                'after_or_equal:date_from',
            ],
            'user_id' => ['nullable', 'integer'],
        ];
    }
}
