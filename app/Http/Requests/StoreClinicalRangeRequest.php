<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for defining a clinical range.
 */
class StoreClinicalRangeRequest extends FormRequest
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
            'patient_id' => ['nullable', 'integer', 'exists:patients,id'],
            'measurement_type_id' => ['required', 'integer', 'exists:measurement_types,id'],
            'min_value' => ['nullable', 'numeric'],
            'max_value' => ['nullable', 'numeric'],
            'severity' => ['required', 'string', 'in:INFORMATIONAL,MODERATE,HIGH,CRITICAL'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'defined_by' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Ensure at least one bound is present and min does not exceed max.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $min = $this->input('min_value');
            $max = $this->input('max_value');

            if ($min === null && $max === null) {
                $validator->errors()->add('min_value', 'At least one of min_value or max_value is required.');

                return;
            }

            if ($min !== null && $max !== null && (float) $min > (float) $max) {
                $validator->errors()->add('min_value', 'The min_value cannot be greater than max_value.');
            }
        });
    }
}
