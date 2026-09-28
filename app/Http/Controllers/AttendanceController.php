<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\Course;
use App\Models\Intake;
use App\Models\Module;
use App\Models\Student;
use App\Models\CourseRegistration;
use App\Support\SpecializationStudentScope;
use App\Exports\AttendanceExport;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Maatwebsite\Excel\Excel as MaatExcel;

class AttendanceController extends Controller
{
    public function index()
    {
        $courses = Course::all(['course_id', 'course_name']);
        $intakes = Intake::all(['intake_id', 'batch']);

        return view('attendance.attendance', compact('courses', 'intakes'));
    }

    public function getCoursesByLocation(Request $request)
    {
        $location = $request->query('location');
        $courseType = $request->query('course_type');

        if (!$location || !$courseType) {
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'Location and Course Type are required.'
            ]);
        }

        try {
            // Debug log to check what we're querying
            \Log::info('getCoursesByLocation query params', [
                'location' => $location,
                'courseType' => $courseType,
            ]);

            $courses = Course::select('course_id', 'course_name')
                ->where('location', $location)
                ->where('course_type', $courseType)
                ->orderBy('course_name', 'asc')
                ->get();

            // Log the results
            \Log::info('Courses found:', [
                'count' => $courses->count(),
                'courses' => $courses->toArray()
            ]);

            return response()->json([
                'success' => true,
                'courses' => $courses,
                'message' => $courses->isEmpty() ? 'No courses found for this location and type.' : 'Courses loaded successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching courses by location: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'An error occurred while fetching courses.'
            ], 500);
        }
    }

    public function getIntakesForCourseAndLocation(Request $request, $courseId, $location)
    {
        try {
            $course = Course::find($courseId);
            if (!$course) {
                return response()->json(['error' => 'Course not found.'], 404);
            }

            $intakes = Intake::forCourse($course, $location)
                            ->orderBy('batch')
                            ->get(['intake_id', 'batch']);

            return response()->json(['intakes' => $intakes]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    public function getSemesters(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,course_id',
            'intake_id' => 'required|integer|exists:intakes,intake_id',
        ]);

        $course = Course::find($request->course_id);
        $intake = Intake::find($request->intake_id);

        if (!$course || !$intake) {
            return response()->json(['error' => 'Invalid course or intake.'], 404);
        }

        // Certificate courses don't use semesters, return empty array
        if ($course->course_type === 'certificate') {
            return response()->json(['semesters' => [], 'is_certificate' => true]);
        }

        $semesters = \App\Models\Semester::where('course_id', $request->course_id)
            ->where('intake_id', $request->intake_id)
            ->orderBy('start_date')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->values()
            ->map(function ($semester, $index) use ($course) {
                $displayValue = $this->formatSemesterDisplayValue($course, $semester->name, $index + 1);
                $label = 'Semester ' . $displayValue;

                return [
                    'semester_id' => $semester->id,
                    'semester_name' => $label,
                    'display_name' => $label,
                    'name' => $semester->name,
                ];
            });

        return response()->json(['semesters' => $semesters, 'is_certificate' => false]);
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

        if (preg_match('/^[A-Za-z]$/', $rawName)) {
            $numeric = ord(strtoupper($rawName)) - 64;
            if ($numeric < 1 || $numeric > $numericUpperBound) {
                $numeric = $fallbackNumber;
            }

            return (string) $numeric;
        }

        return (string) $fallbackNumber;
    }

    private function getCourseSpecializations(Course $course): array
    {
        $specializations = $course->specializations ?? [];

        if (is_string($specializations)) {
            $decoded = json_decode($specializations, true);
            $specializations = is_array($decoded) ? $decoded : [];
        }

        if (!is_array($specializations)) {
            return [];
        }

        return array_values(array_filter(array_map(function ($specialization) {
            if (is_string($specialization)) {
                $trimmed = trim($specialization);
                return $trimmed === '' ? null : $trimmed;
            }
            if (is_array($specialization) || is_object($specialization)) {
                $value = (array) $specialization;
                $name = trim((string) ($value['name'] ?? $value['title'] ?? $value['specialization'] ?? ''));
                return $name === '' ? null : $name;
            }
            return null;
        }, $specializations)));
    }

    private function courseHasSpecializations(Course $course): bool
    {
        return count($this->getCourseSpecializations($course)) > 0;
    }

    private function normalizeSpecializationValue(?string $specialization): ?string
    {
        $specialization = trim((string) $specialization);

        return $specialization !== '' ? $specialization : null;
    }

    /**
     * @return array<int>|null Null means do not filter; an array (including empty) is a strict student-id filter.
     */
    private function resolveAttendanceSpecializationStudentIds(
        $courseId,
        $intakeId,
        ?string $location,
        ?string $specialization,
        $moduleScope = null,
        ?Course $course = null
    ): ?array {
        $specialization = $this->normalizeSpecializationValue($specialization);
        if ($specialization === null) {
            return null;
        }

        $studentIds = SpecializationStudentScope::resolveStudentIds(
            (int) $courseId,
            (int) $intakeId,
            $location,
            $specialization,
            $moduleScope?->specializations ?? null,
            $moduleScope?->specialization ?? null,
            $course ? $this->getCourseSpecializations($course) : null
        );

        $isCommon = strcasecmp($specialization, 'Common') === 0;
        $moduleTargetSpecs = \App\Support\SemesterModuleSpecializationHelper::decodeList(
            $moduleScope?->specializations ?? null,
            $moduleScope?->specialization ?? null
        );

        if ($isCommon && $moduleTargetSpecs === null && empty($studentIds)) {
            return null;
        }

        return array_values($studentIds);
    }

    private function mapAttendanceStudent($registration): ?array
    {
        $student = $registration->student ?? null;
        if (!$student) {
            return null;
        }

        return [
            'course_registration_id' => $registration->course_registration_id ?? null,
            'registration_number' => $registration->course_registration_id
                ?? $student->registration_id
                ?? $student->student_id,
            'student_id' => (int) $student->student_id,
            'name_with_initials' => $student->name_with_initials ?: $student->full_name,
        ];
    }

    private function isCoreAttendanceModule($semesterId, $moduleId): bool
    {
        return DB::table('semester_module')
            ->join('modules', 'modules.module_id', '=', 'semester_module.module_id')
            ->where('semester_module.semester_id', $semesterId)
            ->where('semester_module.module_id', $moduleId)
            ->whereIn('modules.module_type', ['core', 'special_unit_compulsory'])
            ->exists();
    }

    private function resolveCourseRegistrationId($studentId, $courseId, $intakeId, $location): ?string
    {
        return CourseRegistration::where('student_id', $studentId)
            ->where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->where('location', $location)
            ->value('course_registration_id');
    }

    private function applySpecializationFilter($query, ?string $specialization, string $column = 'specialization')
    {
        $specialization = $this->normalizeSpecializationValue($specialization);

        if ($specialization !== null) {
            $query->where($column, $specialization);
        }

        return $query;
    }

    private function getAttendanceSemesterStorageValue(\App\Models\Semester $semester): string
    {
        return trim((string) $semester->name);
    }

    private function getAttendanceSemesterLookupValues(\App\Models\Semester $semester): array
    {
        return collect([
            $this->getAttendanceSemesterStorageValue($semester),
            (string) $semester->id,
        ])
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function normalizeLegacyAttendanceSemesterValues(array $baseConditions, array $lookupValues, string $canonicalSemester): void
    {
        if (empty($lookupValues)) {
            return;
        }

        $legacyValues = array_values(array_filter($lookupValues, function ($value) use ($canonicalSemester) {
            return (string) $value !== $canonicalSemester;
        }));

        if (empty($legacyValues)) {
            return;
        }

        Attendance::query()
            ->where($baseConditions)
            ->whereIn('semester', $legacyValues)
            ->update(array_merge(
                ['semester' => $canonicalSemester, 'updated_at' => now()],
                \App\Support\UserTrackingData::forUpdate()
            ));
    }

    public function getFilteredModules(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,course_id',
            'intake_id' => 'required|integer|exists:intakes,intake_id',
            'semester' => 'nullable|integer',
            'location' => 'required|string',
            'specialization' => 'nullable|string|max:255',
        ]);

        $courseId = $request->input('course_id');
        $intakeId = $request->input('intake_id');
        $semesterId = $request->input('semester');
        $selectedSpecialization = $this->normalizeSpecializationValue($request->input('specialization'));

        // Check if this is a certificate course
        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['error' => 'Course not found.'], 404);
        }

        // Certificate courses use intake modules, not semester modules
        if ($course->course_type === 'certificate') {
            $modules = \App\Models\Module::join('intake_modules', 'modules.module_id', '=', 'intake_modules.module_id')
                ->where('intake_modules.intake_id', $intakeId)
                ->select('modules.module_id', 'modules.module_name')
                ->get();
            return response()->json(['modules' => $modules]);
        }

        // For degree/diploma: Get the semester by ID
        $semester = \App\Models\Semester::where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->where('id', $semesterId)
            ->first();

        if (!$semester) {
            return response()->json(['error' => 'Semester not found.'], 404);
        }

        // Filter modules by semester using the semester_module table
        $modules = \App\Models\Module::join('semester_module', 'modules.module_id', '=', 'semester_module.module_id')
            ->where('semester_module.semester_id', $semester->id)
            ->select('modules.module_id', 'modules.module_name', 'semester_module.specialization', 'semester_module.specializations')
            ->get();

        if ($this->courseHasSpecializations($course) && $selectedSpecialization === null) {
            return response()->json(['modules' => []]);
        }

        if ($selectedSpecialization !== null && trim((string) $selectedSpecialization) !== '') {
            $modules = $modules->filter(function ($module) use ($selectedSpecialization) {
                return \App\Support\SemesterModuleSpecializationHelper::matchesSelection(
                    $module->specializations,
                    $module->specialization,
                    $selectedSpecialization
                );
            })->values();
        }

        return response()->json(['modules' => $modules]);
    }

    public function getStudentsForAttendance(Request $request)
    {
        // Check if this is a certificate course
        $isCertificate = $request->course_type === 'certificate';

        // Validate based on course type
        if ($isCertificate) {
            $request->validate([
                'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
                'course_id' => 'required|exists:courses,course_id',
                'intake_id' => 'required|exists:intakes,intake_id',
            ]);
        } else {
            $request->validate([
                'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
                'course_id' => 'required|exists:courses,course_id',
                'intake_id' => 'required|exists:intakes,intake_id',
                'semester' => 'required',
                'module_id' => 'required|exists:modules,module_id',
                'specialization' => 'nullable|string|max:255',
            ]);
        }

        $courseId = $request->course_id;
        $intakeId = $request->intake_id;
        $location = $request->location;
        $course = Course::find($courseId);
        $specialization = $this->normalizeSpecializationValue($request->input('specialization'));
        $moduleScope = null;
        if (!$isCertificate && $request->filled('semester') && $request->filled('module_id')) {
            $moduleScope = DB::table('semester_module')
                ->where('semester_id', $request->semester)
                ->where('module_id', $request->module_id)
                ->select('specialization', 'specializations')
                ->first();
        }

        $specializedStudentIds = $this->resolveAttendanceSpecializationStudentIds(
            $courseId,
            $intakeId,
            $location,
            $specialization,
            $moduleScope,
            $course
        );

        if (!$isCertificate && $course && $this->courseHasSpecializations($course) && !$specialization) {
            return response()->json([
                'success' => false,
                'message' => 'Specialization is required for this course.'
            ], 422);
        }

        // For certificate courses, fetch students directly from course_registration
        if ($isCertificate) {
            $students = CourseRegistration::where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->where('status', 'Registered')
                ->with('student')
                ->get()
                ->map(fn ($reg) => $this->mapAttendanceStudent($reg))
                ->filter()
                ->unique('student_id')
                ->values();

            return response()->json([
                'success' => true,
                'students' => $students
            ]);
        }

        // For degree/diploma courses - original logic
        $semesterId = $request->semester;
        $moduleId = $request->module_id;

        $semester = \App\Models\Semester::find($semesterId);
        if (!$semester) {
            return response()->json(['success' => false, 'message' => 'Semester not found.'], 404);
        }
        $semesterStorageValue = $this->getAttendanceSemesterStorageValue($semester);
        $semesterLookupValues = $this->getAttendanceSemesterLookupValues($semester);
        $this->normalizeLegacyAttendanceSemesterValues([
            'course_id' => $courseId,
            'intake_id' => $intakeId,
            'location' => $location,
            'module_id' => $moduleId,
        ], $semesterLookupValues, $semesterStorageValue);

        // Check if this is a core module (assigned to semester) or elective module
        $isCoreModule = $this->isCoreAttendanceModule($semesterId, $moduleId);

        if ($isCoreModule) {
            $semesterRegistrationQuery = \App\Models\SemesterRegistration::where('semester_id', $semesterId)
                ->where('semester_registrations.course_id', $courseId)
                ->where('semester_registrations.intake_id', $intakeId)
                ->where('semester_registrations.location', $location)
                ->where('semester_registrations.status', 'registered');

            if (is_array($specializedStudentIds)) {
                $semesterRegistrationQuery->whereIn('semester_registrations.student_id', $specializedStudentIds);
            }

            $students = $semesterRegistrationQuery
                ->leftJoin('course_registration as cr', function($join) {
                    $join->on('semester_registrations.student_id', '=', 'cr.student_id')
                        ->on('semester_registrations.course_id', '=', 'cr.course_id')
                        ->on('semester_registrations.intake_id', '=', 'cr.intake_id')
                        ->on('semester_registrations.location', '=', 'cr.location');
                })
                ->with('student')
                ->get(['semester_registrations.*', 'cr.course_registration_id as course_registration_id'])
                ->map(fn ($reg) => $this->mapAttendanceStudent($reg))
                ->filter()
                ->unique('student_id')
                ->values();
        } else {
            $moduleManagementQuery = \App\Models\ModuleManagement::where('module_id', $moduleId)
                ->where('module_management.course_id', $courseId)
                ->where('module_management.intake_id', $intakeId)
                ->where('module_management.location', $location)
                ->where('module_management.semester', $semester->name);

            if (is_array($specializedStudentIds)) {
                $moduleManagementQuery->whereIn('module_management.student_id', $specializedStudentIds);
            }

            $students = $moduleManagementQuery
                ->leftJoin('course_registration as cr', function($join) {
                    $join->on('module_management.student_id', '=', 'cr.student_id')
                        ->on('module_management.course_id', '=', 'cr.course_id')
                        ->on('module_management.intake_id', '=', 'cr.intake_id')
                        ->on('module_management.location', '=', 'cr.location');
                })
                ->with('student')
                ->get(['module_management.*', 'cr.course_registration_id as course_registration_id'])
                ->map(fn ($reg) => $this->mapAttendanceStudent($reg))
                ->filter()
                ->unique('student_id')
                ->values();
        }

        return response()->json([
            'success' => true,
            'students' => $students
        ]);
    }

    public function storeAttendance(Request $request)
    {
        // Check if this is a certificate course
        $isCertificate = $request->course_type === 'certificate';

        // Validate based on course type
        if ($isCertificate) {
            $request->validate([
                'location' => 'required|string',
                'course_id' => 'required|integer',
                'intake_id' => 'required|integer',
                'date' => 'required|date',
                'attendance_type' => 'nullable|string|in:lectures,labs,special_lectures,tutorials,other',
                'attendance_data' => 'required|array|min:1'
            ]);
        } else {
            $request->validate([
                'location' => 'required|string',
                'course_id' => 'required|integer',
                'intake_id' => 'required|integer',
                'semester' => 'required',
                'module_id' => 'required|integer',
                'date' => 'required|date',
                'attendance_type' => 'nullable|string|in:lectures,labs,special_lectures,tutorials,other',
                'attendance_data' => 'required|array|min:1'
            ]);
        }

        try {
            DB::beginTransaction();

            $date = Carbon::parse($request->date);
            $attendanceType = $request->input('attendance_type', 'lectures');
            $hasAttendanceTypeColumn = Schema::hasColumn('attendance', 'attendance_type');

            // For certificate courses, use null for semester and module_id
            if ($isCertificate) {
                // Delete existing attendance records for this date, course, intake (certificate)
                $deleteQuery = Attendance::where('date', $date)
                         ->where('course_id', $request->course_id)
                         ->where('intake_id', $request->intake_id)
                         ->where('location', $request->location)
                         ->whereNull('semester')
                         ->whereNull('module_id');

                if ($hasAttendanceTypeColumn) {
                    $deleteQuery->where('attendance_type', $attendanceType);
                }

                $deleteQuery->delete();

                // Insert new attendance records
                $attendanceRecords = [];
                foreach ($request->attendance_data as $studentData) {
                    if (!isset($studentData['student_id'])) {
                        continue; // Skip invalid records
                    }

                    $record = [
                        'location' => $request->location,
                        'course_id' => $request->course_id,
                        'intake_id' => $request->intake_id,
                        'semester' => null,
                        'module_id' => null,
                        'date' => $date,
                        'student_id' => $studentData['student_id'],
                        'status' => $studentData['status'] ?? false,
                        'created_at' => now(),
                        'updated_at' => now()
                    ];

                    if ($hasAttendanceTypeColumn) {
                        $record['attendance_type'] = $attendanceType;
                    }

                    $attendanceRecords[] = $record;
                }

                if (empty($attendanceRecords)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No valid attendance data provided.'
                    ], 400);
                }

                foreach ($attendanceRecords as &$attendanceRecord) {
                    $attendanceRecord = array_merge($attendanceRecord, \App\Support\UserTrackingData::forCreate());
                }
                unset($attendanceRecord);
                Attendance::insert($attendanceRecords);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Attendance saved successfully for ' . count($attendanceRecords) . ' students.'
                ]);
            }

            // For degree/diploma courses - original logic
            // Get the semester to convert ID to name
            $semester = \App\Models\Semester::find($request->semester);
            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }
            $semesterStorageValue = $this->getAttendanceSemesterStorageValue($semester);
            $semesterLookupValues = $this->getAttendanceSemesterLookupValues($semester);
            $this->normalizeLegacyAttendanceSemesterValues([
                'course_id' => $request->course_id,
                'intake_id' => $request->intake_id,
                'location' => $request->location,
                'module_id' => $request->module_id,
                'date' => $date->toDateString(),
            ], $semesterLookupValues, $semesterStorageValue);

            // Delete existing attendance records for this date, course, intake, semester, and module
            $deleteQuery = Attendance::where('date', $date)
                     ->where('course_id', $request->course_id)
                     ->where('intake_id', $request->intake_id)
                     ->where('location', $request->location)
                     ->whereIn('semester', $semesterLookupValues)
                     ->where('module_id', $request->module_id);

            if ($hasAttendanceTypeColumn) {
                $deleteQuery->where('attendance_type', $attendanceType);
            }

            $deleteQuery->delete();

            // Insert new attendance records
            $attendanceRecords = [];
            foreach ($request->attendance_data as $studentData) {
                if (!isset($studentData['student_id'])) {
                    continue; // Skip invalid records
                }

                $record = [
                    'location' => $request->location,
                    'course_id' => $request->course_id,
                    'intake_id' => $request->intake_id,
                    'semester' => $semesterStorageValue,
                    'module_id' => $request->module_id,
                    'date' => $date,
                    'student_id' => $studentData['student_id'],
                    'status' => $studentData['status'] ?? false,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                if ($hasAttendanceTypeColumn) {
                    $record['attendance_type'] = $attendanceType;
                }

                $attendanceRecords[] = $record;
            }

            if (empty($attendanceRecords)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid attendance data provided.'
                ], 400);
            }

            foreach ($attendanceRecords as &$attendanceRecord) {
                $attendanceRecord = array_merge($attendanceRecord, \App\Support\UserTrackingData::forCreate());
            }
            unset($attendanceRecord);
            Attendance::insert($attendanceRecords);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Attendance saved successfully for ' . count($attendanceRecords) . ' students.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Attendance save error: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to save attendance: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAttendanceHistory(Request $request)
    {
        $request->validate([
            'location' => 'required|string',
            'course_id' => 'required|integer',
            'intake_id' => 'required|integer',
            'semester' => 'required',
            'module_id' => 'required|integer',
            'date' => 'required|date',
            'attendance_type' => 'nullable|string|in:lectures,labs,special_lectures,tutorials,other',
            'specialization' => 'nullable|string|max:255'
        ]);

        try {
            // Get the semester to convert ID to name
            $semester = \App\Models\Semester::find($request->semester);
            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }
            $semesterStorageValue = $this->getAttendanceSemesterStorageValue($semester);
            $semesterLookupValues = $this->getAttendanceSemesterLookupValues($semester);
            $this->normalizeLegacyAttendanceSemesterValues([
                'location' => $request->location,
                'course_id' => $request->course_id,
                'intake_id' => $request->intake_id,
                'module_id' => $request->module_id,
                'date' => $request->date,
            ], $semesterLookupValues, $semesterStorageValue);

            $course = Course::find($request->course_id);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found.'
                ], 404);
            }

            $specialization = $this->normalizeSpecializationValue($request->input('specialization'));
            if ($this->courseHasSpecializations($course) && !$specialization) {
                return response()->json([
                    'success' => false,
                    'message' => 'Specialization is required for this course.'
                ], 422);
            }

            $studentIds = null;
            if ($request->filled('specialization')) {
                $studentIds = SpecializationStudentScope::resolveStudentIds(
                    $request->course_id,
                    $request->intake_id,
                    $request->location,
                    $specialization
                );

                if (empty($studentIds)) {
                    Log::warning('Unable to resolve specialization student IDs for attendance history; skipping specialization filter.', [
                        'course_id' => $request->course_id,
                        'intake_id' => $request->intake_id,
                        'location' => $request->location,
                        'specialization' => $specialization,
                        'semester' => $request->semester,
                        'module_id' => $request->module_id,
                    ]);
                    $studentIds = null;
                }
            }

            $attendance = Attendance::where('location', $request->location)
                                   ->where('course_id', $request->course_id)
                                   ->where('intake_id', $request->intake_id)
                                   ->whereIn('semester', $semesterLookupValues)
                                   ->where('module_id', $request->module_id)
                                   ->where('date', $request->date);

            if ($request->filled('attendance_type') && Schema::hasColumn('attendance', 'attendance_type')) {
                $attendance->where('attendance_type', $request->attendance_type);
            }

            $attendance = $attendance->with('student')->get();

            if (is_array($studentIds)) {
                $attendance = $attendance->whereIn('student_id', $studentIds)->values();
            }

            return response()->json([
                'success' => true,
                'attendance' => $attendance
            ]);
        } catch (\Exception $e) {
            Log::error('Attendance history error: ' . $e->getMessage(), [
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch attendance history: ' . $e->getMessage()
            ], 500);
        }
    }

    // Debug method to check database data
    public function debugData()
    {
        $courses = Course::all(['course_id', 'course_name', 'location']);
        $intakes = Intake::all(['intake_id', 'course_name', 'location', 'batch']);
        $courseTypes = Course::select('course_type')->distinct()->get();

        return response()->json([
            'distinct_course_types' => $courseTypes,
            'courses' => $courses,
            'intakes' => $intakes,
            'message' => 'Check the browser console for detailed data'
        ]);
    }

    public function getOverallAttendance(Request $request)
    {
        // Check if this is a certificate course (semester and module_id will be null)
        $isCertificate = empty($request->semester) && empty($request->module_id);

        $rules = [
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
        ];

        // Only require semester and module_id for degree/diploma courses
        if (!$isCertificate) {
            $rules['semester'] = 'required';
            $rules['module_id'] = 'required|exists:modules,module_id';
            $rules['specialization'] = 'nullable|string|max:255';
        }

        $request->validate($rules);

        $courseId = $request->course_id;
        $intakeId = $request->intake_id;
        $location = $request->location;
        $semesterId = $request->semester;
        $moduleId = $request->module_id;
        $specialization = $this->normalizeSpecializationValue($request->input('specialization'));

        $course = Course::find($courseId);
        if (!$course) {
            return response()->json(['error' => 'Course not found.'], 404);
        }

        if (!$isCertificate && $this->courseHasSpecializations($course) && !$specialization) {
            return response()->json([
                'success' => false,
                'message' => 'Specialization is required for this course.'
            ], 422);
        }

        $specializedStudentIds = $this->resolveAttendanceSpecializationStudentIds(
            $courseId,
            $intakeId,
            $location,
            $specialization,
            (!$isCertificate && $semesterId && $moduleId)
                ? DB::table('semester_module')
                    ->where('semester_id', $semesterId)
                    ->where('module_id', $moduleId)
                    ->select('specialization', 'specializations')
                    ->first()
                : null,
            $course
        );

        if ($isCertificate) {
            // For certificate courses: Get all attendance sessions (no semester/module filter)
            $attendanceSessions = \App\Models\Attendance::where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->whereNull('semester')
                ->whereNull('module_id')
                ->select('date')
                ->distinct()
                ->get();
            $totalSessions = $attendanceSessions->count();

            // Get students registered for the certificate course
            $registrations = \App\Models\CourseRegistration::where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->where('status', 'Registered')
                ->with('student')
                ->get();

            $attendanceData = [];
            foreach ($registrations as $reg) {
                if (!$reg->student) {
                    continue;
                }
                $attendedSessions = \App\Models\Attendance::where('course_id', $courseId)
                    ->where('intake_id', $intakeId)
                    ->where('location', $location)
                    ->whereNull('semester')
                    ->whereNull('module_id')
                    ->where('student_id', $reg->student_id)
                    ->where('status', true)
                    ->count();
                $attendanceData[] = [
                    'student_id' => $reg->student_id,
                    'registration_number' => $reg->course_registration_id,
                    'name_with_initials' => $reg->student->name_with_initials ?: $reg->student->full_name,
                    'total_sessions' => $totalSessions,
                    'attended_sessions' => $attendedSessions,
                    'percentage' => $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100, 2) : 0
                ];
            }

            $attendanceRecords = \App\Models\Attendance::where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->whereNull('semester')
                ->whereNull('module_id')
                ->get(['date', 'student_id', 'status']);

            $matrixDates = $attendanceRecords
                ->pluck('date')
                ->map(fn ($date) => Carbon::parse($date)->toDateString())
                ->unique()
                ->sort()
                ->values();

            $statusByStudentAndDate = [];
            foreach ($attendanceRecords as $record) {
                $dateKey = Carbon::parse($record->date)->toDateString();
                $statusByStudentAndDate[$record->student_id][$dateKey] = (bool) $record->status;
            }

            $matrixStudents = collect($attendanceData)
                ->map(fn ($item) => [
                    'student_id' => $item['student_id'],
                    'registration_number' => $item['registration_number'],
                    'name_with_initials' => $item['name_with_initials'],
                ])
                ->values();

            $matrixRows = $matrixDates->map(function ($date) use ($matrixStudents, $statusByStudentAndDate) {
                $statuses = [];
                foreach ($matrixStudents as $student) {
                    $studentId = $student['student_id'];
                    $studentStatusMap = $statusByStudentAndDate[$studentId] ?? [];
                    $statuses[$studentId] = array_key_exists($date, $studentStatusMap)
                        ? $studentStatusMap[$date]
                        : null;
                }

                return [
                    'date' => $date,
                    'statuses' => $statuses,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'attendance' => $attendanceData,
                'matrix' => [
                    'students' => $matrixStudents,
                    'rows' => $matrixRows,
                ],
            ]);
        }

        // For degree/diploma courses: Original logic
        // Get the semester to determine if it's core or elective
        $semester = \App\Models\Semester::find($semesterId);
        if (!$semester) {
            return response()->json(['error' => 'Semester not found.'], 404);
        }
        $semesterStorageValue = $this->getAttendanceSemesterStorageValue($semester);
        $semesterLookupValues = $this->getAttendanceSemesterLookupValues($semester);
        $this->normalizeLegacyAttendanceSemesterValues([
            'course_id' => $courseId,
            'intake_id' => $intakeId,
            'location' => $location,
            'module_id' => $moduleId,
        ], $semesterLookupValues, $semesterStorageValue);

        $isCoreModule = $this->isCoreAttendanceModule($semesterId, $moduleId);

        // Get all attendance sessions for this filter (by module)
        $attendanceSessions = \App\Models\Attendance::where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->where('location', $location)
            ->whereIn('semester', $semesterLookupValues)
            ->where('module_id', $moduleId)
            ->select('date')
            ->distinct()
            ->get();
        $totalSessions = $attendanceSessions->count();

        if ($isCoreModule) {
            // For core modules: Get students registered for the semester
            $semesterRegistrationQuery = \App\Models\SemesterRegistration::where('semester_id', $semesterId)
                ->where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->where('status', 'registered');

            if (is_array($specializedStudentIds)) {
                $semesterRegistrationQuery->whereIn('student_id', $specializedStudentIds);
            }

            $registrations = $semesterRegistrationQuery->with('student')->get();
        } else {
            // For elective modules: Get students registered for the specific module
            $moduleManagementQuery = \App\Models\ModuleManagement::where('module_id', $moduleId)
                ->where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->where('semester', $semesterStorageValue);

            if (is_array($specializedStudentIds)) {
                $moduleManagementQuery->whereIn('student_id', $specializedStudentIds);
            }

            $registrations = $moduleManagementQuery->with('student')->get();
        }

        $attendanceData = [];
        foreach ($registrations as $reg) {
            if (!$reg->student) {
                continue;
            }
            // Get the course registration ID from CourseRegistration table
            $courseReg = \App\Models\CourseRegistration::where('student_id', $reg->student_id)
                ->where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->first();

            $attendedSessions = \App\Models\Attendance::where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->whereIn('semester', $semesterLookupValues)
                ->where('module_id', $moduleId)
                ->where('student_id', $reg->student_id)
                ->where('status', true)
                ->count();
            $attendanceData[] = [
                'student_id' => $reg->student_id,
                'registration_number' => $courseReg ? $courseReg->course_registration_id : '',
                'name_with_initials' => $reg->student->name_with_initials ?: $reg->student->full_name,
                'total_sessions' => $totalSessions,
                'attended_sessions' => $attendedSessions,
                'percentage' => $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100, 2) : 0
            ];
        }

        $attendanceRecords = \App\Models\Attendance::where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->where('location', $location)
            ->whereIn('semester', $semesterLookupValues)
            ->where('module_id', $moduleId)
            ->get(['date', 'student_id', 'status']);

        $matrixDates = $attendanceRecords
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->unique()
            ->sort()
            ->values();

        $statusByStudentAndDate = [];
        foreach ($attendanceRecords as $record) {
            $dateKey = Carbon::parse($record->date)->toDateString();
            $statusByStudentAndDate[$record->student_id][$dateKey] = (bool) $record->status;
        }

        $matrixStudents = collect($attendanceData)
            ->map(fn ($item) => [
                'student_id' => $item['student_id'],
                'registration_number' => $item['registration_number'],
                'name_with_initials' => $item['name_with_initials'],
            ])
            ->values();

        $matrixRows = $matrixDates->map(function ($date) use ($matrixStudents, $statusByStudentAndDate) {
            $statuses = [];
            foreach ($matrixStudents as $student) {
                $studentId = $student['student_id'];
                $studentStatusMap = $statusByStudentAndDate[$studentId] ?? [];
                $statuses[$studentId] = array_key_exists($date, $studentStatusMap)
                    ? $studentStatusMap[$date]
                    : null;
            }

            return [
                'date' => $date,
                'statuses' => $statuses,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'attendance' => $attendanceData,
            'matrix' => [
                'students' => $matrixStudents,
                'rows' => $matrixRows,
            ],
        ]);
    }

    /**
     * Download attendance report as Excel.
     */
    public function downloadAttendanceExcel(Request $request)
    {
        // Check if this is a certificate course (semester and module_id will be empty)
        $isCertificate = empty($request->semester) && empty($request->module_id);

        $rules = [
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
        ];

        // Only require semester and module_id for degree/diploma courses
        if (!$isCertificate) {
            $rules['semester'] = 'required';
            $rules['module_id'] = 'required|exists:modules,module_id';
            $rules['specialization'] = 'nullable|string|max:255';
        }

        $request->validate($rules);

        $courseId = $request->course_id;
        $intakeId = $request->intake_id;
        $location = $request->location;
        $semesterId = $request->semester;
        $moduleId = $request->module_id;
        $specialization = $this->normalizeSpecializationValue($request->input('specialization'));
        $specializedStudentIds = null;

        // Get course and intake details
        $course = Course::find($courseId);
        $intake = Intake::find($intakeId);

        if (!$isCertificate && $this->courseHasSpecializations($course) && !$specialization) {
            return response()->json([
                'error' => 'Specialization is required for this course.'
            ], 422);
        }

        $moduleScope = null;
        if (!$isCertificate && $semesterId && $moduleId) {
            $moduleScope = DB::table('semester_module')
                ->where('semester_id', $semesterId)
                ->where('module_id', $moduleId)
                ->select('specialization', 'specializations')
                ->first();
        }

        $specializedStudentIds = $this->resolveAttendanceSpecializationStudentIds(
            $courseId,
            $intakeId,
            $location,
            $specialization,
            $moduleScope,
            $course
        );

        if ($isCertificate) {
            // For certificate courses: Get all attendance sessions (no semester/module filter)
            $attendanceSessions = \App\Models\Attendance::where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->whereNull('semester')
                ->whereNull('module_id')
                ->select('date')
                ->distinct()
                ->get();
            $totalSessions = $attendanceSessions->count();

            // Get students registered for the certificate course
            $registrations = \App\Models\CourseRegistration::where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->where('status', 'Registered')
                ->with('student')
                ->get();

            $excelData = [];
            foreach ($registrations as $reg) {
                if (!$reg->student) {
                    continue;
                }
                $attendedSessions = \App\Models\Attendance::where('course_id', $courseId)
                    ->where('intake_id', $intakeId)
                    ->where('location', $location)
                    ->whereNull('semester')
                    ->whereNull('module_id')
                    ->where('student_id', $reg->student_id)
                    ->where('status', true)
                    ->count();

                $excelData[] = [
                    $reg->course_registration_id ?? $this->resolveCourseRegistrationId($reg->student_id, $courseId, $intakeId, $location) ?? $reg->student->student_id,
                    $reg->student->name_with_initials ?: $reg->student->full_name,
                    $totalSessions,
                    $attendedSessions,
                    $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100, 2) . '%' : '0%'
                ];
            }

            // Generate filename
            $filename = 'attendance_report_' . date('Y-m-d_H-i-s') . '.xlsx';

            // Return Excel file as download
            return Excel::download(
                new AttendanceExport($excelData, $location, $course?->course_name ?? 'N/A', $intake?->batch ?? 'N/A', 'N/A', 'N/A'),
                $filename
            );
        }

        // For degree/diploma courses: Original logic
        // Get the semester to determine if it's core or elective
        $semester = \App\Models\Semester::find($semesterId);
        if (!$semester) {
            return response()->json(['error' => 'Semester not found.'], 404);
        }
        $semesterStorageValue = $this->getAttendanceSemesterStorageValue($semester);
        $semesterLookupValues = $this->getAttendanceSemesterLookupValues($semester);
        $this->normalizeLegacyAttendanceSemesterValues([
            'course_id' => $courseId,
            'intake_id' => $intakeId,
            'location' => $location,
            'module_id' => $moduleId,
        ], $semesterLookupValues, $semesterStorageValue);

        $isCoreModule = $this->isCoreAttendanceModule($semesterId, $moduleId);

        // Get all attendance sessions for this filter (by module)
        $attendanceSessions = \App\Models\Attendance::where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->where('location', $location)
            ->whereIn('semester', $semesterLookupValues)
            ->where('module_id', $moduleId)
            ->select('date')
            ->distinct()
            ->get();
        $totalSessions = $attendanceSessions->count();

        if ($isCoreModule) {
            // For core modules: Get students registered for the semester
            $semesterRegistrationQuery = \App\Models\SemesterRegistration::where('semester_id', $semesterId)
                ->where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->where('status', 'registered');

            if (is_array($specializedStudentIds)) {
                $semesterRegistrationQuery->whereIn('student_id', $specializedStudentIds);
            }

            $registrations = $semesterRegistrationQuery->with('student')->get();
        } else {
            // For elective modules: Get students registered for the specific module
            $moduleManagementQuery = \App\Models\ModuleManagement::where('module_id', $moduleId)
                ->where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->where('semester', $semesterStorageValue);

            if (is_array($specializedStudentIds)) {
                $moduleManagementQuery->whereIn('student_id', $specializedStudentIds);
            }

            $registrations = $moduleManagementQuery->with('student')->get();
        }

        $excelData = [];
        foreach ($registrations as $reg) {
            if (!$reg->student) {
                continue;
            }
            $attendedSessions = \App\Models\Attendance::where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->whereIn('semester', $semesterLookupValues)
                ->where('module_id', $moduleId)
                ->where('student_id', $reg->student_id)
                ->where('status', true)
                ->count();

            $excelData[] = [
                $this->resolveCourseRegistrationId($reg->student_id, $courseId, $intakeId, $location) ?? $reg->student->student_id,
                $reg->student->name_with_initials ?: $reg->student->full_name,
                $totalSessions,
                $attendedSessions,
                $totalSessions > 0 ? round(($attendedSessions / $totalSessions) * 100, 2) . '%' : '0%'
            ];
        }

        // Get module details
        $module = Module::find($moduleId);

        // Generate filename
        $filename = 'attendance_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        $semesterLabel = $semester
            ? 'Semester ' . $this->formatSemesterDisplayValue($course, $semester->name)
            : 'N/A';

        // Return Excel file as download
        return Excel::download(
            new AttendanceExport($excelData, $location, $course?->course_name ?? 'N/A', $intake?->batch ?? 'N/A', $semesterLabel, $module?->module_name ?? 'N/A'),
            $filename
        );
    }

    /**
     * Download a template file for bulk attendance import.
     * Columns: No, Course Registration ID, Student Name, Present (Present/Absent)
     */
    public function downloadTemplate(Request $request)
    {
        // Build an XLSX template with optional prefilled students if filters provided
        $location = $request->query('location');
        $courseId = $request->query('course_id');
        $intakeId = $request->query('intake_id');
        $semesterId = $request->query('semester');
        $moduleId = $request->query('module_id');
        $date = $request->query('date');
        $specialization = $this->normalizeSpecializationValue($request->query('specialization'));

        $course = $courseId ? Course::find($courseId) : null;
        $specializedStudentIds = null;
        $isCertificate = empty($semesterId) && empty($moduleId);

        if ($course && !$isCertificate && $this->courseHasSpecializations($course) && !$specialization) {
            return response()->json([
                'error' => 'Specialization is required for this course.'
            ], 422);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Attendance Template');

        // Header
        $sheet->fromArray(['No', 'Course Registration ID', 'Student Name', 'Present'], null, 'A1');

        // If filters provided, attempt to prefill students
        $startRow = 2;
        $isCertificate = empty($semesterId) && empty($moduleId);

        if ($courseId && $intakeId) {
            try {
                $students = collect();

                if ($isCertificate) {
                    // For certificate courses, get all Registered students in the intake
                    $courseRegs = \App\Models\CourseRegistration::where('course_id', $courseId)
                        ->where('intake_id', $intakeId)
                        ->where('location', $location)
                        ->where('status', 'Registered')
                        ->with('student')
                        ->get();

                    foreach ($courseRegs as $reg) {
                        if ($reg->student) {
                            $students->push([
                                $reg->course_registration_id ?? $reg->student->registration_id ?? $reg->student->student_id,
                                $reg->student->name_with_initials ?: $reg->student->full_name
                            ]);
                        }
                    }
                } else if ($moduleId) {
                    // For degree/diploma courses, use the existing module-based logic
                    $semester = null;
                    if ($semesterId) {
                        $semester = \App\Models\Semester::find($semesterId);
                    }

                    $isCore = $semester
                        ? $this->isCoreAttendanceModule($semesterId, $moduleId)
                        : false;

                    $moduleScope = $semester
                        ? DB::table('semester_module')
                            ->where('semester_id', $semesterId)
                            ->where('module_id', $moduleId)
                            ->select('specialization', 'specializations')
                            ->first()
                        : null;

                    $specializedStudentIds = $this->resolveAttendanceSpecializationStudentIds(
                        $courseId,
                        $intakeId,
                        $location,
                        $specialization,
                        $moduleScope,
                        $course
                    );

                    if ($isCore && $semester) {
                        $semesterRegistrationQuery = \App\Models\SemesterRegistration::where('semester_id', $semesterId)
                            ->where('course_id', $courseId)
                            ->where('intake_id', $intakeId)
                            ->where('location', $location)
                            ->where('status', 'registered');

                        if (is_array($specializedStudentIds)) {
                            $semesterRegistrationQuery->whereIn('student_id', $specializedStudentIds);
                        }

                        $regs = $semesterRegistrationQuery->with('student')->get();

                        foreach ($regs as $r) {
                            if ($r->student) {
                                $students->push([
                                    $this->resolveCourseRegistrationId($r->student_id, $courseId, $intakeId, $location)
                                        ?? $r->student->registration_id
                                        ?? $r->student->student_id,
                                    $r->student->name_with_initials ?: $r->student->full_name
                                ]);
                            }
                        }
                    } else {
                        $moduleManagementQuery = \App\Models\ModuleManagement::where('module_id', $moduleId)
                            ->where('course_id', $courseId)
                            ->where('intake_id', $intakeId)
                            ->where('location', $location)
                            ->when($semester, function($q) use ($semester) {
                                return $q->where('semester', $this->getAttendanceSemesterStorageValue($semester));
                            });

                        if (is_array($specializedStudentIds)) {
                            $moduleManagementQuery->whereIn('student_id', $specializedStudentIds);
                        }

                        $mods = $moduleManagementQuery->with('student')->get();
                        foreach ($mods as $m) {
                            if ($m->student) {
                                $students->push([
                                    $this->resolveCourseRegistrationId($m->student_id, $courseId, $intakeId, $location)
                                        ?? $m->student->registration_id
                                        ?? $m->student->student_id,
                                    $m->student->name_with_initials ?: $m->student->full_name
                                ]);
                            }
                        }
                    }
                }

                if ($students->isNotEmpty()) {
                    $rows = $students->values()->map(function ($student, $index) {
                        return [
                            $index + 1,
                            $student[0],
                            $student[1],
                            ''
                        ];
                    })->toArray();
                    $sheet->fromArray($rows, null, 'A' . $startRow);
                }
            } catch (\Exception $e) {
                // ignore prefill errors
                Log::warning('Failed to prefill attendance template: ' . $e->getMessage());
            }
        }

        // Add data validation dropdown for the attendance column (column D)
        $highestRow = max((int) $sheet->getHighestRow(), 2);
        $validationEnd = max($highestRow + 20, 22);
        for ($row = 2; $row <= $validationEnd; $row++) {
            $validation = $sheet->getCell('D' . $row)->getDataValidation();
            $validation->setType(DataValidation::TYPE_LIST);
            $validation->setErrorStyle(DataValidation::STYLE_STOP);
            $validation->setAllowBlank(true);
            $validation->setShowInputMessage(true);
            $validation->setShowErrorMessage(true);
            $validation->setShowDropDown(true);
            $validation->setErrorTitle('Invalid value');
            $validation->setError('Value must be Present or Absent');
            $validation->setPromptTitle('Select attendance');
            $validation->setPrompt('Choose Present or Absent from the dropdown');
            $validation->setFormula1('"Present,Absent"');
        }

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
        $filename = 'attendance_import_template_' . date('Y-m-d') . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import attendance from uploaded CSV/Excel file.
     * Expects selected filters (location, course_id, intake_id, semester, module_id, date) to be present
     * and file under 'attendance_file'.
     */
    public function importAttendance(Request $request)
    {
        // Detect if this is for certificate courses (semester and module_id may be empty/null)
        $isCertificate = empty($request->semester) && empty($request->module_id);

        $rules = [
            'location' => 'required|string',
            'course_id' => 'required|integer',
            'intake_id' => 'required|integer',
            'date' => 'required|date',
            'attendance_type' => 'nullable|string|in:lectures,labs,special_lectures,tutorials,other',
            'attendance_file' => 'required|file'
        ];

        // Only require semester and module_id for degree/diploma courses
        if (!$isCertificate) {
            $rules['semester'] = 'required';
            $rules['module_id'] = 'required|integer';
        }

        $request->validate($rules);

        $file = $request->file('attendance_file');
        $ext = strtolower($file->getClientOriginalExtension());

        $rows = [];
        try {
            if (in_array($ext, ['xlsx', 'xls'])) {
                // use maatwebsite/excel to get as array
                $import = new class implements \Maatwebsite\Excel\Concerns\ToArray {
                    public function array(array $array)
                    {
                        return $array;
                    }
                };
                $array = Excel::toArray($import, $file);
                if (is_array($array) && isset($array[0]) && count($array[0]) > 0) {
                    $sheet = $array[0];
                    $header = array_map('trim', $sheet[0]);
                    for ($i = 1; $i < count($sheet); $i++) {
                        $row = $sheet[$i];
                        if (count($row) === 0) continue;
                        // pad row to header length
                        $row = array_pad($row, count($header), '');
                        $rows[] = array_combine($header, $row);
                    }
                }
            } else {
                // csv / txt
                if (($handle = fopen($file->getRealPath(), 'r')) !== false) {
                    $header = null;
                    while (($data = fgetcsv($handle, 10000, ',')) !== false) {
                        if (!$header) { $header = array_map('trim', $data); continue; }
                        $rows[] = array_combine($header, $data);
                    }
                    fclose($handle);
                }
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to parse file: ' . $e->getMessage()], 500);
        }

        if (empty($rows)) {
            return response()->json(['success' => false, 'message' => 'No rows found in file. Ensure file has header row and data.'], 400);
        }

        $normalizeHeader = function ($value) {
            $key = strtolower(trim((string)$value));
            $key = preg_replace('/\s+/', '_', $key);
            $key = preg_replace('/[^a-z0-9_]/', '', $key);
            return $key;
        };

        $rows = array_map(function ($row) use ($normalizeHeader) {
            $normalized = [];
            foreach ($row as $key => $value) {
                $normalized[$normalizeHeader($key)] = $value;
            }
            return $normalized;
        }, $rows);

        // Validate and build attendance records
        $attendanceRecords = [];
        $date = Carbon::parse($request->date);
        $attendanceType = $request->input('attendance_type', 'lectures');
        $hasAttendanceTypeColumn = Schema::hasColumn('attendance', 'attendance_type');

        // For certificates, semester and module_id are null
        $semesterName = null;
        $moduleId = null;

        if (!$isCertificate) {
            $semesterModel = \App\Models\Semester::find($request->semester);
            if (!$semesterModel) {
                return response()->json(['success' => false, 'message' => 'Semester not found.'], 404);
            }
            $semesterName = $this->getAttendanceSemesterStorageValue($semesterModel);
            $moduleId = $request->module_id;
        }

        foreach ($rows as $idx => $row) {
            $courseRegId = $row['course_registration_id'] ?? $row['course_registration'] ?? null;
            $regNo = $row['registration_number'] ?? $row['registration_no'] ?? $row['registration_id'] ?? null;
            $studentName = $row['name_with_initials'] ?? $row['student_name'] ?? $row['name'] ?? null;
            $statusRaw = $row['attendance'] ?? $row['present'] ?? null;
            if (!$statusRaw || (!$courseRegId && !$regNo && !$studentName)) continue;

            $status = strtolower(trim($statusRaw)) === 'present' ? 1 : 0;

            // Try to resolve student_id. Some DBs don't have a `registration_id` column
            // so check the schema first and fall back to student_id
            try {
                $student = null;

                if ($courseRegId) {
                    $courseReg = \App\Models\CourseRegistration::where('course_registration_id', $courseRegId)
                        ->where('course_id', $request->course_id)
                        ->where('intake_id', $request->intake_id)
                        ->where('location', $request->location)
                        ->first();
                    if ($courseReg) {
                        $student = \App\Models\Student::where('student_id', $courseReg->student_id)->first();
                    }
                }

                // Prefer searching by registration_id when the column is present, but run the queries
                // in separate steps to avoid building a single query that references a missing column
                // which can cause SQL errors on some environments.
                if (!$student && Schema::hasColumn('students', 'registration_id')) {
                    try {
                        $student = \App\Models\Student::where('registration_id', $regNo)->first();
                    } catch (\Exception $inner) {
                        // ignore and fall back to student_id
                        $student = null;
                    }
                }

                if (!$student && $regNo) {
                    // Try matching by primary key student_id
                    $student = \App\Models\Student::where('student_id', $regNo)->first();
                }
            } catch (\Exception $e) {
                // In case of any DB/schema issues, fallback to searching by primary key
                $student = $regNo ? \App\Models\Student::where('student_id', $regNo)->first() : null;
            }
            if (!$student) {
                // try by name
                $student = \App\Models\Student::where('name_with_initials', 'like', '%' . $studentName . '%')->first();
            }
            if (!$student) continue;

            $record = [
                'location' => $request->location,
                'course_id' => $request->course_id,
                'intake_id' => $request->intake_id,
                'semester' => $semesterName,
                'module_id' => $moduleId,
                'student_id' => $student->student_id,
                'status' => (bool)$status,
                'date' => $date->toDateString(),
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if ($hasAttendanceTypeColumn) {
                $record['attendance_type'] = $attendanceType;
            }

            $attendanceRecords[] = $record;
        }

        if (empty($attendanceRecords)) {
            return response()->json(['success' => false, 'message' => 'No valid student rows found or students could not be matched.'], 400);
        }

        try {
            DB::beginTransaction();

            // Delete existing for that date/intake (and module if not certificate)
            $deleteQuery = Attendance::where('date', $date->toDateString())
                ->where('course_id', $request->course_id)
                ->where('intake_id', $request->intake_id)
                ->where('location', $request->location);

            if ($isCertificate) {
                $deleteQuery->whereNull('semester')
                    ->whereNull('module_id');
            } else {
                $semesterLookupValues = $this->getAttendanceSemesterLookupValues($semesterModel);
                $this->normalizeLegacyAttendanceSemesterValues([
                    'course_id' => $request->course_id,
                    'intake_id' => $request->intake_id,
                    'location' => $request->location,
                    'module_id' => $moduleId,
                    'date' => $date->toDateString(),
                ], $semesterLookupValues, $semesterName);

                $deleteQuery->whereIn('semester', $semesterLookupValues)
                    ->where('module_id', $moduleId);
            }

            if ($hasAttendanceTypeColumn) {
                $deleteQuery->where('attendance_type', $attendanceType);
            }

            $deleteQuery->delete();

            foreach ($attendanceRecords as &$attendanceRecord) {
                $attendanceRecord = array_merge($attendanceRecord, \App\Support\UserTrackingData::forCreate());
            }
            unset($attendanceRecord);
            Attendance::insert($attendanceRecords);

            DB::commit();

            // Return the parsed attendance records with student details for frontend display
            $studentsForDisplay = [];
            foreach ($attendanceRecords as $record) {
                $student = \App\Models\Student::find($record['student_id']);
                if ($student) {
                    $courseRegistrationId = $this->resolveCourseRegistrationId(
                        $student->student_id,
                        $request->course_id,
                        $request->intake_id,
                        $request->location
                    );
                    $studentsForDisplay[] = [
                        'student_id' => $student->student_id,
                        'course_registration_id' => $courseRegistrationId,
                        'registration_number' => $courseRegistrationId ?? $student->registration_id ?? $student->student_id,
                        'name_with_initials' => $student->name_with_initials ?: $student->full_name,
                        'status' => (bool) $record['status']
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Imported attendance for ' . count($attendanceRecords) . ' students.',
                'students' => $studentsForDisplay
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Import attendance error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to import attendance: ' . $e->getMessage()], 500);
        }
    }
}
