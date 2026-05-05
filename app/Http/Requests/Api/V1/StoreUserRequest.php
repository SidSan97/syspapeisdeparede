<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
        ];
    }

    public function messages(): array
    {
        return [
            'role.exists' => 'O papel selecionado não existe.',
            'email.unique' => 'O e-mail já está em uso.',
            'password.confirmed' => 'A confirmação da senha não confere.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $isDropshipping = $this->input('is_dropshipping', false);

        if ($this->input('role') !== UserRole::Reseller->value) {
            $isDropshipping = false;
        }

        $this->merge([
            'is_dropshipping' => $isDropshipping,
        ]);
    }
}
