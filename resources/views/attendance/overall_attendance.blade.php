@extends('inc.app')

@section('title', 'NEBULA | Overall Attendance')

@section('content')
<style nonce="{{ $cspNonce }}">
    .overall-attendance-page,
    .overall-attendance-page .card,
    .overall-attendance-page .card-body {
        min-width: 0;
        max-width: 100%;
        overflow: visible;
        height: auto;
    }
    .overall-attendance-page .tab-content > .tab-pane:not(.active) {
        display: none !important;
        height: 0;
        overflow: hidden;
    }
    body:has(.overall-attendance-page) .body-wrapper > .container-fluid {
        overflow: visible;
    }
    .overall-attendance-page [class*="col-"] {
        min-width: 0;
    }
    .overall-attendance-page .form-select,
    .overall-attendance-page .form-control,
    .overall-attendance-page .nebula-select,
    .overall-attendance-page .nebula-select-toggle {
        width: 100%;
        max-width: 100%;
    }
    .attendance-tabs {
        flex-wrap: wrap;
        overflow: hidden;
        row-gap: 0.25rem;
    }
    .overall-export-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: flex-end;
    }
    .overall-summary-table th,
    .overall-summary-table td {
        word-break: break-word;
        vertical-align: middle;
    }
    .attendance-toast {
        max-width: min(360px, calc(100vw - 1.5rem));
    }
    #spinner-overlay { position: fixed; inset: 0; background-color: rgba(0, 0, 0, 0.5); justify-content: center; align-items: center; z-index: 9999; }
    .lds-ring { display: inline-block; position: relative; width: 80px; height: 80px; }
    .lds-ring div { box-sizing: border-box; display: block; position: absolute; width: 64px; height: 64px; margin: 8px; border: 8px solid #fff; border-radius: 50%; animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite; border-color: #fff transparent transparent transparent; }
    .lds-ring div:nth-child(1) { animation-delay: -0.45s; }
    .lds-ring div:nth-child(2) { animation-delay: -0.3s; }
    .lds-ring div:nth-child(3) { animation-delay: -0.15s; }
    @keyframes lds-ring { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .attendance-matrix-wrap {
        max-height: 65vh;
        overflow: auto;
    }
    .attendance-matrix-table {
        min-width: max-content;
    }
    .attendance-matrix-table thead th {
        position: sticky;
        top: 0;
        z-index: 3;
        background: #f8f9fa;
        font-size: 0.78rem;
        font-weight: 600;
        line-height: 1.2;
        min-width: 120px;
        max-width: 160px;
        padding: 0.45rem 0.4rem;
        vertical-align: bottom;
        white-space: normal;
    }
    .attendance-matrix-table th:first-child,
    .attendance-matrix-table td:first-child {
        position: sticky;
        left: 0;
        z-index: 2;
        background: #ffffff;
        white-space: nowrap;
        min-width: 110px;
    }
    .attendance-matrix-table thead th:first-child {
        z-index: 4;
        background: #f8f9fa;
    }
    .attendance-matrix-legend .badge {
        min-width: 1.5rem;
    }
    .matrix-student-header {
        display: flex;
        flex-direction: column;
        gap: 0.1rem;
    }
    .matrix-student-name {
        color: #212529;
        word-break: break-word;
    }
    .matrix-student-reg {
        color: #6c757d;
        font-size: 0.72rem;
    }
    @media (max-width: 767.98px) {
        .overall-attendance-page h2 {
            font-size: 1.25rem;
        }
        .overall-attendance-page .card-body {
            padding: 1rem 0.75rem;
        }
        .overall-attendance-page .form-control,
        .overall-attendance-page .form-select,
        .overall-attendance-page .nebula-select-toggle {
            font-size: 16px;
        }
        .overall-attendance-page .col-form-label {
            text-align: left !important;
            padding-bottom: 0.2rem;
        }
        .overall-export-actions,
        .overall-export-actions .btn {
            width: 100%;
        }
        .overall-summary-table thead {
            display: none;
        }
        .overall-summary-table,
        .overall-summary-table tbody,
        .overall-summary-table tr,
        .overall-summary-table td {
            display: block;
            width: 100%;
        }
        .overall-summary-table tbody tr[data-student-row] {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 8px 12px 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }
        .overall-summary-table td {
            border: 0;
            padding: 0.45rem 0;
        }
        .overall-summary-table td[data-label]::before {
            content: attr(data-label);
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .attendance-matrix-legend {
            justify-content: flex-start !important;
            flex-wrap: wrap;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 overall-attendance-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Overall Attendance</h2>
            <hr>

            <div id="spinner-overlay" style="display:none;"><div class="lds-ring"><div></div><div></div><div></div><div></div></div></div>
            <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3 attendance-toast" aria-live="polite" aria-atomic="true" style="z-index: 1090;"></div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4 attendance-tabs" id="overallAttendanceTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="degree-tab" data-bs-toggle="tab" data-bs-target="#degree-panel" type="button" role="tab">Degree & Diploma</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="certificate-tab" data-bs-toggle="tab" data-bs-target="#certificate-panel" type="button" role="tab">Certificate</button>
                </li>
            </ul>

            <div class="tab-content" id="overallAttendanceTabContent">
                <!-- Degree & Diploma Tab -->
                <div class="tab-pane fade show active" id="degree-panel" role="tabpanel">
                    <div id="attendance-filters-degree" class="mb-4">
                        <div class="mb-3 row mx-0">
                            <label for="degree_location" class="col-md-2 col-form-label fw-bold">Location <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <select class="form-select degree-filter" id="degree_location" name="location" required>
                                    <option value="" selected disabled>Select a Location</option>
                                    <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                                    <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                                    <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 row mx-0">
                            <label for="degree_course_type" class="col-md-2 col-form-label fw-bold">Course Type <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <select class="form-select degree-filter" id="degree_course_type" name="course_type" required>
                                    <option value="" selected disabled>Select a Course Type</option>
                                    <option value="degree">Degree Program</option>
                                    <option value="diploma">Diploma Program</option>
                                </select>
                            </div>
                        </div>
                        <div id="degree-fields-container">
                            <div class="mb-3 row mx-0">
                                <label for="degree_course" class="col-md-2 col-form-label fw-bold">Course <span class="text-danger">*</span></label>
                                <div class="col-md-10">
                                    <select class="form-select degree-filter" id="degree_course" name="course_id" required>
                                        <option selected disabled value="">Select a Course</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row mx-0">
                                <label for="degree_intake" class="col-md-2 col-form-label fw-bold">Intake <span class="text-danger">*</span></label>
                                <div class="col-md-10">
                                    <select class="form-select degree-filter" id="degree_intake" name="intake_id" required>
                                        <option selected disabled value="">Select an Intake</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row mx-0" id="degree_specialization_row" style="display:none;">
                                <label for="degree_specialization" class="col-md-2 col-form-label fw-bold">Specialization <span class="text-danger">*</span></label>
                                <div class="col-md-10">
                                    <select class="form-select degree-filter" id="degree_specialization" name="specialization" disabled>
                                        <option selected disabled value="">Select a Specialization</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row mx-0">
                                <label for="degree_semester" class="col-md-2 col-form-label fw-bold">Semester <span class="text-danger">*</span></label>
                                <div class="col-md-10">
                                    <select class="form-select degree-filter" id="degree_semester" name="semester" required>
                                        <option selected disabled value="">Select a Semester</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row mx-0">
                                <label for="degree_module" class="col-md-2 col-form-label fw-bold">Module <span class="text-danger">*</span></label>
                                <div class="col-md-10">
                                    <select class="form-select degree-filter" id="degree_module" name="module_id" required>
                                        <option selected disabled value="">Select a Module</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Degree Attendance Section -->
                    <div class="mt-4" id="degreeOverallAttendanceSection" style="display:none;">
                        <hr class="my-4">
                            <div class="mb-3 overall-export-actions">
                                <button id="degreeExportPdfBtn" class="btn btn-outline-primary" type="button">
                                    <i class="ti ti-download"></i> Export to PDF
                                </button>
                                <button id="degreeExportExcelBtn" class="btn btn-outline-success" type="button">
                                    <i class="ti ti-file-spreadsheet"></i> Export to Excel
                                </button>
                            </div>
                        <ul class="nav nav-tabs mb-3" id="degreeResultTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="degree-summary-tab" data-bs-toggle="tab" data-bs-target="#degree-summary-panel" type="button" role="tab">Summary</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="degree-matrix-tab" data-bs-toggle="tab" data-bs-target="#degree-matrix-panel" type="button" role="tab">Date x Students</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="degreeResultTabContent">
                            <div class="tab-pane fade show active" id="degree-summary-panel" role="tabpanel">
                                <h4 class="text-center mb-3">Attendance Summary</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered overall-summary-table" id="degreeAttendanceTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Registration Number</th>
                                                <th>Student Name</th>
                                                <th>Total Sessions</th>
                                                <th>Attended Sessions</th>
                                                <th>Attendance (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="degreeOverallAttendanceTableBody">
                                            <!-- Rows will be added here dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="degree-matrix-panel" role="tabpanel">
                                <h4 class="text-center mb-3">Attendance Matrix (Dates x Students)</h4>
                                <div class="attendance-matrix-legend mb-2 d-flex gap-3 justify-content-end small">
                                    <span><span class="badge bg-success">P</span> Present</span>
                                    <span><span class="badge bg-danger">A</span> Absent</span>
                                    <span><span class="badge bg-secondary">-</span> No Record</span>
                                </div>
                                <div class="table-responsive attendance-matrix-wrap">
                                    <table class="table table-bordered table-sm attendance-matrix-table" id="degreeAttendanceMatrixTable">
                                        <thead class="table-light" id="degreeMatrixHead">
                                            <!-- Dynamic head -->
                                        </thead>
                                        <tbody id="degreeMatrixBody">
                                            <!-- Dynamic body -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Certificate Tab -->
                <div class="tab-pane fade" id="certificate-panel" role="tabpanel">
                    <div id="attendance-filters-cert" class="mb-4">
                        <div class="mb-3 row mx-0">
                            <label for="cert_location" class="col-md-2 col-form-label fw-bold">Location <span class="text-danger">*</span></label>
                            <div class="col-md-10">
                                <select class="form-select cert-filter" id="cert_location" name="location" required>
                                    <option value="" selected disabled>Select a Location</option>
                                    <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                                    <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                                    <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                                </select>
                            </div>
                        </div>
                        <div id="cert-fields-container">
                            <div class="mb-3 row mx-0">
                                <label for="cert_course" class="col-md-2 col-form-label fw-bold">Course <span class="text-danger">*</span></label>
                                <div class="col-md-10">
                                    <select class="form-select cert-filter" id="cert_course" name="course_id" required>
                                        <option selected disabled value="">Select a Course</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row mx-0">
                                <label for="cert_intake" class="col-md-2 col-form-label fw-bold">Intake <span class="text-danger">*</span></label>
                                <div class="col-md-10">
                                    <select class="form-select cert-filter" id="cert_intake" name="intake_id" required>
                                        <option selected disabled value="">Select an Intake</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Certificate Attendance Section -->
                    <div class="mt-4" id="certOverallAttendanceSection" style="display:none;">
                        <hr class="my-4">
                            <div class="mb-3 overall-export-actions">
                                <button id="certExportPdfBtn" class="btn btn-outline-primary" type="button">
                                    <i class="ti ti-download"></i> Export to PDF
                                </button>
                                <button id="certExportExcelBtn" class="btn btn-outline-success" type="button">
                                    <i class="ti ti-file-spreadsheet"></i> Export to Excel
                                </button>
                            </div>
                        <ul class="nav nav-tabs mb-3" id="certResultTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="cert-summary-tab" data-bs-toggle="tab" data-bs-target="#cert-summary-panel" type="button" role="tab">Summary</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="cert-matrix-tab" data-bs-toggle="tab" data-bs-target="#cert-matrix-panel" type="button" role="tab">Date x Students</button>
                            </li>
                        </ul>

                        <div class="tab-content" id="certResultTabContent">
                            <div class="tab-pane fade show active" id="cert-summary-panel" role="tabpanel">
                                <h4 class="text-center mb-3">Attendance Summary</h4>
                                <div class="table-responsive">
                                    <table class="table table-bordered overall-summary-table" id="certAttendanceTable">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Registration Number</th>
                                                <th>Student Name</th>
                                                <th>Total Sessions</th>
                                                <th>Attended Sessions</th>
                                                <th>Attendance (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody id="certOverallAttendanceTableBody">
                                            <!-- Rows will be added here dynamically -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="cert-matrix-panel" role="tabpanel">
                                <h4 class="text-center mb-3">Attendance Matrix (Dates x Students)</h4>
                                <div class="attendance-matrix-legend mb-2 d-flex gap-3 justify-content-end small">
                                    <span><span class="badge bg-success">P</span> Present</span>
                                    <span><span class="badge bg-danger">A</span> Absent</span>
                                    <span><span class="badge bg-secondary">-</span> No Record</span>
                                </div>
                                <div class="table-responsive attendance-matrix-wrap">
                                    <table class="table table-bordered table-sm attendance-matrix-table" id="certAttendanceMatrixTable">
                                        <thead class="table-light" id="certMatrixHead">
                                            <!-- Dynamic head -->
                                        </thead>
                                        <tbody id="certMatrixBody">
                                            <!-- Dynamic body -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" integrity="sha384-JcnsjUPPylna1s1fvi1u12X5qjY5OL56iySh75FdtrwhO/SWXgMjoVqcKyIIWOLk" crossorigin="anonymous"></script>
<script nonce="{{ $cspNonce }}" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.7.0/jspdf.plugin.autotable.min.js" integrity="sha384-VA0FoBFnoj52hvJgGJB/86X6Ymgc+m/+C9RHXKKzH0qDAaY6MHnY5C97eYjQIqRj" crossorigin="anonymous"></script>
<script nonce="{{ $cspNonce }}">
/* HTML Escape Helper Function */
function escapeHtml(text) {
  const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
  return String(text).replace(/[&<>"']/g, m => map[m]);
}

function showSpinner(show) {
        const overlay = document.getElementById('spinner-overlay');
        if (overlay) {
                overlay.style.display = show ? 'flex' : 'none';
        }
}

function showToast(title, message, type) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    container.innerHTML = '';
    const normalized = (type || '').includes('success') ? 'success' : ((type || '').includes('warning') ? 'warning' : ((type || '').includes('info') ? 'info' : 'error'));
    const headerClass = normalized === 'success' ? 'bg-success text-white'
        : (normalized === 'warning' ? 'bg-warning text-dark'
        : (normalized === 'info' ? 'bg-info text-white' : 'bg-danger text-white'));
    const toast = document.createElement('div');
    toast.className = 'toast show';
    toast.setAttribute('role', 'alert');
    const header = document.createElement('div');
    header.className = 'toast-header ' + headerClass;
    const strong = document.createElement('strong');
    strong.className = 'me-auto';
    strong.textContent = title || (normalized === 'success' ? 'Success' : (normalized === 'warning' ? 'Warning' : (normalized === 'info' ? 'Info' : 'Error')));
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close' + (normalized === 'warning' ? '' : ' btn-close-white');
    closeBtn.setAttribute('data-bs-dismiss', 'toast');
    header.appendChild(strong);
    header.appendChild(closeBtn);
    const body = document.createElement('div');
    body.className = 'toast-body';
    body.textContent = message || '';
    toast.appendChild(header);
    toast.appendChild(body);
    container.appendChild(toast);
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 4000 }).show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

document.addEventListener('DOMContentLoaded', function() {
    // Degree Tab Elements
    const degreeLocation = document.getElementById('degree_location');
    const degreeCourseType = document.getElementById('degree_course_type');
    const degreeCourse = document.getElementById('degree_course');
    const degreeIntake = document.getElementById('degree_intake');
    let degreeSpecializationsLoaded = false;
    let degreeSemesterEmpty = false;
    let degreeModuleEmpty = false;
    const degreeSpecialization = document.getElementById('degree_specialization');
    const degreeSpecializationRow = document.getElementById('degree_specialization_row');
    const degreeSemester = document.getElementById('degree_semester');
    const degreeModule = document.getElementById('degree_module');
    const degreeTableBody = document.getElementById('degreeOverallAttendanceTableBody');
    const degreeSection = document.getElementById('degreeOverallAttendanceSection');
    const degreeMatrixHead = document.getElementById('degreeMatrixHead');
    const degreeMatrixBody = document.getElementById('degreeMatrixBody');
    
    // Certificate Tab Elements
    const certLocation = document.getElementById('cert_location');
    const certCourse = document.getElementById('cert_course');
    const certIntake = document.getElementById('cert_intake');
    const certTableBody = document.getElementById('certOverallAttendanceTableBody');
    const certSection = document.getElementById('certOverallAttendanceSection');
    const certMatrixHead = document.getElementById('certMatrixHead');
    const certMatrixBody = document.getElementById('certMatrixBody');
    
    // Tab event listeners
    const degreeTabBtn = document.getElementById('degree-tab');
    const certTabBtn = document.getElementById('certificate-tab');
    
    degreeTabBtn.addEventListener('shown.bs.tab', function() {
        fetchDegreeOverallAttendance();
    });

    certTabBtn.addEventListener('shown.bs.tab', function() {
        fetchCertOverallAttendance();
    });

    function resetAndDisable(select, placeholder) {
        if (!select) return;
        select.innerHTML = '';
        const option = new Option(placeholder, '', true, true);
        option.disabled = true;
        select.add(option);
        select.disabled = true;
    }

    function resetSpecialization() {
        if (!degreeSpecialization || !degreeSpecializationRow) {
            return;
        }

        degreeSpecializationsLoaded = false;
        degreeSpecialization.innerHTML = '';
        const option = new Option('Select a Specialization', '', true, true);
        option.disabled = true;
        degreeSpecialization.add(option);
        degreeSpecialization.disabled = true;
        degreeSpecializationRow.style.display = 'none';
    }

    function hasDegreeSpecializationSelection() {
        return degreeSpecializationsLoaded && (!degreeSpecializationRow || degreeSpecializationRow.style.display === 'none' || !!degreeSpecialization.value);
    }

    function maybeFetchDegreeModules() {
        if (degreeSemester.value && degreeIntake.value && degreeCourse.value && degreeLocation.value && hasDegreeSpecializationSelection()) {
            fetchDegreeModules();
            return;
        }
        resetAndDisable(degreeModule, 'Select a Module');
    }

    function fetchDegreeSpecializations() {
        if (!degreeCourse.value) {
            resetSpecialization();
            return;
        }

        showSpinner(true);
        fetch(`/api/course/${degreeCourse.value}/specializations`)
            .then(response => response.json())
            .then(data => {
                const specializations = data.success && Array.isArray(data.specializations) ? data.specializations.filter(Boolean) : [];

                if (specializations.length > 0) {
                    degreeSpecialization.innerHTML = '';
                    const placeholderOption = new Option('Select a Specialization', '', true, true);
                    placeholderOption.disabled = true;
                    degreeSpecialization.add(placeholderOption);
                    specializations.forEach(spec => {
                        degreeSpecialization.add(new Option(spec, spec));
                    });
                    degreeSpecialization.disabled = false;
                    degreeSpecializationRow.style.display = '';
                    degreeSpecializationsLoaded = true;
                } else {
                    resetSpecialization();
                    degreeSpecializationsLoaded = true;
                }
                maybeFetchDegreeModules();
            })
            .catch(() => {
                resetSpecialization();
                degreeSpecializationsLoaded = true;
                maybeFetchDegreeModules();
            })
            .finally(() => showSpinner(false));
    }

    // Initialize disabled state
    resetAndDisable(degreeCourse, 'Select a Course');
    resetAndDisable(degreeIntake, 'Select an Intake');
    resetAndDisable(degreeSemester, 'Select a Semester');
    resetAndDisable(degreeModule, 'Select a Module');
    resetAndDisable(certCourse, 'Select a Course');
    resetAndDisable(certIntake, 'Select an Intake');

    // DEGREE TAB EVENT LISTENERS
    degreeLocation.addEventListener('change', function() {
        resetAndDisable(degreeCourse, 'Select a Course');
        resetAndDisable(degreeIntake, 'Select an Intake');
        resetSpecialization();
        degreeSemesterEmpty = false;
        degreeModuleEmpty = false;
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        degreeSection.style.display = 'none';
        if (degreeLocation.value && degreeCourseType.value) {
            fetchDegreeCourses(degreeLocation.value, degreeCourseType.value);
        }
    });

    degreeCourseType.addEventListener('change', function() {
        resetAndDisable(degreeCourse, 'Select a Course');
        resetAndDisable(degreeIntake, 'Select an Intake');
        resetSpecialization();
        degreeSemesterEmpty = false;
        degreeModuleEmpty = false;
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        degreeSection.style.display = 'none';
        if (degreeLocation.value && this.value) {
            fetchDegreeCourses(degreeLocation.value, this.value);
        }
    });

    degreeCourse.addEventListener('change', function() {
        resetAndDisable(degreeIntake, 'Select an Intake');
        resetSpecialization();
        degreeSemesterEmpty = false;
        degreeModuleEmpty = false;
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        if (degreeCourse.value && degreeLocation.value) {
            fetchDegreeSpecializations();
            fetchDegreeIntakes(degreeCourse.value, degreeLocation.value);
        }
        fetchDegreeOverallAttendance();
    });

    degreeIntake.addEventListener('change', function() {
        degreeSemesterEmpty = false;
        degreeModuleEmpty = false;
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        if (degreeIntake.value && degreeCourse.value) {
            fetchDegreeSemesters(degreeCourse.value, degreeIntake.value);
        }
    });

    degreeSemester.addEventListener('change', function() {
        degreeModuleEmpty = false;
        resetAndDisable(degreeModule, 'Select a Module');
        maybeFetchDegreeModules();
    });

    degreeModule.addEventListener('change', function() {
        fetchDegreeOverallAttendance();
    });

    degreeSpecialization.addEventListener('change', function() {
        resetAndDisable(degreeModule, 'Select a Module');
        maybeFetchDegreeModules();
        fetchDegreeOverallAttendance();
    });

    // CERTIFICATE TAB EVENT LISTENERS
    certLocation.addEventListener('change', function() {
        resetAndDisable(certCourse, 'Select a Course');
        resetAndDisable(certIntake, 'Select an Intake');
        certSection.style.display = 'none';
        if (certLocation.value) {
            fetchCertCourses(certLocation.value);
        }
    });

    certCourse.addEventListener('change', function() {
        resetAndDisable(certIntake, 'Select an Intake');
        if (certCourse.value && certLocation.value) {
            fetchCertIntakes(certCourse.value, certLocation.value);
        }
        fetchCertOverallAttendance();
    });

    certIntake.addEventListener('change', fetchCertOverallAttendance);

    // DEGREE EXPORT HANDLERS
    function writePdfLines(doc, lines, startY) {
        let y = startY;
        lines.forEach(function (text) {
            const wrapped = doc.splitTextToSize(String(text || ''), 180);
            wrapped.forEach(function (line) {
                if (y > 280) {
                    doc.addPage();
                    y = 16;
                }
                doc.text(line, 14, y);
                y += 7;
            });
        });
        return y;
    }

    function exportSummaryPdf(options) {
        if (!window.jspdf || typeof window.jspdf.jsPDF !== 'function') {
            showToast('Error', 'PDF export is unavailable. Please refresh the page and try again.', 'error');
            return;
        }
        const doc = new window.jspdf.jsPDF();
        if (typeof doc.autoTable !== 'function') {
            showToast('Error', 'PDF export is unavailable. Please refresh the page and try again.', 'error');
            return;
        }

        doc.setFontSize(16);
        let y = writePdfLines(doc, [options.title], 16);
        doc.setFontSize(11);
        y = writePdfLines(doc, options.meta || [], y + 4);

        const tableRows = [];
        options.tableBody.querySelectorAll('tr').forEach(function (tr) {
            const row = [];
            tr.querySelectorAll('td').forEach(function (td) {
                row.push(td.textContent.trim());
            });
            if (row.length) {
                tableRows.push(row);
            }
        });
        const headers = [];
        options.table.querySelectorAll('thead th').forEach(function (th) {
            headers.push(th.textContent.trim());
        });

        if (!tableRows.length) {
            showToast('Warning', 'No attendance rows to export.', 'warning');
            return;
        }

        doc.autoTable({
            head: [headers],
            body: tableRows,
            startY: y + 4,
            styles: { fontSize: 8, overflow: 'linebreak' },
            headStyles: { fillColor: [68, 114, 196] }
        });
        doc.save(options.filename);
    }

    function downloadExcelFile(formData, filename) {
        showSpinner(true);
        fetch('{{ route('download.attendance.excel') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/json'
            }
        })
        .then(function (response) {
            const contentType = (response.headers.get('content-type') || '').toLowerCase();
            if (!response.ok || contentType.includes('application/json')) {
                return response.json().then(function (data) {
                    throw new Error(data.error || data.message || 'Failed to download Excel file.');
                }).catch(function (error) {
                    if (error instanceof Error && error.message && error.message !== 'Unexpected end of JSON input') {
                        throw error;
                    }
                    throw new Error('Failed to download Excel file.');
                });
            }
            return response.blob();
        })
        .then(function (blob) {
            if (!(blob instanceof Blob)) {
                return;
            }
            if ((blob.type || '').toLowerCase().includes('application/json')) {
                return blob.text().then(function (text) {
                    const data = JSON.parse(text);
                    throw new Error(data.error || data.message || 'Failed to download Excel file.');
                });
            }
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);
        })
        .catch(function (error) {
            showToast('Error', error.message || 'Error downloading Excel file.', 'error');
        })
        .finally(function () {
            showSpinner(false);
        });
    }

    document.getElementById('degreeExportPdfBtn').addEventListener('click', function() {
        if (!degreeLocation.value || !degreeCourse.value || !degreeIntake.value || !degreeSemester.value || !degreeModule.value || !hasDegreeSpecializationSelection()) {
            showToast('Warning', 'Please select all filters before exporting to PDF.', 'warning');
            return;
        }
        exportSummaryPdf({
            title: 'Attendance Report',
            filename: 'degree_attendance_report.pdf',
            table: document.getElementById('degreeAttendanceTable'),
            tableBody: degreeTableBody,
            meta: [
                'Location: ' + (degreeLocation.options[degreeLocation.selectedIndex]?.text || ''),
                'Course: ' + (degreeCourse.options[degreeCourse.selectedIndex]?.text || ''),
                'Intake: ' + (degreeIntake.options[degreeIntake.selectedIndex]?.text || '')
            ].concat(
                degreeSpecializationRow && degreeSpecializationRow.style.display !== 'none'
                    ? ['Specialization: ' + (degreeSpecialization.options[degreeSpecialization.selectedIndex]?.text || '')]
                    : []
            ).concat([
                'Semester: ' + (degreeSemester.options[degreeSemester.selectedIndex]?.text || ''),
                'Module: ' + (degreeModule.options[degreeModule.selectedIndex]?.text || '')
            ])
        });
    });

    document.getElementById('degreeExportExcelBtn').addEventListener('click', function() {
        if (!degreeLocation.value || !degreeCourse.value || !degreeIntake.value || !degreeSemester.value || !degreeModule.value || !hasDegreeSpecializationSelection()) {
            showToast('Warning', 'Please select all filters before exporting to Excel.', 'warning');
            return;
        }
        const formData = new FormData();
        formData.append('location', degreeLocation.value);
        formData.append('course_id', degreeCourse.value);
        formData.append('intake_id', degreeIntake.value);
        formData.append('specialization', degreeSpecialization.value || '');
        formData.append('semester', degreeSemester.value);
        formData.append('module_id', degreeModule.value);
        formData.append('_token', '{{ csrf_token() }}');
        downloadExcelFile(formData, 'degree_attendance_report.xlsx');
    });

    // CERTIFICATE EXPORT HANDLERS
    document.getElementById('certExportPdfBtn').addEventListener('click', function() {
        if (!certLocation.value || !certCourse.value || !certIntake.value) {
            showToast('Warning', 'Please select all filters before exporting to PDF.', 'warning');
            return;
        }
        exportSummaryPdf({
            title: 'Certificate Attendance Report',
            filename: 'certificate_attendance_report.pdf',
            table: document.getElementById('certAttendanceTable'),
            tableBody: certTableBody,
            meta: [
                'Location: ' + (certLocation.options[certLocation.selectedIndex]?.text || ''),
                'Course: ' + (certCourse.options[certCourse.selectedIndex]?.text || ''),
                'Intake: ' + (certIntake.options[certIntake.selectedIndex]?.text || '')
            ]
        });
    });

    document.getElementById('certExportExcelBtn').addEventListener('click', function() {
        if (!certLocation.value || !certCourse.value || !certIntake.value) {
            showToast('Warning', 'Please select all filters before exporting to Excel.', 'warning');
            return;
        }
        const formData = new FormData();
        formData.append('location', certLocation.value);
        formData.append('course_id', certCourse.value);
        formData.append('intake_id', certIntake.value);
        formData.append('semester', '');
        formData.append('module_id', '');
        formData.append('_token', '{{ csrf_token() }}');
        downloadExcelFile(formData, 'certificate_attendance_report.xlsx');
    });

    // DEGREE FETCH FUNCTIONS
    function fetchDegreeCourses(location, courseType) {
        const url = `/attendance/get-courses-by-location?location=${encodeURIComponent(location)}&course_type=${encodeURIComponent(courseType)}`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.courses && data.courses.length > 0) {
                    populateDropdown(degreeCourse, data.courses, 'course_id', 'course_name', 'Course');
                    degreeCourse.disabled = false;
                } else {
                    resetAndDisable(degreeCourse, 'No courses found');
                    showToast('Info', 'No courses found for this location.', 'info');
                }
            })
            .catch(() => {
                resetAndDisable(degreeCourse, 'Select a Course');
            });
    }

    function fetchDegreeIntakes(courseId, location) {
        fetch(`/attendance/get-intakes/${courseId}/${location}`)
            .then(response => response.json())
            .then(data => {
                if (data.intakes && data.intakes.length > 0) {
                    populateDropdown(degreeIntake, data.intakes, 'intake_id', 'batch', 'Intake');
                    degreeIntake.disabled = false;
                } else {
                    resetAndDisable(degreeIntake, 'No intakes found');
                    showToast('Info', 'No intakes found for this course.', 'info');
                }
            });
    }

    function fetchDegreeSemesters(courseId, intakeId) {
        fetch(`/attendance/get-semesters?course_id=${encodeURIComponent(courseId)}&intake_id=${encodeURIComponent(intakeId)}`)
            .then(response => response.json())
            .then(data => {
                if (data.semesters && data.semesters.length > 0) {
                    degreeSemesterEmpty = false;
                    populateDropdown(degreeSemester, data.semesters, 'semester_id', 'semester_name', 'Semester');
                    degreeSemester.disabled = false;
                } else {
                    degreeSemesterEmpty = true;
                    resetAndDisable(degreeSemester, 'No semesters found');
                    showToast('Info', 'No semesters found for this intake.', 'info');
                }
            })
            .catch(function () {
                degreeSemesterEmpty = true;
                resetAndDisable(degreeSemester, 'No semesters found');
                showToast('Info', 'No semesters found for this intake.', 'info');
            });
    }

    function fetchDegreeModules() {
        const data = {
            location: degreeLocation.value,
            course_id: degreeCourse.value,
            intake_id: degreeIntake.value,
            semester: degreeSemester.value,
            specialization: degreeSpecialization.value || ''
        };
        fetch('/get-filtered-modules', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.modules && data.modules.length > 0) {
                degreeModuleEmpty = false;
                populateDropdown(degreeModule, data.modules, 'module_id', 'module_name', 'Module');
                degreeModule.disabled = false;
            } else {
                degreeModuleEmpty = true;
                resetAndDisable(degreeModule, 'No modules found');
                degreeSection.style.display = 'none';
                showToast('Info', 'No modules found for this selection.', 'info');
            }
        });
    }

    function fetchDegreeOverallAttendance() {
        const data = {
            location: degreeLocation.value,
            course_id: degreeCourse.value,
            intake_id: degreeIntake.value,
            specialization: degreeSpecialization.value,
            semester: degreeSemester.value,
            module_id: degreeModule.value
        };
        if (!degreeLocation.value || !degreeCourse.value || !degreeIntake.value || !degreeSemester.value || !degreeModule.value || !hasDegreeSpecializationSelection()) {
            degreeSection.style.display = 'none';
            if (degreeTableBody) {
                degreeTableBody.innerHTML = '';
            }
            if (degreeMatrixHead) {
                degreeMatrixHead.innerHTML = '';
            }
            if (degreeMatrixBody) {
                degreeMatrixBody.innerHTML = '';
            }
            return;
        }
        degreeSection.style.display = '';
        fetch('/get-overall-attendance', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.attendance && data.attendance.length > 0) {
                degreeTableBody.innerHTML = '';
                data.attendance.forEach(row => {
                    degreeTableBody.insertAdjacentHTML('beforeend', `<tr data-student-row>
                        <td data-label="Registration Number">${escapeHtml(row.registration_number)}</td>
                        <td data-label="Student Name">${escapeHtml(row.name_with_initials)}</td>
                        <td data-label="Total Sessions">${escapeHtml(String(row.total_sessions))}</td>
                        <td data-label="Attended Sessions">${escapeHtml(String(row.attended_sessions))}</td>
                        <td data-label="Attendance (%)">${escapeHtml(String(row.percentage))}%</td>
                    </tr>`);
                });
            } else {
                degreeTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No data found.</td></tr>';
            }

            renderAttendanceMatrix(data.matrix, degreeMatrixHead, degreeMatrixBody);
        });
    }

    // CERTIFICATE FETCH FUNCTIONS
    function fetchCertCourses(location) {
        const url = `/attendance/get-courses-by-location?location=${encodeURIComponent(location)}&course_type=certificate`;
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.courses && data.courses.length > 0) {
                    populateDropdown(certCourse, data.courses, 'course_id', 'course_name', 'Course');
                    certCourse.disabled = false;
                } else {
                    resetAndDisable(certCourse, 'No courses found');
                    showToast('Info', 'No courses found for this location.', 'info');
                }
            })
            .catch(() => {
                resetAndDisable(certCourse, 'Select a Course');
            });
    }

    function fetchCertIntakes(courseId, location) {
        fetch(`/attendance/get-intakes/${courseId}/${location}`)
            .then(response => response.json())
            .then(data => {
                if (data.intakes && data.intakes.length > 0) {
                    populateDropdown(certIntake, data.intakes, 'intake_id', 'batch', 'Intake');
                    certIntake.disabled = false;
                } else {
                    resetAndDisable(certIntake, 'No intakes found');
                    showToast('Info', 'No intakes found for this course.', 'info');
                }
            });
    }

    function fetchCertOverallAttendance() {
        const data = {
            location: certLocation.value,
            course_id: certCourse.value,
            intake_id: certIntake.value,
            semester: null,
            module_id: null
        };
        if (!certLocation.value || !certCourse.value || !certIntake.value) {
            certSection.style.display = 'none';
            if (certTableBody) {
                certTableBody.innerHTML = '';
            }
            if (certMatrixHead) {
                certMatrixHead.innerHTML = '';
            }
            if (certMatrixBody) {
                certMatrixBody.innerHTML = '';
            }
            return;
        }
        certSection.style.display = '';
        fetch('/get-overall-attendance', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.attendance && data.attendance.length > 0) {
                certTableBody.innerHTML = '';
                data.attendance.forEach(row => {
                    certTableBody.insertAdjacentHTML('beforeend', `<tr data-student-row>
                        <td data-label="Registration Number">${escapeHtml(row.registration_number)}</td>
                        <td data-label="Student Name">${escapeHtml(row.name_with_initials)}</td>
                        <td data-label="Total Sessions">${escapeHtml(String(row.total_sessions))}</td>
                        <td data-label="Attended Sessions">${escapeHtml(String(row.attended_sessions))}</td>
                        <td data-label="Attendance (%)">${escapeHtml(String(row.percentage))}%</td>
                    </tr>`);
                });
            } else {
                certTableBody.innerHTML = '<tr><td colspan="5" class="text-center">No data found.</td></tr>';
            }

            renderAttendanceMatrix(data.matrix, certMatrixHead, certMatrixBody);
        });
    }

    function renderAttendanceMatrix(matrix, headEl, bodyEl) {
        if (!headEl || !bodyEl) return;

        const students = matrix && Array.isArray(matrix.students) ? matrix.students : [];
        const rows = matrix && Array.isArray(matrix.rows) ? matrix.rows : [];

        if (!students.length || !rows.length) {
            headEl.innerHTML = '<tr><th>Date</th></tr>';
            bodyEl.innerHTML = '<tr><td class="text-center">No date-wise attendance data found.</td></tr>';
            return;
        }

        let headHtml = '<tr><th>Date</th>';
        students.forEach(student => {
            const studentName = student.name_with_initials || '-';
            const regNo = student.registration_number || '-';
            headHtml += `<th><div class="matrix-student-header"><span class="matrix-student-name">${escapeHtml(studentName)}</span><span class="matrix-student-reg">${escapeHtml(String(regNo))}</span></div></th>`;
        });
        headHtml += '</tr>';
        headEl.innerHTML = headHtml;

        bodyEl.innerHTML = '';
        rows.forEach(row => {
            let rowHtml = `<tr><td>${escapeHtml(row.date || '')}</td>`;
            students.forEach(student => {
                const key = String(student.student_id);
                const status = row.statuses ? row.statuses[key] : null;

                if (status === true) {
                    rowHtml += '<td class="text-center"><span class="badge bg-success">P</span></td>';
                } else if (status === false) {
                    rowHtml += '<td class="text-center"><span class="badge bg-danger">A</span></td>';
                } else {
                    rowHtml += '<td class="text-center text-muted">-</td>';
                }
            });
            rowHtml += '</tr>';
            bodyEl.insertAdjacentHTML('beforeend', rowHtml);
        });
    }

    // Populate dropdown with items
    function populateDropdown(select, items, valueKey, textKey, defaultText) {
        if (!select) return;
        select.innerHTML = '';
        const placeholder = new Option('Select ' + defaultText, '', true, true);
        placeholder.disabled = true;
        select.add(placeholder);
        (items || []).forEach(item => {
            select.add(new Option(item[textKey], item[valueKey]));
        });
    }
});
</script>
@endpush 