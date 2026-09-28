<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\SemesterRegistration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UhIndexController extends Controller
{
    public function showPage()
    {
        return view('registration.uh_index_numbers');
    }

    public function getCoursesByLocation(Request $request)
    {
        $data = $request->validate([
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
        ]);

        $courses = Course::where('location', $data['location'])
            ->orderBy('course_name')
            ->get(['course_id', 'course_name', 'course_type']);

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'message' => $courses->isEmpty() ? 'No courses found for this location.' : null,
        ]);
    }

    public function getIntakesByCourse(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
        ]);

        $course = Course::find($data['course_id']);
        if (!$course) {
            return response()->json(['success' => false, 'intakes' => [], 'message' => 'Course not found.']);
        }

        $intakes = Intake::forCourse($course, $data['location'])
            ->orderBy('batch')
            ->get(['intake_id', 'batch']);

        return response()->json([
            'success' => true,
            'intakes' => $intakes,
        ]);
    }

    public function getStudentsByIntake(Request $request)
    {
        $data = $request->validate([
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
        ]);

        $registrations = CourseRegistration::query()
            ->where('course_id', $data['course_id'])
            ->where('intake_id', $data['intake_id'])
            ->where('location', $data['location'])
            ->eligible()
            ->with('student')
            ->orderByDesc('id')
            ->get();

        $students = $registrations->map(function ($registration) {
            $student = $registration->student;
            if (!$student) {
                return null;
            }

            return [
                'student_id' => (int) $registration->student_id,
                'course_registration_id' => $registration->course_registration_id,
                'name' => $student->name_with_initials ?: $student->full_name,
                'nic' => $student->id_value,
                'uh_index_number' => $registration->uh_index_number,
            ];
        })->filter()->unique('student_id')->values();

        return response()->json([
            'success' => true,
            'students' => $students,
        ]);
    }

    public function saveUhIndexNumbers(Request $request)
    {
        $data = $request->validate([
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'course_id' => 'required|exists:courses,course_id',
            'intake_id' => 'required|exists:intakes,intake_id',
            'students' => 'required|array|min:1',
            'students.*.student_id' => 'required|integer|exists:students,student_id',
            'students.*.uh_index_number' => 'nullable|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $updated = 0;
            $missing = [];

            foreach ($data['students'] as $row) {
                $value = trim((string) ($row['uh_index_number'] ?? ''));
                $reg = CourseRegistration::query()
                    ->where('student_id', $row['student_id'])
                    ->where('course_id', $data['course_id'])
                    ->where('intake_id', $data['intake_id'])
                    ->where('location', $data['location'])
                    ->orderByDesc('id')
                    ->first();

                if (!$reg) {
                    $missing[] = (string) $row['student_id'];
                    continue;
                }

                $reg->update([
                    'uh_index_number' => $value === '' ? null : $value,
                ]);
                $updated++;
            }

            DB::commit();

            if ($missing && $updated === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No matching course registrations were found for the selected intake.',
                    'updated_count' => 0,
                ], 422);
            }

            $message = $updated . ' external institute ID(s) saved.';
            if ($missing) {
                $message .= ' ' . count($missing) . ' student(s) had no matching registration.';
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'updated_count' => $updated,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('saveUhIndexNumbers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error while saving IDs.',
            ], 500);
        }
    }

    public function terminateStudent(Request $request)
    {
        $request->validate([
            'student_id' => 'required|integer',
            'intake_id' => 'required|integer',
        ]);

        try {
            DB::beginTransaction();

            $sr = SemesterRegistration::where('student_id', $request->student_id)
                ->where('intake_id', $request->intake_id)
                ->where('status', 'registered')
                ->lockForUpdate()
                ->first();

            if (!$sr) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Student is not registered for this intake or already terminated.',
                ], 404);
            }

            SemesterRegistration::where('student_id', $request->student_id)
                ->where('intake_id', $request->intake_id)
                ->update([
                    'status' => 'terminated',
                    'desired_status' => null,
                    'approval_status' => 'none',
                    'updated_at' => now(),
                    ...\App\Support\UserTrackingData::forUpdate(),
                ]);

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Student terminated successfully.']);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('terminateStudent error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Failed to terminate student.'], 500);
        }
    }
}
