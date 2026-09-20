<?php

namespace App\Http\Controllers;

use App\Models\ClearanceRequest;
use Illuminate\Http\Request;

class LibrarianDashboardController extends Controller
{
    public function index(Request $request)
    {
        $pendingList = $this->pendingList($request);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('dashboards.partials.librarian_pending_list', compact('pendingList'))->render(),
            ]);
        }

        $pendingCount = $this->libraryQuery()
            ->where('status', ClearanceRequest::STATUS_PENDING)
            ->count();

        $approvedCount = $this->libraryQuery()
            ->where('status', ClearanceRequest::STATUS_APPROVED)
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();

        $rejectedCount = $this->libraryQuery()
            ->where('status', ClearanceRequest::STATUS_REJECTED)
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();

        $recent = $this->libraryQuery()
            ->with(['student', 'course', 'intake'])
            ->whereIn('status', [
                ClearanceRequest::STATUS_APPROVED,
                ClearanceRequest::STATUS_REJECTED,
            ])
            ->orderByRaw('COALESCE(approved_at, updated_at) DESC')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('dashboards.librarian_dashboard', compact(
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'pendingList',
            'recent'
        ));
    }

    private function pendingList(Request $request)
    {
        return $this->libraryQuery()
            ->with(['student', 'course', 'intake'])
            ->where('status', ClearanceRequest::STATUS_PENDING)
            ->orderByRaw('COALESCE(requested_at, created_at) ASC')
            ->orderBy('id', 'asc')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();
    }

    private function libraryQuery()
    {
        return ClearanceRequest::query()->where('clearance_type', ClearanceRequest::TYPE_LIBRARY);
    }
}
