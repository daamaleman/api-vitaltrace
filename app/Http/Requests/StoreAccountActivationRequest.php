<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for issuing an account activation record.
 *
 * The plain six-digit code is generated server-side and never received from the
 * client; only the target account and destination email are accepted here. The
 * code_hash, expiration and status are set by the activation service.
 */
class StoreAccountActivationRequest extends FormRequest
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
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'sent_to_email' => ['required', 'string', 'email', 'max:150'],
        ];
    }

    /**
     * Normalize the destination email before validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('sent_to_email')) {
            $this->merge(['sent_to_email' => mb_strtolower(trim((string) $this->input('sent_to_email')))]);
        }
    }
}
