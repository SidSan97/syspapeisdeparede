<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBudgetStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public static function allowedStatuses(): array
    {
        return ['open', 'approved', 'canceled'];
    }

    public static function mapStatusToPortuguese(string $status): string
    {
        $map = [
            'open' => 'em aberto',
            'approved' => 'aprovado',
            'canceled' => 'cancelado',
        ];

        return $map[$status] ?? $status;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(self::allowedStatuses())],
        ];
    }

    public function getTranslatedStatus(): string
    {
        return self::mapStatusToPortuguese($this->input('status'));
    }
}
