<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Validation rules for creating a medication catalog entry.
 */
class StoreMedicationRequest extends FormRequest
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
            'generic_name' => ['required', 'string', 'max:150'],
            'presentation' => ['nullable', 'string', 'max:120'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
