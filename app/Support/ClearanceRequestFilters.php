<?php

namespace App\Support;

use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\Intake;
use Illuminate\Http\Request;

trait ClearanceRequestFilters
{
    protected function clearancePageData(Request $request, string $clearanceType): array
    {
        $filters = $request->validate([
            'location' => 'nullable|string|max:255',
            'course_id' => 'nullable|integer|exists:courses,course_id',
            'intake_id' => 'nullable|integer|exists:intakes,intake_id',
        ]);

        $applyFilters = function ($query) use ($filters) {
            return $query
                ->when($filters['location'] ?? null, fn ($q, $location) => $q->whereRaw('LOWER(location) = ?', [strtolower($location)]))
                ->when($filters['course_id'] ?? null, fn ($q, $courseId) => $q->where('course_id', $courseId))
                ->when($filters['intake_id'] ?? null, fn ($q, $intakeId) => $q->where('intake_id', $intakeId));
        };

        $baseQuery = ClearanceRequest::where('clearance_type', $clearanceType);
        $pendingRequests = $applyFilters((clone $baseQuery)->where('status', ClearanceRequest::STATUS_PENDING))
            ->with(['student', 'course', 'intake', 'courseRegistrations'])
            ->orderByDesc('requested_at')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString()
            ->fragment('pending-clearance');

        $processedRequests = $applyFilters((clone $baseQuery)->whereIn('status', [
            ClearanceRequest::STATUS_APPROVED,
            ClearanceRequest::STATUS_REJECTED,
        ]))
            ->with(['student', 'course', 'intake', 'approvedBy', 'courseRegistrations'])
            ->orderByDesc('approved_at')
            ->paginate(10, ['*'], 'processed_page')
            ->withQueryString()
            ->fragment('processed-clearance');

        $courseIds = (clone $baseQuery)->whereNotNull('course_id')->distinct()->pluck('course_id');
        $intakeIds = (clone $baseQuery)->whereNotNull('intake_id')->distinct()->pluck('intake_id');

        return [
            'pendingRequests' => $pendingRequests,
            'processedRequests' => $processedRequests,
            'courses' => Course::whereIn('course_id', $courseIds)->orderBy('course_name')->get(['course_id', 'course_name', 'location']),
            'intakes' => Intake::whereIn('intake_id', $intakeIds)->orderBy('batch')->get(['intake_id', 'course_id', 'batch', 'location']),
            'filters' => $filters,
        ];
    }
}
