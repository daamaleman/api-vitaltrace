<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating an appointment.
 *
 * The patient is immutable; rescheduling, status changes and reassigning the
 * professional are allowed.
 */
class UpdateAppointmentRequest extends FormRequest
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
            'health_staff_id' => ['sometimes', 'integer', 'exists:health_staff,id'],
            'scheduled_at' => ['sometimes', 'date'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'reason' => ['sometimes', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:SCHEDULED,CONFIRMED,ATTENDED,CANCELLED,NO_SHOW'],
            'external_sync' => ['sometimes', 'string', 'in:NOT_APPLICABLE,PENDING,SYNCED,ERROR'],
        ];
    }
}
