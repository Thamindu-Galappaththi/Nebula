<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Semester;
use App\Models\Course;
use App\Models\Intake;
use App\Models\Module;
use App\Support\SemesterModuleSpecializationHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SemesterCreationController extends Controller
{
    public function index()
    {
        $semesters = Semester::with(['course', 'intake', 'modules'])->orderBy('created_at', 'desc')->get();
        $this->assignSemesterSequenceNumbers($semesters);
        $courses = Course::orderBy('course_name', 'asc')->get();
        return view('courses_&_modules.semester_index', compact('semesters', 'courses'));
    }

    public function create()
    {
        $courses = Course::all();
        $intakes = Intake::all();
        $modules = Module::all();
        return view('courses_&_modules.semester_creation', compact('courses', 'intakes', 'modules'));
    }

    public function edit(Semester $semester)
    {
        $semester->load(['course', 'intake', 'modules']);

        $location = optional($semester->intake)->location;
        $courses = Course::query()
            ->when($location, fn ($query) => $query->where('location', $location))
            ->whereIn('course_type', ['degree', 'diploma'])
            ->orderBy('course_name')
            ->get();

        $intakes = Intake::query()
            ->when($location, fn ($query) => $query->where('location', $location))
            ->where(function ($query) use ($semester) {
                $query->where('course_id', $semester->course_id)
                    ->orWhere(function ($fallback) use ($semester) {
                        $fallback->whereNull('course_id')
                            ->where('course_name', optional($semester->course)->course_name);
                    });
            })
            ->orderBy('batch')
            ->get();

        $semesterModules = DB::table('semester_module')
            ->where('semester_id', $semester->id)
            ->get();

        $semesterNumber = $semester->resolvedSlotNumber();
        $semesterLabel = $this->formatSemesterLabel(
            $semesterNumber,
            optional($semester->course)->semester_format ?? 'numerical'
        );

        return view('courses_&_modules.semester_edit', compact(
            'semester',
            'courses',
            'intakes',
            'semesterModules',
            'semesterNumber',
            'semesterLabel'
        ));
    }

    public function update(Request $request, Semester $semester)
    {
        \Log::info('Semester update request data:', $request->all());

        try {
            // Handle JSON requests
            if ($request->isJson()) {
                $data = $request->json()->all();
                $request->merge($data);
            }

            // Map the form field 'semester' to 'name' for the database
            if ($request->has('semester')) {
                $request->merge(['name' => (string) $request->semester]);
            }

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('semesters', 'name')
                        ->ignore($semester->id)
                        ->where(function ($query) use ($request) {
                            return $query
                                ->where('course_id', $request->input('course_id'))
                                ->where('intake_id', $request->input('intake_id'));
                        }),
                ],
                'course_id' => 'required|exists:courses,course_id',
                'intake_id' => 'required|exists:intakes,intake_id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'modules' => 'required|array|min:1',
                'modules.*.module_id' => 'required|exists:modules,module_id',
                'modules.*.specialization' => 'nullable|string|max:255',
                'modules.*.specializations' => 'nullable|array',
                'modules.*.specializations.*' => 'string|max:255',
            ]);

            // Only keep fillable fields for the Semester model
            $semesterData = collect($validated)->only([
                'name', 'course_id', 'intake_id', 'start_date', 'end_date'
            ])->toArray();

            // Determine status based on dates
            $today = now()->toDateString();
            if ($semesterData['start_date'] > $today) {
                $status = 'upcoming';
            } elseif ($semesterData['start_date'] <= $today && $semesterData['end_date'] >= $today) {
                $status = 'active';
            } else {
                $status = 'completed';
            }
            $semesterData['status'] = $status;

            \Log::info('Final semester update data:', $semesterData);

            // Update the semester
            $semester->update($semesterData);

            \Log::info('Semester updated successfully:', ['semester_id' => $semester->id]);

            $this->syncSemesterModules($semester->id, $request->input('modules', []));

            return response()->json([
                'success' => true,
                'message' => 'Semester updated successfully.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error updating semester:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the semester.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Semester $semester)
    {
        try {
            // Delete associated modules first
            \DB::table('semester_module')->where('semester_id', $semester->id)->delete();

            // Delete the semester
            $semester->delete();

            return response()->json([
                'success' => true,
                'message' => 'Semester deleted successfully.'
            ]);
        } catch (\Exception $e) {
            \Log::error('Error deleting semester:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the semester.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        // Debug: Log the incoming request data
        \Log::info('Semester creation request data:', $request->all());

        try {
            // Handle JSON requests
            if ($request->isJson()) {
                $data = $request->json()->all();
                $request->merge($data);
            }

            // Map the form field 'semester' to 'name' for the database
            if ($request->has('semester')) {
                $request->merge(['name' => (string) $request->semester]);
            }

            $validated = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('semesters', 'name')->where(function ($query) use ($request) {
                        return $query
                            ->where('course_id', $request->input('course_id'))
                            ->where('intake_id', $request->input('intake_id'));
                    }),
                ],
                'course_id' => 'required|exists:courses,course_id',
                'intake_id' => 'required|exists:intakes,intake_id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'modules' => 'required|array',
                'modules.*.module_id' => 'required|exists:modules,module_id',
                'modules.*.specialization' => 'nullable|string|max:255',
                'modules.*.specializations' => 'nullable|array',
                'modules.*.specializations.*' => 'string|max:255',
            ]);


            // Only keep fillable fields for the Semester model
            $semesterData = collect($validated)->only([
                'name', 'course_id', 'intake_id', 'start_date', 'end_date'
            ])->toArray();

            // Determine status based on dates
            $today = now()->toDateString();
            if ($semesterData['start_date'] > $today) {
                $status = 'upcoming';
            } elseif ($semesterData['start_date'] <= $today && $semesterData['end_date'] >= $today) {
                $status = 'active';
            } else {
                $status = 'completed';
            }
            $semesterData['status'] = $status;

            \Log::info('Final semester data:', $semesterData);

            // Create the semester
            $semester = Semester::create($semesterData);

            \Log::info('Semester created successfully:', ['semester_id' => $semester->id]);

            // Handle modules if present - save to semester_module table
            $modules = $request->input('modules', []);
            if (!empty($modules) && is_array($modules)) {
                $this->syncSemesterModules($semester->id, $modules);
            }

            return response()->json([
                'success' => true,
                'message' => 'Semester created successfully.'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed:', $e->errors());
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error creating semester:', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the semester.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getFilteredModules(Request $request)
    {
        $creating = $request->boolean('creating');

        $request->validate([
            'course_id'  => 'required|exists:courses,course_id',
            'location'   => 'required|string',
            'intake_id'  => 'required|exists:intakes,intake_id',
            'semester'   => $creating
                ? 'required|integer|min:1'
                : 'required|integer|exists:semesters,id',
        ]);

        $courseId   = (int) $request->course_id;
        $intakeId   = (int) $request->intake_id;
        $semesterId = (int) $request->semester;

        try {
            $course = Course::find($courseId);
            $intake = Intake::find($intakeId);

            if (!$course || !$intake) {
                return response()->json([
                    'success' => false,
                    'message' => 'Course or Intake not found.',
                    'modules' => []
                ], 422);
            }

            $belongsToCourse = ($intake->course_id == $course->course_id)
                || (is_null($intake->course_id) && $intake->course_name === $course->course_name);

            if (!$belongsToCourse || $intake->location !== $request->location) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected intake does not belong to the chosen course or location.',
                    'modules' => []
                ], 422);
            }

            if ($creating) {
                $maxSemesters = (int) ($course->no_of_semesters ?? 0);
                if ($maxSemesters > 0 && $semesterId > $maxSemesters) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The selected semester number is not valid for this course.',
                        'modules' => [],
                    ], 422);
                }

                $modules = $this->queryModulesForSemesterNumber($course, $intake, $semesterId);
            } else {
                $semester = Semester::where('id', $semesterId)
                    ->where('course_id', $courseId)
                    ->where('intake_id', $intakeId)
                    ->first();

                if (!$semester) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The selected semester does not belong to the specified course and intake.',
                        'modules' => [],
                    ], 422);
                }

                $modules = $this->queryModulesForExistingSemester($course, $intake, $semester);
            }

            $formattedModules = $modules->map(function ($module) {
                return [
                    'module_id'   => $module->module_id,
                    'module_name' => $module->module_name,
                    'module_code' => $module->module_code,
                    'module_type' => $module->module_type,
                    'credits'     => $module->credits,
                ];
            });

            return response()->json(['modules' => $formattedModules]);

        } catch (\Exception $e) {
            \Log::error('Error fetching filtered modules: ' . $e->getMessage());
            return response()->json(['modules' => []]);
        }
    }



    public function getCoursesByLocation(Request $request)
    {
        $location = $request->query('location');

        if (!$location) {
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'Location is required.'
            ]);
        }

        $courses = \App\Models\Course::select('course_id', 'course_name')
            ->where('location', $location)
            ->whereIn('course_type', ['degree', 'diploma'])
            ->orderBy('course_name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'message' => $courses->isEmpty() ? 'No courses found.' : 'Courses loaded successfully.'
        ]);
    }

    public function getIntakesForCourseAndLocation($courseId, $location)
    {
        try {
            $course = Course::select('course_id', 'course_name')->find($courseId);

            if (!$course) {
                return response()->json([
                    'success' => false,
                    'intakes' => [],
                    'message' => 'Course not found.'
                ], 404);
            }

            $intakes = Intake::query()
                ->where('location', trim($location))
                ->where(function ($query) use ($course) {
                    $query->where('course_id', $course->course_id)
                        // Fallback keeps old records (created before course_id linkage) working.
                        ->orWhere(function ($fallback) use ($course) {
                            $fallback->whereNull('course_id')
                                ->where('course_name', $course->course_name);
                        });
                })
                ->orderBy('batch', 'asc')
                ->get(['intake_id', 'batch']);

            return response()->json([
                'success' => true,
                'intakes' => $intakes
            ]);
        } catch (\Exception $e) {
            \Log::error('Error fetching intakes for semester creation:', [
                'course_id' => $courseId,
                'location' => $location,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'intakes' => [],
                'message' => 'Failed to load intakes.'
            ], 500);
        }
    }

    public function bulkUpdateStatus(Request $request)
    {
        try {
            $request->validate([
                'semester_ids' => 'required|array',
                'semester_ids.*' => 'exists:semesters,id',
                'status' => 'required|in:upcoming,active,completed'
            ]);

            $semesterIds = $request->semester_ids;
            $status = $request->status;

            // Update semesters
            Semester::whereIn('id', $semesterIds)->update(array_merge(
                ['status' => $status],
                \App\Support\UserTrackingData::forUpdate()
            ));

            $updatedCount = count($semesterIds);

            return response()->json([
                'success' => true,
                'message' => "Successfully updated status for {$updatedCount} semester(s)."
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in bulk status update:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating semester statuses.'
            ], 500);
        }
    }

    public function bulkDelete(Request $request)
    {
        try {
            $request->validate([
                'semester_ids' => 'required|array',
                'semester_ids.*' => 'exists:semesters,id'
            ]);

            $semesterIds = $request->semester_ids;

            // Delete associated modules first
            \DB::table('semester_module')->whereIn('semester_id', $semesterIds)->delete();

            // Delete semesters
            Semester::whereIn('id', $semesterIds)->delete();

            $deletedCount = count($semesterIds);

            return response()->json([
                'success' => true,
                'message' => "Successfully deleted {$deletedCount} semester(s)."
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in bulk delete:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting semesters.'
            ], 500);
        }
    }

    public function duplicateSemester(Request $request, Semester $semester)
    {
        try {
            $request->validate([
                'new_name' => 'required|string|max:255',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
            ]);

            // Create new semester
            $newSemester = $semester->replicate();
            $newSemester->name = $request->new_name;
            $newSemester->start_date = $request->start_date;
            $newSemester->end_date = $request->end_date;

            // Determine status based on dates
            $today = now()->toDateString();
            if ($newSemester->start_date > $today) {
                $newSemester->status = 'upcoming';
            } elseif ($newSemester->start_date <= $today && $newSemester->end_date >= $today) {
                $newSemester->status = 'active';
            } else {
                $newSemester->status = 'completed';
            }

            $newSemester->save();

            // Copy modules
            $semesterModules = \DB::table('semester_module')
                ->where('semester_id', $semester->id)
                ->get();

            foreach ($semesterModules as $module) {
                \DB::table('semester_module')->insert([
                    'semester_id' => $newSemester->id,
                    'module_id' => $module->module_id,
                    'specialization' => $module->specialization,
                    'specializations' => $module->specializations ?? null,
                    ...\App\Support\UserTrackingData::forCreate(),
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Semester duplicated successfully.',
                'semester_id' => $newSemester->id
            ]);
        } catch (\Exception $e) {
            \Log::error('Error duplicating semester:', ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while duplicating the semester.'
            ], 500);
        }
    }

    private function queryModulesForExistingSemester(Course $course, Intake $intake, Semester $semester)
    {
        if ($course->course_type === 'certificate') {
            return $this->queryIntakeModules((int) $intake->intake_id);
        }

        $assigned = $this->querySemesterAssignedModules((int) $semester->id);
        $fromCourse = $this->queryCourseModules(
            (int) $course->course_id,
            $semester->resolvedSlotNumber()
        );

        return $assigned
            ->concat($fromCourse)
            ->unique('module_id')
            ->sortBy('module_name')
            ->values();
    }

    private function queryModulesForSemesterNumber(Course $course, Intake $intake, int $semesterNumber)
    {
        if ($course->course_type === 'certificate') {
            return $this->queryIntakeModules((int) $intake->intake_id);
        }

        $fromCourse = $this->queryCourseModules((int) $course->course_id, $semesterNumber);
        if ($fromCourse->isNotEmpty()) {
            return $fromCourse;
        }

        // Course Management never writes course_modules, so fall back to the
        // degree/diploma catalog the user can actually pick from.
        return $this->queryAvailableCatalogModules($course);
    }

    private function queryIntakeModules(int $intakeId)
    {
        return DB::table('modules')
            ->join('intake_modules', 'modules.module_id', '=', 'intake_modules.module_id')
            ->where('intake_modules.intake_id', $intakeId)
            ->select(
                'modules.module_id',
                'modules.module_name',
                'modules.module_code',
                'modules.module_type',
                'modules.credits'
            )
            ->orderBy('modules.module_name')
            ->distinct()
            ->get();
    }

    private function querySemesterAssignedModules(int $semesterId)
    {
        return DB::table('modules')
            ->join('semester_module', 'modules.module_id', '=', 'semester_module.module_id')
            ->where('semester_module.semester_id', $semesterId)
            ->select(
                'modules.module_id',
                'modules.module_name',
                'modules.module_code',
                'modules.module_type',
                'modules.credits'
            )
            ->orderBy('modules.module_name')
            ->distinct()
            ->get();
    }

    private function queryCourseModules(int $courseId, int $semesterNumber)
    {
        return DB::table('modules')
            ->join('course_modules', 'modules.module_id', '=', 'course_modules.module_id')
            ->where('course_modules.course_id', $courseId)
            ->where('course_modules.semester', $semesterNumber)
            ->select(
                'modules.module_id',
                'modules.module_name',
                'modules.module_code',
                'modules.module_type',
                'modules.credits'
            )
            ->orderBy('modules.module_name')
            ->distinct()
            ->get();
    }

    private function queryAvailableCatalogModules(Course $course)
    {
        $query = DB::table('modules')
            ->select(
                'modules.module_id',
                'modules.module_name',
                'modules.module_code',
                'modules.module_type',
                'modules.credits'
            )
            ->orderBy('modules.module_name');

        if ($course->course_type === 'certificate') {
            return $query->where('modules.module_category', 'certificate')->get();
        }

        return $query
            ->where(function ($builder) {
                $builder->whereNull('modules.module_category')
                    ->orWhere('modules.module_category', '!=', 'certificate');
            })
            ->get();
    }

    private function assignSemesterSequenceNumbers($semesters): void
    {
        $semesters
            ->groupBy(fn (Semester $semester) => $semester->course_id . '-' . $semester->intake_id)
            ->each(function ($group) {
                $ordered = $group->sortBy([
                    fn (Semester $semester) => optional($semester->start_date)->timestamp ?? 0,
                    fn (Semester $semester) => $semester->id,
                ])->values();

                foreach ($ordered as $index => $semester) {
                    $semester->setAttribute('sequence_number', $index + 1);
                }
            });
    }

    private function formatSemesterLabel(int $number, ?string $format): string
    {
        if ($format === 'alphabetical' && $number >= 1 && $number <= 26) {
            return 'Semester ' . chr(64 + $number);
        }

        return 'Semester ' . $number;
    }

    private function syncSemesterModules(int $semesterId, array $modules): void
    {
        $desiredModuleIds = [];

        foreach ($modules as $module) {
            if (!isset($module['module_id'])) {
                continue;
            }

            $normalized = SemesterModuleSpecializationHelper::normalizePayload($module);
            if ($normalized['module_id'] === '') {
                continue;
            }

            $desiredModuleIds[] = $normalized['module_id'];

            DB::table('semester_module')->updateOrInsert(
                [
                    'semester_id' => $semesterId,
                    'module_id' => $normalized['module_id'],
                ],
                [
                    'specializations' => $normalized['specializations'],
                    'specialization' => $normalized['specialization'],
                    ...\App\Support\UserTrackingData::forUpdate(),
                ]
            );
        }

        if (empty($desiredModuleIds)) {
            DB::table('semester_module')->where('semester_id', $semesterId)->delete();
            return;
        }

        DB::table('semester_module')
            ->where('semester_id', $semesterId)
            ->whereNotIn('module_id', $desiredModuleIds)
            ->delete();
    }
}
