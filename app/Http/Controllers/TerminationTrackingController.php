<?php

namespace App\Http\Controllers;

use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Student;
use App\Models\StudentStatusHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TerminationTrackingController extends Controller
{
    public function index(Request $request)
    {
        $payload = $this->buildIndexPayload();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'summary' => $payload['summary'],
                'filters' => $payload['filters'],
                'processes' => $payload['processes']->values()->all(),
            ]);
        }

        return view('student_management.termination_tracking', $payload);
    }

    private function buildIndexPayload(): array
    {
        $processes = Student::where('academic_status', Student::ACADEMIC_TERMINATED)
            ->with([
                'statusHistories' => function ($query) {
                    $query->where('to_status', Student::ACADEMIC_TERMINATED)
                        ->with('user')
                        ->latest('created_at');
                },
                'courseRegistrations' => function ($query) {
                    $query->with(['course', 'intake'])
                        ->orderByDesc('registration_date')
                        ->orderByDesc('id');
                },
                'clearanceRequests.approvedBy',
                'clearanceRequests.course',
                'clearanceRequests.intake',
            ])
            ->orderByDesc('updated_at')
            ->get()
            ->map(function (Student $student) {
                return $this->buildProcessRow($student);
            })
            ->values();

        return [
            'processes' => $processes,
            'summary' => [
                'total' => $processes->count(),
                'clearance_in_progress' => $processes->filter(function ($process) {
                    return in_array($process['overall_status']['key'], ['not_started', 'awaiting_clearances', 'clearance_rejected'], true);
                })->count(),
                'completed' => $processes->filter(function ($process) {
                    return $process['overall_status']['key'] === 'completed';
                })->count(),
            ],
            'filters' => $this->filterCatalog(),
        ];
    }

    private function filterCatalog(): array
    {
        return [
            'locations' => ['Welisara', 'Moratuwa', 'Peradeniya'],
            'courses' => Course::query()
                ->orderBy('course_name')
                ->get(['course_id', 'course_name', 'location'])
                ->map(function (Course $course) {
                    return [
                        'id' => (int) $course->course_id,
                        'name' => $course->course_name,
                        'location' => $course->location,
                    ];
                })
                ->unique('id')
                ->values()
                ->all(),
            'intakes' => Intake::query()
                ->orderBy('batch')
                ->get(['intake_id', 'batch', 'course_id', 'location'])
                ->map(function (Intake $intake) {
                    return [
                        'id' => (int) $intake->intake_id,
                        'name' => $intake->batch,
                        'course_id' => (int) $intake->course_id,
                        'location' => $intake->location,
                    ];
                })
                ->unique('id')
                ->values()
                ->all(),
        ];
    }

    private function buildProcessRow(Student $student): array
    {
        $terminationHistory = $student->relationLoaded('statusHistories')
            ? $student->statusHistories->first()
            : StudentStatusHistory::where('student_id', $student->student_id)
                ->where('to_status', Student::ACADEMIC_TERMINATED)
                ->with('user')
                ->latest('created_at')
                ->first();

        $latestRegistration = $student->relationLoaded('courseRegistrations')
            ? $student->courseRegistrations->first()
            : CourseRegistration::where('student_id', $student->student_id)
                ->with(['course', 'intake'])
                ->orderByDesc('registration_date')
                ->orderByDesc('id')
                ->first();

        $studentClearances = $student->relationLoaded('clearanceRequests')
            ? $student->clearanceRequests
            : ClearanceRequest::where('student_id', $student->student_id)
                ->with(['approvedBy', 'course', 'intake'])
                ->get();

        if ($latestRegistration) {
            $studentClearances = $studentClearances
                ->filter(function ($clearance) use ($latestRegistration) {
                    return (int) $clearance->course_id === (int) $latestRegistration->course_id
                        && (int) $clearance->intake_id === (int) $latestRegistration->intake_id;
                })
                ->values();
        }

        $clearances = collect(ClearanceRequest::getClearanceTypes())
            ->map(function ($label, $type) use ($studentClearances) {
                $request = $studentClearances
                    ->where('clearance_type', $type)
                    ->sortByDesc(function (ClearanceRequest $clearance) {
                        return optional($clearance->requested_at)->timestamp
                            ?? $clearance->id;
                    })
                    ->first();

                if (!$request) {
                    return [
                        'type' => $type,
                        'label' => $label,
                        'status_key' => 'not_requested',
                        'status_label' => 'Not requested',
                        'badge_class' => 'bg-secondary',
                        'requested_at' => null,
                        'approved_at' => null,
                        'remarks' => null,
                        'approved_by' => null,
                        'course_name' => null,
                        'intake_name' => null,
                        'clearance_slip_url' => null,
                    ];
                }

                return [
                    'type' => $type,
                    'label' => $label,
                    'status_key' => $request->status,
                    'status_label' => ucfirst($request->status),
                    'badge_class' => $this->statusBadgeClass($request->status),
                    'requested_at' => optional($request->requested_at)->format('Y-m-d H:i'),
                    'approved_at' => optional($request->approved_at)->format('Y-m-d H:i'),
                    'remarks' => $request->remarks,
                    'approved_by' => optional($request->approvedBy)->name ?? optional($request->approvedBy)->full_name,
                    'course_name' => optional($request->course)->course_name,
                    'intake_name' => optional($request->intake)->batch,
                    'clearance_slip_url' => $this->publicFileUrl($request->clearance_slip),
                ];
            })
            ->values();

        $clearanceSummary = [
            'approved' => $clearances->where('status_key', ClearanceRequest::STATUS_APPROVED)->count(),
            'pending' => $clearances->where('status_key', ClearanceRequest::STATUS_PENDING)->count(),
            'rejected' => $clearances->where('status_key', ClearanceRequest::STATUS_REJECTED)->count(),
            'not_requested' => $clearances->where('status_key', 'not_requested')->count(),
            'requested' => $clearances->filter(function ($clearance) {
                return $clearance['status_key'] !== 'not_requested';
            })->count(),
        ];

        $overallStatus = $this->buildOverallStatus($clearanceSummary);

        return [
            'student_id' => $student->student_id,
            'student_name' => $student->full_name,
            'student_nic' => $student->id_value,
            'location' => $student->institute_location,
            'academic_status' => $student->academic_status,
            'termination_reason' => $terminationHistory?->reason ?? $student->academic_status_reason,
            'terminated_at' => optional($terminationHistory?->created_at ?? $student->academic_status_changed_at)->format('Y-m-d H:i'),
            'terminated_by' => optional($terminationHistory?->user)->name ?? optional($terminationHistory?->user)->full_name,
            'termination_document_url' => $this->publicFileUrl($terminationHistory?->document ?? $student->academic_status_document),
            'course_name' => optional($latestRegistration?->course)->course_name,
            'course_id' => $latestRegistration?->course_id ? (int) $latestRegistration->course_id : null,
            'intake_name' => optional($latestRegistration?->intake)->batch,
            'intake_id' => $latestRegistration?->intake_id ? (int) $latestRegistration->intake_id : null,
            'clearances' => $clearances,
            'clearance_summary' => $clearanceSummary,
            'overall_status' => $overallStatus,
            'profile_url' => url('/student/profile/' . $student->student_id),
        ];
    }

    private function buildOverallStatus(array $clearanceSummary): array
    {
        if ($clearanceSummary['requested'] === 0) {
            return [
                'key' => 'not_started',
                'label' => 'No clearances requested',
                'badge_class' => 'bg-secondary',
            ];
        }

        if ($clearanceSummary['rejected'] > 0) {
            return [
                'key' => 'clearance_rejected',
                'label' => 'Clearance rejected',
                'badge_class' => 'bg-danger',
            ];
        }

        if ($clearanceSummary['pending'] > 0 || $clearanceSummary['not_requested'] > 0) {
            return [
                'key' => 'awaiting_clearances',
                'label' => 'Awaiting clearances',
                'badge_class' => 'bg-warning text-dark',
            ];
        }

        return [
            'key' => 'completed',
            'label' => 'Clearances completed',
            'badge_class' => 'bg-success',
        ];
    }

    private function statusBadgeClass(string $status): string
    {
        return match ($status) {
            'approved' => 'bg-success',
            'rejected' => 'bg-danger',
            'pending' => 'bg-warning text-dark',
            default => 'bg-secondary',
        };
    }

    private function publicFileUrl(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        $normalizedPath = preg_replace('#^public/#', '', $path);

        return Storage::disk('public')->url($normalizedPath);
    }
}