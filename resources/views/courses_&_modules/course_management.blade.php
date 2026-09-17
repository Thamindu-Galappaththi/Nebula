@extends('inc.app')

@section('title', 'NEBULA | Course Management')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<style nonce="{{ $cspNonce }}">
    .course-management-page .nebula-select {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .course-list-header { gap: 0.75rem; }
    .course-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #coursesTable {
        min-width: 980px;
        margin-bottom: 0;
    }
    .course-actions {
        display: inline-flex;
        flex-wrap: nowrap;
        gap: 0.35rem;
    }
    .course-pagination-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .course-pagination-bar .pagination {
        margin-bottom: 0;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .duration-fields {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .duration-fields .form-control {
        min-width: 0;
        flex: 1 1 6rem;
    }
    .specialization-inputs .input-group {
        flex-wrap: nowrap;
        align-items: stretch;
    }
    .specialization-inputs .specialization-input {
        min-width: 0;
    }
    .remove-specialization {
        flex: 0 0 auto !important;
        width: auto;
        min-width: 2rem;
        padding: 0.15rem 0.45rem;
        font-size: 0.75rem;
        line-height: 1;
        white-space: nowrap;
    }
    .record-edit-modal .remove-specialization {
        min-height: 31px;
        height: 31px;
    }
    .swal2-container { z-index: 20000; }
    .record-edit-modal .modal-dialog {
        max-width: min(1080px, calc(100vw - 1.25rem));
        margin: 0.5rem auto;
    }
    .record-edit-modal .modal-content {
        max-height: calc(100vh - 1rem);
        overflow: hidden;
    }
    .record-edit-modal .modal-content > form {
        display: flex;
        flex-direction: column;
        min-height: 0;
        max-height: calc(100vh - 1rem);
        overflow: hidden;
    }
    .record-edit-modal .modal-header,
    .record-edit-modal .modal-footer {
        flex: 0 0 auto;
    }
    .record-edit-modal .modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        padding: 0.65rem 1rem 0.35rem;
    }
    .record-edit-modal .form-label {
        font-size: 0.75rem;
        font-weight: 600;
        margin-bottom: 0.15rem;
    }
    .record-edit-modal .form-control,
    .record-edit-modal .form-select,
    .record-edit-modal .nebula-select-toggle {
        min-height: 31px;
        height: 31px;
        padding: 0.15rem 0.55rem;
        font-size: 0.8125rem;
    }
    .record-edit-modal textarea.form-control {
        height: auto;
        min-height: 2.6rem;
        padding-top: 0.3rem;
        padding-bottom: 0.3rem;
    }
    .record-edit-modal .nebula-select {
        width: 100%;
        max-width: 100%;
    }
    .record-edit-modal .duration-fields {
        gap: 0.35rem;
    }
    .record-edit-modal .duration-fields .form-control {
        flex: 1 1 0;
        min-width: 3.75rem;
    }
    .record-edit-modal .btn-sm {
        padding: 0.2rem 0.5rem;
        font-size: 0.75rem;
    }
    @media (max-width: 575.98px) {
        .record-edit-modal .modal-content,
        .record-edit-modal .modal-content > form {
            max-height: 100%;
            height: 100%;
        }
    }
    @media (max-width: 991.98px) {
        .course-list-header {
            flex-direction: column;
            align-items: stretch !important;
        }
        .course-list-header .btn { width: 100%; }
        #coursesTable { min-width: 0; }
        #coursesTable thead { display: none; }
        #coursesTable,
        #coursesTable tbody,
        #coursesTable tr,
        #coursesTable td {
            display: block;
            width: 100%;
        }
        #coursesTable tr[data-course-id] {
            margin-bottom: 0.85rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 0.75rem 0.9rem;
            background: #fff;
        }
        #coursesTable td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            border: 0;
            border-bottom: 1px solid #f1f3f5;
            padding: 0.45rem 0;
        }
        #coursesTable td:last-child { border-bottom: 0; }
        #coursesTable td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            flex: 0 0 38%;
            max-width: 38%;
        }
        #coursesTable td[data-label=""]::before,
        #coursesTable td.course-select-cell::before { display: none; }
        #coursesTable td.course-select-cell,
        #coursesTable td.course-actions-cell {
            justify-content: flex-end;
            align-items: center;
        }
        #coursesTable .empty-row td {
            display: block;
            text-align: center;
            border: 0;
        }
        #coursesTable .empty-row td::before { display: none; }
        .course-pagination-bar { flex-direction: column; align-items: stretch; }
        .course-pagination-bar .pagination { justify-content: center; }
    }
</style>

<div class="container-fluid px-2 px-md-3 course-management-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Create New Courses</h2>
            <hr>
            <form id="courseForm">
                @csrf
                <div class="row g-2 g-md-3 align-items-md-center mb-3">
                    <label for="location" class="col-12 col-md-3 col-lg-2 col-form-label">Location <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9 col-lg-10">
                        <select class="form-select" id="location" name="location" required>
                            <option selected disabled value="">Choose a location...</option>
                            <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                            <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                            <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 g-md-3 align-items-md-center mb-3">
                    <label for="course_type" class="col-12 col-md-3 col-lg-2 col-form-label">Course Type <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9 col-lg-10">
                        <select class="form-select" id="course_type" name="course_type" required>
                            <option selected disabled value="">Choose course type...</option>
                            <option value="degree">Degree Program</option>
                            <option value="diploma">Diploma Program</option>
                            <option value="certificate">Certificate Program</option>
                        </select>
                    </div>
                </div>

                <div id="degree_program_fields" hidden>
                    @include('courses_&_modules.partials.course_degree_fields', ['prefix' => '', 'required' => true])
                </div>
                <div id="certificate_program_fields" hidden>
                    @include('courses_&_modules.partials.course_certificate_fields', ['prefix' => 'cert_', 'required' => true])
                </div>

                <div class="d-grid d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary" id="submitBtn">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 course-list-header">
                <h2 class="mb-0">Existing Courses</h2>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDeleteCourseBtn" hidden>
                        <i class="ti ti-trash"></i> Delete Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" id="exportCourseBtn">
                        <i class="ti ti-download"></i> Export CSV
                    </button>
                </div>
            </div>
            <hr>

            <form id="courseFilterForm" method="GET" action="{{ route('course.management') }}" class="row g-2 g-md-3 align-items-end mb-3">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted" for="searchCourseInput">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="searchCourseInput" name="search" placeholder="Search courses..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small text-muted" for="filterCourseType">Type</label>
                    <select class="form-select" id="filterCourseType" name="course_type">
                        <option value="">All Types</option>
                        <option value="degree" @selected(($filters['course_type'] ?? '') === 'degree')>Degree</option>
                        <option value="diploma" @selected(($filters['course_type'] ?? '') === 'diploma')>Diploma</option>
                        <option value="certificate" @selected(($filters['course_type'] ?? '') === 'certificate')>Certificate</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small text-muted" for="filterLocation">Location</label>
                    <select class="form-select" id="filterLocation" name="location">
                        <option value="">All Locations</option>
                        <option value="Welisara" @selected(($filters['location'] ?? '') === 'Welisara')>Welisara</option>
                        <option value="Moratuwa" @selected(($filters['location'] ?? '') === 'Moratuwa')>Moratuwa</option>
                        <option value="Peradeniya" @selected(($filters['location'] ?? '') === 'Peradeniya')>Peradeniya</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small text-muted" for="perPageCourseSelect">Per page</label>
                    <select class="form-select" id="perPageCourseSelect" name="per_page">
                        <option value="10" @selected((int) $perPage === 10)>10 per page</option>
                        <option value="25" @selected((int) $perPage === 25)>25 per page</option>
                        <option value="50" @selected((int) $perPage === 50)>50 per page</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2 d-grid gap-2">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <button class="btn btn-outline-secondary" type="button" id="clearCourseFiltersBtn">Clear</button>
                </div>
            </form>

            <div class="d-flex justify-content-end align-items-center mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAllCourses">
                    <label class="form-check-label" for="selectAllCourses"><small>Select All</small></label>
                </div>
            </div>

            <div class="course-table-scroll">
                <table class="table table-striped table-bordered table-hover" id="coursesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAllCoursesHeader" class="form-check-input" aria-label="Select all courses">
                            </th>
                            <th>Course Name</th>
                            <th>Course Type</th>
                            <th>Location</th>
                            <th>Duration</th>
                            <th>Medium</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="existingCoursesTableBody">
                        @include('courses_&_modules.partials.course_rows')
                    </tbody>
                </table>
            </div>
            <div id="coursePagination">
                @include('courses_&_modules.partials.course_pagination')
            </div>
        </div>
    </div>
</div>

<div class="modal fade record-edit-modal" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form id="editCourseForm">
                @csrf
                <input type="hidden" id="edit_course_id">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="editCourseModalLabel">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-12 col-md-6">
                            <label for="edit_location" class="form-label">Location <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_location" name="location" required>
                                <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                                <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                                <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit_course_type" class="form-label">Course Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_course_type" name="course_type" required>
                                <option value="degree">Degree Program</option>
                                <option value="diploma">Diploma Program</option>
                                <option value="certificate">Certificate Program</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <div id="edit_degree_program_fields" class="row g-2" hidden>
                                @include('courses_&_modules.partials.course_degree_fields', ['prefix' => 'edit_', 'required' => true, 'layout' => 'modal'])
                            </div>
                            <div id="edit_certificate_program_fields" class="row g-2" hidden>
                                @include('courses_&_modules.partials.course_certificate_fields', ['prefix' => 'edit_cert_', 'required' => true, 'layout' => 'modal'])
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
<script nonce="{{ $cspNonce }}">
$(function () {
    const csrfToken = '{{ csrf_token() }}';
    const listUrl = '{{ route("course.management") }}';
    const storeUrl = '{{ route("course.store") }}';
    const showUrlTemplate = '{{ url("/api/courses") }}';
    const updateUrlTemplate = '{{ url("/courses") }}';
    const exportUrl = '{{ route("course.export") }}';
    const editModalEl = document.getElementById('editCourseModal');
    const editModal = editModalEl && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(editModalEl) : null;

    function showAlert(title, text, icon) {
        if (window.Swal) {
            return Swal.fire({ title: title, text: text, icon: icon, confirmButtonText: 'OK' });
        }
        window.alert(text);
        return Promise.resolve();
    }

    function confirmDelete(title, text) {
        if (!window.Swal) {
            return Promise.resolve(window.confirm(text));
        }
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true
        }).then(function (result) { return result.isConfirmed; });
    }

    function validationMessage(xhr) {
        if (xhr.responseJSON && xhr.responseJSON.errors) {
            return Object.values(xhr.responseJSON.errors).flat().join(' ');
        }
        return (xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred.';
    }

    function setSectionEnabled($section, enabled) {
        $section.prop('hidden', !enabled);
        $section.find('input, select, textarea').prop('disabled', !enabled);
    }

    function applyCreateCourseType(type, applyDefaults) {
        if (type === 'degree' || type === 'diploma') {
            setSectionEnabled($('#degree_program_fields'), true);
            setSectionEnabled($('#certificate_program_fields'), false);
            if (applyDefaults) {
                $('#duration_years').val(type === 'diploma' ? 2 : 3);
                $('#duration_months').val(0);
                $('#duration_days').val(0);
            }
        } else if (type === 'certificate') {
            setSectionEnabled($('#degree_program_fields'), false);
            setSectionEnabled($('#certificate_program_fields'), true);
            if (applyDefaults) {
                $('#cert_duration_years').val(1);
                $('#cert_duration_months').val(0);
                $('#cert_duration_days').val(0);
            }
        } else {
            setSectionEnabled($('#degree_program_fields'), false);
            setSectionEnabled($('#certificate_program_fields'), false);
        }
    }

    function applyEditCourseType(type) {
        if (type === 'degree' || type === 'diploma') {
            setSectionEnabled($('#edit_degree_program_fields'), true);
            setSectionEnabled($('#edit_certificate_program_fields'), false);
        } else if (type === 'certificate') {
            setSectionEnabled($('#edit_degree_program_fields'), false);
            setSectionEnabled($('#edit_certificate_program_fields'), true);
        } else {
            setSectionEnabled($('#edit_degree_program_fields'), false);
            setSectionEnabled($('#edit_certificate_program_fields'), false);
        }
    }

    function syncNebulaSelect(select) {
        if (!select) return;
        const selected = select.options[select.selectedIndex];
        const wrap = select.closest('.nebula-select');
        const toggle = wrap ? wrap.querySelector('.nebula-select-toggle') : null;
        if (toggle) {
            toggle.textContent = selected ? selected.text : '';
            toggle.title = toggle.textContent;
        }
    }

    function setSelectValue(select, value) {
        if (!select) return;
        select.value = value == null ? '' : String(value);
        syncNebulaSelect(select);
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    setSectionEnabled($('#degree_program_fields'), false);
    setSectionEnabled($('#certificate_program_fields'), false);

    $('#course_type').on('change', function () {
        applyCreateCourseType($(this).val(), true);
    });
    $('#edit_course_type').on('change', function () {
        applyEditCourseType($(this).val());
    });

    $(document).on('change', 'input[name="has_specialization"]', function () {
        const fields = $(this).closest('form').find('.specialization-fields');
        fields.prop('hidden', $(this).val() !== 'yes');
    });

    $(document).on('click', '.add-specialization-btn', function () {
        const wrap = $(this).closest('form').find('.specialization-inputs').first();
        wrap.append(
            '<div class="input-group mb-2">' +
            '<input type="text" class="form-control specialization-input" name="specializations[]" placeholder="Enter specialization name">' +
            '<button type="button" class="btn btn-outline-danger btn-sm remove-specialization" title="Remove" aria-label="Remove"><i class="ti ti-x"></i></button>' +
            '</div>'
        );
        updateRemoveButtons(wrap);
    });

    $(document).on('click', '.remove-specialization', function () {
        const wrap = $(this).closest('.specialization-inputs');
        $(this).closest('.input-group').remove();
        updateRemoveButtons(wrap);
    });

    function updateRemoveButtons(wrap) {
        const count = wrap.find('.input-group').length;
        wrap.find('.remove-specialization').each(function () {
            $(this).prop('hidden', count <= 1);
        });
    }

    $(document).on('change', '.conducted-by-select', function () {
        const $other = $(this).siblings('.other-conducted-by');
        if ($(this).val() === 'Other') {
            $other.prop('hidden', false).prop('required', true);
        } else {
            $other.prop('hidden', true).prop('required', false).val('');
        }
    });

    function currentListUrl() {
        const params = new URLSearchParams($('#courseFilterForm').serialize());
        const query = params.toString();
        return query ? (listUrl + '?' + query) : listUrl;
    }

    function loadCourses(url, pushUrl) {
        const $body = $('#existingCoursesTableBody');
        const $pager = $('#coursePagination');
        $body.addClass('opacity-50');
        $pager.addClass('opacity-50');
        $.ajax({
            url: url,
            method: 'GET',
            dataType: 'json',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            success: function (res) {
                if (res && res.html) $body.html(res.html);
                if (res && res.pagination) $pager.html(res.pagination);
                $('#selectAllCourses, #selectAllCoursesHeader').prop('checked', false);
                updateBulkDeleteButton();
                if (pushUrl) history.pushState({ courseAjax: true }, '', url);
            },
            error: function () {
                showAlert('Error', 'Failed to load courses.', 'error');
            },
            complete: function () {
                $body.removeClass('opacity-50');
                $pager.removeClass('opacity-50');
            }
        });
    }

    $('#courseFilterForm').on('submit', function (e) {
        e.preventDefault();
        loadCourses(currentListUrl(), true);
    });

    $('#perPageCourseSelect').on('change', function () {
        loadCourses(currentListUrl(), true);
    });

    function syncNebulaSelects(root) {
        $(root).find('select').each(function () {
            const wrap = this.closest('.nebula-select');
            const toggle = wrap ? wrap.querySelector('.nebula-select-toggle') : null;
            if (!toggle) return;
            const selected = this.options[this.selectedIndex];
            toggle.textContent = selected ? selected.text : '';
            toggle.title = toggle.textContent;
        });
    }

    function resetFilterSelect(select, value) {
        if (!select) return;
        if (value === undefined || value === '') {
            select.value = '';
            select.selectedIndex = 0;
        } else {
            select.value = String(value);
        }
        syncNebulaSelect(select);
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    $('#clearCourseFiltersBtn').on('click', function () {
        $('#searchCourseInput').val('');
        resetFilterSelect(document.getElementById('filterCourseType'), '');
        resetFilterSelect(document.getElementById('filterLocation'), '');
        resetFilterSelect(document.getElementById('perPageCourseSelect'), '10');
    });

    $(document).on('click', '#coursePagination .pagination a.page-link', function (e) {
        const href = $(this).attr('href');
        if (!href || href === '#' || $(this).closest('.page-item').hasClass('disabled') || $(this).closest('.page-item').hasClass('active')) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        loadCourses(href, true);
    });

    window.addEventListener('popstate', function () {
        if (!$('.course-management-page').length) return;
        loadCourses(window.location.href, false);
    });

    function updateBulkDeleteButton() {
        const count = $('.course-checkbox:checked').length;
        if (count > 0) {
            $('#bulkDeleteCourseBtn').prop('hidden', false).html('<i class="ti ti-trash"></i> Delete Selected (' + count + ')');
        } else {
            $('#bulkDeleteCourseBtn').prop('hidden', true);
        }
    }

    $('#selectAllCourses, #selectAllCoursesHeader').on('change', function () {
        const isChecked = $(this).prop('checked');
        $('#selectAllCourses, #selectAllCoursesHeader').prop('checked', isChecked);
        $('.course-checkbox').prop('checked', isChecked);
        updateBulkDeleteButton();
    });

    $(document).on('change', '.course-checkbox', function () {
        const total = $('.course-checkbox').length;
        const checked = $('.course-checkbox:checked').length;
        $('#selectAllCourses, #selectAllCoursesHeader').prop('checked', total > 0 && total === checked);
        updateBulkDeleteButton();
    });

    function conductedByValue($select, $other) {
        return $select.val() === 'Other' ? $other.val() : $select.val();
    }

    $('#courseForm').on('submit', function (e) {
        e.preventDefault();
        const type = $('#course_type').val();
        if (!type) {
            showAlert('Missing type', 'Please select a course type.', 'warning');
            return;
        }
        const formData = new FormData(this);
        if (type === 'certificate') {
            formData.set('conducted_by', conductedByValue($('#cert_conducted_by'), $('#cert_other_conducted_by')));
        } else {
            formData.set('conducted_by', conductedByValue($('#conducted_by'), $('#other_conducted_by')));
        }
        formData.delete('other_conducted_by');
        formData.delete('has_specialization');

        $.ajax({
            url: storeUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    $('#courseForm')[0].reset();
                    $('#courseForm .specialization-fields').prop('hidden', true);
                    $('#courseForm .other-conducted-by').prop('hidden', true).prop('required', false).val('');
                    applyCreateCourseType('', false);
                    syncNebulaSelects('#courseForm');
                    showAlert('Created', response.message, 'success').then(function () {
                        loadCourses(currentListUrl(), false);
                    });
                } else {
                    showAlert('Error', response.message || 'Could not create the course.', 'error');
                }
            },
            error: function (xhr) {
                showAlert('Error', validationMessage(xhr), 'error');
            }
        });
    });

    function parseSpecializations(value) {
        if (!value) return [];
        if (Array.isArray(value)) return value.filter(function (spec) { return spec && String(spec).trim() !== ''; });
        try {
            const parsed = JSON.parse(value);
            return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
        } catch (e) {
            return [];
        }
    }

    function fillSpecializationInputs($wrap, specs) {
        if (!$wrap.length) return;
        if (!specs.length) {
            $wrap.html('<div class="input-group mb-2"><input type="text" class="form-control specialization-input" name="specializations[]" placeholder="Enter specialization name"><button type="button" class="btn btn-outline-danger btn-sm remove-specialization" title="Remove" aria-label="Remove" hidden><i class="ti ti-x"></i></button></div>');
            return;
        }
        $wrap.html(specs.map(function (spec) {
            const safe = String(spec)
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/</g, '&lt;');
            return '<div class="input-group mb-2"><input type="text" class="form-control specialization-input" name="specializations[]" value="' + safe + '" placeholder="Enter specialization name"><button type="button" class="btn btn-outline-danger btn-sm remove-specialization" title="Remove" aria-label="Remove"><i class="ti ti-x"></i></button></div>';
        }).join(''));
        updateRemoveButtons($wrap);
    }

    function setConductedBy(selectId, otherId, value) {
        const select = document.getElementById(selectId);
        const $other = $('#' + otherId);
        if (!select) return;
        const hasOption = Array.from(select.options).some(function (opt) { return opt.value === value; });
        if (hasOption) {
            setSelectValue(select, value);
        } else {
            setSelectValue(select, 'Other');
            $other.prop('hidden', false).prop('required', true).val(value || '');
        }
    }

    $(document).on('click', '.edit-course-btn', function () {
        const courseId = $(this).data('course-id');
        $.ajax({
            url: showUrlTemplate + '/' + courseId,
            type: 'GET',
            success: function (response) {
                if (!response.success || !response.course) {
                    showAlert('Error', 'Failed to fetch course details.', 'error');
                    return;
                }
                const course = response.course;
                $('#edit_course_id').val(course.course_id);
                setSelectValue(document.getElementById('edit_location'), course.location);
                setSelectValue(document.getElementById('edit_course_type'), course.course_type);
                applyEditCourseType(course.course_type);

                const duration = course.duration || {};
                const training = course.training_period || {};
                if (course.course_type === 'certificate') {
                    $('#edit_cert_course_name').val(course.course_name);
                    setSelectValue(document.getElementById('edit_cert_course_medium'), course.course_medium);
                    setConductedBy('edit_cert_conducted_by', 'edit_cert_other_conducted_by', course.conducted_by);
                    $('#edit_cert_duration_years').val(duration.years ?? 0);
                    $('#edit_cert_duration_months').val(duration.months ?? 0);
                    $('#edit_cert_duration_days').val(duration.days ?? 0);
                    $('#edit_cert_training_years').val(training.years ?? 0);
                    $('#edit_cert_training_months').val(training.months ?? 0);
                    $('#edit_cert_training_days').val(training.days ?? 0);
                    $('#edit_cert_course_content').val(course.course_content || '');
                    $('#edit_cert_entry_qualification').val(course.entry_qualification || '');
                } else {
                    $('#edit_course_name').val(course.course_name);
                    setSelectValue(document.getElementById('edit_course_medium'), course.course_medium);
                    setConductedBy('edit_conducted_by', 'edit_other_conducted_by', course.conducted_by);
                    $('#edit_duration_years').val(duration.years ?? 0);
                    $('#edit_duration_months').val(duration.months ?? 0);
                    $('#edit_duration_days').val(duration.days ?? 0);
                    $('#edit_no_of_semesters').val(course.no_of_semesters || '');
                    setSelectValue(document.getElementById('edit_semester_format'), course.semester_format || 'numerical');
                    $('#edit_training_years').val(training.years ?? 0);
                    $('#edit_training_months').val(training.months ?? 0);
                    $('#edit_training_days').val(training.days ?? 0);
                    $('#edit_min_credits').val(course.min_credits || '');
                    $('#edit_entry_qualification').val(course.entry_qualification || '');
                    const specs = parseSpecializations(course.specializations);
                    if (specs.length) {
                        $('#edit_specializationYes').prop('checked', true);
                        $('#edit_specializationFields').prop('hidden', false);
                    } else {
                        $('#edit_specializationNo').prop('checked', true);
                        $('#edit_specializationFields').prop('hidden', true);
                    }
                    fillSpecializationInputs($('#edit_specializationInputs'), specs);
                }
                if (editModal) {
                    editModal.show();
                    setTimeout(function () { syncNebulaSelects('#editCourseForm'); }, 50);
                }
            },
            error: function () {
                showAlert('Error', 'Error fetching course details.', 'error');
            }
        });
    });

    $('#editCourseForm').on('submit', function (e) {
        e.preventDefault();
        const courseId = $('#edit_course_id').val();
        const type = $('#edit_course_type').val();
        const formData = new FormData(this);
        if (type === 'certificate') {
            formData.set('conducted_by', conductedByValue($('#edit_cert_conducted_by'), $('#edit_cert_other_conducted_by')));
        } else {
            formData.set('conducted_by', conductedByValue($('#edit_conducted_by'), $('#edit_other_conducted_by')));
        }
        formData.delete('other_conducted_by');
        formData.delete('has_specialization');

        $.ajax({
            url: updateUrlTemplate + '/' + courseId + '/update',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    if (editModal) editModal.hide();
                    showAlert('Updated', response.message, 'success').then(function () {
                        loadCourses(window.location.href, false);
                    });
                } else {
                    showAlert('Error', response.message || 'Could not update the course.', 'error');
                }
            },
            error: function (xhr) {
                showAlert('Error', validationMessage(xhr), 'error');
            }
        });
    });

    $(document).on('click', '.delete-course-btn', function () {
        const courseId = $(this).data('course-id');
        const name = $(this).closest('tr').find('.course-name').text().trim();
        confirmDelete('Delete course?', 'Delete "' + name + '"? This cannot be undone.').then(function (ok) {
            if (!ok) return;
            $.ajax({
                url: updateUrlTemplate + '/' + courseId,
                type: 'DELETE',
                data: { _token: csrfToken },
                success: function (response) {
                    if (response.success) {
                        showAlert('Deleted', response.message, 'success').then(function () {
                            loadCourses(window.location.href, false);
                        });
                    } else {
                        showAlert('Error', response.message || 'Could not delete the course.', 'error');
                    }
                },
                error: function (xhr) {
                    showAlert('Error', validationMessage(xhr), 'error');
                }
            });
        });
    });

    $('#bulkDeleteCourseBtn').on('click', function () {
        const selectedIds = [];
        $('.course-checkbox:checked').each(function () {
            selectedIds.push($(this).data('course-id'));
        });
        if (!selectedIds.length) return;
        confirmDelete('Delete selected courses?', 'Delete ' + selectedIds.length + ' course(s)? This cannot be undone.').then(function (ok) {
            if (!ok) return;
            $.ajax({
                url: '{{ route("course.bulkDestroy") }}',
                type: 'POST',
                data: { _token: csrfToken, ids: selectedIds },
                success: function (response) {
                    showAlert(response.success ? 'Deleted' : 'Error', response.message, response.success ? 'success' : 'error').then(function () {
                        loadCourses(window.location.href, false);
                    });
                },
                error: function (xhr) {
                    showAlert('Error', validationMessage(xhr), 'error');
                }
            });
        });
    });

    $('#exportCourseBtn').on('click', function () {
        const params = new URLSearchParams();
        const search = $.trim($('#searchCourseInput').val() || '');
        const type = $('#filterCourseType').val() || '';
        const location = $('#filterLocation').val() || '';
        if (search) params.set('search', search);
        if (type) params.set('course_type', type);
        if (location) params.set('location', location);
        window.location.assign(params.toString() ? (exportUrl + '?' + params.toString()) : exportUrl);
    });
});
</script>
@endpush

