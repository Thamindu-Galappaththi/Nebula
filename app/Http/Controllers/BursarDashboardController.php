<?php

namespace App\Http\Controllers;

use App\Models\ClearanceRequest;

class BursarDashboardController extends Controller
{
    private function paymentQuery()
    {
        return ClearanceRequest::query()->where('clearance_type', ClearanceRequest::TYPE_PAYMENT);
    }

    public function index()
    {
        $pendingCount = $this->paymentQuery()
            ->where('status', ClearanceRequest::STATUS_PENDING)
            ->count();

        $approvedCount = $this->paymentQuery()
            ->where('status', ClearanceRequest::STATUS_APPROVED)
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();

        $rejectedCount = $this->paymentQuery()
            ->where('status', ClearanceRequest::STATUS_REJECTED)
            ->whereMonth('approved_at', now()->month)
            ->whereYear('approved_at', now()->year)
            ->count();

        $pendingList = $this->paymentQuery()
            ->with(['student', 'course', 'intake'])
            ->where('status', ClearanceRequest::STATUS_PENDING)
            ->orderByRaw('COALESCE(requested_at, created_at) ASC')
            ->orderBy('id', 'asc')
            ->paginate(10, ['*'], 'pending_page')
            ->withQueryString();

        $recent = $this->paymentQuery()
            ->with(['student', 'course', 'intake'])
            ->whereIn('status', [
                ClearanceRequest::STATUS_APPROVED,
                ClearanceRequest::STATUS_REJECTED,
            ])
            ->orderByRaw('COALESCE(approved_at, updated_at) DESC')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('dashboards.bursar_dashboard', compact(
            'pendingCount',
            'approvedCount',
            'rejectedCount',
            'pendingList',
            'recent'
        ));
    }
}
