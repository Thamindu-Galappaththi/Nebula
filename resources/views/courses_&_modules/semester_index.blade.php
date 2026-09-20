@extends('inc.app')

@section('title', 'NEBULA | Semester Management')

@section('content')
@php
    $formatDate = function ($value, $withTime = false) {
        if (!$value) {
            return 'N/A';
        }
        $dt = $value instanceof \Carbon\Carbon ? $value : \Carbon\Carbon::parse($value);
        return $dt->format($withTime ? 'M d, Y H:i' : 'M d, Y');
    };
    $durationDays = function ($start, $end) {
        if (!$start || !$end) {
            return null;
        }
        return \Carbon\Carbon::parse($start)->diffInDays(\Carbon\Carbon::parse($end));
    };
@endphp
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<style nonce="{{ $cspNonce }}">
    .semester-page .nebula-select {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .semester-page .nebula-select-sm {
        width: 5.75rem;
        max-width: 5.75rem;
        flex: 0 0 5.75rem;
    }
    .semester-page-header,
    .semester-page-actions {
        gap: 0.75rem;
    }
    .semester-stat-card {
        height: 100%;
    }
    .semester-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #semestersTable {
        min-width: 1100px;
    }
    .semester-modules {
        display: inline-flex;
        align-items: center;
        flex-wrap: nowrap;
        gap: 0.4rem;
        white-space: nowrap;
    }
    .semester-modules .badge {
        flex: 0 0 auto;
        white-space: nowrap;
    }
    .semester-modules .btn {
        flex: 0 0 auto;
        margin-left: 0 !important;
    }
    .semester-actions {
        display: inline-flex;
        flex-wrap: nowrap;
        gap: 0.35rem;
    }
    .semester-pagination-bar {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }
    .semester-pagination-bar .pagination {
        margin-bottom: 0;
        flex-wrap: wrap;
    }
    .semester-toast-wrap {
        z-index: 9999;
    }
    .semester-page ~ .swal2-container,
    .swal2-container {
        z-index: 20000;
    }
    .semester-sheet-modal {
        max-width: min(800px, calc(100vw - 1.5rem));
        margin: 1rem auto;
    }
    .semester-sheet-modal .modal-content {
        height: auto;
        max-height: calc(100dvh - 2rem);
    }
    .semester-sheet-modal .modal-body {
        overflow-y: auto;
    }
    @media (max-width: 575.98px) {
        .semester-sheet-modal .modal-footer {
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .semester-sheet-modal .modal-footer .btn {
            flex: 1 1 auto;
            margin: 0;
        }
        .semester-sheet-modal .table td {
            white-space: normal;
            overflow-wrap: break-word;
            word-break: normal;
        }
        .semester-modules-table thead {
            display: none;
        }
        .semester-modules-table,
        .semester-modules-table tbody,
        .semester-modules-table tr,
        .semester-modules-table td {
            display: block;
            width: 100%;
            max-width: 100%;
            box-shadow: none !important;
            white-space: normal;
        }
        .semester-modules-table tbody tr {
            margin-bottom: 0.85rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 0.75rem 0.9rem;
            background: #fff;
        }
        .semester-modules-table td {
            border: 0 !important;
            border-bottom: 1px solid #f1f3f5 !important;
            padding: 0.5rem 0;
            text-align: left;
            overflow-wrap: break-word;
            word-break: normal;
        }
        .semester-modules-table td:last-child {
            border-bottom: 0 !important;
        }
        .semester-modules-table td::before {
            content: attr(data-label);
            display: block;
            font-weight: 600;
            color: #6c757d;
            margin-bottom: 0.2rem;
        }
        .semester-modules-table .badge {
            white-space: normal;
            text-align: left;
        }
        .semester-modules-wrap {
            overflow: visible;
        }
    }
    @media (max-width: 991.98px) {
        .semester-page-header {
            flex-direction: column;
            align-items: stretch !important;
        }
        .semester-page-actions,
        .semester-page-actions .btn {
            width: 100%;
        }
        .semester-stat-card small {
            display: block;
            line-height: 1.3;
        }
        #semestersTable {
            min-width: 0;
        }
        #semestersTable thead {
            display: none;
        }
        #semestersTable,
        #semestersTable tbody,
        #semestersTable tr,
        #semestersTable td {
            display: block;
            width: 100%;
        }
        #semestersTable tr[data-semester] {
            margin-bottom: 0.85rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 0.75rem 0.9rem;
            background: #fff;
        }
        #semestersTable td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            border: 0;
            border-bottom: 1px solid #f1f3f5;
            padding: 0.45rem 0;
        }
        #semestersTable td:last-child {
            border-bottom: 0;
        }
        #semestersTable td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            flex: 0 0 38%;
            max-width: 38%;
        }
        #semestersTable td[data-label=""]::before,
        #semestersTable td.semester-select-cell::before {
            display: none;
        }
        #semestersTable td.semester-select-cell,
        #semestersTable td.semester-actions-cell,
        #semestersTable td.semester-modules-cell {
            justify-content: flex-end;
            align-items: center;
        }
        #semestersTable td.semester-modules-cell .semester-modules {
            margin-left: auto;
        }
        #semestersTable .empty-row td {
            display: block;
            text-align: center;
            border: 0;
        }
        #semestersTable .empty-row td::before {
            display: none;
        }
        .semester-pagination-bar {
            flex-direction: column;
            align-items: stretch;
        }
        .semester-pagination-bar .pagination {
            justify-content: center;
        }
        .semester-toast-wrap {
            top: auto !important;
            bottom: 0;
            left: 0;
            right: 0;
        }
        .bulk-actions-footer {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .bulk-actions-footer .btn {
            width: 100%;
            margin: 0 !important;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 semester-page">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4 semester-page-header">
                <h2 class="mb-0">Semester Management</h2>
                <div class="d-flex flex-wrap semester-page-actions">
                    <button type="button" id="bulkActionsBtn" class="btn btn-outline-secondary" hidden>
                        <i class="ti ti-list-check"></i> Bulk Actions
                    </button>
                    <a href="{{ route('semesters.create') }}" class="btn btn-primary">
                        <i class="ti ti-plus"></i> Create New Semester
                    </a>
                </div>
            </div>
            <hr>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <label class="form-label small text-muted" for="searchInput">Search</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="ti ti-search"></i></span>
                        <input type="text" id="searchInput" class="form-control" placeholder="Search name, course or intake">
                    </div>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small text-muted" for="statusFilter">Status</label>
                    <select id="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small text-muted" for="courseFilter">Course</label>
                    <select id="courseFilter" class="form-select">
                        <option value="">All Courses</option>
                        @foreach($courses ?? [] as $course)
                            <option value="{{ $course->course_id }}">{{ $course->course_name }}@if(!empty($course->location)) ({{ $course->location }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex align-items-end">
                    <button type="button" id="clearFilters" class="btn btn-outline-secondary w-100">
                        Clear
                    </button>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-6 col-lg-3">
                    <div class="card bg-primary text-white semester-stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0" id="totalSemesters">{{ $semesters->count() }}</h4>
                                    <small>Total Semesters</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="ti ti-calendar fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card bg-success text-white semester-stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0" id="activeSemesters">{{ $semesters->where('status', 'active')->count() }}</h4>
                                    <small>Active Semesters</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="ti ti-player-play fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card bg-warning text-white semester-stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0" id="upcomingSemesters">{{ $semesters->where('status', 'upcoming')->count() }}</h4>
                                    <small>Upcoming Semesters</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="ti ti-clock fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-lg-3">
                    <div class="card bg-secondary text-white semester-stat-card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h4 class="mb-0" id="completedSemesters">{{ $semesters->where('status', 'completed')->count() }}</h4>
                                    <small>Completed Semesters</small>
                                </div>
                                <div class="align-self-center">
                                    <i class="ti ti-circle-check fs-2"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="semester-table-scroll">
                <table class="table table-striped table-bordered align-middle" id="semestersTable">
                    <thead class="table-dark">
                        <tr>
                            <th>
                                <input type="checkbox" id="selectAll" class="form-check-input" aria-label="Select all visible semesters">
                            </th>
                            <th>Semester Name</th>
                            <th>Course</th>
                            <th>Intake</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Modules</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($semesters as $semester)
                            @php
                                $days = $durationDays($semester->start_date, $semester->end_date);
                                $moduleCount = $semester->modules->count();
                                $status = $semester->status ?: 'completed';
                            @endphp
                            <tr data-semester="{{ strtolower($semester->name) }}"
                                data-course="{{ strtolower($semester->course->course_name ?? '') }}"
                                data-course-id="{{ $semester->course_id }}"
                                data-intake="{{ strtolower($semester->intake->batch ?? '') }}"
                                data-status="{{ $status }}">
                                <td class="semester-select-cell" data-label="">
                                    <input type="checkbox" class="form-check-input semester-checkbox" value="{{ $semester->id }}" aria-label="Select {{ $semester->name }}">
                                </td>
                                <td data-label="Semester">
                                    <strong class="semester-name">{{ $semester->name }}</strong>
                                    <span class="badge bg-success ms-2 current-badge" @if($status !== 'active') hidden @endif>Current</span>
                                </td>
                                <td data-label="Course">{{ $semester->course->course_name ?? 'N/A' }}</td>
                                <td data-label="Intake">{{ $semester->intake->batch ?? 'N/A' }}</td>
                                <td data-label="Start Date">{{ $formatDate($semester->start_date) }}</td>
                                <td data-label="End Date">{{ $formatDate($semester->end_date) }}</td>
                                <td data-label="Duration">{{ $days !== null ? $days . ' days' : 'N/A' }}</td>
                                <td data-label="Status" class="semester-status-cell">
                                    @if($status === 'upcoming')
                                        <span class="badge bg-warning">Upcoming</span>
                                    @elseif($status === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Completed</span>
                                    @endif
                                </td>
                                <td class="semester-modules-cell" data-label="Modules">
                                    <div class="semester-modules">
                                        <span class="badge bg-info">{{ $moduleCount }} module{{ $moduleCount !== 1 ? 's' : '' }}</span>
                                        @if($moduleCount > 0)
                                            <button type="button" class="btn btn-sm btn-outline-info"
                                                    data-bs-toggle="modal" data-bs-target="#modulesModal{{ $semester->id }}"
                                                    title="View modules">
                                                <i class="ti ti-eye"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="semester-actions-cell" data-label="Actions">
                                    <div class="semester-actions">
                                        <a href="{{ route('semesters.edit', $semester) }}" class="btn btn-sm btn-outline-primary" title="Edit Semester">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-info"
                                                data-bs-toggle="modal" data-bs-target="#semesterModal{{ $semester->id }}"
                                                title="View Details">
                                            <i class="ti ti-eye"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger delete-semester"
                                                data-semester-id="{{ $semester->id }}"
                                                data-semester-name="{{ $semester->name }}"
                                                title="Delete Semester">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                        @endforelse
                        <tr class="empty-row" id="emptySemesterRow" @if($semesters->count() > 0) hidden @endif>
                            <td colspan="10" class="text-center">No semesters found.</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="semester-pagination-bar mt-3" id="semesterPaginationBar" hidden>
                <small class="text-muted" id="semesterResultRange"></small>
                <nav aria-label="Semester pages">
                    <ul class="pagination pagination-sm" id="semesterPagination"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

@foreach($semesters as $semester)
    @php
        $days = $durationDays($semester->start_date, $semester->end_date);
        $status = $semester->status ?: 'completed';
    @endphp
<div class="modal fade" id="semesterModal{{ $semester->id }}" tabindex="-1" aria-labelledby="semesterModalLabel{{ $semester->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered semester-sheet-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="semesterModalLabel{{ $semester->id }}">Semester Details - {{ $semester->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12 col-md-6">
                        <h6>Basic Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Name:</strong></td><td>{{ $semester->name }}</td></tr>
                            <tr><td><strong>Course:</strong></td><td>{{ $semester->course->course_name ?? 'N/A' }}</td></tr>
                            <tr><td><strong>Intake:</strong></td><td>{{ $semester->intake->batch ?? 'N/A' }}</td></tr>
                            <tr><td><strong>Status:</strong></td><td>
                                @if($status === 'upcoming')
                                    <span class="badge bg-warning">Upcoming</span>
                                @elseif($status === 'active')
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Completed</span>
                                @endif
                            </td></tr>
                        </table>
                    </div>
                    <div class="col-12 col-md-6">
                        <h6>Date Information</h6>
                        <table class="table table-sm">
                            <tr><td><strong>Start Date:</strong></td><td>{{ $formatDate($semester->start_date) }}</td></tr>
                            <tr><td><strong>End Date:</strong></td><td>{{ $formatDate($semester->end_date) }}</td></tr>
                            <tr><td><strong>Duration:</strong></td><td>{{ $days !== null ? $days . ' days' : 'N/A' }}</td></tr>
                            <tr><td><strong>Created:</strong></td><td>{{ $formatDate($semester->created_at, true) }}</td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="{{ route('semesters.edit', $semester) }}" class="btn btn-primary">Edit Semester</a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modulesModal{{ $semester->id }}" tabindex="-1" aria-labelledby="modulesModalLabel{{ $semester->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered semester-sheet-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modulesModalLabel{{ $semester->id }}">Modules - {{ $semester->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                @if($semester->modules->count() > 0)
                    <div class="table-responsive semester-modules-wrap">
                        <table class="table table-striped semester-modules-table">
                            <thead>
                                <tr>
                                    <th>Module Name</th>
                                    <th>Type</th>
                                    <th>Credits</th>
                                    <th>Specialization</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($semester->modules as $module)
                                <tr>
                                    <td data-label="Module Name">{{ $module->module_name }}</td>
                                    <td data-label="Type">
                                        <span class="badge bg-{{ $module->module_type === 'core' ? 'primary' : ($module->module_type === 'elective' ? 'success' : 'warning') }}">
                                            {{ ucfirst(str_replace('_', ' ', $module->module_type ?? '')) }}
                                        </span>
                                    </td>
                                    <td data-label="Credits">{{ $module->credits ?? 'N/A' }}</td>
                                    <td data-label="Specialization">{{ $module->pivot->specialization ?? 'N/A' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-center text-muted">No modules assigned to this semester.</p>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endforeach

<div class="modal fade" id="bulkActionsModal" tabindex="-1" aria-labelledby="bulkActionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-dialog-centered semester-sheet-modal">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkActionsModalLabel">Bulk Actions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Selected Semesters: <span id="selectedCount">0</span></label>
                </div>
                <div class="mb-3">
                    <label for="bulkStatus" class="form-label">Update Status:</label>
                    <select id="bulkStatus" class="form-select">
                        <option value="">Select Status</option>
                        <option value="upcoming">Upcoming</option>
                        <option value="active">Active</option>
                        <option value="completed">Completed</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer bulk-actions-footer">
                <button type="button" class="btn btn-danger" id="bulkDeleteBtn">
                    <i class="ti ti-trash"></i> Delete Selected
                </button>
                <button type="button" class="btn btn-primary" id="bulkUpdateStatusBtn">
                    <i class="ti ti-device-floppy"></i> Update Status
                </button>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3 semester-toast-wrap">
    <div id="mainToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="mainToastBody"></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const courseFilter = document.getElementById('courseFilter');
    const clearFilters = document.getElementById('clearFilters');
    const table = document.getElementById('semestersTable');
    const tbody = table.querySelector('tbody');
    const emptyRow = document.getElementById('emptySemesterRow');
    const selectAllCheckbox = document.getElementById('selectAll');
    const bulkActionsBtn = document.getElementById('bulkActionsBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    const bulkStatusSelect = document.getElementById('bulkStatus');
    const bulkUpdateStatusBtn = document.getElementById('bulkUpdateStatusBtn');
    const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
    const paginationBar = document.getElementById('semesterPaginationBar');
    const paginationEl = document.getElementById('semesterPagination');
    const resultRange = document.getElementById('semesterResultRange');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const perPage = 10;
    let currentPage = 1;

    const bulkActionsModalEl = document.getElementById('bulkActionsModal');
    const bulkActionsModal = (bulkActionsModalEl && typeof bootstrap !== 'undefined')
        ? bootstrap.Modal.getOrCreateInstance(bulkActionsModalEl)
        : null;

    function semesterRows() {
        return Array.from(tbody.querySelectorAll('tr[data-semester]'));
    }

    function matchesFilters(row) {
        const searchTerm = (searchInput.value || '').toLowerCase().trim();
        const statusValue = (statusFilter.value || '').toLowerCase();
        const courseValue = String(courseFilter.value || '');
        const semesterName = row.getAttribute('data-semester') || '';
        const courseName = row.getAttribute('data-course') || '';
        const intakeName = row.getAttribute('data-intake') || '';
        const courseId = String(row.getAttribute('data-course-id') || '');
        const status = row.getAttribute('data-status') || '';

        const matchesSearch = !searchTerm
            || semesterName.includes(searchTerm)
            || courseName.includes(searchTerm)
            || intakeName.includes(searchTerm);
        const matchesStatus = !statusValue || status === statusValue;
        const matchesCourse = !courseValue || courseId === courseValue;

        return matchesSearch && matchesStatus && matchesCourse;
    }

    function filteredRows() {
        return semesterRows().filter(matchesFilters);
    }

    function visiblePageRows() {
        return semesterRows().filter(row => row.style.display !== 'none' && !row.hidden);
    }

    function renderPagination(total) {
        const lastPage = Math.max(1, Math.ceil(total / perPage));
        if (currentPage > lastPage) {
            currentPage = lastPage;
        }

        if (!paginationBar || !paginationEl || !resultRange) {
            return lastPage;
        }

        paginationEl.replaceChildren();
        if (total === 0) {
            paginationBar.hidden = true;
            resultRange.textContent = '';
            return lastPage;
        }

        paginationBar.hidden = false;
        const start = ((currentPage - 1) * perPage) + 1;
        const end = Math.min(currentPage * perPage, total);
        resultRange.textContent = `Showing ${start}–${end} of ${total}`;

        if (lastPage <= 1) {
            return lastPage;
        }

        const addItem = (label, page, disabled, active) => {
            const li = document.createElement('li');
            li.className = 'page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '');
            const btn = document.createElement(active || disabled ? 'span' : 'button');
            btn.className = 'page-link';
            btn.textContent = label;
            if (!active && !disabled) {
                btn.type = 'button';
                btn.addEventListener('click', function () {
                    currentPage = page;
                    applyFilters();
                });
            }
            li.appendChild(btn);
            paginationEl.appendChild(li);
        };

        addItem('Prev', currentPage - 1, currentPage === 1, false);
        for (let page = 1; page <= lastPage; page++) {
            addItem(String(page), page, false, page === currentPage);
        }
        addItem('Next', currentPage + 1, currentPage === lastPage, false);
        return lastPage;
    }

    function applyFilters() {
        const matches = filteredRows();
        renderPagination(matches.length);

        semesterRows().forEach(row => {
            row.style.display = 'none';
        });

        const start = (currentPage - 1) * perPage;
        matches.slice(start, start + perPage).forEach(row => {
            row.style.display = '';
        });

        if (emptyRow) {
            emptyRow.hidden = matches.length > 0;
        }

        updateStatistics(matches);
        updateBulkActionsButton();
    }

    function updateStatistics(matches) {
        const rows = matches || filteredRows();
        document.getElementById('totalSemesters').textContent = String(rows.length);
        document.getElementById('activeSemesters').textContent = String(rows.filter(row => row.getAttribute('data-status') === 'active').length);
        document.getElementById('upcomingSemesters').textContent = String(rows.filter(row => row.getAttribute('data-status') === 'upcoming').length);
        document.getElementById('completedSemesters').textContent = String(rows.filter(row => row.getAttribute('data-status') === 'completed').length);
    }

    function updateBulkActionsButton() {
        const selectedCount = document.querySelectorAll('.semester-checkbox:checked').length;
        bulkActionsBtn.hidden = selectedCount === 0;
        selectedCountSpan.textContent = String(selectedCount);
    }

    function statusBadgeClass(status) {
        if (status === 'upcoming') return 'badge bg-warning';
        if (status === 'active') return 'badge bg-success';
        return 'badge bg-secondary';
    }

    function applyStatusToRow(row, status) {
        row.setAttribute('data-status', status);
        const statusBadge = row.querySelector('.semester-status-cell .badge');
        if (statusBadge) {
            statusBadge.className = statusBadgeClass(status);
            statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        }
        const currentBadge = row.querySelector('.current-badge');
        if (currentBadge) {
            currentBadge.hidden = status !== 'active';
        }
    }

    function jsonHeaders() {
        return {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json'
        };
    }

    function confirmSemesterDelete(title, text) {
        if (typeof Swal === 'undefined') {
            return Promise.resolve(window.confirm(text));
        }
        return Swal.fire({
            title: title,
            text: text,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it',
            cancelButtonText: 'Cancel',
            reverseButtons: true,
            focusCancel: true
        }).then(result => result.isConfirmed);
    }

    searchInput.addEventListener('input', function () {
        currentPage = 1;
        applyFilters();
    });
    statusFilter.addEventListener('change', function () {
        currentPage = 1;
        applyFilters();
    });
    courseFilter.addEventListener('change', function () {
        currentPage = 1;
        applyFilters();
    });
    function resetFilterSelect(select) {
        if (!select) return;
        select.value = '';
        select.selectedIndex = 0;
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    clearFilters.addEventListener('click', function() {
        searchInput.value = '';
        resetFilterSelect(statusFilter);
        resetFilterSelect(courseFilter);
        currentPage = 1;
        applyFilters();
    });

    selectAllCheckbox.addEventListener('change', function() {
        visiblePageRows().forEach(row => {
            const checkbox = row.querySelector('.semester-checkbox');
            if (checkbox) checkbox.checked = selectAllCheckbox.checked;
        });
        updateBulkActionsButton();
    });

    document.addEventListener('change', function(e) {
        if (!e.target.classList.contains('semester-checkbox')) {
            return;
        }
        updateBulkActionsButton();
        const pageCheckboxes = visiblePageRows().map(row => row.querySelector('.semester-checkbox')).filter(Boolean);
        const checkedCount = pageCheckboxes.filter(cb => cb.checked).length;
        selectAllCheckbox.indeterminate = checkedCount > 0 && checkedCount < pageCheckboxes.length;
        selectAllCheckbox.checked = pageCheckboxes.length > 0 && checkedCount === pageCheckboxes.length;
    });

    bulkActionsBtn.addEventListener('click', function() {
        if (bulkActionsModal) bulkActionsModal.show();
    });

    bulkUpdateStatusBtn.addEventListener('click', function() {
        const selectedCheckboxes = document.querySelectorAll('.semester-checkbox:checked');
        const status = bulkStatusSelect.value;

        if (selectedCheckboxes.length === 0) {
            showToast('Please select at least one semester.', 'warning');
            return;
        }
        if (!status) {
            showToast('Please select a status to update.', 'warning');
            return;
        }

        const semesterIds = Array.from(selectedCheckboxes).map(cb => cb.value);

        fetch('{{ route("semesters.bulkUpdateStatus") }}', {
            method: 'POST',
            headers: jsonHeaders(),
            body: JSON.stringify({ semester_ids: semesterIds, status: status })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                showToast(data.message, 'danger');
                return;
            }
            showToast(data.message, 'success');
            if (bulkActionsModal) bulkActionsModal.hide();
            selectedCheckboxes.forEach(checkbox => applyStatusToRow(checkbox.closest('tr'), status));
            selectAllCheckbox.checked = false;
            document.querySelectorAll('.semester-checkbox').forEach(cb => { cb.checked = false; });
            updateBulkActionsButton();
            applyFilters();
        })
        .catch(() => {
            showToast('An error occurred while updating semester statuses.', 'danger');
        });
    });

    bulkDeleteBtn.addEventListener('click', function() {
        const selectedCheckboxes = document.querySelectorAll('.semester-checkbox:checked');
        if (selectedCheckboxes.length === 0) {
            showToast('Please select at least one semester.', 'warning');
            return;
        }
        confirmSemesterDelete(
            'Delete selected semesters?',
            `Are you sure you want to delete ${selectedCheckboxes.length} semester(s)? This action cannot be undone.`
        ).then(confirmed => {
            if (!confirmed) return;

            const semesterIds = Array.from(selectedCheckboxes).map(cb => cb.value);

            fetch('{{ route("semesters.bulkDelete") }}', {
                method: 'POST',
                headers: jsonHeaders(),
                body: JSON.stringify({ semester_ids: semesterIds })
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    showToast(data.message, 'danger');
                    return;
                }
                showToast(data.message, 'success');
                if (bulkActionsModal) bulkActionsModal.hide();
                selectedCheckboxes.forEach(checkbox => checkbox.closest('tr')?.remove());
                selectAllCheckbox.checked = false;
                updateBulkActionsButton();
                applyFilters();
            })
            .catch(() => {
                showToast('An error occurred while deleting semesters.', 'danger');
            });
        });
    });

    tbody.addEventListener('click', function (e) {
        const button = e.target.closest('.delete-semester');
        if (!button) return;

        const semesterId = button.dataset.semesterId;
        const semesterName = button.dataset.semesterName || 'this semester';
        confirmSemesterDelete(
            'Delete this semester?',
            `Are you sure you want to delete "${semesterName}"? This action cannot be undone.`
        ).then(confirmed => {
            if (!confirmed) return;

            button.disabled = true;
            fetch(`{{ url('/semesters') }}/${encodeURIComponent(semesterId)}`, {
                method: 'DELETE',
                headers: jsonHeaders()
            })
            .then(response => response.json())
            .then(data => {
                if (!data.success) {
                    button.disabled = false;
                    showToast(data.message, 'danger');
                    return;
                }
                showToast(data.message, 'success');
                button.closest('tr')?.remove();
                applyFilters();
            })
            .catch(() => {
                button.disabled = false;
                showToast('An error occurred while deleting the semester.', 'danger');
            });
        });
    });

    applyFilters();
});

function showToast(message, type = 'success') {
    const toastEl = document.getElementById('mainToast');
    const toastBody = document.getElementById('mainToastBody');
    if (!toastEl || !toastBody || typeof bootstrap === 'undefined') {
        return;
    }
    toastBody.textContent = message;
    toastEl.className = 'toast align-items-center border-0 text-bg-' + (
        type === 'success' ? 'success' : (type === 'danger' ? 'danger' : (type === 'warning' ? 'warning' : 'primary'))
    );
    bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 3000 }).show();
}
</script>
@endsection
