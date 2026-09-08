<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\CourseRegistration;
use App\Models\Course;
use App\Models\Intake;
use App\Support\SpecializationStudentScope;

class StudentViewController extends Controller
{
    private function normalizeSpecializationValue($value)
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' || strtolower($value) === 'all' ? null : $value;
    }

    public function index()
    {
        $courses = Course::orderBy('course_name')->get();
        $intakes = Intake::orderBy('batch')->get();
        return view('student_management.student_view', compact('courses', 'intakes'));
    }

    public function filter(Request $request)
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

        if ($specialization && $selectedCourseId) {
            SpecializationStudentScope::applyToQuery(
                $query,
                'student_id',
                (int) $selectedCourseId,
                $selectedIntakeId ? (int) $selectedIntakeId : null,
                null,
                $specialization
            );
        }

        if ($request->filled('status')) {
            $query->where('academic_status', $request->status);
        }

        if ($request->filled('location')) {
            $query->where('institute_location', $request->location);
        }

        $students = $query->orderBy('student_id', 'asc')->get();

        if ($specialization) {
            $students->each(function ($student) use ($specialization) {
                if (trim((string) ($student->specialization ?? '')) === '') {
                    $student->setAttribute('specialization', $specialization);
                }
            });
        }

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }

    public function getStudentCourses(Request $request)
    {
        $studentId = $request->query('student_id');

        $student = Student::where('student_id', $studentId)
                    ->orWhere('id_value', $studentId)
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
            'intakes' => Intake::forCourse($course)
                ->orderBy('batch')
                ->get(['intake_id', 'batch']),
        ]);
    }

}
