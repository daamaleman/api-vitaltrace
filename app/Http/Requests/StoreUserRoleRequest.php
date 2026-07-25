<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // The user who will receive the role. Must exist in users table.
            'user_id' => ['required', 'integer', 'exists:users,id'],
            // The role to assign. Must exist in roles table.
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            // Optional flag indicating if the assignment is active.
            'active' => ['sometimes', 'boolean'],
            // Optional id of the user who performed the assignment.
            'assigned_by' => ['nullable', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Configure the validator instance.
     *
     * Adds an after-validation hook to ensure the same role is not
     * assigned to the user more than once.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            // Check if a UserRole record with the given user_id and role_id exists.
            $exists = \App\Models\UserRole::query()
                ->where('user_id', $this->input('user_id'))
                ->where('role_id', $this->input('role_id'))
                ->exists();

            if ($exists) {
                // Add a validation error when the role is already assigned.
                $validator->errors()->add('role_id', 'This role is already assigned to the user.');
            }
        });
    }
}
