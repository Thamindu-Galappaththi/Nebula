<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Module;
use App\Models\Course;
use App\Models\Intake;
use App\Models\Student;
use App\Models\ModuleManagement;
use App\Models\CourseRegistration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Semester;
use App\Models\SpecializationRegistration;
use App\Support\SemesterModuleSpecializationHelper;
use App\Support\SpecializationStudentScope;
use Illuminate\Support\Facades\Schema;

class ModuleManagementController extends Controller
{
    private const COMMON_SPECIALIZATION = 'Common';

    private function normalizeSpecializationValue($value)
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function courseHasSpecializations(?Course $course): bool
    {
        if (!$course || empty($course->specializations)) {
            return false;
        }

        $specializations = is_array($course->specializations)
            ? $course->specializations
            : json_decode($course->specializations, true);

        return is_array($specializations) && count(array_filter($specializations)) > 0;
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
            if (is_string($value)) {
                $trimmed = trim($value);
                return $trimmed === '' ? null : $trimmed;
            }
            if (is_array($value) || is_object($value)) {
                $value = (array) $value;
                $name = trim((string) ($value['name'] ?? $value['title'] ?? $value['specialization'] ?? ''));
                return $name === '' ? null : $name;
            }
            return null;
        }, $decoded))));
    }

    /**
     * Display the module management page.
     */
    public function showModuleManagement()
    {
        if (!Auth::check() || !Auth::user()->status) {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }

        $degreeCourses = Course::where('course_type', 'degree')->orderBy('course_name')->get(['course_id', 'course_name', 'location']);
        $diplomaCourses = Course::where('course_type', 'diploma')->orderBy('course_name')->get(['course_id', 'course_name', 'location']);

        return view('registration.module_management', compact('degreeCourses', 'diplomaCourses'));
    }

    /**
     * Get intakes for selected course and location
     */
    public function getIntakes(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'course_type' => 'nullable|string'
        ]);

        try {
            // Get the course name for the given course_id
            $course = \App\Models\Course::find($request->course_id);
            if (!$course) {
                return response()->json(['success' => false, 'data' => []]);
            }

            // Build the query
            $query = \App\Models\Intake::forCourse($course, $request->location);

            // Add course_type filter if provided
            if ($request->has('course_type') && $request->course_type) {
                $query->where('intake_type', $request->course_type);
            }

            // Get intakes ordered by batch
            $intakes = $query->orderBy('batch')
                ->get(['intake_id', 'batch'])
                ->map(function ($intake) {
                    return [
                        'intake_id' => $intake->intake_id,
                        'intake_name' => $intake->batch,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $intakes
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching intakes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching intakes.'
            ], 500);
        }
    }

    /**
     * Get students for selected intake and semester
     */
    public function getStudents(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'intake_id' => 'required|exists:intakes,intake_id',
            'semester' => 'required|string',
            'course_id' => 'required|exists:courses,course_id',
            'specialization' => 'nullable|string'
        ]);

        try {
            $specialization = $this->normalizeSpecializationValue($request->specialization);
            $course = Course::find($request->course_id);

            if ($this->courseHasSpecializations($course) && !$specialization) {
                return response()->json(['success' => false, 'message' => 'Please select a specialization for this course.'], 422);
            }

            // Get students who have registered for this semester through semester registration
            $students = \App\Models\SemesterRegistration::where('intake_id', $request->intake_id)
                                                       ->where('course_id', $request->course_id)
                                                       ->where('semester_id', $request->semester)
                                                       ->when($specialization, function ($query) use ($specialization) {
                                                           $query->where('specialization', $specialization);
                                                       })
                                                       ->with(['student:student_id,full_name,id_value,email'])
                                                       ->get();

            $mappedStudents = $students->map(function ($registration) {
                return [
                    'student_id' => $registration->student->student_id ?? null,
                    'name' => $registration->student->full_name ?? '',
                    'full_name' => $registration->student->full_name ?? '',
                    'nic' => $registration->student->id_value ?? '',
                    'id_value' => $registration->student->id_value ?? '',
                    'email' => $registration->student->email ?? '',
                    'specialization' => $registration->specialization ?? ''
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $mappedStudents
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching students.'
            ], 500);
        }
    }

    /**
     * Get modules for selected course
     */
    public function getModules(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'course_id' => 'required|exists:courses,course_id'
        ]);

        try {
            $course = Course::find($request->course_id);
            if (!$course) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course not found.'
                ], 404);
            }

            // Get modules belonging to the course using the canonical course-module relation
            $modules = $course->modules()->orderBy('module_name')->get();

            return response()->json([
                'success' => true,
                'data' => $modules
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching modules: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching modules.'
            ], 500);
        }
    }

    /**
     * Get current module assignments for students
     */
    public function getModuleAssignments(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'intake_id' => 'required|exists:intakes,intake_id',
            'semester' => 'required|in:1,2,3,4,5,6',
            'specialization' => 'nullable|string'
        ]);

        try {
            $specialization = $this->normalizeSpecializationValue($request->specialization);

            $assignments = ModuleManagement::where('intake_id', $request->intake_id)
                                         ->where('semester', $request->semester)
                                         ->when($specialization, function ($query) use ($specialization) {
                                             $query->where('specialization', $specialization);
                                         })
                                         ->with(['student:student_id,full_name', 'module:module_id,module_name'])
                                         ->get()
                                         ->map(function ($assignment) {
                                             return [
                                                 'id' => $assignment->id,
                                                 'student_id' => $assignment->student->student_id ?? null,
                                                 'student_name' => $assignment->student->full_name ?? '',
                                                 'full_name' => $assignment->student->full_name ?? '',
                                                 'module_id' => $assignment->module->module_id ?? null,
                                                 'module_name' => $assignment->module->module_name ?? '',
                                                 'specialization' => $assignment->specialization ?? ''
                                             ];
                                         });

            return response()->json([
                'success' => true,
                'data' => $assignments
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching module assignments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching module assignments.'
            ], 500);
        }
    }

    /**
     * Assign modules to students
     */
    public function assignModules(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'assignments' => 'required|array',
            'assignments.*.student_id' => 'required|exists:students,student_id',
            'assignments.*.module_id' => 'required|exists:modules,module_id',
            'intake_id' => 'required|exists:intakes,intake_id',
            'course_id' => 'required|exists:courses,course_id',
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'semester' => 'required|in:1,2,3,4,5,6',
            'specialization' => 'nullable|string'
        ]);

        try {
            $specialization = $this->normalizeSpecializationValue($request->specialization);
            $course = Course::find($request->course_id);

            if ($this->courseHasSpecializations($course) && !$specialization) {
                return response()->json(['success' => false, 'message' => 'Please select a specialization for this course.'], 422);
            }

            DB::beginTransaction();

            // Delete existing assignments for this intake and semester
            ModuleManagement::where('intake_id', $request->intake_id)
                          ->where('semester', $request->semester)
                          ->when($specialization, function ($query) use ($specialization) {
                              $query->where('specialization', $specialization);
                          })
                          ->delete();

            // Create new assignments
            $assignments = [];
            foreach ($request->assignments as $assignment) {
                $assignments[] = [
                    'student_id' => $assignment['student_id'],
                    'module_id' => $assignment['module_id'],
                    'intake_id' => $request->intake_id,
                    'course_id' => $request->course_id,
                    'location' => $request->location,
                    'semester' => $request->semester,
                    'specialization' => $specialization,
                    'created_at' => now(),
                    'updated_at' => now(),
                    ...\App\Support\UserTrackingData::forCreate(),
                ];
            }

            ModuleManagement::insert($assignments);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Modules assigned successfully.'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error assigning modules: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error assigning modules.'
            ], 500);
        }
    }

    /**
     * Get current elective module registrations
     */
    public function getElectiveRegistrations(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'specialization' => 'nullable|string'
        ]);

        try {
            $semester = Semester::find($request->semester_id);
            $specialization = $this->normalizeSpecializationValue($request->specialization);
            
            $registrations = ModuleManagement::where('intake_id', $request->intake_id)
                                           ->where('course_id', $request->course_id)
                                           ->where('location', $request->location)
                                           ->where('semester', $semester->name)
                                           ->when($specialization, function ($query) use ($specialization) {
                                               $query->where('specialization', $specialization);
                                           })
                                           ->whereHas('module', function($query) use ($request) {
                                               $query->whereHas('courses', function($q) use ($request) {
                                                   $q->where('course_id', $request->course_id)
                                                     ->where('is_core', false);
                                               });
                                           })
                                           ->with(['student:student_id,full_name', 'module:module_id,module_name'])
                                           ->get()
                                           ->map(function ($registration) {
                                               return [
                                                   'id' => $registration->id,
                                                   'student_id' => $registration->student->student_id ?? null,
                                                   'student_name' => $registration->student->full_name ?? '',
                                                   'full_name' => $registration->student->full_name ?? '',
                                                   'module_id' => $registration->module->module_id ?? null,
                                                   'module_name' => $registration->module->module_name ?? '',
                                                   'specialization' => $registration->specialization,
                                               ];
                                           });

            return response()->json([
                'success' => true,
                'data' => $registrations
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching elective registrations: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching elective registrations.'
            ], 500);
        }
    }

    /**
     * Remove a module assignment
     */
    public function removeAssignment(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'assignment_id' => 'required|exists:module_management,id'
        ]);

        try {
            $assignment = ModuleManagement::find($request->assignment_id);
            if (!$assignment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Assignment not found.'
                ], 404);
            }

            $assignment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Assignment removed successfully.'
            ]);
        } catch (\Exception $e) {
            Log::error('Error removing assignment: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error removing assignment.'
            ], 500);
        }
    }

    /**
     * Get module statistics
     */
    public function getModuleStatistics(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $statistics = [
                'total_assignments' => ModuleManagement::count(),
                'assignments_by_location' => ModuleManagement::getStudentCountByLocation(),
                'assignments_by_semester' => ModuleManagement::getStudentCountBySemester(),
                'assignments_by_course' => ModuleManagement::getStudentCountByCourse(),
                'assignments_by_module' => ModuleManagement::getStudentCountByModule()
            ];

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching module statistics: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching module statistics.'
            ], 500);
        }
    }

    /**
     * Get ongoing semesters for elective module registration
     */
    public function getOngoingSemesters(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya'
        ]);

        try {
            $semesters = Semester::where('course_id', $request->course_id)
                                ->where('intake_id', $request->intake_id)
                                ->whereIn('status', ['active', 'upcoming']) // Show both ongoing and upcoming semesters
                                ->orderBy('name')
                                ->get()
                                ->map(function($semester) {
                                    // Get elective modules for this semester from semester_module table
                                    $electiveModules = \DB::table('modules')
                                        ->join('semester_module', 'modules.module_id', '=', 'semester_module.module_id')
                                        ->where('semester_module.semester_id', $semester->id)
                                        ->whereRaw('LOWER(modules.module_type) = ?', ['elective'])
                                        ->select('modules.module_id', 'modules.module_name', 'modules.module_type', 'modules.credits', 'semester_module.specialization', 'semester_module.specializations')
                                        ->orderBy('modules.module_name')
                                        ->get();

                                    return [
                                        'id' => $semester->id,
                                        'name' => $semester->name,
                                        'status' => $semester->status,
                                        'elective_modules' => $electiveModules
                                    ];
                                });

            return response()->json([
                'success' => true,
                'data' => $semesters
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching ongoing semesters: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching ongoing semesters.'
            ], 500);
        }
    }

    /**
     * Get eligible students for elective module registration
     */
    public function getElectiveStudents(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
            'semester_id' => 'required|exists:semesters,id',
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'specialization' => 'nullable|string|max:255',
            'module_id' => 'nullable|exists:modules,module_id',
        ]);

        try {
            $specialization = $this->normalizeSpecializationValue($request->specialization);
            $course = Course::find($request->course_id);

            $query = \App\Models\SemesterRegistration::query()
                ->where('course_id', $request->course_id)
                ->where('intake_id', $request->intake_id)
                ->where('semester_id', $request->semester_id)
                ->where('location', $request->location)
                ->where('status', 'registered')
                ->whereHas('student')
                ->with('student');

            if ($specialization && $specialization !== self::COMMON_SPECIALIZATION) {
                $specializationStudentIds = SpecializationStudentScope::resolveStudentIds(
                    (int) $request->course_id,
                    (int) $request->intake_id,
                    $request->location,
                    $specialization,
                    null,
                    null,
                    $this->decodeCourseSpecializations($course)
                );

                if (empty($specializationStudentIds)) {
                    return response()->json(['success' => true, 'students' => []]);
                }

                $query->whereIn('student_id', $specializationStudentIds);
            }

            $students = $query->get()->unique('student_id')->values();
            $studentIds = $students->pluck('student_id')->filter()->unique()->values();

            $courseRegistrationByStudent = CourseRegistration::where('course_id', $request->course_id)
                ->where('intake_id', $request->intake_id)
                ->where('location', $request->location)
                ->whereIn('student_id', $studentIds)
                ->orderByDesc('id')
                ->get()
                ->unique('student_id')
                ->keyBy(fn ($registration) => (int) $registration->student_id);

            $assignmentsByStudentId = collect();
            if (Schema::hasTable('specialization_registrations') && $studentIds->isNotEmpty()) {
                $assignmentsByStudentId = SpecializationRegistration::query()
                    ->where('course_id', $request->course_id)
                    ->where('intake_id', $request->intake_id)
                    ->where('location', $request->location)
                    ->where('status', 'registered')
                    ->whereIn('student_id', $studentIds)
                    ->get()
                    ->keyBy(fn ($assignment) => (int) $assignment->student_id);
            }

            $alreadyRegistered = [];
            if ($request->filled('module_id')) {
                $semester = Semester::find($request->semester_id);
                if ($semester && $studentIds->isNotEmpty()) {
                    $alreadyRegistered = ModuleManagement::query()
                        ->where('module_id', $request->module_id)
                        ->where('semester', $semester->name)
                        ->where('course_id', $request->course_id)
                        ->where('intake_id', $request->intake_id)
                        ->where('location', $request->location)
                        ->whereIn('student_id', $studentIds)
                        ->pluck('student_id')
                        ->map(fn ($id) => (int) $id)
                        ->all();
                }
            }

            $mappedStudents = $students->map(function ($reg) use ($courseRegistrationByStudent, $assignmentsByStudentId, $alreadyRegistered) {
                $student = $reg->student;
                if (!$student) {
                    return null;
                }

                $studentId = (int) $student->student_id;
                $assignment = $assignmentsByStudentId->get($studentId);
                $specialization = $assignment ? trim((string) $assignment->specialization) : null;

                return [
                    'student_id' => $studentId,
                    'course_registration_id' => optional($courseRegistrationByStudent->get($studentId))->course_registration_id,
                    'name' => $student->name_with_initials ?: $student->full_name,
                    'specialization' => $specialization !== '' ? $specialization : null,
                    'email' => $student->email,
                    'nic' => $student->id_value,
                    'already_registered' => in_array($studentId, $alreadyRegistered, true),
                ];
            })->filter()->values();

            return response()->json([
                'success' => true,
                'students' => $mappedStudents
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching elective students: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching eligible students.'
            ], 500);
        }
    }

    /**
     * Get elective modules for a specific semester
     */
    public function getElectiveModules(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'semester_id' => 'required|exists:semesters,id',
            'course_id' => 'required|exists:courses,course_id',
            'specialization' => 'nullable|string|max:255'
        ]);

        try {
            $selectedSpecialization = $this->normalizeSpecializationValue($request->specialization);
            $allElectiveModules = \DB::table('modules')
                ->join('semester_module', 'modules.module_id', '=', 'semester_module.module_id')
                ->where('semester_module.semester_id', $request->semester_id)
                ->whereRaw('LOWER(modules.module_type) = ?', ['elective'])
                ->select('modules.module_id', 'modules.module_name', 'modules.module_type', 'modules.credits', 'semester_module.specialization', 'semester_module.specializations')
                ->orderBy('modules.module_name')
                ->get();

            $moduleScopes = $allElectiveModules->map(function ($module) {
                $module->scope_specializations = SemesterModuleSpecializationHelper::decodeList(
                    $module->specializations,
                    $module->specialization
                );
                return $module;
            });

            $availableSpecializations = $moduleScopes->flatMap(fn ($module) => $module->scope_specializations ?? [])->unique()->values();
            $commonAvailable = $moduleScopes->contains(fn ($module) => $module->scope_specializations === null);
            $electiveModules = $moduleScopes->filter(function ($module) use ($selectedSpecialization) {
                if (!$selectedSpecialization) return true;
                if ($selectedSpecialization === self::COMMON_SPECIALIZATION) return $module->scope_specializations === null;
                return in_array($selectedSpecialization, $module->scope_specializations ?? [], true);
            })->values();

            return response()->json([
                'success' => true,
                'data' => $electiveModules,
                'available_specializations' => $availableSpecializations,
                'common_available' => $commonAvailable,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching elective modules: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching elective modules.'
            ], 500);
        }
    }

    /**
     * Register students for elective modules
     */
    public function registerElectiveModules(Request $request)
    {
        if (!Auth::check() || !Auth::user()->status) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $request->validate([
            'register_students' => 'required|array|min:1',
            'register_students.*' => 'exists:students,student_id',
            'semester_id' => 'required|exists:semesters,id',
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'module_id' => 'required|exists:modules,module_id',
            'specialization' => 'nullable|string|max:255'
        ]);

        try {
            DB::beginTransaction();

            $semester = Semester::find($request->semester_id);
            if (!$semester) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid semester selected. Please try again.'
                ], 400);
            }

            $moduleScope = DB::table('modules')
                ->join('semester_module', 'modules.module_id', '=', 'semester_module.module_id')
                ->where('semester_module.semester_id', $semester->id)
                ->where('modules.module_id', $request->module_id)
                ->whereRaw('LOWER(modules.module_type) = ?', ['elective'])
                ->select('semester_module.specialization', 'semester_module.specializations')
                ->first();

            if (!$moduleScope) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'The selected module is not an elective assigned to this semester.'], 422);
            }

            $selectedSpecialization = $this->normalizeSpecializationValue($request->specialization);
            $moduleSpecializations = SemesterModuleSpecializationHelper::decodeList(
                $moduleScope->specializations,
                $moduleScope->specialization
            );

            $selectionMatchesModule = $moduleSpecializations === null
                ? $selectedSpecialization === self::COMMON_SPECIALIZATION
                : $selectedSpecialization && in_array($selectedSpecialization, $moduleSpecializations, true);

            if (!$selectionMatchesModule) {
                DB::rollBack();
                return response()->json(['success' => false, 'message' => 'Select the specialization that matches the elective module.'], 422);
            }

            $registrations = [];
            $alreadyRegistered = 0;
            foreach ($request->register_students as $studentId) {
                // Check if student is already registered for this module in this semester
                $existing = ModuleManagement::where('student_id', $studentId)
                                          ->where('module_id', $request->module_id)
                                          ->where('semester', $semester->name)
                                          ->where('course_id', $request->course_id)
                                          ->where('intake_id', $request->intake_id)
                                          ->where('location', $request->location)
                                          ->exists();

                if (!$existing) {
                    $registrations[] = [
                        'student_id' => $studentId,
                        'module_id' => $request->module_id,
                        'intake_id' => $request->intake_id,
                        'course_id' => $request->course_id,
                        'location' => $request->location,
                        'specialization' => $selectedSpecialization === self::COMMON_SPECIALIZATION ? null : $selectedSpecialization,
                        'semester' => $semester->name,
                        'created_at' => now(),
                        'updated_at' => now(),
                        ...\App\Support\UserTrackingData::forCreate(),
                    ];
                } else {
                    $alreadyRegistered++;
                }
            }

            if (!empty($registrations)) {
                ModuleManagement::insert($registrations);
            }

            DB::commit();

            // Get module name for the success message
            $module = \DB::table('modules')->where('module_id', $request->module_id)->first();
            $moduleName = $module ? $module->module_name : 'Elective Module';

            if (count($registrations) > 0) {
                $successMessage = count($registrations) . ' student(s) registered for ' . $moduleName . ' in ' . $semester->name . '.';
                if ($alreadyRegistered > 0) {
                    $successMessage .= ' ' . $alreadyRegistered . ' student(s) were already registered.';
                }
            } else {
                $successMessage = 'All selected students are already registered for ' . $moduleName . ' in ' . $semester->name . '.';
            }

            return response()->json([
                'success' => true,
                'message' => $successMessage,
                'registrations_count' => count($registrations),
                'already_registered_count' => $alreadyRegistered
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Error registering elective modules: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while registering elective modules. Please try again.'
            ], 500);
        }
    }
}
