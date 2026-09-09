<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Intake;
use App\Models\Module;
use App\Models\Student;
use App\Models\ExamResult;
use App\Models\CourseRegistration;
use App\Support\SpecializationStudentScope;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExamResultController extends Controller
{
    /**
     * Show the student exam result management view.
     */
    public function showStudentExamResultManagement()
    {
        $courses = Course::where('course_type', 'degree')->orderBy('course_name')->get();
        $modules = Module::orderBy('module_name')->get();
        $intakes = Intake::join('courses', 'intakes.course_name', '=', 'courses.course_name')
            ->select('intakes.*', 'courses.course_name as course_display_name')
            ->get()
            ->map(function ($intake) {
                $intake->intake_display_name = $intake->course_display_name . ' - ' . $intake->intake_no;
                return $intake;
            });

        return view('exam_&_results.exam_results', compact('courses', 'modules', 'intakes'));
    }

    /**
     * Get course data including modules, semesters, and years.
     */
    public function getCourseData($courseID)
    {
        try {
            $course = Course::with(['modules'])->find($courseID);

            if ($course) {
                // Assuming 'duration' is in years and 'no_of_semesters' is the total.
                // The range of years will be from 1 up to the course duration.
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
            \Log::error('Error in getCourseData for course ID ' . $courseID . ': ' . $e->getMessage());
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
     * Store a new exam result.
     */
    public function storeResult(Request $request)
    {
        try {
            \Log::info('storeResult called with data:', $request->all());

            // Check if this is a certificate course
            $isCertificate = $request->course_type === 'certificate';
            $certificateSemesterValue = $this->getCertificateSemesterStorageValue();
            $certificateModuleId = null;

            if ($isCertificate) {
                $validatedData = $request->validate([
                    'course_id' => 'required|exists:courses,course_id',
                    'intake_id' => 'required|exists:intakes,intake_id',
                    'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
                    'course_type' => 'required|string',
                    'results' => 'required|array|min:1',
                    'results.*.student_id' => 'required|exists:students,student_id',
                    'results.*.marks' => 'nullable|numeric|min:0|max:100',
                    'results.*.grade' => 'nullable|string|max:5',
                    'results.*.remarks' => 'nullable|string|max:255',
                ]);

                $certificateModuleId = $this->resolveCertificateModuleId(
                    (int) $validatedData['course_id'],
                    (int) $validatedData['intake_id']
                );

                if ($certificateModuleId === null) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No module is assigned to this certificate intake.'
                    ], 422);
                }
            } else {
                $validatedData = $request->validate([
                    'course_id' => 'required|exists:courses,course_id',
                    'intake_id' => 'required|exists:intakes,intake_id',
                    'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
                    'semester' => 'required',
                    'module_id' => 'required|exists:modules,module_id',
                    'specialization' => 'nullable|string|max:255',
                    'results' => 'required|array|min:1',
                    'results.*.student_id' => 'required|exists:students,student_id',
                    'results.*.marks' => 'nullable|numeric|min:0|max:100',
                    'results.*.grade' => 'nullable|string|max:5',
                    'results.*.remarks' => 'nullable|string|max:255',
                ]);
            }

            // Additional validation: ensure at least one of marks, grade, or remarks is provided for each result
            foreach ($validatedData['results'] as $index => $result) {
                $hasMarks = isset($result['marks']) && $result['marks'] !== null;
                $hasGrade = isset($result['grade']) && !empty($result['grade']);
                $hasRemarks = isset($result['remarks']) && !empty($result['remarks']);

                if (!$hasMarks && !$hasGrade && !$hasRemarks) {
                    return response()->json([
                        'success' => false,
                        'message' => "At least marks, grade, or remarks must be provided for student " . ($index + 1)
                    ], 422);
                }
            }

            \Log::info('Validation passed, validated data:', $validatedData);

            $course = Course::find($validatedData['course_id']);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found.'
                ], 404);
            }

            $specialization = null;
            if (!$isCertificate) {
                $specialization = $this->normalizeSpecializationValue($request->input('specialization'));
                if ($this->courseHasSpecializations($course) && !$specialization) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Specialization is required for this course.'
                    ], 422);
                }
            }

            $semesterName = null;
            $semesterLookupValues = [];
            if (!$isCertificate) {
                $semester = \App\Models\Semester::find($validatedData['semester']);
                if (!$semester) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Semester not found.'
                    ], 404);
                }

                $semesterName = $this->getSemesterStorageValue($course, $semester);
                $semesterLookupValues = $this->getSemesterLookupValues($course, $semester);

                \Log::info('Resolved exam result semester value', [
                    'semester_id' => $semester->id,
                    'raw_name' => $semester->name,
                    'stored_value' => $semesterName,
                    'lookup_values' => $semesterLookupValues,
                ]);
            }

            DB::beginTransaction();
            \Log::info('Database transaction started');

            $createdCount = 0;
            $updatedCount = 0;

            foreach ($validatedData['results'] as $index => $result) {
                \Log::info("Processing result {$index}:", $result);

                // Build query for existing result
                $existingQuery = ExamResult::where('student_id', $result['student_id'])
                    ->where('course_id', $validatedData['course_id'])
                    ->where('intake_id', $validatedData['intake_id'])
                    ->where('location', $validatedData['location']);

                if ($isCertificate) {
                    $this->applyCertificateExamResultScope($existingQuery, $certificateModuleId);
                } else {
                    $existingQuery->whereIn('semester', $semesterLookupValues)
                                  ->where('module_id', $validatedData['module_id']);
                }

                $existingResult = $existingQuery->first();

                if ($existingResult) {
                    // Update existing result
                    \Log::info("Updating existing result for student {$result['student_id']}");
                    $existingResult->update([
                        'marks' => $result['marks'] ?? null,
                        'grade' => $result['grade'] ?? null,
                        'remarks' => $result['remarks'] ?? null,
                    ]);
                    $updatedCount++;
                } else {
                    // Create new result
                    \Log::info("Creating new result for student {$result['student_id']}");
                    $resultData = [
                        'student_id' => $result['student_id'],
                        'course_id' => $validatedData['course_id'],
                        'intake_id' => $validatedData['intake_id'],
                        'location' => $validatedData['location'],
                        'marks' => $result['marks'] ?? null,
                        'grade' => $result['grade'] ?? null,
                        'remarks' => $result['remarks'] ?? null,
                    ];

                    if ($isCertificate) {
                        $resultData['semester'] = $certificateSemesterValue;
                        $resultData['module_id'] = $certificateModuleId;
                    } else {
                        $resultData['semester'] = $semesterName;
                        $resultData['module_id'] = $validatedData['module_id'];
                    }

                    ExamResult::create($resultData);
                    $createdCount++;
                }
            }

            DB::commit();
            \Log::info("Database transaction committed. Created: {$createdCount}, Updated: {$updatedCount}");

            $totalCount = $createdCount + $updatedCount;
            return response()->json([
                'success' => true,
                'message' => "Exam results stored successfully for {$totalCount} student(s)."
            ], Response::HTTP_CREATED);

        } catch (QueryException $e) {
            DB::rollBack();
            \Log::error('Database error storing exam result: ' . $e->getMessage(), [
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error storing exam result: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while storing the results.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get intakes for a given course and location.
     */
    public function getIntakesForCourseAndLocation($courseID, $location)
    {
        try {
            $course = \App\Models\Course::find($courseID);
            if (!$course) {
                return response()->json(['error' => 'Course not found.'], 404);
            }
            $intakes = \App\Models\Intake::forCourse($course, $location)
                ->orderBy('batch')
                ->get(['intake_id', 'batch']);

            return response()->json(['intakes' => $intakes]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server Error: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get modules filtered by course, intake, year, semester, and location.
     */
    public function getFilteredModules(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,course_id',
            'intake_id' => 'required|integer|exists:intakes,intake_id',
            'semester' => 'nullable|string',
            'location' => 'required|string',
            'specialization' => 'nullable|string|max:255',
        ]);

        $courseId = $request->input('course_id');
        $intakeId = $request->input('intake_id');
        $semesterId = $request->input('semester');
        $selectedSpecialization = $this->normalizeSpecializationValue($request->input('specialization'));

        // Check if this is a certificate course
        $course = \App\Models\Course::find($courseId);
        if (!$course) {
            return response()->json(['error' => 'Course not found.'], 404);
        }

        \Log::info('getFilteredModules called with:', [
            'course_id' => $courseId,
            'intake_id' => $intakeId,
            'semester_id' => $semesterId,
            'course_type' => $course->course_type
        ]);

        // Certificate courses use intake modules, not semester modules
        if ($course->course_type === 'certificate') {
            $modules = \App\Models\Module::join('intake_modules', 'modules.module_id', '=', 'intake_modules.module_id')
                ->where('intake_modules.intake_id', $intakeId)
                ->select('modules.module_id', 'modules.module_name')
                ->get();
            \Log::info('Certificate course modules found:', ['count' => $modules->count()]);
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

    public function getCoursesByLocation(Request $request)
    {
        $location = $request->query('location');
        $courseType = $request->query('course_type');

        if (!$location) {
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'Location is required.'
            ]);
        }

        try {
            $query = Course::select('course_id', 'course_name')
                ->where('location', $location);

            if ($courseType) {
                $query->where('course_type', $courseType);
            }

            $courses = $query->orderBy('course_name', 'asc')->get();

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

    public function getSemesters(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer|exists:courses,course_id',
            'intake_id' => 'required|integer|exists:intakes,intake_id',
        ]);

        $course = \App\Models\Course::find($request->course_id);
        $intake = \App\Models\Intake::find($request->intake_id);

        if (!$course || !$intake) {
            return response()->json(['error' => 'Invalid course or intake.'], 404);
        }

        // Certificate courses don't use semesters, return empty array
        if ($course->course_type === 'certificate') {
            \Log::info('ExamResultController getSemesters: Certificate course detected, returning empty semesters');
            return response()->json(['semesters' => [], 'is_certificate' => true]);
        }

        // Get semesters that have been created for this course and intake
        $semesters = \App\Models\Semester::where('course_id', $request->course_id)
            ->where('intake_id', $request->intake_id)
            ->select('id', 'name')
            ->orderBy('start_date')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(function ($semester, $index) use ($course) {
                $displayValue = $this->formatSemesterDisplayValue($course, $semester->name, $index + 1);

                return [
                    'id' => $semester->id,
                    'name' => $semester->name,
                    'display_name' => 'Semester ' . $displayValue,
                ];
            });

        \Log::info('ExamResultController getSemesters called with:', [
            'course_id' => $request->course_id,
            'intake_id' => $request->intake_id,
            'semesters_found' => $semesters->count(),
            'semesters_data' => $semesters->toArray()
        ]);

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

        return array_values(array_filter($specializations, function ($specialization) {
            return is_string($specialization) ? trim($specialization) !== '' : !empty($specialization);
        }));
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

    private function getSemesterSequenceNumber(int $courseId, int $intakeId, int $semesterId): int
    {
        $semesterSequence = \App\Models\Semester::where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->orderBy('start_date')
            ->orderBy('id')
            ->pluck('id')
            ->values();

        $semesterIndex = $semesterSequence->search($semesterId);

        return $semesterIndex === false ? 1 : ($semesterIndex + 1);
    }

    private function getSemesterStorageValue(Course $course, \App\Models\Semester $semester, ?int $fallbackNumber = null): string
    {
        $rawName = trim((string) $semester->name);
        $fallbackNumber = $fallbackNumber && $fallbackNumber > 0
            ? $fallbackNumber
            : $this->getSemesterSequenceNumber($course->course_id, $semester->intake_id, $semester->id);
        $maxSemesters = (int) ($course->no_of_semesters ?? 0);
        $numericUpperBound = $maxSemesters > 0 ? min($maxSemesters, 12) : 12;

        if (is_numeric($rawName)) {
            $numeric = (int) $rawName;
            if ($numeric >= 1 && $numeric <= $numericUpperBound) {
                return (string) $numeric;
            }
        }

        if (preg_match('/^[A-Za-z]$/', $rawName)) {
            $numeric = ord(strtoupper($rawName)) - 64;
            if ($numeric >= 1 && $numeric <= min($numericUpperBound, 26)) {
                return (string) $numeric;
            }
        }

        return (string) $fallbackNumber;
    }

    private function getSemesterLookupValues(Course $course, \App\Models\Semester $semester): array
    {
        $fallbackNumber = $this->getSemesterSequenceNumber($course->course_id, $semester->intake_id, $semester->id);

        return collect([
            $this->getSemesterStorageValue($course, $semester, $fallbackNumber),
            trim((string) $semester->name),
            $this->formatSemesterDisplayValue($course, $semester->name, $fallbackNumber),
        ])
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->unique()
            ->values()
            ->all();
    }

    private function getCertificateSemesterStorageValue(): string
    {
        return '1';
    }

    private function resolveCertificateModuleId(int $courseId, int $intakeId): ?int
    {
        $moduleId = DB::table('intake_modules')
            ->where('intake_id', $intakeId)
            ->orderBy('module_id')
            ->value('module_id');

        if ($moduleId === null) {
            $moduleId = DB::table('course_modules')
                ->where('course_id', $courseId)
                ->orderBy('module_id')
                ->value('module_id');
        }

        return $moduleId !== null ? (int) $moduleId : null;
    }

    private function applyCertificateExamResultScope($query, ?int $moduleId = null): void
    {
        $certificateSemester = $this->getCertificateSemesterStorageValue();

        $query->where(function ($semesterQuery) use ($certificateSemester) {
            $semesterQuery->whereNull('semester')
                ->orWhere('semester', $certificateSemester);
        });

        $query->where(function ($moduleQuery) use ($moduleId) {
            $moduleQuery->whereNull('module_id');

            if ($moduleId !== null) {
                $moduleQuery->orWhere('module_id', $moduleId);
            }
        });
    }

    public function getStudentsForExamResult(Request $request)
    {
        // Check if this is a certificate course (no semester or module required)
        $isCertificate = $request->course_type === 'certificate';

        if ($isCertificate) {
            $request->validate([
                'course_id' => 'required|integer|exists:courses,course_id',
                'intake_id' => 'required|integer|exists:intakes,intake_id',
                'location' => 'required|string',
                'course_type' => 'required|string',
            ]);
        } else {
            $request->validate([
                'course_id' => 'required|integer|exists:courses,course_id',
                'intake_id' => 'required|integer|exists:intakes,intake_id',
                'location' => 'required|string',
                'semester' => 'required',
                'module_id' => 'required|integer|exists:modules,module_id',
                'specialization' => 'nullable|string|max:255',
            ]);
        }

        $courseId = $request->course_id;
        $intakeId = $request->intake_id;
        $location = $request->location;
        $semesterId = $request->semester;
        $moduleId = $request->module_id;
        $specialization = $this->normalizeSpecializationValue($request->input('specialization'));
        $certificateModuleId = null;
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

        // For certificate courses, get all enrolled students
        if ($isCertificate) {
            $certificateModuleId = $this->resolveCertificateModuleId((int) $courseId, (int) $intakeId);

            // Check if exam results already exist for this intake
            $existingResults = ExamResult::where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location);

            $this->applyCertificateExamResultScope($existingResults, $certificateModuleId);
            $existingResults = $existingResults->exists();

            // Get students enrolled in the certificate course
            $students = \App\Models\CourseRegistration::where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->where('status', 'Registered')
                ->with('student')
                ->get()
                ->map(function($reg) use ($request, $existingResults, $courseId, $intakeId, $location, $certificateModuleId) {
                    $studentData = [
                        'registration_id' => $reg->course_registration_id,
                        'student_id' => $reg->student->student_id,
                        'name' => $reg->student->full_name,
                    ];

                    // If results exist, fetch the existing marks and grade
                    if ($existingResults) {
                        $existingResult = ExamResult::where('course_id', $courseId)
                            ->where('intake_id', $intakeId)
                            ->where('location', $location)
                            ->where('student_id', $reg->student->student_id);

                        $this->applyCertificateExamResultScope($existingResult, $certificateModuleId);
                        $existingResult = $existingResult->first();

                        if ($existingResult) {
                            $studentData['marks'] = $existingResult->marks;
                            $studentData['grade'] = $existingResult->grade;
                            $studentData['remarks'] = $existingResult->remarks;
                        } else {
                            $studentData['marks'] = '';
                            $studentData['grade'] = '';
                            $studentData['remarks'] = '';
                        }
                    } else {
                        $studentData['marks'] = '';
                        $studentData['grade'] = '';
                        $studentData['remarks'] = '';
                    }

                    return $studentData;
                });

            return response()->json([
                'success' => true,
                'students' => $students,
                'results_exist' => $existingResults
            ]);
        }

        // Get the semester to determine if it's core or elective (for degree/diploma)
        $semester = \App\Models\Semester::find($semesterId);
        if (!$semester) {
            return response()->json(['error' => 'Semester not found.'], 404);
        }

        $semesterLookupValues = $this->getSemesterLookupValues($course, $semester);

        // Check if this is a core module (assigned to semester) or elective module
        $isCoreModule = \DB::table('semester_module')
            ->join('modules', 'modules.module_id', '=', 'semester_module.module_id')
            ->where('semester_module.semester_id', $semesterId)
            ->where('semester_module.module_id', $moduleId)
            ->whereIn('modules.module_type', ['core', 'special_unit_compulsory'])
            ->exists();

        // Check if exam results already exist for this module
        $existingResults = ExamResult::where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->where('location', $location)
            ->whereIn('semester', $semesterLookupValues)
            ->where('module_id', $moduleId)
            ->exists();

        $moduleScope = DB::table('semester_module')
            ->where('semester_id', $semesterId)
            ->where('module_id', $moduleId)
            ->select('specialization', 'specializations')
            ->first();

        if ($isCoreModule) {
            // For core modules: Get students registered for the semester
            $students = \App\Models\SemesterRegistration::where('semester_id', $semesterId)
                ->where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->where('status', 'registered')
                ->when($specialization !== null, function ($query) use ($specialization, $courseId, $intakeId, $location, $moduleScope, $course) {
                    return \App\Support\SpecializationStudentScope::applyToQuery(
                        $query,
                        'student_id',
                        (int) $courseId,
                        (int) $intakeId,
                        $location,
                        $specialization,
                        $moduleScope?->specializations,
                        $moduleScope?->specialization,
                        $this->getCourseSpecializations($course)
                    );
                })
                ->with('student')
                ->get()
                ->map(function($reg) use ($request, $existingResults, $semesterLookupValues, $courseId, $intakeId, $location) {
                    // Get the course registration ID from CourseRegistration table
                    $courseReg = \App\Models\CourseRegistration::where('student_id', $reg->student_id)
                        ->where('course_id', $courseId)
                        ->where('intake_id', $intakeId)
                        ->where('location', $location)
                        ->first();

                    $studentData = [
                        'registration_id' => $courseReg ? $courseReg->course_registration_id : '',
                        'student_id' => $reg->student->student_id,
                        'name' => $reg->student->full_name,
                    ];

                    // If results exist, fetch the existing marks and grade
                    if ($existingResults) {
                        $existingResult = ExamResult::where('course_id', $request->course_id)
                            ->where('intake_id', $request->intake_id)
                            ->where('location', $request->location)
                            ->whereIn('semester', $semesterLookupValues)
                            ->where('module_id', $request->module_id)
                            ->where('student_id', $reg->student->student_id)
                            ->first();

                        if ($existingResult) {
                            $studentData['marks'] = $existingResult->marks;
                            $studentData['grade'] = $existingResult->grade;
                            $studentData['remarks'] = $existingResult->remarks;
                        } else {
                            $studentData['marks'] = '';
                            $studentData['grade'] = '';
                            $studentData['remarks'] = '';
                        }
                    } else {
                        $studentData['marks'] = '';
                        $studentData['grade'] = '';
                        $studentData['remarks'] = '';
                    }

                    return $studentData;
                })
                ->sortBy('registration_id')
                ->values();
        } else {
            // For elective modules: Get students registered for the specific module
            $students = \App\Models\ModuleManagement::where('module_id', $moduleId)
                ->where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->where('semester', $semester->name)
                ->when($specialization !== null, function ($query) use ($specialization, $courseId, $intakeId, $location, $moduleScope, $course) {
                    return \App\Support\SpecializationStudentScope::applyToQuery(
                        $query,
                        'student_id',
                        (int) $courseId,
                        (int) $intakeId,
                        $location,
                        $specialization,
                        $moduleScope?->specializations,
                        $moduleScope?->specialization,
                        $this->getCourseSpecializations($course)
                    );
                })
                ->with('student')
                ->get()
                ->map(function($reg) use ($request, $existingResults, $semesterLookupValues, $courseId, $intakeId, $location) {
                    // Get the course registration ID from CourseRegistration table
                    $courseReg = \App\Models\CourseRegistration::where('student_id', $reg->student_id)
                        ->where('course_id', $courseId)
                        ->where('intake_id', $intakeId)
                        ->where('location', $location)
                        ->first();

                    $studentData = [
                        'registration_id' => $courseReg ? $courseReg->course_registration_id : '',
                        'student_id' => $reg->student->student_id,
                        'name' => $reg->student->full_name,
                    ];

                    // If results exist, fetch the existing marks and grade
                    if ($existingResults) {
                        $existingResult = ExamResult::where('course_id', $request->course_id)
                            ->where('intake_id', $request->intake_id)
                            ->where('location', $request->location)
                            ->whereIn('semester', $semesterLookupValues)
                            ->where('module_id', $request->module_id)
                            ->where('student_id', $reg->student->student_id)
                            ->first();

                        if ($existingResult) {
                            $studentData['marks'] = $existingResult->marks;
                            $studentData['grade'] = $existingResult->grade;
                            $studentData['remarks'] = $existingResult->remarks;
                        } else {
                            $studentData['marks'] = '';
                            $studentData['grade'] = '';
                            $studentData['remarks'] = '';
                        }
                    } else {
                        $studentData['marks'] = '';
                        $studentData['grade'] = '';
                        $studentData['remarks'] = '';
                    }

                    return $studentData;
                })
                ->sortBy('registration_id')
                ->values();
        }

        return response()->json([
            'success' => true,
            'students' => $students,
            'results_exist' => $existingResults
        ]);
    }

    /**
     * Show the exam results view and edit page.
     */
    public function showExamResultsViewEdit()
    {
        return view('exam_&_results.exam_results_view_edit');
    }

    /**
     * Get existing exam results for viewing and editing.
     */
    public function getExistingExamResults(Request $request)
    {
        $validatedBase = $request->validate([
            'course_id' => 'required|integer|exists:courses,course_id',
            'intake_id' => 'required|integer|exists:intakes,intake_id',
            'location' => 'required|string',
            'specialization' => 'nullable|string|max:255',
        ]);

        $course = Course::where('course_id', $validatedBase['course_id'])->first();
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

        $resultsQuery = ExamResult::where('course_id', $validatedBase['course_id'])
            ->where('intake_id', $validatedBase['intake_id'])
            ->where('location', $validatedBase['location']);

        if ($course->course_type === 'certificate') {
            $certificateModuleId = $this->resolveCertificateModuleId(
                (int) $validatedBase['course_id'],
                (int) $validatedBase['intake_id']
            );
            $this->applyCertificateExamResultScope($resultsQuery, $certificateModuleId);
        } else {
            $validatedDegree = $request->validate([
                'semester' => 'required|integer|exists:semesters,id',
                'module_id' => 'required|integer|exists:modules,module_id',
            ]);

            $semester = \App\Models\Semester::find($validatedDegree['semester']);
            if (!$semester) {
                return response()->json([
                    'success' => false,
                    'message' => 'Semester not found.'
                ], 404);
            }

            $resultsQuery->whereIn('semester', $this->getSemesterLookupValues($course, $semester))
                ->where('module_id', $validatedDegree['module_id']);
        }

        $results = $resultsQuery
            ->with(['student.courseRegistrations', 'course', 'module', 'intake'])
            ->get()
            ->map(function($result) {
                $registration = $result->student->courseRegistrations
                    ->where('course_id', $result->course_id)
                    ->where('intake_id', $result->intake_id)
                    ->first();

                return [
                    'id' => $result->id,
                    'student_id' => $result->student_id,
                    'registration_id' => $registration ? $registration->course_registration_id : '',
                    'student_name' => $result->student->full_name,
                    'marks' => $result->marks,
                    'grade' => $result->grade,
                    'remarks' => $result->remarks,
                    'created_at' => $result->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $result->updated_at->format('Y-m-d H:i:s'),
                ];
            })
            ->sortBy('registration_id')
            ->values();

        return response()->json([
            'success' => true,
            'results' => $results,
            'total_count' => $results->count()
        ]);
    }

    /**
     * Update existing exam results.
     */
    public function updateResult(Request $request)
    {
        try {
            $validationRules = [
                'course_id' => 'required|exists:courses,course_id',
                'intake_id' => 'required|exists:intakes,intake_id',
                'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
                'course_type' => 'nullable|string',
                'results' => 'required|array|min:1',
                'results.*.id' => 'required|exists:exam_results,id',
                'results.*.marks' => 'nullable|numeric|min:0|max:100',
                'results.*.grade' => 'nullable|string|max:5',
                'results.*.remarks' => 'nullable|string|max:255',
            ];

            $validatedData = $request->validate($validationRules);

            $course = Course::find($validatedData['course_id']);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found.'
                ], 404);
            }

            $isCertificate = ($validatedData['course_type'] ?? $course->course_type) === 'certificate';

            if (!$isCertificate) {
                $request->validate([
                    'semester' => 'required',
                    'module_id' => 'required|exists:modules,module_id',
                ]);
            }

            // Additional validation: ensure at least one of marks, grade, or remarks is provided for each result
            foreach ($validatedData['results'] as $index => $result) {
                $hasMarks = isset($result['marks']) && $result['marks'] !== null;
                $hasGrade = isset($result['grade']) && !empty($result['grade']);
                $hasRemarks = isset($result['remarks']) && !empty($result['remarks']);

                if (!$hasMarks && !$hasGrade && !$hasRemarks) {
                    return response()->json([
                        'success' => false,
                        'message' => "At least marks, grade, or remarks must be provided for result " . ($index + 1)
                    ], 422);
                }
            }

            if (!$isCertificate) {
                $semester = \App\Models\Semester::find($request->input('semester'));
                if (!$semester) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Semester not found.'
                    ], 404);
                }
            }

            DB::beginTransaction();

            $updatedCount = 0;
            foreach ($validatedData['results'] as $result) {
                $examResult = ExamResult::find($result['id']);
                if ($examResult) {
                    $examResult->update([
                        'marks' => $result['marks'] ?? null,
                        'grade' => $result['grade'] ?? null,
                        'remarks' => $result['remarks'] ?? null,
                    ]);
                    $updatedCount++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Successfully updated {$updatedCount} exam result(s)."
            ], Response::HTTP_OK);

        } catch (QueryException $e) {
            DB::rollBack();
            \Log::error('Database error updating exam result: ' . $e->getMessage(), [
                'request_data' => $request->all()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error updating exam result: ' . $e->getMessage(), [
                'request_data' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the results.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Auto-calculate grades from marks for existing exam results.
     */
    public function autoCalculateGrades(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'course_id' => 'required|exists:courses,course_id',
                'intake_id' => 'required|exists:intakes,intake_id',
                'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
                'semester' => 'required',
                'module_id' => 'required|exists:modules,module_id',
            ]);

            $updatedCount = ExamResult::autoCalculateGrades(
                $validatedData['course_id'],
                $validatedData['module_id'],
                $validatedData['intake_id']
            );

            return response()->json([
                'success' => true,
                'message' => "Successfully auto-calculated grades for {$updatedCount} exam result(s).",
                'updated_count' => $updatedCount
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            \Log::error('Error auto-calculating grades: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while auto-calculating grades.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Download template CSV with actual student data
     */
    public function downloadTemplate(Request $request)
    {
        try {
            $request->validate([
                'course_id' => 'required|integer|exists:courses,course_id',
                'intake_id' => 'required|integer|exists:intakes,intake_id',
                'location' => 'required|string',
                'semester' => 'required',
                'module_id' => 'required|integer|exists:modules,module_id',
            ]);

            $courseId = $request->course_id;
            $intakeId = $request->intake_id;
            $location = $request->location;
            $semesterId = $request->semester;
            $moduleId = $request->module_id;

            // Get course, intake, semester, and module names
            $course = Course::find($courseId);
            $intake = Intake::find($intakeId);
            $semester = \App\Models\Semester::find($semesterId);
            $module = Module::find($moduleId);

            if (!$course || !$intake || !$semester || !$module) {
                return response()->json(['error' => 'Invalid course, intake, semester, or module.'], 404);
            }

            $semesterSequence = \App\Models\Semester::where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->orderBy('start_date')
                ->orderBy('id')
                ->pluck('id')
                ->values();
            $semesterIndex = $semesterSequence->search($semester->id);
            $semesterDisplayValue = $this->formatSemesterDisplayValue(
                $course,
                $semester->name,
                $semesterIndex === false ? null : ($semesterIndex + 1)
            );

            Log::info('Download template called with:', [
                'course_id' => $courseId,
                'intake_id' => $intakeId,
                'location' => $location,
                'semester_id' => $semesterId,
                'module_id' => $moduleId,
                'course_name' => $course->course_name,
                'intake_no' => $intake->intake_no,
                'semester_name' => $semester->name,
                'semester_display' => $semesterDisplayValue,
                'module_name' => $module->module_name
            ]);

            $students = collect();

            $isElectiveModule = strtolower((string) $module->module_type) === 'elective';

            // Core and special-unit modules apply to semester-registered students.
            // Elective modules use only the registrations recorded in module_management.
            $semesterRegistrations = $isElectiveModule ? collect() : \App\Models\SemesterRegistration::where('semester_id', $semesterId)
                ->where('course_id', $courseId)
                ->where('intake_id', $intakeId)
                ->where('location', $location)
                ->where('status', 'registered')
                ->with('student')
                ->get();

            Log::info('SemesterRegistration query result:', [
                'count' => $semesterRegistrations->count(),
                'student_ids' => $semesterRegistrations->pluck('student_id')->toArray()
            ]);

            if ($semesterRegistrations->count() > 0) {
                $students = $semesterRegistrations->map(function($reg) use ($course, $module, $intake, $location, $semesterDisplayValue) {
                    return [
                        'Student Name' => $reg->student->full_name,
                        'Course Name' => $course->course_name,
                        'Module Name' => $module->module_name,
                        'Intake' => $intake->intake_no ?? $intake->batch ?? '2025-August',
                        'Location' => $location,
                        'Semester' => $semesterDisplayValue,
                        'Marks' => '',
                        'Grade' => '',
                        'Remarks' => ''
                    ];
                });
            } else {
                // Fallback: Try module management for elective modules
                $moduleRegistrations = \App\Models\ModuleManagement::where('module_id', $moduleId)
                    ->where('course_id', $courseId)
                    ->where('intake_id', $intakeId)
                    ->where('location', $location)
                    ->with('student')
                    ->get();

                Log::info('ModuleManagement query result:', [
                    'count' => $moduleRegistrations->count(),
                    'student_ids' => $moduleRegistrations->pluck('student_id')->toArray()
                ]);

                if ($moduleRegistrations->count() > 0) {
                    $students = $moduleRegistrations->map(function($reg) use ($course, $module, $intake, $location, $semesterDisplayValue) {
                        return [
                            'Student Name' => $reg->student->full_name,
                            'Course Name' => $course->course_name,
                            'Module Name' => $module->module_name,
                            'Intake' => $intake->intake_no ?? $intake->batch ?? '2025-August',
                            'Location' => $location,
                            'Semester' => $semesterDisplayValue,
                            'Marks' => '',
                            'Grade' => '',
                            'Remarks' => ''
                        ];
                    });
                } elseif (!$isElectiveModule) {
                    // Final fallback: Get all students registered for this course and intake
                    $courseRegistrations = \App\Models\CourseRegistration::where('course_id', $courseId)
                        ->where('intake_id', $intakeId)
                        ->where('location', $location)
                        ->whereIn('approval_status', ['approved', 'registered'])
                        ->with('student')
                        ->get();

                    Log::info('CourseRegistration fallback query result:', [
                        'count' => $courseRegistrations->count(),
                        'student_ids' => $courseRegistrations->pluck('student_id')->toArray()
                    ]);

                    $students = $courseRegistrations->map(function($reg) use ($course, $module, $intake, $location, $semesterDisplayValue) {
                        return [
                            'Student Name' => $reg->student->full_name,
                            'Course Name' => $course->course_name,
                            'Module Name' => $module->module_name,
                            'Intake' => $intake->intake_no ?? $intake->batch ?? '2025-August',
                            'Location' => $location,
                            'Semester' => $semesterDisplayValue,
                            'Marks' => '',
                            'Grade' => '',
                            'Remarks' => ''
                        ];
                    });
                }
            }

            Log::info('Final student count for template:', ['count' => $students->count()]);

            // Create CSV content
            $csvContent = [];

            // Add headers
            if ($students->count() > 0) {
                $csvContent[] = implode(',', array_keys($students->first()));

                // Add student data rows
                foreach ($students as $student) {
                    $row = [];
                    foreach ($student as $value) {
                        // Properly escape values that contain commas, quotes, or newlines
                        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
                            $row[] = '"' . str_replace('"', '""', $value) . '"';
                        } else {
                            $row[] = $value;
                        }
                    }
                    $csvContent[] = implode(',', $row);
                }
            } else {
                // If no students found, create template with headers only
                $csvContent[] = 'Student Name,Course Name,Module Name,Intake,Location,Semester,Marks,Grade,Remarks';
                Log::warning('No students found for template generation');
            }

            $csv = implode("\n", $csvContent);

            // Create response with CSV content
            $filename = 'exam_results_template_' .
                       str_replace(' ', '_', $course->course_name) . '_' .
                       str_replace(' ', '_', $module->module_name) . '_' .
                       $intake->intake_no . '.csv';

            return response($csv, 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Error downloading template: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while downloading template.'
            ], 500);
        }
    }
}
