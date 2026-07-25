<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\RolePermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Handle validation for updating a role-permission assignment.
 */
class UpdateRolePermissionRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to perform this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules for the request payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Optional role reference to update.
            'role_id' => ['sometimes', 'integer', 'exists:roles,id'],
            // Optional permission reference to update.
            'permission_id' => ['sometimes', 'integer', 'exists:permissions,id'],
        ];
    }

    /**
     * Add post-validation checks for duplicate role-permission combinations.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $roleId = $this->input('role_id');
            $permissionId = $this->input('permission_id');

            // If neither field is being updated, skip duplicate validation.
            if ($roleId === null && $permissionId === null) {
                return;
            }

            $current = $this->route('role_permission');

            // Validate uniqueness of the role-permission pair excluding current record.
            $exists = RolePermission::query()
                ->where('role_id', $roleId ?? $current?->role_id)
                ->where('permission_id', $permissionId ?? $current?->permission_id)
                ->where('id', '!=', $current?->id)
                ->exists();

            if ($exists) {
                $validator->errors()->add('permission_id', 'This permission is already assigned to the role.');
            }
        });
    }
}
