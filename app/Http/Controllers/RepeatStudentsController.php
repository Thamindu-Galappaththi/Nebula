<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Intake;
use App\Models\Module;
use App\Models\Student;
use App\Models\ExamResult;
use App\Models\SemesterRegistration;
use App\Models\CourseRegistration;
use App\Models\PaymentDetail;
use App\Models\Semester;
use App\Models\StudentPaymentPlan;
use App\Models\PaymentInstallment;
use App\Models\PaymentPlanDiscount;
use App\Models\Discount;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RepeatStudentsController extends Controller
{



    /**
     * Show the repeat students management view.
     */

    public function showRepeatStudentsManagement()
    {
        // Only show courses that have at least one intake (treat these as "repeatable" courses)
        // The intakes table in this project stores course names (course_name) rather than course_id,
        // so collect distinct course_name values and find matching Course rows by name.
        $courseNamesWithIntakes = Intake::distinct()->pluck('course_name')->toArray();
        $courses = Course::whereIn('course_name', $courseNamesWithIntakes)->orderBy('course_name')->get();
        $modules = Module::orderBy('module_name')->get();
        $intakes = Intake::join('courses', 'intakes.course_name', '=', 'courses.course_name')
            ->select('intakes.*', 'courses.course_name as course_display_name')
            ->get()
            ->map(function ($intake) {
                $intake->intake_display_name = $intake->course_display_name . ' - ' . $intake->intake_no;
                return $intake;
            });

        return view('exam_&_results.repeat_students', compact('courses', 'modules', 'intakes'));
    }

    /**
     * Get course data including modules, semesters, and years.
     */
    public function getCourseData($courseID)
    {
        try {
            $course = Course::with(['modules'])->find($courseID);

            if ($course) {
                $years = range(1, (int)$course->duration);
                // Get actual created semesters for this course
                $semesters = \App\Models\Semester::where('course_id', $courseID)
                    ->whereIn('status', ['active', 'upcoming'])
                    ->select('id', 'name')
                    ->get();

                return response()->json([
                    'modules' => $course->modules,
                    'semesters' => $semesters,
                ]);
            }

            return response()->json(['error' => 'Course not found or invalid data.'], Response::HTTP_NOT_FOUND);

        } catch (\Exception $e) {
            Log::error('Error in getCourseData for course ID ' . $courseID . ': ' . $e->getMessage());
            return response()->json(['error' => 'An internal server error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get student name by ID.
     */
    public function getStudentName(Request $request)
    {
        try {
            $student = Student::where('student_id', $request->input('student_id'))->first();

            if ($student) {
                return response()->json(['success' => true, 'name' => $student->full_name]);
            }
            return response()->json(['success' => false, 'message' => 'Student not found.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get repeat students for exam results.
     */
    public function getRepeatStudentsForExamResults(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,course_id',
            'intake_id' => 'required|integer|exists:intakes,intake_id',
            'location' => 'required|string',
            'semester' => 'required',
            'module_id' => 'required|integer|exists:modules,module_id',
        ]);

        $students = CourseRegistration::where('course_id', $request->course_id)
            ->where('intake_id', $request->intake_id)
            ->where('location', $request->location)
            ->eligible()
            ->with(['student', 'examResults' => function($query) use ($request) {
                $query->where('module_id', $request->module_id)
                      ->where('semester', $request->semester);
            }])
            ->get()
            ->filter(function($reg) {
                // Filter students who have failed or need to repeat
                return $reg->examResults->where('grade', 'F')->count() > 0 ||
                       $reg->examResults->where('marks', '<', 40)->count() > 0;
            })
            ->map(function($reg) use ($request) {
                $failedResult = $reg->examResults->where('grade', 'F')->first() ??
                               $reg->examResults->where('marks', '<', 40)->first();

                return [
                    'registration_id' => $reg->course_registration_id ?? $reg->id,
                    'student_id' => $reg->student->student_id,
                    'name' => $reg->student->full_name,
                    'previous_marks' => $failedResult ? $failedResult->marks : 'N/A',
                    'previous_grade' => $failedResult ? $failedResult->grade : 'N/A',
                    'repeat_count' => $reg->examResults->where('module_id', $request->module_id)->count(),
                ];
            });

        return response()->json([
            'success' => true,
            'students' => $students
        ]);
    }

    /**
     * Get repeat students for payment processes.
     */
    public function getRepeatStudentsForPayments(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,course_id',
            'intake_id' => 'required|integer|exists:intakes,intake_id',
            'location' => 'required|string',
        ]);

        $students = CourseRegistration::where('course_id', $request->course_id)
            ->where('intake_id', $request->intake_id)
            ->where('location', $request->location)
            ->eligible()
            ->with(['student', 'course', 'intake', 'payments'])
            ->get()
            ->filter(function($reg) {
                // Filter students with outstanding payments or repeat payment requirements
                $totalPayments = $reg->payments
                    ->where('status', 'paid')
                    ->sum('amount');
                $courseFee = $reg->intake->course_fee ?? 0;
                return $totalPayments < $courseFee || $reg->payments->contains(
                    fn ($payment) => $payment->status !== 'paid'
                );
            })
            ->map(function($reg) {
                $totalPayments = $reg->payments
                    ->where('status', 'paid')
                    ->sum('amount');
                $courseFee = $reg->intake->course_fee ?? 0;
                $outstanding = $courseFee - $totalPayments;

                return [
                    'registration_id' => $reg->course_registration_id ?? $reg->id,
                    'student_id' => $reg->student->student_id,
                    'name' => $reg->student->full_name,
                    'course_fee' => $courseFee,
                    'paid_amount' => $totalPayments,
                    'outstanding_amount' => $outstanding,
                    'payment_status' => $outstanding <= 0 ? 'Paid' : 'Outstanding',
                ];
            });

        return response()->json([
            'success' => true,
            'students' => $students
        ]);
    }

    /**
     * Update exam results for repeat students.
     */
    public function updateExamResults(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'course_id' => 'required|exists:courses,course_id',
                'intake_id' => 'required|exists:intakes,intake_id',
                'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
                'semester' => 'required',
                'module_id' => 'required|exists:modules,module_id',
                'results' => 'required|array|min:1',
                'results.*.student_id' => 'required|exists:students,student_id',
                'results.*.marks' => 'required|integer|min:0|max:100',
                'results.*.grade' => 'required|string|max:5',
                'results.*.remarks' => 'nullable|string|max:255',
            ]);

            foreach ($validatedData['results'] as $result) {
                // Update existing result or create new one
                ExamResult::updateOrCreate(
                    [
                        'student_id' => $result['student_id'],
                        'course_id' => $validatedData['course_id'],
                        'module_id' => $validatedData['module_id'],
                        'intake_id' => $validatedData['intake_id'],
                        'location' => $validatedData['location'],
                        'semester' => $validatedData['semester'],
                    ],
                    [
                        'marks' => $result['marks'],
                        'grade' => $result['grade'],
                        'remarks' => $result['remarks'] ?? null,
                    ]
                );
            }

            return response()->json(['success' => true, 'message' => 'Exam results updated successfully.'], Response::HTTP_OK);

        } catch (QueryException $e) {
            return response()->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update payment details for repeat students.
     */
    public function updatePaymentDetails(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'payments' => 'required|array|min:1',
                'payments.*.student_id' => 'required|exists:students,student_id',
                'payments.*.payment_amount' => 'required|numeric|min:0',
                'payments.*.payment_method' => 'required|string',
                'payments.*.payment_date' => 'required|date',
                'payments.*.payment_reference' => 'nullable|string',
                'payments.*.remarks' => 'nullable|string',
            ]);

            foreach ($validatedData['payments'] as $payment) {
                PaymentDetail::create([
                    'student_id' => $payment['student_id'],
                    'course_registration_id' => $request->registration_id,
                    'amount' => $payment['payment_amount'],
                    'total_fee' => $payment['payment_amount'],
                    'payment_method' => $payment['payment_method'],
                    'payment_date' => $payment['payment_date'],
                    'transaction_id' => $payment['payment_reference'] ?? null,
                    'status' => 'paid',
                    'payment_name' => 'Repeat Student Payment',
                    'remarks' => $payment['remarks'] ?? null,
                ]);
            }

            return response()->json(['success' => true, 'message' => 'Payment details updated successfully.'], Response::HTTP_OK);

        } catch (QueryException $e) {
            return response()->json(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $e->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get intakes for course and location.
     */
    public function getIntakesForCourseAndLocation($courseID, $location)
    {
        try {
            // Intake rows reference course_name; resolve the course name from provided id
            $course = Course::find($courseID);
            if (!$course) {
                return response()->json(['success' => false, 'message' => 'Course not found.'], Response::HTTP_NOT_FOUND);
            }

            $intakes = Intake::forCourse($course, $location)
                ->get();

            return response()->json(['success' => true, 'intakes' => $intakes]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get filtered modules.
     */
    public function getFilteredModules(Request $request)
    {
        try {
            $modules = Module::where('course_id', $request->course_id)->get();
            return response()->json(['success' => true, 'modules' => $modules]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function getRepeatStudentByNic(Request $request)
    {
        $nic = $request->input('nic');
        $student = \App\Models\Student::where('id_value', $nic)->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.']);
        }

        $holdingRegs = \App\Models\SemesterRegistration::where('student_id', $student->student_id)
            ->where('status', 'holding')  // Only fetch holding registrations for re-registration
            ->with(['course', 'intake', 'semester'])
            ->orderByDesc('registration_date')
            ->get()
            ->map(function($reg){
                return [
                    'id'             => $reg->id,
                    'course_id'      => $reg->course_id,
                    'course_name'    => $reg->course->course_name ?? '',
                    'intake_id'      => $reg->intake_id,
                    'intake'         => $reg->intake->batch ?? '',
                    'location'       => $reg->location ?? '',
                    'semester_id'    => $reg->semester_id,
                    'semester_name'  => $this->getSemesterDisplayLabel($reg->course, $reg->semester),
                    'status'         => $reg->status,
                    'specialization' => $reg->specialization ?? '',
                ];
            });

        $studentArr = $student->toArray();
        $studentArr['parent'] = $student->parentGuardian ? $student->parentGuardian->toArray() : null;

        // Also include a current/active course registration if available (useful when student is not on hold)
        $currentCourseReg = \App\Models\CourseRegistration::where('student_id', $student->student_id)
            ->where(function ($q) {
                $q->eligible()
                  ->orWhere('status', 'Pending');
            })
            ->with('course', 'intake')
            ->orderByDesc('id')
            ->first();

        $currentRegArr = null;
        if ($currentCourseReg) {
            $currentRegArr = [
                'id' => $currentCourseReg->id,
                'course_id' => $currentCourseReg->course_id,
                'course_name' => $currentCourseReg->course->course_name ?? null,
                'intake_id' => $currentCourseReg->intake_id,
                'intake' => $currentCourseReg->intake->batch ?? null,
                'status' => $currentCourseReg->status,
            ];
        }

        return response()->json([
            'success' => true,
            'student' => $studentArr,
            'holding_history' => $holdingRegs,
            'current_registration' => $currentRegArr,
        ]);
    }

    // UPDATED: Method for updating semester registration details (handles form submit)
// UPDATED: Method for updating semester registration details (handles form submit)
public function updateSemesterRegistration(Request $request)
{
    // Validate input (matches form fields)
    $validated = $request->validate([
        'registration_id' => 'required|integer|exists:semester_registrations,id',
        'location'        => 'required|string|in:Welisara,Moratuwa,Peradeniya',
        'course_id'       => 'required|integer|exists:courses,course_id',
        'intake_id'       => 'required|integer|exists:intakes,intake_id',
        'semester_id'     => 'required|integer|exists:semesters,id',
        'specialization'  => 'nullable|string|max:255',
    ]);

    try {
        DB::beginTransaction();

        // Fetch the registration record
        $registration = \App\Models\SemesterRegistration::find($validated['registration_id']);
        if (!$registration) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Registration not found.']);
        }

        // Ensure it's a holding record (prevent updating active/terminated records)
        if ($registration->status !== 'holding') {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Only holding registrations can be updated.']);
        }

        // Preserve existing specialization when the incoming specialization is empty/null
        $newSpecialization = isset($validated['specialization']) && $validated['specialization'] !== ''
            ? $validated['specialization']
            : $registration->specialization;

        // Update the fields and change status from holding -> registered
        $registration->update([
            'location'       => $validated['location'],
            'course_id'      => $validated['course_id'],
            'intake_id'      => $validated['intake_id'],
            'semester_id'    => $validated['semester_id'],
            'specialization' => $newSpecialization,
            'status'         => 'registered',
        ]);

        // ✅ NEW: Archive existing active student payment plans and their installments
        $existingPlans = \App\Models\StudentPaymentPlan::where('student_id', $registration->student_id)
            ->where('course_id', $validated['course_id'])
            ->where('status', 'active')
            ->get();

        foreach ($existingPlans as $plan) {
            try {
                // Archive the main payment plan
                $plan->update(['status' => 'archived']);
            } catch (\Throwable $t) {
                // fallback if 'archived' enum not supported
                $plan->update(['status' => 'inactive']);
            }

            // Archive all related installments under this plan
            \App\Models\PaymentInstallment::where('payment_plan_id', $plan->id)
                ->update(array_merge(
                    ['status' => 'archived'],
                    \App\Support\UserTrackingData::forUpdate()
                ));
        }

        // Also update corresponding course_registration rows for this student + course
        \App\Models\CourseRegistration::where('student_id', $registration->student_id)
            ->where('course_id', $validated['course_id'])
            ->update(array_merge(
                ['intake_id' => $validated['intake_id']],
                \App\Support\UserTrackingData::forUpdate()
            ));

        // Fetch a representative course registration for this student+course to return to frontend
        $updatedCourseReg = \App\Models\CourseRegistration::where('student_id', $registration->student_id)
            ->where('course_id', $validated['course_id'])
            ->with('intake', 'course')
            ->orderByDesc('id')
            ->first();

        DB::commit();

        return response()->json([
            'success' => true,
            'message' => 'Semester registration updated successfully.',
            'updated_semester_registration' => $registration,
            'updated_course_registration' => $updatedCourseReg ? [
                'id' => $updatedCourseReg->id,
                'course_id' => $updatedCourseReg->course_id,
                'course_name' => $updatedCourseReg->course->course_name ?? null,
                'intake_id' => $updatedCourseReg->intake_id,
                'intake' => $updatedCourseReg->intake ? ($updatedCourseReg->intake->batch ?? $updatedCourseReg->intake->intake_no) : null,
                'status' => $updatedCourseReg->status,
            ] : null,
        ]);
    } catch (\Exception $e) {
        DB::rollBack();
        Log::error('Error updating semester registration: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while updating registration.',
            'error_detail' => env('APP_DEBUG') ? $e->getMessage() : null,
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}


    /**
     * API: Return list of courses for populating selects
     */
    public function apiCourses()
    {
        try {
            $courses = Course::orderBy('course_name')->get(['course_id', 'course_name']);
            return response()->json(['success' => true, 'courses' => $courses]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch courses.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API: Return intakes for a given course (optionally filtered by location).
     * Query param: course_id, optional location
     */
    public function apiIntakes(Request $request)
    {
        $courseId = $request->query('course_id');
        $location = $request->query('location');

        if (!$courseId) {
            return response()->json(['success' => false, 'message' => 'course_id is required.'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Resolve course_name (intakes table stores course_name)
            $course = Course::find($courseId);
            if (!$course) {
                return response()->json(['success' => false, 'message' => 'Course not found.'], Response::HTTP_NOT_FOUND);
            }

            $query = Intake::forCourse($course, $location ?: null);

            $now = Carbon::now();

            // identify next upcoming intake (if any)
            $next = Intake::forCourse($course, $location ?: null)
                ->whereNotNull('start_date')
                ->where('start_date', '>=', $now->toDateString())
                ->orderBy('start_date', 'asc')
                ->first();

            $nextId = $next ? ($next->intake_id ?? $next->id) : null;

            $intakes = $query->orderBy('start_date', 'asc')->get()->map(function ($i) use ($nextId) {
                $id = $i->intake_id ?? $i->id;
                $batch = $i->batch ?? ($i->intake_no ?? '');
                $start = $i->start_date ? Carbon::parse($i->start_date)->toDateString() : null;
                $label = $batch;
                if ($start) {
                    // append start date to label for clarity
                    $label .= $label ? ' (' . $start . ')' : $start;
                }

                return [
                    'intake_id' => $id,
                    'batch' => $batch,
                    'start_date' => $start,
                    'end_date' => $i->end_date ?? null,
                    'label' => $label,
                    'is_next' => ($id == $nextId),
                ];
            });

            return response()->json([
                'success' => true,
                'intakes' => $intakes,
                'next_intake_id' => $nextId,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to fetch intakes.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * API: Return semesters for a given course and (optionally) intake.
     * Query params: course_id, intake_id
     */
    public function apiSemesters(Request $request)
{
    $courseId = $request->query('course_id');
    $intakeId = $request->query('intake_id');

    if (!$courseId || !$intakeId) {
        return response()->json([
            'success' => false,
            'message' => 'Both course_id and intake_id are required.'
        ], 400);
    }

    try {
        // Find intake for label & location context
        $intake = \App\Models\Intake::find($intakeId);
        if (!$intake) {
            return response()->json(['success' => false, 'message' => 'Intake not found.'], 404);
        }

        $course = Course::find($courseId);

        // Get only semesters that match both course and intake
        $semesters = \App\Models\Semester::where('course_id', $courseId)
            ->where('intake_id', $intakeId) // only this intake's semesters
            ->orderBy('start_date', 'asc')
            ->orderBy('id', 'asc')
            ->get(['id', 'name', 'course_id', 'intake_id'])
            ->values();

        // Append display name with intake info
        $intakeLabel = $intake->batch ?? $intake->intake_no ?? 'Unknown Intake';
        $semesters = $semesters->map(function ($s, $index) use ($intakeLabel, $course) {
            $s->display_name = $this->getSemesterDisplayLabel($course, $s, $index + 1) . " – {$intakeLabel}";
            return $s;
        });

        // If no semesters found for that intake, fallback to course-level semesters
        if ($semesters->isEmpty()) {
            $semesters = \App\Models\Semester::where('course_id', $courseId)
                ->orderBy('start_date', 'asc')
                ->orderBy('id', 'asc')
                ->get(['id', 'name', 'course_id', 'intake_id'])
                ->values()
                ->map(function ($s, $index) use ($intakeLabel, $course) {
                    $s->display_name = $this->getSemesterDisplayLabel($course, $s, $index + 1) . " – {$intakeLabel} (Course Default)";
                    return $s;
                });
        }

        return response()->json([
            'success' => true,
            'semesters' => $semesters
        ]);

    } catch (\Throwable $e) {
        \Log::error('Error in apiSemesters: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Failed to fetch semesters: ' . $e->getMessage()
        ], 500);
    }
}

    private function getSemesterSequenceNumber(int $courseId, int $intakeId, int $semesterId): int
    {
        $semesterIds = Semester::where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->orderBy('start_date')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        $position = $semesterIds->search($semesterId);

        return $position === false ? 1 : $position + 1;
    }

    private function getSemesterDisplayLabel(?Course $course, ?Semester $semester, ?int $fallbackNumber = null): string
    {
        if (!$semester) {
            return '';
        }

        $course = $course ?: Course::find($semester->course_id);
        if (!$course) {
            return (string) ($semester->name ?? '');
        }

        $sequenceNumber = $fallbackNumber && $fallbackNumber > 0
            ? $fallbackNumber
            : $this->getSemesterSequenceNumber($course->course_id, $semester->intake_id, $semester->id);

        return 'Semester ' . $this->formatSemesterDisplayValue($course, $semester->name, $sequenceNumber);
    }

    private function formatSemesterDisplayValue(Course $course, $rawName, ?int $fallbackNumber = null): string
    {
        $rawName = trim((string) $rawName);
        $fallbackNumber = $fallbackNumber && $fallbackNumber > 0 ? $fallbackNumber : 1;
        $maxSemesters = (int) ($course->no_of_semesters ?? 0);
        $numericUpperBound = $maxSemesters > 0 ? $maxSemesters : 12;

        if ($course->semester_format === 'alphabetical') {
            if (preg_match('/^[A-Za-z]$/', $rawName)) {
                return strtoupper($rawName);
            }

            if (is_numeric($rawName)) {
                $numeric = (int) $rawName;
                if ($numeric < 1 || $numeric > $numericUpperBound || $numeric > 26) {
                    $numeric = $fallbackNumber;
                }

                return $numeric >= 1 && $numeric <= 26 ? chr(64 + $numeric) : (string) $numeric;
            }

            return $fallbackNumber >= 1 && $fallbackNumber <= 26
                ? chr(64 + $fallbackNumber)
                : (string) $fallbackNumber;
        }

        if (is_numeric($rawName)) {
            $numeric = (int) $rawName;
            if ($numeric < 1 || $numeric > $numericUpperBound) {
                $numeric = $fallbackNumber;
            }

            return (string) $numeric;
        }

        return $rawName !== '' ? $rawName : (string) $fallbackNumber;
    }

    public function getSemesters(Request $request)
    {
        $courseId = $request->query('course_id') ?? $request->query('courseId');
        $query = Semester::query();
        if ($courseId) {
            $query->where('course_id', $courseId);
        }
        $semesters = $query->orderBy('name')->get();
        return response()->json(['success' => true, 'semesters' => $semesters, 'data' => $semesters]);
    }
}
