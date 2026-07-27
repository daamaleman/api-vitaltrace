<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for appending an alert history entry.
 *
 * This is the only write operation available for the immutable log.
 */
class StoreAlertHistoryRequest extends FormRequest
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
            'alert_id' => ['required', 'integer', 'exists:alerts,id'],
            'action' => ['required', 'string', 'max:80'],
            'previous_status' => ['nullable', 'string', 'max:40'],
            'new_status' => ['required', 'string', 'max:40'],
            'comment' => ['nullable', 'string'],
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ];
    }
}
