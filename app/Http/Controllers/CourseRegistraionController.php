<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Student;
use App\Models\Intake;
use App\Models\CourseRegistration;
use App\Models\StudentExam;
use App\Models\PaymentDetail;
use App\Models\SemesterRegistration;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class CourseRegistraionController extends Controller
{
    public function checkStudents(Request $request)
    {
        try {
            // Get the courseId and studentIds from the request
            $courseId = $request->input('courseId');
            $studentIds = $request->input('studentIds');

            // Initialize an array to store the registration status of each student
            $studentStatus = [];

            // Loop through each student ID
            foreach ($studentIds as $studentId) {
                // Check if the student is registered for the specified course
                $registered = CourseRegistration::where('student_id', $studentId)
                    ->where('course_id', $courseId)
                    ->exists();

                // Push the student ID and registration status to the array
                $studentStatus[] = [
                    'student_id' => $studentId,
                    'registered' => $registered ? true : false,
                ];
            }

            // Return the student IDs and registration status as JSON response
            return response()->json(['success' => true, 'data' => $studentStatus]);
        } catch (\Exception $e) {
            // Log the error
            Log::error('Error checking students: ' . $e->getMessage());

            // Return an error response
            return response()->json(['success' => false, 'message' => 'An error occurred while checking students']);
        }
    }

    public function showCourseRegistration(Request $request)
    {
        // Fetch the student exam record (adjust as per your logic)
        $studentExam = StudentExam::where('student_id', $request->student_id)->first();

        // Default to empty array if not found
        $olSubjects = [];
        $alSubjects = [];

        if ($studentExam) {
            // First decode to get the string, then decode again to get the array
            $olSubjectsRaw = json_decode($studentExam->ol_exam_subjects, true);
            if (is_string($olSubjectsRaw)) {
                $olSubjects = json_decode($olSubjectsRaw, true) ?? [];
            } else {
                $olSubjects = $olSubjectsRaw ?? [];
            }

            $alSubjectsRaw = json_decode($studentExam->al_exam_subjects, true);
            if (is_string($alSubjectsRaw)) {
                $alSubjects = json_decode($alSubjectsRaw, true) ?? [];
            } else {
                $alSubjects = $alSubjectsRaw ?? [];
            }
        }

        // Pass to view
        return view('registration.course_registration', [
            // ...other variables...
            'olSubjects' => $olSubjects,
            'alSubjects' => $alSubjects,
        ]);
    }

    public function getCoursesByLocation($location = null, Request $request = null)
    {
        if ($request instanceof Request) {
            $location = $location ?? $request->query('location') ?? $request->input('location');
        }

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

            \Log::info('Requested location:', ['location' => $location]);
            \Log::info('Courses found:', ['courses' => $courses]);

            return response()->json([
                'success' => $courses->isNotEmpty(),
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

    public function batchDropdownOptions(Request $request)
    {
        // Validate the incoming request data
        $request->validate([
            'registered_from' => 'required|date',
            'registered_to' => 'required|date',
            'courseSearch' => 'required|exists:courses,course_id',
        ]);

        // Retrieve the date range and course ID from the request
        $registeredFrom = $request->input('registered_from');
        $registeredTo = $request->input('registered_to');
        $courseId = $request->input('courseSearch');

        // Retrieve all intake details relevant to the given course
        $intakeDetails = Intake::where('course_id', $courseId)->get();

        Log::info('Intake details retrieved', ['courseId' => $courseId, 'intakeDetails' => $intakeDetails->toArray()]);

        $intakesForCourse = [];

        if ($intakeDetails->isNotEmpty()) {
            // Return intake details
            $intakesForCourse = $intakeDetails->toArray();
            Log::info('Intakes for course retrieved', ['intakesForCourse' => $intakesForCourse]);
        } else {
            Log::info('No intake details found for the given course and date range.');
        }

        return response()->json([
            'intakesForCourse' => $intakesForCourse,
        ]);
    }

    public function checkBlacklistStatus(Request $request)
    {
        $indexNo = $request->query('index_no');

        if (!$indexNo) {
            Log::warning('Missing index number in request');
            return response()->json(['error' => 'Index number is required'], 400);
        }

        try {
            $isBlacklisted = DB::table('students')
                ->where('registration_id', $indexNo)
                ->value('is_blacklisted');

            if ($isBlacklisted === null) {
                Log::info('Student not found in database:', ['index_no' => $indexNo]);
                return response()->json(['error' => 'Student not found'], 404);
            }

            Log::info('Blacklist status retrieved:', [
                'index_no' => $indexNo,
                'is_blacklisted' => $isBlacklisted,
            ]);

            return response()->json([
                'is_blacklisted' => (int)$isBlacklisted === 1, // Compare value
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching blacklist status:', [
                'message' => $e->getMessage(),
            ]);

            return response()->json(['error' => 'An error occurred while checking blacklist status.'], 500);
        }
    }

    public function checkStudentExists(Request $request)
    {
        if (Auth::check() && Auth::user()->status) {
            try {
                // Validate the incoming request data
                $request->validate([
                    'registered_from' => 'required|date',
                    'registered_to' => 'required|date',
                    'courseSearch' => 'required|exists:courses,course_id',
                ]);

                // Retrieve the date range and course ID from the request
                $registeredFrom = $request->input('registered_from');
                $registeredTo = $request->input('registered_to');
                $courseId = $request->input('courseSearch');
                $userLocation = Auth::user()->user_location; // Get the user's location

                // Check if the registeredFrom and registeredTo dates are the same
                if ($registeredFrom == $registeredTo) {
                    // Fetch students created on the specific date and in the user's location
                    $students = Student::whereDate('created_at', $registeredFrom)
                        ->where('institute_location', $userLocation)
                        ->select('student_id', 'registration_id', 'name_with_initials', 'id_type', 'id_value')
                        ->get();
                } else {
                    // Fetch students created within the specified date range and in the user's location
                    $students = Student::whereBetween('created_at', [$registeredFrom, $registeredTo])
                        ->where('institute_location', $userLocation)
                        ->select('student_id', 'registration_id', 'name_with_initials', 'id_type', 'id_value')
                        ->get();
                }

                // Check if each student is registered for the course
                $students->each(function ($student) use ($courseId) {
                    $student->registered = CourseRegistration::where('student_id', $student->student_id)
                        ->where('course_id', $courseId)
                        ->exists() ? 'yes' : 'no';
                });

                // Count the number of students
                $studentCount = $students->count();

                // Check if student count is 0
                if ($studentCount === 0) {
                    // Return a JSON response with success false and custom message
                    return response()->json([
                        'success' => false,
                        'message' => 'Students not found',
                    ]);
                }

                // Prepare the response data
                $responseData = [
                    'success' => true,
                    'message' => 'Students found',
                    'students' => $students,
                    'studentCount' => $studentCount, // Include the student count in the response
                ];

                // Return the JSON response
                return response()->json($responseData);
            } catch (\Exception $e) {
                // Log the error with detailed information
                Log::error('Error in checkStudentExists method: ' . $e->getMessage(), [
                    'exception' => $e,
                    'trace' => $e->getTraceAsString()
                ]);

                // Return a generic error message
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred. Please try again later.'
                ]);
            }
        } else {
            // Redirect to login or show an error message
            return redirect()->route('login')->with('error', 'You are not authorized to access this page.');
        }
    }

    public function storeCourseRegistration(Request $request)
    {
        if (Auth::check() && Auth::user()->status) {
            try {
                // Validate the incoming request data
                $validatedData = $request->validate([
                    'studentId' => 'required|exists:students,student_id',
                    'course' => 'required|exists:courses,course_id',
                    'location' => 'required|string',
                    'sltEmployee' => 'required|string|in:yes,no',
                    'serviceNo' => 'nullable|string|max:255',
                    'counselorName' => 'nullable|string|max:255',
                    'counselorNic' => 'nullable|string|max:255',
                    'counselorPhone' => 'nullable|string|max:255',
                    'options' => 'nullable|string',
                    'surveyNo' => 'required|numeric',
                    'registrationFee' => 'required|numeric',
                    'courseStartDate' => 'required|date',
                    'intakeId' => 'required|exists:intakes,intake_id', // Add intake validation
                ]);

                // Check if the student is already registered for the course.
                $isAlreadyRegistered = CourseRegistration::where('student_id', $validatedData['studentId'])
                    ->where('course_id', $validatedData['course'])
                    ->whereNotIn('approval_status', ['Rejected', 'rejected'])
                    ->whereNotIn('status', ['Not eligible', 'not eligible', 'Cancelled', 'cancelled'])
                    ->exists();

                if ($isAlreadyRegistered) {
                    $blocking = CourseRegistration::where('student_id', $validatedData['studentId'])
                        ->where('course_id', $validatedData['course'])
                        ->whereNotIn('approval_status', ['Rejected', 'rejected'])
                        ->whereNotIn('status', ['Not eligible', 'not eligible', 'Cancelled', 'cancelled'])
                        ->first();

                    return response()->json([
                        'success' => false,
                        'message' => 'The student is already registered for this course.',
                        'blocking_registration' => $blocking
                    ], 400);
                }

                // Get the default intake for the course if not provided
                $intakeId = $validatedData['intakeId'];

                // Create a new CourseRegistration instance
                $courseRegistration = new CourseRegistration();

                // Assign the validated data to the CourseRegistration instance
                $courseRegistration->student_id = $validatedData['studentId'];
                $courseRegistration->course_id = $validatedData['course'];
                $courseRegistration->intake_id = $intakeId; // Assign intake_id
                $courseRegistration->registration_date = now();
                $courseRegistration->registration_fee = $validatedData['registrationFee'];
                $courseRegistration->status = 'Pending';
                $courseRegistration->approval_status = 'Pending';
                $courseRegistration->location = $validatedData['location'];
                $courseRegistration->slt_employee = ($validatedData['sltEmployee'] === 'yes') ? 1 : 0;
                $courseRegistration->employee_service_number = $validatedData['serviceNo'];
                $courseRegistration->counselor_name = $validatedData['counselorName'] ?? null;
                $courseRegistration->counselor_nic = $validatedData['counselorNic'] ?? null;
                $courseRegistration->counselor_phone = $validatedData['counselorPhone'] ?? null;
                $courseRegistration->course_start_date = $validatedData['courseStartDate'];
                $courseRegistration->remarks = 'Pre-registration via web form';

                // Save the CourseRegistration instance
                $courseRegistration->save();

                // Update student's marketing survey
                $student = Student::find($validatedData['studentId']);
                if ($student) {
                    $surveyOptions = trim($validatedData['options'] ?? '');
                    $student->marketing_survey = $surveyOptions !== '' ? $surveyOptions : null;
                    $student->save();
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Course registration completed successfully.',
                    'registration_id' => $courseRegistration->id
                ]);
            } catch (\Illuminate\Validation\ValidationException $e) {
                Log::error('Validation error in storeCourseRegistration: ' . json_encode($e->errors()));
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            } catch (\Exception $e) {
                Log::error('Error in storeCourseRegistration: ' . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing the registration.'
                ], 500);
            }
        } else {
            return redirect()->route('login')->with('error', 'You are not authorized to access this page.');
        }
    }

    // API method to get student by NIC
    public function getStudentByNic($nic)
    {
        try {
            // Find student by NIC (id_value)
            $student = Student::where('id_value', $nic)->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found'
                ], 404);
            }

            // Determine latest semester registration and termination status
            $latestSemReg = SemesterRegistration::where('student_id', $student->student_id)
                ->latest('id')
                ->first();

            $isTerminated = $student->academic_status === Student::ACADEMIC_TERMINATED
                || ($latestSemReg && strtolower((string) $latestSemReg->status) === 'terminated');

            // Get student exam details
            $studentExam = StudentExam::where('student_id', $student->student_id)->first();

            // Prepare O/L exam data
            $ol_exams = [];
            if ($studentExam && $studentExam->ol_exam_type) {
                $ol_exams[] = [
                    'exam_type' => ['exam_type' => $studentExam->ol_exam_type],
                    'exam_year' => $studentExam->ol_exam_year,
                    'subjects'  => $this->decodeExamSubjects($studentExam->ol_exam_subjects),
                ];
            }

            // Prepare A/L exam data
            $al_exams = [];
            if ($studentExam && $studentExam->al_exam_type) {
                $al_exams[] = [
                    'exam_type' => ['exam_type' => $studentExam->al_exam_type],
                    'exam_year' => $studentExam->al_exam_year,
                    'stream'    => ['stream' => $studentExam->al_exam_stream],
                    'z_score'   => $studentExam->z_score_value,
                    'subjects'  => $this->decodeExamSubjects($studentExam->al_exam_subjects),
                ];
            }

            return response()->json([
                'success' => true,
                'student' => [
                    'student_id'        => $student->student_id,
                    'name_with_initials' => $student->name_with_initials,
                    'id_value'          => $student->id_value,
                    'registration_id'   => $student->student_id, // kept for compatibility
                    'status'            => $student->academic_status ?? $student->status ?? null,
                    'blacklisted'       => (bool) $student->blacklisted,
                ],
                'ol_exams' => $ol_exams,
                'al_exams' => $al_exams,
                'is_terminated' => $isTerminated,
                'is_blacklisted' => (bool) $student->blacklisted,
                'latest_semester_registration' => $latestSemReg
            ]);
        } catch (\Exception $e) {
            \Log::error('Error getting student by NIC: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching student data'
            ], 500);
        }
    }


    // API method to get intakes by course
    public function getIntakesByCourse($courseId)
    {
        try {
            $intakes = Intake::where('course_id', $courseId)
                ->where('start_date', '>=', now())
                ->orderBy('start_date', 'asc')
                ->get();

            return response()->json([
                'success' => true,
                'intakes' => $intakes
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting intakes by course: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching intakes'
            ], 500);
        }
    }

    // API method to get intakes by course and location
    public function getIntakesForCourseAndLocation($courseName, $location)
    {
        try {
            $course = null;
            if (is_numeric($courseName)) {
                $course = Course::where('course_id', $courseName)
                    ->where('location', $location)
                    ->first()
                    ?? Course::where('course_id', $courseName)->first();
            } else {
                $course = Course::where('course_name', $courseName)
                    ->where('location', $location)
                    ->first();

                if (!$course) {
                    $course = Course::where('course_name', $courseName)->first();
                }
            }

            if ($course) {
                $intakes = Intake::forCourse($course, $location)
                    ->orderBy('batch')
                    ->get(['intake_id', 'batch', 'start_date', 'registration_fee']);
            } else {
                // Legacy fallback if no matching course record exists
                $intakes = Intake::where('course_name', $courseName)
                    ->where('location', $location)
                    ->orderBy('batch')
                    ->get(['intake_id', 'batch', 'start_date', 'registration_fee']);
            }

            return response()->json([
                'success' => true,
                'intakes' => $intakes
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting intakes by course and location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'intakes' => []
            ]);
        }
    }

    // Updated API method for course registration
    public function storeCourseRegistrationAPI(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'student_id' => 'required|exists:students,student_id',
                'course_id' => 'required|exists:courses,course_id',
                'intake_id' => 'required|exists:intakes,intake_id',
                'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
                'slt_employee' => 'required|in:yes,no',
                'service_no' => 'nullable|string|max:255',
                'counselor_name' => 'nullable|string|max:255',
                'counselor_id' => 'nullable|string|max:255',
                'counselor_phone' => 'nullable|string|max:255',
                'counselor_nic' => 'nullable|string|max:255',
                'marketing_survey_options' => 'nullable|array',
                'course_start_date' => 'required|date',
                'registration_fee' => 'required|numeric|min:0',
            ]);

            // Check if the student is already registered for the course.
            $isAlreadyRegistered = CourseRegistration::where('student_id', $validatedData['student_id'])
                ->where('course_id', $validatedData['course_id'])
                ->whereNotIn('approval_status', ['Rejected', 'rejected'])
                ->whereNotIn('status', ['Not eligible', 'not eligible', 'Cancelled', 'cancelled'])
                ->exists();

            if ($isAlreadyRegistered) {
                $blocking = CourseRegistration::where('student_id', $validatedData['student_id'])
                    ->where('course_id', $validatedData['course_id'])
                    ->whereNotIn('approval_status', ['Rejected', 'rejected'])
                    ->whereNotIn('status', ['Not eligible', 'not eligible', 'Cancelled', 'cancelled'])
                    ->first();

                return response()->json([
                    'success' => false,
                    'message' => 'The student is already registered for this course.',
                    'blocking_registration' => $blocking
                ], 400);
            }

            // Prevent registration if the student's latest semester registration is terminated
            $student = Student::find($validatedData['student_id']);
            if ($student) {
                $latestSemReg = SemesterRegistration::where('student_id', $student->student_id)->latest('id')->first();
                if ($latestSemReg && strtolower($latestSemReg->status) === 'terminated') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot register student: student is terminated.'
                    ], 422);
                }
            }

            // Create a new CourseRegistration instance
            $courseRegistration = new CourseRegistration();

            // Assign the validated data to the CourseRegistration instance
            $courseRegistration->student_id = $validatedData['student_id'];
            $courseRegistration->course_id = $validatedData['course_id'];
            $courseRegistration->intake_id = $validatedData['intake_id'];
            $courseRegistration->registration_date = now();
            $courseRegistration->registration_fee = $validatedData['registration_fee'];
            $courseRegistration->status = 'Pending';
            $courseRegistration->approval_status = 'Pending';
            $courseRegistration->location = $validatedData['location'];
            $courseRegistration->slt_employee = ($validatedData['slt_employee'] === 'yes') ? 1 : 0;
            $courseRegistration->employee_service_number = $validatedData['service_no'];
            $courseRegistration->course_start_date = $validatedData['course_start_date'];
            $courseRegistration->remarks = 'Pre-registration via web form';

            // Add counselor information if SLT employee is 'no'
            if ($validatedData['slt_employee'] === 'no') {
                $courseRegistration->counselor_name = $validatedData['counselor_name'] ?? null;
                $courseRegistration->counselor_id = $validatedData['counselor_id'] ?? null;
                $courseRegistration->counselor_phone = $validatedData['counselor_phone'] ?? null;
                $courseRegistration->counselor_nic = $validatedData['counselor_nic'] ?? null;
            }

            // Save the CourseRegistration instance
            $courseRegistration->save();

            // Update student's marketing survey - join array with comma if provided
            $student = Student::find($validatedData['student_id']);
            if ($student) {
                $marketingSurvey = null;
                if (!empty($validatedData['marketing_survey_options']) && is_array($validatedData['marketing_survey_options'])) {
                    $marketingSurvey = implode(', ', $validatedData['marketing_survey_options']);
                    // Truncate to avoid DB column length issues
                    $marketingSurvey = mb_substr($marketingSurvey, 0, 255);
                }
                $student->marketing_survey = $marketingSurvey;
                $student->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Course registration completed successfully.',
                'registration_id' => $courseRegistration->id
            ]);
        } catch (ValidationException $e) {
            // Return validation errors as 422 so frontend can display them correctly
            Log::error('Validation error in storeCourseRegistrationAPI: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in storeCourseRegistrationAPI: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the registration.'
            ], 500);
        }
    }

    public function getYears()
    {
        // Example: Return years from 2020 to current year
        $years = range(2020, date('Y'));
        return response()->json(['years' => $years]);
    }

    public function getSemestersByYear(Request $request)
    {
        $year = $request->query('year');
        $courseId = $request->query('course_id');
        $intakeId = $request->query('intake_id');

        if (!$courseId || !$intakeId) {
            return response()->json(['semesters' => []]);
        }

        // Get actual created semesters for this course and intake
        $semesters = \App\Models\Semester::where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->whereIn('status', ['active', 'upcoming'])
            ->select('id', 'name')
            ->get();

        return response()->json(['semesters' => $semesters]);
    }

    public function getIntakes(Request $request)
    {
        $request->validate([
            'course_name' => 'required|string',
            'location' => 'required|string',
        ]);

        $courseName = $request->input('course_name');
        $location = $request->input('location');

        $course = \App\Models\Course::where('course_name', $courseName)
            ->where('location', $location)
            ->first();

        if (!$course) {
            // Fallback for older data inconsistencies.
            $course = \App\Models\Course::where('course_name', $courseName)->first();
        }

        if ($course) {
            $intakes = \App\Models\Intake::forCourse($course, $location)
                ->orderBy('start_date', 'asc')
                ->get(['intake_id', 'batch', 'start_date', 'registration_fee']);
        } else {
            // Legacy fallback if no matching course record
            $intakes = \App\Models\Intake::where('course_name', $courseName)
                ->where('location', $location)
                ->orderBy('start_date', 'asc')
                ->get(['intake_id', 'batch', 'start_date', 'registration_fee']);
        }

        return response()->json($intakes);
    }

    // API method for saving course registration for eligibility checking
    public function storeCourseRegistrationForEligibilityAPI(Request $request)
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'student_id' => 'required|exists:students,student_id',
                'course_id' => 'required|exists:courses,course_id',
                'intake_id' => 'required|exists:intakes,intake_id',
                'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
                'slt_employee' => 'required|in:yes,no',
                'service_no' => 'nullable|string|max:255',
                'counselor_name' => 'nullable|string|max:255',
                'counselor_id' => 'nullable|string|max:255',
                'counselor_phone' => 'nullable|string|max:255',
                'counselor_nic' => 'nullable|string|max:255',
                'marketing_survey_options' => 'nullable|array',
                'course_start_date' => 'required|date',
                'registration_fee' => 'required|numeric|min:0',
            ]);

            // Check if the student is already registered for the course.
            $isAlreadyRegistered = CourseRegistration::where('student_id', $validatedData['student_id'])
                ->where('course_id', $validatedData['course_id'])
                ->whereNotIn('approval_status', ['Rejected', 'rejected'])
                ->whereNotIn('status', ['Not eligible', 'not eligible', 'Cancelled', 'cancelled'])
                ->exists();

            if ($isAlreadyRegistered) {
                $blocking = CourseRegistration::where('student_id', $validatedData['student_id'])
                    ->where('course_id', $validatedData['course_id'])
                    ->whereNotIn('approval_status', ['Rejected', 'rejected'])
                    ->whereNotIn('status', ['Not eligible', 'not eligible', 'Cancelled', 'cancelled'])
                    ->first();

                return response()->json([
                    'success' => false,
                    'message' => 'The student is already registered for this course.',
                    'blocking_registration' => $blocking
                ], 400);
            }

            // Prevent registration if the student's latest semester registration is terminated
            $student = Student::find($validatedData['student_id']);
            if ($student) {
                $latestSemReg = SemesterRegistration::where('student_id', $student->student_id)->latest('id')->first();
                if ($latestSemReg && strtolower($latestSemReg->status) === 'terminated') {
                    return response()->json([
                        'success' => false,
                        'message' => 'Cannot register student: student is terminated.'
                    ], 422);
                }
            }

            // Create a new CourseRegistration instance
            $courseRegistration = new CourseRegistration();

            // Assign the validated data to the CourseRegistration instance
            $courseRegistration->student_id = $validatedData['student_id'];
            $courseRegistration->course_id = $validatedData['course_id'];
            $courseRegistration->intake_id = $validatedData['intake_id'];
            $courseRegistration->registration_date = now();
            $courseRegistration->registration_fee = $validatedData['registration_fee'];
            $courseRegistration->status = 'Pending';
            $courseRegistration->approval_status = 'Pending';
            $courseRegistration->location = $validatedData['location'];
            $courseRegistration->slt_employee = ($validatedData['slt_employee'] === 'yes') ? 1 : 0;
            $courseRegistration->employee_service_number = $validatedData['service_no'];
            $courseRegistration->course_start_date = $validatedData['course_start_date'];
            $courseRegistration->remarks = 'Pre-registration via web form for eligibility checking';

            // Add counselor information if SLT employee is 'no'
            if ($validatedData['slt_employee'] === 'no') {
                $courseRegistration->counselor_name = $validatedData['counselor_name'] ?? null;
                $courseRegistration->counselor_id = $validatedData['counselor_id'] ?? null;
                $courseRegistration->counselor_phone = $validatedData['counselor_phone'] ?? null;
                $courseRegistration->counselor_nic = $validatedData['counselor_nic'] ?? null;
            }

            // Save the CourseRegistration instance
            $courseRegistration->save();

            // Update student's marketing survey - join array with comma if provided
            $student = Student::find($validatedData['student_id']);
            if ($student) {
                $marketingSurvey = null;
                if (!empty($validatedData['marketing_survey_options']) && is_array($validatedData['marketing_survey_options'])) {
                    $marketingSurvey = implode(', ', $validatedData['marketing_survey_options']);
                    $marketingSurvey = mb_substr($marketingSurvey, 0, 255);
                }
                $student->marketing_survey = $marketingSurvey;
                $student->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Course registration saved for eligibility checking.',
                'registration_id' => $courseRegistration->id
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation error in storeCourseRegistrationForEligibilityAPI: ' . json_encode($e->errors()));
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error in storeCourseRegistrationForEligibilityAPI: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the registration.'
            ], 500);
        }
    }

    public function findStudent(Request $request)
    {
        $nic = $request->input('nic') ?? $request->input('student_id') ?? $request->input('query');
        $student = Student::where('id_value', $nic)
            ->orWhere('student_id', $nic)
            ->orWhere('registration_id', $nic)
            ->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        return response()->json(['success' => true, 'student' => $student]);
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
