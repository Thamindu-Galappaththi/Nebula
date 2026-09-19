<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\ParentGuardian;
use App\Models\CourseRegistration;
use App\Models\Course;
use App\Models\Module;
use App\Models\ExamResult;
use App\Models\StudentExam;
use App\Models\Attendance;
use App\Models\PaymentDetail;
use App\Models\PaymentPlan;
use App\Models\StudentPaymentPlan;
use App\Models\SltLoanReceivableRecord;
use App\Models\StudentClearance;
use App\Models\StudentOtherInformation;
use App\Models\Intake;
use App\Models\Batch;
use Illuminate\Support\Facades\Log;
use App\Models\StudentStatusHistory;
use App\Models\ClearanceRequest;
use Illuminate\Support\Facades\Storage;
use App\Http\Requests\UpdateParentInfoRequest;

class StudentProfileController extends Controller
{
    // Show the student profile view
    public function showStudentProfile(Request $request, $studentId)
    {
        if ($studentId === 'me') {
            $user = auth()->user();
            if (!$user || !$user->student_id) {
                return redirect()->route('dashboard')->with('error', 'No student profile associated with your account.');
            }
            $studentId = $user->student_id;
        }
        if ($studentId == 0) {
            return view('student_management.student_profile');
        }
        $student = Student::with(['parentGuardian', 'exams', 'otherInformation'])->find($studentId);
        if (!$student) {
            return redirect()->back()->with('error', 'Student not found.');
        }
        
        Log::info('Student profile parent (Blade):', ['parent' => $student->parent]);
        return view('student_management.student_profile', compact('student'));
    }

    // Get student details (AJAX)
    public function getStudentDetails(Request $request)
    {
        $identificationType = $request->input('identificationType');
        $idValue = $request->input('idValue');
        if (empty($idValue)) {
            return response()->json(['success' => false, 'message' => 'ID value is required'], 400);
        }
        // Complete fields array with correct column names
        $fields = [
            'student_id', // Always include this for relationships
            'registration_id',
            'full_name',
            'name_with_initials',
            'title',
            'gender',
            'birthday', // Correct column name
            'id_value', // Correct column name
            'id_type',
            'email',
            'mobile_phone',
            'home_phone',
            'emergency_contact_number',
            'address',
            'special_needs',
            'extracurricular_activities',
            'future_potentials',
            'institute_location',
            'course_id',
            'intake',
            'status',
            'remarks'
        ];
        $student = null;
        switch ($identificationType) {
            case 'registration_number':
                $student = Student::with('parentGuardian')->select($fields)->where('registration_id', $idValue)->first();
                break;
            case 'id_number':
                $student = Student::with('parentGuardian')->select($fields)->where('id_number', $idValue)->first();
                break;
            case 'Course_registration_id':
                $courseRegistration = CourseRegistration::where('id', $idValue)->first();
                if ($courseRegistration) {
                    $student = Student::with('parentGuardian')->select($fields)->where('student_id', $courseRegistration->student_id)->first();
                }
                break;
            default:
                return response()->json(['success' => false, 'message' => 'Invalid identification type'], 400);
        }
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found'], 404);
        }
        $student->parent = $student->parentGuardian;
        Log::info('Student profile parent (AJAX):', ['parent' => $student->parent]);
        // Enhance student data (no payment details)
        $student->course_registrations = CourseRegistration::where('student_id', $student->student_id)->get();
        $student->exams = StudentExam::where('student_id', $student->student_id)->get();
        $student->attendance = Attendance::where('student_id', $student->student_id)->get();
        $student->exam_results = ExamResult::where('student_id', $student->student_id)->get();
        $student->other_information = StudentOtherInformation::where('student_id', $student->student_id)->first();
        return response()->json([
            'success' => true,
            'message' => 'Student found',
            'student' => $student
        ]);
    }

    // Update personal info
    public function updatePersonalInfo(Request $request, $studentId)
    {
        $student = Student::find($studentId);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.']);
        }
        $validated = $request->validate([
            'name_with_initials' => 'required|string|max:255',
            'birthday' => 'required|date',
            'email' => 'required|email',
            'mobile_phone' => 'required|string',
            'address' => 'required|string',
        ]);
        $student->name_with_initials = $validated['name_with_initials'];
        $student->birthday = $validated['birthday'];
        $student->email = $validated['email'];
        $student->mobile_phone = $validated['mobile_phone'];
        $student->address = $validated['address'];
        if ($student->save()) {
            return response()->json(['success' => true, 'student' => $student]);
        } else {
            return response()->json(['success' => false, 'message' => 'Failed to update.']);
        }
    }

    // Update personal info via AJAX
    public function updatePersonalInfoAjax(Request $request)
{
    $studentId = $request->input('student_id');
    $student = \App\Models\Student::find($studentId);

    if (!$student) {
        return response()->json(['success' => false, 'message' => 'Student not found.']);
    }

    try {
        // Validate required fields
        $validatedData = $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'title' => 'required|string|max:255',
            'full_name' => 'required|string|max:255',
            'id_value' => 'required|string|max:255',
            'institute_location' => 'required|string|max:255',
            'birthday' => 'required|date',
            'gender' => 'required|in:Male,Female',
            'email' => 'required|email|max:255',
            'mobile_phone' => 'required|string|max:20',
            'home_phone' => 'nullable|string|max:20',
            'address' => 'required|string',
            // Optional fields
            'name_with_initials' => 'nullable|string|max:255',
            'special_needs' => 'nullable|string',
            'extracurricular_activities' => 'nullable|string',
            'future_potentials' => 'nullable|string',
            'emergency_contact_number' => 'nullable|string|max:20'
        ]);

        // Update student fields
        $student->title = $validatedData['title'];
        $student->full_name = $validatedData['full_name'];
        $student->id_value = $validatedData['id_value'];
        $student->institute_location = $validatedData['institute_location'];
        $student->birthday = $validatedData['birthday'];
        $student->gender = $validatedData['gender'];
        $student->email = $validatedData['email'];
        $student->mobile_phone = $validatedData['mobile_phone'];
        $student->address = $validatedData['address'];
        
        // Optional fields
        if (isset($validatedData['name_with_initials'])) {
            $student->name_with_initials = $validatedData['name_with_initials'];
        }
        if (isset($validatedData['home_phone'])) {
            $student->home_phone = $validatedData['home_phone'];
        }
        if (isset($validatedData['special_needs'])) {
            $student->special_needs = $validatedData['special_needs'];
        }
        if (isset($validatedData['extracurricular_activities'])) {
            $student->extracurricular_activities = $validatedData['extracurricular_activities'];
        }
        if (isset($validatedData['future_potentials'])) {
            $student->future_potentials = $validatedData['future_potentials'];
        }

        $student->save();

        // ✅ Handle emergency contact number (belongs to ParentGuardian)
        if (!empty($validatedData['emergency_contact_number'])) {
            \App\Models\ParentGuardian::updateOrCreate(
                ['student_id' => $studentId],
                ['emergency_contact_number' => $validatedData['emergency_contact_number']]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Personal information updated successfully!'
        ]);
    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'Validation failed.',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to update personal information: ' . $e->getMessage()
        ]);
    }
}


    // Update parent/guardian info via AJAX
    public function updateParentInfoAjax(UpdateParentInfoRequest $request)
    {
        $validated = $request->validated();
        $studentId = $validated['student_id'] ?? null;
        $student = Student::find($studentId);

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        try {
            // Find or create parent/guardian record
            $parentGuardian = ParentGuardian::where('student_id', $studentId)->first();

            if (!$parentGuardian) {
                $parentGuardian = new ParentGuardian();
                $parentGuardian->student_id = $studentId;
            }

            // Update parent/guardian fields using validated data
            $parentGuardian->guardian_name = $validated['guardian_name'];
            $parentGuardian->guardian_profession = $validated['guardian_profession'] ?? null;
            $parentGuardian->guardian_contact_number = $validated['guardian_contact_number'];
            $parentGuardian->guardian_email = $validated['guardian_email'] ?? null;
            $parentGuardian->guardian_address = $validated['guardian_address'];
            $parentGuardian->emergency_contact_number = $validated['emergency_contact_number'];

            $parentGuardian->save();

            return response()->json([
                'success' => true,
                'message' => 'Parent/Guardian information updated successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update parent/guardian information: ' . $e->getMessage()
            ]);
        }
    }

    

    // API: Get course registration history for a student
   public function getCourseRegistrationHistory($studentId)
    {
        $registrations = \App\Models\CourseRegistration::where('student_id', $studentId)
            ->with(['course', 'intake'])
            ->orderBy('created_at', 'desc')
            ->get();

        $history = $registrations->map(function ($registration) {
            $semesterReg = \App\Models\SemesterRegistration::where('student_id', $registration->student_id)
                ->where('course_id', $registration->course_id)
                ->where('intake_id', $registration->intake_id)
                ->first();

            return [
                'id' => $registration->id,
                'course_id' => $registration->course_id,
                'course_name' => $registration->course->course_name ?? 'N/A',
                'intake' => $registration->intake->batch ?? 'N/A',
                'status' => $registration->status ?? 'N/A',
                'full_grade' => $registration->full_grade ?? '',
                'specialization' => $semesterReg ? $semesterReg->specialization : ($registration->specialization ?? ''),
                'specializations' => $this->specializationsForCourse($registration->course),
            ];
        });

        return response()->json([
            'success' => true,
            'history' => $history
        ]);
    }

    public function getCourseSpecializations($courseId)
    {
        $course = \App\Models\Course::find($courseId);
        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Course not found.'], 404);
        }

        $specializations = $this->specializationsForCourse($course);

        return response()->json(['success' => true, 'specializations' => $specializations]);
    }

    // API: Update course registration grade and specialization
    public function updateCourseRegistrationGrade(Request $request, $id)
    {
        // Validate inputs
        $validated = $request->validate([
            'full_grade' => 'nullable|string|max:255',
            'specialization' => 'nullable|string|max:255',
        ]);

        // Specialization membership is maintained only on the dedicated
        // Specialization Registration page. Keep accepting this legacy field
        // so older clients can still update a grade, but never persist it here.
        unset($validated['specialization']);

        $registration = \App\Models\CourseRegistration::find($id);
        if (!$registration) {
            return response()->json(['success' => false, 'message' => 'Registration not found.'], 404);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Update only the full_grade column on the course_registration table using query builder
            \Illuminate\Support\Facades\DB::table('course_registration')
                ->where('id', $registration->id)
                ->update(array_merge([
                    'full_grade' => $validated['full_grade'] ?? null,
                    'updated_at' => now()
                ], \App\Support\UserTrackingData::forUpdate()));

            // Update or create the corresponding semester_registration so specialization is persisted
            $semesterReg = \App\Models\SemesterRegistration::where('student_id', $registration->student_id)
                ->where('course_id', $registration->course_id)
                ->where('intake_id', $registration->intake_id)
                ->first();

            if (!$semesterReg) {
                // Determine the semester_id for this course+intake. Prefer exact match, fall back to latest semester for the course.
                $semester = \App\Models\Semester::where('course_id', $registration->course_id)
                    ->where('intake_id', $registration->intake_id)
                    ->first();

                if (!$semester) {
                    // fallback: most recent semester for the course (if any)
                    $semester = \App\Models\Semester::where('course_id', $registration->course_id)
                        ->orderBy('start_date', 'desc')
                        ->first();
                }

                if (!$semester) {
                    // No semester found - for certificate courses we don't require a semester_registration.
                    $course = \App\Models\Course::find($registration->course_id);
                    if ($course && strtolower($course->course_type) === 'certificate') {
                        // If specialization present in request, persist it on the course_registration row only if the column exists
                        if (array_key_exists('specialization', $validated)) {
                            $newSpec = $validated['specialization'] !== '' ? $validated['specialization'] : ($registration->specialization ?? null);
                            if (\Illuminate\Support\Facades\Schema::hasColumn('course_registration', 'specialization')) {
                                \Illuminate\Support\Facades\DB::table('course_registration')
                                    ->where('id', $registration->id)
                                    ->update(array_merge(
                                        ['specialization' => $newSpec, 'updated_at' => now()],
                                        \App\Support\UserTrackingData::forUpdate()
                                    ));
                                // Read authoritative value from DB
                                $finalSpec = \Illuminate\Support\Facades\DB::table('course_registration')->where('id', $registration->id)->value('specialization');
                            } else {
                                // Column not present; log and return the intended specialization value without DB write
                                \Illuminate\Support\Facades\Log::warning('course_registration.specialization column missing; skipping persist', ['registration_id' => $registration->id]);
                                $finalSpec = $newSpec;
                            }
                        } else {
                            $finalSpec = $registration->specialization ?? null;
                        }

                        // Commit and return authoritative specialization
                        \Illuminate\Support\Facades\DB::commit();
                        return response()->json([ 'success' => true, 'specialization' => $finalSpec ]);
                    }
                    // fallback: most recent semester for the course (if any)
                    $semester = \App\Models\Semester::where('course_id', $registration->course_id)
                        ->orderBy('start_date', 'desc')
                        ->first();
                }

                // Normalize status to allowed enum values
                $allowedStatuses = ['registered', 'pending', 'cancelled'];
                $regStatus = strtolower(trim((string)($registration->status ?? '')));
                $statusToSet = in_array($regStatus, $allowedStatuses) ? $regStatus : 'registered';

                // create a minimal semester registration record with a valid semester_id
                $semesterReg = \App\Models\SemesterRegistration::create([
                    'student_id' => $registration->student_id,
                    'semester_id' => $semester->id,
                    'course_id' => $registration->course_id,
                    'intake_id' => $registration->intake_id,
                    'location' => $registration->location ?? null,
                    'status' => $statusToSet,
                    'full_grade' => $validated['full_grade'] ?? null,
                    'registration_date' => $registration->created_at ?? now(),
                ]);
            }

            if (array_key_exists('specialization', $validated)) {
                // Preserve existing specialization when incoming is empty string
                $newSpec = $validated['specialization'] !== '' ? $validated['specialization'] : $semesterReg->specialization;
                $semesterReg->specialization = $newSpec;
                $semesterReg->save();
            }

            \Illuminate\Support\Facades\DB::commit();

            // Return the final stored specialization so client can update UI from authoritative source
            return response()->json([
                'success' => true,
                'specialization' => $semesterReg->specialization
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            \Illuminate\Support\Facades\Log::error('Database error updating course registration grade/specialization', ['message' => $e->getMessage(), 'registration_id' => $id]);
            return response()->json(['success' => false, 'message' => 'Failed to update registration.'], 500);
        }
    }
    
    // API: Get intakes for a specific course
    public function getIntakesForCourse($studentId, $courseId)
    {
        try {
            $intakes = CourseRegistration::where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->with('intake')
                ->get()
                ->pluck('intake.batch')
                ->filter()
                ->unique()
                ->values();

            return response()->json([
                'success' => true,
                'intakes' => $intakes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch intakes: ' . $e->getMessage()
            ], 500);
        }
    }

    // API: Get payment details for a specific course and intake
    public function getPaymentDetails($studentId, $courseId, $intake)
    {
        try {
            // Mock payment data - replace with actual payment model queries
            $paymentData = [
                'total_fee' => '150,000 LKR',
                'paid_amount' => '75,000 LKR',
                'balance' => '75,000 LKR',
                'payment_status' => 'Partially Paid'
            ];

            return response()->json([
                'success' => true,
                'total_fee' => $paymentData['total_fee'],
                'paid_amount' => $paymentData['paid_amount'],
                'balance' => $paymentData['balance'],
                'payment_status' => $paymentData['payment_status']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment details: ' . $e->getMessage()
            ], 500);
        }
    }

    // API: Get payment history for a specific course and intake
    public function getPaymentHistory($studentId, $courseId, $intake)
    {
        try {
            // Mock payment history - replace with actual payment model queries
            $paymentHistory = [
                [
                    'payment_date' => '15/01/2025',
                    'amount' => '50,000 LKR',
                    'payment_method' => 'Bank Transfer',
                    'receipt_url' => null
                ],
                [
                    'payment_date' => '15/02/2025',
                    'amount' => '25,000 LKR',
                    'payment_method' => 'Cash',
                    'receipt_url' => '/receipts/receipt_001.pdf'
                ]
            ];

            return response()->json([
                'success' => true,
                'history' => $paymentHistory
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment history: ' . $e->getMessage()
            ], 500);
        }
    }

    // API: Get payment schedule for a specific course and intake
    public function getPaymentSchedule($studentId, $courseId, $intake)
    {
        try {
            // Mock payment schedule - replace with actual payment model queries
            $paymentSchedule = [
                [
                    'due_date' => '15/01/2025',
                    'amount' => '50,000 LKR',
                    'status' => 'Paid',
                    'payment_date' => '15/01/2025',
                    'receipt_url' => null
                ],
                [
                    'due_date' => '15/02/2025',
                    'amount' => '50,000 LKR',
                    'status' => 'Paid',
                    'payment_date' => '15/02/2025',
                    'receipt_url' => '/receipts/receipt_001.pdf'
                ],
                [
                    'due_date' => '15/03/2025',
                    'amount' => '50,000 LKR',
                    'status' => 'Pending',
                    'payment_date' => null,
                    'receipt_url' => null
                ]
            ];

            return response()->json([
                'success' => true,
                'schedule' => $paymentSchedule
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch payment schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStudentDetailsByNic(Request $request)
    {
        $nic = strtoupper(preg_replace('/\s+/', '', trim((string) $request->query('nic', ''))));
        if ($nic === '') {
            return response()->json(['success' => false, 'message' => 'Please enter a NIC number.'], 400);
        }

        $student = Student::with(['parentGuardian', 'otherInformation', 'exams'])
            ->whereRaw("UPPER(REPLACE(id_value, ' ', '')) = ?", [$nic])
            ->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'No student profile found for this NIC.'], 404);
        }

        $payload = $student->toArray();
        $payload['parent'] = $student->parentGuardian;
        $payload['other_information'] = $student->otherInformation;
        $payload['birthday'] = $student->birthday
            ? \Illuminate\Support\Carbon::parse($student->birthday)->format('Y-m-d')
            : null;
        $payload['academic_status'] = $student->academic_status;

        return response()->json(['success' => true, 'student' => $payload]);
    }
    // Other methods (academic details, attendance, clearance, certificates, etc.) remain unchanged




    //show exam results 
    public function getRegisteredCourses($studentId)
    {
        $courses = \App\Models\Course::whereIn(
            'course_id',
            \App\Models\CourseRegistration::where('student_id', $studentId)->pluck('course_id')
        )->get(['course_id', 'course_name', 'course_type']);
        return response()->json(['success' => true, 'courses' => $courses]);
    }

    public function getSemesters($studentId, $courseId)
    {
        try {
            $semestersList = \App\Models\Semester::where('course_id', (int) $courseId)
                ->orderBy('id')
                ->get();

            $named = collect($semestersList->map(function ($semester) {
                return trim((string) ($semester->name ?: $semester->id));
            })->all());

            $examSemesters = \App\Models\ExamResult::where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->pluck('semester');

            $attendanceSemesters = \App\Models\Attendance::where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->pluck('semester');

            $fromRecords = collect($examSemesters->merge($attendanceSemesters)->all())
                ->map(function ($sem) use ($semestersList) {
                    $sem = trim((string) $sem);
                    if ($sem === '') {
                        return null;
                    }
                    foreach ($semestersList as $semester) {
                        if ((string) $semester->id === $sem || (string) $semester->name === $sem) {
                            return trim((string) $semester->name);
                        }
                    }
                    return $sem;
                });

            $allSemesters = $named->merge($fromRecords)
                ->filter(fn ($sem) => $sem !== null && $sem !== '')
                ->unique()
                ->values();

            if ($allSemesters->isEmpty()) {
                $course = \App\Models\Course::find($courseId);
                $count = (int) ($course->no_of_semesters ?? 0);
                if ($count > 0) {
                    $allSemesters = collect(range(1, $count))->map(fn ($n) => (string) $n)->values();
                }
            }

            return response()->json(['success' => true, 'semesters' => $allSemesters]);
        } catch (\Throwable $e) {
            \Log::error('Failed to fetch exam semesters: ' . $e->getMessage(), [
                'student_id' => $studentId,
                'course_id' => $courseId,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load semesters.',
                'semesters' => [],
            ]);
        }
    }

    public function getModuleResults($studentId, $courseId, $semester)
    {
        $semesterIds = \App\Models\Semester::where('course_id', (int) $courseId)
            ->where(function ($q) use ($semester) {
                $q->where('name', $semester)->orWhere('id', $semester);
            })
            ->pluck('id')
            ->all();

        $results = \App\Models\ExamResult::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where(function ($q) use ($semester, $semesterIds) {
                $q->where('semester', $semester);
                if ($semesterIds) {
                    $q->orWhereIn('semester', $semesterIds);
                }
            })
            ->with('module')
            ->get()
            ->map(function ($r) {
                return [
                    'module_name' => $r->module->module_name ?? 'N/A',
                    'marks' => $r->marks,
                    'grade' => $r->grade,
                ];
            });
        return response()->json(['success' => true, 'results' => $results]);
    }

    public function getPaymentSummary($studentId, $courseId)
    {
        try {
            // Find student by ID
            $student = Student::find($studentId);
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
            }

            // Get course registration for this student and course
            $registration = CourseRegistration::where('student_id', $student->student_id)
                ->where('course_id', $courseId)
                ->with(['course', 'intake'])
                ->first();
            if (!$registration) {
                return response()->json(['success' => false, 'message' => 'Student is not registered for this course.'], 404);
            }

            // Get all payment records for this student and course registration
            $payments = \App\Models\PaymentDetail::where('student_id', $student->student_id)
                ->where('course_registration_id', $registration->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Build registration fee amount and payment plan installments
            $baseRegistrationFee = $registration->intake->registration_fee ?? 0;
            $studentPaymentPlan = StudentPaymentPlan::where('student_id', $student->student_id)
                ->where('course_id', $registration->course_id)
                ->where('status', '!=', 'archived')
                ->orderByDesc('id')
                ->with(['installments', 'discounts.discount'])
                ->first();

            $registrationFee = $this->calculateRegistrationFeeAmount($baseRegistrationFee, $studentPaymentPlan);
            $intakeCourseFee = (float) ($registration->intake->course_fee ?? $registration->course->course_fee ?? 0);
            $intakeFranchiseFee = (float) ($registration->intake->franchise_payment ?? 0);
            $franchiseCurrency = $registration->intake->franchise_payment_currency
                ?? $registration->intake->international_currency
                ?? 'USD';
            if ($studentPaymentPlan && !empty($studentPaymentPlan->international_currency)) {
                $franchiseCurrency = $studentPaymentPlan->international_currency;
            }

            $installmentSpecs = $this->collectPaymentInstallmentSpecs($studentPaymentPlan, $registration, $franchiseCurrency);
            if (!empty($installmentSpecs['franchise_currency'])) {
                $franchiseCurrency = $installmentSpecs['franchise_currency'];
            }
            $specs = $installmentSpecs['specs'];
            $includesRegistration = $this->planLocalTotalsIncludeRegistration(
                $specs,
                (float) $registrationFee,
                $intakeCourseFee,
                $studentPaymentPlan
            );
            if ($includesRegistration) {
                $specs = $this->unbundleRegistrationFromLocalSpecs($specs, (float) $registrationFee);
            }

            $courseInstallmentRows = [];
            $franchiseInstallmentRows = [];
            $courseFee = 0;
            $franchiseFee = 0;

            foreach ($specs as $spec) {
                $installmentNumber = $spec['installment_number'];
                $dueDate = $spec['due_date'];
                $localAmount = round((float) ($spec['local_amount'] ?? 0), 2);
                $franchiseAmount = round((float) ($spec['franchise_amount'] ?? 0), 2);

                if ($localAmount > 0) {
                    $row = $this->buildFeeRowFromPayments($payments, 'course_fee', $installmentNumber, $localAmount, $dueDate);
                    $courseFee += $localAmount;
                    $courseInstallmentRows[] = $row;
                }

                if ($franchiseAmount > 0) {
                    $row = $this->buildFeeRowFromPayments($payments, 'franchise_fee', $installmentNumber, $franchiseAmount, $dueDate);
                    $row['amount_currency'] = $franchiseAmount;
                    $row['currency'] = $franchiseCurrency;
                    $row['sscl_tax'] = $row['sscl_tax'] ?? 0;
                    $row['bank_charges'] = $row['bank_charges'] ?? 0;
                    $row['total_amount_lkr'] = $row['total_amount_lkr'] ?? $franchiseAmount;
                    $franchiseFee += $franchiseAmount;
                    $franchiseInstallmentRows[] = $row;
                }
            }

            if (empty($courseInstallmentRows) && $intakeCourseFee > 0) {
                $courseInstallmentRows[] = $this->buildContractedFeeFallbackRow($payments, 'course_fee', $intakeCourseFee, optional($registration->registration_date)->format('Y-m-d'));
                $courseFee = $intakeCourseFee;
            }
            if (empty($franchiseInstallmentRows) && $intakeFranchiseFee > 0) {
                $row = $this->buildContractedFeeFallbackRow($payments, 'franchise_fee', $intakeFranchiseFee, optional($registration->registration_date)->format('Y-m-d'));
                $row['amount_currency'] = $intakeFranchiseFee;
                $row['currency'] = $franchiseCurrency;
                $franchiseInstallmentRows[] = $row;
                $franchiseFee = $intakeFranchiseFee;
            }

            $registrationPayments = $this->paymentsOfType($payments, 'registration_fee');
            $registrationPaid = (float) $registrationPayments->sum('amount');
            if ($includesRegistration) {
                $allocated = $this->allocateBundledRegistrationPaid($courseInstallmentRows, $registrationPaid, (float) $registrationFee);
                $courseInstallmentRows = $allocated['course_rows'];
                $registrationPaid = $allocated['registration_paid'];
                $courseFee = collect($courseInstallmentRows)->sum('total_amount');
            }
            $registrationOutstanding = max(round((float) $registrationFee - $registrationPaid, 2), 0);
            $latestRegistrationPayment = $this->latestPayment($registrationPayments);
            $registrationRows = [[
                'total_amount' => round((float) $registrationFee, 2),
                'paid_amount' => round($registrationPaid, 2),
                'outstanding' => $registrationOutstanding,
                'payment_date' => $this->paymentDate($latestRegistrationPayment),
                'due_date' => optional($registration->registration_date)->format('Y-m-d'),
                'receipt_no' => $latestRegistrationPayment->transaction_id ?? null,
                'uploaded_receipt' => $latestRegistrationPayment && $latestRegistrationPayment->paid_slip_path ? asset('storage/' . $latestRegistrationPayment->paid_slip_path) : null,
                'installment_number' => 1,
            ]];

            $libraryRows = $this->buildPaymentRowsFromDetails($payments, 'library_fee');
            $hostelRows = $this->buildPaymentRowsFromDetails($payments, 'hostel_fee');
            $otherRows = $this->buildPaymentRowsFromDetails($payments, 'other');

            $paymentTypes = [
                'course_fee' => ['payments' => $courseInstallmentRows, 'total' => $courseFee, 'paid' => collect($courseInstallmentRows)->sum('paid_amount')],
                'franchise_fee' => ['payments' => $franchiseInstallmentRows, 'total' => $franchiseFee, 'paid' => collect($franchiseInstallmentRows)->sum('paid_amount')],
                'registration_fee' => ['payments' => $registrationRows, 'total' => $registrationFee, 'paid' => $registrationPaid],
                'library_fee' => ['payments' => $libraryRows, 'total' => collect($libraryRows)->sum('total_amount'), 'paid' => collect($libraryRows)->sum('paid_amount')],
                'hostel_fee' => ['payments' => $hostelRows, 'total' => collect($hostelRows)->sum('total_amount'), 'paid' => collect($hostelRows)->sum('paid_amount')],
                'other' => ['payments' => $otherRows, 'total' => collect($otherRows)->sum('total_amount'), 'paid' => collect($otherRows)->sum('paid_amount')],
            ];

            $paymentDetails = [];
            foreach ($paymentTypes as $type => $data) {
                if ($data['total'] > 0 || $data['paid'] > 0 || count($data['payments']) > 0) {
                    $outstanding = max(0, $data['total'] - $data['paid']);
                    $paymentRate = $data['total'] > 0 ? round(($data['paid'] / $data['total']) * 100, 2) : 0;
                    $lastPayment = collect($data['payments'])->sortByDesc('payment_date')->first();
                    $lastPaymentDate = $lastPayment ? $lastPayment['payment_date'] : null;

                    $paymentDetails[] = [
                        'payment_type' => $type,
                        'total_amount' => $data['total'],
                        'paid_amount' => $data['paid'],
                        'outstanding' => $outstanding,
                        'payment_rate' => $paymentRate,
                        'installment_count' => count($data['payments']),
                        'last_payment_date' => $lastPaymentDate,
                        'payments' => $data['payments'],
                    ];
                }
            }

            $totalPaid = collect($paymentDetails)->sum('paid_amount');
            $totalOutstanding = collect($paymentDetails)->sum('outstanding');
            $totalCourseAmount = $courseFee + $franchiseFee + $registrationFee;
            $overallPaymentRate = $totalCourseAmount > 0 ? round(($totalPaid / $totalCourseAmount) * 100, 2) : 0;

            $paymentHistory = $payments->map(function ($payment) {
                return [
                    'payment_date' => $payment->payment_effective_date ? $payment->payment_effective_date->format('Y-m-d') : $payment->created_at->format('Y-m-d'),
                    'payment_type' => $this->getPaymentTypeDisplay($payment->installment_type ?? $payment->payment_type ?? 'course_fee'),
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'receipt_no' => $payment->transaction_id,
                    'status' => $payment->status === 'paid' ? 'Paid' : 'Pending'
                ];
            })->toArray();

            $localPaid = collect($courseInstallmentRows)->sum('paid_amount') + $registrationPaid;
            $franchisePaid = collect($franchiseInstallmentRows)->sum('paid_amount');
            $summary = [
                'student' => [
                    'student_id' => $student->student_id,
                    'student_name' => $student->full_name,
                    'course_name' => $registration->course->course_name,
                    'registration_date' => $registration->registration_date ? $registration->registration_date->format('Y-m-d') : '',
                    'total_amount' => $totalCourseAmount
                ],
                'total_amount' => $totalCourseAmount,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalOutstanding,
                'payment_rate' => $overallPaymentRate,
                'course_fee' => round((float) $courseFee, 2),
                'registration_fee' => round((float) $registrationFee, 2),
                'total_local_amount' => round((float) $courseFee + (float) $registrationFee, 2),
                'total_franchise_amount' => round((float) $franchiseFee, 2),
                'local_paid' => round((float) $localPaid, 2),
                'franchise_paid' => round((float) $franchisePaid, 2),
                'local_outstanding' => round(max(0, ($courseFee + $registrationFee) - $localPaid), 2),
                'franchise_currency' => $franchiseCurrency,
                'payment_details' => $paymentDetails,
                'payment_history' => $paymentHistory,
                'slt_loan_receivables' => $this->buildSltLoanReceivableSummary($studentPaymentPlan),
            ];

            return response()->json([
                'success' => true,
                'summary' => $summary
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    private function calculateRegistrationFeeAmount($baseRegistrationFee, $studentPaymentPlan)
    {
        if (!$studentPaymentPlan) {
            return (float) $baseRegistrationFee;
        }

        $discountRecord = \App\Models\PaymentPlanDiscount::where('payment_plan_id', $studentPaymentPlan->id)
            ->whereHas('discount', function ($query) {
                $query->where('discount_category', 'registration_fee');
            })
            ->with('discount')
            ->first();

        if (!$discountRecord || !$discountRecord->discount) {
            return (float) $baseRegistrationFee;
        }

        $discount = $discountRecord->discount;
        $discountAmount = 0;

        if ($discount->type === 'percentage') {
            $discountAmount = ($baseRegistrationFee * $discount->value) / 100;
        } else {
            $discountAmount = min((float) $discount->value, (float) $baseRegistrationFee);
        }

        return max(0, (float) $baseRegistrationFee - $discountAmount);
    }

    private function collectPaymentInstallmentSpecs(?StudentPaymentPlan $studentPaymentPlan, $registration, string $franchiseCurrency): array
    {
        $specs = [];
        $resolvedCurrency = $franchiseCurrency;

        if ($studentPaymentPlan && $studentPaymentPlan->installments->isNotEmpty()) {
            foreach ($studentPaymentPlan->installments->sortBy('installment_number') as $installment) {
                $localAmount = (float) ($installment->final_amount ?? $installment->amount ?? 0);
                $franchiseAmount = (float) ($installment->international_amount ?? 0);
                if ($localAmount <= 0 && $franchiseAmount <= 0) {
                    continue;
                }

                $specs[] = [
                    'installment_number' => $installment->installment_number,
                    'due_date' => optional($installment->due_date)->format('Y-m-d'),
                    'local_amount' => $localAmount,
                    'franchise_amount' => $franchiseAmount,
                    'base_local_amount' => (float) ($installment->base_amount ?? $installment->amount ?? $localAmount),
                ];
            }

            return ['specs' => $specs, 'franchise_currency' => $resolvedCurrency];
        }

        $paymentPlan = PaymentPlan::where('course_id', $registration->course_id)
            ->where('intake_id', $registration->intake_id)
            ->first();
        if ($paymentPlan && !empty($paymentPlan->international_currency)) {
            $resolvedCurrency = $paymentPlan->international_currency;
        }
        if (!$paymentPlan || !$paymentPlan->installments) {
            return ['specs' => $specs, 'franchise_currency' => $resolvedCurrency];
        }

        $installmentsData = $paymentPlan->installments;
        if (is_string($installmentsData)) {
            $installmentsData = json_decode($installmentsData, true);
        }
        if (!is_array($installmentsData)) {
            return ['specs' => $specs, 'franchise_currency' => $resolvedCurrency];
        }

        foreach ($installmentsData as $installment) {
            $localAmount = (float) ($installment['local_amount'] ?? $installment['amount'] ?? 0);
            $franchiseAmount = (float) ($installment['international_amount'] ?? 0);
            if ($localAmount <= 0 && $franchiseAmount <= 0) {
                continue;
            }

            $specs[] = [
                'installment_number' => $installment['installment_number'] ?? null,
                'due_date' => $installment['due_date'] ?? null,
                'local_amount' => $localAmount,
                'franchise_amount' => $franchiseAmount,
                'base_local_amount' => $localAmount,
            ];
        }

        return ['specs' => $specs, 'franchise_currency' => $resolvedCurrency];
    }

    private function planLocalTotalsIncludeRegistration(array $specs, float $registrationFee, float $intakeCourseFee, ?StudentPaymentPlan $plan): bool
    {
        if ($registrationFee <= 0 || empty($specs)) {
            return false;
        }

        $localAmounts = [];
        $baseAmounts = [];
        foreach ($specs as $spec) {
            $local = round((float) ($spec['local_amount'] ?? 0), 2);
            if ($local <= 0) {
                continue;
            }
            $localAmounts[] = $local;
            $baseAmounts[] = round((float) ($spec['base_local_amount'] ?? $local), 2);
        }

        if (empty($localAmounts)) {
            return false;
        }

        $baseSum = round(array_sum($baseAmounts), 2);
        $bundledList = round($intakeCourseFee + $registrationFee, 2);
        $planTotal = $plan ? round((float) ($plan->total_amount ?? 0), 2) : 0.0;

        if ($bundledList > 0 && abs($baseSum - $bundledList) <= 1) {
            return true;
        }

        if ($planTotal > 0 && abs($baseSum - $planTotal) <= 1 && abs($planTotal - $bundledList) <= 1) {
            return true;
        }

        if (count($localAmounts) === 1 && $intakeCourseFee > 0) {
            $amount = $localAmounts[0];
            if ($amount > $intakeCourseFee + 1 && $amount <= $bundledList + 1) {
                return true;
            }
        }

        return false;
    }

    private function unbundleRegistrationFromLocalSpecs(array $specs, float $registrationFee): array
    {
        $remaining = round($registrationFee, 2);
        foreach ($specs as &$spec) {
            if ($remaining <= 0) {
                break;
            }
            $local = round((float) ($spec['local_amount'] ?? 0), 2);
            if ($local <= 0) {
                continue;
            }
            $deduct = min($local, $remaining);
            $spec['local_amount'] = round($local - $deduct, 2);
            $remaining = round($remaining - $deduct, 2);
        }
        unset($spec);

        return $specs;
    }

    private function paymentsOfType($payments, string $type)
    {
        return $payments->filter(function ($payment) use ($type) {
            return $this->categorizePaymentType($payment->installment_type ?? $payment->payment_type ?? '') === $type;
        });
    }

    private function latestPayment($payments)
    {
        return $payments->sortByDesc(function ($payment) {
            return $payment->payment_effective_date
                ? $payment->payment_effective_date->timestamp
                : $payment->created_at->timestamp;
        })->first();
    }

    private function paymentDate($payment): ?string
    {
        if (!$payment) {
            return null;
        }

        return $payment->payment_effective_date
            ? $payment->payment_effective_date->format('Y-m-d')
            : $payment->created_at->format('Y-m-d');
    }

    private function buildFeeRowFromPayments($payments, string $type, $installmentNumber, float $totalAmount, ?string $dueDate): array
    {
        $matched = $this->paymentsOfType($payments, $type)->filter(function ($payment) use ($installmentNumber) {
            return is_null($installmentNumber) || $payment->installment_number == $installmentNumber;
        });
        $latestPayment = $this->latestPayment($matched);
        $paidAmount = round((float) $matched->sum('amount'), 2);
        $totalAmount = round($totalAmount, 2);

        $row = [
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'outstanding' => round(max($totalAmount - $paidAmount, 0), 2),
            'payment_date' => $this->paymentDate($latestPayment),
            'due_date' => $dueDate,
            'receipt_no' => $latestPayment->transaction_id ?? null,
            'uploaded_receipt' => $latestPayment && $latestPayment->paid_slip_path ? asset('storage/' . $latestPayment->paid_slip_path) : null,
            'installment_number' => $installmentNumber,
        ];

        if ($type === 'franchise_fee' && $latestPayment) {
            $row['sscl_tax'] = (float) ($latestPayment->sscl_tax_amount ?? 0);
            $row['bank_charges'] = (float) ($latestPayment->bank_charges ?? 0);
            $row['total_amount_lkr'] = (float) ($latestPayment->total_fee ?? $totalAmount);
        }

        return $row;
    }

    private function buildContractedFeeFallbackRow($payments, string $type, float $contractedTotal, ?string $dueDate): array
    {
        $matched = $this->paymentsOfType($payments, $type);
        $latestPayment = $this->latestPayment($matched);
        $paidAmount = round((float) $matched->sum('amount'), 2);
        $contractedTotal = round($contractedTotal, 2);

        $row = [
            'total_amount' => $contractedTotal,
            'paid_amount' => $paidAmount,
            'outstanding' => round(max($contractedTotal - $paidAmount, 0), 2),
            'payment_date' => $this->paymentDate($latestPayment),
            'due_date' => $dueDate,
            'receipt_no' => $latestPayment->transaction_id ?? null,
            'uploaded_receipt' => $latestPayment && $latestPayment->paid_slip_path ? asset('storage/' . $latestPayment->paid_slip_path) : null,
            'installment_number' => $latestPayment->installment_number ?? 1,
        ];

        if ($type === 'franchise_fee' && $latestPayment) {
            $row['sscl_tax'] = (float) ($latestPayment->sscl_tax_amount ?? 0);
            $row['bank_charges'] = (float) ($latestPayment->bank_charges ?? 0);
            $row['total_amount_lkr'] = (float) ($latestPayment->total_fee ?? $contractedTotal);
        }

        return $row;
    }

    private function allocateBundledRegistrationPaid(array $courseRows, float $registrationPaid, float $registrationFee): array
    {
        $regGap = round(max(0, $registrationFee - $registrationPaid), 2);
        if ($regGap <= 0) {
            return ['course_rows' => $courseRows, 'registration_paid' => round($registrationPaid, 2)];
        }

        foreach ($courseRows as &$row) {
            if ($regGap <= 0) {
                break;
            }
            $paid = round((float) ($row['paid_amount'] ?? 0), 2);
            if ($paid <= 0) {
                continue;
            }
            $shift = min($paid, $regGap);
            $row['paid_amount'] = round($paid - $shift, 2);
            $row['outstanding'] = round(max((float) $row['total_amount'] - (float) $row['paid_amount'], 0), 2);
            $registrationPaid = round($registrationPaid + $shift, 2);
            $regGap = round($regGap - $shift, 2);
        }
        unset($row);

        return ['course_rows' => $courseRows, 'registration_paid' => round($registrationPaid, 2)];
    }

    private function buildSltLoanReceivableSummary(?StudentPaymentPlan $plan): ?array
    {
        if (!$plan || ($plan->slt_loan_applied ?? 'no') !== 'yes') {
            return null;
        }

        $loanAmount = (float) ($plan->slt_loan_amount ?? 0);
        $years = (int) ($plan->slt_loan_years ?? 0);
        $installmentCount = $years > 0 ? $years * 12 : 0;
        $monthlyReceivable = $installmentCount > 0 ? round($loanAmount / $installmentCount, 2) : 0;

        $records = collect();
        try {
            $records = SltLoanReceivableRecord::where('student_payment_plan_id', $plan->id)
                ->orderBy('loan_installment_number')
                ->get()
                ->keyBy('loan_installment_number');
        } catch (\Throwable $e) {
            \Log::warning("slt_loan_receivable_records table query failed in StudentProfileController: " . $e->getMessage());
        }

        $installments = [];
        for ($i = 1; $i <= $installmentCount; $i++) {
            $record = $records->get($i);
            $installments[] = [
                'installment_number' => $i,
                'receivable_amount' => $monthlyReceivable,
                'status' => $record ? 'Recorded' : 'Pending',
                'payment_effective_date' => $record?->payment_effective_date?->format('Y-m-d'),
                'recorded_at' => $record?->created_at?->format('Y-m-d H:i'),
            ];
        }

        return [
            'slt_loan_amount' => $loanAmount,
            'loan_taken_years' => $years,
            'loan_installment_count' => $installmentCount,
            'apply_from_installment' => (int) ($plan->slt_loan_start_installment ?? 1),
            'monthly_receivable' => $monthlyReceivable,
            'last_payment_effective_date' => optional($plan->slt_receivable_effective_date)->format('Y-m-d'),
            'recorded_count' => $records->count(),
            'pending_count' => max(0, $installmentCount - $records->count()),
            'installments' => $installments,
        ];
    }

    private function buildPaymentRowsFromDetails($payments, $type)
    {
        $rows = [];

        $payments->filter(function ($payment) use ($type) {
            return $this->categorizePaymentType($payment->installment_type ?? $payment->payment_type ?? '') === $type;
        })->each(function ($payment) use (&$rows, $type) {
            $paidAmount = (float) ($payment->amount ?? 0);
            $totalAmount = (float) ($payment->total_fee ?? $payment->amount ?? 0);
            $outstanding = $payment->remaining_amount !== null
                ? max(0, (float) $payment->remaining_amount)
                : max($totalAmount - $paidAmount, 0);
            $paymentDate = $payment->payment_effective_date
                ? $payment->payment_effective_date->format('Y-m-d')
                : $payment->created_at->format('Y-m-d');

            $row = [
                'total_amount' => $totalAmount,
                'paid_amount' => $paidAmount,
                'outstanding' => $outstanding,
                'payment_date' => $paymentDate,
                'due_date' => $payment->due_date ? $payment->due_date->format('Y-m-d') : null,
                'receipt_no' => $payment->transaction_id,
                'uploaded_receipt' => $payment->paid_slip_path ? asset('storage/' . $payment->paid_slip_path) : null,
                'installment_number' => $payment->installment_number,
            ];

            if ($type === 'franchise_fee') {
                $row['amount_currency'] = (float) ($payment->foreign_currency_amount ?? $payment->amount ?? 0);
                $row['currency'] = $payment->foreign_currency_code ?? 'USD';
                $row['sscl_tax'] = (float) ($payment->sscl_tax_amount ?? 0);
                $row['bank_charges'] = (float) ($payment->bank_charges ?? 0);
                $row['total_amount_lkr'] = (float) ($payment->total_fee ?? $payment->amount ?? 0);
            }

            $rows[] = $row;
        });

        return $rows;
    }

    // Helper: categorize payment type
    private function categorizePaymentType($paymentType)
    {
        $types = [
            'course_fee' => 'course_fee',
            'franchise_fee' => 'franchise_fee',
            'registration_fee' => 'registration_fee',
            'library_fee' => 'library_fee',
            'hostel_fee' => 'hostel_fee',
            'other' => 'other',
        ];
        return $types[$paymentType] ?? 'other';
    }

    // Helper: display name for payment type
    private function getPaymentTypeDisplay($paymentType)
    {
        $types = [
            'course_fee' => 'Course Fee',
            'franchise_fee' => 'Franchise Fee',
            'registration_fee' => 'Registration Fee',
            'library_fee' => 'Library Fee',
            'hostel_fee' => 'Hostel Fee',
            'other' => 'Other',
        ];
        return $types[$paymentType] ?? ucfirst(str_replace('_', ' ', $paymentType));
    }


    // API: Get attendance records for a specific student, course, and semester
    public function getAttendance($studentId, $courseId, $semester)
    {
        try {
            // Log the incoming parameters for debugging
            \Log::debug('getAttendance called with', [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'semester' => $semester,
                'types' => [
                    'student_id_type' => gettype($studentId),
                    'course_id_type' => gettype($courseId),
                    'semester_type' => gettype($semester)
                ]
            ]);

            $semesterName = (string)$semester;
            $semestersList = \App\Models\Semester::where('course_id', (int)$courseId)->get();
            $semesterLookupValues = [$semesterName];

            foreach ($semestersList as $sModel) {
                if ((string)$sModel->id === $semesterName) {
                    $semesterLookupValues[] = trim((string)$sModel->name);
                } elseif (trim((string)$sModel->name) === $semesterName) {
                    $semesterLookupValues[] = (string)$sModel->id;
                }
            }
            $semesterLookupValues = array_values(array_unique($semesterLookupValues));

            // Build the query to fetch attendance records
            $query = \App\Models\Attendance::where('student_id', (int)$studentId)
                ->where('course_id', (int)$courseId)
                ->whereIn('semester', $semesterLookupValues)
                ->with('module');

            $attendanceRecords = $query->get();
            
            \Log::debug('Attendance records found', [
                'count' => $attendanceRecords->count(),
                'first_record' => $attendanceRecords->first()?->toArray()
            ]);

            if ($attendanceRecords->isEmpty()) {
                return response()->json(['success' => true, 'attendance' => []]);
            }

            // Group by module_id and transform the data
            $attendance = $attendanceRecords
                ->groupBy('module_id')
                ->map(function ($records, $moduleId) {
                    $firstRecord = $records->first();
                    
                    // Safely access module relationship
                    $moduleName = optional($firstRecord->module)->module_name ?? 'N/A';
                    $totalDays = $records->count();
                    
                    // Count present (status = 1 or true) and absent (status = 0 or false)
                    $presentDays = 0;
                    $absentDays = 0;
                    
                    foreach ($records as $record) {
                        // Handle both integer (0/1) and boolean representations
                        $status = $record->status;
                        if ($status === true || $status == 1 || $status === '1') {
                            $presentDays++;
                        } elseif ($status === false || $status == 0 || $status === '0') {
                            $absentDays++;
                        }
                    }
                    
                    $attendancePercent = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
                    
                    return [
                        'module_name' => $moduleName,
                        'total_days' => $totalDays,
                        'present_days' => $presentDays,
                        'absent_days' => $absentDays,
                        'attendance_percent' => $attendancePercent
                    ];
                })
                ->values();

            return response()->json(['success' => true, 'attendance' => $attendance]);
        } catch (\Exception $e) {
            \Log::error('Error fetching attendance', [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'semester' => $semester,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Failed to fetch attendance records: ' . $e->getMessage()
            ], 500);
        }
    }

    // API: Get attendance semesters for a student and course (diagnostic method)
    public function getAttendanceSemesters($studentId, $courseId)
    {
        try {
            $semesters = \App\Models\Attendance::where('student_id', (int)$studentId)
                ->where('course_id', (int)$courseId)
                ->distinct('semester')
                ->pluck('semester')
                ->filter()
                ->sort()
                ->values();

            $count = \App\Models\Attendance::where('student_id', (int)$studentId)
                ->where('course_id', (int)$courseId)
                ->count();

            \Log::debug('getAttendanceSemesters', [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'semesters' => $semesters->toArray(),
                'total_records' => $count
            ]);

            return response()->json([
                'success' => true,
                'semesters' => $semesters,
                'total_records' => $count
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance semesters: ' . $e->getMessage()
            ], 500);
        }
    }

    // API: Get attendance records for certificate courses (no semester)
    public function getAttendanceForCertificate($studentId, $courseId)
    {
        try {
            // Log the incoming parameters for debugging
            \Log::debug('getAttendanceForCertificate called with', [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'types' => [
                    'student_id_type' => gettype($studentId),
                    'course_id_type' => gettype($courseId)
                ]
            ]);

            // Build the query to fetch attendance records for certificate courses (semester is null or empty)
            $query = \App\Models\Attendance::where('student_id', (int)$studentId)
                ->where('course_id', (int)$courseId)
                ->where(function($q) {
                    $q->whereNull('semester')
                      ->orWhere('semester', '');
                })
                ->with('module');

            $attendanceRecords = $query->get();
            
            \Log::debug('Certificate attendance records found', [
                'count' => $attendanceRecords->count(),
                'first_record' => $attendanceRecords->first()?->toArray()
            ]);

            if ($attendanceRecords->isEmpty()) {
                return response()->json(['success' => true, 'attendance' => []]);
            }

            // Group by module_id and transform the data
            $attendance = $attendanceRecords
                ->groupBy('module_id')
                ->map(function ($records, $moduleId) {
                    $firstRecord = $records->first();
                    
                    // Safely access module relationship
                    $moduleName = optional($firstRecord->module)->module_name ?? 'N/A';
                    $totalDays = $records->count();
                    $presentDays = $records->where('status', 'present')->count();
                    $absentDays = $records->where('status', 'absent')->count();
                    
                    $attendancePercent = $totalDays > 0 ? round(($presentDays / $totalDays) * 100, 2) : 0;
                    
                    return [
                        'module_name' => $moduleName,
                        'total_days' => $totalDays,
                        'present_days' => $presentDays,
                        'absent_days' => $absentDays,
                        'attendance_percent' => $attendancePercent . '%'
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'attendance' => $attendance
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getAttendanceForCertificate', [
                'student_id' => $studentId,
                'course_id' => $courseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false, 
                'message' => 'Failed to fetch attendance records: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getStudentClearances($studentId)
    {
        $clearances = \App\Models\ClearanceRequest::where('student_id', $studentId)
            ->get()
            ->map(function ($c) {
                return [
                    'label' => $c->getClearanceTypeTextAttribute(),
                    'status' => $c->status === \App\Models\ClearanceRequest::STATUS_APPROVED,
                    'approved_date' => $c->approved_at ? $c->approved_at->format('d/m/Y') : null,
                    'remarks' => $c->remarks,
                    'clearance_slip' => $c->clearance_slip,
                ];
            });

        return response()->json([
            'success' => true,
            'clearances' => $clearances
        ]);
    }


    public function getStudentCertificates($studentId)
    {
        $student = \App\Models\Student::find($studentId);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        // Get latest OL and AL exam records
        $ol_exam = \App\Models\StudentExam::where('student_id', $studentId)
            ->whereNotNull('ol_certificate')
            ->orderByDesc('created_at')
            ->first();

        $al_exam = \App\Models\StudentExam::where('student_id', $studentId)
            ->whereNotNull('al_certificate')
            ->orderByDesc('created_at')
            ->first();

        $otherInfo = \App\Models\StudentOtherInformation::where('student_id', $studentId)->first();

        $ol_cert = $ol_exam && !empty($ol_exam->ol_certificate) ? $ol_exam->ol_certificate : null;
        $al_cert = $al_exam && !empty($al_exam->al_certificate) ? $al_exam->al_certificate : null;
        $disciplinary_doc = $otherInfo && !empty($otherInfo->disciplinary_issue_document) ? $otherInfo->disciplinary_issue_document : null;

        return response()->json([
            'success' => true,
            'ol_certificate' => $ol_cert,
            'al_certificate' => $al_cert,
            'disciplinary_issue_document' => $disciplinary_doc,
        ]);
    }

    /**
     * Upload OL certificate for a student
     */
    public function uploadOLCertificate(Request $request, $studentId)
    {
        $request->validate([
            'ol_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $student = Student::find($studentId);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        try {
            // Store file in storage/app/public/certificates/ol
            $path = $request->file('ol_certificate')->store('certificates/ol', 'public');

            // Find or create StudentExam record for this student
            $studentExam = \App\Models\StudentExam::where('student_id', $studentId)->first();
            
            if (!$studentExam) {
                $studentExam = new \App\Models\StudentExam();
                $studentExam->student_id = $studentId;
            }

            // Delete previous OL certificate if it exists
            if (!empty($studentExam->ol_certificate) && Storage::disk('public')->exists($studentExam->ol_certificate)) {
                try {
                    Storage::disk('public')->delete($studentExam->ol_certificate);
                } catch (\Throwable $e) {
                    Log::warning('Failed to delete old OL certificate', [
                        'student_id' => $studentId,
                        'old_path' => $studentExam->ol_certificate,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Save new path
            $studentExam->ol_certificate = $path;
            $studentExam->save();

            return response()->json([
                'success' => true,
                'message' => 'OL certificate uploaded successfully.',
                'url' => asset('storage/' . $path),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload OL certificate', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload OL certificate. Please try again.',
            ], 500);
        }
    }

    /**
     * Upload AL certificate for a student
     */
    public function uploadALCertificate(Request $request, $studentId)
    {
        $request->validate([
            'al_certificate' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $student = Student::find($studentId);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        try {
            // Store file in storage/app/public/certificates/al
            $path = $request->file('al_certificate')->store('certificates/al', 'public');

            // Find or create StudentExam record for this student
            $studentExam = \App\Models\StudentExam::where('student_id', $studentId)->first();
            
            if (!$studentExam) {
                $studentExam = new \App\Models\StudentExam();
                $studentExam->student_id = $studentId;
            }

            // Delete previous AL certificate if it exists
            if (!empty($studentExam->al_certificate) && Storage::disk('public')->exists($studentExam->al_certificate)) {
                try {
                    Storage::disk('public')->delete($studentExam->al_certificate);
                } catch (\Throwable $e) {
                    Log::warning('Failed to delete old AL certificate', [
                        'student_id' => $studentId,
                        'old_path' => $studentExam->al_certificate,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Save new path
            $studentExam->al_certificate = $path;
            $studentExam->save();

            return response()->json([
                'success' => true,
                'message' => 'AL certificate uploaded successfully.',
                'url' => asset('storage/' . $path),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to upload AL certificate', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to upload AL certificate. Please try again.',
            ], 500);
        }
    }

    // API: Get student status history (terminate / reinstate logs)
    public function getStudentStatusHistory($studentId)
    {
        $history = \App\Models\StudentStatusHistory::where('student_id', $studentId)
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($h) {
                return [
                    'id' => $h->id,
                    'from_status' => $h->from_status,
                    'to_status' => $h->to_status,
                    'reason' => $h->reason,
                    'document' => $h->document,
                    'changed_by' => $h->changed_by,
                    'changed_by_name' => $h->user ? ($h->user->name ?? null) : null,
                    'created_at' => $h->created_at ? $h->created_at->format('d/m/Y H:i') : null,
                ];
            });

        return response()->json(['success' => true, 'history' => $history]);
    }

    public function terminate(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'reason'     => 'required|string|max:2000',
            'document'   => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $student = \App\Models\Student::where('student_id', $request->student_id)->firstOrFail();

        if ($student->isTerminated()) {
            return response()->json([
                'success' => false,
                'message' => 'This student is already terminated. Use Re-Register on the Student Profile to restore them, or process clearance from All Clearance / Termination Tracking. Do not create a new student record.',
                'profile_url' => route('student_management.profile', ['studentId' => $student->student_id]),
            ], 422);
        }

        // optional: store doc
        $path = $request->file('document')?->store('termination_docs', 'public');

        // save history (create a model/table if you want an audit trail)
        \App\Models\StudentStatusHistory::create([
            'student_id' => $student->student_id,
            'from_status' => $student->academic_status ?? 'active',
            'to_status'  => 'terminated',
            'reason'     => $request->reason,
            'document'   => $path,
            'changed_by' => auth()->id(),
        ]);

        $student->academic_status = 'terminated';
        $student->save();

        // Auto-create clearance requests for all 4 clearance types
        $clearanceTypes = [
            ClearanceRequest::TYPE_HOSTEL,
            ClearanceRequest::TYPE_LIBRARY,
            ClearanceRequest::TYPE_PAYMENT,
            ClearanceRequest::TYPE_PROJECT,
        ];

        $registrations = CourseRegistration::where('student_id', $student->student_id)->get();

        foreach ($registrations as $registration) {
            foreach ($clearanceTypes as $type) {
                $exists = ClearanceRequest::where('student_id', $student->student_id)
                    ->where('clearance_type', $type)
                    ->where('course_id', $registration->course_id)
                    ->where('intake_id', $registration->intake_id)
                    ->exists();

                if (!$exists) {
                    ClearanceRequest::create([
                        'clearance_type' => $type,
                        'location'       => $registration->location,
                        'course_id'      => $registration->course_id,
                        'intake_id'      => $registration->intake_id,
                        'student_id'     => $student->student_id,
                        'status'         => ClearanceRequest::STATUS_PENDING,
                        'requested_at'   => now(),
                    ]);
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Student terminated successfully. Clearance requests have been sent for hostel, library, payment, and project.']);
    }

    public function reinstate(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,student_id',
            'reason'     => 'required|string|max:2000',
            'document'   => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        ]);

        $student = \App\Models\Student::where('student_id', $request->student_id)->firstOrFail();

        $path = $request->file('document')?->store('reinstate_docs', 'public');

        \App\Models\StudentStatusHistory::create([
            'student_id' => $student->student_id,
            'from_status' => $student->academic_status ?? 'terminated',
            'to_status'  => 'active',
            'reason'     => $request->reason,
            'document'   => $path,
            'changed_by' => auth()->id(),
        ]);

        $student->academic_status = 'active';
        $student->save();

        return response()->json([
            'success' => true,
            'message' => 'Student re-registered successfully.',
            'academic_status' => 'active',
        ]);
    }

    /**
     * Update student profile picture
     */
    public function updateStudentProfilePicture(Request $request, $studentId)
    {
        $request->validate([
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg,webp|max:4096',
        ]);

        $student = Student::find($studentId);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        try {
            // Store file in storage/app/public/student_profile_pictures
            $path = $request->file('profile_picture')->store('student_profile_pictures', 'public');

            // Delete previous file if it exists
            if (!empty($student->user_photo) && Storage::disk('public')->exists($student->user_photo)) {
                try {
                    Storage::disk('public')->delete($student->user_photo);
                } catch (\Throwable $e) {
                    // Log error but don't fail the update
                    Log::warning('Failed to delete old student profile picture', [
                        'student_id' => $studentId,
                        'old_path' => $student->user_photo,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Save new path to student record
            $student->user_photo = $path;
            $student->save();

            return response()->json([
                'success' => true,
                'message' => 'Profile picture updated successfully.',
                'path' => $path,
                'url' => '/storage/' . ltrim($path, '/'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update student profile picture', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update profile picture. Please try again.',
            ], 500);
        }
    }

    /**
     * Update O/L exam results for a student
     */
    public function updateOLResults(Request $request, $studentId)
    {
        $request->validate([
            'ol_index_no' => 'nullable|string|max:255',
            'ol_exam_type' => 'required|string|max:255',
            'ol_exam_year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'ol_exam_subjects' => 'required|array|min:1',
            'ol_exam_subjects.*.subject' => 'required|string|max:255',
            'ol_exam_subjects.*.result' => 'required|string|max:10',
        ]);

        $student = Student::find($studentId);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        try {
            // Find or create StudentExam record for this student
            $studentExam = \App\Models\StudentExam::where('student_id', $studentId)->first();
            
            if (!$studentExam) {
                $studentExam = new \App\Models\StudentExam();
                $studentExam->student_id = $studentId;
            }

            // Update O/L exam fields
            $studentExam->ol_index_no = $request->input('ol_index_no');
            $studentExam->ol_exam_type = $request->input('ol_exam_type');
            $studentExam->ol_exam_year = $request->input('ol_exam_year');
            $studentExam->ol_exam_subjects = json_encode($request->input('ol_exam_subjects'));
            
            $studentExam->save();

            return response()->json([
                'success' => true,
                'message' => 'O/L exam results updated successfully!',
                'exam' => $studentExam
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update O/L exam results', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update O/L exam results. Please try again.',
            ], 500);
        }
    }

    /**
     * Update A/L exam results for a student
     */
    public function updateALResults(Request $request, $studentId)
    {
        $request->validate([
            'al_index_no' => 'nullable|string|max:255',
            'al_exam_type' => 'required|string|max:255',
            'al_exam_year' => 'required|integer|min:1900|max:' . (date('Y') + 1),
            'al_stream' => 'required|string|max:255',
            'al_exam_subjects' => 'required|array|min:1',
            'al_exam_subjects.*.subject' => 'required|string|max:255',
            'al_exam_subjects.*.result' => 'required|string|max:10',
        ]);

        $student = Student::find($studentId);
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        try {
            // Find or create StudentExam record for this student
            $studentExam = \App\Models\StudentExam::where('student_id', $studentId)->first();
            
            if (!$studentExam) {
                $studentExam = new \App\Models\StudentExam();
                $studentExam->student_id = $studentId;
            }

            // Update A/L exam fields
            $studentExam->al_index_no = $request->input('al_index_no');
            $studentExam->al_exam_type = $request->input('al_exam_type');
            $studentExam->al_exam_year = $request->input('al_exam_year');
            $studentExam->al_exam_stream = $request->input('al_stream');
            $studentExam->al_exam_subjects = json_encode($request->input('al_exam_subjects'));
            
            $studentExam->save();

            return response()->json([
                'success' => true,
                'message' => 'A/L exam results updated successfully!',
                'exam' => $studentExam
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to update A/L exam results', [
                'student_id' => $studentId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update A/L exam results. Please try again.',
            ], 500);
        }
    }

    private function specializationsForCourse($course): array
    {
        if (!$course) {
            return [];
        }

        $specializations = $this->normalizeSpecializationList($course->specializations ?? []);

        $hasCommonModule = \DB::table('semester_module')
            ->join('semesters', 'semesters.id', '=', 'semester_module.semester_id')
            ->where('semesters.course_id', $course->course_id)
            ->where(function ($query) {
                $query->whereNull('semester_module.specializations')
                    ->orWhere('semester_module.specializations', '[]')
                    ->orWhere('semester_module.specializations', 'null');
            })
            ->exists();

        if ($hasCommonModule) {
            $specializations = array_values(array_unique(array_merge(['Common'], $specializations)));
        }

        return $specializations;
    }

    private function normalizeSpecializationList($raw): array
    {
        if (is_string($raw)) {
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/', $raw);
        }
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $item) {
            if (is_array($item)) {
                $item = $item['name'] ?? $item['specialization'] ?? $item['title'] ?? reset($item);
            }
            $item = trim((string) $item);
            if ($item !== '' && strtolower($item) !== 'null') {
                $out[] = $item;
            }
        }

        return array_values(array_unique($out));
    }
}
