<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\StudentExam;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class EligibilityCheckingAndRegistrationController extends Controller
{
    public function index()
    {
        return view('eligibility_checking_&_registration');
    }

    public function search(Request $request)
    {
        try {
            $nic = $request->input('nic');
            $courseId = $request->input('course_id');

            $student = Student::where('id_value', $nic)->first();
            $course = Course::find($courseId);

            if (!$student || !$course) {
                return back()->with('error', 'No matching student or course found.');
            }

            return view('eligibility_checking_&_registration', [
                'student' => $student,
                'course' => $course,
                'showDetails' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in eligibility search: ' . $e->getMessage());
            return back()->with('error', 'An error occurred while searching.');
        }
    }

    // Show the eligibility registration view
    public function showEligibilityRegistration()
    {
        return view('registration.eligibility_registration');
    }

    // Fetch courses by location
    public function getCoursesByLocation(Request $request)
    {
        $location = $request->query('location');
        $courseType = $request->query('course_type'); // e.g., 'degree'

        if (!$location) {
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'Location is required.'
            ]);
        }

        $query = Course::where('location', $location);
        if ($courseType) {
            $query->where('course_type', $courseType);
        }
        $courses = $query->orderBy('course_name')->get(['course_id', 'course_name']);

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'message' => $courses->isEmpty() ? 'No courses found.' : 'Courses loaded successfully.'
        ]);
    }

    // Fetch intakes for a course and location
    public function getIntakesForCourseAndLocation($courseId, $location)
    {
        $course = \App\Models\Course::find($courseId);
        if (!$course) {
            return response()->json(['intakes' => []]);
        }
        $intakes = \App\Models\Intake::forCourse($course, $location)
            ->orderBy('batch')
            ->get(['intake_id', 'batch']);
        return response()->json(['intakes' => $intakes]);
    }

    // Fetch eligible students for the table
    public function getEligibleStudents(Request $request)
    {
        $request->validate([
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
        ]);
        $students = CourseRegistration::where('course_id', $request->course_id)
            ->where('intake_id', $request->intake_id)
            ->where('location', $request->location)
            ->with('student')
            ->get()
            ->map(function ($reg) {
                return [
                    'registration_number' => $reg->student->registration_id ?? $reg->student->student_id,
                    'student_id' => $reg->student->student_id,
                    'name' => $reg->student->full_name,
                    'approval_status' => $reg->approval_status,
                ];
            });
        return response()->json(['success' => true, 'students' => $students]);
    }

    // Approve a student
    public function verifyEligibility(Request $request)
    {
        $request->validate(['student_id' => 'required|integer']);

        $registration = CourseRegistration::where('student_id', $request->student_id)
            ->where('status', 'Special approval required')
            ->first();

        if ($registration) {
            $registration->approval_status = 'Approved by manager';
            $registration->status = 'Registered';
            $registration->save();

            return response()->json([
                'success' => true,
                'message' => 'Student approved successfully',
                'registration_id' => $registration->id
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'No pending special approval registration found for this student'
            ]);
        }
    }

    // Get registered courses for a student by NIC
    public function getRegisteredCoursesByNic(Request $request)
    {
        $nic = $request->query('nic');

        $student = \App\Models\Student::where('id_value', $nic)->first();

        if (!$student) {
            return response()->json(['success' => false, 'courses' => [], 'message' => 'Student not found.']);
        }

        // 🔒 Block terminated students
        if (strtolower($student->academic_status ?? '') === 'terminated') {
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'This student is terminated. Please re‑register before proceeding.'
            ], 200);
        }

        $courseRegs = \App\Models\CourseRegistration::where('student_id', $student->student_id)
            ->with('course')
            ->get();

        $courses = $courseRegs->filter(fn($reg) => $reg->course)
            ->map(fn($reg) => [
                'course_id'   => $reg->course->course_id,
                'course_name' => $reg->course->course_name,
                'location'    => $reg->course->location,
            ])
            ->unique('course_id')
            ->values();

        return response()->json(['success' => true, 'courses' => $courses]);
    }


    // Get eligible students for a NIC and course
    public function getEligibleStudentsByNic(Request $request)
    {
        $request->validate([
            'nic' => 'required',
            'course_id' => 'required|exists:courses,course_id',
        ]);

        $student = \App\Models\Student::where('id_value', $request->nic)->first();

        if (!$student) {
            return response()->json([
                'success'  => false,
                'students' => [],
                'message'  => 'Student not found.',
            ]);
        }

        // 🚫 Block terminated students from eligibility flow
        if (strtolower($student->academic_status ?? '') === 'terminated') {
            return response()->json([
                'success'  => false,
                'students' => [],
                'message'  => 'This student is terminated. Please re-register the student before proceeding.',
            ]);
        }

        $reg = \App\Models\CourseRegistration::where('student_id', $student->student_id)
            ->where('course_id', $request->course_id)
            ->first();

        if (!$reg) {
            return response()->json([
                'success'  => false,
                'students' => [],
                'message'  => 'No registration found for this course.',
            ]);
        }

        $students = [[
            'registration_number' => $student->registration_id ?? $student->student_id,
            'student_id'          => $student->student_id,
            'name'                => $student->full_name,
            'approval_status'     => $reg->approval_status,
        ]];

        return response()->json([
            'success'  => true,
            'students' => $students,
        ]);
    }


    public function getStudentExamDetailsByNicCourse(Request $request)
    {
        $request->validate([
            'nic' => 'required',
            'course_id' => 'required|exists:courses,course_id',
        ]);
        $student = \App\Models\Student::where('id_value', $request->nic)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found']);
        }
        $exam = \App\Models\StudentExam::where('student_id', $student->student_id)->first();
        $ol = $al = null;
        if ($exam) {
            $ol = [
                'type' => $exam->ol_exam_type,
                'year' => $exam->ol_exam_year,
                'subjects' => $this->decodeExamSubjects($exam->ol_exam_subjects),
            ];
            $al = [
                'type' => $exam->al_exam_type,
                'year' => $exam->al_exam_year,
                'stream' => $exam->al_exam_stream,
                'subjects' => $this->decodeExamSubjects($exam->al_exam_subjects),
                'remarks' => $exam->remarks ?? '',
            ];
        }
        // Fetch course registration for this student and course
        $registration = \App\Models\CourseRegistration::where('student_id', $student->student_id)
            ->where('course_id', $request->course_id)
            ->first();
        $intake_id = $registration ? $registration->intake_id : null;
        $course_registration_id = $registration ? $registration->course_registration_id : null;
        $intake_batch = null;
        if ($intake_id) {
            $intake = \App\Models\Intake::find($intake_id);
            $intake_batch = $intake ? $intake->batch : null;
        }
        return response()->json([
            'success' => true,
            'student' => [
                'student_id' => $student->student_id,
                'location' => $student->institute_location,
                'full_name' => $student->full_name,
                'nic' => $student->id_value,
                'registration_number' => $student->student_id,
                'ol' => $ol,
                'al' => $al,
                'intake_id' => $intake_id,
                'intake_batch' => $intake_batch,
                'course_registration_id' => $course_registration_id,
            ]
        ]);
    }

    // Special Approval List endpoint
    public function getSpecialApprovalList(Request $request)
    {
        $registrations = CourseRegistration::where('status', 'Special approval required')
            ->where(function ($query) {
                $query->whereNull('approval_status')
                    ->orWhere('approval_status', '!=', 'Rejected');
            })
            ->with(['student', 'course', 'intake'])
            ->get();

        $mappedData = $registrations
            ->map(fn ($reg) => $this->mapSpecialApprovalRegistration($reg))
            ->filter()
            ->values();

        return response()->json(['success' => true, 'students' => $mappedData]);
    }

    // Rejected Special Approval list
    public function getSpecialApprovalRejectedList(Request $request)
    {
        $registrations = CourseRegistration::where('status', 'Special approval required')
            ->where('approval_status', 'Rejected')
            ->with(['student', 'course', 'intake'])
            ->get();

        $mapped = $registrations
            ->map(fn ($reg) => $this->mapSpecialApprovalRegistration($reg, true))
            ->filter()
            ->values();

        return response()->json(['success' => true, 'students' => $mapped]);
    }

    private function mapSpecialApprovalRegistration(CourseRegistration $reg, bool $rejected = false): ?array
    {
        if (!$reg->student) {
            return null;
        }

        $documentUrl = null;
        if ($reg->special_approval_pdf && Storage::disk('public')->exists($reg->special_approval_pdf)) {
            $documentUrl = Storage::disk('public')->url($reg->special_approval_pdf);
            if (!str_starts_with((string) $documentUrl, 'http')) {
                $documentUrl = request()->getScheme() . '://' . request()->getHttpHost() . '/storage/' . $reg->special_approval_pdf;
            }
        }

        $row = [
            'registration_number' => $reg->course_registration_id
                ?? $reg->student->registration_id
                ?? $reg->student->student_id,
            'student_id' => $reg->student->student_id,
            'registration_id' => $reg->id,
            'name' => $reg->student->name_with_initials ?: $reg->student->full_name,
            'nic' => $reg->student->id_value ?? $reg->student->nic_number ?? 'N/A',
            'course_id' => $reg->course_id,
            'course_name' => $reg->course?->course_name ?? 'Unknown Course',
            'intake' => $reg->intake?->batch ?? '—',
            'approval_status' => $reg->approval_status,
            'document_url' => $documentUrl,
            'document_path' => $reg->special_approval_pdf,
            'remarks' => $reg->remarks,
            'dgm_comment' => $reg->dgm_comment,
        ];

        if ($rejected) {
            $row['reason'] = $reg->remarks ?? '—';
            $row['rejected_at'] = optional($reg->updated_at)->format('Y-m-d H:i');
        }

        return $row;
    }

    // Register eligible student
    public function registerEligibleStudent(Request $request)
    {
        $request->validate([
            'nic' => 'required',
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
        ]);

        $student = Student::where('id_value', $request->nic)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        $course = Course::find($request->course_id);
        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Course not found.'], 404);
        }

        // Validate that intake_id belongs to the selected course and location
        $intake = \App\Models\Intake::forCourse($course)->where('intake_id', $request->intake_id)->first();
        if (!$intake) {
            return response()->json([
                'success' => false,
                'message' => 'Selected intake does not belong to the selected course.'
            ], 422);
        }

        if ($course->location && $intake->location && $course->location !== $intake->location) {
            return response()->json([
                'success' => false,
                'message' => 'Selected intake does not match the course location.'
            ], 422);
        }

        // Find or create registration strictly for this student and course (no cross-student fallback)
        $registration = CourseRegistration::where('student_id', $student->student_id)
            ->where('course_id', $course->course_id)
            ->first();

        if (!$registration) {
            $registration = new CourseRegistration([
                'student_id' => $student->student_id,
                'course_id' => $course->course_id,
            ]);
        }

        $registration->intake_id = $intake->intake_id;

        // Assign course_registration_id using intake pattern if not already assigned
        if (!$registration->course_registration_id && $intake->course_registration_id_pattern) {
            $pattern = $intake->course_registration_id_pattern;
            if (preg_match('/^(.*?)(\d+)$/', $pattern, $matches)) {
                $prefix = $matches[1];
                $numberLength = strlen($matches[2]);
                $latest = CourseRegistration::where('intake_id', $intake->intake_id)
                    ->where('course_registration_id', 'like', $prefix . '%')
                    ->orderByDesc('course_registration_id')
                    ->first();
                if ($latest && preg_match('/^(.*?)(\d+)$/', $latest->course_registration_id, $latestMatches)) {
                    $nextNumber = str_pad(((int)$latestMatches[2]) + 1, $numberLength, '0', STR_PAD_LEFT);
                } else {
                    $nextNumber = $matches[2];
                }
                $registration->course_registration_id = $prefix . $nextNumber;
            }
        }

        $registration->status = 'Registered';
        $registration->approval_status = 'Approved by manager';
        $registration->registration_date = now();
        $registration->location = $intake->location ?? $course->location;
        $registration->registration_fee = $course->registration_fee ?? 0;
        $registration->remarks = 'Registered via eligibility page';
        $registration->save();

        return response()->json([
            'success' => true,
            'message' => 'Student registered successfully.',
            'course_registration_id' => $registration->course_registration_id
        ]);
    }

    // Get course entry qualification
    public function getCourseEntryQualification(Request $request)
    {
        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
        ]);
        $course = Course::find($request->course_id);
        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Course not found.']);
        }
        return response()->json([
            'success' => true,
            'course' => [
                'course_name' => $course->course_name,
                'entry_qualification' => $course->entry_qualification,
            ]
        ]);
    }

    // Get course details
    public function getCourseDetails($courseId)
    {
        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Course not found.']);
        }

        // Get the latest intake for this course
        $latestIntake = \App\Models\Intake::forCourse($course)
            ->orderBy('created_at', 'desc')
            ->first();

        $intake = $latestIntake ? $latestIntake->batch : '2025-September';

        return response()->json([
            'success' => true,
            'course' => [
                'course_id' => $course->course_id,
                'course_name' => $course->course_name,
                'location' => $course->location,
                'course_type' => $course->course_type,
                'intake' => $intake,
            ]
        ]);
    }



    // Send special approval request to DGM
    public function sendSpecialApprovalRequest(Request $request)
    {
        // Debug logging
        Log::info('Special approval request received', [
            'request_data' => $request->all(),
            'files' => $request->allFiles(),
            'user' => auth()->user() ? auth()->user()->user_role : 'not authenticated'
        ]);

        $request->validate([
            'nic' => 'required',
            'course_id' => 'required|exists:courses,course_id',
            'special_approval_document' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // 5MB max
            'remarks' => 'nullable|string|max:10000',
        ]);

        $student = \App\Models\Student::where('id_value', $request->nic)->first();
        $course = \App\Models\Course::find($request->course_id);

        if (!$student || !$course) {
            return response()->json(['success' => false, 'message' => 'Student or course not found.']);
        }

        // Handle document upload
        $documentPath = null;
        if ($request->hasFile('special_approval_document')) {
            $file = $request->file('special_approval_document');
            $documentPath = $file->store('special_approvals', 'public');
        }

        // Find or create registration with "Special approval required" status
        $registration = CourseRegistration::firstOrNew([
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
        ]);

        $registration->status = 'Special approval required';
        $registration->approval_status = 'Pending';
        $registration->registration_date = now();
        $registration->location = $course->location;
        $registration->registration_fee = $course->registration_fee ?? 0;
        $registration->special_approval_pdf = $documentPath;
        $registration->remarks = $request->input('remarks', 'Special approval requested via eligibility page');
        $registration->save();

        // Find DGM user(s) and log the request
        $dgms = \App\Models\User::where('user_role', 'DGM')->get();
        if ($dgms->isEmpty()) {
            Log::warning('No DGM user found for special approval request', [
                'student_id' => $student->student_id,
                'course_id' => $course->course_id
            ]);
        } else {
            foreach ($dgms as $dgm) {
                Log::info('Special approval request sent to DGM', [
                    'dgm_id' => $dgm->user_id,
                    'dgm_name' => $dgm->name,
                    'student_id' => $student->student_id,
                    'student_name' => $student->full_name,
                    'course_id' => $course->course_id,
                    'course_name' => $course->course_name,
                    'registration_id' => $registration->id,
                    'document_path' => $documentPath
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Special approval request with document uploaded successfully.',
            'registration_id' => $registration->id
        ]);
    }

    // Get next available course registration ID for an intake
    public function getNextCourseRegistrationId(Request $request)
    {
        // Accept both GET and POST
        $intake_id = $request->input('intake_id');
        if (!$intake_id || !is_numeric($intake_id)) {
            return response()->json(['success' => false, 'message' => 'Intake ID is required and must be numeric.']);
        }
        $intake = \App\Models\Intake::find($intake_id);
        if (!$intake) {
            return response()->json(['success' => false, 'message' => 'Intake not found.']);
        }
        $pattern = $intake->course_registration_id_pattern;
        if (!$pattern) {
            return response()->json(['success' => false, 'message' => 'No pattern set for this intake.']);
        }

        // Extract prefix and numeric part from pattern
        if (preg_match('/^(.*?)(\d+)$/', $pattern, $matches)) {
            $prefix = $matches[1];
            $numberLength = strlen($matches[2]);
            $startNumber = (int)$matches[2];
        } else {
            return response()->json(['success' => false, 'message' => 'Invalid pattern format. Pattern must end with numbers (e.g., 001, 01, 1).']);
        }

        // Find the latest registration ID for this specific intake that matches the prefix
        // Exclude empty or null course_registration_id values
        $latest = \App\Models\CourseRegistration::where('intake_id', $intake->intake_id)
            ->where('course_registration_id', 'like', $prefix . '%')
            ->whereNotNull('course_registration_id')
            ->where('course_registration_id', '!=', '')
            ->orderByDesc('course_registration_id')
            ->first();

        // Log for debugging
        Log::info('Looking for existing registrations', [
            'intake_id' => $intake->intake_id,
            'pattern' => $pattern,
            'prefix' => $prefix,
            'latest_registration' => $latest ? $latest->course_registration_id : 'none',
            'total_registrations_for_intake' => \App\Models\CourseRegistration::where('intake_id', $intake->intake_id)->count()
        ]);

        if ($latest && preg_match('/^(.*?)(\d+)$/', $latest->course_registration_id, $latestMatches)) {
            // If we have existing registrations, increment the number
            $nextNumber = str_pad(((int)$latestMatches[2]) + 1, $numberLength, '0', STR_PAD_LEFT);
        } else {
            // If no existing registrations, use the start number from pattern
            $nextNumber = str_pad($startNumber, $numberLength, '0', STR_PAD_LEFT);
        }

        $nextId = $prefix . $nextNumber;

        // Log for debugging
        Log::info('Generated course registration ID', [
            'intake_id' => $intake_id,
            'pattern' => $pattern,
            'prefix' => $prefix,
            'number_length' => $numberLength,
            'start_number' => $startNumber,
            'next_number' => $nextNumber,
            'next_id' => $nextId,
            'latest_registration' => $latest ? $latest->course_registration_id : 'none'
        ]);

        return response()->json(['success' => true, 'next_id' => $nextId]);
    }

    // Update DGM comment for a special approval request
    public function updateDgmComment(Request $request)
    {
        $request->validate([
            'registration_id' => 'required|exists:course_registration,id',
            'dgm_comment' => 'nullable|string|max:10000',
        ]);

        $registration = CourseRegistration::find($request->registration_id);

        if (!$registration) {
            return response()->json(['success' => false, 'message' => 'Registration not found.']);
        }

        if ($registration->status !== 'Special approval required') {
            return response()->json(['success' => false, 'message' => 'This registration is not pending special approval.']);
        }

        $registration->dgm_comment = $request->input('dgm_comment');
        $registration->save();

        return response()->json([
            'success' => true,
            'message' => 'DGM comment updated successfully.',
            'dgm_comment' => $registration->dgm_comment
        ]);
    }

    public function getStudentData(Request $request)
    {
        $nic = $request->input('nic') ?? $request->input('id_value') ?? $request->input('student_id');
        $student = Student::where('id_value', $nic)
            ->orWhere('student_id', $nic)
            ->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        return response()->json([
            'success' => true,
            'student' => $student,
            'data' => $student
        ]);
    }

    public function checkApproval(Request $request)
    {
        $nic = $request->input('nic') ?? $request->input('student_id');
        $courseId = $request->input('course_id');

        $student = Student::where('id_value', $nic)
            ->orWhere('student_id', $nic)
            ->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        $registration = CourseRegistration::where('student_id', $student->student_id)
            ->when($courseId, fn($q) => $q->where('course_id', $courseId))
            ->orderByDesc('id')
            ->first();

        return response()->json([
            'success' => true,
            'approved' => $registration && in_array($registration->approval_status, ['Approved by manager', 'Approved by DGM', 'approved']),
            'status' => $registration->approval_status ?? 'No request',
            'registration' => $registration
        ]);
    }

    private function decodeExamSubjects(mixed $value): array
    {
        if (is_array($value)) {
            $subjects = $value;
        } elseif (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            $subjects = is_array($decoded) ? $decoded : [];
        } else {
            $subjects = [];
        }

        return array_values(array_map(function ($subject) {
            if (!is_array($subject)) {
                return [
                    'subject' => (string) $subject,
                    'result' => 'N/A',
                ];
            }

            return [
                'subject' => $subject['subject'] ?? $subject['name'] ?? $subject['title'] ?? 'N/A',
                'result' => $subject['result'] ?? $subject['grade'] ?? $subject['mark'] ?? 'N/A',
            ];
        }, $subjects));
    }
}
