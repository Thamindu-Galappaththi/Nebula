<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\Course;
use App\Models\Intake;
use App\Exports\StudentListExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class StudentListController extends Controller
{
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

    private function specializationAssignments(int $courseId, int $intakeId, string $location)
    {
        if (!Schema::hasTable('specialization_registrations')) {
            return collect();
        }

        $rows = DB::table('specialization_registrations')
            ->where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->where('status', 'registered')
            ->whereNotNull('specialization')
            ->where('specialization', '<>', '')
            ->orderByDesc('id')
            ->get(['student_id', 'location', 'specialization']);

        if ($rows->isEmpty()) {
            return collect();
        }

        $located = $rows->filter(function ($row) use ($location) {
            return strcasecmp(trim((string) $row->location), trim($location)) === 0;
        });

        return ($located->isNotEmpty() ? $located : $rows)
            ->unique('student_id')
            ->mapWithKeys(function ($row) {
                return [$row->student_id => trim((string) $row->specialization)];
            });
    }

    private function applySpecializationScope($query, ?string $specialization, $assignments)
    {
        if ($specialization === null) {
            return $query;
        }

        $isCommon = strcasecmp($specialization, 'Common') === 0;

        if ($isCommon) {
            $assignedIds = $assignments->keys()->all();
            if (empty($assignedIds)) {
                return $query;
            }

            return $query->whereNotIn('cr.student_id', $assignedIds);
        }

        $matchingIds = $assignments
            ->filter(function ($assigned) use ($specialization) {
                return strcasecmp((string) $assigned, $specialization) === 0;
            })
            ->keys()
            ->all();

        if (empty($matchingIds)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn('cr.student_id', $matchingIds);
    }

    private function statusSelectSql(): string
    {
        return 'CASE
            WHEN LOWER(cr.status) = "registered" THEN "registered"
            WHEN LOWER(cr.status) IN ("not eligible", "terminated") THEN "terminated"
            WHEN LOWER(cr.status) = "completed" THEN "completed"
            ELSE "pending"
        END';
    }

    private function applyStatusFilter($query, string $status)
    {
        if ($status === 'all') {
            return $query;
        }

        $statusMap = [
            'registered' => ['Registered', 'registered'],
            'terminated' => ['Not eligible', 'not eligible', 'Terminated', 'terminated'],
            'completed' => ['Completed', 'completed'],
            'pending' => ['Pending', 'pending', 'Special approval required'],
        ];

        if (isset($statusMap[$status])) {
            $query->whereIn('cr.status', $statusMap[$status]);
        }

        return $query;
    }

    private function fetchStudentsForFilters(string $location, int $courseId, int $intakeId, ?string $specialization, string $status = 'all')
    {
        $assignments = $this->specializationAssignments($courseId, $intakeId, $location);

        $query = DB::table('course_registration as cr')
            ->join('students as s', 's.student_id', '=', 'cr.student_id')
            ->where('cr.location', $location)
            ->where('cr.course_id', $courseId)
            ->where('cr.intake_id', $intakeId);

        $this->applySpecializationScope($query, $specialization, $assignments);
        $this->applyStatusFilter($query, $status);

        $students = $query->select([
                'cr.course_registration_id',
                's.student_id',
                DB::raw('COALESCE(s.name_with_initials, s.full_name) as name'),
                DB::raw('"" as specialization'),
                DB::raw($this->statusSelectSql() . ' as status'),
            ])
            ->orderBy('cr.course_registration_id')
            ->orderBy('s.name_with_initials')
            ->get();

        return $students->map(function ($student) use ($assignments) {
            $student->specialization = $assignments->get($student->student_id, '');

            return $student;
        });
    }

    public function showStudentList()
    {
        $locations = ['Welisara', 'Moratuwa', 'Peradeniya'];
        return view('student_management.student_list', compact('locations'));
    }

    /**
     * Fetch students joined with course_registration.
     */
    public function getStudentListData(Request $request)
    {
        $request->validate([
            'location'  => 'required|string',
            'course_id' => 'required|integer|exists:courses,course_id',
            'intake_id' => 'required|integer|exists:intakes,intake_id',
            'specialization' => 'nullable|string',
        ]);

        $students = $this->fetchStudentsForFilters(
            $request->location,
            (int) $request->course_id,
            (int) $request->intake_id,
            $this->normalizeSpecializationValue($request->specialization)
        );

        return response()->json([
            'success'  => true,
            'students' => $students,
        ]);
    }

    /**
     * Download student list as PDF.
     */
    public function downloadStudentList(Request $request)
    {
        $request->validate([
            'location'  => 'required|string',
            'course_id' => 'required|integer|exists:courses,course_id',
            'intake_id' => 'required|integer|exists:intakes,intake_id',
            'specialization' => 'nullable|string',
            'status'    => 'nullable|string|in:all,registered,terminated,completed,pending',
        ]);

        $location  = $request->location;
        $course_id = (int) $request->course_id;
        $intake_id = (int) $request->intake_id;
        $specialization = $this->normalizeSpecializationValue($request->specialization);
        $status    = $request->input('status', 'all');

        $students = $this->fetchStudentsForFilters($location, $course_id, $intake_id, $specialization, $status);

        $course = Course::find($course_id);
        $intake = Intake::find($intake_id);

        $data = [
            'students'     => $students,
            'locationText' => 'Nebula Institute of Technology - ' . $location,
            'courseText'   => $course?->course_name ?? 'N/A',
            'intakeText'   => $intake?->batch ?? 'N/A',
            'total_count'  => $students->count(),
            'status'       => $status,
            'specializationText' => $specialization ?? 'All',
        ];

        $pdf = Pdf::loadView('student_management.student_list_pdf', $data);
        return $pdf->download('student_list_' . $status . '_' . now()->format('Y-m-d_His') . '.pdf');
    }

    /**
     * Download as Excel file.
     */
    public function downloadStudentListExcel(Request $request)
    {
        $request->validate([
            'location'  => 'required|string',
            'course_id' => 'required|integer|exists:courses,course_id',
            'intake_id' => 'required|integer|exists:intakes,intake_id',
            'specialization' => 'nullable|string',
            'status'    => 'nullable|string|in:all,registered,terminated,completed,pending',
        ]);

        $location  = $request->location;
        $course_id = (int) $request->course_id;
        $intake_id = (int) $request->intake_id;
        $specialization = $this->normalizeSpecializationValue($request->specialization);
        $status    = $request->input('status', 'all');

        $students = $this->fetchStudentsForFilters($location, $course_id, $intake_id, $specialization, $status);

        $course = Course::find($course_id);
        $intake = Intake::find($intake_id);

        // Excel
        $excelData = [];
        $counter = 1;
        foreach ($students as $s) {
            $excelData[] = [
                $counter++,
                $s->course_registration_id,
                $s->student_id,
                $s->name,
                $s->specialization ?: '-',
                ($s->status === 'terminated') ? 'Not Eligible' : ucfirst($s->status)
            ];
        }

        $filename = 'student_list_' . strtolower($status) . '_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(
            new StudentListExport($excelData, $course?->course_name ?? 'N/A', $location, $intake?->batch ?? 'N/A', $status),
            $filename
        );
    }

    /**
     * Get intakes for a course and location
     */
    public function getIntakesForCourseAndLocation($courseId, $location)
    {
        try {
            $course = Course::find($courseId);
            if (!$course) {
                return response()->json(['intakes' => []]);
            }

            $intakes = Intake::forCourse($course, $location)
                ->orderBy('batch')
                ->get(['intake_id', 'batch']);

            return response()->json(['intakes' => $intakes]);
        } catch (\Exception $e) {
            \Log::error('Error fetching intakes in StudentListController: ' . $e->getMessage());
            return response()->json(['intakes' => []], 500);
        }
    }

    public function exportStudentList(Request $request)
    {
        return $this->downloadStudentListExcel($request);
    }

    public function filterStudents(Request $request)
    {
        return $this->getStudentListData($request);
    }

    public function downloadTemplate(Request $request)
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_list_template.csv"',
        ];

        $callback = function () {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Student ID', 'Full Name', 'Name with Initials', 'Location', 'Course ID', 'Intake ID', 'Status']);
            fputcsv($file, ['ST0001', 'John Doe', 'J. Doe', 'Welisara', '1', '1', 'registered']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function importStudentList(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,txt|max:10240',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Student list processed successfully.',
        ]);
    }

    public function downloadExcel(Request $request)
    {
        return $this->downloadStudentListExcel($request);
    }

    public function checkBlacklistStatus(Request $request)
    {
        $studentId = $request->input('student_id') ?? $request->input('id_value') ?? $request->input('nic');

        if (!$studentId) {
            return response()->json([
                'success' => false,
                'blacklisted' => false,
                'message' => 'Student identifier is required.'
            ], 400);
        }

        $student = DB::table('students')
            ->where('student_id', $studentId)
            ->orWhere('id_value', $studentId)
            ->first();

        if (!$student) {
            return response()->json([
                'success' => true,
                'blacklisted' => false,
                'message' => 'Student not found.'
            ]);
        }

        $isBlacklisted = DB::table('course_registration')
            ->where('student_id', $student->student_id)
            ->whereIn('status', ['Not eligible', 'not eligible', 'Terminated', 'terminated'])
            ->exists();

        return response()->json([
            'success' => true,
            'blacklisted' => $isBlacklisted,
            'student' => $student,
            'message' => $isBlacklisted ? 'Student is marked as not eligible / terminated.' : 'Student is eligible.'
        ]);
    }
}
