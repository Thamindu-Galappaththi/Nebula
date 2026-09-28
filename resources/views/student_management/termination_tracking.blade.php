@extends('inc.app')

@section('title', 'NEBULA | Termination Tracking')

@section('content')
<style nonce="{{ $cspNonce }}">
.termination-tracking-page,
.termination-tracking-page .card,
.termination-tracking-page .card-body {
    min-width: 0;
    max-width: 100%;
}
.termination-tracking-page [class*="col-"] {
    min-width: 0;
}
.termination-tracking-page .card,
.termination-tracking-page .card-body {
    overflow: visible;
}
body:has(.termination-tracking-page) .body-wrapper > .container-fluid {
    overflow: visible;
}
.termination-filters {
    position: relative;
    z-index: 1;
    overflow: visible;
}
.termination-filters [class*="col-"] {
    min-width: 0;
    overflow: visible;
}
.termination-filters .nebula-select,
.termination-filters .nebula-select-toggle {
    width: 100%;
    max-width: 100%;
}
.termination-page-size .form-select,
.termination-page-size .nebula-select {
    width: 5.75rem;
}
.termination-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding-top: 0.75rem;
}
.termination-page-size {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.termination-pagination {
    max-width: 100%;
    overflow-x: auto;
}
.termination-pagination .pagination {
    flex-wrap: wrap;
    margin-bottom: 0;
}
.termination-toolbar {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.75rem;
}
.termination-toolbar .btn {
    white-space: nowrap;
}
.summary-card {
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    padding: 18px;
    height: 100%;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    box-shadow: 0 6px 18px rgba(15, 23, 42, 0.06);
}
.summary-card .label {
    color: #64748b;
    font-size: 0.85rem;
    margin-bottom: 8px;
}
.summary-card .value {
    font-size: 1.8rem;
    font-weight: 700;
    color: #0f172a;
    word-break: break-word;
}
.termination-table-scroll {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.termination-table td,
.termination-table th {
    vertical-align: middle;
}
.termination-table .btn {
    min-height: 38px;
}
.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
    gap: 12px;
}
.detail-card {
    border: 1px solid #e5e7eb;
    border-radius: 12px;
    padding: 14px;
    background: #fff;
    min-width: 0;
}
.detail-card h6 {
    margin-bottom: 10px;
    font-weight: 700;
}
.detail-meta {
    color: #64748b;
    font-size: 0.85rem;
    word-break: break-word;
}
.empty-state {
    border: 1px dashed #cbd5e1;
    border-radius: 14px;
    padding: 36px 16px;
    text-align: center;
    color: #64748b;
    background: #f8fafc;
}
.termination-badge {
    white-space: normal;
    text-align: left;
}
#terminationProcessModal .table-responsive {
    -webkit-overflow-scrolling: touch;
}
@media (max-width: 767.98px) {
    .termination-tracking-page h2 {
        font-size: 1.35rem;
    }
    .termination-tracking-page .card-body {
        padding: 1rem 0.75rem;
    }
    .termination-toolbar {
        flex-direction: column;
        align-items: stretch;
    }
    .termination-toolbar .btn {
        width: 100%;
    }
    .summary-card {
        padding: 14px;
    }
    .summary-card .value {
        font-size: 1.45rem;
    }
    .termination-table thead {
        display: none;
    }
    .termination-table,
    .termination-table tbody,
    .termination-table tr,
    .termination-table td {
        display: block;
        width: 100%;
    }
    .termination-table tr[data-student-id] {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 12px;
        padding: 8px 12px 12px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
    }
    .termination-table td {
        border: 0;
        padding: 0.45rem 0;
    }
    .termination-table td[data-label]::before {
        content: attr(data-label);
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 2px;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    .termination-table td.termination-action {
        padding-top: 0.75rem;
    }
    .termination-table td.termination-action .btn {
        width: 100%;
    }
    .termination-footer {
        flex-direction: column;
        align-items: stretch;
    }
    .termination-page-size {
        width: 100%;
    }
    .termination-pagination .pagination {
        justify-content: center;
    }
    .termination-filters .nebula-select-menu {
        max-height: min(45vh, 280px) !important;
    }
    .detail-grid {
        grid-template-columns: 1fr;
    }
    #terminationProcessModal .modal-body {
        padding: 0.9rem;
    }
    #terminationProcessModal .btn {
        width: 100%;
    }
    #terminationProcessModal thead {
        display: none;
    }
    #terminationProcessModal table,
    #terminationProcessModal tbody {
        display: block;
        width: 100%;
    }
    #processClearanceBody tr {
        display: block;
        border: 1px solid #e5e7eb;
        border-radius: 10px;
        margin-bottom: 10px;
        padding: 8px 10px;
        background: #fff;
    }
    #processClearanceBody td {
        display: block;
        border: 0;
        padding: 0.35rem 0;
        word-break: break-word;
    }
    #processClearanceBody td[data-label]::before {
        content: attr(data-label);
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 2px;
    }
}
</style>

<div class="container-fluid px-2 px-md-3 termination-tracking-page">
    <div class="card">
        <div class="card-body">
            <div class="termination-toolbar mb-4">
                <div>
                    <h2 class="mb-1">Termination Tracking</h2>
                    <p class="text-muted mb-0">Track clearance progress for terminated students.</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-outline-secondary" id="clearTerminationFilters">
                        <i class="ti ti-filter-off me-1"></i>Clear Filters
                    </button>
                    <button type="button" class="btn btn-outline-primary" id="refreshTerminationBtn">
                        <i class="ti ti-refresh me-1"></i>Refresh
                    </button>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-12 col-sm-4">
                    <div class="summary-card">
                        <div class="label">Currently Terminated Students</div>
                        <div class="value" id="summaryTotal">{{ $summary['total'] }}</div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="summary-card">
                        <div class="label">Clearance In Progress</div>
                        <div class="value" id="summaryInProgress">{{ $summary['clearance_in_progress'] }}</div>
                    </div>
                </div>
                <div class="col-12 col-sm-4">
                    <div class="summary-card">
                        <div class="label">Completed</div>
                        <div class="value" id="summaryCompleted">{{ $summary['completed'] }}</div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3 termination-filters">
                <div class="col-12">
                    <label class="form-label" for="terminationSearch">Search</label>
                    <input type="text" id="terminationSearch" class="form-control" placeholder="Search by student ID, NIC, name, location, course, intake or reason" autocomplete="off">
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label" for="locationFilter">Location</label>
                    <select id="locationFilter" class="form-select">
                        <option value="">All Locations</option>
                        @foreach($filters['locations'] as $location)
                            <option value="{{ $location }}">{{ $location }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label" for="courseFilter">Course</label>
                    <select id="courseFilter" class="form-select">
                        <option value="">All Courses</option>
                        @foreach($filters['courses'] as $course)
                            <option value="{{ $course['id'] }}" data-location="{{ $course['location'] }}">{{ $course['name'] }} ({{ $course['location'] }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label" for="intakeFilter">Intake</label>
                    <select id="intakeFilter" class="form-select">
                        <option value="">All Intakes</option>
                        @foreach($filters['intakes'] as $intake)
                            <option value="{{ $intake['id'] }}" data-course-id="{{ $intake['course_id'] }}" data-location="{{ $intake['location'] }}">{{ $intake['name'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label" for="overallStatusFilter">Process Status</label>
                    <select id="overallStatusFilter" class="form-select">
                        <option value="">All Process Statuses</option>
                        <option value="not_started">No clearances requested</option>
                        <option value="awaiting_clearances">Awaiting clearances</option>
                        <option value="clearance_rejected">Clearance rejected</option>
                        <option value="completed">Clearances completed</option>
                    </select>
                </div>
            </div>

            <div id="terminationEmpty" class="empty-state{{ $processes->isEmpty() ? '' : ' d-none' }}">
                <h5 class="mb-2">No terminated students to track</h5>
                <p class="mb-0">Once a student is terminated, their process status will appear here.</p>
            </div>
            <div id="terminationTableWrap" class="termination-table-scroll table-responsive{{ $processes->isEmpty() ? ' d-none' : '' }}">
                <table class="table table-bordered table-hover termination-table mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Student</th>
                            <th>Course / Intake</th>
                            <th>Terminated On</th>
                            <th>Clearances</th>
                            <th>Overall</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="terminationTableBody">
                        @foreach($processes as $process)
                            @php
                                $summaryText = $process['clearance_summary']['requested'] === 0
                                    ? 'No requests'
                                    : $process['clearance_summary']['approved'] . ' approved / ' .
                                        $process['clearance_summary']['pending'] . ' pending / ' .
                                        $process['clearance_summary']['rejected'] . ' rejected / ' .
                                        $process['clearance_summary']['not_requested'] . ' not requested';
                            @endphp
                            <tr
                                data-student-id="{{ $process['student_id'] }}"
                                data-overall-status="{{ $process['overall_status']['key'] }}"
                                data-location="{{ strtolower((string) ($process['location'] ?? '')) }}"
                                data-course="{{ strtolower((string) ($process['course_name'] ?? '')) }}"
                                data-course-id="{{ $process['course_id'] ?? '' }}"
                                data-intake="{{ strtolower((string) ($process['intake_name'] ?? '')) }}"
                                data-intake-id="{{ $process['intake_id'] ?? '' }}"
                                data-search="{{ strtolower(implode(' ', array_filter([
                                    $process['student_id'],
                                    $process['student_nic'],
                                    $process['student_name'],
                                    $process['location'],
                                    $process['course_name'],
                                    $process['intake_name'],
                                    $process['termination_reason'],
                                ]))) }}"
                            >
                                <td data-label="Student">
                                    <div class="fw-semibold">{{ $process['student_name'] }}</div>
                                    <div class="text-muted small">ID: {{ $process['student_id'] }}</div>
                                    <div class="text-muted small">NIC: {{ $process['student_nic'] ?? 'N/A' }}</div>
                                    <div class="text-muted small">Location: {{ $process['location'] ?? 'N/A' }}</div>
                                </td>
                                <td data-label="Course / Intake">
                                    <div>{{ $process['course_name'] ?? 'N/A' }}</div>
                                    <div class="text-muted small">{{ $process['intake_name'] ?? 'No intake' }}</div>
                                </td>
                                <td data-label="Terminated On">
                                    <div>{{ $process['terminated_at'] ?? 'N/A' }}</div>
                                    <div class="text-muted small">By: {{ $process['terminated_by'] ?? 'N/A' }}</div>
                                </td>
                                <td data-label="Clearances">
                                    <div class="small">{{ $summaryText }}</div>
                                </td>
                                <td data-label="Overall">
                                    <span class="badge termination-badge {{ $process['overall_status']['badge_class'] }}">{{ $process['overall_status']['label'] }}</span>
                                </td>
                                <td class="termination-action" data-label="Action">
                                    <button type="button" class="btn btn-sm btn-outline-primary view-process-btn" data-student-id="{{ $process['student_id'] }}">
                                        View Process
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div id="terminationNoResults" class="empty-state mt-3 d-none">
                <h5 class="mb-2">No matching students</h5>
                <p class="mb-0">Try a different search or clear the filters.</p>
            </div>
            <div class="termination-footer{{ $processes->isEmpty() ? ' d-none' : '' }}" id="terminationPaginationBar">
                <div class="termination-page-size">
                    <label class="form-label mb-0 small text-muted" for="terminationPerPage">Per page</label>
                    <select id="terminationPerPage" class="form-select form-select-sm page-size-select">
                        <option value="10" selected>10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
                <div class="text-muted small" id="terminationResultRange"></div>
                <nav class="termination-pagination" aria-label="Termination tracking pages">
                    <ul class="pagination pagination-sm mb-0" id="terminationPagination"></ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="terminationProcessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Termination Process Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="detail-grid mb-3">
                    <div class="detail-card">
                        <h6>Student</h6>
                        <div id="processStudentBlock"></div>
                    </div>
                    <div class="detail-card">
                        <h6>Termination</h6>
                        <div id="processTerminationBlock"></div>
                    </div>
                    <div class="detail-card">
                        <h6>Current Context</h6>
                        <div id="processContextBlock"></div>
                    </div>
                    <div class="detail-card">
                        <h6>Overall Progress</h6>
                        <div id="processOverallBlock"></div>
                    </div>
                </div>

                <div class="card mb-0">
                    <div class="card-header fw-semibold">Clearance Status</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Clearance Type</th>
                                        <th>Status</th>
                                        <th>Requested At</th>
                                        <th>Approved / Rejected At</th>
                                        <th>Approved By</th>
                                        <th>Remarks</th>
                                        <th>Document</th>
                                    </tr>
                                </thead>
                                <tbody id="processClearanceBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    let processes = @json($processes->keyBy('student_id')->all());
    let catalog = @json($filters);
    let currentPage = 1;
    const tableBody = document.getElementById('terminationTableBody');
    const tableWrap = document.getElementById('terminationTableWrap');
    const emptyState = document.getElementById('terminationEmpty');
    const searchInput = document.getElementById('terminationSearch');
    const locationFilter = document.getElementById('locationFilter');
    const courseFilter = document.getElementById('courseFilter');
    const intakeFilter = document.getElementById('intakeFilter');
    const statusFilter = document.getElementById('overallStatusFilter');
    const perPageSelect = document.getElementById('terminationPerPage');
    const paginationBar = document.getElementById('terminationPaginationBar');
    const paginationEl = document.getElementById('terminationPagination');
    const resultRange = document.getElementById('terminationResultRange');
    const noResults = document.getElementById('terminationNoResults');
    const clearFiltersBtn = document.getElementById('clearTerminationFilters');
    const refreshBtn = document.getElementById('refreshTerminationBtn');
    const modalElement = document.getElementById('terminationProcessModal');
    const modal = modalElement ? new bootstrap.Modal(modalElement) : null;
    const refreshUrl = @json(route('termination.tracking'));

    function escapeHtml(text) {
        const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
        return String(text ?? '').replace(/[&<>"']/g, function (match) {
            return map[match];
        });
    }

    function renderBadge(label, badgeClass) {
        return '<span class="badge termination-badge ' + badgeClass + '">' + escapeHtml(label) + '</span>';
    }

    function normalizeValue(value) {
        return String(value ?? '').trim().toLowerCase();
    }

    function sameLocation(left, right) {
        const a = normalizeValue(left);
        const b = normalizeValue(right);
        if (!a || !b) {
            return false;
        }
        return a === b || a.indexOf(b) !== -1 || b.indexOf(a) !== -1;
    }

    function clearanceSummaryText(process) {
        if (!process.clearance_summary || Number(process.clearance_summary.requested) === 0) {
            return 'No requests';
        }
        return process.clearance_summary.approved + ' approved / ' +
            process.clearance_summary.pending + ' pending / ' +
            process.clearance_summary.rejected + ' rejected / ' +
            process.clearance_summary.not_requested + ' not requested';
    }

    function searchText(process) {
        return normalizeValue([
            process.student_id,
            process.student_nic,
            process.student_name,
            process.location,
            process.course_name,
            process.intake_name,
            process.termination_reason
        ].filter(Boolean).join(' '));
    }

    function fillSelect(select, placeholder, items, previousValue) {
        if (!select) {
            return;
        }

        const previous = previousValue !== undefined ? String(previousValue ?? '') : String(select.value ?? '');
        select.innerHTML = '';
        const allOption = document.createElement('option');
        allOption.value = '';
        allOption.textContent = placeholder;
        select.appendChild(allOption);

        const values = [];
        const seen = {};
        (items || []).forEach(function (item) {
            const value = String(item.value ?? '');
            if (!value || seen[value]) {
                return;
            }
            seen[value] = true;
            const option = document.createElement('option');
            option.value = value;
            option.textContent = item.label;
            if (item.location) {
                option.dataset.location = item.location;
            }
            if (item.courseId) {
                option.dataset.courseId = item.courseId;
            }
            select.appendChild(option);
            values.push(value);
        });

        select.value = values.indexOf(previous) !== -1 ? previous : '';
    }

    function matchingCourses() {
        const location = locationFilter?.value || '';
        return (catalog.courses || []).filter(function (course) {
            return !location || sameLocation(course.location, location);
        });
    }

    function matchingIntakes() {
        const location = locationFilter?.value || '';
        const courseId = courseFilter?.value || '';
        return (catalog.intakes || []).filter(function (intake) {
            if (location && !sameLocation(intake.location, location)) {
                return false;
            }
            if (courseId && String(intake.course_id) !== String(courseId)) {
                return false;
            }
            return true;
        });
    }

    function fillLocationOptions(previousValue) {
        fillSelect(locationFilter, 'All Locations', (catalog.locations || []).map(function (location) {
            return { value: location, label: location };
        }), previousValue);
    }

    function fillCourseOptions(previousValue) {
        fillSelect(courseFilter, 'All Courses', matchingCourses().map(function (course) {
            return {
                value: course.id,
                label: course.name + ' (' + (course.location || '-') + ')',
                location: course.location
            };
        }), previousValue);
    }

    function fillIntakeOptions(previousValue) {
        fillSelect(intakeFilter, 'All Intakes', matchingIntakes().map(function (intake) {
            return {
                value: intake.id,
                label: intake.name + (intake.location ? ' (' + intake.location + ')' : ''),
                location: intake.location,
                courseId: intake.course_id
            };
        }), previousValue);
    }

    function rebuildCatalogOptions(preserveSelection) {
        const savedLocation = preserveSelection ? (locationFilter?.value || '') : '';
        const savedCourse = preserveSelection ? (courseFilter?.value || '') : '';
        const savedIntake = preserveSelection ? (intakeFilter?.value || '') : '';
        fillLocationOptions(savedLocation);
        fillCourseOptions(savedCourse);
        fillIntakeOptions(savedIntake);
    }

    function matchedRows() {
        if (!tableBody) {
            return [];
        }

        const query = normalizeValue(searchInput?.value);
        const location = normalizeValue(locationFilter?.value);
        const courseId = String(courseFilter?.value || '');
        const intakeId = String(intakeFilter?.value || '');
        const status = normalizeValue(statusFilter?.value);

        return Array.from(tableBody.querySelectorAll('tr[data-student-id]')).filter(function (row) {
            const matchesQuery = !query || (row.dataset.search || '').includes(query);
            const matchesStatus = !status || row.dataset.overallStatus === status;
            const matchesLocation = !location || row.dataset.location === location;
            const matchesCourse = !courseId || String(row.dataset.courseId || '') === courseId;
            const matchesIntake = !intakeId || String(row.dataset.intakeId || '') === intakeId;
            return matchesQuery && matchesLocation && matchesCourse && matchesIntake && matchesStatus;
        });
    }

    function renderPagination(total, from, to, lastPage) {
        if (!paginationEl || !resultRange || !paginationBar) {
            return;
        }

        paginationEl.innerHTML = '';
        paginationBar.classList.toggle('d-none', total === 0);

        if (!total) {
            resultRange.textContent = '';
            return;
        }

        resultRange.textContent = 'Showing ' + from + ' to ' + to + ' of ' + total;

        const addItem = function (label, targetPage, options) {
            const settings = options || {};
            const li = document.createElement('li');
            li.className = 'page-item';
            if (settings.disabled) {
                li.classList.add('disabled');
            }
            if (settings.active) {
                li.classList.add('active');
            }

            const btn = document.createElement(settings.disabled || settings.active ? 'span' : 'button');
            btn.className = 'page-link';
            btn.textContent = label;
            if (btn.tagName === 'BUTTON') {
                btn.type = 'button';
                btn.addEventListener('click', function () {
                    currentPage = targetPage;
                    applyFilters({ resetPage: false });
                });
            }
            li.appendChild(btn);
            paginationEl.appendChild(li);
        };

        addItem('Previous', currentPage - 1, { disabled: currentPage <= 1 });

        const start = Math.max(1, currentPage - 2);
        const end = Math.min(lastPage, currentPage + 2);
        if (start > 1) {
            addItem('1', 1);
            if (start > 2) {
                addItem('...', currentPage, { disabled: true });
            }
        }
        for (let page = start; page <= end; page += 1) {
            addItem(String(page), page, { active: page === currentPage });
        }
        if (end < lastPage) {
            if (end < lastPage - 1) {
                addItem('...', currentPage, { disabled: true });
            }
            addItem(String(lastPage), lastPage);
        }

        addItem('Next', currentPage + 1, { disabled: currentPage >= lastPage });
    }

    function applyFilters(options) {
        if (!tableBody) {
            return;
        }

        const resetPage = !options || options.resetPage !== false;
        if (resetPage) {
            currentPage = 1;
        }

        const rows = Array.from(tableBody.querySelectorAll('tr[data-student-id]'));
        const matched = matchedRows();
        const perPage = Math.max(1, Number(perPageSelect?.value || 10));
        const lastPage = Math.max(1, Math.ceil(matched.length / perPage) || 1);
        if (currentPage > lastPage) {
            currentPage = lastPage;
        }

        const start = (currentPage - 1) * perPage;
        const end = start + perPage;

        rows.forEach(function (row) {
            row.style.display = 'none';
        });
        matched.forEach(function (row, index) {
            row.style.display = (index >= start && index < end) ? '' : 'none';
        });

        const hasRows = rows.length > 0;
        emptyState?.classList.toggle('d-none', hasRows);
        tableWrap?.classList.toggle('d-none', !hasRows || matched.length === 0);
        noResults?.classList.toggle('d-none', !hasRows || matched.length !== 0);
        renderPagination(
            matched.length,
            matched.length ? start + 1 : 0,
            Math.min(matched.length, end),
            lastPage
        );
    }

    function renderRows(processList) {
        if (!tableBody) {
            return;
        }

        tableBody.innerHTML = processList.map(function (process) {
            return [
                '<tr data-student-id="' + escapeHtml(process.student_id) + '"',
                ' data-overall-status="' + escapeHtml(process.overall_status.key) + '"',
                ' data-location="' + escapeHtml(normalizeValue(process.location)) + '"',
                ' data-course="' + escapeHtml(normalizeValue(process.course_name)) + '"',
                ' data-course-id="' + escapeHtml(process.course_id || '') + '"',
                ' data-intake="' + escapeHtml(normalizeValue(process.intake_name)) + '"',
                ' data-intake-id="' + escapeHtml(process.intake_id || '') + '"',
                ' data-search="' + escapeHtml(searchText(process)) + '">',
                '<td data-label="Student"><div class="fw-semibold">' + escapeHtml(process.student_name) + '</div>',
                '<div class="text-muted small">ID: ' + escapeHtml(process.student_id) + '</div>',
                '<div class="text-muted small">NIC: ' + escapeHtml(process.student_nic || 'N/A') + '</div>',
                '<div class="text-muted small">Location: ' + escapeHtml(process.location || 'N/A') + '</div></td>',
                '<td data-label="Course / Intake"><div>' + escapeHtml(process.course_name || 'N/A') + '</div>',
                '<div class="text-muted small">' + escapeHtml(process.intake_name || 'No intake') + '</div></td>',
                '<td data-label="Terminated On"><div>' + escapeHtml(process.terminated_at || 'N/A') + '</div>',
                '<div class="text-muted small">By: ' + escapeHtml(process.terminated_by || 'N/A') + '</div></td>',
                '<td data-label="Clearances"><div class="small">' + escapeHtml(clearanceSummaryText(process)) + '</div></td>',
                '<td data-label="Overall">' + renderBadge(process.overall_status.label, process.overall_status.badge_class) + '</td>',
                '<td class="termination-action" data-label="Action"><button type="button" class="btn btn-sm btn-outline-primary view-process-btn" data-student-id="' + escapeHtml(process.student_id) + '">View Process</button></td>',
                '</tr>'
            ].join('');
        }).join('');
    }

    function setHtml(id, html) {
        const element = document.getElementById(id);
        if (element) {
            element.innerHTML = html;
        }
    }

    function academicStatusLabel(status) {
        const value = String(status || '').replace(/_/g, ' ');
        return value ? value.charAt(0).toUpperCase() + value.slice(1) : 'Terminated';
    }

    function renderProcess(studentId) {
        const process = processes[studentId];
        if (!process) {
            return;
        }

        setHtml('processStudentBlock', [
            '<div class="fw-semibold">' + escapeHtml(process.student_name) + '</div>',
            '<div class="detail-meta">Student ID: ' + escapeHtml(process.student_id) + '</div>',
            '<div class="detail-meta">NIC: ' + escapeHtml(process.student_nic || 'N/A') + '</div>',
            '<div class="detail-meta">Location: ' + escapeHtml(process.location || 'N/A') + '</div>',
            '<div class="mt-2"><a class="btn btn-sm btn-outline-secondary" href="' + escapeHtml(process.profile_url) + '">Open Profile</a></div>'
        ].join(''));

        setHtml('processTerminationBlock', [
            '<div>' + renderBadge(academicStatusLabel(process.academic_status), 'bg-danger') + '</div>',
            '<div class="detail-meta mt-2">Terminated At: ' + escapeHtml(process.terminated_at || 'N/A') + '</div>',
            '<div class="detail-meta">Terminated By: ' + escapeHtml(process.terminated_by || 'N/A') + '</div>',
            '<div class="mt-2"><strong>Reason:</strong><br>' + escapeHtml(process.termination_reason || 'No reason recorded') + '</div>',
            (process.termination_document_url
                ? '<div class="mt-2"><a class="btn btn-sm btn-outline-primary" target="_blank" href="' + escapeHtml(process.termination_document_url) + '">View Document</a></div>'
                : '')
        ].join(''));

        setHtml('processContextBlock', [
            '<div><strong>Course:</strong> ' + escapeHtml(process.course_name || 'N/A') + '</div>',
            '<div class="detail-meta">Intake: ' + escapeHtml(process.intake_name || 'N/A') + '</div>'
        ].join(''));

        setHtml('processOverallBlock', [
            '<div>' + renderBadge(process.overall_status.label, process.overall_status.badge_class) + '</div>',
            '<div class="mt-2 detail-meta">Clearances: ' + process.clearance_summary.approved + ' approved / ' + process.clearance_summary.pending + ' pending / ' + process.clearance_summary.rejected + ' rejected / ' + process.clearance_summary.not_requested + ' not requested</div>',
        ].join(''));

        const clearanceRows = process.clearances.map(function (clearance) {
            return [
                '<tr>',
                '<td data-label="Clearance Type">' + escapeHtml(clearance.label) + '</td>',
                '<td data-label="Status">' + renderBadge(clearance.status_label, clearance.badge_class) + '</td>',
                '<td data-label="Requested At">' + escapeHtml(clearance.requested_at || 'N/A') + '</td>',
                '<td data-label="Approved / Rejected At">' + escapeHtml(clearance.approved_at || 'N/A') + '</td>',
                '<td data-label="Approved By">' + escapeHtml(clearance.approved_by || 'N/A') + '</td>',
                '<td data-label="Remarks">' + escapeHtml(clearance.remarks || 'N/A') + '</td>',
                '<td data-label="Document">' + (clearance.clearance_slip_url
                    ? '<a target="_blank" href="' + escapeHtml(clearance.clearance_slip_url) + '">View</a>'
                    : '<span class="text-muted">N/A</span>') + '</td>',
                '</tr>'
            ].join('');
        }).join('');

        setHtml('processClearanceBody', clearanceRows);

        if (modal) {
            modal.show();
        }
    }

    function refreshList() {
        if (!refreshBtn) {
            return;
        }
        refreshBtn.disabled = true;
        const originalHtml = refreshBtn.innerHTML;
        refreshBtn.innerHTML = '<i class="ti ti-loader me-1"></i>Refreshing';

        fetch(refreshUrl, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('Refresh failed');
                }
                return response.json();
            })
            .then(function (data) {
                const processList = data.processes || [];
                processes = {};
                processList.forEach(function (process) {
                    processes[process.student_id] = process;
                });

                document.getElementById('summaryTotal').textContent = data.summary?.total ?? 0;
                document.getElementById('summaryInProgress').textContent = data.summary?.clearance_in_progress ?? 0;
                document.getElementById('summaryCompleted').textContent = data.summary?.completed ?? 0;

                catalog = data.filters || catalog;
                rebuildCatalogOptions(true);
                renderRows(processList);
                applyFilters({ resetPage: true });
            })
            .catch(function () {
                if (typeof showErrorMessage === 'function') {
                    showErrorMessage('Could not refresh termination tracking.');
                }
            })
            .finally(function () {
                refreshBtn.disabled = false;
                refreshBtn.innerHTML = originalHtml;
            });
    }

    searchInput?.addEventListener('input', function () {
        applyFilters({ resetPage: true });
    });
    locationFilter?.addEventListener('change', function () {
        fillCourseOptions();
        fillIntakeOptions();
        applyFilters({ resetPage: true });
    });
    courseFilter?.addEventListener('change', function () {
        fillIntakeOptions();
        applyFilters({ resetPage: true });
    });
    intakeFilter?.addEventListener('change', function () {
        applyFilters({ resetPage: true });
    });
    statusFilter?.addEventListener('change', function () {
        applyFilters({ resetPage: true });
    });
    perPageSelect?.addEventListener('change', function () {
        applyFilters({ resetPage: true });
    });
    refreshBtn?.addEventListener('click', refreshList);

    clearFiltersBtn?.addEventListener('click', function () {
        if (searchInput) {
            searchInput.value = '';
        }
        if (statusFilter) {
            statusFilter.value = '';
            statusFilter.dispatchEvent(new Event('change', { bubbles: true }));
        }
        rebuildCatalogOptions(false);
        applyFilters({ resetPage: true });
    });

    tableBody?.addEventListener('click', function (event) {
        const button = event.target.closest('.view-process-btn');
        if (!button) {
            return;
        }

        renderProcess(button.getAttribute('data-student-id'));
    });

    rebuildCatalogOptions(true);
    applyFilters({ resetPage: true });
});
</script>
@endsection
