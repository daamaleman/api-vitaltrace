<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Handles validation rules for updating an administrative staff record.
 */
class UpdateAdministrativeStaffRequest extends FormRequest
{
    /**
     * Determine whether the user is authorized to make this request.
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
        /** @var int|string|null $staffId */
        $staffId = $this->route('administrative_staff')?->id;

        return [
            // Unique employee code, ignoring the current staff record during update.
            'employee_code' => [
                'sometimes',
                'string',
                'max:30',
                Rule::unique('administrative_staff', 'employee_code')->ignore($staffId),
            ],
            // Administrative staff type.
            'type' => ['sometimes', 'string', 'in:ADMISSION,SYSTEM_ADMIN'],
            // Job position/title.
            'position' => ['sometimes', 'string', 'max:100'],
            // Staff activation status.
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
