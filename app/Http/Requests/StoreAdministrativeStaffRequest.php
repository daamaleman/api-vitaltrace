<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Handle validation for creating a new administrative staff record.
 */
class StoreAdministrativeStaffRequest extends FormRequest
{
    /**
     * Determine whether the current user is allowed to perform this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define the validation rules for the incoming request payload.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', 'exists:people,id', 'unique:administrative_staff,person_id'],
            'employee_code' => ['required', 'string', 'max:30', 'unique:administrative_staff,employee_code'],
            'type' => ['required', 'string', 'in:ADMISSION,SYSTEM_ADMIN'],
            'position' => ['required', 'string', 'max:100'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Provide custom validation messages for specific rule failures.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'person_id.unique' => 'Esta persona ya está registrada como personal administrativo.',
            'employee_code.unique' => 'El código de empleado ya está en uso.',
        ];
    }
}
