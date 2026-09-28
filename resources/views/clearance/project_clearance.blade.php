@extends('inc.app')

@section('title', 'NEBULA | Project Clearance')

@section('content')
    <div class="container-fluid clearance-management-page">
        <div class="row justify-content-center">
            <div class="col-md-12 mt-2">
                <div class="p-4 rounded shadow w-100 bg-white mt-4">
                    <h2 class="text-center mb-4">Project Clearance Management</h2>
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
                        <div class="card-body" id="pendingRequestsBody">
                            @include('clearance.partials.pending_requests_body')
                        </div>
                    </div>

                    <!-- Processed Requests Section -->
                    <div class="card" id="processed-clearance">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="ti ti-list-check"></i> Processed Clearance Requests</h5>
                        </div>
                        <div class="card-body" id="processedRequestsBody">
                            @include('clearance.partials.processed_requests_body')
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

            // Approve button click
            $(document).on('click', '.approve-btn', function () {
                currentRequestId = $(this).data('request-id');
                currentAction = 'approve';
                const studentName = $(this).data('student-name');

                $('#approvalModalTitle').text('Approve Clearance');
                $('#approvalModalText').text(`Are you sure you want to approve project clearance for ${studentName}?`);
                $('#remarks').val('');
                $('#confirmApproval').removeClass('btn-danger').addClass('btn-success');
                $('#approvalModal').modal('show');
            });

            // Reject button click
            $(document).on('click', '.reject-btn', function () {
                currentRequestId = $(this).data('request-id');
                currentAction = 'reject';
                const studentName = $(this).data('student-name');

                $('#approvalModalTitle').text('Reject Clearance');
                $('#approvalModalText').text(`Are you sure you want to reject project clearance for ${studentName}?`);
                $('#remarks').val('');
                $('#confirmApproval').removeClass('btn-success').addClass('btn-danger');
                $('#approvalModal').modal('show');
            });

            // Confirm approval/rejection
            $('#confirmApproval').on('click', function () {
                if (!currentRequestId || !currentAction) return;

                const url = currentAction === 'approve'
                    ? '{{ route("project.approve.clearance") }}'
                    : '{{ route("project.reject.clearance") }}';

                $.ajax({
                    url: url,
                    method: 'POST',
                    data: {
                        request_id: currentRequestId,
                        remarks: $('#remarks').val(),
                        _token: '{{ csrf_token() }}'
                    },
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

        });
    </script>
    @include('clearance.partials.management_scripts')
@endpush
