<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * UpdatePermissionRequest
 * 
 * Form request class for validating permission update data.
 * This class handles the validation rules and authorization checks
 * when updating an existing permission in the system.
 */
class UpdatePermissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool Always returns true, allowing all users to make update requests.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     * 
     * Defines the validation rules for permission updates. The 'code' field must be unique
     * in the permissions table, ignoring the current permission being updated to allow
     * the same code to be retained.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     *         Returns an associative array of field names and their validation rules.
     */
    public function rules(): array
    {
        // Retrieve the permission ID from the route parameter to exclude it from unique validation
        $permissionId = $this->route('permission')?->id;

        return [
            // Code field: optional string up to 80 characters, must be unique in permissions table
            'code' => ['sometimes', 'string', 'max:80', Rule::unique('permissions', 'code')->ignore($permissionId)],
            // Name field: optional string up to 120 characters
            'name' => ['sometimes', 'string', 'max:120'],
            // Description field: optional nullable string up to 255 characters
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
