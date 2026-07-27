<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for creating a notification.
 */
class StoreAppNotificationRequest extends FormRequest
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
            'type' => ['required', 'string', 'max:80'],
            'channel' => ['required', 'string', 'in:INTERNAL,EMAIL'],
            'subject' => ['required', 'string', 'max:160'],
            'general_message' => ['required', 'string'],
            'status' => ['sometimes', 'string', 'in:PENDING,SENT,ERROR,CANCELLED'],
            'scheduled_at' => ['nullable', 'date'],
        ];
    }
}
