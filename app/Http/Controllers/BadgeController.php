<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseBadge;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BadgeController extends Controller
{
    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }

    public function index()
    {
        $courses = Course::orderBy('course_name')->orderBy('location')->get(['course_id', 'course_name', 'location']);

        return view('student_management.generate', compact('courses'));
    }

    private function registrationQuery(Request $request)
    {
        $query = CourseRegistration::query()->with(['student', 'course', 'intake']);

        if ($request->filled('student_id')) {
            $studentIds = Student::query()
                ->where(function ($q) use ($request) {
                    $q->where('student_id', $request->student_id)
                      ->orWhere('id_value', $request->student_id);
                })
                ->pluck('student_id');

            if ($studentIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('student_id', $studentIds);
            }
        }

        if ($request->filled('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        if ($request->filled('intake_id')) {
            $query->where('intake_id', $request->intake_id);
        }

        if ($request->filled('mode')) {
            $query->whereHas('intake', function ($q) use ($request) {
                $q->where('intake_mode', $request->mode);
            });
        }

        return $query->orderByDesc('id');
    }

    private function attachBadges($registrations): void
    {
        if ($registrations->isEmpty()) {
            return;
        }

        $studentIds = $registrations->pluck('student_id')->unique()->filter()->all();
        $badges = CourseBadge::query()
            ->whereIn('student_id', $studentIds)
            ->orderByDesc('id')
            ->get();

        $byCohort = [];
        foreach ($badges as $badge) {
            $key = $badge->student_id . ':' . $badge->course_id . ':' . $badge->intake_id;
            if (!isset($byCohort[$key])) {
                $byCohort[$key] = $badge;
            }
        }

        foreach ($registrations as $registration) {
            $key = $registration->student_id . ':' . $registration->course_id . ':' . $registration->intake_id;
            $registration->setRelation('badge', $byCohort[$key] ?? null);
        }
    }

    private function mapRegistrationRow(CourseRegistration $registration): array
    {
        $badge = $registration->getRelation('badge');
        $courseType = strtolower((string) ($registration->course?->course_type ?? ''));
        $mode = $registration->intake?->intake_mode ?? '-';
        $status = $registration->status ?: '-';

        return [
            'id' => $registration->id,
            'status' => $status,
            'student' => [
                'full_name' => $registration->student?->full_name ?: ($registration->student?->name_with_initials ?: '-'),
                'name_with_initials' => $registration->student?->name_with_initials,
            ],
            'course' => [
                'course_name' => $registration->course?->course_name ?? '-',
                'course_type' => $registration->course?->course_type ?? '-',
            ],
            'intake' => [
                'batch' => $registration->intake?->batch ?? '-',
                'intake_mode' => $mode,
            ],
            'badge' => $badge ? [
                'id' => $badge->id,
                'verification_code' => $badge->verification_code,
            ] : null,
            'eligible_for_badge' => $courseType === 'certificate' && strcasecmp((string) $mode, 'Online') === 0,
        ];
    }

    public function search(Request $request)
    {
        $perPage = $this->perPage($request);
        $page = max(1, (int) $request->input('page', 1));
        $paginator = $this->registrationQuery($request)->paginate($perPage, ['*'], 'page', $page);
        $registrations = $paginator->getCollection();
        $this->attachBadges($registrations);

        $mapped = $registrations->map(fn (CourseRegistration $registration) => $this->mapRegistrationRow($registration))->values();

        return response()->json([
            'success' => true,
            'data' => $mapped,
            'courses' => $mapped,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ]);
    }

    public function searchStudent(Request $request)
    {
        return $this->search($request);
    }

    public function searchByCourse(Request $request)
    {
        return $this->search($request);
    }

    public function getCourseIntakes(Request $request)
    {
        $request->validate(['course_id' => 'required|integer|exists:courses,course_id']);

        $course = Course::findOrFail($request->course_id);

        return response()->json([
            'success' => true,
            'intakes' => Intake::forCourse($course, $course->location)
                ->orderBy('batch')
                ->get(['intake_id', 'batch', 'location', 'intake_mode']),
        ]);
    }

    public function details($code)
    {
        $badge = CourseBadge::with(['student', 'course', 'intake'])
            ->where('verification_code', $code)
            ->first();

        if (!$badge) {
            return response('<div class="text-danger text-center p-3 fw-bold">Badge not found.</div>', 404);
        }

        return view('student_management.badge_details', compact('badge'));
    }

    public function completeCourse(Request $request)
    {
        try {
            $registration = CourseRegistration::with(['course', 'intake', 'student'])->find($request->id);

            if (!$registration) {
                return response()->json(['success' => false, 'message' => 'Registration not found']);
            }

            $course = $registration->course;
            $intake = $registration->intake;

            if (!$course || !$intake) {
                return response()->json(['success' => false, 'message' => 'Missing course or intake details.']);
            }

            if (strtolower((string) $course->course_type) !== 'certificate' || strcasecmp((string) $intake->intake_mode, 'Online') !== 0) {
                return response()->json(['success' => false, 'message' => 'Only Online Certificate Courses are eligible for badges.']);
            }

            $templatePath = public_path('images/badges/nebula_badge.png');
            if (!file_exists($templatePath)) {
                return response()->json(['success' => false, 'message' => 'Badge template not found.']);
            }

            $registration->status = 'completed';
            $registration->save();

            $uuid = (string) Str::uuid();

            $badge = CourseBadge::create([
                'student_id'        => $registration->student_id,
                'course_id'         => $registration->course_id,
                'intake_id'         => $registration->intake_id,
                'badge_title'       => $course->course_name,
                'verification_code' => $uuid,
                'issued_date'       => now(),
                'status'            => 'active',
            ]);

            Storage::disk('public')->makeDirectory('badges');

            $image = imagecreatefrompng($templatePath);
            $black = imagecolorallocate($image, 0, 0, 0);
            $blue  = imagecolorallocate($image, 13, 110, 253);
            $gray  = imagecolorallocate($image, 102, 102, 102);

            $localFont  = public_path('fonts/arial.ttf');
            $systemFont = '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf';

            if (file_exists($localFont)) {
                $fontPath = $localFont;
            } elseif (file_exists($systemFont)) {
                $fontPath = $systemFont;
            } else {
                $fontPath = null;
            }

            $studentName = $registration->student->full_name
                ?? $registration->student->name_with_initials
                ?? 'Unknown Student';

            if ($fontPath) {
                imagettftext($image, 30, 0, 180, 150, $blue, $fontPath, 'Certificate of Completion');
                imagettftext($image, 24, 0, 180, 220, $black, $fontPath, "Awarded to: {$studentName}");
                imagettftext($image, 20, 0, 180, 270, $black, $fontPath, "For completing {$course->course_name}");
                imagettftext($image, 18, 0, 180, 320, $gray, $fontPath, "Nebula Institute of Technology");
                imagettftext($image, 16, 0, 180, 370, $gray, $fontPath, "Issued on " . now()->format('d M Y'));
            } else {
                imagestring($image, 5, 180, 150, 'Certificate of Completion', $blue);
                imagestring($image, 4, 180, 200, "Awarded to: {$studentName}", $black);
                imagestring($image, 4, 180, 250, "For completing {$course->course_name}", $black);
                imagestring($image, 3, 180, 300, "Nebula Institute of Technology", $gray);
                imagestring($image, 2, 180, 340, "Issued on " . now()->format('d M Y'), $gray);
            }

            $path = "badges/{$uuid}.png";
            $fullPath = storage_path("app/public/{$path}");
            imagepng($image, $fullPath);
            imagedestroy($image);

            $badge->update(['badge_image_path' => $path]);

            return response()->json([
                'success' => true,
                'message' => 'Course marked as completed and badge generated successfully.',
                'badge_id' => $badge->id,
                'verification_code' => $uuid,
                'verification_url' => url('/verify-badge/' . $uuid),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    public function cancelBadge(Request $request)
    {
        $registration = CourseRegistration::find($request->registration_id);

        if (!$registration) {
            return response()->json(['success' => false, 'message' => 'Registration not found.']);
        }

        $badge = null;
        if ($request->badge_id) {
            $badge = CourseBadge::find($request->badge_id);
        }

        if (!$badge) {
            $badge = CourseBadge::query()
                ->where('student_id', $registration->student_id)
                ->where('course_id', $registration->course_id)
                ->where('intake_id', $registration->intake_id)
                ->orderByDesc('id')
                ->first();
        }

        if ($badge) {
            if ($badge->badge_image_path && Storage::disk('public')->exists($badge->badge_image_path)) {
                Storage::disk('public')->delete($badge->badge_image_path);
            }
            $badge->delete();
        }

        $registration->status = 'Pending';
        $registration->save();

        return response()->json([
            'success' => true,
            'message' => 'Certificate cancelled and course reverted to pending status.',
        ]);
    }

    public function verify($code)
    {
        $badge = CourseBadge::where('verification_code', $code)->with(['student', 'course', 'intake'])->first();

        if (!$badge) {
            abort(404, 'Invalid badge link.');
        }

        return view('student_management.verify', compact('badge'));
    }
}
