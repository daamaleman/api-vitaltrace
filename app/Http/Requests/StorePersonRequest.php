<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name'        => ['required', 'string', 'max:80'],
            'middle_name'       => ['nullable', 'string', 'max:80'],
            'first_last_name'   => ['required', 'string', 'max:80'],
            'second_last_name'  => ['nullable', 'string', 'max:80'],
            'date_of_birth'     => ['required', 'date', 'before_or_equal:today'],
            'gender'            => ['required', 'string', 'max:30'],
            'identity_document' => ['nullable', 'string', 'max:40', 'unique:people,identity_document'],
            'phone'             => ['nullable', 'string', 'max:25'],
            'address'           => ['nullable', 'string'],
        ];
    }
}
