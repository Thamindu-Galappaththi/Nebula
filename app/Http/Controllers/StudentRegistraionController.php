<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\StudentRegistrationRequest;
use App\Models\Student;
use App\Models\ParentGuardian;
use App\Models\StudentExam;
use App\Models\Course;
use App\Services\FileManagementService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StudentRegistraionController extends Controller
{
    protected $fileService;

    public function __construct(FileManagementService $fileService)
    {
        $this->fileService = $fileService;
    }

    // Method to show the student registration view
    public function showStudentRegistration()
    {
        // Check if user is authenticated
        if (Auth::check()) {
            // Define static data for dropdowns based on the actual database enums
            $titles = [
                ['TitleID' => 'Mr', 'TitleName' => 'Mr'],
                ['TitleID' => 'Mrs', 'TitleName' => 'Mrs'],
                ['TitleID' => 'Miss', 'TitleName' => 'Miss'],
                ['TitleID' => 'Dr', 'TitleName' => 'Dr'],
                ['TitleID' => 'Rev', 'TitleName' => 'Rev'],
                ['TitleID' => 'Ms', 'TitleName' => 'Ms'],
                ['TitleID' => 'Other', 'TitleName' => 'Other']
            ];

            $genders = [
                ['id' => 'Male', 'name' => 'Male'],
                ['id' => 'Female', 'name' => 'Female']
            ];

            $idTypes = [
                ['id' => 'National id', 'id_type' => 'National ID'],
                ['id' => 'Postal id', 'id_type' => 'Postal ID'],
                ['id' => 'Passport', 'id_type' => 'Passport'],
                ['id' => 'Driving Licence', 'id_type' => 'Driving Licence']
            ];

            $campuses = [
                ['id' => 'Welisara', 'name' => 'Nebula Institute of Technology - Welisara'],
                ['id' => 'Moratuwa', 'name' => 'Nebula Institute of Technology - Moratuwa'],
                ['id' => 'Peradeniya', 'name' => 'Nebula Institute of Technology - Peradeniya']
            ];

            $btecCourses = [
                ['id' => 1, 'course_name' => 'BTEC Level 3 Extended Diploma in Business'],
                ['id' => 2, 'course_name' => 'BTEC Level 3 Extended Diploma in Computing'],
                ['id' => 3, 'course_name' => 'BTEC Level 3 Extended Diploma in Engineering'],
                ['id' => 4, 'course_name' => 'BTEC Level 3 Extended Diploma in Health and Social Care'],
                ['id' => 5, 'course_name' => 'BTEC Level 3 Extended Diploma in Travel and Tourism']
            ];

            $districts = [
                'Ampara',
                'Anuradhapura',
                'Badulla',
                'Batticaloa',
                'Colombo',
                'Galle',
                'Gampaha',
                'Hambantota',
                'Jaffna',
                'Kalutara',
                'Kandy',
                'Kegalle',
                'Kilinochchi',
                'Kurunegala',
                'Mannar',
                'Matale',
                'Matara',
                'Monaragala',
                'Mullaitivu',
                'Nuwara Eliya',
                'Polonnaruwa',
                'Puttalam',
                'Ratnapura',
                'Trincomalee',
                'Vavuniya'
            ];


            // Get exam types from StudentExam model
            $examTypes = StudentExam::getExamTypes();

            return view('student_management..student_registration', compact('titles', 'genders', 'idTypes', 'campuses', 'districts', 'btecCourses', 'examTypes'));
        } else {
            return redirect()->route('login')->with('error', 'You are not authorized to access this page.');
        }
    }

    public function register(StudentRegistrationRequest $request)
    {
        try {
            // Upload files using FileManagementService
            $otherDocs = $request->file('otherDocumentsFiles');
            $photo = $request->file('userPhoto');
            $olCert = $request->file('ol_certificate');
            $alCert = $request->file('al_certificate');

            $otherDocNames = [];
            $uploadErrors = [];

            if ($otherDocs) {
                foreach ($otherDocs as $doc) {
                    try {
                        $result = $this->fileService->uploadFile($doc, 'documents', 'student_docs');
                        $otherDocNames[] = $result['filename'];
                    } catch (\Exception $e) {
                        Log::error('Failed to upload other document', [
                            'original_name' => $doc->getClientOriginalName(),
                            'error' => $e->getMessage()
                        ]);
                        $uploadErrors[] = 'Failed to upload: ' . $doc->getClientOriginalName();
                    }
                }
            }

            $photoName = null;
            if ($photo) {
                try {
                    $result = $this->fileService->uploadFile($photo, 'photos', 'photos');
                    $photoName = $result['filename'];
                } catch (\Exception $e) {
                    Log::error('Failed to upload photo', [
                        'original_name' => $photo->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                    $uploadErrors[] = 'Failed to upload photo';
                }
            }

            $olCertName = null;
            if ($olCert) {
                try {
                    $result = $this->fileService->uploadFile($olCert, 'certificates', 'certificates');
                    $olCertName = $result['filename'];
                } catch (\Exception $e) {
                    Log::error('Failed to upload O/L certificate', [
                        'original_name' => $olCert->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                    $uploadErrors[] = 'Failed to upload O/L certificate';
                }
            }

            $alCertName = null;
            if ($alCert) {
                try {
                    $result = $this->fileService->uploadFile($alCert, 'certificates', 'certificates');
                    $alCertName = $result['filename'];
                } catch (\Exception $e) {
                    Log::error('Failed to upload A/L certificate', [
                        'original_name' => $alCert->getClientOriginalName(),
                        'error' => $e->getMessage()
                    ]);
                    $uploadErrors[] = 'Failed to upload A/L certificate';
                }
            }

            // If there are upload errors, return them but continue with registration
            if (!empty($uploadErrors)) {
                Log::warning('File upload errors during student registration', ['errors' => $uploadErrors]);
            }

            // Create student record with only the fields that exist in the database
            $student = new Student();
            $student->title = $request->title;
            $student->name_with_initials = $request->nameWithInitials;
            $student->full_name = $request->fullName;
            $student->gender = $request->gender;
            $student->id_type = $request->identificationType;
            $student->id_value = $request->idValue;
            $student->address = $request->address;
            $student->district = $request->district;
            $student->email = $request->email;
            $student->mobile_phone = $request->mobilePhone;
            $student->home_phone = $request->homePhone;
            $student->whatsapp_phone = $request->whatsappPhone;
            $student->birthday = $request->birthday;
            $student->institute_location = $request->institute_location;
            $student->foundation_program = $request->boolean('foundationComplete');
            $student->btec_completed = $request->boolean('btecCompleted');
            $student->special_needs = $request->specialNeeds ?? null;
            $student->extracurricular_activities = $request->extraCurricular ?? null;
            $student->future_potentials = $request->futurePotential ?? null;
            $student->other_document_upload = !empty($otherDocNames) ? implode(',', $otherDocNames) : null;
            $student->remarks = $request->remarks ?? null;
            $student->status = 'Unmarried'; // Default status as per database enum

            if ($request->marketing_survey === 'Other' && $request->filled('marketing_survey_other')) {
                $student->marketing_survey = $request->marketing_survey_other;
            } else {
                $student->marketing_survey = $request->marketing_survey;
            }


            $student->save();

            // Create parent/guardian record
            $parentGuardian = new ParentGuardian();
            $parentGuardian->student_id = $student->student_id;
            $parentGuardian->guardian_name = $request->parentName;
            $parentGuardian->guardian_profession = $request->parentProfession ?? null;
            $parentGuardian->guardian_contact_number = $request->parentContactNo;
            $parentGuardian->guardian_email = $request->parentEmail ?? null;
            $parentGuardian->guardian_address = $request->parentAddress;
            $parentGuardian->emergency_contact_number = $request->emergencyContactNo;

            $parentGuardian->save();

            if ($request->pending_result === 'no') {
    $studentExam = new StudentExam();
    $studentExam->student_id = $student->student_id;

    // O/L Details
    if ($request->ol_index_no || $request->ol_exam_type || $request->ol_exam_year) {
        $studentExam->ol_index_no = $request->ol_index_no;
        $studentExam->ol_exam_type = $request->ol_exam_type;
        $studentExam->ol_exam_year = $request->ol_exam_year;
        $studentExam->ol_certificate = $olCertName ? basename($olCertName) : null;

        $olSubjects = [];
        if ($request->has('ol_subjects') && $request->has('ol_results')) {
            foreach ($request->ol_subjects as $index => $subject) {
                $olSubjects[] = [
                    'subject' => $subject,
                    'result' => $request->ol_results[$index] ?? 'N/A',
                ];
            }
        }
        $studentExam->ol_exam_subjects = json_encode($olSubjects); // Always set, even if empty
    }

    // A/L Details
    if ($request->al_index_no || $request->al_exam_type || $request->al_exam_year) {
        $studentExam->al_index_no = $request->al_index_no;
        $studentExam->al_exam_type = $request->al_exam_type;
        $studentExam->al_exam_year = $request->al_exam_year;
        $studentExam->al_exam_stream = $request->al_stream;
        $studentExam->z_score_value = $request->z_score_value ?? null;
        $studentExam->al_certificate = $alCertName ? basename($alCertName) : null;

        $alSubjects = [];
        if ($request->has('al_subjects') && $request->has('al_results')) {
            foreach ($request->al_subjects as $index => $subject) {
                $alSubjects[] = [
                    'subject' => $subject,
                    'result' => $request->al_results[$index] ?? 'N/A',
                ];
            }
        }
        $studentExam->al_exam_subjects = json_encode($alSubjects); // Always set, even if empty
    }

    $studentExam->save();
}

            $message = 'Student registered successfully!';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'student_id' => $student->student_id,
                ]);
            }

            return redirect()->route('student_management.registration')->with('success', $message);

        } catch (QueryException $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Database error occurred. Please try again.'], 500);
            }

            return redirect()->back()->withErrors(['Database error occurred. Please try again.'])->withInput();
        } catch (\Exception $e) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'An error occurred. Please try again.'], 500);
            }

            return redirect()->back()->withErrors(['An error occurred. Please try again.'])->withInput();
        }
    }

    public function getCoursesByLocation($location)
    {
        if (!$location) {
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'Location is required.'
            ], 400);
        }
        try {
            $courses = Course::select('course_id', 'course_name')
                ->where('location', $location)
                ->orderBy('course_name', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'courses' => $courses,
                'message' => $courses->isEmpty() ? 'No courses found for this location.' : 'Courses loaded successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching courses by location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'An error occurred while fetching courses.'
            ], 500);
        }
    }

    public function getSubjectsByExamType($examTypeId)
    {
        $subjects = [
            'Local' => ['Sinhala', 'History', 'Religion', 'English', 'Maths', 'Science', 'Other'],
            'London Cambridge' => ['English Language', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Accounting', 'Business Studies', 'Economics', 'Computer Science', 'Other'],
            'London Edexcel' => ['English Language', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Accounting', 'Business Studies', 'Economics', 'Computer Science', 'Other'],
            'Other' => ['Other'],
        ];

        $list = $subjects[$examTypeId] ?? $subjects['Local'];

        return response()->json([
            'success' => true,
            'data' => $list,
            'subjects' => $list
        ]);
    }

    public function getStreamsByExamType($examTypeId)
    {
        $streams = [
            'Local' => ['Physical Science', 'Bio Science', 'Arts', 'Commerce', 'Technology', 'Other'],
            'London Cambridge' => ['Sciences', 'Mathematics', 'Arts & Humanities', 'Business & Economics', 'Technology', 'Other'],
            'London Edexcel' => ['Sciences', 'Mathematics', 'Arts & Humanities', 'Business & Economics', 'Technology', 'Other'],
            'Other' => ['Other'],
        ];

        $list = $streams[$examTypeId] ?? $streams['Local'];

        return response()->json([
            'success' => true,
            'data' => $list,
            'streams' => $list
        ]);
    }
}



