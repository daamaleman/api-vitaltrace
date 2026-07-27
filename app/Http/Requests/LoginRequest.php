<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for the SPA login request.
 */
class LoginRequest extends FormRequest
{
    /**
     * Authorization is handled by the login flow itself.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validation rules.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Normalize the email before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }
}
