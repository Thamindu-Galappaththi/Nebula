@php
    $total = method_exists($pendingRequests, 'total') ? $pendingRequests->total() : $pendingRequests->count();
@endphp
@if($total > 0)
    <div class="table-responsive" style="overflow-x: auto; width: 100%;">
        <table class="table table-hover" id="pendingTable" style="table-layout: fixed; width: max-content; min-width: 1100px;">
            <thead class="table-light" style="position: sticky; top: 0; background: #fff; z-index: 2;">
                <tr>
                    <th>Course Registration ID</th>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Intake</th>
                    <th>Location</th>
                    <th>Requested Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($pendingRequests as $request)
                    <tr>
                        <td>{{ $request->course_registration_identifier ?? '—' }}</td>
                        <td>{{ $request->student->name_with_initials ?? 'N/A' }}</td>
                        <td>{{ $request->course->course_name ?? 'N/A' }}</td>
                        <td>{{ $request->intake->batch ?? 'N/A' }}</td>
                        <td>{{ $request->location }}</td>
                        <td>{{ $request->requestedAtSriLanka()?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                        <td>
                            <button class="btn btn-success btn-sm approve-btn"
                                data-request-id="{{ $request->id }}"
                                data-student-name="{{ $request->student->name_with_initials ?? '' }}">
                                <i class="ti ti-check"></i> Approve
                            </button>
                            <button class="btn btn-danger btn-sm reject-btn"
                                data-request-id="{{ $request->id }}"
                                data-student-name="{{ $request->student->name_with_initials ?? '' }}">
                                <i class="ti ti-x"></i> Reject
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('clearance.partials.pagination', ['paginator' => $pendingRequests, 'label' => 'Pending clearance pages'])
@else
    <div class="text-center py-4">
        <i class="ti ti-check-circle text-success" style="font-size: 3rem;"></i>
        <h5 class="mt-3">No Pending Requests</h5>
        <p class="text-muted">{{ $pendingEmptyMessage ?? 'All clearance requests have been processed.' }}</p>
    </div>
@endif
