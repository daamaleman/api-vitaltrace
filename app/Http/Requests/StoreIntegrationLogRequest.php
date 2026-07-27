<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for appending an integration log entry.
 *
 * This is the only write operation available for the immutable log.
 */
class StoreIntegrationLogRequest extends FormRequest
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
            'service' => ['required', 'string', 'max:80'],
            'operation' => ['required', 'string', 'max:100'],
            'local_reference' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'string', 'in:PENDING,SUCCESS,ERROR'],
            'attempts' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'error_summary' => ['nullable', 'string'],
            'request_id' => ['required', 'uuid'],
        ];
    }
}
