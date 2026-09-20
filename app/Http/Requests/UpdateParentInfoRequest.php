<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParentInfoRequest extends FormRequest
{
    public function authorize()
    {
        // Adjust authorization as needed (e.g., check user permissions)
        return true;
    }

    public function rules()
    {
        return [
            'student_id' => 'required|exists:students,student_id',
            'guardian_name' => 'required|string|max:255',
            'guardian_profession' => 'nullable|string|max:255',
            'guardian_contact_number' => ['required', 'string', 'max:20', 'regex:/^(?:\+94|94|0)?[1-9][0-9]{8}$/'],
            'guardian_email' => 'nullable|email|max:255',
            'guardian_address' => 'required|string',
            'emergency_contact_number' => ['required', 'string', 'max:20', 'regex:/^(?:\+94|94|0)?[1-9][0-9]{8}$/'],
        ];
    }

    public function messages()
    {
        return [
            'guardian_name.required' => 'Guardian name is required.',
            'guardian_contact_number.required' => 'Contact number is required.',
            'guardian_contact_number.regex' => 'Contact number must be a valid phone number (e.g. 0771234567, 94771234567, or +94771234567).',
            'guardian_email.email' => 'Please provide a valid email address.',
            'guardian_address.required' => 'Address is required.',
            'emergency_contact_number.required' => 'Emergency contact number is required.',
            'emergency_contact_number.regex' => 'Emergency contact must be a valid phone number (e.g. 0771234567, 94771234567, or +94771234567).',
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['guardian_contact_number', 'emergency_contact_number'] as $field) {
            if ($this->exists($field)) {
                $this->merge([
                    $field => preg_replace('/\s+/', '', (string) $this->input($field)),
                ]);
            }
        }
    }
}
