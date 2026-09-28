<?php

namespace App\Http\Requests;

use App\Models\Student;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class StudentRegistrationRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            // Personal Information
            'title' => 'required|string|max:50',
            // Only letters, spaces, and dots
            'nameWithInitials' => 'required|string|max:100|regex:/^[a-zA-Z\s\.]+$/',
            // Only letters and spaces
            'fullName' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'gender' => 'required|in:Male,Female',
            'identificationType' => 'required|in:National id,Postal id,Passport,Driving Licence',
            'idValue' => [
                'required',
                'string',
                'max:50',
                'unique:students,id_value',
                function ($attribute, $value, $fail) {
                    \Log::info('ID Validation', [
                        'identificationType' => $this->identificationType,
                        'idValue' => $value
                    ]);
                    // Accept both old (9 digits + V/v/x/X) and new (12 digits) NIC formats
                    if ($this->identificationType === 'National id') {
                        if (!preg_match('/^([0-9]{9}[vVxX]|[0-9]{12})$/', $value)) {
                            $fail('The National ID must be in the format: 123456789V or 200012345678');
                        }
                    } elseif ($this->identificationType === 'Postal id') {
                        if (!preg_match('/^[0-9]{9}$/', $value)) {
                            $fail('The Postal ID must be 9 digits.');
                        }
                    } elseif ($this->identificationType === 'Passport') {
                        if (!preg_match('/^[A-Z]{1,2}[0-9]{7}$/', $value)) {
                            $fail('The Passport number must be in the format: N1234567');
                        }
                    } elseif ($this->identificationType === 'Driving Licence') {
                        if (!preg_match('/^[A-Z]{2}[0-9]{7}$/', $value)) {
                            $fail('The Driving Licence must be in the format: AB1234567');
                        }
                    } else {
                        $fail('Invalid identification type selected.');
                    }
                }
            ],
            'address' => 'required|string|max:500',
            'district' => 'required|in:Ampara,Anuradhapura,Badulla,Batticaloa,Colombo,Galle,Gampaha,Hambantota,Jaffna,Kalutara,Kandy,Kegalle,Kilinochchi,Kurunegala,Mannar,Matale,Matara,Monaragala,Mullaitivu,Nuwara Eliya,Polonnaruwa,Puttalam,Ratnapura,Trincomalee,Vavuniya',
            'email' => 'required|email|max:255|unique:students,email',
            // Sri Lankan phone number: +94 or 0, then 9 digits
            'mobilePhone' => [
                'required',
                'string',
                'max:15',
                'regex:/^(\+94|0)[1-9][0-9]{8}$/'
            ],
            // Optional Sri Lankan phone number (nullable)
            'homePhone' => [
                'nullable',
                'string',
                'max:15',
                'regex:/^(\+94|0)[1-9][0-9]{8}$/'
            ],
            'whatsappPhone' => [
                'required',
                'string',
                'max:15',
                'regex:/^(\+94|0)[1-9][0-9]{8}$/'
            ],
            'birthday' => [
                'required',
                'date',
                'before:today',
                'after:1900-01-01'
            ],
            'institute_location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            
            'specialNeeds' => 'nullable|string|max:500',
            'extraCurricular' => 'nullable|string|max:500',
            'futurePotential' => 'nullable|string|max:500',
            'remarks' => 'nullable|string|max:1000',
            
            // Parent/Guardian Information
            // Only letters and spaces
            'parentName' => 'required|string|max:255|regex:/^[a-zA-Z\s]+$/',
            'parentProfession' => 'nullable|string|max:100',
            'parentContactNo' => [
                'required',
                'string',
                'max:15',
                'regex:/^(\+94|0)[1-9][0-9]{8}$/'
            ],
            'parentEmail' => 'nullable|email|max:255',
            'parentAddress' => 'required|string|max:500',
            'emergencyContactNo' => [
                'required',
                'string',
                'max:15',
                'regex:/^(\+94|0)[1-9][0-9]{8}$/'
            ],
            
            // File Uploads
            'userPhoto' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'ol_certificate' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120'
            ],
            'al_certificate' => [
                'nullable',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'max:5120'
            ],
            'otherDocumentsFiles.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:5120',
            
            // Exam Information
            'pending_result' => 'required|in:yes,no',
            
            // O/L Details
            'ol_index_no' => 'nullable|string|max:20',
            'ol_exam_type' => 'nullable|string|max:100',
            'ol_exam_year' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    if (is_null($value) || $value === '') return;
                    $birth = $this->input('birthday');
                    if (!$birth) {
                        $fail('Date of birth is required to validate O/L exam year.');
                        return;
                    }
                    $birthYear = (int) date('Y', strtotime($birth));
                    $year = (int) $value;
                    if ($year <= $birthYear) {
                        $fail('O/L exam year must be after the student\'s birth year.');
                        return;
                    }
                    if ($year < 1900) {
                        $fail('O/L exam year must be 1900 or later.');
                        return;
                    }
                    if ($year > (int) date('Y') + 1) {
                        $fail('O/L exam year cannot be in the distant future.');
                        return;
                    }
                }
            ],
            'ol_subjects.*' => 'nullable|string|max:50',
            'ol_results.*' => 'nullable|string|max:10',
            
            // A/L Details
            'al_index_no' => 'nullable|string|max:20',
            'al_exam_type' => 'nullable|string|max:100',
            'al_exam_year' => [
                'nullable',
                'integer',
                function ($attribute, $value, $fail) {
                    if (is_null($value) || $value === '') return;
                    $birth = $this->input('birthday');
                    if (!$birth) {
                        $fail('Date of birth is required to validate A/L exam year.');
                        return;
                    }
                    $birthYear = (int) date('Y', strtotime($birth));
                    $year = (int) $value;
                    if ($year <= $birthYear) {
                        $fail('A/L exam year must be after the student\'s birth year.');
                        return;
                    }
                    if ($year < 1900) {
                        $fail('A/L exam year must be 1900 or later.');
                        return;
                    }
                    if ($year > (int) date('Y') + 1) {
                        $fail('A/L exam year cannot be in the distant future.');
                        return;
                    }
                }
            ],
            'al_stream' => 'nullable|string|max:50',
            'z_score_value' => 'nullable|numeric|min:0|max:300',
            'al_subjects.*' => 'nullable|string|max:50',
            'al_results.*' => 'nullable|string|max:10',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please select a title.',
            'nameWithInitials.required' => 'Name with initials is required.',
            'nameWithInitials.regex' => 'Name with initials can only contain letters, spaces, and dots.',
            'fullName.required' => 'Full name is required.',
            'fullName.regex' => 'Full name can only contain letters and spaces.',
            'gender.required' => 'Please select a gender.',
            'identificationType.required' => 'Please select an identification type.',
            'idValue.required' => 'ID value is required.',
            'idValue.unique' => 'This ID value is already registered. Open the existing Student Profile instead of creating a new record.',
            'address.required' => 'Address is required.',
            'district.required' => 'Please select a district.',
            'district.in' => 'Please select a valid district in Sri Lanka.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'mobilePhone.required' => 'Mobile phone number is required.',
            'mobilePhone.regex' => 'Please enter a valid Sri Lankan phone number.',
            'whatsappPhone.required' => 'WhatsApp phone number is required.',
            'whatsappPhone.regex' => 'Please enter a valid Sri Lankan phone number.',
            'birthday.required' => 'Date of birth is required.',
            'birthday.before' => 'Date of birth cannot be in the future.',
            'birthday.after' => 'Date of birth must be after 1900.',
            'institute_location.required' => 'Please select an institute location.',
            'parentName.required' => 'Parent/Guardian name is required.',
            'parentName.regex' => 'Parent/Guardian name can only contain letters and spaces.',
            'parentContactNo.required' => 'Parent/Guardian contact number is required.',
            'parentContactNo.regex' => 'Please enter a valid Sri Lankan phone number.',
            'parentAddress.required' => 'Parent/Guardian address is required.',
            'emergencyContactNo.required' => 'Emergency contact number is required.',
            'emergencyContactNo.regex' => 'Please enter a valid Sri Lankan phone number.',
            'userPhoto.image' => 'Photo must be an image file.',
            'userPhoto.mimes' => 'Photo must be a JPEG, PNG, or JPG file.',
            'userPhoto.max' => 'Photo size must not exceed 2MB.',
            'ol_certificate.file' => 'O/L certificate must be a file.',
            'ol_certificate.mimes' => 'O/L certificate must be a PDF, JPG, JPEG, or PNG file.',
            'ol_certificate.max' => 'O/L certificate size must not exceed 5MB.',
            'ol_certificate.required' => 'O/L certificate is required when O/L results are not pending. Please attach the certificate file.',
            'al_certificate.file' => 'A/L certificate must be a file.',
            'al_certificate.mimes' => 'A/L certificate must be a PDF, JPG, JPEG, or PNG file.',
            'al_certificate.max' => 'A/L certificate size must not exceed 5MB.',
            'al_certificate.required' => 'A/L certificate is required when A/L results are not pending. Please attach the certificate file.',
            'otherDocumentsFiles.*.file' => 'Other documents must be files.',
            'otherDocumentsFiles.*.mimes' => 'Other documents must be PDF, JPG, JPEG, PNG, DOC, or DOCX files.',
            'otherDocumentsFiles.*.max' => 'Other documents size must not exceed 5MB.',
            'pending_result.required' => 'Please indicate if you have pending results.',
            'ol_exam_year.integer' => 'O/L exam year must be a valid year.',
            'ol_exam_year.min' => 'O/L exam year must be 1990 or later.',
            'ol_exam_year.max' => 'O/L exam year cannot be in the future.',
            'al_exam_year.integer' => 'A/L exam year must be a valid year.',
            'al_exam_year.min' => 'A/L exam year must be 1990 or later.',
            'al_exam_year.max' => 'A/L exam year cannot be in the future.',
            'z_score_value.numeric' => 'Z-score must be a number.',
            'z_score_value.min' => 'Z-score must be 0 or greater.',
            'z_score_value.max' => 'Z-score cannot exceed 300.',
        ];
    }

    /**
     * Get custom validation attributes.
     */
    public function attributes(): array
    {
        return [
            'nameWithInitials' => 'name with initials',
            'fullName' => 'full name',
            'identificationType' => 'identification type',
            'idValue' => 'ID value',
            'district' => 'district',
            'mobilePhone' => 'mobile phone',
            'whatsappPhone' => 'WhatsApp phone',
            'birthday' => 'date of birth',
            'institute_location' => 'institute location',
            'specialNeeds' => 'special needs',
            'extraCurricular' => 'extracurricular activities',
            'futurePotential' => 'future potential',
            'parentName' => 'parent/guardian name',
            'parentProfession' => 'parent/guardian profession',
            'parentContactNo' => 'parent/guardian contact number',
            'parentEmail' => 'parent/guardian email',
            'parentAddress' => 'parent/guardian address',
            'emergencyContactNo' => 'emergency contact number',
            'userPhoto' => 'photo',
            'ol_certificate' => 'O/L certificate',
            'al_certificate' => 'A/L certificate',
            'otherDocumentsFiles' => 'other documents',
            'pending_result' => 'pending result status',
            'ol_index_no' => 'O/L index number',
            'ol_exam_type' => 'O/L exam type',
            'ol_exam_year' => 'O/L exam year',
            'al_index_no' => 'A/L index number',
            'al_exam_type' => 'A/L exam type',
            'al_exam_year' => 'A/L exam year',
            'al_stream' => 'A/L stream',
            'z_score_value' => 'Z-score',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        $existing = $this->existingStudentPayload();
        if ($existing) {
            $validator->errors()->forget('idValue');
            $validator->errors()->add('idValue', $existing['message']);
        }

        throw new HttpResponseException(response()->json([
            'success' => false,
            'message' => $validator->errors()->first() ?: 'Validation failed.',
            'errors' => $validator->errors(),
            'existing_student' => $existing,
        ], 422));
    }

    private function existingStudentPayload(): ?array
    {
        $idValue = trim((string) $this->input('idValue', ''));
        if ($idValue === '') {
            return null;
        }

        $student = Student::where('id_value', $idValue)->first();
        if (!$student) {
            return null;
        }

        $status = strtolower((string) ($student->academic_status ?: 'active'));
        $statusLabel = $status === 'terminated' ? 'Terminated' : ucfirst($status);
        $name = $student->full_name ?: $student->name_with_initials ?: 'this student';

        return [
            'student_id' => $student->student_id,
            'name' => $name,
            'academic_status' => $student->academic_status,
            'profile_url' => route('student_management.profile', ['studentId' => $student->student_id]),
            'message' => sprintf(
                'This ID is already registered as %s (Student ID %s, status: %s). Do not create a new student. Open the existing Student Profile to re-register, process clearance, or continue termination.',
                $name,
                $student->student_id,
                $statusLabel
            ),
        ];
    }
}
