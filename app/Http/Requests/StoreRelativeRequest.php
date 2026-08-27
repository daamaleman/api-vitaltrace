<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreRelativeRequest
 * 
 * Form request class for validating and handling the creation of a relative record.
 * This class ensures that the person_id provided is valid, exists in the database,
 * and is not already registered as a relative.
 */
class StoreRelativeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * 
     * @return bool Always returns true, allowing any user to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Define the validation rules for the request.
     * 
     * Rules:
     * - person_id: Required integer that must exist in the people table
     *   and must be unique in the relatives table (not already registered as a relative).
     *
     * @return array<string, mixed> Array of validation rules keyed by field name.
     */
    public function rules(): array
    {
        return [
            'person_id' => ['required', 'integer', 'exists:people,id', 'unique:relatives,person_id'],
        ];
    }

    /**
     * Define custom validation error messages.
     * 
     * Provides user-friendly error messages that override the default validation messages.
     *
     * @return array<string, string> Array of custom error messages keyed by validation rule.
     */
    public function messages(): array
    {
        return [
            'person_id.unique' => 'Esta persona ya está registrada como familiar.',
        ];
    }
}
