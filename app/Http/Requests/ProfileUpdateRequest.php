<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique(User::class, 'name')->ignore($this->user()->id),
            ],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
        ];

        if ($this->user()->isRole('customer')) {
            $rules += [
                'phone' => ['nullable', 'string', 'max:30'],
                'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
                'gender' => ['nullable', Rule::in(['female', 'male', 'other', 'prefer_not_to_say'])],
                'address' => ['nullable', 'string', 'max:1000'],
                'emergency_contact_name' => ['nullable', 'string', 'max:255'],
                'emergency_contact_phone' => ['nullable', 'string', 'max:30'],
                'emergency_contact_relationship' => ['nullable', 'string', 'max:100'],
            ];
        }

        return $rules;
    }

    /**
     * Get the validation messages for the request.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'Họ và tên này đã được sử dụng. Vui lòng chọn tên khác.',
        ];
    }
}
