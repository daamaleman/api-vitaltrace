<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating a notification.
 *
 * The recipient and channel are immutable; delivery lifecycle fields (status,
 * attempts, timestamps, error summary) can be updated by the delivery process.
 */
class UpdateAppNotificationRequest extends FormRequest
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
            'subject' => ['sometimes', 'string', 'max:160'],
            'general_message' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', 'in:PENDING,SENT,ERROR,CANCELLED'],
            'attempts' => ['sometimes', 'integer', 'min:0', 'max:255'],
            'scheduled_at' => ['sometimes', 'nullable', 'date'],
            'sent_at' => ['sometimes', 'nullable', 'date'],
            'error_summary' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
