<?php

namespace App\Http\Controllers;

use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Student;
use App\Models\StudentClearance;
use App\Support\SpecializationStudentScope;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class AllClearanceController extends Controller
{
    public function showAllClearance(Request $request)
    {
        $student = null;
        $courses = Course::all(['course_id', 'course_name']);

        $allClearanceRequests = ClearanceRequest::with(['student', 'course', 'intake', 'approvedBy'])
            ->orderBy('requested_at', 'desc')
            ->get();

        $filteredRequests = $allClearanceRequests->filter(function ($item) {
            return $item->intake_id && $item->course_id && $item->location && !$item->is_individual_request;
        });

        $groupedRequests = $filteredRequests->groupBy(function ($item) {
            return $item->intake_id . '-' . $item->course_id . '-' . $item->location . '-' . $item->clearance_type;
        });

        $intakeRequests = collect();
        foreach ($groupedRequests as $group) {
            $firstRequest = $group->first();
            if (!$firstRequest || !$firstRequest->intake || !$firstRequest->course) {
                continue;
            }

            $totalStudents = $group->count();
            $approvedCount = $group->where('status', ClearanceRequest::STATUS_APPROVED)->count();
            $rejectedCount = $group->where('status', ClearanceRequest::STATUS_REJECTED)->count();
            $pendingCount  = $group->where('status', ClearanceRequest::STATUS_PENDING)->count();

            $intakeRequests->push((object) [
                'intake'         => $firstRequest->intake,
                'course'         => $firstRequest->course,
                'location'       => $firstRequest->location,
                'clearance_type' => $firstRequest->clearance_type,
                'total_students' => $totalStudents,
                'approved_count' => $approvedCount,
                'rejected_count' => $rejectedCount,
                'pending_count'  => $pendingCount,
                'received_count' => $approvedCount + $rejectedCount,
                'requested_at'   => $group->min('requested_at'),
                'latest_status'  => $group->sortByDesc('requested_at')->first()->status,
                'status_color'   => $group->sortByDesc('requested_at')->first()->status_color,
                'status_text'    => $group->sortByDesc('requested_at')->first()->status_text,
            ]);
        }

        $intakeRequests = $intakeRequests->sortByDesc(function ($item) {
            return $item->requested_at;
        })->values();

        $individualRequests = $allClearanceRequests
            ->filter(function ($item) {
                return $item->is_individual_request;
            })
            ->sortByDesc(function ($item) {
                return $item->requested_at;
            })
            ->values();

        $pendingRequests  = $allClearanceRequests->where('status', ClearanceRequest::STATUS_PENDING);
        $approvedRequests = $allClearanceRequests->where('status', ClearanceRequest::STATUS_APPROVED);
        $rejectedRequests = $allClearanceRequests->where('status', ClearanceRequest::STATUS_REJECTED);
        $pendingCount     = $pendingRequests->count();
        $approvedCount    = $approvedRequests->count();
        $rejectedCount    = $rejectedRequests->count();
        $totalCount       = $allClearanceRequests->count();

        $intakeRequests = $this->paginateCollection($intakeRequests, $request, 'intake_page', 'intake-clearance-requests');
        $individualRequests = $this->paginateCollection($individualRequests, $request, 'individual_page', 'individual-clearance-requests');

        if ($request->ajax()) {
            return response()->json([
                'intake_html' => view('clearance.partials.intake_requests_body', compact('intakeRequests'))->render(),
                'individual_html' => view('clearance.partials.individual_requests_body', compact('individualRequests'))->render(),
                'pendingCount' => $pendingCount,
                'approvedCount' => $approvedCount,
                'rejectedCount' => $rejectedCount,
                'totalCount' => $totalCount,
            ]);
        }

        if ($request->filled('student_id')) {
            $student = Student::where('student_id', $request->student_id)
                ->orWhere('id_value', $request->student_id)
                ->first();
        }

        return view('clearance.all_clearance', compact(
            'student',
            'courses',
            'allClearanceRequests',
            'pendingRequests',
            'approvedRequests',
            'rejectedRequests',
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'totalCount',
            'intakeRequests',
            'individualRequests'
        ));
    }

    private function paginateCollection(Collection $items, Request $request, string $pageName, string $fragment): LengthAwarePaginator
    {
        $perPage = 10;
        $page = max(1, (int) $request->input($pageName, 1));

        $paginator = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'pageName' => $pageName,
            ]
        );

        return $paginator
            ->appends(array_merge($request->except($pageName), ['tab' => 'status']))
            ->fragment($fragment);
    }

    public function librarysearch(Request $request)
    {
        $studentIdLibrary = $request->get('student_id');

        $libraryRecords = ClearanceRequest::where('student_id', $studentIdLibrary)
            ->where('clearance_type', ClearanceRequest::TYPE_LIBRARY)
            ->orderBy('requested_at', 'desc')
            ->get();

        return view('clearance.all_clearance', compact('libraryRecords', 'studentIdLibrary'));
    }

    public function paymentsearch(Request $request)
    {
        $studentIdPayment = $request->get('student_id');

        $paymentRecords = ClearanceRequest::where('student_id', $studentIdPayment)
            ->where('clearance_type', ClearanceRequest::TYPE_PAYMENT)
            ->orderBy('requested_at', 'desc')
            ->get();

        return view('clearance.all_clearance', compact('paymentRecords', 'studentIdPayment'));
    }

    public function hostelsearch(Request $request)
    {
        $studentId = $request->get('student_id');

        $records = ClearanceRequest::where('student_id', $studentId)
            ->where('clearance_type', ClearanceRequest::TYPE_HOSTEL)
            ->orderBy('requested_at', 'desc')
            ->get();

        return view('clearance.all_clearance', compact('records', 'studentId'));
    }

    public function projectsearch(Request $request)
    {
        $studentIdProject = $request->get('student_id');

        $projectRecords = ClearanceRequest::where('student_id', $studentIdProject)
            ->where('clearance_type', ClearanceRequest::TYPE_PROJECT)
            ->orderBy('requested_at', 'desc')
            ->get();

        return view('clearance.all_clearance', compact('projectRecords', 'studentIdProject'));
    }

    public function sendClearance($type, $student_id)
    {
        return back()->with('success', ucfirst($type) . ' clearance form sent for student ID: ' . $student_id);
    }

    public function sendClearanceRequest(Request $request)
    {
        $request->validate([
            'type'       => 'required|in:library,hostel,payment,project',
            'location'   => 'required|string',
            'course_id'  => 'required|exists:courses,course_id',
            'intake_id'  => 'required|exists:intakes,intake_id',
            'student_id' => 'nullable|exists:students,student_id',
        ]);

        try {
            $regQuery = CourseRegistration::where('course_id', $request->course_id)
                ->where('intake_id', $request->intake_id)
                ->where('location', $request->location);

            if ($request->filled('student_id')) {
                $regQuery->where('student_id', $request->student_id);
            } else {
                $regQuery->where(function ($query) {
                    $query->eligible()
                        ->orWhereHas('student', function ($studentQuery) {
                            $studentQuery->whereIn('academic_status', [
                                Student::ACADEMIC_TERMINATED,
                                Student::ACADEMIC_SUSPENDED,
                            ]);
                        });
                });
            }

            $students = $regQuery->with('student')->get();

            $createdCount = 0;
            foreach ($students as $registration) {
                $existingPendingRequest = ClearanceRequest::where('student_id', $registration->student_id)
                    ->where('clearance_type', $request->type)
                    ->where('course_id', $request->course_id)
                    ->where('intake_id', $request->intake_id)
                    ->where('status', ClearanceRequest::STATUS_PENDING)
                    ->first();

                if (!$existingPendingRequest) {
                    ClearanceRequest::create([
                        'clearance_type'        => $request->type,
                        'location'              => $request->location,
                        'course_id'             => $request->course_id,
                        'intake_id'             => $request->intake_id,
                        'student_id'            => $registration->student_id,
                        'status'                => ClearanceRequest::STATUS_PENDING,
                        'requested_at'          => now(),
                        'is_individual_request' => $request->filled('student_id'),
                    ]);
                    $createdCount++;
                }
            }

            $totalCount   = $students->count();
            $skippedCount = $totalCount - $createdCount;

            return response()->json([
                'success' => true,
                'message' => "Clearance request(s) processed. Sent: {$createdCount}. Skipped (already pending): {$skippedCount}.",
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send clearance requests: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getRegisteredCourses(Request $request)
    {
        $nic     = $request->query('nic');
        $student = \App\Models\Student::where('id_value', $nic)->first();
        if (!$student) {
            return response()->json(['success' => false, 'courses' => [], 'message' => 'Student not found']);
        }

        $registrations = \App\Models\CourseRegistration::where('student_id', $student->student_id)->get();
        $courses = [];
        foreach ($registrations as $reg) {
            if ($reg->course) {
                $courses[] = [
                    'id'   => $reg->course->course_id,
                    'name' => $reg->course->course_name,
                ];
            }
        }

        return response()->json(['success' => true, 'courses' => $courses]);
    }

    public function getStudentCourseDetails(Request $request)
    {
        $nic      = $request->query('nic');
        $courseId = $request->query('course_id');
        $student  = \App\Models\Student::where('id_value', $nic)->first();
        $course   = \App\Models\Course::find($courseId);

        if (!$student || !$course) {
            return response()->json(['success' => false, 'message' => 'Student or course not found']);
        }

        return response()->json([
            'success' => true,
            'name'    => $student->name_with_initials,
            'nic'     => $student->id_value,
            'course'  => $course->course_name,
        ]);
    }

    public function getStudentsForIntake(Request $request)
    {
        try {
            $intakeId = $request->input('intake_id');
            $courseId = $request->input('course_id');
            $location = $request->input('location');
            $query    = CourseRegistration::where('intake_id', $intakeId)
                ->whereHas('student', function ($q) {
                    $q->whereIn('academic_status', [
                        Student::ACADEMIC_ACTIVE,
                        Student::ACADEMIC_TERMINATED,
                        Student::ACADEMIC_SUSPENDED,
                    ]);
                })
                ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
                ->when($location, fn ($q) => $q->where('location', $location))
                ->with('student');

            $registrations = $query->get();

            $students = $registrations
                ->unique('student_id')
                ->map(function ($registration) use ($intakeId, $courseId, $location) {
                    $student = $registration->student;
                    if (!$student) {
                        return null;
                    }

                    $latestRequestQuery = ClearanceRequest::where('student_id', $student->student_id)
                        ->where('intake_id', $intakeId)
                        ->when($courseId, fn ($q) => $q->where('course_id', $courseId))
                        ->when($location, fn ($q) => $q->where('location', $location));

                    $latest     = $latestRequestQuery->orderByDesc('requested_at')->first();
                    $statusText = $latest->status_text ?? ($latest->status ?? 'No Request');

                    return [
                        'student_id'       => $student->student_id,
                        'name'             => $student->name_with_initials ?? $student->name ?? ($student->full_name ?? ''),
                        'clearance_status' => $statusText,
                    ];
                })
                ->filter()
                ->values()
                ->all();

            return response()->json(['success' => true, 'data' => $students]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Failed to load students: ' . $e->getMessage()], 500);
        }
    }

    public function getIntakeDetails(Request $request)
    {
        $request->validate([
            'intake_id'     => 'required|exists:intakes,intake_id',
            'course_id'     => 'required|exists:courses,course_id',
            'location'      => 'required|string',
            'clearance_type' => 'required|string',
        ]);

        try {
            $clearanceRequests = ClearanceRequest::where('intake_id', $request->intake_id)
                ->where('course_id', $request->course_id)
                ->where('location', $request->location)
                ->where('clearance_type', $request->clearance_type)
                ->with(['student', 'course', 'intake', 'approvedBy'])
                ->orderBy('requested_at', 'desc')
                ->get();

            if ($clearanceRequests->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No clearance requests found for the specified criteria.',
                ]);
            }

            $firstRequest = $clearanceRequests->first();
            $intakeName   = $firstRequest->intake->batch;
            $courseName   = $firstRequest->course->course_name;

            $students = $clearanceRequests->map(function ($item) {
                return [
                    'student_id'     => $item->student->student_id,
                    'student_name'   => $item->student->name_with_initials,
                    'status'         => $item->status,
                    'status_text'    => $item->status_text,
                    'status_color'   => $item->status_color,
                    'processed_by'   => $item->approvedBy->name ?? null,
                    'processed_date' => $item->approved_at
                        ? $item->approved_at->timezone('Asia/Colombo')->format('d/m/Y H:i')
                        : null,
                    'remarks'        => $item->remarks,
                ];
            });

            return response()->json([
                'success'     => true,
                'intake_name' => $intakeName,
                'course_name' => $courseName,
                'location'    => $request->location,
                'students'    => $students,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load intake details: ' . $e->getMessage(),
            ], 500);
        }
    }
}
