<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * Validation rules for activating an account with an emailed code.
 */
class ActivateAccountRequest extends FormRequest
{
    /**
     * Authorization is handled by the activation flow itself.
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
            'code' => ['required', 'string', 'digits:6'],
            'password' => ['required', 'string', 'confirmed', Password::min(8)->letters()->numbers()->symbols()],
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
