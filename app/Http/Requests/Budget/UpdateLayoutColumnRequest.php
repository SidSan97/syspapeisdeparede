<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLayoutColumnRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $typePage = $this->input('type_page', 'layout');
        
        $rules = [
            'order_budget_id' => ['required', 'integer', 'exists:order_budgets,id'],
            'layout_column_names_id' => ['required', 'integer'],
            'type_page' => ['nullable', 'string', 'in:layout,product'],
        ];

        // Validação condicional baseada no type_page
        if ($typePage === 'product') {
            $rules['layout_column_names_id'][] = 'exists:production_column_names,id';
        } else {
            $rules['layout_column_names_id'][] = 'exists:layout_column_names,id';
        }

        return $rules;
    }
}

