<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating an alert.
 *
 * The patient and originating measurement are immutable; classification,
 * escalation, severity and closing are managed here.
 */
class UpdateAlertRequest extends FormRequest
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
            'type' => ['sometimes', 'string', 'max:80'],
            'severity' => ['sometimes', 'string', 'in:INFORMATIONAL,MODERATE,HIGH,CRITICAL'],
            'status' => ['sometimes', 'string', 'in:NEW,CLASSIFIED,ESCALATED,IN_PROGRESS,CLOSED'],
            'description' => ['sometimes', 'string'],
            'closed_at' => ['sometimes', 'nullable', 'date'],
        ];
    }
}
