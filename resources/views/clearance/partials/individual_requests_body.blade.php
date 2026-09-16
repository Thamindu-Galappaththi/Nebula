@php
    $formatSlDate = $formatSlDate ?? function ($value) {
        if (!$value) {
            return 'N/A';
        }
        $dt = $value instanceof \Carbon\Carbon ? $value->copy() : \Carbon\Carbon::parse($value);
        return $dt->timezone('Asia/Colombo')->format('d/m/Y H:i');
    };
    $total = method_exists($individualRequests, 'total') ? $individualRequests->total() : $individualRequests->count();
@endphp
@if($total > 0)
    <div class="all-clearance-table-scroll">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student ID</th>
                    <th>Student Name</th>
                    <th>Clearance Type</th>
                    <th>Course</th>
                    <th>Intake</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Requested Date</th>
                    <th>Processed By</th>
                    <th>Processed Date</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($individualRequests as $request)
                    <tr>
                        <td>{{ $request->student->student_id ?? 'N/A' }}</td>
                        <td>{{ $request->student->name_with_initials ?? 'N/A' }}</td>
                        <td><span class="badge bg-secondary">{{ ucfirst($request->clearance_type) }}</span></td>
                        <td>{{ $request->course->course_name ?? 'N/A' }}</td>
                        <td>{{ $request->intake->batch ?? 'N/A' }}</td>
                        <td>{{ $request->location }}</td>
                        <td><span class="badge bg-{{ $request->status_color }}">{{ $request->status_text }}</span></td>
                        <td>{{ $formatSlDate($request->requested_at) }}</td>
                        <td>{{ $request->approvedBy->name ?? 'N/A' }}</td>
                        <td>{{ $request->approved_at ? $formatSlDate($request->approved_at) : 'N/A' }}</td>
                        <td>{{ $request->remarks ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('clearance.partials.pagination', ['paginator' => $individualRequests, 'label' => 'Individual clearance pages'])
@else
    <div class="text-center py-4">
        <i class="ti ti-info-circle text-info" style="font-size: 3rem;"></i>
        <h5 class="mt-3">No Individual Clearance Requests</h5>
        <p class="text-muted mb-0">No individual clearance requests have been sent yet.</p>
    </div>
@endif
