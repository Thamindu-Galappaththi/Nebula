@extends('inc.app')

@section('title', 'NEBULA | Intake Creation')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<style nonce="{{ $cspNonce }}">
    .intake-creation-page .nebula-select {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .intake-list-header { gap: 0.75rem; }
    .intake-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #intakesTable {
        min-width: 1100px;
        margin-bottom: 0;
    }
    .intake-actions {
        display: inline-flex;
        flex-wrap: nowrap;
        gap: 0.35rem;
    }
    .intake-pagination-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        margin-top: 1rem;
    }
    .intake-pagination-bar .pagination {
        margin-bottom: 0;
        flex-wrap: wrap;
        justify-content: flex-end;
    }
    .currency-highlight {
        background: linear-gradient(135deg, #fff3cd 0%, #fffacd 100%) !important;
        border: 2px solid #ffc107 !important;
        color: #856404 !important;
        font-weight: 600 !important;
    }
    .locked-field {
        background-color: #f1f3f5 !important;
        cursor: not-allowed;
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
    .record-edit-modal .input-group .nebula-select {
        flex: 0 0 5.25rem;
        max-width: 5.25rem;
    }
    .record-edit-modal .input-group .form-control,
    .record-edit-modal .input-group .form-select,
    .record-edit-modal .input-group .nebula-select-toggle {
        min-height: 31px;
        height: 31px;
    }
    .selected-modules-box {
        max-width: 100%;
        overflow: hidden;
    }
    .selected-modules-list {
        display: flex;
        flex-wrap: wrap;
        gap: 0.4rem;
        max-width: 100%;
    }
    .badge.module-chip {
        display: inline-flex;
        align-items: flex-start;
        gap: 0.35rem;
        max-width: 100%;
        white-space: normal;
        text-align: left;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1.35;
        padding: 0.3rem 0.45rem;
    }
    .badge.module-chip span {
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .badge.module-chip .btn-close {
        flex: 0 0 auto;
        width: 0.55rem;
        height: 0.55rem;
        margin-top: 0.15rem;
        opacity: 0.85;
    }
    @media (max-width: 575.98px) {
        .record-edit-modal .modal-content,
        .record-edit-modal .modal-content > form {
            max-height: 100%;
            height: 100%;
        }
    }
    @media (max-width: 991.98px) {
        .intake-list-header {
            flex-direction: column;
            align-items: stretch !important;
        }
        .intake-list-header .btn { width: 100%; }
        #intakesTable { min-width: 0; }
        #intakesTable thead { display: none; }
        #intakesTable,
        #intakesTable tbody,
        #intakesTable tr,
        #intakesTable td {
            display: block;
            width: 100%;
        }
        #intakesTable tr[data-intake-id] {
            margin-bottom: 0.85rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 0.75rem 0.9rem;
            background: #fff;
        }
        #intakesTable td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            border: 0;
            border-bottom: 1px solid #f1f3f5;
            padding: 0.45rem 0;
        }
        #intakesTable td:last-child { border-bottom: 0; }
        #intakesTable td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            flex: 0 0 38%;
            max-width: 38%;
        }
        #intakesTable td[data-label=""]::before,
        #intakesTable td.intake-select-cell::before { display: none; }
        #intakesTable td.intake-select-cell,
        #intakesTable td.intake-actions-cell {
            justify-content: flex-end;
            align-items: center;
        }
        #intakesTable .empty-row td {
            display: block;
            text-align: center;
            border: 0;
        }
        #intakesTable .empty-row td::before { display: none; }
        .intake-pagination-bar { flex-direction: column; align-items: stretch; }
        .intake-pagination-bar .pagination { justify-content: center; }
    }
</style>

<div class="container-fluid px-2 px-md-3 intake-creation-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Create New Intake</h2>
            <hr>
            <form id="intakeForm">
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
                        <select class="form-select locked-field" id="course_type" name="course_type" required disabled>
                            <option selected disabled value="">Choose course type...</option>
                            <option value="degree">Degree Program</option>
                            <option value="diploma">Diploma Program</option>
                            <option value="certificate">Certificate Program</option>
                        </select>
                    </div>
                </div>

                <div id="intake_fields_container" hidden>
                    <div class="row g-2 g-md-3 align-items-md-center mb-3">
                        <label for="course_id" class="col-12 col-md-3 col-lg-2 col-form-label">Course <span class="text-danger">*</span></label>
                        <div class="col-12 col-md-9 col-lg-10">
                            <select class="form-select" id="course_id" name="course_id" required>
                                <option selected disabled value="">Choose a course...</option>
                            </select>
                        </div>
                    </div>
                    <div id="courseDetailsBox" class="mb-3" hidden>
                        <div class="p-3 rounded" style="background:#ededed;">
                            <div><b>Conducted By</b> <span id="cd_conducted_by"></span></div>
                            <div><b>Minimum credits</b> <span id="cd_min_credits"></span></div>
                            <div><b>Medium</b> <span id="cd_medium"></span></div>
                        </div>
                    </div>
                    <div class="row g-2 g-md-3 align-items-md-center mb-3">
                        <label for="batch" class="col-12 col-md-3 col-lg-2 col-form-label">Batch Name / Code <span class="text-danger">*</span></label>
                        <div class="col-12 col-md-9 col-lg-10">
                            <input type="text" class="form-control" id="batch" name="batch" placeholder="e.g., 2024-Sep-CS" required>
                        </div>
                    </div>
                    <div class="row g-2 g-md-3 align-items-md-center mb-3">
                        <label for="batch_size" class="col-12 col-md-3 col-lg-2 col-form-label">Batch Size <span class="text-danger">*</span></label>
                        <div class="col-12 col-md-9 col-lg-10">
                            <input type="number" class="form-control" id="batch_size" name="batch_size" placeholder="Enter number of students" min="1" required>
                        </div>
                    </div>
                    <div class="row g-2 g-md-3 align-items-md-center mb-3">
                        <label for="intake_mode" class="col-12 col-md-3 col-lg-2 col-form-label">Intake Mode <span class="text-danger">*</span></label>
                        <div class="col-12 col-md-9 col-lg-10">
                            <select class="form-select" id="intake_mode" name="intake_mode" required>
                                <option selected disabled value="">Choose a mode...</option>
                                <option value="Physical">Physical</option>
                                <option value="Online">Online</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 g-md-3 align-items-md-center mb-3">
                        <label for="intake_type" class="col-12 col-md-3 col-lg-2 col-form-label">Intake Type <span class="text-danger">*</span></label>
                        <div class="col-12 col-md-9 col-lg-10">
                            <select class="form-select" id="intake_type" name="intake_type" required>
                                <option selected disabled value="">Choose a type...</option>
                                <option value="Fulltime">Full Time</option>
                                <option value="Parttime">Part Time</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 g-md-3 align-items-md-center mb-3">
                        <label for="registration_fee" class="col-12 col-md-3 col-lg-2 col-form-label">Registration Fee (LKR) <span class="text-danger">*</span></label>
                        <div class="col-12 col-md-9 col-lg-10">
                            <input type="number" class="form-control" id="registration_fee" name="registration_fee" placeholder="e.g., 5000.00" step="0.01" min="0" required>
                        </div>
                    </div>

                    <div id="degree_diploma_fields" hidden>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="franchise_payment" class="col-12 col-md-3 col-lg-2 col-form-label">Franchise Payment <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <div class="input-group">
                                    <select class="form-select currency-highlight" id="franchise_payment_currency" name="franchise_payment_currency" style="max-width:90px; flex-shrink:0;">
                                        <option value="LKR">LKR</option>
                                        <option value="USD">USD</option>
                                        <option value="GBP">GBP</option>
                                        <option value="EUR">EUR</option>
                                    </select>
                                    <input type="number" class="form-control" id="franchise_payment" name="franchise_payment" placeholder="e.g., 10000.00" step="0.01" min="0">
                                </div>
                                <small class="form-text text-muted d-block mt-1"><em>(Please select the currency type first)</em></small>
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="sscl_tax" class="col-12 col-md-3 col-lg-2 col-form-label">SSCL Tax Percentage <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <div class="input-group">
                                    <input type="number" class="form-control" id="sscl_tax" name="sscl_tax" placeholder="e.g., 15.00" step="0.01" min="0" max="100">
                                    <span class="input-group-text">%</span>
                                </div>
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="bank_charges" class="col-12 col-md-3 col-lg-2 col-form-label">Bank Charges (LKR) <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <input type="number" class="form-control" id="bank_charges" name="bank_charges" placeholder="e.g., 500.00" step="0.01" min="0">
                            </div>
                        </div>
                    </div>

                    <div id="certificate_fields" hidden>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="modules_select" class="col-12 col-md-3 col-lg-2 col-form-label">Select Modules <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select class="form-select" id="modules_select">
                                    <option value="">Choose modules to add...</option>
                                    @foreach($modules as $module)
                                        <option value="{{ $module->module_id }}" data-name="{{ $module->module_name }}" data-code="{{ $module->module_code }}">
                                            {{ $module->module_code }} - {{ $module->module_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">Select modules one at a time to add them to the intake</small>
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-start mb-3">
                            <label class="col-12 col-md-3 col-lg-2 col-form-label">Selected Modules</label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <div id="selected_modules_container" class="border rounded p-3 selected-modules-box" style="min-height: 100px; background-color: #f8f9fa;">
                                    <div id="selected_modules_list" class="selected-modules-list"></div>
                                    <div id="no_modules_message" class="text-muted text-center">No modules selected yet</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-2 g-md-3 align-items-md-center mb-3">
                        <label for="course_fee" class="col-12 col-md-3 col-lg-2 col-form-label">Course Fee (LKR) <span class="text-danger">*</span></label>
                        <div class="col-12 col-md-9 col-lg-10">
                            <input type="number" class="form-control" id="course_fee" name="course_fee" placeholder="e.g., 250000.00" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="row g-2 g-md-3 align-items-md-center mb-3">
                        <label for="start_date" class="col-12 col-md-3 col-lg-2 col-form-label">Start Date <span class="text-danger">*</span></label>
                        <div class="col-12 col-md-9 col-lg-10">
                            <input type="date" class="form-control" id="start_date" name="start_date" required>
                        </div>
                    </div>
                    <div class="row g-2 g-md-3 align-items-md-center mb-3">
                        <label for="end_date" class="col-12 col-md-3 col-lg-2 col-form-label">End Date <span class="text-danger">*</span></label>
                        <div class="col-12 col-md-9 col-lg-10">
                            <input type="date" class="form-control" id="end_date" name="end_date" required>
                        </div>
                    </div>
                    <div class="row g-2 g-md-3 align-items-md-center mb-3">
                        <label for="enrollment_end_date" class="col-12 col-md-3 col-lg-2 col-form-label">Enrollment End Date</label>
                        <div class="col-12 col-md-9 col-lg-10">
                            <input type="date" class="form-control" id="enrollment_end_date" name="enrollment_end_date">
                            <small class="form-text text-muted">Last date for students to enroll in this intake (optional)</small>
                        </div>
                    </div>
                    <div class="row g-2 g-md-3 align-items-md-center mb-3">
                        <label for="course_registration_id_pattern" class="col-12 col-md-3 col-lg-2 col-form-label">Course Registration ID pattern</label>
                        <div class="col-12 col-md-9 col-lg-10">
                            <input type="text" class="form-control" id="course_registration_id_pattern" name="course_registration_id_pattern" placeholder="e.g., REG-2023-001" required>
                        </div>
                    </div>
                </div>

                <div class="d-grid d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-primary" id="submitIntakeBtn" disabled>Create Intake</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 intake-list-header">
                <h2 class="mb-0">Existing Intakes</h2>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-sm btn-outline-danger" id="bulkDeleteIntakeBtn" hidden>
                        <i class="ti ti-trash"></i> Delete Selected
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" id="exportIntakeBtn">
                        <i class="ti ti-download"></i> Export CSV
                    </button>
                </div>
            </div>
            <hr>

            <form id="intakeFilterForm" method="GET" action="{{ route('intake.create') }}" class="row g-2 g-md-3 align-items-end mb-3">
                <div class="col-12 col-md-3">
                    <label class="form-label small text-muted" for="searchIntakeInput">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" class="form-control" id="searchIntakeInput" name="search" placeholder="Search intakes..." value="{{ $filters['search'] ?? '' }}">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small text-muted" for="filterIntakeLocation">Location</label>
                    <select class="form-select" id="filterIntakeLocation" name="location">
                        <option value="">All Locations</option>
                        <option value="Welisara" @selected(($filters['location'] ?? '') === 'Welisara')>Welisara</option>
                        <option value="Moratuwa" @selected(($filters['location'] ?? '') === 'Moratuwa')>Moratuwa</option>
                        <option value="Peradeniya" @selected(($filters['location'] ?? '') === 'Peradeniya')>Peradeniya</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small text-muted" for="filterIntakeMode">Mode</label>
                    <select class="form-select" id="filterIntakeMode" name="intake_mode">
                        <option value="">All Modes</option>
                        <option value="Physical" @selected(($filters['intake_mode'] ?? '') === 'Physical')>Physical</option>
                        <option value="Online" @selected(($filters['intake_mode'] ?? '') === 'Online')>Online</option>
                        <option value="Hybrid" @selected(($filters['intake_mode'] ?? '') === 'Hybrid')>Hybrid</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small text-muted" for="filterIntakeStatus">Status</label>
                    <select class="form-select" id="filterIntakeStatus" name="status">
                        <option value="">All Status</option>
                        <option value="upcoming" @selected(($filters['status'] ?? '') === 'upcoming')>Upcoming</option>
                        <option value="ongoing" @selected(($filters['status'] ?? '') === 'ongoing')>Ongoing</option>
                        <option value="finished" @selected(($filters['status'] ?? '') === 'finished')>Finished</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small text-muted" for="perPageIntakeSelect">Per page</label>
                    <select class="form-select" id="perPageIntakeSelect" name="per_page">
                        <option value="10" @selected((int) $perPage === 10)>10 per page</option>
                        <option value="25" @selected((int) $perPage === 25)>25 per page</option>
                        <option value="50" @selected((int) $perPage === 50)>50 per page</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2 d-grid gap-2">
                    <button class="btn btn-primary" type="submit">Filter</button>
                    <button class="btn btn-outline-secondary" type="button" id="clearIntakeFiltersBtn">Clear</button>
                </div>
            </form>

            <div class="d-flex justify-content-end align-items-center mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="selectAllIntakes">
                    <label class="form-check-label" for="selectAllIntakes"><small>Select All</small></label>
                </div>
            </div>

            <div class="intake-table-scroll">
                <table class="table table-striped table-bordered table-hover" id="intakesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40px;">
                                <input type="checkbox" id="selectAllIntakesHeader" class="form-check-input" aria-label="Select all intakes">
                            </th>
                            <th>Course Name</th>
                            <th>Batch</th>
                            <th>Location</th>
                            <th>Mode</th>
                            <th>Type</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Enrollment End</th>
                            <th>Capacity</th>
                            <th>Status</th>
                            <th style="width: 140px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="intake-table-body">
                        @include('courses_&_modules.partials.intake_rows')
                    </tbody>
                </table>
            </div>
            <div id="intakePagination">
                @include('courses_&_modules.partials.intake_pagination')
            </div>
        </div>
    </div>
</div>

<div class="modal fade record-edit-modal" id="editIntakeModal" tabindex="-1" aria-labelledby="editIntakeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <form id="editIntakeForm">
                @csrf
                <input type="hidden" id="edit_intake_id" name="intake_id">
                <div class="modal-header py-2">
                    <h5 class="modal-title" id="editIntakeModalLabel">Edit Intake</h5>
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
                            <label for="edit_course_id" class="form-label">Course <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_course_id" name="course_id" required>
                                <option value="">Choose a course...</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit_batch" class="form-label">Batch Name / Code <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_batch" name="batch" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit_batch_size" class="form-label">Batch Size <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_batch_size" name="batch_size" min="1" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit_intake_mode" class="form-label">Intake Mode <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_intake_mode" name="intake_mode" required>
                                <option value="Physical">Physical</option>
                                <option value="Online">Online</option>
                                <option value="Hybrid">Hybrid</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit_intake_type" class="form-label">Intake Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="edit_intake_type" name="intake_type" required>
                                <option value="Fulltime">Full Time</option>
                                <option value="Parttime">Part Time</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit_registration_fee" class="form-label">Registration Fee (LKR) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_registration_fee" name="registration_fee" step="0.01" min="0" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="edit_course_fee" class="form-label">Course Fee (LKR) <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="edit_course_fee" name="course_fee" step="0.01" min="0" required>
                        </div>
                        <div class="col-12" id="edit_degree_diploma_fields">
                            <div class="row g-2">
                                <div class="col-12 col-md-6">
                                    <label for="edit_franchise_payment" class="form-label">Franchise Payment <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-select currency-highlight" id="edit_franchise_payment_currency" name="franchise_payment_currency">
                                            <option value="LKR">LKR</option>
                                            <option value="USD">USD</option>
                                            <option value="GBP">GBP</option>
                                            <option value="EUR">EUR</option>
                                        </select>
                                        <input type="number" class="form-control" id="edit_franchise_payment" name="franchise_payment" step="0.01" min="0">
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="edit_sscl_tax" class="form-label">SSCL Tax Percentage <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="edit_sscl_tax" name="sscl_tax" step="0.01" min="0" max="100">
                                        <span class="input-group-text">%</span>
                                    </div>
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="edit_bank_charges" class="form-label">Bank Charges (LKR)</label>
                                    <input type="number" class="form-control" id="edit_bank_charges" name="bank_charges" step="0.01" min="0">
                                </div>
                            </div>
                        </div>
                        <div class="col-12" id="edit_certificate_fields" hidden>
                            <div class="row g-2">
                                <div class="col-12">
                                    <label for="edit_modules_select" class="form-label">Select Modules <span class="text-danger">*</span></label>
                                    <select class="form-select" id="edit_modules_select">
                                        <option value="">Choose modules to add...</option>
                                        @foreach($modules as $module)
                                            <option value="{{ $module->module_id }}" data-name="{{ $module->module_name }}" data-code="{{ $module->module_code }}">
                                                {{ $module->module_code }} - {{ $module->module_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Selected Modules</label>
                                    <div id="edit_selected_modules_container" class="border rounded p-2 selected-modules-box" style="min-height: 42px; background-color: #f8f9fa;">
                                        <div id="edit_selected_modules_list" class="selected-modules-list"></div>
                                        <div id="edit_no_modules_message" class="text-muted small">No modules selected yet</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="edit_start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_start_date" name="start_date" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="edit_end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="edit_end_date" name="end_date" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="edit_enrollment_end_date" class="form-label">Enrollment End Date</label>
                            <input type="date" class="form-control" id="edit_enrollment_end_date" name="enrollment_end_date">
                        </div>
                        <div class="col-12">
                            <label for="edit_course_registration_id_pattern" class="form-label">Course Registration ID pattern</label>
                            <input type="text" class="form-control" id="edit_course_registration_id_pattern" name="course_registration_id_pattern" required>
                            <small class="form-text text-muted">Must end with a number, e.g. REG-2023-001</small>
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
    const listUrl = '{{ route("intake.create") }}';
    const storeUrl = '{{ route("intake.store") }}';
    const exportUrl = '{{ route("intake.export") }}';
    const updateUrlTemplate = '{{ url("/intake-creation") }}';
    const allCourses = @json($allCoursesForJson);
    const editModalEl = document.getElementById('editIntakeModal');
    const editModal = editModalEl && window.bootstrap ? bootstrap.Modal.getOrCreateInstance(editModalEl) : null;
    let selectedModules = [];
    let editSelectedModules = [];

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

    function syncNebulaSelects(root) {
        $(root).find('select').each(function () { syncNebulaSelect(this); });
    }

    function formatCourseType(type) {
        if (!type) return '';
        return type.charAt(0).toUpperCase() + type.slice(1);
    }

    function formatDateForInput(dateValue) {
        if (!dateValue) return '';
        if (typeof window.toLocalDateString === 'function') {
            return window.toLocalDateString(dateValue) || '';
        }
        const dateObj = new Date(dateValue);
        if (isNaN(dateObj)) return String(dateValue).split('T')[0];
        return dateObj.toISOString().slice(0, 10);
    }

    function rebuildSelect(select, placeholder, items) {
        if (!select) return;
        select.innerHTML = '';
        const first = document.createElement('option');
        first.value = '';
        first.textContent = placeholder;
        first.disabled = true;
        first.selected = true;
        select.appendChild(first);
        items.forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.value;
            opt.textContent = item.label;
            select.appendChild(opt);
        });
        syncNebulaSelect(select);
    }

    function coursesFor(location, courseType) {
        return allCourses.filter(function (course) {
            return (!location || course.location === location) && (!courseType || course.course_type === courseType);
        });
    }

    function populateCreateCourses() {
        const location = $('#location').val();
        const courseType = $('#course_type').val();
        const items = coursesFor(location, courseType).map(function (course) {
            return { value: course.course_id, label: course.course_name };
        });
        rebuildSelect(document.getElementById('course_id'), items.length ? 'Choose a course...' : 'No courses available for this type', items);
        $('#courseDetailsBox').prop('hidden', true);
        if (!items.length && location && courseType) {
            showAlert('No courses', 'No ' + courseType + ' courses available at ' + location + '.', 'warning');
        }
    }

    function populateEditCourseOptions(location, selectedId) {
        const items = coursesFor(location, null).map(function (course) {
            return { value: course.course_id, label: formatCourseType(course.course_type) + ' - ' + course.course_name };
        });
        rebuildSelect(document.getElementById('edit_course_id'), 'Choose a course...', items);
        if (selectedId) {
            const select = document.getElementById('edit_course_id');
            select.value = String(selectedId);
            syncNebulaSelect(select);
        }
    }

    function applyCreateCourseType(type) {
        const location = $('#location').val();
        if (!type) {
            setSectionEnabled($('#intake_fields_container'), false);
            setSectionEnabled($('#degree_diploma_fields'), false);
            setSectionEnabled($('#certificate_fields'), false);
            $('#submitIntakeBtn').prop('disabled', true);
            return;
        }
        if (!location) {
            showAlert('Missing location', 'Please select a location first.', 'warning');
            resetFilterSelect(document.getElementById('course_type'), '');
            return;
        }
        setSectionEnabled($('#intake_fields_container'), true);
        if (type === 'degree' || type === 'diploma') {
            setSectionEnabled($('#degree_diploma_fields'), true);
            setSectionEnabled($('#certificate_fields'), false);
        } else {
            setSectionEnabled($('#degree_diploma_fields'), false);
            setSectionEnabled($('#certificate_fields'), true);
            selectedModules = [];
            updateSelectedModulesList();
        }
        populateCreateCourses();
        $('#submitIntakeBtn').prop('disabled', false);
    }

    function applyEditCourseType(type) {
        if (type === 'certificate') {
            setSectionEnabled($('#edit_degree_diploma_fields'), false);
            setSectionEnabled($('#edit_certificate_fields'), true);
        } else {
            setSectionEnabled($('#edit_degree_diploma_fields'), true);
            setSectionEnabled($('#edit_certificate_fields'), false);
        }
    }

    $('#location').on('change', function () {
        const hasLocation = !!$(this).val();
        $('#course_type').prop('disabled', !hasLocation).toggleClass('locked-field', !hasLocation);
        resetFilterSelect(document.getElementById('course_type'), '');
        applyCreateCourseType('');
        $('#courseDetailsBox').prop('hidden', true);
    });

    $('#course_type').on('change', function () {
        applyCreateCourseType($(this).val());
    });

    function moduleChip(moduleId, code, name, removeClass) {
        const safeCode = $('<div>').text(code || '').html();
        const safeName = $('<div>').text(name || '').html();
        return '<div class="badge bg-primary module-chip">' +
            '<span>' + safeCode + ' - ' + safeName + '</span>' +
            '<button type="button" class="btn-close btn-close-white ' + removeClass + '" data-module-id="' + moduleId + '" aria-label="Remove"></button>' +
            '</div>';
    }

    function updateSelectedModulesList() {
        const container = $('#selected_modules_list');
        container.empty();
        if (!selectedModules.length) {
            $('#no_modules_message').show();
            return;
        }
        $('#no_modules_message').hide();
        selectedModules.forEach(function (moduleId) {
            const option = $('#modules_select option[value="' + moduleId + '"]');
            container.append(moduleChip(moduleId, option.data('code'), option.data('name'), 'remove-create-module'));
        });
    }

    function updateEditSelectedModulesList() {
        const container = $('#edit_selected_modules_list');
        container.empty();
        if (!editSelectedModules.length) {
            $('#edit_no_modules_message').show();
            return;
        }
        $('#edit_no_modules_message').hide();
        editSelectedModules.forEach(function (moduleId) {
            const option = $('#edit_modules_select option[value="' + moduleId + '"]');
            container.append(moduleChip(moduleId, option.data('code'), option.data('name'), 'remove-edit-module'));
        });
    }

    $('#modules_select').on('change', function () {
        const moduleId = parseInt($(this).val(), 10);
        if (!moduleId) return;
        if (selectedModules.indexOf(moduleId) !== -1) {
            showAlert('Already added', 'This module has already been added.', 'warning');
            $(this).val('');
            syncNebulaSelect(this);
            return;
        }
        selectedModules.push(moduleId);
        updateSelectedModulesList();
        $(this).val('');
        syncNebulaSelect(this);
    });

    $('#edit_modules_select').on('change', function () {
        const moduleId = parseInt($(this).val(), 10);
        if (!moduleId) return;
        if (editSelectedModules.indexOf(moduleId) !== -1) {
            showAlert('Already added', 'This module has already been added.', 'warning');
            $(this).val('');
            syncNebulaSelect(this);
            return;
        }
        editSelectedModules.push(moduleId);
        updateEditSelectedModulesList();
        $(this).val('');
        syncNebulaSelect(this);
    });

    $(document).on('click', '.remove-create-module', function () {
        const moduleId = parseInt($(this).data('module-id'), 10);
        selectedModules = selectedModules.filter(function (id) { return id !== moduleId; });
        updateSelectedModulesList();
    });

    $(document).on('click', '.remove-edit-module', function () {
        const moduleId = parseInt($(this).data('module-id'), 10);
        editSelectedModules = editSelectedModules.filter(function (id) { return id !== moduleId; });
        updateEditSelectedModulesList();
    });

    function currentListUrl() {
        const params = new URLSearchParams($('#intakeFilterForm').serialize());
        const query = params.toString();
        return query ? (listUrl + '?' + query) : listUrl;
    }

    function loadIntakes(url, pushUrl) {
        const $body = $('#intake-table-body');
        const $pager = $('#intakePagination');
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
                $('#selectAllIntakes, #selectAllIntakesHeader').prop('checked', false);
                updateBulkDeleteButton();
                if (pushUrl) history.pushState({ intakeAjax: true }, '', url);
            },
            error: function () {
                showAlert('Error', 'Failed to load intakes.', 'error');
            },
            complete: function () {
                $body.removeClass('opacity-50');
                $pager.removeClass('opacity-50');
            }
        });
    }

    $('#intakeFilterForm').on('submit', function (e) {
        e.preventDefault();
        loadIntakes(currentListUrl(), true);
    });

    $('#perPageIntakeSelect').on('change', function () {
        loadIntakes(currentListUrl(), true);
    });

    $('#clearIntakeFiltersBtn').on('click', function () {
        $('#searchIntakeInput').val('');
        resetFilterSelect(document.getElementById('filterIntakeLocation'), '');
        resetFilterSelect(document.getElementById('filterIntakeMode'), '');
        resetFilterSelect(document.getElementById('filterIntakeStatus'), '');
        resetFilterSelect(document.getElementById('perPageIntakeSelect'), '10');
    });

    $(document).on('click', '#intakePagination .pagination a.page-link', function (e) {
        const href = $(this).attr('href');
        if (!href || href === '#' || $(this).closest('.page-item').hasClass('disabled') || $(this).closest('.page-item').hasClass('active')) {
            e.preventDefault();
            return;
        }
        e.preventDefault();
        loadIntakes(href, true);
    });

    window.addEventListener('popstate', function () {
        if (!$('.intake-creation-page').length) return;
        loadIntakes(window.location.href, false);
    });

    function updateBulkDeleteButton() {
        const count = $('.intake-checkbox:checked').length;
        if (count > 0) {
            $('#bulkDeleteIntakeBtn').prop('hidden', false).html('<i class="ti ti-trash"></i> Delete Selected (' + count + ')');
        } else {
            $('#bulkDeleteIntakeBtn').prop('hidden', true);
        }
    }

    $('#selectAllIntakes, #selectAllIntakesHeader').on('change', function () {
        const isChecked = $(this).prop('checked');
        $('#selectAllIntakes, #selectAllIntakesHeader').prop('checked', isChecked);
        $('.intake-checkbox').prop('checked', isChecked);
        updateBulkDeleteButton();
    });

    $(document).on('change', '.intake-checkbox', function () {
        const total = $('.intake-checkbox').length;
        const checked = $('.intake-checkbox:checked').length;
        $('#selectAllIntakes, #selectAllIntakesHeader').prop('checked', total > 0 && total === checked);
        updateBulkDeleteButton();
    });

    $('#intakeForm').on('submit', function (e) {
        e.preventDefault();
        const type = $('#course_type').val();
        if (!type) {
            showAlert('Missing type', 'Please select a course type.', 'warning');
            return;
        }
        if (type === 'certificate' && !selectedModules.length) {
            showAlert('Modules required', 'Please select at least one module for a certificate course.', 'warning');
            return;
        }
        const formData = new FormData(this);
        if (type === 'certificate') {
            selectedModules.forEach(function (moduleId) {
                formData.append('module_ids[]', moduleId);
            });
        }
        $.ajax({
            url: storeUrl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    $('#intakeForm')[0].reset();
                    selectedModules = [];
                    updateSelectedModulesList();
                    $('#course_type').prop('disabled', true).addClass('locked-field');
                    applyCreateCourseType('');
                    syncNebulaSelects('#intakeForm');
                    showAlert('Created', response.message, 'success').then(function () {
                        loadIntakes(currentListUrl(), false);
                    });
                } else {
                    showAlert('Error', response.message || 'Could not create the intake.', 'error');
                }
            },
            error: function (xhr) {
                showAlert('Error', validationMessage(xhr), 'error');
            }
        });
    });

    $(document).on('click', '.edit-intake-btn', function () {
        const intakeId = $(this).data('intake-id');
        $.ajax({
            url: updateUrlTemplate + '/' + intakeId + '/edit',
            type: 'GET',
            success: function (response) {
                if (!response.success || !response.intake) {
                    showAlert('Error', response.message || 'Failed to fetch intake details.', 'error');
                    return;
                }
                const intake = response.intake;
                $('#edit_intake_id').val(intake.intake_id);
                setSelectValue(document.getElementById('edit_location'), intake.location);
                populateEditCourseOptions(intake.location, intake.course_id);
                $('#edit_batch').val(intake.batch);
                $('#edit_batch_size').val(intake.batch_size);
                setSelectValue(document.getElementById('edit_intake_mode'), intake.intake_mode);
                setSelectValue(document.getElementById('edit_intake_type'), intake.intake_type);
                $('#edit_registration_fee').val(intake.registration_fee);
                setSelectValue(document.getElementById('edit_franchise_payment_currency'), intake.franchise_payment_currency || 'LKR');
                $('#edit_franchise_payment').val(intake.franchise_payment);
                $('#edit_course_fee').val(intake.course_fee);
                $('#edit_sscl_tax').val(intake.sscl_tax);
                $('#edit_bank_charges').val(intake.bank_charges);
                $('#edit_start_date').val(formatDateForInput(intake.start_date));
                $('#edit_end_date').val(formatDateForInput(intake.end_date));
                $('#edit_enrollment_end_date').val(formatDateForInput(intake.enrollment_end_date));
                $('#edit_course_registration_id_pattern').val(intake.course_registration_id_pattern);

                const courseType = intake.course ? intake.course.course_type : '';
                applyEditCourseType(courseType);
                editSelectedModules = [];
                if (courseType === 'certificate' && intake.modules) {
                    intake.modules.forEach(function (module) {
                        editSelectedModules.push(module.module_id);
                    });
                }
                updateEditSelectedModulesList();
                if (editModal) {
                    editModal.show();
                    setTimeout(function () { syncNebulaSelects('#editIntakeForm'); }, 50);
                }
            },
            error: function () {
                showAlert('Error', 'Error loading intake data.', 'error');
            }
        });
    });

    $('#edit_location').on('change', function () {
        populateEditCourseOptions($(this).val());
    });

    $('#edit_course_id').on('change', function () {
        const course = allCourses.find(function (item) { return String(item.course_id) === String($('#edit_course_id').val()); });
        applyEditCourseType(course ? course.course_type : '');
    });

    $('#editIntakeForm').on('submit', function (e) {
        e.preventDefault();
        const intakeId = $('#edit_intake_id').val();
        const course = allCourses.find(function (item) { return String(item.course_id) === String($('#edit_course_id').val()); });
        if (course && course.course_type === 'certificate' && !editSelectedModules.length) {
            showAlert('Modules required', 'Please select at least one module for a certificate course.', 'warning');
            return;
        }
        const formData = new FormData(this);
        formData.append('_method', 'PUT');
        if (course && course.course_type === 'certificate') {
            editSelectedModules.forEach(function (moduleId) {
                formData.append('module_ids[]', moduleId);
            });
        }
        $.ajax({
            url: updateUrlTemplate + '/' + intakeId,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response.success) {
                    if (editModal) editModal.hide();
                    showAlert('Updated', response.message, 'success').then(function () {
                        loadIntakes(window.location.href, false);
                    });
                } else {
                    showAlert('Error', response.message || 'Could not update the intake.', 'error');
                }
            },
            error: function (xhr) {
                showAlert('Error', validationMessage(xhr), 'error');
            }
        });
    });

    $(document).on('click', '.delete-intake-btn', function () {
        const intakeId = $(this).data('intake-id');
        const name = $(this).closest('tr').find('.intake-batch').text().trim();
        confirmDelete('Delete intake?', 'Delete "' + name + '"? This cannot be undone.').then(function (ok) {
            if (!ok) return;
            $.ajax({
                url: updateUrlTemplate + '/' + intakeId,
                type: 'DELETE',
                data: { _token: csrfToken },
                success: function (response) {
                    if (response.success) {
                        showAlert('Deleted', response.message, 'success').then(function () {
                            loadIntakes(window.location.href, false);
                        });
                    } else {
                        showAlert('Error', response.message || 'Could not delete the intake.', 'error');
                    }
                },
                error: function (xhr) {
                    showAlert('Error', validationMessage(xhr), 'error');
                }
            });
        });
    });

    $('#bulkDeleteIntakeBtn').on('click', function () {
        const selectedIds = [];
        $('.intake-checkbox:checked').each(function () {
            selectedIds.push($(this).data('intake-id'));
        });
        if (!selectedIds.length) return;
        confirmDelete('Delete selected intakes?', 'Delete ' + selectedIds.length + ' intake(s)? This cannot be undone.').then(function (ok) {
            if (!ok) return;
            $.ajax({
                url: '{{ route("intake.bulkDestroy") }}',
                type: 'POST',
                data: { _token: csrfToken, ids: selectedIds },
                success: function (response) {
                    showAlert(response.success ? 'Deleted' : 'Error', response.message, response.success ? 'success' : 'error').then(function () {
                        loadIntakes(window.location.href, false);
                    });
                },
                error: function (xhr) {
                    showAlert('Error', validationMessage(xhr), 'error');
                }
            });
        });
    });

    $('#exportIntakeBtn').on('click', function () {
        const params = new URLSearchParams($('#intakeFilterForm').serialize());
        params.delete('per_page');
        ['search', 'location', 'intake_mode', 'status'].forEach(function (key) {
            const value = $.trim(params.get(key) || '');
            if (value) {
                params.set(key, value);
            } else {
                params.delete(key);
            }
        });
        window.location.assign(params.toString() ? (exportUrl + '?' + params.toString()) : exportUrl);
    });

    $('#course_id').on('change', function () {
        const courseId = $(this).val();
        const course = allCourses.find(function (item) { return String(item.course_id) === String(courseId); });
        if (!course) {
            $('#courseDetailsBox').prop('hidden', true);
            return;
        }
        $('#cd_min_credits').text(course.min_credits ? course.min_credits : '-');
        $('#cd_medium').text(course.course_medium ? course.course_medium : '-');
        $('#cd_conducted_by').text(course.conducted_by ? course.conducted_by : '-');
        $('#courseDetailsBox').prop('hidden', false);
        autofillPaymentPlan();
    });

    function autofillPaymentPlan() {
        const courseId = $('#course_id').val();
        const location = $('#location').val();
        const courseType = $('#course_type').val();
        if (!courseId || !location || !courseType) return;
        $.ajax({
            url: '{{ route("get.payment.plan.details") }}',
            type: 'POST',
            data: {
                _token: csrfToken,
                course_id: courseId,
                location: location,
                course_type: courseType
            },
            success: function (response) {
                if (!response.success) return;
                $('#registration_fee').val(response.registration_fee);
                $('#course_fee').val(response.course_fee);
                if (response.franchise_payment != null) $('#franchise_payment').val(response.franchise_payment);
                if (response.franchise_payment_currency) setSelectValue(document.getElementById('franchise_payment_currency'), response.franchise_payment_currency);
                if (response.sscl_tax != null) $('#sscl_tax').val(response.sscl_tax);
                if (response.bank_charges != null) $('#bank_charges').val(response.bank_charges);
            },
            error: function () {
                // Fees stay blank so the user can enter them when no plan exists yet.
            }
        });
    }

    function checkEnrollmentDate($input, startSelector) {
        const value = $input.val();
        if (!value) return;
        const enrollmentEndDate = new Date(value);
        const startVal = $(startSelector).val();
        if (!startVal || isNaN(enrollmentEndDate)) return;
        const startDate = new Date(startVal);
        const oneMonthAfterStart = new Date(startDate);
        oneMonthAfterStart.setMonth(oneMonthAfterStart.getMonth() + 1);
        if (enrollmentEndDate > oneMonthAfterStart) {
            showAlert('Invalid date', 'Enrollment end date cannot be more than one month after the course start date.', 'warning');
            $input.val('');
        }
    }

    $('#enrollment_end_date').on('blur change', function () {
        checkEnrollmentDate($(this), '#start_date');
    });
    $('#edit_enrollment_end_date').on('blur change', function () {
        checkEnrollmentDate($(this), '#edit_start_date');
    });

    setSectionEnabled($('#intake_fields_container'), false);
    setSectionEnabled($('#degree_diploma_fields'), false);
    setSectionEnabled($('#certificate_fields'), false);
});
</script>
@endpush
