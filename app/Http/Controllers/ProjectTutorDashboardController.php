<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ClearanceRequest;

class ProjectTutorDashboardController extends Controller
{
    private function normalizeLocation(?string $location): string
    {
        $location = $location ?? 'Welisara';
        $location = str_replace([
            'Nebula Institute of Technology – ',
            'Nebula Institute of Technology - '
        ], '', $location);

        return trim($location);
    }

    private function applyLocationScope($query, string $location)
    {
        return $query->whereRaw(
            "LOWER(TRIM(REPLACE(REPLACE(location, 'Nebula Institute of Technology – ', ''), 'Nebula Institute of Technology - ', ''))) = ?",
            [strtolower($location)]
        );
    }

    private function projectQuery(?string $location = null)
    {
        $query = ClearanceRequest::query()->where('clearance_type', ClearanceRequest::TYPE_PROJECT);

        if ($location && $location !== 'all') {
            $this->applyLocationScope($query, $this->normalizeLocation($location));
        }

        return $query;
    }

    public function index()
    {
        $pendingCount = $this->projectQuery()
            ->where('status', ClearanceRequest::STATUS_PENDING)
            ->count();

        $approvedCount = $this->projectQuery()
            ->where('status', ClearanceRequest::STATUS_APPROVED)
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();

        $rejectedCount = $this->projectQuery()
            ->where('status', ClearanceRequest::STATUS_REJECTED)
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();

        $pendingList = $this->projectQuery()
            ->with(['student', 'course', 'intake'])
            ->where('status', ClearanceRequest::STATUS_PENDING)
            ->orderByRaw('COALESCE(requested_at, created_at) ASC')
            ->orderBy('id', 'asc')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $recent = $this->projectQuery()
            ->with(['student', 'course', 'intake'])
            ->whereIn('status', [
                ClearanceRequest::STATUS_APPROVED,
                ClearanceRequest::STATUS_REJECTED,
            ])
            ->orderByRaw('COALESCE(approved_at, updated_at) DESC')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('dashboards.project_tutor_dashboard', compact(
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'pendingList',
            'recent'
        ));
    }

    public function getPendingClearances(Request $request)
    {
        $list = $this->projectQuery($request->query('location'))
            ->with(['student', 'course', 'intake'])
            ->where('status', ClearanceRequest::STATUS_PENDING)
            ->orderByRaw('COALESCE(requested_at, created_at) ASC')
            ->get();

        return response()->json(['success' => true, 'data' => $list]);
    }

    public function getRecentUpdates(Request $request)
    {
        $list = $this->projectQuery($request->query('location'))
            ->with(['student', 'course', 'intake'])
            ->whereIn('status', [
                ClearanceRequest::STATUS_APPROVED,
                ClearanceRequest::STATUS_REJECTED,
            ])
            ->orderByRaw('COALESCE(approved_at, updated_at) DESC')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return response()->json(['success' => true, 'data' => $list]);
    }

    public function getSummary(Request $request)
    {
        $query = $this->projectQuery($request->query('location'));

        $pendingCount = (clone $query)->where('status', ClearanceRequest::STATUS_PENDING)->count();
        $approvedCount = (clone $query)->where('status', ClearanceRequest::STATUS_APPROVED)
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();
        $rejectedCount = (clone $query)->where('status', ClearanceRequest::STATUS_REJECTED)
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();

        return response()->json([
            'success' => true,
            'pending_count' => $pendingCount,
            'approved_count' => $approvedCount,
            'rejected_count' => $rejectedCount,
        ]);
    }

    public function approveProject($id, Request $request)
    {
        $clearanceRequest = ClearanceRequest::findOrFail($id);

        if ($clearanceRequest->clearance_type !== ClearanceRequest::TYPE_PROJECT) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid clearance type for this action.',
            ], 400);
        }

        $clearanceRequest->approve(auth()->id(), $request->input('remarks'));
        return response()->json(['success' => true, 'message' => 'Project clearance approved.']);
    }

    public function rejectProject($id, Request $request)
    {
        $clearanceRequest = ClearanceRequest::findOrFail($id);

        if ($clearanceRequest->clearance_type !== ClearanceRequest::TYPE_PROJECT) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid clearance type for this action.',
            ], 400);
        }

        $clearanceRequest->reject(auth()->id(), $request->input('remarks'));
        return response()->json(['success' => true, 'message' => 'Project clearance rejected.']);
    }
}
