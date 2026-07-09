<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
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
            'role' => ['required', 'string', 'exists:roles,name'],
            'name' => ['required', 'string', 'max:191'],
            'email' => ['required', 'string', 'email', 'max:191', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_dropshipping' => ['nullable', 'boolean'],
            'wallet_balance' => ['nullable', 'numeric', 'min:0'],
            'reseller_id' => [
                'nullable',
                Rule::requiredIf(fn () => $this->input('role') === UserRole::Reseller->value),
                'integer',
                'exists:resellers,id',
                'unique:users,reseller_id',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'role.exists' => 'O papel selecionado não existe.',
            'email.unique' => 'O e-mail já está em uso.',
            'password.confirmed' => 'A confirmação da senha não confere.',
            'reseller_id.required' => 'Selecione o revendedor vinculado a este usuário.',
            'reseller_id.exists' => 'O revendedor selecionado não existe.',
            'reseller_id.unique' => 'Este revendedor já está vinculado a outro usuário.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $isReseller = $this->input('role') === UserRole::Reseller->value;

        $this->merge([
            'is_dropshipping' => $isReseller ? $this->input('is_dropshipping', false) : false,
            'reseller_id' => $isReseller ? $this->input('reseller_id') : null,
        ]);
    }
}
