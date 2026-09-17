@extends('inc.app')

@section('title', 'NEBULA | Hostel Clearance')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-12 mt-2">
                <div class="p-4 rounded shadow w-100 bg-white mt-4">
                    <h2 class="text-center mb-4">Hostel Clearance Management</h2>
                    <hr style="margin-bottom: 30px;">

                    <form method="GET" class="row g-3 align-items-end mb-4">
                        <div class="col-md-4"><label for="locationFilter" class="form-label">Location</label><select class="form-select" id="locationFilter" name="location"><option value="">All Locations</option>@foreach(['Welisara', 'Moratuwa', 'Peradeniya'] as $location)<option value="{{ $location }}" @selected(($filters['location'] ?? '') === $location)>{{ $location }}</option>@endforeach</select></div>
                        <div class="col-md-4"><label for="courseFilter" class="form-label">Course</label><select class="form-select" id="courseFilter" name="course_id"><option value="">All Courses</option>@foreach($courses as $course)<option value="{{ $course->course_id }}" data-location="{{ $course->location }}" @selected((string) ($filters['course_id'] ?? '') === (string) $course->course_id)>{{ $course->course_name }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label for="intakeFilter" class="form-label">Intake</label><select class="form-select" id="intakeFilter" name="intake_id"><option value="">All Intakes</option>@foreach($intakes as $intake)<option value="{{ $intake->intake_id }}" data-course-id="{{ $intake->course_id }}" data-location="{{ $intake->location }}" @selected((string) ($filters['intake_id'] ?? '') === (string) $intake->intake_id)>{{ $intake->batch }}</option>@endforeach</select></div>
                        <div class="col-md-1 d-grid"><button class="btn btn-primary" type="submit">Filter</button></div>
                    </form>
                    <!-- Pending Requests Section -->
                    <div class="card mb-4" id="pending-clearance">

                        <div class="card-header bg-warning text-white">
                            <h5 class="mb-0"><i class="ti ti-clock"></i> Pending Clearance Requests</h5>
                        </div>
                        <div class="card-body">
                            @if($pendingRequests->count() > 0)
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
                                                    <td>{{ $request->student->name_with_initials }}</td>
                                                    <td>{{ $request->course->course_name }}</td>
                                                    <td>{{ $request->intake->batch }}</td>
                                                    <td>{{ $request->location }}</td>
                                                    <td>{{ $request->requestedAtSriLanka()?->format('d/m/Y H:i') ?? 'N/A' }}</td>
                                                    <td>
                                                        <button class="btn btn-success btn-sm approve-btn"
                                                            data-request-id="{{ $request->id }}"
                                                            data-student-name="{{ $request->student->name_with_initials }}">
                                                            <i class="ti ti-check"></i> Approve
                                                        </button>
                                                        <button class="btn btn-danger btn-sm reject-btn"
                                                            data-request-id="{{ $request->id }}"
                                                            data-student-name="{{ $request->student->name_with_initials }}">
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
                                    <p class="text-muted">All hostel clearance requests have been processed.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Processed Requests Section -->
                    <div class="card" id="processed-clearance">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="ti ti-list-check"></i> Processed Clearance Requests</h5>
                        </div>
                        <div class="card-body">
                            @if($processedRequests->count() > 0)
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
                                                    <td>{{ $request->student->name_with_initials }}</td>
                                                    <td>{{ $request->course->course_name }}</td>
                                                    <td>{{ $request->intake->batch }}</td>
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
                                    <p class="text-muted">No clearance requests have been processed yet.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Approval Modal -->
    <div class="modal fade" id="approvalModal" tabindex="-1" aria-labelledby="approvalModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="approvalModalTitle">Confirm Action</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="approvalModalText">Are you sure you want to proceed with this action?</p>
                    <div class="mb-3">
                        <label for="remarks" class="form-label">Remarks (Optional)</label>
                        <textarea class="form-control" id="remarks" rows="3" placeholder="Enter any remarks..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="clearance_slip" class="form-label">Clearance Slip (Optional)</label>
                        <input type="file" class="form-control" id="clearance_slip" name="clearance_slip"
                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                        <small class="text-muted">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max size: 5MB)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmApproval">
                        <i class="ti ti-check"></i> Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3"></div>
@endsection

@push('scripts')
    <script nonce="{{ $cspNonce }}">
        $(document).ready(function () {
            let currentRequestId = null;
            let currentAction = null;

            // Show toast function
            function showToast(message, type) {
                const toast = `
                    <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                        <div class="d-flex">
                            <div class="toast-body">${message}</div>
                            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                        </div>
                    </div>
                `;
                $('.toast-container').append(toast);
                $('.toast').toast('show');
                $('.toast').on('hidden.bs.toast', function () {
                    $(this).remove();
                });
            }

            // Approve button click
            $('.approve-btn').on('click', function () {
                currentRequestId = $(this).data('request-id');
                currentAction = 'approve';
                const studentName = $(this).data('student-name');

                $('#approvalModalTitle').text('Approve Clearance');
                $('#approvalModalText').text(`Are you sure you want to approve hostel clearance for ${studentName}?`);
                $('#remarks').val('');
                $('#clearance_slip').val('');
                $('#confirmApproval').removeClass('btn-danger').addClass('btn-success');
                $('#approvalModal').modal('show');
            });

            // Reject button click
            $('.reject-btn').on('click', function () {
                currentRequestId = $(this).data('request-id');
                currentAction = 'reject';
                const studentName = $(this).data('student-name');

                $('#approvalModalTitle').text('Reject Clearance');
                $('#approvalModalText').text(`Are you sure you want to reject hostel clearance for ${studentName}?`);
                $('#remarks').val('');
                $('#clearance_slip').val('');
                $('#confirmApproval').removeClass('btn-success').addClass('btn-danger');
                $('#approvalModal').modal('show');
            });

            // Confirm approval/rejection
            $('#confirmApproval').on('click', function () {
                if (!currentRequestId || !currentAction) return;

                const url = currentAction === 'approve'
                    ? '{{ route("hostel.approve.clearance") }}'
                    : '{{ route("hostel.reject.clearance") }}';

                const formData = new FormData();
                formData.append('request_id', currentRequestId);
                formData.append('remarks', $('#remarks').val());
                formData.append('_token', '{{ csrf_token() }}');

                const fileInput = document.getElementById('clearance_slip');
                if (fileInput.files.length > 0) {
                    formData.append('clearance_slip', fileInput.files[0]);
                }

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function (response) {
                        if (response.success) {
                            showToast(response.message, 'success');
                            setTimeout(() => {
                                location.reload();
                            }, 1500);
                        } else {
                            showToast(response.message, 'danger');
                        }
                    },
                    error: function () {
                        showToast('An error occurred while processing the request.', 'danger');
                    }
                });

                $('#approvalModal').modal('hide');
            });
        });
    </script>
@endpush

@push('scripts')
<script nonce="{{ $cspNonce }}">
$(function () {
    const $location = $('#locationFilter'), $course = $('#courseFilter'), $intake = $('#intakeFilter');
    function updateOptions(resetCourse) {
        const location = $location.val(); if (resetCourse) $course.val('');
        $course.find('option[data-location]').each(function () { $(this).toggle(!location || $(this).data('location') === location); });
        const courseId = $course.val();
        $intake.find('option[data-course-id]').each(function () { $(this).toggle((!location || $(this).data('location') === location) && (!courseId || String($(this).data('course-id')) === String(courseId))); });
        if (!$intake.find('option:selected').is(':visible')) $intake.val('');
    }
    $location.on('change', function () { $intake.val(''); updateOptions(true); });
    $course.on('change', function () { $intake.val(''); updateOptions(false); }); updateOptions(false);
});
</script>
@endpush
