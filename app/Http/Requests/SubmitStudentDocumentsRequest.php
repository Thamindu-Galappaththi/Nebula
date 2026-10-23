<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitStudentDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $maxKb = (int) config('student_documents.max_file_kb', 5120);
        $mimes = implode(',', config('student_documents.allowed_mimes', ['pdf', 'jpg', 'jpeg', 'png']));
        $maxOther = (int) config('student_documents.max_other_files', 3);

        return [
            'full_name' => ['required', 'string', 'max:255'],
            'id_value' => ['required', 'string', 'max:64'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'nic_document' => ['required', 'file', 'max:' . $maxKb, 'mimes:' . $mimes],
            'birth_certificate' => ['required', 'file', 'max:' . $maxKb, 'mimes:' . $mimes],
            'other_documents' => ['nullable', 'array', 'max:' . $maxOther],
            'other_documents.*' => ['file', 'max:' . $maxKb, 'mimes:' . $mimes],
        ];
    }

    public function messages(): array
    {
        return [
            'nic_document.required' => 'Please upload your National Identity Card.',
            'birth_certificate.required' => 'Please upload your birth certificate.',
            'nic_document.mimes' => 'Documents must be PDF, JPG, or PNG files.',
            'birth_certificate.mimes' => 'Documents must be PDF, JPG, or PNG files.',
            'other_documents.*.mimes' => 'Documents must be PDF, JPG, or PNG files.',
        ];
    }
}
