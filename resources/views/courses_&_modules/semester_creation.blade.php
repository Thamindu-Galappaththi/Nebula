@extends('inc.app')

@section('title', 'NEBULA | Semester Creation')

@section('content')
<style nonce="{{ $cspNonce }}">
    .semester-create-page .nebula-select {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }
    .semester-create-page .row > [class*="col-"] {
        min-width: 0;
    }
    .semester-create-header,
    .semester-create-actions {
        gap: 0.75rem;
    }
    .semester-create-page select:disabled,
    .semester-create-page .nebula-select.is-disabled .nebula-select-toggle {
        background-color: #f5f5f5 !important;
        border-color: #ddd !important;
        color: #aaa !important;
        box-shadow: none;
    }
    .semester-module-picker {
        display: grid;
        grid-template-columns: minmax(0, 14rem) minmax(0, 1fr) auto;
        gap: 0.5rem;
        align-items: end;
    }
    .semester-module-picker .nebula-select,
    .semester-module-type,
    .semester-module-choice {
        width: 100%;
        max-width: 100%;
        min-width: 0;
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
        margin-bottom: 0;
    }
    .remove-module {
        flex: 0 0 auto !important;
        width: auto;
        min-width: 2rem;
        padding: 0.15rem 0.45rem;
        font-size: 0.75rem;
        line-height: 1;
        white-space: nowrap;
    }
    .semester-toast-wrap {
        z-index: 9999;
    }
    @media (max-width: 991.98px) {
        .semester-create-header {
            flex-direction: column;
            align-items: stretch !important;
        }
        .semester-create-actions,
        .semester-create-actions .btn {
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
        #modules_table td[data-label="Action"]::before {
            content: none;
        }
        .semester-toast-wrap {
            top: auto !important;
            bottom: 0;
            left: 0;
            right: 0;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 semester-create-page">
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-4 semester-create-header">
                <h2 class="mb-0">Create Semester</h2>
                <div class="semester-create-actions">
                    <a href="{{ route('semesters.index') }}" class="btn btn-secondary">
                        <i class="ti ti-arrow-left"></i> Back to Semesters
                    </a>
                </div>
            </div>
            <hr>
            <form action="{{ route('semesters.store') }}" method="POST" id="semesterCreateForm">
                @csrf
                <div class="row mb-3 g-2 g-md-3 align-items-md-center">
                    <label for="location" class="col-12 col-md-3 col-lg-2 col-form-label">Location <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9 col-lg-10">
                        <select name="location" id="location" class="form-select" required>
                            <option selected disabled value="">Select a Location</option>
                            <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                            <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                            <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3 g-2 g-md-3 align-items-md-center">
                    <label for="course_id" class="col-12 col-md-3 col-lg-2 col-form-label">Course <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9 col-lg-10">
                        <select name="course_id" id="course_id" class="form-select" required disabled>
                            <option selected disabled value="">Select Course</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3 g-2 g-md-3 align-items-md-center">
                    <label for="intake_id" class="col-12 col-md-3 col-lg-2 col-form-label">Intake <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9 col-lg-10">
                        <select name="intake_id" id="intake_id" class="form-select" required disabled>
                            <option selected disabled value="">Select Intake</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3 g-2 g-md-3 align-items-md-center">
                    <label for="semester" class="col-12 col-md-3 col-lg-2 col-form-label">Semester <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9 col-lg-10">
                        <select name="semester" id="semester" class="form-select" required disabled>
                            <option selected disabled value="">Select Semester</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3 g-2 g-md-3 align-items-md-center">
                    <label for="start_date" class="col-12 col-md-3 col-lg-2 col-form-label">Start Date <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9 col-lg-10">
                        <input type="date" name="start_date" id="start_date" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3 g-2 g-md-3 align-items-md-center">
                    <label for="end_date" class="col-12 col-md-3 col-lg-2 col-form-label">End Date <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9 col-lg-10">
                        <input type="date" name="end_date" id="end_date" class="form-control" required>
                    </div>
                </div>
                <div class="row mb-3 g-2 g-md-3">
                    <div class="col-12 col-md-3 col-lg-2 col-form-label">Status</div>
                    <div class="col-12 col-md-9 col-lg-10">
                        <p class="form-text mb-0 pt-md-2">Status is set automatically from the start and end dates (upcoming, active, or completed).</p>
                    </div>
                </div>
                <div class="row mb-3 g-2 g-md-3" id="specializationScopeRow" hidden>
                    <label class="col-12 col-md-3 col-lg-2 col-form-label">Module Applicability</label>
                    <div class="col-12 col-md-9 col-lg-10">
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
                <div class="row mb-3 g-2 g-md-3 align-items-md-start" id="modulesFieldRow">
                    <label class="col-12 col-md-3 col-lg-2 col-form-label" for="module_select">Modules <span class="text-danger">*</span></label>
                    <div class="col-12 col-md-9 col-lg-10">
                        <div class="semester-module-picker">
                            <div class="semester-module-type">
                                <label class="form-label small text-muted" for="module_type">Type</label>
                                <select id="module_type" class="form-select">
                                    <option value="">All types</option>
                                    <option value="Core">Core</option>
                                    <option value="Elective">Elective</option>
                                    <option value="Special Unit Compulsory (S/U)">Special Unit Compulsory (S/U)</option>
                                </select>
                            </div>
                            <div class="semester-module-choice">
                                <label class="form-label small text-muted" for="module_select">Module</label>
                                <select id="module_select" class="form-select" disabled>
                                    <option selected disabled value="">Select a module...</option>
                                </select>
                            </div>
                            <div class="semester-module-add">
                                <label class="form-label small text-muted d-none d-lg-block">&nbsp;</label>
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
                <div class="d-grid d-md-flex justify-content-md-end">
                    <button type="submit" class="btn btn-success" id="submitBtn">
                        <span id="submitText">Create Semester</span>
                        <span id="submitSpinner" class="spinner-border spinner-border-sm ms-2" style="display: none;"></span>
                    </button>
                </div>
            </form>
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
let courseSemesterFormat = 'numerical';

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
    let addedModules = [];
    let allModules = [];

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
        const wrap = select.closest('.nebula-select');
        const toggle = wrap ? wrap.querySelector('.nebula-select-toggle') : null;
        if (toggle) {
            const selected = select.options[select.selectedIndex];
            toggle.textContent = selected ? selected.text : '';
            toggle.title = toggle.textContent;
            toggle.disabled = select.disabled;
            wrap.classList.toggle('is-disabled', select.disabled);
        }
    }

    function resetAndDisable(select, placeholder) {
        setSelectOptions(select, `<option value="" selected disabled>${escapeHtml(placeholder)}</option>`, true);
    }

    function enableSelect(select) {
        select.disabled = false;
    }

    function clearAddedModules() {
        addedModules = [];
        allModules = [];
        if (modulesTableBody) {
            modulesTableBody.innerHTML = '';
        }
    }

    function fetchIntakesForSemesterCreation() {
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

    locationSelect.addEventListener('change', function() {
        resetAndDisable(courseSelect, 'Select Course');
        resetAndDisable(intakeSelect, 'Select Intake');
        resetAndDisable(semesterSelect, 'Select Semester');
        resetAndDisable(moduleSelect, 'Select a module...');
        clearAddedModules();
        if (!locationSelect.value) {
            return;
        }
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

    function getSelectedSpecializationsForModule() {
        if (!courseSpecializations.length) {
            return null;
        }

        const scopeAll = document.getElementById('spec_scope_all')?.checked;
        if (scopeAll) {
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
            document.getElementById('specializationScopeRow').hidden = false;
            document.getElementById('spec_scope_all').checked = true;
            document.getElementById('specializationCheckboxes').style.display = 'none';
        } else {
            courseSpecializations = [];
            document.getElementById('specializationScopeRow').hidden = true;
        }
        updateModulesTableHeader();
    }

    courseSelect.addEventListener('change', function() {
        resetAndDisable(intakeSelect, 'Select Intake');
        resetAndDisable(semesterSelect, 'Select Semester');
        resetAndDisable(moduleSelect, 'Select a module...');
        clearAddedModules();
        if (!courseSelect.value) {
            courseSpecializations = [];
            document.getElementById('specializationScopeRow').hidden = true;
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
                    document.getElementById('specializationScopeRow').hidden = true;
                    updateModulesTableHeader();
                }
                fetchIntakesForSemesterCreation();
            })
            .catch(() => {
                courseSpecializations = [];
                document.getElementById('specializationScopeRow').hidden = true;
                updateModulesTableHeader();
                fetchIntakesForSemesterCreation();
            });
    });

    function semesterDisplayName(semesterNumber) {
        const number = parseInt(semesterNumber, 10);
        if (courseSemesterFormat === 'alphabetical' && number >= 1 && number <= 26) {
            return 'Semester ' + String.fromCharCode(64 + number);
        }
        return 'Semester ' + String(semesterNumber);
    }

    intakeSelect.addEventListener('change', function() {
        resetAndDisable(semesterSelect, 'Select Semester');
        resetAndDisable(moduleSelect, 'Select a module...');
        clearAddedModules();
        if (!courseSelect.value || !intakeSelect.value) {
            return;
        }
        setSelectOptions(semesterSelect, '<option value="" selected disabled>Loading semesters...</option>', true);
        fetch(`/semester-registration/get-all-semesters-for-course?course_id=${encodeURIComponent(courseSelect.value)}&intake_id=${encodeURIComponent(intakeSelect.value)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.semesters && data.semesters.length > 0) {
                    let options = '<option value="" selected disabled>Select Semester</option>';
                    data.semesters.forEach(sem => {
                        const semesterNumber = sem.semester_id;
                        options += `<option value="${escapeHtml(String(semesterNumber))}">${escapeHtml(semesterDisplayName(semesterNumber))}</option>`;
                    });
                    setSelectOptions(semesterSelect, options, false);
                } else {
                    resetAndDisable(semesterSelect, 'No semesters available');
                }
            })
            .catch(() => {
                resetAndDisable(semesterSelect, 'Failed to load semesters');
            });
    });

    semesterSelect.addEventListener('change', function() {
        resetAndDisable(moduleSelect, 'Select a module...');
        clearAddedModules();
        if (!semesterSelect.value || !intakeSelect.value || !courseSelect.value || !locationSelect.value) {
            return;
        }
        setSelectOptions(moduleSelect, '<option value="" selected disabled>Loading modules...</option>', true);
        fetch('/semester/get-filtered-modules', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json'},
            body: JSON.stringify({
                location: locationSelect.value,
                course_id: courseSelect.value,
                intake_id: intakeSelect.value,
                semester: semesterSelect.value,
                creating: true
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
    });

    function formatModuleType(type) {
        const labels = {
            core: 'Core',
            elective: 'Elective',
            special_unit_compulsory: 'Special Unit Compulsory (S/U)'
        };
        const key = String(type || '').toLowerCase();
        return labels[key] || type || '—';
    }

    function filterAndPopulateModules() {
        const typeMap = {
            'Core': 'core',
            'Elective': 'elective',
            'Special Unit Compulsory (S/U)': 'special_unit_compulsory'
        };
        const selectedType = typeMap[moduleTypeSelect.value] || '';
        let options = '<option value="" selected disabled>Select a module...</option>';
        const filtered = selectedType
            ? allModules.filter(m => String(m.module_type || '').toLowerCase() === selectedType)
            : allModules;
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

    addModuleBtn.addEventListener('click', function() {
        const moduleId = moduleSelect.value;
        const moduleOption = moduleSelect.options[moduleSelect.selectedIndex];
        const moduleName = moduleOption ? moduleOption.text : '';
        const moduleType = moduleOption ? moduleOption.getAttribute('data-type') : '';
        const moduleCredits = moduleOption ? moduleOption.getAttribute('data-credits') : '';
        const semester = semesterSelect.value;
        const specializations = getSelectedSpecializationsForModule();
        if (specializations === undefined) {
            return;
        }

        if (!moduleId || !moduleName || !semester) {
            window.showToast('Please select a semester and a module.', 'danger');
            return;
        }

        if (addedModules.some(m => m.moduleId === moduleId && m.semester === semester)) {
            window.showToast('This module is already added. Remove it first if you need to change its specialization scope.', 'warning');
            return;
        }

        addedModules.push({
            moduleId,
            moduleName,
            moduleType,
            moduleCredits,
            semester,
            specializations
        });

        const row = document.createElement('tr');
        let rowHtml = `<td data-label="Semester">${escapeHtml(semesterSelect.options[semesterSelect.selectedIndex].text)}</td>`;
        if (courseSpecializations.length > 0) {
            rowHtml += `<td data-label="Specialization">${escapeHtml(formatSpecializationsLabel(specializations))}</td>`;
        }
        rowHtml += `
            <td data-label="Module Name">${escapeHtml(moduleName)}</td>
            <td data-label="Type">${escapeHtml(formatModuleType(moduleType))}</td>
            <td data-label="Credits">${escapeHtml(moduleCredits)}</td>
            <td data-label="Action"><button type="button" class="btn btn-outline-danger btn-sm remove-module" title="Remove" aria-label="Remove"><i class="ti ti-x"></i></button></td>
        `;
        row.innerHTML = rowHtml;
        row.dataset.moduleId = moduleId;
        row.dataset.semester = semester;
        row.dataset.specializations = specializations ? JSON.stringify(specializations) : '';

        modulesTableBody.appendChild(row);
    });

    modulesTableBody.addEventListener('click', function(e) {
        const button = e.target.closest('.remove-module');
        if (!button) return;
        const row = button.closest('tr');
        const moduleId = row.dataset.moduleId;
        const semester = row.dataset.semester;
        addedModules = addedModules.filter(m => !(m.moduleId === moduleId && m.semester === semester));
        row.remove();
    });

    const semesterForm = document.getElementById('semesterCreateForm');
    semesterForm.addEventListener('submit', function(e) {
        e.preventDefault();

        const requiredFields = [
            'location', 'course_id', 'intake_id', 'semester',
            'start_date', 'end_date'
        ];
        const missingFields = requiredFields.filter(field => {
            const element = document.getElementById(field);
            return !element || !element.value;
        });
        if (missingFields.length > 0) {
            window.showToast('Please fill in all required fields.', 'danger');
            return;
        }

        if (endDateInput.value < startDateInput.value) {
            window.showToast('End date must be on or after the start date.', 'danger');
            return;
        }

        const formData = {
            location: locationSelect.value,
            course_id: courseSelect.value,
            intake_id: intakeSelect.value,
            semester: semesterSelect.value,
            start_date: startDateInput.value,
            end_date: endDateInput.value,
            _token: '{{ csrf_token() }}'
        };

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
            modules.push({
                module_id: moduleId,
                specializations: specializations
            });
        });
        formData.modules = modules;

        if (modules.length === 0) {
            window.showToast('Please add at least one module to the semester.', 'danger');
            return;
        }

        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');

        function resetSubmitState() {
            submitBtn.disabled = false;
            submitText.textContent = 'Create Semester';
            submitSpinner.style.display = 'none';
        }

        submitBtn.disabled = true;
        submitText.textContent = 'Creating Semester...';
        submitSpinner.style.display = 'inline-block';

        fetch(semesterForm.action, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify(formData)
        })
        .then(async response => {
            let data = {};
            try {
                data = await response.json();
            } catch (err) {
                throw new Error('Server returned an invalid response.');
            }
            if (!response.ok) {
                const parts = [data.message || 'An error occurred while creating the semester.'];
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
                window.showToast(data.message || 'Semester created successfully!', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route("semesters.index") }}';
                }, 1500);
            } else {
                window.showToast(data.message || 'Failed to create semester.', 'danger');
            }
        })
        .catch(error => {
            resetSubmitState();
            window.showToast(error.message || 'An unexpected error occurred.', 'danger');
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
