<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListPatientTreatmentsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('active') && is_string($this->input('active'))) {
            $value = filter_var($this->input('active'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($value !== null) {
                $this->merge(['active' => $value]);
            }
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', 'string', 'in:ACTIVE,FINISHED,SUSPENDED'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
