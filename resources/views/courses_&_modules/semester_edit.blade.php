@extends('inc.app')

@section('title', 'NEBULA | Edit Semester')

@section('content')
@php
    $intakeLocation = optional($semester->intake)->location ?? '';
    $startDate = $semester->start_date
        ? ($semester->start_date instanceof \Carbon\Carbon
            ? $semester->start_date->format('Y-m-d')
            : \Carbon\Carbon::parse($semester->start_date)->format('Y-m-d'))
        : '';
    $endDate = $semester->end_date
        ? ($semester->end_date instanceof \Carbon\Carbon
            ? $semester->end_date->format('Y-m-d')
            : \Carbon\Carbon::parse($semester->end_date)->format('Y-m-d'))
        : '';
@endphp
<style nonce="{{ $cspNonce }}">
    .semester-edit-page .nebula-select {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .semester-edit-header,
    .semester-edit-actions {
        gap: 0.75rem;
    }
    .semester-edit-page select:disabled,
    .semester-edit-page .nebula-select.is-disabled .nebula-select-toggle {
        background-color: #f5f5f5 !important;
        border-color: #ddd !important;
        color: #aaa !important;
        box-shadow: none;
    }
    .semester-module-picker {
        display: grid;
        grid-template-columns: minmax(10rem, 14rem) minmax(0, 1fr) auto;
        gap: 0.5rem;
        align-items: end;
    }
    .semester-module-add .btn {
        white-space: nowrap;
    }
    .semester-modules-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    #modules_table {
        min-width: 640px;
    }
    .semester-toast-wrap {
        z-index: 20000;
        pointer-events: none;
    }
    .semester-toast-wrap .toast {
        pointer-events: auto;
    }
    #duplicateModal .modal-dialog {
        max-width: min(32rem, calc(100vw - 1.5rem));
        margin: 1rem auto;
    }
    #duplicateModal .modal-content {
        height: auto;
        max-height: calc(100dvh - 2rem);
    }
    #duplicateModal .modal-body {
        overflow-y: auto;
    }
    @media (max-width: 767.98px) {
        .semester-edit-header {
            flex-direction: column;
            align-items: stretch !important;
        }
        .semester-edit-actions,
        .semester-edit-actions .btn {
            width: 100%;
        }
        .semester-module-picker {
            grid-template-columns: 1fr;
        }
        .semester-module-add .btn {
            width: 100%;
        }
        #modules_table {
            min-width: 0;
        }
        #modules_table thead {
            display: none;
        }
        #modules_table,
        #modules_table tbody,
        #modules_table tr,
        #modules_table td {
            display: block;
            width: 100%;
        }
        #modules_table tbody tr {
            margin-bottom: 0.85rem;
            border: 1px solid #dee2e6;
            border-radius: 10px;
            padding: 0.75rem 0.9rem;
            background: #fff;
        }
        #modules_table td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 0.75rem;
            border: 0;
            border-bottom: 1px solid #f1f3f5;
            padding: 0.45rem 0;
        }
        #modules_table td:last-child {
            border-bottom: 0;
        }
        #modules_table td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #6c757d;
            flex: 0 0 38%;
            max-width: 38%;
        }
        #modules_table td[data-label="Action"] {
            justify-content: flex-end;
            align-items: center;
        }
        .semester-toast-wrap {
            top: auto !important;
            bottom: 0.75rem;
            left: auto;
            right: 0.75rem;
            width: auto;
            max-width: calc(100vw - 1.5rem);
        }
        .semester-duplicate-footer {
            flex-wrap: wrap;
            gap: 0.5rem;
        }
        .semester-duplicate-footer .btn {
            flex: 1 1 auto;
            width: 100%;
            margin: 0 !important;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 semester-edit-page">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4 semester-edit-header">
                <h2 class="mb-0">Edit Semester</h2>
                <div class="d-flex flex-wrap semester-edit-actions">
                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal" data-bs-target="#duplicateModal">
                        <i class="ti ti-copy"></i> Duplicate
                    </button>
                    <a href="{{ route('semesters.index') }}" class="btn btn-secondary">
                        <i class="ti ti-arrow-left"></i> Back to Semesters
                    </a>
                </div>
            </div>
            <hr>
            <form action="{{ route('semesters.update', $semester) }}" method="POST" id="semesterEditForm">
                @csrf
                @method('PUT')
                <div class="row mb-3 g-2 align-items-md-center">
                    <label for="location" class="col-md-3 col-lg-2 col-form-label">Location <span class="text-danger">*</span></label>
                    <div class="col-md-9 col-lg-10">
                        <select name="location" id="location" class="form-select" required>
                            <option disabled value="">Select a Location</option>
                            <option value="Welisara" {{ $intakeLocation === 'Welisara' ? 'selected' : '' }}>Nebula Institute of Technology - Welisara</option>
                            <option value="Moratuwa" {{ $intakeLocation === 'Moratuwa' ? 'selected' : '' }}>Nebula Institute of Technology - Moratuwa</option>
                            <option value="Peradeniya" {{ $intakeLocation === 'Peradeniya' ? 'selected' : '' }}>Nebula Institute of Technology - Peradeniya</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3 g-2 align-items-md-center">
                    <label for="course_id" class="col-md-3 col-lg-2 col-form-label">Course <span class="text-danger">*</span></label>
                    <div class="col-md-9 col-lg-10">
                        <select name="course_id" id="course_id" class="form-select" required>
                            <option disabled value="">Select Course</option>
                            @foreach($courses as $course)
                                <option value="{{ $course->course_id }}" {{ $semester->course_id == $course->course_id ? 'selected' : '' }}>
                                    {{ $course->course_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-3 g-2 align-items-md-center">
                    <label for="intake_id" class="col-md-3 col-lg-2 col-form-label">Intake <span class="text-danger">*</span></label>
                    <div class="col-md-9 col-lg-10">
                        <select name="intake_id" id="intake_id" class="form-select" required>
                            <option disabled value="">Select Intake</option>
                            @foreach($intakes as $intake)
                                <option value="{{ $intake->intake_id }}" {{ $semester->intake_id == $intake->intake_id ? 'selected' : '' }}>
                                    {{ $intake->batch }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-3 g-2 align-items-md-center">
                    <label for="semester" class="col-md-3 col-lg-2 col-form-label">Semester <span class="text-danger">*</span></label>
                    <div class="col-md-9 col-lg-10">
                        <select name="semester" id="semester" class="form-select" required>
                            <option disabled value="">Select Semester</option>
                            <option value="{{ $semesterNumber }}" selected>{{ $semesterLabel }}</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3 g-2 align-items-md-center">
                    <label for="start_date" class="col-md-3 col-lg-2 col-form-label">Start Date <span class="text-danger">*</span></label>
                    <div class="col-md-9 col-lg-10">
                        <input type="date" name="start_date" id="start_date" class="form-control" value="{{ $startDate }}" required>
                    </div>
                </div>
                <div class="row mb-3 g-2 align-items-md-center">
                    <label for="end_date" class="col-md-3 col-lg-2 col-form-label">End Date <span class="text-danger">*</span></label>
                    <div class="col-md-9 col-lg-10">
                        <input type="date" name="end_date" id="end_date" class="form-control" value="{{ $endDate }}" required>
                    </div>
                </div>
                <div class="row mb-3 g-2">
                    <div class="col-md-3 col-lg-2 col-form-label">Status</div>
                    <div class="col-md-9 col-lg-10">
                        <p class="form-text mb-0 pt-md-2">Status is set automatically from the start and end dates (upcoming, active, or completed).</p>
                    </div>
                </div>
                <div class="row mb-3 g-2" id="specializationScopeRow" style="display:none;">
                    <label class="col-md-3 col-lg-2 col-form-label">Module Applicability</label>
                    <div class="col-md-9 col-lg-10">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="spec_scope" id="spec_scope_all" value="all" checked>
                            <label class="form-check-label" for="spec_scope_all">All specializations (common)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="spec_scope" id="spec_scope_selected" value="selected">
                            <label class="form-check-label" for="spec_scope_selected">Selected specializations only</label>
                        </div>
                        <div id="specializationCheckboxes" class="mt-2 border rounded p-2" style="display:none;"></div>
                        <small class="form-text text-muted">Use selected specializations when a module is common to only some tracks.</small>
                    </div>
                </div>
                <div class="row mb-3 g-2">
                    <label class="col-md-3 col-lg-2 col-form-label">Modules <span class="text-danger">*</span></label>
                    <div class="col-md-9 col-lg-10">
                        <div class="semester-module-picker">
                            <div class="semester-module-type">
                                <label class="form-label small text-muted d-md-none" for="module_type">Type</label>
                                <select id="module_type" class="form-select">
                                    <option value="Core">Core</option>
                                    <option value="Elective">Elective</option>
                                    <option value="Special Unit Compulsory (S/U)">Special Unit Compulsory (S/U)</option>
                                </select>
                            </div>
                            <div class="semester-module-choice">
                                <label class="form-label small text-muted d-md-none" for="module_select">Module</label>
                                <select id="module_select" class="form-select" disabled>
                                    <option selected disabled value="">Select a module...</option>
                                </select>
                            </div>
                            <div class="semester-module-add">
                                <button type="button" id="add_module_btn" class="btn btn-primary">Add</button>
                            </div>
                        </div>
                        <div class="table-responsive semester-modules-scroll mt-2">
                            <table class="table table-bordered mb-0" id="modules_table">
                                <thead style="background:#6c8cff;color:white;">
                                    <tr id="modulesTableHeaderRow">
                                        <th>Semester</th>
                                        <th>Module Name</th>
                                        <th>Type</th>
                                        <th>Credits</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <span id="submitText">Update Semester</span>
                        <span id="submitSpinner" class="spinner-border spinner-border-sm ms-2" style="display: none;"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="duplicateModal" tabindex="-1" aria-labelledby="duplicateModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="duplicateModalLabel">Duplicate Semester</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted">This will create a copy of "{{ $semesterLabel }}" with all its modules and settings.</p>
                <div class="mb-3">
                    <label for="newSemesterName" class="form-label">New Semester Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newSemesterName" placeholder="Enter new semester name" required>
                </div>
                <div class="mb-3">
                    <label for="newStartDate" class="form-label">Start Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="newStartDate" required>
                </div>
                <div class="mb-3">
                    <label for="newEndDate" class="form-label">End Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="newEndDate" required>
                </div>
            </div>
            <div class="modal-footer semester-duplicate-footer">
                <button type="button" class="btn btn-secondary" id="duplicateCancelBtn" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="duplicateSemesterBtn">
                    <i class="ti ti-copy"></i> Duplicate Semester
                </button>
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
<script nonce="{{ $cspNonce }}">
let courseSpecializations = [];
let courseSemesterFormat = @json(optional($semester->course)->semester_format ?? 'numerical');
let addedModules = [];
let allModules = [];

const existingModules = @json($semesterModules);
const semesterModules = @json($semester->modules);
const originalSemesterId = {{ (int) $semester->id }};
const originalSemesterNumber = @json((string) $semesterNumber);
const originalCourseId = @json((string) $semester->course_id);
const originalIntakeId = @json((string) $semester->intake_id);
const originalLocation = @json((string) $intakeLocation);

document.addEventListener('DOMContentLoaded', function() {
    const locationSelect = document.getElementById('location');
    const courseSelect = document.getElementById('course_id');
    const intakeSelect = document.getElementById('intake_id');
    const semesterSelect = document.getElementById('semester');
    const moduleTypeSelect = document.getElementById('module_type');
    const moduleSelect = document.getElementById('module_select');
    const addModuleBtn = document.getElementById('add_module_btn');
    const modulesTableBody = document.querySelector('#modules_table tbody');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');

    function escapeHtml(text) {
        if (text == null) {
            return '';
        }
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    function setSelectOptions(select, html, disabled) {
        select.innerHTML = html;
        select.disabled = !!disabled;
    }

    function resetAndDisable(select, placeholder) {
        setSelectOptions(select, `<option value="" selected disabled>${escapeHtml(placeholder)}</option>`, true);
    }

    function typeLabel(type) {
        const labels = {
            core: 'Core',
            elective: 'Elective',
            special_unit_compulsory: 'Special Unit Compulsory (S/U)'
        };
        return labels[String(type || '').toLowerCase()] || type || '';
    }

    function semesterDisplayName(semesterNumber) {
        const number = parseInt(semesterNumber, 10);
        if (courseSemesterFormat === 'alphabetical' && number >= 1 && number <= 26) {
            return 'Semester ' + String.fromCharCode(64 + number);
        }
        return 'Semester ' + String(semesterNumber);
    }

    function isOriginalContext() {
        return locationSelect.value === originalLocation
            && String(courseSelect.value) === originalCourseId
            && String(intakeSelect.value) === originalIntakeId
            && String(semesterSelect.value) === originalSemesterNumber;
    }

    function populateSpecializationCheckboxes() {
        const container = document.getElementById('specializationCheckboxes');
        if (!container) return;
        container.innerHTML = courseSpecializations.map((spec, index) => `
            <div class="form-check form-check-inline">
                <input class="form-check-input spec-checkbox" type="checkbox" value="${escapeHtml(String(spec))}" id="spec_cb_${index}">
                <label class="form-check-label" for="spec_cb_${index}">${escapeHtml(String(spec))}</label>
            </div>
        `).join('');
    }

    function formatSpecializationsLabel(specializations) {
        if (!specializations || specializations.length === 0) {
            return 'All Specializations';
        }
        return specializations.join(', ');
    }

    function decodeStoredSpecializations(semesterModule) {
        if (semesterModule.specializations) {
            if (Array.isArray(semesterModule.specializations)) {
                return semesterModule.specializations.length ? semesterModule.specializations : null;
            }
            try {
                const parsed = JSON.parse(semesterModule.specializations);
                return Array.isArray(parsed) && parsed.length ? parsed : null;
            } catch (error) {
                return null;
            }
        }
        if (semesterModule.specialization && semesterModule.specialization !== 'General') {
            return [semesterModule.specialization];
        }
        return null;
    }

    function getSelectedSpecializationsForModule() {
        if (!courseSpecializations.length) {
            return null;
        }
        if (document.getElementById('spec_scope_all')?.checked) {
            return null;
        }
        const selected = [];
        document.querySelectorAll('.spec-checkbox:checked').forEach(checkbox => {
            selected.push(checkbox.value);
        });
        if (selected.length === 0) {
            window.showToast('Select at least one specialization or choose All specializations.', 'warning');
            return undefined;
        }
        if (selected.length === courseSpecializations.length) {
            return null;
        }
        return selected;
    }

    document.querySelectorAll('input[name="spec_scope"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const checkboxContainer = document.getElementById('specializationCheckboxes');
            if (!checkboxContainer) return;
            checkboxContainer.style.display = document.getElementById('spec_scope_selected')?.checked ? 'block' : 'none';
        });
    });

    function appendModuleRow(moduleData, displayName) {
        const row = document.createElement('tr');
        let rowHtml = `<td data-label="Semester">${escapeHtml(displayName)}</td>`;
        if (courseSpecializations.length > 0) {
            rowHtml += `<td data-label="Specialization">${escapeHtml(formatSpecializationsLabel(moduleData.specializations))}</td>`;
        }
        rowHtml += `
            <td data-label="Module Name">${escapeHtml(moduleData.moduleName)}</td>
            <td data-label="Type">${escapeHtml(typeLabel(moduleData.moduleType))}</td>
            <td data-label="Credits">${escapeHtml(moduleData.moduleCredits ?? '')}</td>
            <td data-label="Action"><button type="button" class="btn btn-danger btn-sm remove-module">Remove</button></td>
        `;
        row.innerHTML = rowHtml;
        row.dataset.moduleId = moduleData.moduleId;
        row.dataset.semester = moduleData.semester;
        row.dataset.specializations = moduleData.specializations ? JSON.stringify(moduleData.specializations) : '';
        modulesTableBody.appendChild(row);
    }

    function loadExistingModules() {
        modulesTableBody.innerHTML = '';
        addedModules = [];
        const displayName = semesterSelect.options[semesterSelect.selectedIndex]?.text || semesterDisplayName(semesterSelect.value);
        existingModules.forEach(semesterModule => {
            const module = semesterModules.find(m => String(m.module_id) === String(semesterModule.module_id));
            if (!module) return;
            const specializations = decodeStoredSpecializations(semesterModule);
            const moduleData = {
                moduleId: String(module.module_id),
                moduleName: module.module_name,
                moduleType: module.module_type,
                moduleCredits: module.credits,
                semester: String(semesterSelect.value),
                specializations
            };
            addedModules.push(moduleData);
            appendModuleRow(moduleData, displayName);
        });
    }

    function updateModulesTableHeader() {
        const headerRow = document.getElementById('modulesTableHeaderRow');
        if (!headerRow) return;
        headerRow.querySelectorAll('th').forEach(th => {
            if (th.textContent.trim() === 'Specialization') th.remove();
        });
        if (courseSpecializations.length > 0) {
            const th = document.createElement('th');
            th.textContent = 'Specialization';
            headerRow.insertBefore(th, headerRow.children[1]);
            modulesTableBody.querySelectorAll('tr').forEach(row => {
                if (row.querySelector('td[data-label="Specialization"]')) return;
                let label = 'All Specializations';
                if (row.dataset.specializations) {
                    try {
                        label = formatSpecializationsLabel(JSON.parse(row.dataset.specializations));
                    } catch (error) {
                        label = 'All Specializations';
                    }
                }
                const td = document.createElement('td');
                td.setAttribute('data-label', 'Specialization');
                td.textContent = label;
                row.insertBefore(td, row.children[1]);
            });
        }
    }

    function applyCourseDetails(course) {
        courseSemesterFormat = course.semester_format || 'numerical';
        let specializations = [];
        if (course.specializations) {
            if (typeof course.specializations === 'string') {
                try {
                    specializations = JSON.parse(course.specializations);
                } catch (e) {
                    specializations = [];
                }
            } else if (Array.isArray(course.specializations)) {
                specializations = course.specializations;
            }
        }
        specializations = specializations.filter(spec => spec && String(spec).trim() !== '');
        if (specializations.length > 0) {
            courseSpecializations = specializations;
            populateSpecializationCheckboxes();
            document.getElementById('specializationScopeRow').style.display = '';
            document.getElementById('spec_scope_all').checked = true;
            document.getElementById('specializationCheckboxes').style.display = 'none';
        } else {
            courseSpecializations = [];
            document.getElementById('specializationScopeRow').style.display = 'none';
        }
        updateModulesTableHeader();
    }

    function filterAndPopulateModules() {
        const typeMap = {
            'Core': 'core',
            'Elective': 'elective',
            'Special Unit Compulsory (S/U)': 'special_unit_compulsory'
        };
        const selectedType = typeMap[moduleTypeSelect.value];
        let options = '<option value="" selected disabled>Select a module...</option>';
        const filtered = allModules.filter(m => String(m.module_type || '').toLowerCase() === selectedType);
        if (filtered.length > 0) {
            filtered.forEach(module => {
                const moduleCode = module.module_code ? ` (${escapeHtml(String(module.module_code))})` : '';
                options += `<option value="${escapeHtml(String(module.module_id))}" data-type="${escapeHtml(String(module.module_type ?? ''))}" data-credits="${escapeHtml(String(module.credits ?? ''))}">${escapeHtml(String(module.module_name))}${moduleCode}</option>`;
            });
            setSelectOptions(moduleSelect, options, false);
        } else {
            setSelectOptions(moduleSelect, '<option value="" selected disabled>No modules available for this type</option>', false);
        }
    }

    function loadAvailableModules() {
        if (!semesterSelect.value || !intakeSelect.value || !courseSelect.value || !locationSelect.value) {
            resetAndDisable(moduleSelect, 'Select a module...');
            allModules = [];
            return;
        }
        setSelectOptions(moduleSelect, '<option value="" selected disabled>Loading modules...</option>', true);
        const usingOriginal = isOriginalContext();
        fetch('/semester/get-filtered-modules', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
            body: JSON.stringify({
                location: locationSelect.value,
                course_id: courseSelect.value,
                intake_id: intakeSelect.value,
                semester: usingOriginal ? originalSemesterId : semesterSelect.value,
                creating: !usingOriginal
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.modules && data.modules.length > 0) {
                allModules = data.modules;
                filterAndPopulateModules();
            } else {
                allModules = [];
                resetAndDisable(moduleSelect, 'No modules available');
            }
        })
        .catch(() => {
            allModules = [];
            resetAndDisable(moduleSelect, 'Failed to load modules');
        });
    }

    function fetchIntakesForSemesterEdit() {
        if (!courseSelect.value || !locationSelect.value) {
            resetAndDisable(intakeSelect, 'Select Intake');
            return;
        }
        fetch(`/semester/get-intakes/${encodeURIComponent(courseSelect.value)}/${encodeURIComponent(locationSelect.value)}`)
            .then(response => response.json())
            .then(data => {
                if (data.intakes && data.intakes.length > 0) {
                    let options = '<option value="" selected disabled>Select Intake</option>';
                    data.intakes.forEach(intake => {
                        options += `<option value="${escapeHtml(String(intake.intake_id))}">${escapeHtml(String(intake.batch))}</option>`;
                    });
                    setSelectOptions(intakeSelect, options, false);
                } else {
                    resetAndDisable(intakeSelect, 'No intakes available');
                }
            })
            .catch(() => {
                resetAndDisable(intakeSelect, 'Failed to load intakes');
            });
    }

    function populateSemesterOptions(semesters, selectedNumber) {
        const numbers = [];
        (semesters || []).forEach(sem => {
            const value = String(sem.semester_id);
            if (!numbers.includes(value)) {
                numbers.push(value);
            }
        });
        if (selectedNumber && !numbers.includes(String(selectedNumber))) {
            numbers.unshift(String(selectedNumber));
        }
        if (numbers.length === 0) {
            resetAndDisable(semesterSelect, 'No semesters available');
            return;
        }
        let options = '<option value="" selected disabled>Select Semester</option>';
        numbers.forEach(number => {
            const selected = String(number) === String(selectedNumber) ? ' selected' : '';
            options += `<option value="${escapeHtml(String(number))}"${selected}>${escapeHtml(semesterDisplayName(number))}</option>`;
        });
        setSelectOptions(semesterSelect, options, false);
    }

    if (courseSelect.value) {
        fetch(`/api/courses/${courseSelect.value}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.course) {
                    applyCourseDetails(data.course);
                }
                loadExistingModules();
                loadAvailableModules();
            })
            .catch(() => {
                loadExistingModules();
                loadAvailableModules();
            });
    } else {
        loadExistingModules();
    }

    locationSelect.addEventListener('change', function() {
        resetAndDisable(courseSelect, 'Select Course');
        resetAndDisable(intakeSelect, 'Select Intake');
        resetAndDisable(semesterSelect, 'Select Semester');
        resetAndDisable(moduleSelect, 'Select a module...');
        addedModules = [];
        allModules = [];
        modulesTableBody.innerHTML = '';
        if (!locationSelect.value) return;
        setSelectOptions(courseSelect, '<option value="" selected disabled>Loading courses...</option>', true);
        fetch(`/courses/by-location?location=${encodeURIComponent(locationSelect.value)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.courses && data.courses.length > 0) {
                    let options = '<option value="" selected disabled>Select Course</option>';
                    data.courses.forEach(course => {
                        options += `<option value="${escapeHtml(String(course.course_id))}">${escapeHtml(String(course.course_name))}</option>`;
                    });
                    setSelectOptions(courseSelect, options, false);
                } else {
                    resetAndDisable(courseSelect, 'No courses available');
                }
            })
            .catch(() => {
                resetAndDisable(courseSelect, 'Failed to load courses');
            });
    });

    courseSelect.addEventListener('change', function() {
        resetAndDisable(intakeSelect, 'Select Intake');
        resetAndDisable(semesterSelect, 'Select Semester');
        resetAndDisable(moduleSelect, 'Select a module...');
        addedModules = [];
        allModules = [];
        modulesTableBody.innerHTML = '';
        if (!courseSelect.value) {
            courseSpecializations = [];
            document.getElementById('specializationScopeRow').style.display = 'none';
            updateModulesTableHeader();
            return;
        }
        fetch(`/api/courses/${courseSelect.value}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.course) {
                    applyCourseDetails(data.course);
                } else {
                    courseSpecializations = [];
                    document.getElementById('specializationScopeRow').style.display = 'none';
                    updateModulesTableHeader();
                }
                fetchIntakesForSemesterEdit();
            })
            .catch(() => {
                courseSpecializations = [];
                document.getElementById('specializationScopeRow').style.display = 'none';
                updateModulesTableHeader();
                fetchIntakesForSemesterEdit();
            });
    });

    intakeSelect.addEventListener('change', function() {
        resetAndDisable(semesterSelect, 'Select Semester');
        resetAndDisable(moduleSelect, 'Select a module...');
        addedModules = [];
        allModules = [];
        modulesTableBody.innerHTML = '';
        if (!courseSelect.value || !intakeSelect.value) return;
        setSelectOptions(semesterSelect, '<option value="" selected disabled>Loading semesters...</option>', true);
        fetch(`/semester-registration/get-all-semesters-for-course?course_id=${encodeURIComponent(courseSelect.value)}&intake_id=${encodeURIComponent(intakeSelect.value)}`)
            .then(response => response.json())
            .then(data => {
                const keepCurrent = String(intakeSelect.value) === originalIntakeId && String(courseSelect.value) === originalCourseId
                    ? originalSemesterNumber
                    : '';
                populateSemesterOptions(data.semesters || [], keepCurrent);
            })
            .catch(() => {
                resetAndDisable(semesterSelect, 'Failed to load semesters');
            });
    });

    semesterSelect.addEventListener('change', function() {
        resetAndDisable(moduleSelect, 'Select a module...');
        if (isOriginalContext()) {
            loadExistingModules();
        } else {
            addedModules = [];
            modulesTableBody.innerHTML = '';
        }
        loadAvailableModules();
    });

    moduleTypeSelect.addEventListener('change', function() {
        if (allModules.length > 0) {
            filterAndPopulateModules();
        }
    });

    startDateInput.addEventListener('change', function() {
        if (startDateInput.value) {
            endDateInput.min = startDateInput.value;
            if (endDateInput.value && endDateInput.value < startDateInput.value) {
                endDateInput.value = startDateInput.value;
            }
        }
    });
    if (startDateInput.value) {
        endDateInput.min = startDateInput.value;
    }

    addModuleBtn.addEventListener('click', function() {
        const moduleId = moduleSelect.value;
        const moduleOption = moduleSelect.options[moduleSelect.selectedIndex];
        const moduleName = moduleOption ? moduleOption.text : '';
        const moduleType = moduleTypeSelect.value;
        const moduleCredits = moduleOption ? moduleOption.getAttribute('data-credits') : '';
        const semester = semesterSelect.value;
        const specializations = getSelectedSpecializationsForModule();
        if (specializations === undefined) {
            return;
        }
        if (!moduleId || !moduleName || !semester) {
            window.showToast('Please select semester, module, and type.', 'danger');
            return;
        }
        if (addedModules.some(m => String(m.moduleId) === String(moduleId) && String(m.semester) === String(semester))) {
            window.showToast('This module is already added. Remove it first if you need to change its specialization scope.', 'warning');
            return;
        }
        const moduleData = {
            moduleId: String(moduleId),
            moduleName,
            moduleType,
            moduleCredits,
            semester: String(semester),
            specializations
        };
        addedModules.push(moduleData);
        appendModuleRow(moduleData, semesterSelect.options[semesterSelect.selectedIndex].text);
    });

    modulesTableBody.addEventListener('click', function(e) {
        const button = e.target.closest('.remove-module');
        if (!button) return;
        const row = button.closest('tr');
        addedModules = addedModules.filter(m => !(String(m.moduleId) === String(row.dataset.moduleId) && String(m.semester) === String(row.dataset.semester)));
        row.remove();
    });

    const semesterForm = document.getElementById('semesterEditForm');
    semesterForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const requiredFields = ['location', 'course_id', 'intake_id', 'semester', 'start_date', 'end_date'];
        const missing = requiredFields.some(field => {
            const element = document.getElementById(field);
            return !element || !element.value;
        });
        if (missing) {
            window.showToast('Please fill in all required fields.', 'danger');
            return;
        }
        if (endDateInput.value < startDateInput.value) {
            window.showToast('End date must be on or after the start date.', 'danger');
            return;
        }

        const modules = [];
        document.querySelectorAll('#modules_table tbody tr').forEach(row => {
            const moduleId = row.dataset.moduleId;
            if (!moduleId) return;
            let specializations = null;
            if (row.dataset.specializations) {
                try {
                    specializations = JSON.parse(row.dataset.specializations);
                } catch (error) {
                    specializations = null;
                }
            }
            modules.push({ module_id: moduleId, specializations });
        });
        if (modules.length === 0) {
            window.showToast('Please add at least one module to the semester.', 'danger');
            return;
        }

        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');
        function resetSubmitState() {
            submitBtn.disabled = false;
            submitText.textContent = 'Update Semester';
            submitSpinner.style.display = 'none';
        }
        submitBtn.disabled = true;
        submitText.textContent = 'Updating Semester...';
        submitSpinner.style.display = 'inline-block';

        fetch(semesterForm.action, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                location: locationSelect.value,
                course_id: courseSelect.value,
                intake_id: intakeSelect.value,
                semester: semesterSelect.value,
                start_date: startDateInput.value,
                end_date: endDateInput.value,
                modules
            })
        })
        .then(async response => {
            let data = {};
            try {
                data = await response.json();
            } catch (err) {
                throw new Error('Server returned an invalid response.');
            }
            if (!response.ok) {
                const parts = [data.message || 'An error occurred while updating the semester.'];
                if (data.errors) {
                    Object.values(data.errors).flat().forEach(msg => parts.push(msg));
                }
                throw new Error(parts.filter(Boolean).join(' '));
            }
            return data;
        })
        .then(data => {
            resetSubmitState();
            if (data.success) {
                window.showToast(data.message || 'Semester updated successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route("semesters.index") }}';
                }, 1500);
            } else {
                window.showToast(data.message || 'Failed to update semester.', 'danger');
            }
        })
        .catch(error => {
            resetSubmitState();
            window.showToast(error.message || 'An unexpected error occurred.', 'danger');
        });
    });

    document.getElementById('duplicateCancelBtn')?.addEventListener('click', function () {
        const modalEl = document.getElementById('duplicateModal');
        const modal = window.bootstrap?.Modal.getOrCreateInstance(modalEl);
        if (modal) {
            modal.hide();
        }
    });

    document.getElementById('duplicateSemesterBtn').addEventListener('click', function() {
        const newName = document.getElementById('newSemesterName').value.trim();
        const newStartDate = document.getElementById('newStartDate').value;
        const newEndDate = document.getElementById('newEndDate').value;
        const duplicateBtn = this;
        if (!newName || !newStartDate || !newEndDate) {
            window.showToast('Please fill in all required fields.', 'warning');
            return;
        }
        if (newEndDate < newStartDate) {
            window.showToast('End date must be on or after the start date.', 'warning');
            return;
        }
        duplicateBtn.disabled = true;
        fetch('{{ route("semesters.duplicate", $semester) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                new_name: newName,
                start_date: newStartDate,
                end_date: newEndDate
            })
        })
        .then(async response => {
            let data = {};
            try {
                data = await response.json();
            } catch (err) {
                throw new Error('Server returned an invalid response.');
            }
            if (!response.ok) {
                throw new Error(data.message || 'An error occurred while duplicating the semester.');
            }
            return data;
        })
        .then(data => {
            duplicateBtn.disabled = false;
            if (data.success) {
                window.showToast(data.message, 'success');
                const modal = bootstrap.Modal.getInstance(document.getElementById('duplicateModal'));
                if (modal) modal.hide();
                setTimeout(() => {
                    window.location.href = '{{ route("semesters.index") }}';
                }, 1500);
            } else {
                window.showToast(data.message || 'Failed to duplicate semester.', 'danger');
            }
        })
        .catch(error => {
            duplicateBtn.disabled = false;
            window.showToast(error.message || 'An error occurred while duplicating the semester.', 'danger');
        });
    });
});

window.showToast = function(message, type = 'success') {
    const toastEl = document.getElementById('mainToast');
    const toastBody = document.getElementById('mainToastBody');
    toastBody.textContent = message;
    toastEl.className = 'toast align-items-center border-0 text-bg-' + (type === 'success' ? 'success' : (type === 'danger' ? 'danger' : (type === 'warning' ? 'warning' : 'primary')));
    const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
    toast.show();
};
</script>
@endsection
