<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\SpecializationRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class SpecializationRegistrationController extends Controller
{
    private function courseSpecializations(Course $course): array
    {
        $specializations = $course->specializations;
        for ($i = 0; $i < 2 && is_string($specializations); $i++) {
            $specializations = json_decode($specializations, true);
        }

        return is_array($specializations)
            ? array_values(array_filter(array_map(function ($value) {
                if (is_string($value)) {
                    $trimmed = trim($value);
                    return $trimmed === '' ? null : $trimmed;
                }
                if (is_array($value)) {
                    $label = trim((string) ($value['name'] ?? $value['title'] ?? $value['specialization'] ?? ''));
                    return $label === '' ? null : $label;
                }
                return null;
            }, $specializations)))
            : [];
    }

    public function index()
    {
        return view('registration.specialization_registration', [
            'locations' => ['Welisara', 'Moratuwa', 'Peradeniya'],
        ]);
    }

    public function courses(Request $request)
    {
        $data = $request->validate([
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
        ]);

        $courses = Course::where('location', $data['location'])
            ->whereIn('course_type', ['degree', 'diploma'])
            ->orderBy('course_name')
            ->get(['course_id', 'course_name', 'specializations']);

        return response()->json([
            'success' => true,
            'courses' => $courses,
        ]);
    }

    public function intakes(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
        ]);

        $course = Course::find($data['course_id']);
        if (!$course) {
            return response()->json(['success' => false, 'intakes' => [], 'message' => 'Course not found.']);
        }

        return response()->json([
            'success' => true,
            'intakes' => Intake::forCourse($course, $data['location'])
                ->orderBy('batch')
                ->get(['intake_id', 'batch']),
        ]);
    }

    public function students(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
        ]);

        $registrations = CourseRegistration::query()
            ->where($data)
            ->eligible()
            ->with('student')
            ->get();

        $assignmentsByStudentId = collect();
        if (Schema::hasTable('specialization_registrations')) {
            $assignmentsByStudentId = SpecializationRegistration::query()
                ->where('course_id', $data['course_id'])
                ->where('intake_id', $data['intake_id'])
                ->where('location', $data['location'])
                ->where('status', 'registered')
                ->whereIn('student_id', $registrations->pluck('student_id')->all())
                ->get()
                ->keyBy('student_id');
        }

        $students = $registrations->map(function ($registration) use ($assignmentsByStudentId) {
                $student = $registration->student;
                if (!$student) {
                    return null;
                }

                $assignment = $assignmentsByStudentId->get($registration->student_id);

                return [
                    'student_id' => $registration->student_id,
                    'course_registration_id' => $registration->course_registration_id,
                    'name' => $student->name_with_initials,
                    'email' => $student->email,
                    'nic' => $student->id_value ?? $student->nic ?? null,
                    'specialization' => $assignment?->status === 'registered' ? $assignment->specialization : null,
                ];
            })
            ->filter()
            ->unique('student_id')
            ->values();

        return response()->json(['success' => true, 'students' => $students]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'specialization' => 'required|string|max:255',
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:students,student_id',
        ]);

        $course = Course::find($data['course_id']);
        if (!$course || !in_array($course->course_type, ['degree', 'diploma'], true)) {
            return response()->json([
                'success' => false,
                'message' => 'Specialization registration is only for degree and diploma courses.',
            ], 422);
        }
        if (!in_array($data['specialization'], $this->courseSpecializations($course), true)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid specialization for this course.',
            ], 422);
        }
        if (!Schema::hasTable('specialization_registrations')) {
            return response()->json([
                'success' => false,
                'message' => 'Specialization assignments table is missing. Please run pending migrations.',
            ], 422);
        }

        $eligibleIds = CourseRegistration::where('course_id', $data['course_id'])->where('intake_id', $data['intake_id'])
            ->where('location', $data['location'])->whereIn('student_id', $data['student_ids'])
            ->eligible()
            ->pluck('student_id')->all();

        foreach ($eligibleIds as $studentId) {
            SpecializationRegistration::updateOrCreate(
                ['student_id' => $studentId, 'course_id' => $data['course_id'], 'intake_id' => $data['intake_id']],
                ['location' => $data['location'], 'specialization' => $data['specialization'], 'status' => 'registered']
            );
        }

        return response()->json(['success' => true, 'message' => count($eligibleIds) . ' student(s) registered for the specialization.']);
    }
}
