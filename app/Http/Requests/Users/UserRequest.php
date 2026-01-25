<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
        if ($this->isMethod('post')) {
            return $this->createRules();
        } elseif ($this->isMethod('put')) {
            return $this->updateRules();
        }

        return [];
    }

    public function createRules(): array
    {
        return [
            'role' => ['required', 'string', 'exists:roles,name'],
            'name'       => ['required', 'string', 'max:191'],
            'email'      => ['required', 'string', 'email', 'max:191', 'unique:users'],
            'password'   => ['required', 'string', 'min:6'],
            'password_confirmation' => ['required', 'string', 'same:password'],
            'is_dropshipping' => ['nullable', 'integer', 'in:0,1'],
        ];
    }

    public function updateRules(): array
    {
        return [
            'role' => ['required', 'string', 'exists:roles,name'],
            'name'       => ['required', 'string', 'max:191'],
            'email' => [
                'required',
                'string',
                'email',
                'max:191',
                'unique:users,email,' . $this->get('id')
            ],
            'password'   => ['nullable', 'string', 'min:6'],
            'is_dropshipping' => ['nullable', 'integer', 'in:0,1'],
        ];
    }
}
