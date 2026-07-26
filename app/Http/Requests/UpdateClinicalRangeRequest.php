<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for updating a clinical range.
 *
 * The patient scope, measurement type and defining doctor are immutable; a
 * different scope should be a new range.
 */
class UpdateClinicalRangeRequest extends FormRequest
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
            'min_value' => ['sometimes', 'nullable', 'numeric'],
            'max_value' => ['sometimes', 'nullable', 'numeric'],
            'severity' => ['sometimes', 'string', 'in:INFORMATIONAL,MODERATE,HIGH,CRITICAL'],
            'start_date' => ['sometimes', 'date'],
            'end_date' => ['sometimes', 'nullable', 'date', 'after_or_equal:start_date'],
        ];
    }

    /**
     * Validate bound consistency against the resulting values.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $current = $this->route('clinical_range');

            if ($current === null) {
                return;
            }

            $min = $this->has('min_value') ? $this->input('min_value') : $current->min_value;
            $max = $this->has('max_value') ? $this->input('max_value') : $current->max_value;

            if ($min !== null && $max !== null && (float) $min > (float) $max) {
                $validator->errors()->add('min_value', 'The min_value cannot be greater than max_value.');
            }
        });
    }
}
