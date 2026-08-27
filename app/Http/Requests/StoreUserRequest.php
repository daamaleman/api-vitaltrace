<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /**
     * Determine whether the request is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define the validation rules for storing a user.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', 'exists:people,id', 'unique:users,person_id'],
            'email' => ['required', 'string', 'email', 'max:150', 'unique:users,email'],
            'password' => ['nullable', 'string', 'confirmed', Password::min(8)->letters()->numbers()->symbols()],
            'status' => ['sometimes', 'string', 'in:PENDING,ACTIVE,BLOCKED,SUSPENDED,DEACTIVATED'],
        ];
    }

    /**
     * Normalize input data before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }

    /**
     * Define custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'person_id.unique' => 'Esta persona ya tiene una cuenta de usuario.',
            'email.unique' => 'El correo electrónico ya está registrado.',
        ];
    }
}
