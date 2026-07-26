<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for scheduling an appointment.
 */
class StoreAppointmentRequest extends FormRequest
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
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'health_staff_id' => ['required', 'integer', 'exists:health_staff,id'],
            'scheduled_at' => ['required', 'date'],
            'duration_minutes' => ['sometimes', 'integer', 'min:1', 'max:1440'],
            'reason' => ['required', 'string', 'max:255'],
            'status' => ['sometimes', 'string', 'in:SCHEDULED,CONFIRMED,ATTENDED,CANCELLED,NO_SHOW'],
            'external_sync' => ['sometimes', 'string', 'in:NOT_APPLICABLE,PENDING,SYNCED,ERROR'],
        ];
    }
}
