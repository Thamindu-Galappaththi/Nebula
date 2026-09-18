<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Auth;

class LoginRequest extends FormRequest
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
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:1',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Username is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.max' => 'Username cannot exceed 255 characters.',
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 1 character.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => 'username',
            'password' => 'password',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw new ValidationException($validator);
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Additional custom validation logic can be added here
            if ($this->has('email') && $this->has('password')) {
                // Check if user exists
                $user = \App\Models\User::where('email', $this->email)->first();
                
                if (!$user) {
                    $validator->errors()->add('email', 'Invalid username or password. Please try again.');
                    return;
                }

                // Check if user is active
                if ($user->status != "1") {
                    $validator->errors()->add('email', 'Your account is not active. Please contact administrator.');
                    return;
                }

                // Check if user has a valid role
                if (!$user->hasAssignedRoles()) {
                    $validator->errors()->add('email', 'Your account does not have a valid role assigned. Please contact administrator.');
                    return;
                }

                // Check password
                if (!Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
                    $validator->errors()->add('email', 'Invalid username or password. Please try again.');
                    return;
                }
            }
        });
    }
} 