<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\RolePermission;
use Illuminate\Foundation\Http\FormRequest;

class StoreRolePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool True if the request is allowed, false otherwise.
     */
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
            // ID of the role to which a permission will be assigned
            'role_id' => ['required', 'integer', 'exists:roles,id'],
            // ID of the permission to assign to the role
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
        ];
    }

    public function withValidator($validator): void
    {
        // Add a post-validation hook to ensure the same permission is not
        // already assigned to the role. If it exists, add a validation error.
        $validator->after(function ($validator) {
            $exists = RolePermission::query()
                ->where('role_id', $this->input('role_id'))
                ->where('permission_id', $this->input('permission_id'))
                ->exists();

            if ($exists) {
                // Prevent duplicate role-permission assignments
                $validator->errors()->add('permission_id', 'This permission is already assigned to the role.');
            }
        });
    }
}
