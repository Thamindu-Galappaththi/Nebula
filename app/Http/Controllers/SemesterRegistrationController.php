<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Course;
use App\Models\Intake;
use App\Models\Semester;
use App\Models\Student;
use App\Models\CourseRegistration;
use App\Models\SemesterRegistration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\ClearanceRequest;
use App\Models\SpecializationRegistration;
use App\Support\SpecializationStudentScope;
use App\Support\SemesterModuleSpecializationHelper;
use Illuminate\Support\Facades\Schema;


class SemesterRegistrationController extends Controller
{
    public function index()
    {
        return view('registration.semester_registration');
    }

    /**
     * Store semester registrations with Special-Approval support.
     *
     * Expects:
     * - register_students: JSON array [{student_id, status}]
     * - sa_reasons[<student_id>]: string (optional, required when re-registering a terminated student)
     * - sa_files[<student_id>]: uploaded file (optional)
     */
    public function store(Request $request)
    {
        Log::info('Semester registration store method called', $request->all());

        // A pending record means the student is not registered for this semester
        // and has not been placed on hold or terminated.
        $request->validate([
            'course_id'         => 'required|exists:courses,course_id',
            'intake_id'         => 'required|exists:intakes,intake_id',
            'semester_id'       => 'required|exists:semesters,id',
            'location'          => 'required|in:Welisara,Moratuwa,Peradeniya',
            'specialization'    => 'nullable|string|max:255',
            'register_students' => 'required|string',

            // special approval maps (optional)
            'sa_reasons'   => 'array',
            'sa_reasons.*' => 'string',
            'sa_files'     => 'array',
            'sa_files.*'   => 'file|max:4096', // 4MB per file cap
        ]);

        try {
            $selectedStudents = json_decode($request->input('register_students'), true);

            if (!is_array($selectedStudents) || empty($selectedStudents)) {
                Log::warning('No students selected for registration.');
                return response()->json([
                    'success' => false,
                    'message' => 'No students selected for registration.'
                ], 400);
            }

            // Validate payload entries and allowed statuses
            foreach ($selectedStudents as $entry) {
                if (!isset($entry['student_id']) || !isset($entry['status'])) {
                    Log::warning('Invalid student entry format.', $entry);
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid student entry format.'
                    ], 400);
                }
                // Ensure status is one of the allowed values
                $allowedStatuses = ['pending', 'registered', 'holding', 'terminated'];
                if (!in_array(strtolower($entry['status']), $allowedStatuses)) {
                    Log::warning('Invalid status provided.', ['status' => $entry['status']]);
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid status. Allowed: pending, registered, holding, terminated.'
                    ], 400);
                }
            }

            // Validate student IDs exist
            $studentIds        = array_column($selectedStudents, 'student_id');
            $validStudentIds   = Student::whereIn('student_id', $studentIds)->pluck('student_id')->toArray();
            $invalidStudentIds = array_diff($studentIds, $validStudentIds);
            if (!empty($invalidStudentIds)) {
                Log::warning('Invalid student IDs.', ['invalid' => $invalidStudentIds]);
                return response()->json([
                    'success' => false,
                    'message' => 'Some selected students do not exist in the system.'
                ], 400);
            }

            $requestedSpecialization = trim((string) $request->input('specialization')) ?: null;
            if ($requestedSpecialization !== null && trim((string) $requestedSpecialization) !== '') {
                $allowedStudentIds = $this->getAllowedStudentIdsForSelection(
                    (int) $request->course_id,
                    (int) $request->intake_id,
                    $request->location,
                    $requestedSpecialization,
                    (int) $request->semester_id
                );

                if (!empty($allowedStudentIds)) {
                    $notEligible = array_values(array_diff($studentIds, $allowedStudentIds));
                    if (!empty($notEligible)) {
                        Log::warning('Selected students are out of the specialization scope.', [
                            'course_id' => $request->course_id,
                            'intake_id' => $request->intake_id,
                            'semester_id' => $request->semester_id,
                            'specialization' => $requestedSpecialization,
                            'students' => $notEligible,
                        ]);

                        return response()->json([
                            'success' => false,
                            'message' => 'Selected students are outside the allowed specialization scope for this semester.'
                        ], 422);
                    }
                }
            }

            // SA maps
            $saReasons = $request->input('sa_reasons', []);
            $saFiles   = $request->file('sa_files', []);

            $messages = [];

            // Use DB transaction for atomicity
            DB::transaction(function () use ($selectedStudents, $request, $saReasons, $saFiles, &$messages) {
                foreach ($selectedStudents as $entry) {
                    $studentId    = (int) $entry['student_id'];
                    $newStatus    = strtolower($entry['status']); // pending | registered | holding | terminated
                    $origFromUI   = strtolower($entry['original_status'] ?? '');

                    // Current semester registration (if any) for this student/intake/semester
                    $current = SemesterRegistration::where('student_id', $studentId)
                        ->where('intake_id',  $request->intake_id)
                        ->where('semester_id', $request->semester_id)
                        ->latest('id')
                        ->first();

                    // Consider terminated if DB says so; fall back to UI-provided original status
                    $wasTerminated = $current
                        ? ($current->status === 'terminated')
                        : ($origFromUI === 'terminated');

                    // Special-approval payload
                    $reason = $saReasons[$studentId] ?? null;
                    $hasSA  = $reason && trim($reason) !== '';

                    // === CASE A: re-registering a TERMINATED student → create/refresh a PENDING approval ===
                    if ($newStatus === 'registered' && ($wasTerminated || $hasSA)) {
                        $filePath = null;
                        if (isset($saFiles[$studentId]) && $saFiles[$studentId]->isValid()) {
                            try {
                                $filePath = $saFiles[$studentId]->store('semester_special_approvals', 'public');
                            } catch (\Exception $e) {
                                Log::error('File upload failed for student ' . $studentId, ['error' => $e->getMessage()]);
                                throw new \Exception('File upload failed for student ' . $studentId);
                            }
                        }

                        SemesterRegistration::updateOrCreate(
                            [
                                'student_id'  => $studentId,
                                'intake_id'   => $request->intake_id,
                                'semester_id' => $request->semester_id,
                            ],
                            [
                                'course_id'             => $request->course_id,
                                'location'              => $request->location,
                                'specialization'        => $this->registeredSpecialization(
                                    $studentId,
                                    (int) $request->course_id,
                                    (int) $request->intake_id
                                ) ?? $current?->specialization,

                                // keep status TERMINATED until DGM approves
                                'status'                => 'terminated',
                                'desired_status'        => 'registered',
                                'approval_status'       => 'pending',
                                'approval_reason'       => $reason ?: '—',
                                'approval_file_path'    => $filePath,
                                'approval_requested_at' => now(),

                                'registration_date'     => $current?->registration_date ?? now()->toDateString(),
                                'updated_at'            => now(),
                            ]
                        );

                        $messages[] = "Student {$studentId}: Special approval requested (pending DGM).";
                        continue; // IMPORTANT: do not fall through and overwrite approval_* fields
                    }

                    // === CASE B: There is an already-approved SA to move to registered → finalize ===
                    $approvedToRegistered = $current
                        && $current->approval_status === 'approved'
                        && $current->desired_status  === 'registered'
                        && $newStatus === 'registered';

                    // === CASE C: Normal update (no SA involved) ===
                    $update = [
                        'course_id'         => $request->course_id,
                        'location'          => $request->location,
                        'specialization'    => $this->registeredSpecialization(
                            $studentId,
                            (int) $request->course_id,
                            (int) $request->intake_id
                        ) ?? $current?->specialization,
                        'status'            => $approvedToRegistered ? 'registered' : $newStatus, // Handles 'holding' here
                        'registration_date' => now()->toDateString(),
                        'updated_at'        => now(),
                    ];

                    // Only clear approval flags if we actually consumed an approved request
                    if ($approvedToRegistered) {
                        $update['desired_status']         = null;
                        $update['approval_status']        = 'none';
                        $update['approval_reason']        = null;
                        $update['approval_file_path']     = null;
                        $update['approval_requested_at']  = null;
                        $update['approval_decided_at']    = now();
                        $update['approval_decided_by']    = auth()->id();
                        // $update['approval_dgm_comment'] = $request->input('comment'); // set if you pass one
                    }

                    SemesterRegistration::updateOrCreate(
                        [
                            'student_id'  => $studentId,
                            'intake_id'   => $request->intake_id,
                            'semester_id' => $request->semester_id,
                        ],
                        $update
                    );
                }
            });

            $note = empty($messages) ? '' : (' ' . implode(' ', $messages));
            return response()->json([
                'success' => true,
                'message' => 'Student registration statuses processed.' . $note
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in semester registration: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed: ' . $e->getMessage()
            ], 422);
        } catch (\Throwable $e) {
            Log::error('Error saving semester registrations: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Server error occurred: ' . $e->getMessage() // More specific for debugging
            ], 500);
        }
    }

    // 1. Get courses by location (degree programs only)
    public function getCoursesByLocation(Request $request)
    {
        $location = $request->input('location');

        if (!$location) {
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'Location is required.'
            ]);
        }

        if (!in_array($location, ['Welisara', 'Moratuwa', 'Peradeniya'], true)) {
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'Location is required.'
            ]);
        }

        $courses = Course::where('location', $location)
            ->whereIn('course_type', ['degree', 'diploma'])
            ->orderBy('course_name')
            ->get(['course_id', 'course_name', 'course_type']);

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'message' => $courses->isEmpty() ? 'No courses found.' : 'Courses loaded successfully.'
        ]);
    }

    // 2. Get ongoing intakes for a course/location
    public function getOngoingIntakes(Request $request)
    {
        $courseId = $request->input('course_id');
        $location = $request->input('location');
        $now = now();

        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['success' => true, 'intakes' => []]);
        }

        $base = Intake::forCourse($course, $location);

        $activeIntakes = (clone $base)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now)
            ->get(['intake_id', 'batch']);

        $intakesWithSemesters = (clone $base)
            ->whereIn('intake_id', function ($q) use ($courseId) {
                $q->select('intake_id')->from('semesters')->where('course_id', $courseId);
            })
            ->get(['intake_id', 'batch']);

        $allIntakes = $activeIntakes->merge($intakesWithSemesters)->unique('intake_id')->values();

        return response()->json(['success' => true, 'intakes' => $allIntakes]);
    }

    // 3. Get open semesters for a course/intake/location
    public function getOpenSemesters(Request $request)
    {
        $courseId = $request->input('course_id');
        $intakeId = $request->input('intake_id');
        $course = Course::find($courseId);

        $semesters = Semester::where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get(['id', 'name', 'status', 'course_id', 'intake_id'])
            ->values()
            ->map(function ($semester, $index) use ($course) {
                $label = $this->getSemesterDisplayLabel($course, $semester, $index + 1);

                return [
                    'semester_id'   => $semester->id,
                    'semester_name' => $label,
                    'display_name'  => $label,
                    'raw_name'      => $semester->name,
                    'status'        => $semester->status
                ];
            });

        return response()->json(['success' => true, 'semesters' => $semesters]);
    }

    private function registeredSpecialization(int $studentId, int $courseId, int $intakeId): ?string
    {
        if (!Schema::hasTable('specialization_registrations')) {
            return null;
        }

        $specialization = SpecializationRegistration::query()
            ->where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->where('status', 'registered')
            ->value('specialization');

        $specialization = is_string($specialization) ? trim($specialization) : '';

        return $specialization === '' ? null : $specialization;
    }

    private function decodeCourseSpecializations(?Course $course): array
    {
        if (!$course || empty($course->specializations)) {
            return [];
        }

        $decoded = is_array($course->specializations)
            ? $course->specializations
            : json_decode((string) $course->specializations, true);

        if (!is_array($decoded)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map(function ($value) {
            if (!is_string($value)) {
                return null;
            }

            $trimmed = trim($value);
            return $trimmed === '' ? null : $trimmed;
        }, $decoded), fn ($value) => $value !== null)));
    }

    private function getSemesterCommonSpecializations(int $courseId, int $intakeId, ?int $semesterId, array $courseSpecializations = []): array
    {
        if ($semesterId === null) {
            return $courseSpecializations;
        }

        $rows = DB::table('semester_module as sm')
            ->join('semesters as s', 's.id', '=', 'sm.semester_id')
            ->where('s.course_id', $courseId)
            ->where('s.intake_id', $intakeId)
            ->where('sm.semester_id', $semesterId)
            ->select('sm.specialization', 'sm.specializations')
            ->get();

        $common = [];

        foreach ($rows as $row) {
            $decoded = SemesterModuleSpecializationHelper::decodeList($row->specializations ?? null, $row->specialization ?? null);

            if (is_array($decoded) && !empty($decoded)) {
                foreach ($decoded as $spec) {
                    $trimmed = trim((string) $spec);
                    if ($trimmed !== '') {
                        $common[$trimmed] = true;
                    }
                }
                continue;
            }

            foreach ($courseSpecializations as $spec) {
                $trimmed = trim((string) $spec);
                if ($trimmed !== '') {
                    $common[$trimmed] = true;
                }
            }
        }

        if (empty($common)) {
            return $courseSpecializations;
        }

        return array_values(array_unique(array_filter(array_map(function ($spec) {
            $trimmed = trim((string) $spec);
            return $trimmed === '' ? null : $trimmed;
        }, array_keys($common)), fn ($spec) => $spec !== null)));
    }

    private function getAllowedStudentIdsForSelection(int $courseId, int $intakeId, ?string $location, ?string $specialization, ?int $semesterId): array
    {
        if ($specialization === null || trim((string) $specialization) === '') {
            return [];
        }

        $course = Course::find($courseId);
        $courseSpecializations = $this->decodeCourseSpecializations($course);
        $normalizedSpecialization = trim((string) $specialization);
        $selection = strtolower($normalizedSpecialization) === 'common' ? 'Common' : $normalizedSpecialization;

        $effectiveCourseSpecializations = $selection === 'Common'
            ? $this->getSemesterCommonSpecializations($courseId, $intakeId, $semesterId, $courseSpecializations)
            : $courseSpecializations;

        return SpecializationStudentScope::resolveStudentIds(
            $courseId,
            $intakeId,
            $location,
            $selection,
            null,
            null,
            $effectiveCourseSpecializations
        );
    }

    // 4. Get eligible students for a course/intake (registered from eligibility page)
    public function getEligibleStudents(Request $request)
    {
        $courseId = $request->input('course_id');
        $intakeId = $request->input('intake_id');
        $semesterId = $request->input('semester_id');
        $location = $request->input('location');
        $specialization = trim((string) $request->input('specialization')) ?: null;

        $course = Course::find($courseId);
        $courseSpecializations = $this->decodeCourseSpecializations($course);
        $effectiveCourseSpecializations = $specialization && strtolower($specialization) === 'common'
            ? $this->getSemesterCommonSpecializations((int) $courseId, (int) $intakeId, $semesterId ? (int) $semesterId : null, $courseSpecializations)
            : $courseSpecializations;

        $registrations = CourseRegistration::where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->when($location, fn ($query) => $query->where('location', $location))
            ->eligible()
            ->when($specialization, function ($query) use ($courseId, $intakeId, $location, $specialization, $effectiveCourseSpecializations) {
                return SpecializationStudentScope::applyToQuery(
                    $query,
                    'student_id',
                    (int) $courseId,
                    (int) $intakeId,
                    $location,
                    $specialization,
                    null,
                    null,
                    $effectiveCourseSpecializations
                );
            })
            ->whereHas('student')
            ->with('student')
            ->get();

        $studentIds = $registrations->pluck('student.student_id')->unique()->filter()->values();
        $semesterRegs = $studentIds->isEmpty()
            ? collect()
            : SemesterRegistration::query()
                ->whereIn('student_id', $studentIds)
                ->where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->when($semesterId, fn ($query) => $query->where('semester_id', $semesterId))
                ->orderByDesc('id')
                ->get()
                ->unique('student_id')
                ->keyBy('student_id');

        $students = $registrations
            ->map(function ($reg) use ($semesterRegs) {
                $student = $reg->student;
                if (!$student) {
                    return null;
                }

                $semReg = $semesterRegs->get($student->student_id);

                return [
                    'student_id' => $student->student_id,
                    'name'       => $student->name_with_initials ?: $student->full_name,
                    'email'      => $student->email,
                    'nic'        => $student->id_value,
                    'status'     => $semReg?->status ?? 'pending',
                ];
            })
            ->filter()
            ->unique('student_id')
            ->values();

        return response()->json(['success' => true, 'students' => $students]);
    }

    // 5. Get all possible semesters for a course (for semester creation page)
    public function getAllSemestersForCourse(Request $request)
    {
        $courseId = $request->input('course_id');
        $intakeId = $request->input('intake_id');
        $course = Course::find($courseId);
        if (!$course || !$course->no_of_semesters) {
            return response()->json(['success' => false, 'semesters' => [], 'message' => 'Course not found or no semesters defined.']);
        }

        $semesterQuery = Semester::where('course_id', $courseId);
        if ($intakeId) {
            $semesterQuery->where('intake_id', $intakeId);
        }

        // Cast to string so numeric names (e.g., "1") compare consistently against the loop counter
        $createdSemesterNames = $semesterQuery
            ->pluck('name')
            ->map(function ($name) {
                return (string) $name;
            })
            ->toArray();

        $allPossibleSemesters = [];
        for ($i = 1; $i <= $course->no_of_semesters; $i++) {
            if (!in_array((string) $i, $createdSemesterNames, true)) {
                $allPossibleSemesters[] = [
                    'semester_id'   => $i,
                    'semester_name' => 'Semester ' . $i
                ];
            }
        }

        return response()->json(['success' => true, 'semesters' => $allPossibleSemesters]);
    }

    // (Legacy small endpoint) Update a single student's status
    public function updateStatus(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|integer',
            'semester_id' => 'required|integer',
            'intake_id'   => 'required|integer',
            'status'      => 'required|in:pending,registered,holding,terminated',
        ]);

        SemesterRegistration::updateOrCreate(
            [
                'student_id'  => $request->student_id,
                'semester_id' => $request->semester_id,
            ],
            [
                'intake_id' => $request->intake_id,
                'status'    => $request->status,
                'updated_at' => now(),
            ]
        );

        return response()->json(['success' => true, 'message' => 'Status updated successfully.']);
    }

    // =========================
    // DGM Actions (Approve/Reject)
    // =========================

    public function approveReRegister(Request $request)
    {
        $request->validate([
            'request_id'  => 'nullable|integer',
            'student_id'  => 'nullable|integer',
            'intake_id'   => 'nullable|integer',
            'semester_id' => 'nullable|integer',
            'comment'     => 'nullable|string'
        ]);

        // Find the record either by request_id or by (student,intake,semester)
        $reg = null;
        if ($request->filled('request_id')) {
            $reg = SemesterRegistration::find($request->request_id);
        } else {
            $reg = SemesterRegistration::where('student_id', $request->student_id)
                ->where('intake_id', $request->intake_id)
                ->where('semester_id', $request->semester_id)
                ->first();
        }

        if (!$reg || $reg->approval_status !== 'pending' || $reg->desired_status !== 'registered') {
            return response()->json(['success' => false, 'message' => 'No pending special-approval request found.'], 404);
        }

        $reg->approval_status      = 'approved';
        $reg->approval_dgm_comment = $request->comment;
        $reg->approval_decided_at  = now();
        $reg->approval_decided_by  = auth()->id();
        $reg->status               = 'registered';
        $reg->desired_status       = null;
        $reg->save();

        return response()->json(['success' => true, 'message' => 'Request approved and student registered.']);
    }


    public function rejectReRegister(Request $request)
    {
        $request->validate([
            'student_id'  => 'required|integer',
            'intake_id'   => 'required|integer',
            'semester_id' => 'required|integer',
            'comment'     => 'nullable|string'
        ]);

        $reg = SemesterRegistration::where('student_id', $request->student_id)
            ->where('intake_id', $request->intake_id)
            ->where('semester_id', $request->semester_id)
            ->first();

        if (!$reg || $reg->approval_status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'No pending special-approval request found.'], 404);
        }

        $reg->approval_status       = 'rejected';
        $reg->approval_dgm_comment  = $request->comment;
        $reg->approval_decided_at   = now();
        $reg->approval_decided_by   = auth()->id();
        // keep status as terminated
        $reg->desired_status        = null;
        $reg->save();

        return response()->json(['success' => true, 'message' => 'Request rejected. Student remains terminated.']);
    }

    // Return terminated → re-register requests
    public function terminatedRequests(Request $request)
    {
        $status = strtolower($request->query('status', 'pending')); // pending | rejected | approved

        $query = SemesterRegistration::with(['student', 'course', 'intake', 'semester'])
            ->where('status', 'terminated');

        if ($status === 'rejected') {
            $query->where('approval_status', 'rejected');
        } elseif ($status === 'approved') {
            $query->where('approval_status', 'approved');
        } else {
            // pending
            $query->where('desired_status', 'registered')
                  ->where('approval_status', 'pending');
        }

        $rows = $query->orderByDesc('approval_requested_at')->orderByDesc('approval_decided_at')->get();

        // Shape the payload expected by the front-end
        $requests = $rows->map(function ($r) {
            return [
                'id'             => $r->id,
                'student_id'     => $r->student_id,
                'student_name'   => optional($r->student)->name_with_initials ?? optional($r->student)->full_name ?? '',
                'course_id'      => $r->course_id,
                'course_name'    => optional($r->course)->course_name ?? '',
                'intake_id'      => $r->intake_id,
                'intake'         => optional($r->intake)->batch ?? '',
                'semester_id'    => $r->semester_id,
                'semester_name'  => $this->getSemesterDisplayLabel($r->course, $r->semester),
                'current_status' => $r->status,                 // 'terminated'
                'desired_status' => $r->desired_status,         // 'registered'
                'reason'         => $r->approval_reason ?? '',
                'document_url'   => $r->approval_file_path
                    ? \Storage::disk('public')->url($r->approval_file_path)
                    : null,
                'requested_at'   => optional($r->approval_requested_at)->toDateTimeString(),
                'rejected_at'    => $r->approval_status === 'rejected' ? optional($r->approval_decided_at)->toDateTimeString() : null,
            ];
        });

        return response()->json([
            'success'  => true,
            'requests' => $requests,
        ]);
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

    /**
     * AJAX: Check pending clearances for a single student.
     * Returns an array of clearance types and their status texts.
     */
    public function checkStudentClearances(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'course_id'  => 'nullable|integer',
            'intake_id'  => 'nullable|integer',
        ]);

        $studentId = $request->student_id;

        $rows = ClearanceRequest::where('student_id', $studentId)
            ->when($request->course_id, fn ($query) => $query->where('course_id', $request->course_id))
            ->when($request->intake_id, fn ($query) => $query->where('intake_id', $request->intake_id))
            ->orderByDesc('requested_at')
            ->get();

        $types = ClearanceRequest::getClearanceTypes();

        // default: if no record for a type → treat as 'None'
        $result = [];
        foreach ($types as $key => $label) {
            $r = $rows->firstWhere('clearance_type', $key);
            if ($r) {
                $result[] = [
                    'type' => $key,
                    'label' => $label,
                    'status' => $r->status,
                    'status_text' => $r->status_text,
                    'note' => $r->remarks,
                ];
            } else {
                $result[] = [
                    'type' => $key,
                    'label' => $label,
                    'status' => 'none',
                    'status_text' => 'No record',
                    'note' => null,
                ];
            }
        }

        // Pending payments: any payment details with remaining_amount > 0 or status != 'paid'
        $pendingPayments = \App\Models\PaymentDetail::where('student_id', $studentId)
            ->where(function($q) {
                $q->where('remaining_amount', '>', 0)
                  ->orWhere('status', '!=', 'paid');
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'amount' => $p->remaining_amount ?? $p->total_fee ?? $p->amount,
                    'description' => $p->remarks ?? ($p->payment_method ? $p->payment_method : 'Payment'),
                    'due_date' => $p->due_date?->format('Y-m-d') ?? null,
                    'payment_status' => $p->status,
                    'formatted' => 'Rs. ' . number_format($p->remaining_amount ?? $p->total_fee ?? $p->amount, 2),
                ];
            })->toArray();

        return response()->json(['success' => true, 'clearances' => $result, 'pending_payments' => $pendingPayments]);
    }
}
