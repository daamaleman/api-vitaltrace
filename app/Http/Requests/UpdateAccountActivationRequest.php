<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating an activation record.
 *
 * Only lifecycle fields (status, attempts, usage) can change; the hash and the
 * owning user are immutable at this layer.
 */
class UpdateAccountActivationRequest extends FormRequest
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
            'status' => ['sometimes', 'string', 'in:PENDING,USED,EXPIRED,INVALIDATED'],
            'attempts' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'used_at' => ['sometimes', 'nullable', 'date'],
            'expires_at' => ['sometimes', 'date'],
        ];
    }
}
