@php
    $total = method_exists($pendingList, 'total') ? $pendingList->total() : $pendingList->count();
@endphp

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
        @if($total > 0)
            Showing {{ $pendingList->firstItem() }} to {{ $pendingList->lastItem() }} of {{ $pendingList->total() }} pending request{{ $pendingList->total() === 1 ? '' : 's' }}
        @else
            Showing 0 pending requests
        @endif
    </div>
    <div class="d-flex gap-2">
        <a href="{{ $pendingList->previousPageUrl() ?: '#' }}"
           class="btn btn-outline-secondary btn-sm hostel-pending-page {{ $pendingList->onFirstPage() ? 'disabled' : '' }}"
           @if($pendingList->onFirstPage()) aria-disabled="true" tabindex="-1" @endif>
            <i class="ti ti-chevron-left"></i> Previous
        </a>
        <a href="{{ $pendingList->hasMorePages() ? $pendingList->nextPageUrl() : '#' }}"
           class="btn btn-outline-secondary btn-sm hostel-pending-page {{ $pendingList->hasMorePages() ? '' : 'disabled' }}"
           @if(!$pendingList->hasMorePages()) aria-disabled="true" tabindex="-1" @endif>
            Next <i class="ti ti-chevron-right"></i>
        </a>
    </div>
</div>
