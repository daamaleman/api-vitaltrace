<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the request is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id;

        return [
            'person_id' => [
                'sometimes',
                'integer',
                'exists:people,id',
                Rule::unique('users', 'person_id')->ignore($userId),
            ],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password' => ['sometimes', 'nullable', 'string', 'confirmed', Password::min(8)->letters()->numbers()->symbols()],
            'status' => ['sometimes', 'string', 'in:PENDING,ACTIVE,BLOCKED,SUSPENDED,DEACTIVATED'],
            'failed_attempts' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'blocked_until' => ['sometimes', 'nullable', 'date'],
        ];
    }

    /**
     * Normalize input before validation runs.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }
}
