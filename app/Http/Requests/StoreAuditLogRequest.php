<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for appending an audit log entry.
 *
 * This is the only write operation available for the immutable trail.
 */
class StoreAuditLogRequest extends FormRequest
{
    /**
     * Authorization is handled by policies/middleware at the route level.
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
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'role_snapshot' => ['nullable', 'string', 'max:50'],
            'action' => ['required', 'string', 'in:CREATE,UPDATE,DELETE,ACCESS,LOGIN,LOGOUT'],
            'table' => ['nullable', 'string', 'max:100'],
            'record_id' => ['nullable', 'integer'],
            'old_values' => ['nullable', 'array'],
            'new_values' => ['nullable', 'array'],
            'ip_address' => ['nullable', 'ip'],
            'user_agent' => ['nullable', 'string'],
            'request_id' => ['required', 'uuid'],
        ];
    }
}
