@php
    $total = method_exists($processedRequests, 'total') ? $processedRequests->total() : $processedRequests->count();
@endphp
@if($total > 0)
    <div class="table-responsive" style="overflow-x: auto; width: 100%;">
        <table class="table table-hover" id="processedTable" style="table-layout: fixed; width: max-content; min-width: 1100px;">
            <thead class="table-light" style="position: sticky; top: 0; background: #fff; z-index: 2;">
                <tr>
                    <th>Course Registration ID</th>
                    <th>Student Name</th>
                    <th>Course</th>
                    <th>Intake</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th>Processed Date</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
                @foreach($processedRequests as $request)
                    <tr>
                        <td>{{ $request->course_registration_identifier ?? '—' }}</td>
                        <td>{{ $request->student->name_with_initials ?? 'N/A' }}</td>
                        <td>{{ $request->course->course_name ?? 'N/A' }}</td>
                        <td>{{ $request->intake->batch ?? 'N/A' }}</td>
                        <td>{{ $request->location }}</td>
                        <td>
                            @if($request->status === 'approved')
                                <span class="badge bg-success">Approved</span>
                            @else
                                <span class="badge bg-danger">Rejected</span>
                            @endif
                        </td>
                        <td>{{ $request->approved_at ? $request->processedAtSriLanka()?->format('d/m/Y H:i') : 'N/A' }}</td>
                        <td>{{ $request->remarks ?: 'No remarks' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @include('clearance.partials.pagination', ['paginator' => $processedRequests, 'label' => 'Processed clearance pages'])
@else
    <div class="text-center py-4">
        <i class="ti ti-inbox text-muted" style="font-size: 3rem;"></i>
        <h5 class="mt-3">No Processed Requests</h5>
        <p class="text-muted">{{ $processedEmptyMessage ?? 'No clearance requests have been processed yet.' }}</p>
    </div>
@endif
