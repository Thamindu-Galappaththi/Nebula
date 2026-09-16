@extends('inc.app')

@section('title', 'NEBULA | All Clearance')

@section('content')
@php
    $pendingCount = $pendingCount ?? (isset($pendingRequests) ? $pendingRequests->count() : 0);
    $approvedCount = $approvedCount ?? (isset($approvedRequests) ? $approvedRequests->count() : 0);
    $rejectedCount = $rejectedCount ?? (isset($rejectedRequests) ? $rejectedRequests->count() : 0);
    $totalCount = $totalCount ?? (isset($allClearanceRequests) ? $allClearanceRequests->count() : 0);
    $intakeRequests = $intakeRequests ?? collect();
    $individualRequests = $individualRequests ?? collect();
    $formatSlDate = function ($value) {
        if (!$value) {
            return 'N/A';
        }
        $dt = $value instanceof \Carbon\Carbon ? $value : \Carbon\Carbon::parse($value);
        return $dt->timezone('Asia/Colombo')->format('d/m/Y H:i');
    };
    $canSendClearance = auth()->user()->hasAnyRole(['Program Administrator (level 01)', 'Program Administrator (level 02)', 'Developer']);
    $canViewIntakeStudents = auth()->user()->hasAnyRole(['Librarian', 'Hostel Manager', 'Bursar', 'Project Tutor']);
@endphp
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<div class="container-fluid all-clearance-page px-2 px-md-3 mt-3 mb-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="text-center mb-4">All Clearance Requests</h2>
            <hr>

            <ul class="nav nav-tabs exam-results-tabs mb-4" id="clearanceTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ request('tab') === 'status' ? '' : 'active' }}" id="intake-tab" data-bs-toggle="tab" data-bs-target="#intake-clearance" type="button" role="tab" aria-controls="intake-clearance" aria-selected="{{ request('tab') === 'status' ? 'false' : 'true' }}">Intake Clearance</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="individual-tab" data-bs-toggle="tab" data-bs-target="#individual-clearance" type="button" role="tab" aria-controls="individual-clearance" aria-selected="false">Individual Clearance</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ request('tab') === 'status' ? 'active' : '' }}" id="status-tab" data-bs-toggle="tab" data-bs-target="#request-status" type="button" role="tab" aria-controls="request-status" aria-selected="{{ request('tab') === 'status' ? 'true' : 'false' }}">Request Status</button>
                </li>
            </ul>

            <div class="tab-content" id="clearanceTabsContent">
                <div class="tab-pane fade {{ request('tab') === 'status' ? '' : 'show active' }}" id="intake-clearance" role="tabpanel" aria-labelledby="intake-tab">
                    <form id="clearanceFilterForm" class="all-clearance-filters">
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="locationDropdown" class="col-12 col-md-3 col-lg-2 col-form-label fw-bold">Location<span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select id="locationDropdown" class="form-select" required>
                                    <option selected disabled value="">Select a Location</option>
                                    <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                                    <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                                    <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="courseDropdown" class="col-12 col-md-3 col-lg-2 col-form-label fw-bold">Course<span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select id="courseDropdown" class="form-select" required disabled>
                                    <option selected disabled value="">Select Course</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="intakeDropdown" class="col-12 col-md-3 col-lg-2 col-form-label fw-bold">Intake<span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select id="intakeDropdown" class="form-select" required disabled>
                                    <option selected disabled value="">Select Intake</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <div id="clearanceTableSection" hidden>
                        <div class="all-clearance-table-scroll">
                            <table class="table table-bordered align-middle text-center mt-2 mb-0">
                                <thead class="all-clearance-thead">
                                    <tr>
                                        <th>Description of Clearance</th>
                                        <th>Clearance Form</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(['payment' => 'Payment', 'library' => 'Library', 'hostel' => 'Hostel', 'project' => 'Project Tutor'] as $type => $label)
                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td>
                                                @if($canSendClearance)
                                                    <button class="btn btn-primary px-4 send-clearance-btn" data-type="{{ $type }}">Send</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if($canViewIntakeStudents)
                        <div id="studentListSection" hidden>
                            <h5 class="fw-bold mt-4 mb-3">Students in Selected Intake</h5>
                            <div class="all-clearance-table-scroll">
                                <table class="table table-bordered align-middle text-center mb-0">
                                    <thead class="all-clearance-thead">
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Name</th>
                                            <th>Clearance Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="studentListTableBody"></tbody>
                                </table>
                            </div>
                            <div id="studentListPagination" class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3 clearance-pagination" hidden></div>
                        </div>
                    @endif
                </div>

                <div class="tab-pane fade" id="individual-clearance" role="tabpanel" aria-labelledby="individual-tab">
                    <form id="individualClearanceFilterForm" class="all-clearance-filters">
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="ind_locationDropdown" class="col-12 col-md-3 col-lg-2 col-form-label fw-bold">Location<span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select id="ind_locationDropdown" class="form-select" required>
                                    <option selected disabled value="">Select Location</option>
                                    <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                                    <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                                    <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="ind_courseDropdown" class="col-12 col-md-3 col-lg-2 col-form-label fw-bold">Course<span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select id="ind_courseDropdown" class="form-select" required disabled>
                                    <option selected disabled value="">Select Course</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="ind_intakeDropdown" class="col-12 col-md-3 col-lg-2 col-form-label fw-bold">Intake<span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select id="ind_intakeDropdown" class="form-select" required disabled>
                                    <option selected disabled value="">Select Intake</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="ind_studentDropdown" class="col-12 col-md-3 col-lg-2 col-form-label fw-bold">Student<span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select id="ind_studentDropdown" class="form-select" required disabled>
                                    <option selected disabled value="">Select Student</option>
                                </select>
                            </div>
                        </div>
                    </form>

                    <div id="individualClearanceTableSection" hidden>
                        <div class="all-clearance-table-scroll">
                            <table class="table table-bordered align-middle text-center mt-2 mb-0">
                                <thead class="all-clearance-thead">
                                    <tr>
                                        <th>Description of Clearance</th>
                                        <th>Clearance Form</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(['payment' => 'Payment', 'library' => 'Library', 'hostel' => 'Hostel', 'project' => 'Project Tutor'] as $type => $label)
                                        <tr>
                                            <td>{{ $label }}</td>
                                            <td>
                                                @if($canSendClearance)
                                                    <button class="btn btn-primary px-4 send-individual-clearance-btn" data-type="{{ $type }}">Send</button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade {{ request('tab') === 'status' ? 'show active' : '' }}" id="request-status" role="tabpanel" aria-labelledby="status-tab">
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card bg-warning text-white h-100">
                                <div class="card-body text-center">
                                    <h4 class="card-title mb-1">{{ $pendingCount }}</h4>
                                    <p class="card-text mb-0">Pending Requests</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card bg-success text-white h-100">
                                <div class="card-body text-center">
                                    <h4 class="card-title mb-1">{{ $approvedCount }}</h4>
                                    <p class="card-text mb-0">Approved Requests</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card bg-danger text-white h-100">
                                <div class="card-body text-center">
                                    <h4 class="card-title mb-1">{{ $rejectedCount }}</h4>
                                    <p class="card-text mb-0">Rejected Requests</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-sm-6 col-lg-3">
                            <div class="card bg-info text-white h-100">
                                <div class="card-body text-center">
                                    <h4 class="card-title mb-1">{{ $totalCount }}</h4>
                                    <p class="card-text mb-0">Total Requests</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4" id="intake-clearance-requests">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="ti ti-users"></i> Intake Clearance Requests</h5>
                        </div>
                        <div class="card-body" id="intakeRequestsBody">
                            @include('clearance.partials.intake_requests_body')
                        </div>
                    </div>

                    <div class="card" id="individual-clearance-requests">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="ti ti-user"></i> Individual Clearance Requests</h5>
                        </div>
                        <div class="card-body" id="individualRequestsBody">
                            @include('clearance.partials.individual_requests_body')
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="intakeDetailsModal" tabindex="-1" aria-labelledby="intakeDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="intakeDetailsModalLabel">Intake Clearance Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="intakeDetailsContent"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1055;"></div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.all.min.js"></script>
<script nonce="{{ $cspNonce }}">
$(document).ready(function () {
    const perPage = 10;
    const canViewIntakeStudents = @json($canViewIntakeStudents);
    let intakeStudents = [];
    let intakeStudentPage = 1;
    let detailsStudents = [];
    let detailsStudentPage = 1;

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch]);
        });
    }

    function showAlert(title, text, icon) {
        if (window.Swal) {
            Swal.fire({ title: title, text: text, icon: icon, confirmButtonText: 'OK' });
            return;
        }
        window.alert(text);
    }

    function keepStatusTab() {
        const tab = document.getElementById('status-tab');
        if (tab && window.bootstrap && bootstrap.Tab) {
            bootstrap.Tab.getOrCreateInstance(tab).show();
        }
    }

    function loadStatusPage(url, pushUrl) {
        const $intakeBody = $('#intakeRequestsBody');
        const $individualBody = $('#individualRequestsBody');
        $intakeBody.addClass('opacity-50');
        $individualBody.addClass('opacity-50');

        $.ajax({
            url: url,
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res && res.intake_html) {
                    $intakeBody.html(res.intake_html);
                }
                if (res && res.individual_html) {
                    $individualBody.html(res.individual_html);
                }
                if (pushUrl) {
                    history.pushState({ allClearanceAjax: true }, '', url);
                }
                keepStatusTab();
            },
            error: function () {
                showAlert('Error', 'Failed to load the next page.', 'error');
            },
            complete: function () {
                $intakeBody.removeClass('opacity-50');
                $individualBody.removeClass('opacity-50');
            }
        });
    }

    $(document).on('click', '#request-status .clearance-pagination a.page-link', function (e) {
        const href = $(this).attr('href');
        if (!href || href === '#' || $(this).closest('.page-item').hasClass('disabled') || $(this).closest('.page-item').hasClass('active')) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        loadStatusPage(href, true);
    });

    window.addEventListener('popstate', function () {
        if (!$('#request-status').length) {
            return;
        }
        loadStatusPage(window.location.href, false);
    });

    function showToast(message, type) {
        $('.toast').remove();
        const toast = `<div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">${escapeHtml(message)}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>`;
        $('.toast-container').append(toast);
        const toastElement = $('.toast').last();
        toastElement.toast({ delay: 3000, autohide: true });
        toastElement.toast('show');
        toastElement.on('hidden.bs.toast', function () { $(this).remove(); });
    }

    function renderPager($target, page, total, onPage) {
        if (!total) {
            $target.attr('hidden', true).empty();
            return;
        }
        const pageCount = Math.max(1, Math.ceil(total / perPage));
        const start = ((page - 1) * perPage) + 1;
        const end = Math.min(total, page * perPage);
        let nav = '';
        if (pageCount > 1) {
            nav = '<nav><ul class="pagination mb-0 flex-wrap justify-content-md-end">';
            nav += `<li class="page-item ${page <= 1 ? 'disabled' : ''}"><button type="button" class="page-link" data-page="${page - 1}">Previous</button></li>`;
            for (let i = 1; i <= pageCount; i++) {
                nav += `<li class="page-item ${i === page ? 'active' : ''}"><button type="button" class="page-link" data-page="${i}">${i}</button></li>`;
            }
            nav += `<li class="page-item ${page >= pageCount ? 'disabled' : ''}"><button type="button" class="page-link" data-page="${page + 1}">Next</button></li>`;
            nav += '</ul></nav>';
        }
        $target.html(`<small class="text-muted">Showing ${start}–${end} of ${total}</small>${nav}`).removeAttr('hidden');
        $target.find('.page-link').on('click', function () {
            const nextPage = Number($(this).data('page'));
            if (!nextPage || nextPage < 1 || nextPage > pageCount) {
                return;
            }
            onPage(nextPage);
        });
    }

    function renderStudentRows($tbody, rows, emptyText) {
        if (!rows.length) {
            $tbody.html(`<tr><td colspan="3" class="text-muted py-3">${escapeHtml(emptyText)}</td></tr>`);
            return;
        }
        $tbody.html(rows.map(function (student) {
            return `<tr><td>${escapeHtml(student.student_id)}</td><td>${escapeHtml(student.name)}</td><td>${escapeHtml(student.clearance_status)}</td></tr>`;
        }).join(''));
    }

    function renderIntakeStudents() {
        const start = (intakeStudentPage - 1) * perPage;
        renderStudentRows($('#studentListTableBody'), intakeStudents.slice(start, start + perPage), 'No students found for this intake.');
        renderPager($('#studentListPagination'), intakeStudentPage, intakeStudents.length, function (page) {
            intakeStudentPage = page;
            renderIntakeStudents();
        });
    }

    function resetSelect($select, placeholder, disabled) {
        $select.empty().append(`<option selected disabled value="">${placeholder}</option>`).prop('disabled', !!disabled);
    }

    function loadCoursesByLocation(location, $courseDropdown, $intakeDropdown, hideSections) {
        resetSelect($courseDropdown, 'Loading courses...', true);
        if ($intakeDropdown) {
            resetSelect($intakeDropdown, 'Select Intake', true);
        }
        hideSections();

        $.ajax({
            url: '{{ route('exam.results.courses.by.location') }}',
            method: 'GET',
            data: { location: location },
            success: function (response) {
                resetSelect($courseDropdown, 'Select Course', true);
                if (response && response.success && Array.isArray(response.courses) && response.courses.length) {
                    response.courses.forEach(function (course) {
                        $courseDropdown.append(`<option value="${escapeHtml(course.course_id)}">${escapeHtml(course.course_name)}</option>`);
                    });
                    $courseDropdown.prop('disabled', false);
                    return;
                }
                resetSelect($courseDropdown, 'No courses found for selected location', true);
                showAlert('No courses', 'No courses are included for the selected location.', 'info');
            },
            error: function () {
                resetSelect($courseDropdown, 'Failed to load courses', true);
                showAlert('Error', 'Failed to load courses for the selected location.', 'error');
            }
        });
    }

    function loadIntakes(courseId, location, $intakeDropdown, hideSections) {
        resetSelect($intakeDropdown, 'Select Intake', true);
        hideSections();
        if (!courseId || !location) {
            return;
        }

        $.ajax({
            url: '{{ route('module.management.getIntakes') }}',
            method: 'POST',
            data: { course_id: courseId, location: location, _token: '{{ csrf_token() }}' },
            success: function (response) {
                resetSelect($intakeDropdown, 'Select Intake', true);
                if (response && response.success && Array.isArray(response.data) && response.data.length) {
                    response.data.forEach(function (intake) {
                        $intakeDropdown.append(`<option value="${escapeHtml(intake.intake_id)}">${escapeHtml(intake.intake_name)}</option>`);
                    });
                    $intakeDropdown.prop('disabled', false);
                    return;
                }
                resetSelect($intakeDropdown, 'No intakes found', true);
                showAlert('No intakes', 'No intakes are included for this course and location.', 'info');
            },
            error: function () {
                resetSelect($intakeDropdown, 'Failed to load intakes', true);
                showAlert('Error', 'Failed to load intakes.', 'error');
            }
        });
    }

    function loadStudentList() {
        const intakeId = $('#intakeDropdown').val();
        const courseId = $('#courseDropdown').val();
        const location = $('#locationDropdown').val();
        if (!intakeId) {
            return;
        }

        $.ajax({
            url: '{{ route('clearance.getStudentsForIntake') }}',
            method: 'POST',
            data: {
                intake_id: intakeId,
                course_id: courseId,
                location: location,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                intakeStudents = (response && response.success && Array.isArray(response.data)) ? response.data : [];
                intakeStudentPage = 1;
                renderIntakeStudents();
            },
            error: function () {
                intakeStudents = [];
                intakeStudentPage = 1;
                renderIntakeStudents();
                showAlert('Error', 'Failed to load students for this intake.', 'error');
            }
        });
    }

    function hideIntakeSections() {
        $('#clearanceTableSection').attr('hidden', true);
        $('#studentListSection').attr('hidden', true);
        intakeStudents = [];
        intakeStudentPage = 1;
    }

    $('#locationDropdown').on('change', function () {
        const location = $('#locationDropdown').val();
        if (location) {
            loadCoursesByLocation(location, $('#courseDropdown'), $('#intakeDropdown'), hideIntakeSections);
        }
    });

    $('#courseDropdown').on('change', function () {
        loadIntakes($('#courseDropdown').val(), $('#locationDropdown').val(), $('#intakeDropdown'), hideIntakeSections);
    });

    $('#intakeDropdown').on('change', function () {
        if ($('#locationDropdown').val() && $('#courseDropdown').val() && $('#intakeDropdown').val()) {
            $('#clearanceTableSection').removeAttr('hidden');
            if (canViewIntakeStudents) {
                loadStudentList();
                $('#studentListSection').removeAttr('hidden');
            }
        } else {
            hideIntakeSections();
        }
    });

    $(document).on('click', '.send-clearance-btn', function () {
        const button = $(this);
        const type = button.data('type');
        const location = $('#locationDropdown').val();
        const courseId = $('#courseDropdown').val();
        const intakeId = $('#intakeDropdown').val();
        if (button.hasClass('loading')) {
            return;
        }
        if (!location || !courseId || !intakeId) {
            showAlert('Missing details', 'Please select Location, Course and Intake first.', 'warning');
            return;
        }

        button.addClass('loading').prop('disabled', true).text('Sending...');
        $.ajax({
            url: '{{ route('clearance.sendRequest') }}',
            method: 'POST',
            data: {
                type: type,
                location: location,
                course_id: courseId,
                intake_id: intakeId,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                showToast(response.message || 'Clearance request sent successfully!', 'success');
            },
            error: function () {
                showToast('Failed to send clearance request.', 'danger');
            },
            complete: function () {
                button.removeClass('loading').prop('disabled', false).text('Send');
            }
        });
    });

    function resetIndividualDownstream() {
        resetSelect($('#ind_intakeDropdown'), 'Select Intake', true);
        resetSelect($('#ind_studentDropdown'), 'Select Student', true);
        $('#individualClearanceTableSection').attr('hidden', true);
    }

    $('#ind_locationDropdown').on('change', function () {
        const location = $('#ind_locationDropdown').val();
        resetSelect($('#ind_courseDropdown'), 'Select Course', true);
        resetIndividualDownstream();
        if (!location) {
            return;
        }
        loadCoursesByLocation(location, $('#ind_courseDropdown'), $('#ind_intakeDropdown'), function () {
            resetSelect($('#ind_studentDropdown'), 'Select Student', true);
            $('#individualClearanceTableSection').attr('hidden', true);
        });
    });

    $('#ind_courseDropdown').on('change', function () {
        resetIndividualDownstream();
        loadIntakes($('#ind_courseDropdown').val(), $('#ind_locationDropdown').val(), $('#ind_intakeDropdown'), function () {
            resetSelect($('#ind_studentDropdown'), 'Select Student', true);
            $('#individualClearanceTableSection').attr('hidden', true);
        });
    });

    $('#ind_intakeDropdown').on('change', function () {
        const intakeId = $(this).val();
        $('#individualClearanceTableSection').attr('hidden', true);
        if (!intakeId) {
            return;
        }
        resetSelect($('#ind_studentDropdown'), 'Loading...', true);
        $.ajax({
            url: '{{ route('clearance.getStudentsForIntake') }}',
            method: 'POST',
            data: {
                intake_id: intakeId,
                course_id: $('#ind_courseDropdown').val(),
                location: $('#ind_locationDropdown').val(),
                _token: '{{ csrf_token() }}'
            },
            success: function (res) {
                resetSelect($('#ind_studentDropdown'), 'Select Student', true);
                if (res && res.success && Array.isArray(res.data) && res.data.length) {
                    res.data.forEach(function (student) {
                        $('#ind_studentDropdown').append(`<option value="${escapeHtml(student.student_id)}">${escapeHtml(student.student_id)} - ${escapeHtml(student.name)}</option>`);
                    });
                    $('#ind_studentDropdown').prop('disabled', false);
                    return;
                }
                resetSelect($('#ind_studentDropdown'), 'No students found', true);
                showAlert('No students', 'No students are included for this intake.', 'info');
            },
            error: function () {
                resetSelect($('#ind_studentDropdown'), 'Failed to load', true);
                showAlert('Error', 'Failed to load students.', 'error');
            }
        });
    });

    $('#ind_studentDropdown').on('change', function () {
        if ($(this).val()) {
            $('#individualClearanceTableSection').removeAttr('hidden');
        } else {
            $('#individualClearanceTableSection').attr('hidden', true);
        }
    });

    $(document).on('click', '.send-individual-clearance-btn', function () {
        const $btn = $(this);
        if ($btn.hasClass('loading')) {
            return;
        }
        const payload = {
            type: $btn.data('type'),
            location: $('#ind_locationDropdown').val(),
            course_id: $('#ind_courseDropdown').val(),
            intake_id: $('#ind_intakeDropdown').val(),
            student_id: $('#ind_studentDropdown').val(),
            _token: '{{ csrf_token() }}'
        };
        if (!payload.location || !payload.course_id || !payload.intake_id || !payload.student_id) {
            showAlert('Missing details', 'Please select Location, Course, Intake and Student first.', 'warning');
            return;
        }

        $btn.addClass('loading').prop('disabled', true).text('Sending...');
        $.ajax({
            url: '{{ route('clearance.sendRequest') }}',
            method: 'POST',
            data: payload,
            success: function (res) {
                showToast((res && res.message) ? res.message : 'Request sent', 'success');
            },
            error: function () {
                showToast('Failed to send the request', 'danger');
            },
            complete: function () {
                $btn.removeClass('loading').prop('disabled', false).text('Send');
            }
        });
    });

    function renderDetailsStudents() {
        const start = (detailsStudentPage - 1) * perPage;
        const pageRows = detailsStudents.slice(start, start + perPage);
        const $tbody = $('#intakeDetailsTableBody');
        if (!$tbody.length) {
            return;
        }
        if (!pageRows.length) {
            $tbody.html('<tr><td colspan="6" class="text-muted py-3">No students found.</td></tr>');
        } else {
            $tbody.html(pageRows.map(function (student) {
                return `<tr>
                    <td>${escapeHtml(student.student_id)}</td>
                    <td>${escapeHtml(student.student_name)}</td>
                    <td><span class="badge bg-${escapeHtml(student.status_color)}">${escapeHtml(student.status_text)}</span></td>
                    <td>${escapeHtml(student.processed_by || 'N/A')}</td>
                    <td>${escapeHtml(student.processed_date || 'N/A')}</td>
                    <td>${escapeHtml(student.remarks || 'N/A')}</td>
                </tr>`;
            }).join(''));
        }
        renderPager($('#intakeDetailsPagination'), detailsStudentPage, detailsStudents.length, function (page) {
            detailsStudentPage = page;
            renderDetailsStudents();
        });
    }

    $(document).on('click', '.view-intake-details', function () {
        const intakeId = $(this).data('intake');
        const courseId = $(this).data('course');
        const location = $(this).data('location');
        const type = $(this).data('type');

        $('#intakeDetailsContent').html('<div class="text-center"><i class="ti ti-loader ti-spin" style="font-size: 2rem;"></i><p>Loading details...</p></div>');
        $('#intakeDetailsModal').modal('show');

        $.ajax({
            url: '{{ route("clearance.getIntakeDetails") }}',
            method: 'POST',
            data: {
                intake_id: intakeId,
                course_id: courseId,
                location: location,
                clearance_type: type,
                _token: '{{ csrf_token() }}'
            },
            success: function (response) {
                if (response.success) {
                    detailsStudents = Array.isArray(response.students) ? response.students : [];
                    detailsStudentPage = 1;
                    $('#intakeDetailsContent').html(`
                        <div class="row mb-3">
                            <div class="col-12">
                                <h6 class="text-muted mb-0">Intake: ${escapeHtml(response.intake_name)} | Course: ${escapeHtml(response.course_name)} | Location: ${escapeHtml(response.location)}</h6>
                            </div>
                        </div>
                        <div class="all-clearance-table-scroll">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Student Name</th>
                                        <th>Status</th>
                                        <th>Processed By</th>
                                        <th>Processed Date</th>
                                        <th>Remarks</th>
                                    </tr>
                                </thead>
                                <tbody id="intakeDetailsTableBody"></tbody>
                            </table>
                        </div>
                        <div id="intakeDetailsPagination" class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mt-3 clearance-pagination" hidden></div>
                    `);
                    renderDetailsStudents();
                } else {
                    $('#intakeDetailsContent').html('<div class="alert alert-danger">Failed to load details: ' + escapeHtml(response.message) + '</div>');
                }
            },
            error: function () {
                $('#intakeDetailsContent').html('<div class="alert alert-danger">Failed to load details. Please try again.</div>');
            }
        });
    });
});
</script>
<style nonce="{{ $cspNonce }}">
.all-clearance-page [class*="col-"] { min-width: 0; }
.all-clearance-page .form-select,
.all-clearance-page .form-control,
.all-clearance-page .nebula-select { max-width: 100%; }
.exam-results-tabs { display: flex; flex-wrap: wrap; overflow: visible; }
.exam-results-tabs .nav-item { flex: 0 0 auto; }
.all-clearance-table-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.all-clearance-table-scroll table { min-width: 640px; }
.all-clearance-thead { background-color: #5D9CFF; color: #fff; }
.all-clearance-page .view-intake-details { white-space: nowrap; }
.clearance-pagination .pagination { margin-bottom: 0; flex-wrap: wrap; }
@media (max-width: 767.98px) {
    .all-clearance-page .card-body { padding: 1rem 0.75rem; }
    .all-clearance-page h2 { font-size: 1.25rem; }
    .all-clearance-page .form-control,
    .all-clearance-page .form-select,
    .all-clearance-page .nebula-select-toggle { font-size: 16px; }
    .all-clearance-page .send-clearance-btn,
    .all-clearance-page .send-individual-clearance-btn,
    .all-clearance-page .view-intake-details { width: 100%; }
    .all-clearance-table-scroll table { min-width: 720px; }
}
</style>
@endpush
