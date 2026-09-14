<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Student;
use App\Models\Course;
use App\Models\Intake;
use App\Exports\StudentViewExport;
use App\Support\SpecializationStudentScope;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class StudentViewController extends Controller
{
    private const EXPORT_COLUMNS = [
        'student' => 'Student',
        'nic' => 'NIC',
        'course' => 'Course',
        'intake' => 'Intake',
        'specialization' => 'Specialization',
        'location' => 'Location',
        'status' => 'Status',
    ];

    private function normalizeSpecializationValue($value)
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' || strtolower($value) === 'all' ? null : $value;
    }

    private function selectedExportColumns(Request $request): array
    {
        $requested = $request->input('columns', array_keys(self::EXPORT_COLUMNS));
        if (is_string($requested)) {
            $requested = array_filter(array_map('trim', explode(',', $requested)));
        }

        $columns = array_values(array_intersect(array_keys(self::EXPORT_COLUMNS), (array) $requested));

        return $columns ?: array_keys(self::EXPORT_COLUMNS);
    }

    private function applySpecializationScope($query, ?string $specialization, $selectedCourseId, $selectedIntakeId): void
    {
        if (!$specialization || !$selectedCourseId) {
            return;
        }

        $courseId = (int) $selectedCourseId;
        $intakeId = $selectedIntakeId ? (int) $selectedIntakeId : null;

        $studentIds = SpecializationStudentScope::resolveStudentIds(
            $courseId,
            $intakeId,
            null,
            $specialization
        );

        if (!empty($studentIds)) {
            $query->whereIn('student_id', $studentIds);
            return;
        }

        $hasSpecializationRegistrations = Schema::hasTable('specialization_registrations')
            && DB::table('specialization_registrations')
                ->where('course_id', $courseId)
                ->when($intakeId, fn ($q) => $q->where('intake_id', $intakeId))
                ->exists();

        if ($hasSpecializationRegistrations) {
            $query->whereRaw('1 = 0');
        }
    }

    private function attachSpecializations($students): void
    {
        if ($students->isEmpty() || !Schema::hasTable('specialization_registrations')) {
            return;
        }

        $rows = DB::table('specialization_registrations')
            ->whereIn('student_id', $students->pluck('student_id')->unique()->all())
            ->where('status', 'registered')
            ->orderByDesc('id')
            ->get(['student_id', 'course_id', 'intake_id', 'specialization']);

        $byCohort = [];
        $byStudent = [];
        foreach ($rows as $row) {
            $key = $row->student_id . ':' . $row->course_id . ':' . $row->intake_id;
            if (!isset($byCohort[$key])) {
                $byCohort[$key] = $row->specialization;
            }
            if (!isset($byStudent[$row->student_id])) {
                $byStudent[$row->student_id] = $row->specialization;
            }
        }

        foreach ($students as $student) {
            $reg = $student->courseRegistrations->first();
            $spec = null;
            if ($reg) {
                $spec = $byCohort[$student->student_id . ':' . $reg->course_id . ':' . $reg->intake_id] ?? null;
            }
            $spec = $spec ?: ($byStudent[$student->student_id] ?? null);
            if ($spec) {
                $student->setAttribute('specialization', $spec);
            }
        }
    }

    private function mapStudentRow(Student $student): array
    {
        $reg = $student->courseRegistrations->first();
        $specialization = $student->getAttribute('specialization') ?: '-';
        $courseName = $reg?->course?->course_name ?? '-';
        $intakeBatch = $reg?->intake?->batch ?? '-';

        return [
            'student_id' => $student->student_id,
            'full_name' => $student->full_name ?: ($student->name_with_initials ?: '-'),
            'id_value' => $student->id_value ?: '-',
            'course' => $courseName,
            'intake' => $intakeBatch,
            'specialization' => $specialization,
            'location' => $reg?->location ?: ($student->institute_location ?: '-'),
            'academic_status' => $student->academic_status ?: '-',
            'course_registrations' => $reg ? [[
                'course' => ['course_name' => $courseName],
                'intake' => ['batch' => $intakeBatch],
                'specialization' => $specialization,
                'location' => $reg->location,
            ]] : [],
        ];
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }

    private function studentQuery(Request $request)
    {
        $selectedCourseId = $request->input('course_id');
        $selectedIntakeId = $request->input('intake_id');
        $specialization = $this->normalizeSpecializationValue($request->input('specialization'));

        $query = Student::query()
            ->with(['courseRegistrations' => function ($registrationQuery) use ($selectedCourseId, $selectedIntakeId) {
                if ($selectedCourseId) {
                    $registrationQuery->where('course_id', $selectedCourseId);
                }

                if ($selectedIntakeId) {
                    $registrationQuery->where('intake_id', $selectedIntakeId);
                }

                $registrationQuery->with(['course', 'intake'])->orderByDesc('id');
            }]);

        if ($request->filled('student_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('student_id', $request->student_id)
                  ->orWhere('id_value', $request->student_id);
            });
        }

        if ($request->filled('course_id') || $request->filled('intake_id')) {
            $query->whereHas('courseRegistrations', function ($q) use ($request) {
                if ($request->filled('course_id')) {
                    $q->where('course_id', $request->course_id);
                }
                if ($request->filled('intake_id')) {
                    $q->where('intake_id', $request->intake_id);
                }
            });
        }

        $this->applySpecializationScope($query, $specialization, $selectedCourseId, $selectedIntakeId);

        if ($request->filled('status')) {
            $query->where('academic_status', $request->status);
        }

        if ($request->filled('location')) {
            $query->where('institute_location', $request->location);
        }

        return $query->orderBy('student_id', 'asc');
    }

    private function decorateStudents($students, Request $request): void
    {
        $specialization = $this->normalizeSpecializationValue($request->input('specialization'));
        $this->attachSpecializations($students);

        if ($specialization) {
            $students->each(function ($student) use ($specialization) {
                if (trim((string) ($student->getAttribute('specialization') ?? '')) === '') {
                    $student->setAttribute('specialization', $specialization);
                }
            });
        }
    }

    private function fetchStudents(Request $request)
    {
        $students = $this->studentQuery($request)->get();
        $this->decorateStudents($students, $request);

        return $students;
    }

    private function exportRows($students, array $columns): array
    {
        $rows = [];
        $counter = 1;

        foreach ($students as $student) {
            $mapped = $this->mapStudentRow($student);
            $row = [$counter++];
            foreach ($columns as $column) {
                $row[] = match ($column) {
                    'student' => $mapped['full_name'],
                    'nic' => $mapped['id_value'],
                    'course' => $mapped['course'],
                    'intake' => $mapped['intake'],
                    'specialization' => $mapped['specialization'],
                    'location' => $mapped['location'],
                    'status' => ucfirst((string) $mapped['academic_status']),
                    default => '-',
                };
            }
            $rows[] = $row;
        }

        return $rows;
    }

    private function exportMeta(Request $request): array
    {
        $course = $request->filled('course_id') ? Course::find($request->course_id) : null;
        $intake = $request->filled('intake_id') ? Intake::find($request->intake_id) : null;
        $specialization = $this->normalizeSpecializationValue($request->input('specialization'));

        return [
            'studentId' => $request->input('student_id') ?: 'All',
            'courseText' => $course?->course_name ?? 'All Courses',
            'intakeText' => $intake?->batch ?? 'All Intakes',
            'specializationText' => $specialization ?? 'All',
            'statusText' => $request->filled('status') ? ucfirst((string) $request->status) : 'All',
        ];
    }

    public function index()
    {
        $courses = Course::orderBy('course_name')->orderBy('location')->get(['course_id', 'course_name', 'location']);

        return view('student_management.student_view', compact('courses'));
    }

    public function filter(Request $request)
    {
        $perPage = $this->perPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $this->studentQuery($request)->paginate($perPage, ['*'], 'page', $page);
        $students = $paginator->getCollection();
        $this->decorateStudents($students, $request);

        return response()->json([
            'success' => true,
            'data' => $students->map(fn (Student $student) => $this->mapStudentRow($student))->values(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ]);
    }

    public function exportExcel(Request $request)
    {
        $columns = $this->selectedExportColumns($request);
        $students = $this->fetchStudents($request);
        $meta = $this->exportMeta($request);
        $headings = array_merge(['No.'], array_map(fn ($column) => self::EXPORT_COLUMNS[$column], $columns));
        $filename = 'all_students_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(
            new StudentViewExport($this->exportRows($students, $columns), $headings, $meta),
            $filename
        );
    }

    public function exportPdf(Request $request)
    {
        $columns = $this->selectedExportColumns($request);
        $students = $this->fetchStudents($request);
        $mapped = $students->map(fn (Student $student) => $this->mapStudentRow($student))->values();
        $meta = $this->exportMeta($request);

        $pdf = Pdf::loadView('student_management.student_view_pdf', [
            'students' => $mapped,
            'columns' => $columns,
            'columnLabels' => self::EXPORT_COLUMNS,
            'meta' => $meta,
            'total_count' => $mapped->count(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('all_students_' . now()->format('Y-m-d_His') . '.pdf');
    }

    public function getStudentCourses(Request $request)
    {
        $studentId = $request->query('student_id');

        $student = Student::where(function ($q) use ($studentId) {
                $q->where('student_id', $studentId)
                  ->orWhere('id_value', $studentId);
            })
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'Student not found'
            ]);
        }

        $courses = $student->courseRegistrations()
                    ->with('course')
                    ->get()
                    ->pluck('course')
                    ->filter()
                    ->unique('course_id')
                    ->values();

        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }

    public function getCourseIntakes(Request $request)
    {
        $request->validate(['course_id' => 'required|integer|exists:courses,course_id']);

        $course = Course::findOrFail($request->course_id);

        return response()->json([
            'success' => true,
            'intakes' => Intake::forCourse($course, $course->location)
                ->orderBy('batch')
                ->get(['intake_id', 'batch']),
        ]);
    }
}
