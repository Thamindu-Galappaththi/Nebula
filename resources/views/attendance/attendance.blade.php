@extends('inc.app')

@section('title', 'NEBULA | Attendance')

@section('content')
<style nonce="{{ $cspNonce }}">
    .attendance-page,
    .attendance-page .card,
    .attendance-page .card-body {
        min-width: 0;
        max-width: 100%;
        overflow: visible;
        height: auto;
    }
    .attendance-page .tab-content > .tab-pane:not(.active) {
        display: none !important;
        height: 0;
        overflow: hidden;
    }
    body:has(.attendance-page) .body-wrapper > .container-fluid {
        overflow: visible;
    }
    .attendance-page [class*="col-"] {
        min-width: 0;
    }
    .attendance-page .form-select,
    .attendance-page .form-control,
    .attendance-page .nebula-select,
    .attendance-page .nebula-select-toggle {
        width: 100%;
        max-width: 100%;
    }
    .attendance-tabs {
        flex-wrap: wrap;
        overflow: hidden;
        row-gap: 0.25rem;
    }
    .attendance-bulk-help {
        display: block;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        padding: 0.9rem 1.1rem;
        margin-bottom: 1rem;
        text-align: justify;
        text-justify: inter-word;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    .attendance-bulk-help .badge {
        white-space: nowrap;
        vertical-align: middle;
    }
    .attendance-bulk-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }
    .attendance-bulk-actions > .btn {
        flex: 0 0 auto;
        width: auto;
        white-space: nowrap;
    }
    .attendance-file-picker {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        flex: 1 1 220px;
        min-width: 0;
        max-width: 100%;
        border: 1px solid #dee2e6;
        border-radius: 0.375rem;
        padding: 0.25rem 0.5rem;
        background: #fff;
    }
    .attendance-file-picker .btn {
        flex: 0 0 auto;
        white-space: nowrap;
    }
    .attendance-file-name {
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .attendance-table th,
    .attendance-table td {
        word-break: break-word;
        vertical-align: middle;
    }
    .attendance-toast {
        max-width: min(360px, calc(100vw - 1.5rem));
    }
    .lds-ring { display: inline-block; position: relative; width: 80px; height: 80px; }
    .lds-ring div { box-sizing: border-box; display: block; position: absolute; width: 64px; height: 64px; margin: 8px; border: 8px solid #fff; border-radius: 50%; animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite; border-color: #fff transparent transparent transparent; }
    .lds-ring div:nth-child(1) { animation-delay: -0.45s; }
    .lds-ring div:nth-child(2) { animation-delay: -0.3s; }
    .lds-ring div:nth-child(3) { animation-delay: -0.15s; }
    @keyframes lds-ring { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    #spinner-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); display: none; justify-content: center; align-items: center; z-index: 9999; }
    @media (max-width: 991.98px) {
        .attendance-file-picker {
            width: 100%;
            flex: 1 1 100%;
        }
    }
    @media (max-width: 767.98px) {
        .attendance-page h2 {
            font-size: 1.25rem;
        }
        .attendance-page .card-body {
            padding: 1rem 0.75rem;
        }
        .attendance-page .form-control,
        .attendance-page .form-select,
        .attendance-page .nebula-select-toggle {
            font-size: 16px;
        }
        .attendance-page .col-form-label {
            text-align: left !important;
            padding-bottom: 0.2rem;
        }
        .attendance-table thead {
            display: none;
        }
        .attendance-table,
        .attendance-table tbody,
        .attendance-table tr,
        .attendance-table td {
            display: block;
            width: 100%;
        }
        .attendance-table tbody tr[data-student-row] {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 8px 12px 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }
        .attendance-table td {
            border: 0;
            padding: 0.45rem 0;
        }
        .attendance-table td[data-label]::before {
            content: attr(data-label);
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 attendance-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Attendance</h2>
            <hr>

            <!-- Spinner and Toast containers -->
            <div id="spinner-overlay" style="display:none;"><div class="lds-ring"><div></div><div></div><div></div><div></div></div></div>
            <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3 attendance-toast" aria-live="polite" aria-atomic="true" style="z-index: 1090;"></div>

            <!-- Tabs -->
            <ul class="nav nav-tabs mb-4 attendance-tabs" id="attendanceTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="degree-tab" data-bs-toggle="tab" data-bs-target="#degree-panel" type="button" role="tab">Degree & Diploma</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="certificate-tab" data-bs-toggle="tab" data-bs-target="#certificate-panel" type="button" role="tab">Certificate</button>
                </li>
            </ul>

            <div class="tab-content" id="attendanceTabContent">
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
                            <div class="mb-3 row mx-0">
                                <label for="degree_date" class="col-md-2 col-form-label fw-bold">Date <span class="text-danger">*</span></label>
                                <div class="col-md-10">
                                    <input type="date" class="form-control" id="degree_date" name="attendance_date" required>
                                </div>
                            </div>
                            <div class="mb-3 row mx-0">
                                <label for="degree_attendance_type" class="col-md-2 col-form-label fw-bold">Attendance Type <span class="text-danger">*</span></label>
                                <div class="col-md-10">
                                    <select class="form-select degree-filter" id="degree_attendance_type" name="attendance_type" required>
                                        <option value="" selected disabled>Select Attendance Type</option>
                                        <option value="lectures">Lectures</option>
                                        <option value="labs">Labs</option>
                                        <option value="special_lectures">Special Lectures</option>
                                        <option value="tutorials">Tutorials</option>
                                        <option value="other">Other Categories</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Degree Bulk Import Section -->
                    <div id="degreeBulkImportSection" class="mb-4" style="display:none;">
                        <hr class="my-4">
                        <h5>Bulk Import</h5>
                        <p class="text-muted">Download a pre-populated template with student details, mark attendance, then upload it back.</p>
                        <div class="alert alert-info attendance-bulk-help">
                            <strong>How to fill the template:</strong>
                            The template will be pre-filled with student registration numbers and names. In the <strong>"attendance"</strong> column, select from dropdown: <span class="badge bg-success">Present</span> or <span class="badge bg-danger">Absent</span>. You must use exactly these words (not case-sensitive).
                        </div>
                        <div class="attendance-bulk-actions">
                            <a id="degreeDownloadTemplateBtn" href="#" class="btn btn-sm btn-outline-primary">Download Template (XLSX)</a>
                            <div class="attendance-file-picker">
                                <input type="file" id="degreeAttendanceFileInput" accept=".csv, .xlsx, .xls" class="d-none">
                                <label for="degreeAttendanceFileInput" class="btn btn-sm btn-outline-secondary mb-0">Choose File</label>
                                <span id="degreeAttendanceFileName" class="attendance-file-name text-muted">No file chosen</span>
                            </div>
                            <button type="button" id="degreeUploadAttendanceFileBtn" class="btn btn-sm btn-primary">Upload File</button>
                        </div>
                    </div>

                    <!-- Degree Attendance Table -->
                    <div class="mt-4" id="degreeAttendanceTableSection" style="display:none;">
                        <h4 id="degreeAttendanceTableHeader" class="text-center mb-3" style="display: none;"></h4>
                        <div class="table-responsive">
                            <table class="table table-bordered attendance-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Course Registration ID</th>
                                        <th>Student Name</th>
                                        <th>Present</th>
                                    </tr>
                                </thead>
                                <tbody id="degreeAttendanceTableBody">
                                    <!-- Rows will be added here dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Degree Submit Button -->
                    <div class="text-center mt-4" id="degreeSaveAttendanceBtnSection" style="display:none;">
                        <button type="button" id="degreeSaveAttendanceBtn" class="btn btn-primary w-100 py-2">Save Attendance</button>
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
                            <div class="mb-3 row mx-0">
                                <label for="cert_date" class="col-md-2 col-form-label fw-bold">Date <span class="text-danger">*</span></label>
                                <div class="col-md-10">
                                    <input type="date" class="form-control" id="cert_date" name="attendance_date" required>
                                </div>
                            </div>
                            <div class="mb-3 row mx-0">
                                <label for="cert_attendance_type" class="col-md-2 col-form-label fw-bold">Attendance Type <span class="text-danger">*</span></label>
                                <div class="col-md-10">
                                    <select class="form-select cert-filter" id="cert_attendance_type" name="attendance_type" required>
                                        <option value="" selected disabled>Select Attendance Type</option>
                                        <option value="lectures">Lectures</option>
                                        <option value="labs">Labs</option>
                                        <option value="special_lectures">Special Lectures</option>
                                        <option value="tutorials">Tutorials</option>
                                        <option value="other">Other Categories</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Certificate Bulk Import Section -->
                    <div id="certBulkImportSection" class="mb-4" style="display:none;">
                        <hr class="my-4">
                        <h5>Bulk Import</h5>
                        <p class="text-muted">Download a pre-populated template with student details, mark attendance, then upload it back.</p>
                        <div class="alert alert-info attendance-bulk-help">
                            <strong>How to fill the template:</strong>
                            The template will be pre-filled with student registration numbers and names. In the <strong>"attendance"</strong> column, select from dropdown: <span class="badge bg-success">Present</span> or <span class="badge bg-danger">Absent</span>. You must use exactly these words (not case-sensitive).
                        </div>
                        <div class="attendance-bulk-actions">
                            <a id="certDownloadTemplateBtn" href="#" class="btn btn-sm btn-outline-primary">Download Template (XLSX)</a>
                            <div class="attendance-file-picker">
                                <input type="file" id="certAttendanceFileInput" accept=".csv, .xlsx, .xls" class="d-none">
                                <label for="certAttendanceFileInput" class="btn btn-sm btn-outline-secondary mb-0">Choose File</label>
                                <span id="certAttendanceFileName" class="attendance-file-name text-muted">No file chosen</span>
                            </div>
                            <button type="button" id="certUploadAttendanceFileBtn" class="btn btn-sm btn-primary">Upload File</button>
                        </div>
                    </div>

                    <!-- Certificate Attendance Table -->
                    <div class="mt-4" id="certAttendanceTableSection" style="display:none;">
                        <h4 id="certAttendanceTableHeader" class="text-center mb-3" style="display: none;"></h4>
                        <div class="table-responsive">
                            <table class="table table-bordered attendance-table">
                                <thead class="table-light">
                                    <tr>
                                        <th>No</th>
                                        <th>Course Registration ID</th>
                                        <th>Student Name</th>
                                        <th>Present</th>
                                    </tr>
                                </thead>
                                <tbody id="certAttendanceTableBody">
                                    <!-- Rows will be added here dynamically -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Certificate Submit Button -->
                    <div class="text-center mt-4" id="certSaveAttendanceBtnSection" style="display:none;">
                        <button type="button" id="certSaveAttendanceBtn" class="btn btn-primary w-100 py-2">Save Attendance</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function() {
    let degreeStudents = [];
    let certStudents = [];
    let degreeSpecializationsLoaded = false;
    let degreeSemesterEmpty = false;
    let degreeModuleEmpty = false;
    let degreeFetchId = 0;
    let certFetchId = 0;
    
    // Degree Tab Elements
    const degreeLocation = document.getElementById('degree_location');
    const degreeCourseType = document.getElementById('degree_course_type');
    const degreeCourse = document.getElementById('degree_course');
    const degreeIntake = document.getElementById('degree_intake');
    const degreeSpecialization = document.getElementById('degree_specialization');
    const degreeSpecializationRow = document.getElementById('degree_specialization_row');
    const degreeSemester = document.getElementById('degree_semester');
    const degreeModule = document.getElementById('degree_module');
    const degreeDate = document.getElementById('degree_date');
    const degreeAttendanceType = document.getElementById('degree_attendance_type');
    
    // Certificate Tab Elements
    const certLocation = document.getElementById('cert_location');
    const certCourse = document.getElementById('cert_course');
    const certIntake = document.getElementById('cert_intake');
    const certDate = document.getElementById('cert_date');
    const certAttendanceType = document.getElementById('cert_attendance_type');
    
    // Degree-specific elements
    const degreeAttendanceTableBody = document.getElementById('degreeAttendanceTableBody');
    const degreeSaveAttendanceBtn = document.getElementById('degreeSaveAttendanceBtn');
    const degreeAttendanceTableHeader = document.getElementById('degreeAttendanceTableHeader');
    const degreeDownloadTemplateBtn = document.getElementById('degreeDownloadTemplateBtn');
    const degreeAttendanceFileInput = document.getElementById('degreeAttendanceFileInput');
    const degreeUploadAttendanceFileBtn = document.getElementById('degreeUploadAttendanceFileBtn');
    
    // Certificate-specific elements
    const certAttendanceTableBody = document.getElementById('certAttendanceTableBody');
    const certSaveAttendanceBtn = document.getElementById('certSaveAttendanceBtn');
    const certAttendanceTableHeader = document.getElementById('certAttendanceTableHeader');
    const certDownloadTemplateBtn = document.getElementById('certDownloadTemplateBtn');
    const certAttendanceFileInput = document.getElementById('certAttendanceFileInput');
    const certUploadAttendanceFileBtn = document.getElementById('certUploadAttendanceFileBtn');
    const degreeAttendanceFileName = document.getElementById('degreeAttendanceFileName');
    const certAttendanceFileName = document.getElementById('certAttendanceFileName');

    resetAndDisable(degreeCourse, 'Select a Course');
    resetAndDisable(degreeIntake, 'Select an Intake');
    resetAndDisable(degreeSemester, 'Select a Semester');
    resetAndDisable(degreeModule, 'Select a Module');
    resetAndDisable(certCourse, 'Select a Course');
    resetAndDisable(certIntake, 'Select an Intake');
    
    // Tab change event listeners to ensure proper section visibility
    const degreeTabBtn = document.getElementById('degree-tab');
    const certTabBtn = document.getElementById('certificate-tab');
    
    degreeTabBtn.addEventListener('shown.bs.tab', function() {
        updateBulkImportSection();
        maybeFetchDegreeStudents();
    });

    certTabBtn.addEventListener('shown.bs.tab', function() {
        maybeFetchCertStudents();
    });
    
    // Helper functions
    function getActiveTab() {
        const degreePanel = document.getElementById('degree-panel');
        return degreePanel.classList.contains('active') && degreePanel.classList.contains('show') ? 'degree' : 'certificate';
    }
    
    function getCurrentStudents() {
        return getActiveTab() === 'degree' ? degreeStudents : certStudents;
    }
    
    function setCurrentStudents(students) {
        if (getActiveTab() === 'degree') {
            degreeStudents = students;
        } else {
            certStudents = students;
        }
    }

    function escapeHtml(text) {
        const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
        return String(text ?? '').replace(/[&<>"']/g, function (match) {
            return map[match];
        });
    }

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
            degreeModule.disabled = false;
            handleDegreeModuleFetch();
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

    // DEGREE TAB EVENT LISTENERS
    degreeLocation.addEventListener('change', function() {
        degreeSemesterEmpty = false;
        degreeModuleEmpty = false;
        resetAndDisable(degreeCourse, 'Select a Course');
        resetAndDisable(degreeIntake, 'Select an Intake');
        resetSpecialization();
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        if (degreeLocation.value && degreeCourseType.value) {
            fetchDegreeCourses(degreeLocation.value, degreeCourseType.value);
        }
        maybeFetchDegreeStudents();
    });

    degreeCourseType.addEventListener('change', function() {
        degreeSemesterEmpty = false;
        degreeModuleEmpty = false;
        resetAndDisable(degreeCourse, 'Select a Course');
        resetAndDisable(degreeIntake, 'Select an Intake');
        resetSpecialization();
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        if (degreeLocation.value && this.value) {
            fetchDegreeCourses(degreeLocation.value, this.value);
        }
        maybeFetchDegreeStudents();
    });

    degreeCourse.addEventListener('change', function() {
        degreeSemesterEmpty = false;
        degreeModuleEmpty = false;
        resetAndDisable(degreeIntake, 'Select an Intake');
        resetSpecialization();
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        if (degreeCourse.value && degreeLocation.value) {
            degreeIntake.disabled = false;
            fetchDegreeSpecializations();
            handleDegreeIntakeFetch();
        }
        maybeFetchDegreeStudents();
    });

    degreeIntake.addEventListener('change', function() {
        degreeSemesterEmpty = false;
        degreeModuleEmpty = false;
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        if (degreeIntake.value && degreeCourse.value) {
            fetchDegreeSemesters(degreeCourse.value, degreeIntake.value);
        }
        maybeFetchDegreeStudents();
    });

    degreeSemester.addEventListener('change', function() {
        degreeModuleEmpty = false;
        resetAndDisable(degreeModule, 'Select a Module');
        maybeFetchDegreeModules();
        maybeFetchDegreeStudents();
    });

    degreeModule.addEventListener('change', function() {
        maybeFetchDegreeStudents();
    });

    degreeDate.addEventListener('change', function() {
        maybeFetchDegreeStudents();
        updateBulkImportSection();
    });

    degreeSpecialization.addEventListener('change', function() {
        resetAndDisable(degreeModule, 'Select a Module');
        maybeFetchDegreeModules();
        maybeFetchDegreeStudents();
        updateBulkImportSection();
    });

    degreeAttendanceType.addEventListener('change', function() {
        maybeFetchDegreeStudents();
        updateBulkImportSection();
    });

    // CERTIFICATE TAB EVENT LISTENERS
    certLocation.addEventListener('change', function() {
        resetAndDisable(certCourse, 'Select a Course');
        resetAndDisable(certIntake, 'Select an Intake');
        if (certLocation.value) {
            fetchCertCourses(certLocation.value);
        }
        maybeFetchCertStudents();
    });

    certCourse.addEventListener('change', function() {
        resetAndDisable(certIntake, 'Select an Intake');
        if (certCourse.value && certLocation.value) {
            certIntake.disabled = false;
            handleCertIntakeFetch();
        }
        maybeFetchCertStudents();
    });

    certIntake.addEventListener('change', function() {
        maybeFetchCertStudents();
    });

    certDate.addEventListener('change', function() {
        maybeFetchCertStudents();
    });

    certAttendanceType.addEventListener('change', function() {
        maybeFetchCertStudents();
    });

    // Validation functions
    function allDegreeFilled() {
         return degreeLocation.value && degreeCourseType.value && degreeCourse.value && 
             degreeIntake.value && hasDegreeSpecializationSelection() && degreeSemester.value && degreeModule.value && degreeDate.value && degreeAttendanceType.value;
    }

    function allCertFilled() {
        return certLocation.value && certCourse.value && certIntake.value && certDate.value && certAttendanceType.value;
    }

    function hideDegreeResults() {
        degreeFetchId += 1;
        degreeStudents = [];
        if (degreeAttendanceTableBody) {
            degreeAttendanceTableBody.innerHTML = '';
        }
        document.getElementById('degreeAttendanceTableSection').style.display = 'none';
        document.getElementById('degreeSaveAttendanceBtnSection').style.display = 'none';
    }

    function hideCertResults() {
        certFetchId += 1;
        certStudents = [];
        if (certAttendanceTableBody) {
            certAttendanceTableBody.innerHTML = '';
        }
        document.getElementById('certAttendanceTableSection').style.display = 'none';
        document.getElementById('certSaveAttendanceBtnSection').style.display = 'none';
    }

    function maybeFetchDegreeStudents() {
        if (allDegreeFilled()) {
            fetchDegreeStudentsForAttendance();
        } else {
            hideDegreeResults();
            updateBulkImportSection();
        }
    }

    function maybeFetchCertStudents() {
        if (allCertFilled()) {
            fetchCertStudentsForAttendance();
        } else {
            hideCertResults();
            updateBulkImportSection();
        }
    }

    // Helper to populate dropdowns
    function populateDropdown(select, items, valueKey, textKey, defaultText) {
        if (!select) return;
        select.innerHTML = '';
        const placeholder = new Option('Select ' + defaultText, '', true, true);
        placeholder.disabled = true;
        select.add(placeholder);
        (items || []).forEach(item => {
            const value = item[valueKey];
            const displayText = item[textKey];
            if (displayText && value) {
                select.add(new Option(displayText, value));
            }
        });
    }

    // Fetch functions for Degree tab
    function fetchDegreeCourses(location, courseType) {
        showSpinner(true);
        fetch(`/attendance/get-courses-by-location?location=${encodeURIComponent(location)}&course_type=${encodeURIComponent(courseType)}`)
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
            .catch(() => resetAndDisable(degreeCourse, 'Select a Course'))
            .finally(() => showSpinner(false));
    }

    function handleDegreeIntakeFetch() {
        showSpinner(true);
        fetch(`/attendance/get-intakes/${degreeCourse.value}/${degreeLocation.value}`)
            .then(response => response.json())
            .then(data => {
                if (!data.error && data.intakes && data.intakes.length > 0) {
                    populateDropdown(degreeIntake, data.intakes, 'intake_id', 'batch', 'Intake');
                    degreeIntake.disabled = false;
                } else {
                    resetAndDisable(degreeIntake, 'No intakes found');
                    showToast('Info', 'No intakes found for this course.', 'info');
                }
            })
            .catch(() => resetAndDisable(degreeIntake, 'Select an Intake'))
            .finally(() => showSpinner(false));
    }

    function fetchDegreeSemesters(courseId, intakeId) {
        showSpinner(true);
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
            .catch(() => {
                degreeSemesterEmpty = true;
                resetAndDisable(degreeSemester, 'No semesters found');
                showToast('Info', 'No semesters found for this intake.', 'info');
            })
            .finally(() => showSpinner(false));
    }

    function handleDegreeModuleFetch() {
        const data = {
            location: degreeLocation.value,
            course_id: degreeCourse.value,
            intake_id: degreeIntake.value,
            semester: degreeSemester.value,
            specialization: degreeSpecialization.value || ''
        };
        showSpinner(true);
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
                showToast('Info', 'No modules found for this selection.', 'info');
            }
        })
        .catch(() => {
            degreeModuleEmpty = true;
            resetAndDisable(degreeModule, 'No modules found');
            showToast('Info', 'No modules found for this selection.', 'info');
        })
        .finally(() => showSpinner(false));
    }

    // Fetch functions for Certificate tab
    function fetchCertCourses(location) {
        showSpinner(true);
        fetch(`/attendance/get-courses-by-location?location=${encodeURIComponent(location)}&course_type=certificate`)
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
            .catch(() => resetAndDisable(certCourse, 'Select a Course'))
            .finally(() => showSpinner(false));
    }

    function handleCertIntakeFetch() {
        showSpinner(true);
        fetch(`/attendance/get-intakes/${certCourse.value}/${certLocation.value}`)
            .then(response => response.json())
            .then(data => {
                if (!data.error && data.intakes && data.intakes.length > 0) {
                    populateDropdown(certIntake, data.intakes, 'intake_id', 'batch', 'Intake');
                    certIntake.disabled = false;
                } else {
                    resetAndDisable(certIntake, 'No intakes found');
                    showToast('Info', 'No intakes found for this course.', 'info');
                }
            })
            .catch(() => resetAndDisable(certIntake, 'Select an Intake'))
            .finally(() => showSpinner(false));
    }

    function fetchDegreeStudentsForAttendance() {
        const data = {
            location: degreeLocation.value,
            course_type: degreeCourseType.value,
            course_id: degreeCourse.value,
            intake_id: degreeIntake.value,
            specialization: degreeSpecialization.value,
            semester: degreeSemester.value,
            module_id: degreeModule.value,
            date: degreeDate.value
        };
        showSpinner(true);
        const requestId = ++degreeFetchId;
        fetch('/get-students-for-attendance', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (requestId !== degreeFetchId) {
                return;
            }
            if (data.success && data.students && data.students.length > 0) {
                degreeStudents = data.students.map(s => ({ ...s, status: true }));
                renderAttendanceTable();
                document.getElementById('degreeAttendanceTableSection').style.display = '';
                document.getElementById('degreeSaveAttendanceBtnSection').style.display = '';
            } else if (data.success && data.students && data.students.length === 0) {
                degreeStudents = [];
                if (degreeAttendanceTableBody) {
                    degreeAttendanceTableBody.innerHTML = '';
                }
                showToast('Warning', 'No students found for these filters. Please verify the filters are correct.', 'warning');
                document.getElementById('degreeAttendanceTableSection').style.display = 'none';
                document.getElementById('degreeSaveAttendanceBtnSection').style.display = 'none';
            } else {
                degreeStudents = [];
                if (degreeAttendanceTableBody) {
                    degreeAttendanceTableBody.innerHTML = '';
                }
                showToast('Error', data.message || 'Failed to fetch students.', 'error');
                document.getElementById('degreeAttendanceTableSection').style.display = 'none';
                document.getElementById('degreeSaveAttendanceBtnSection').style.display = 'none';
            }
            updateBulkImportSection();
        })
        .catch(() => {
            if (requestId !== degreeFetchId) {
                return;
            }
            showToast('Error', 'Failed to fetch students.', 'error');
            document.getElementById('degreeAttendanceTableSection').style.display = 'none';
            document.getElementById('degreeSaveAttendanceBtnSection').style.display = 'none';
            updateBulkImportSection();
        })
        .finally(() => showSpinner(false));
    }

    function fetchCertStudentsForAttendance() {
        const data = {
            location: certLocation.value,
            course_type: 'certificate',
            course_id: certCourse.value,
            intake_id: certIntake.value,
            semester: null,
            module_id: null,
            date: certDate.value
        };
        showSpinner(true);
        const requestId = ++certFetchId;
        fetch('/get-students-for-attendance', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (requestId !== certFetchId) {
                return;
            }
            if (data.success && data.students && data.students.length > 0) {
                certStudents = data.students.map(s => ({ ...s, status: true }));
                renderAttendanceTable();
                document.getElementById('certAttendanceTableSection').style.display = '';
                document.getElementById('certSaveAttendanceBtnSection').style.display = '';
            } else if (data.success && data.students && data.students.length === 0) {
                hideCertResults();
                showToast('Warning', 'No students found for these filters. Please verify the filters are correct.', 'warning');
            } else {
                hideCertResults();
                showToast('Error', data.message || 'Failed to fetch students.', 'error');
            }
            updateBulkImportSection();
        })
        .catch(() => {
            if (requestId !== certFetchId) {
                return;
            }
            hideCertResults();
            showToast('Error', 'Failed to fetch students.', 'error');
            updateBulkImportSection();
        })
        .finally(() => showSpinner(false));
    }

    function renderAttendanceTable() {
        const activeTab = getActiveTab();
        const students = getCurrentStudents();
        const tableBody = activeTab === 'degree' ? degreeAttendanceTableBody : certAttendanceTableBody;

        tableBody.innerHTML = '';
        students.forEach((student, index) => {
            const courseRegistrationId = student.course_registration_id || student.registration_id || student.registration_number || '-';
            const row = '<tr data-student-row>' +
                '<td data-label="No">' + escapeHtml(index + 1) + '</td>' +
                '<td data-label="Course Registration ID">' + escapeHtml(courseRegistrationId) + '</td>' +
                '<td data-label="Student Name">' + escapeHtml(student.name_with_initials || '-') + '</td>' +
                '<td class="text-center" data-label="Present">' +
                    '<input type="checkbox" ' + (student.status ? 'checked' : '') + ' data-student-index="' + index + '" class="attendance-checkbox-' + activeTab + '">' +
                '</td>' +
            '</tr>';
            tableBody.insertAdjacentHTML('beforeend', row);
        });

        document.querySelectorAll('.attendance-checkbox-' + activeTab).forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const currentStudents = getCurrentStudents();
                const index = parseInt(this.getAttribute('data-student-index'), 10);
                if (currentStudents[index]) {
                    currentStudents[index].status = this.checked;
                }
            });
        });
    }

    function updateBulkImportSection() {
        const activeTab = getActiveTab();
        const degreeBulk = document.getElementById('degreeBulkImportSection');
        const certBulk = document.getElementById('certBulkImportSection');

        if (activeTab === 'degree' && allDegreeFilled()) {
            const params = new URLSearchParams();
            params.append('location', degreeLocation.value || '');
            params.append('course_id', degreeCourse.value || '');
            params.append('intake_id', degreeIntake.value || '');
            params.append('specialization', degreeSpecialization.value || '');
            params.append('semester', degreeSemester.value || '');
            params.append('module_id', degreeModule.value || '');
            params.append('date', degreeDate.value || '');
            params.append('attendance_type', degreeAttendanceType.value || '');
            degreeBulk.style.display = '';
            degreeDownloadTemplateBtn.href = '{{ route('attendance.download.template') }}?' + params.toString();
        } else {
            degreeBulk.style.display = 'none';
        }

        if (activeTab === 'certificate' && allCertFilled()) {
            const params = new URLSearchParams();
            params.append('location', certLocation.value || '');
            params.append('course_id', certCourse.value || '');
            params.append('intake_id', certIntake.value || '');
            params.append('semester', '');
            params.append('module_id', '');
            params.append('date', certDate.value || '');
            params.append('attendance_type', certAttendanceType.value || '');
            certBulk.style.display = '';
            certDownloadTemplateBtn.href = '{{ route('attendance.download.template') }}?' + params.toString();
        } else {
            certBulk.style.display = 'none';
        }
    }

    // Degree save attendance button
    degreeSaveAttendanceBtn.addEventListener('click', function() {
        const students = degreeStudents;
        
        let data = {
            attendance_data: students.map(s => ({ student_id: s.student_id, status: s.status }))
        };
        
        data.location = degreeLocation.value;
        data.course_type = degreeCourseType.value;
        data.course_id = degreeCourse.value;
        data.intake_id = degreeIntake.value;
        data.semester = degreeSemester.value;
        data.module_id = degreeModule.value;
        data.date = degreeDate.value;
        data.attendance_type = degreeAttendanceType.value;
        
        if (Object.values(data).filter(v => v !== null).some(v => !v) || !data.attendance_data.length) {
            showToast('Warning', 'Please select all filters and mark attendance for at least one student.', 'warning');
            return;
        }
        
        showSpinner(true);
        fetch('/store-attendance', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', 'Attendance saved successfully!', 'success');
                document.getElementById('degreeAttendanceTableSection').style.display = 'none';
                document.getElementById('degreeSaveAttendanceBtnSection').style.display = 'none';
            } else {
                showToast('Error', data.message || 'Failed to save attendance.', 'error');
            }
        })
        .catch(() => {
            showToast('Error', 'Failed to save attendance.', 'error');
        })
        .finally(() => showSpinner(false));
    });

    // Certificate save attendance button
    certSaveAttendanceBtn.addEventListener('click', function() {
        const students = certStudents;
        
        let data = {
            attendance_data: students.map(s => ({ student_id: s.student_id, status: s.status }))
        };
        
        data.location = certLocation.value;
        data.course_type = 'certificate';
        data.course_id = certCourse.value;
        data.intake_id = certIntake.value;
        data.semester = null;
        data.module_id = null;
        data.date = certDate.value;
        data.attendance_type = certAttendanceType.value;
        
        if (Object.values(data).filter(v => v !== null).some(v => !v) || !data.attendance_data.length) {
            showToast('Warning', 'Please select all filters and mark attendance for at least one student.', 'warning');
            return;
        }
        
        showSpinner(true);
        fetch('/store-attendance', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', 'Attendance saved successfully!', 'success');
                document.getElementById('certAttendanceTableSection').style.display = 'none';
                document.getElementById('certSaveAttendanceBtnSection').style.display = 'none';
            } else {
                showToast('Error', data.message || 'Failed to save attendance.', 'error');
            }
        })
        .catch(() => {
            showToast('Error', 'Failed to save attendance.', 'error');
        })
        .finally(() => showSpinner(false));
    });

    function showSpinner(show) {
        document.getElementById('spinner-overlay').style.display = show ? 'flex' : 'none';
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
        const instance = bootstrap.Toast.getOrCreateInstance(toast, { delay: 4000 });
        instance.show();
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
    }

    degreeAttendanceFileInput.addEventListener('change', function() {
        if (degreeAttendanceFileName) {
            degreeAttendanceFileName.textContent = this.files && this.files[0] ? this.files[0].name : 'No file chosen';
        }
    });

    certAttendanceFileInput.addEventListener('change', function() {
        if (certAttendanceFileName) {
            certAttendanceFileName.textContent = this.files && this.files[0] ? this.files[0].name : 'No file chosen';
        }
    });

    // Degree bulk import upload handler
    degreeUploadAttendanceFileBtn.addEventListener('click', function() {
        const file = degreeAttendanceFileInput.files[0];
        if (!file) {
            showToast('Warning', 'Please choose a file to upload.', 'warning');
            return;
        }

        const payload = new FormData();
        payload.append('attendance_file', file);
        payload.append('location', degreeLocation.value || '');
        payload.append('course_id', degreeCourse.value || '');
        payload.append('intake_id', degreeIntake.value || '');
        payload.append('semester', degreeSemester.value || '');
        payload.append('module_id', degreeModule.value || '');
        payload.append('date', degreeDate.value || '');
        payload.append('attendance_type', degreeAttendanceType.value || '');
        payload.append('_token', '{{ csrf_token() }}');

        showSpinner(true);
        fetch('/attendance/import', {
            method: 'POST',
            body: payload
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Success', data.message || 'Import successful', 'success');
                // Use the returned student data with their attendance status
                if (data.students && data.students.length > 0) {
                    degreeStudents = data.students;
                    renderAttendanceTable();
                    document.getElementById('degreeAttendanceTableSection').style.display = '';
                    document.getElementById('degreeSaveAttendanceBtnSection').style.display = '';
                } else {
                    fetchDegreeStudentsForAttendance();
                }
            } else {
                showToast('Error', data.message || 'Import failed', 'error');
            }
        })
        .catch(() => {
            showToast('Error', 'Upload failed. Please try again.', 'error');
        })
        .finally(() => showSpinner(false));
    });

    // Degree template download should also render the table on page
    degreeDownloadTemplateBtn.addEventListener('click', function(event) {
        if (!allDegreeFilled()) {
            event.preventDefault();
            showToast('Warning', 'Please select all filters before downloading the template.', 'warning');
            return;
        }
        updateBulkImportSection();
        fetchDegreeStudentsForAttendance();
    });

    // Certificate bulk import upload handler
    certUploadAttendanceFileBtn.addEventListener('click', function() {
        const file = certAttendanceFileInput.files[0];
        if (!file) {
            showToast('Warning', 'Please choose a file to upload.', 'warning');
            return;
        }

        const payload = new FormData();
        payload.append('attendance_file', file);
        payload.append('location', certLocation.value || '');
        payload.append('course_id', certCourse.value || '');
        payload.append('intake_id', certIntake.value || '');
        payload.append('semester', '');
        payload.append('module_id', '');
        payload.append('date', certDate.value || '');
        payload.append('attendance_type', certAttendanceType.value || '');
        payload.append('_token', '{{ csrf_token() }}');

        showSpinner(true);
        fetch('/attendance/import', {
            method: 'POST',
            body: payload
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                showToast('Success', data.message || 'Import successful', 'success');
                // Use the returned student data with their attendance status
                if (data.students && data.students.length > 0) {
                    certStudents = data.students;
                    renderAttendanceTable();
                    document.getElementById('certAttendanceTableSection').style.display = '';
                    document.getElementById('certSaveAttendanceBtnSection').style.display = '';
                } else {
                    fetchCertStudentsForAttendance();
                }
            } else {
                showToast('Error', data.message || 'Import failed', 'error');
            }
        })
        .catch(() => {
            showToast('Error', 'Upload failed. Please try again.', 'error');
        })
        .finally(() => showSpinner(false));
    });

    // Certificate template download should also render the table on page
    certDownloadTemplateBtn.addEventListener('click', function(event) {
        if (!allCertFilled()) {
            event.preventDefault();
            showToast('Warning', 'Please select all filters before downloading the template.', 'warning');
            return;
        }
        updateBulkImportSection();
        fetchCertStudentsForAttendance();
    });
});
</script>
@endpush
