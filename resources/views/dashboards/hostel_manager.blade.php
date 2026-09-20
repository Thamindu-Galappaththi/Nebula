@extends('inc.app')

@section('title', 'NEBULA | Hostel Manager Dashboard')

@section('content')

<style nonce="{{ $cspNonce }}">
    .stat-card {
        transition: 0.2s;
        border-left: 4px solid transparent;
        background: white;
    }
    .stat-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 6px 18px rgba(0,0,0,0.1);
    }
    .pending { border-left-color: #f59e0b; }
    .approved { border-left-color: #198754; }
    .rejected { border-left-color: #dc3545; }

    .badge-status {
        padding: 5px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        text-transform: capitalize;
    }
    .badge-pending { background: #f59e0b; color: #1f2937; }
    .badge-approved { background: #198754; color: white; }
    .badge-rejected { background: #dc3545; color: white; }
</style>

<div class="container-fluid">
    <div class="card shadow-sm p-3 mb-4 bg-white">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div>
                <h3 class="fw-bold mb-1">Hostel Manager Dashboard</h3>
                <small class="text-muted">Reviewing hostel clearance requests and student eligibility</small>
            </div>
            <a href="{{ route('hostel.clearance.form.management') }}" class="btn btn-primary btn-sm">
                Open hostel clearance
            </a>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card stat-card pending p-4 shadow-sm">
                <h6 class="text-muted">Pending Reviews</h6>
                <h2 class="text-warning fw-bold">{{ $pendingCount }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card approved p-4 shadow-sm">
                <h6 class="text-muted">Approved This Month</h6>
                <h2 class="text-success fw-bold">{{ $approvedCount }}</h2>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card rejected p-4 shadow-sm">
                <h6 class="text-muted">Rejected This Month</h6>
                <h2 class="text-danger fw-bold">{{ $rejectedCount }}</h2>
            </div>
        </div>
    </div>

    <div class="card shadow-sm p-4 mb-4 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h5 class="fw-semibold m-0">Hostel Clearance Pending</h5>
            <span class="badge bg-warning text-dark">{{ $pendingCount }} pending</span>
        </div>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>Student ID</th>
                        <th>Course</th>
                        <th>Intake</th>
                        <th>Requested</th>
                        <th>Status</th>
                        <th>Review</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingList as $req)
                        @php
                            $reviewUrl = route('hostel.clearance.form.management', array_filter([
                                'location' => $req->location,
                                'course_id' => $req->course_id,
                                'intake_id' => $req->intake_id,
                            ]));
                        @endphp
                        <tr>
                            <td>{{ $req->student?->name_with_initials ?? $req->student?->full_name ?? 'N/A' }}</td>
                            <td><code>{{ $req->student?->student_id ?? $req->student_id ?? '-' }}</code></td>
                            <td>{{ $req->course?->course_name ?? 'N/A' }}</td>
                            <td>{{ $req->intake?->batch ?? '-' }}</td>
                            <td>{{ optional($req->requested_at ?? $req->created_at)->format('Y-m-d') ?? '-' }}</td>
                            <td>
                                <span class="badge-status badge-pending">Pending</span>
                            </td>
                            <td>
                                <a href="{{ $reviewUrl }}" class="btn btn-sm btn-primary">Review</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-muted py-3">No pending hostel clearances</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
            <div class="text-muted fs-13">
                @if($pendingList->total() > 0)
                    Showing {{ $pendingList->firstItem() }} to {{ $pendingList->lastItem() }} of {{ $pendingList->total() }} pending request{{ $pendingList->total() === 1 ? '' : 's' }}
                @else
                    Showing 0 pending requests
                @endif
            </div>
            <div class="d-flex gap-2">
                <a href="{{ $pendingList->previousPageUrl() ?: '#' }}"
                   class="btn btn-outline-secondary btn-sm {{ $pendingList->onFirstPage() ? 'disabled' : '' }}"
                   @if($pendingList->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
                    <i class="ti ti-chevron-left"></i> Previous
                </a>
                <a href="{{ $pendingList->hasMorePages() ? $pendingList->nextPageUrl() : '#' }}"
                   class="btn btn-outline-secondary btn-sm {{ $pendingList->hasMorePages() ? '' : 'disabled' }}"
                   @if(!$pendingList->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>
                    Next <i class="ti ti-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>

    <div class="card shadow-sm p-4 mb-4 bg-white">
        <h5 class="fw-semibold mb-1">Recent Hostel Clearance Updates</h5>
        <p class="text-muted mb-3">Latest approved or rejected requests</p>

        <div class="table-responsive">
            <table class="table table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <th>Student</th>
                        <th>ID</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Processed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recent as $req)
                        @php $status = strtolower((string) $req->status); @endphp
                        <tr>
                            <td>{{ $req->student?->name_with_initials ?? $req->student?->full_name ?? 'N/A' }}</td>
                            <td><code>{{ $req->student?->student_id ?? $req->student_id ?? '-' }}</code></td>
                            <td>{{ $req->course?->course_name ?? 'N/A' }}</td>
                            <td>
                                <span class="badge-status badge-{{ $status }}">{{ $req->status_text }}</span>
                            </td>
                            <td>{{ $req->processedAtSriLanka()?->format('Y-m-d H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-muted py-3">No processed hostel updates yet</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
