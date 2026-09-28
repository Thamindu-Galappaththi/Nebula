@php
    $formatSlDate = $formatSlDate ?? function ($value) {
        if (!$value) {
            return 'N/A';
        }
        $dt = $value instanceof \Carbon\Carbon ? $value->copy() : \Carbon\Carbon::parse($value);
        return $dt->timezone('Asia/Colombo')->format('d/m/Y H:i');
    };
    $total = method_exists($intakeRequests, 'total') ? $intakeRequests->total() : $intakeRequests->count();
@endphp
@if($total > 0)
    <div class="all-clearance-table-scroll">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Intake</th>
                    <th>Course</th>
                    <th>Location</th>
                    <th>Clearance Type</th>
                    <th>Total Students</th>
                    <th>Responses Received</th>
                    <th>Requested Date</th>
                    <th>Progress</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($intakeRequests as $request)
                    <tr>
                        <td><strong>{{ $request->intake->batch ?? 'N/A' }}</strong></td>
                        <td>{{ $request->course->course_name ?? 'N/A' }}</td>
                        <td>{{ $request->location }}</td>
                        <td><span class="badge bg-secondary">{{ ucfirst($request->clearance_type) }}</span></td>
                        <td><span class="badge bg-info">{{ $request->total_students }}</span></td>
                        <td>
                            <div class="d-flex flex-wrap align-items-center gap-1">
                                <span class="badge bg-success">{{ $request->approved_count }} Approved</span>
                                <span class="badge bg-danger">{{ $request->rejected_count }} Rejected</span>
                                <span class="badge bg-warning text-dark">{{ $request->pending_count }} Pending</span>
                            </div>
                        </td>
                        <td>{{ $formatSlDate($request->requested_at) }}</td>
                        <td>
                            @php
                                $progress = $request->total_students > 0
                                    ? round(($request->received_count / $request->total_students) * 100)
                                    : 0;
                            @endphp
                            <div class="progress" style="height: 20px; min-width: 4.5rem;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $progress }}%" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100">
                                    {{ $progress }}%
                                </div>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-primary view-intake-details" type="button"
                                    data-intake="{{ $request->intake->intake_id ?? '' }}"
                                    data-course="{{ $request->course->course_id ?? '' }}"
                                    data-location="{{ $request->location }}"
                                    data-type="{{ $request->clearance_type }}">
                                <i class="ti ti-eye"></i> View Details
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('clearance.partials.pagination', ['paginator' => $intakeRequests, 'label' => 'Intake clearance pages'])
@else
    <div class="text-center py-4">
        <i class="ti ti-info-circle text-info" style="font-size: 3rem;"></i>
        <h5 class="mt-3">No Intake Clearance Requests</h5>
        <p class="text-muted mb-0">No intake clearance requests have been sent yet.</p>
    </div>
@endif
