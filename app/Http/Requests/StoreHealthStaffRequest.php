<?php

namespace App\Http\Requests;

use App\Models\HealthStaff;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreHealthStaffRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('SYSTEM_ADMIN') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->integer('user_id') ? User::query()->find($this->integer('user_id')) : null;
        $existingStaffId = $user?->person_id
            ? HealthStaff::withTrashed()->where('person_id', $user->person_id)->value('id')
            : null;

        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'professional_type' => ['required', 'string', Rule::in(['NURSE', 'DOCTOR'])],
            'professional_code' => [
                'required', 'string', 'max:50',
                Rule::unique('health_staff', 'professional_code')->ignore($existingStaffId),
            ],
            'specialty_id' => ['nullable', 'integer', 'exists:specialties,id'],
            'active' => ['sometimes', 'boolean'],
        ];
    }
}
