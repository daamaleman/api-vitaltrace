<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ListPatientAppointmentsRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('upcoming') && is_string($this->input('upcoming'))) {
            $value = filter_var($this->input('upcoming'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($value !== null) {
                $this->merge(['upcoming' => $value]);
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
            'status' => ['sometimes', 'string', 'in:SCHEDULED,CONFIRMED,ATTENDED,CANCELLED,NO_SHOW'],
            'date_from' => ['sometimes', 'date'],
            'date_to' => ['sometimes', 'date', 'after_or_equal:date_from'],
            'upcoming' => ['sometimes', 'boolean'],
        ];
    }
}
