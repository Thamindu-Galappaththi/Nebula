@extends('inc.app')

@section('title', 'NEBULA | Exam Results')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<div class="container-fluid exam-results-page px-2 px-md-3 mt-3 mb-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="text-center mb-4">Exam Result Management</h2>
            <hr>

            <div id="spinner-overlay" class="exam-results-spinner" hidden>
                <div class="lds-ring" aria-hidden="true"><div></div><div></div><div></div><div></div></div>
                <p class="text-white mt-3 mb-0 small">Please wait…</p>
            </div>

            <ul class="nav nav-tabs exam-results-tabs mb-4" id="examResultsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="degree-tab" data-bs-toggle="tab" data-bs-target="#degree-panel" type="button" role="tab">Degree &amp; Diploma</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="certificate-tab" data-bs-toggle="tab" data-bs-target="#certificate-panel" type="button" role="tab">Certificate</button>
                </li>
            </ul>

            <div class="tab-content" id="examResultsTabContent">
                <!-- Degree & Diploma Tab -->
                <div class="tab-pane fade show active" id="degree-panel" role="tabpanel">
                    <div id="exam-filters-bootstrap-degree" class="exam-results-filters mb-4">
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="degree_location" class="col-12 col-md-3 col-lg-2 col-form-label">Location <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select class="form-select degree-filter" id="degree_location" name="location" required>
                                    <option value="" selected disabled>Select a Location</option>
                                    <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                                    <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                                    <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="degree_course_type" class="col-12 col-md-3 col-lg-2 col-form-label">Course Type <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select class="form-select degree-filter" id="degree_course_type" name="course_type" required>
                                    <option value="" selected disabled>Select a Course Type</option>
                                    <option value="degree">Degree Program</option>
                                    <option value="diploma">Diploma Program</option>
                                </select>
                            </div>
                        </div>
                        <div id="degree-fields-container">
                            <div class="row g-2 g-md-3 align-items-md-center mb-3">
                                <label for="degree_course" class="col-12 col-md-3 col-lg-2 col-form-label">Course <span class="text-danger">*</span></label>
                                <div class="col-12 col-md-9 col-lg-10">
                                    <select class="form-select degree-filter" id="degree_course" name="course_id" required>
                                        <option selected disabled value="">Select a Course</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 g-md-3 align-items-md-center mb-3">
                                <label for="degree_intake" class="col-12 col-md-3 col-lg-2 col-form-label">Intake <span class="text-danger">*</span></label>
                                <div class="col-12 col-md-9 col-lg-10">
                                    <select class="form-select degree-filter" id="degree_intake" name="intake_id" required>
                                        <option selected disabled value="">Select an Intake</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 g-md-3 align-items-md-center mb-3" id="degree_specialization_row" hidden>
                                <label for="degree_specialization" class="col-12 col-md-3 col-lg-2 col-form-label">Specialization</label>
                                <div class="col-12 col-md-9 col-lg-10">
                                    <select class="form-select degree-filter" id="degree_specialization" name="specialization" disabled>
                                        <option selected disabled value="">Select a Specialization</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 g-md-3 align-items-md-center mb-3">
                                <label for="degree_semester" class="col-12 col-md-3 col-lg-2 col-form-label">Semester <span class="text-danger">*</span></label>
                                <div class="col-12 col-md-9 col-lg-10">
                                    <select class="form-select degree-filter" id="degree_semester" name="semester" required>
                                        <option selected disabled value="">Select a Semester</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 g-md-3 align-items-md-center mb-3">
                                <label for="degree_module" class="col-12 col-md-3 col-lg-2 col-form-label">Module <span class="text-danger">*</span></label>
                                <div class="col-12 col-md-9 col-lg-10">
                                    <select class="form-select degree-filter" id="degree_module" name="module_id" required>
                                        <option selected disabled value="">Select a Module</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Degree Bulk Upload Section -->
                    <div class="card mb-4" id="degreeBulkUploadSection" hidden>
                        <div class="card-header exam-results-section-header">
                            <h6 class="mb-0">
                                <i class="ti ti-upload me-2"></i>Bulk Upload Exam Results
                            </h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="degreeDownloadTemplateBtn">
                                <i class="ti ti-download me-1"></i>Download Template
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="exam-results-upload-row">
                                <input type="file" class="form-control" id="degreeBulkUploadFile" accept=".csv,.xlsx,.xls">
                                <button type="button" class="btn btn-success exam-results-upload-btn" id="degreeUploadResultsBtn">
                                    <i class="ti ti-upload me-1"></i>Upload Results
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">Select a CSV file with exam results data. Maximum file size: 10MB</small>
                        </div>
                    </div>

                    <!-- Degree Results Table -->
                    <div class="mt-4" id="degreeResultsTableSection" hidden>
                        <h4 id="degreeResultsTableHeader" class="text-center mb-3 exam-results-table-title" hidden></h4>

                        <div id="degreeResultsStatusAlert" class="alert alert-info mb-3" hidden>
                            <i class="ti ti-info-circle"></i>
                            <strong>Exam Results Status:</strong>
                            <span id="degreeResultsStatusText"></span>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Add New Student</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 align-items-start">
                                    <div class="col-12 col-md-4">
                                        <label for="degree_new_student_id" class="form-label">Student ID / NIC</label>
                                        <input type="text" class="form-control" id="degree_new_student_id" placeholder="Enter Student ID or NIC">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="degree_new_student_name" class="form-label">Student Name</label>
                                        <input type="text" class="form-control" id="degree_new_student_name" placeholder="Student Name" readonly>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label d-none d-md-block">&nbsp;</label>
                                        <button type="button" class="btn btn-success w-100" id="degreeAddStudentBtn">
                                            <i class="ti ti-plus"></i> Add Student
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 exam-results-column-actions">
                            <button type="button" class="btn btn-outline-primary" id="degreeAddMarksColumnBtn">
                                <i class="ti ti-plus"></i> Add Marks Column
                            </button>
                            <button type="button" class="btn btn-outline-success" id="degreeAddGradeColumnBtn">
                                <i class="ti ti-plus"></i> Add Grade Column
                            </button>
                            <button type="button" class="btn btn-outline-info" id="degreeAddRemarksColumnBtn">
                                <i class="ti ti-plus"></i> Add Remarks Column
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="degreeRemoveMarksColumnBtn" hidden>
                                <i class="ti ti-minus"></i> Remove Marks Column
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="degreeRemoveGradeColumnBtn" hidden>
                                <i class="ti ti-minus"></i> Remove Grade Column
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="degreeRemoveRemarksColumnBtn" hidden>
                                <i class="ti ti-minus"></i> Remove Remarks Column
                            </button>
                        </div>

                        <div class="exam-results-table-scroll">
                            <table class="table table-bordered" id="degreeResultsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Registration Number</th>
                                        <th>Student Name</th>
                                    </tr>
                                </thead>
                                <tbody id="degreeResultsTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="text-center mt-4" id="degreeSaveAllBtnSection" hidden>
                        <button type="button" id="degreeSaveAllBtn" class="btn btn-primary exam-results-save-btn py-2">Save All Results</button>
                    </div>
                </div>

                <!-- Certificate Tab -->
                <div class="tab-pane fade" id="certificate-panel" role="tabpanel">
                    <div id="exam-filters-bootstrap-cert" class="exam-results-filters mb-4">
                        <div class="row g-2 g-md-3 align-items-md-center mb-3">
                            <label for="cert_location" class="col-12 col-md-3 col-lg-2 col-form-label">Location <span class="text-danger">*</span></label>
                            <div class="col-12 col-md-9 col-lg-10">
                                <select class="form-select cert-filter" id="cert_location" name="location" required>
                                    <option value="" selected disabled>Select a Location</option>
                                    <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                                    <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                                    <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                                </select>
                            </div>
                        </div>
                        <div id="cert-fields-container">
                            <div class="row g-2 g-md-3 align-items-md-center mb-3">
                                <label for="cert_course" class="col-12 col-md-3 col-lg-2 col-form-label">Course <span class="text-danger">*</span></label>
                                <div class="col-12 col-md-9 col-lg-10">
                                    <select class="form-select cert-filter" id="cert_course" name="course_id" required>
                                        <option selected disabled value="">Select a Course</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 g-md-3 align-items-md-center mb-3">
                                <label for="cert_intake" class="col-12 col-md-3 col-lg-2 col-form-label">Intake <span class="text-danger">*</span></label>
                                <div class="col-12 col-md-9 col-lg-10">
                                    <select class="form-select cert-filter" id="cert_intake" name="intake_id" required>
                                        <option selected disabled value="">Select an Intake</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-4">

                    <!-- Certificate Bulk Upload Section -->
                    <div class="card mb-4" id="certBulkUploadSection" hidden>
                        <div class="card-header exam-results-section-header">
                            <h6 class="mb-0">
                                <i class="ti ti-upload me-2"></i>Bulk Upload Exam Results
                            </h6>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="certDownloadTemplateBtn">
                                <i class="ti ti-download me-1"></i>Download Template
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="exam-results-upload-row">
                                <input type="file" class="form-control" id="certBulkUploadFile" accept=".csv,.xlsx,.xls">
                                <button type="button" class="btn btn-success exam-results-upload-btn" id="certUploadResultsBtn">
                                    <i class="ti ti-upload me-1"></i>Upload Results
                                </button>
                            </div>
                            <small class="text-muted d-block mt-2">Select a CSV file with exam results data. Maximum file size: 10MB</small>
                        </div>
                    </div>

                    <div class="mt-4" id="certResultsTableSection" hidden>
                        <h4 id="certResultsTableHeader" class="text-center mb-3 exam-results-table-title" hidden></h4>

                        <div id="certResultsStatusAlert" class="alert alert-info mb-3" hidden>
                            <i class="ti ti-info-circle"></i>
                            <strong>Exam Results Status:</strong>
                            <span id="certResultsStatusText"></span>
                        </div>

                        <div class="card mb-3">
                            <div class="card-header">
                                <h6 class="mb-0">Add New Student</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3 align-items-start">
                                    <div class="col-12 col-md-4">
                                        <label for="cert_new_student_id" class="form-label">Student ID / NIC</label>
                                        <input type="text" class="form-control" id="cert_new_student_id" placeholder="Enter Student ID or NIC">
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label for="cert_new_student_name" class="form-label">Student Name</label>
                                        <input type="text" class="form-control" id="cert_new_student_name" placeholder="Student Name" readonly>
                                    </div>
                                    <div class="col-12 col-md-4">
                                        <label class="form-label d-none d-md-block">&nbsp;</label>
                                        <button type="button" class="btn btn-success w-100" id="certAddStudentBtn">
                                            <i class="ti ti-plus"></i> Add Student
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3 exam-results-column-actions">
                            <button type="button" class="btn btn-outline-primary" id="certAddMarksColumnBtn">
                                <i class="ti ti-plus"></i> Add Marks Column
                            </button>
                            <button type="button" class="btn btn-outline-success" id="certAddGradeColumnBtn">
                                <i class="ti ti-plus"></i> Add Grade Column
                            </button>
                            <button type="button" class="btn btn-outline-info" id="certAddRemarksColumnBtn">
                                <i class="ti ti-plus"></i> Add Remarks Column
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="certRemoveMarksColumnBtn" hidden>
                                <i class="ti ti-minus"></i> Remove Marks Column
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="certRemoveGradeColumnBtn" hidden>
                                <i class="ti ti-minus"></i> Remove Grade Column
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="certRemoveRemarksColumnBtn" hidden>
                                <i class="ti ti-minus"></i> Remove Remarks Column
                            </button>
                        </div>

                        <div class="exam-results-table-scroll">
                            <table class="table table-bordered" id="certResultsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Registration Number</th>
                                        <th>Student Name</th>
                                    </tr>
                                </thead>
                                <tbody id="certResultsTableBody">
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="text-center mt-4" id="certSaveAllBtnSection" hidden>
                        <button type="button" id="certSaveAllBtn" class="btn btn-primary exam-results-save-btn py-2">Save All Results</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function() {
    let degreeResults = [];
    let certResults = [];
    let degreeSpecializationsLoaded = false;

    // Degree Tab Elements
    const degreeLocation = document.getElementById('degree_location');
    const degreeCourseType = document.getElementById('degree_course_type');
    const degreeCourse = document.getElementById('degree_course');
    const degreeIntake = document.getElementById('degree_intake');
    const degreeSpecialization = document.getElementById('degree_specialization');
    const degreeSpecializationRow = document.getElementById('degree_specialization_row');
    const degreeSemester = document.getElementById('degree_semester');
    const degreeModule = document.getElementById('degree_module');

    // Certificate Tab Elements
    const certLocation = document.getElementById('cert_location');
    const certCourse = document.getElementById('cert_course');
    const certIntake = document.getElementById('cert_intake');

    // Degree-specific elements
    const degreeAddStudentBtn = document.getElementById('degreeAddStudentBtn');
    const degreeResultsTableBody = document.getElementById('degreeResultsTableBody');
    const degreeSaveAllBtn = document.getElementById('degreeSaveAllBtn');
    const degreeResultsTableHeader = document.getElementById('degreeResultsTableHeader');
    const degreeSaveAllBtnSection = document.getElementById('degreeSaveAllBtnSection');
    const degreeAddMarksColumnBtn = document.getElementById('degreeAddMarksColumnBtn');
    const degreeAddGradeColumnBtn = document.getElementById('degreeAddGradeColumnBtn');
    const degreeAddRemarksColumnBtn = document.getElementById('degreeAddRemarksColumnBtn');
    const degreeRemoveMarksColumnBtn = document.getElementById('degreeRemoveMarksColumnBtn');
    const degreeRemoveGradeColumnBtn = document.getElementById('degreeRemoveGradeColumnBtn');
    const degreeRemoveRemarksColumnBtn = document.getElementById('degreeRemoveRemarksColumnBtn');

    // Certificate-specific elements
    const certAddStudentBtn = document.getElementById('certAddStudentBtn');
    const certResultsTableBody = document.getElementById('certResultsTableBody');
    const certSaveAllBtn = document.getElementById('certSaveAllBtn');
    const certResultsTableHeader = document.getElementById('certResultsTableHeader');
    const certSaveAllBtnSection = document.getElementById('certSaveAllBtnSection');
    const certAddMarksColumnBtn = document.getElementById('certAddMarksColumnBtn');
    const certAddGradeColumnBtn = document.getElementById('certAddGradeColumnBtn');
    const certAddRemarksColumnBtn = document.getElementById('certAddRemarksColumnBtn');
    const certRemoveMarksColumnBtn = document.getElementById('certRemoveMarksColumnBtn');
    const certRemoveGradeColumnBtn = document.getElementById('certRemoveGradeColumnBtn');
    const certRemoveRemarksColumnBtn = document.getElementById('certRemoveRemarksColumnBtn');

    // Tab panes already hide inactive content. Do not force display:none on
    // the other tab's sections or results disappear after switching back.

    function setHidden(el, hidden) {
        if (!el) return;
        el.hidden = !!hidden;
    }

    function columnHeaderId(type) {
        return getActiveTab() + '-' + type + 'ColumnHeader';
    }

    function getColumnHeader(type) {
        return document.getElementById(columnHeaderId(type));
    }

    function notify(icon, title, text) {
        if (typeof Swal === 'undefined') {
            return Promise.resolve();
        }
        const isToast = icon === 'success' || icon === 'info';
        return Swal.fire({
            icon,
            title,
            text,
            toast: isToast,
            position: isToast ? 'top-end' : 'center',
            timer: isToast ? 2500 : undefined,
            timerProgressBar: isToast,
            showConfirmButton: !isToast,
            confirmButtonColor: '#0d6efd',
        });
    }

    function showSpinner(show) {
        const overlay = document.getElementById('spinner-overlay');
        if (!overlay) return;
        overlay.hidden = !show;
    }

    function showToast(title, message, type) {
        const icon = type === 'bg-danger' || type === 'error' ? 'error'
            : (type === 'bg-warning' || type === 'warning' ? 'warning'
            : (type === 'success' || type === '#ccffcc' ? 'success' : 'info'));
        return notify(icon, title, String(message || '').replace(/<br\s*\/?>/gi, '\n'));
    }

    // Helper functions
    function getActiveTab() {
        const degreePanel = document.getElementById('degree-panel');
        return degreePanel.classList.contains('active') && degreePanel.classList.contains('show') ? 'degree' : 'certificate';
    }

    function getCurrentResults() {
        return getActiveTab() === 'degree' ? degreeResults : certResults;
    }

    function setCurrentResults(results) {
        if (getActiveTab() === 'degree') {
            degreeResults = results;
        } else {
            certResults = results;
        }
    }

    function getTabElements() {
        const activeTab = getActiveTab();
        if (activeTab === 'degree') {
            return {
                resultsTableBody: degreeResultsTableBody,
                addMarksColumnBtn: degreeAddMarksColumnBtn,
                addGradeColumnBtn: degreeAddGradeColumnBtn,
                addRemarksColumnBtn: degreeAddRemarksColumnBtn,
                removeMarksColumnBtn: degreeRemoveMarksColumnBtn,
                removeGradeColumnBtn: degreeRemoveGradeColumnBtn,
                removeRemarksColumnBtn: degreeRemoveRemarksColumnBtn,
                resultsTable: document.getElementById('degreeResultsTable'),
                addStudentBtn: degreeAddStudentBtn,
                saveAllBtn: degreeSaveAllBtn,
                resultsTableHeader: degreeResultsTableHeader
            };
        } else {
            return {
                resultsTableBody: certResultsTableBody,
                addMarksColumnBtn: certAddMarksColumnBtn,
                addGradeColumnBtn: certAddGradeColumnBtn,
                addRemarksColumnBtn: certAddRemarksColumnBtn,
                removeMarksColumnBtn: certRemoveMarksColumnBtn,
                removeGradeColumnBtn: certRemoveGradeColumnBtn,
                removeRemarksColumnBtn: certRemoveRemarksColumnBtn,
                resultsTable: document.getElementById('certResultsTable'),
                addStudentBtn: certAddStudentBtn,
                saveAllBtn: certSaveAllBtn,
                resultsTableHeader: certResultsTableHeader
            };
        }
    }

    function resetTableStructure() {
        const { resultsTableBody, addMarksColumnBtn, addGradeColumnBtn, addRemarksColumnBtn,
                removeMarksColumnBtn, removeGradeColumnBtn, removeRemarksColumnBtn } = getTabElements();

        resultsTableBody.replaceChildren();

        ['marks', 'grade', 'remarks'].forEach(type => {
            const header = getColumnHeader(type);
            if (header) header.remove();
        });

        setHidden(addMarksColumnBtn, false);
        setHidden(addGradeColumnBtn, false);
        setHidden(addRemarksColumnBtn, false);
        setHidden(removeMarksColumnBtn, true);
        setHidden(removeGradeColumnBtn, true);
        setHidden(removeRemarksColumnBtn, true);
    }

    resetTableStructure();

    function ensureTwoColumns() {
        const { resultsTableBody, addMarksColumnBtn, addGradeColumnBtn, addRemarksColumnBtn,
                removeMarksColumnBtn, removeGradeColumnBtn, removeRemarksColumnBtn, resultsTable } = getTabElements();

        resultsTableBody.replaceChildren();

        const tableHeader = resultsTable.querySelector('thead tr');
        if (tableHeader) {
            Array.from(tableHeader.querySelectorAll('th')).slice(2).forEach(th => th.remove());
        }

        setHidden(addMarksColumnBtn, false);
        setHidden(addGradeColumnBtn, false);
        setHidden(addRemarksColumnBtn, false);
        setHidden(removeMarksColumnBtn, true);
        setHidden(removeGradeColumnBtn, true);
        setHidden(removeRemarksColumnBtn, true);
    }

    ensureTwoColumns();

    // Helper to reset and disable dropdowns
    function resetAndDisable(select, placeholder) {
        if (!select) return;
        select.innerHTML = `<option selected disabled value="">${placeholder}</option>`;
        select.disabled = true;
    }

    function explainEmptyOptions(select, message) {
        resetAndDisable(select, message);
        showToast('Info', message, 'info');
    }

    function optionCount(items, valueKey, textKey) {
        return (items || []).filter(item => item && item[valueKey] && item[textKey]).length;
    }

    function resetSpecialization() {
        if (!degreeSpecialization || !degreeSpecializationRow) {
            return;
        }

        degreeSpecializationsLoaded = false;
        degreeSpecialization.innerHTML = '<option selected disabled value="">Select a Specialization</option>';
        degreeSpecialization.disabled = true;
        setHidden(degreeSpecializationRow, true);
    }

    function hasDegreeSpecializationSelection() {
        return degreeSpecializationsLoaded;
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
                    degreeSpecialization.add(new Option('All', ''));
                    specializations.forEach(spec => {
                        const label = String(spec || '').trim();
                        if (label && label.toLowerCase() !== 'all') {
                            degreeSpecialization.add(new Option(label, label));
                        }
                    });
                    degreeSpecialization.value = '';
                    degreeSpecialization.disabled = false;
                    setHidden(degreeSpecializationRow, false);
                    degreeSpecializationsLoaded = true;
                } else {
                    resetSpecialization();
                    degreeSpecializationsLoaded = true;
                }

                if (allDegreeFilled()) {
                    fetchDegreeStudentsForResultEntry();
                }
            })
            .catch(() => {
                resetSpecialization();
                degreeSpecializationsLoaded = true;
            })
            .finally(() => showSpinner(false));
    }

    // DEGREE TAB EVENT LISTENERS
    degreeLocation.addEventListener('change', function() {
        resetAndDisable(degreeCourse, 'Select a Course');
        resetAndDisable(degreeIntake, 'Select an Intake');
        resetSpecialization();
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        if (degreeLocation.value && degreeCourseType.value) {
            fetchDegreeCourses(degreeLocation.value, degreeCourseType.value);
        }
    });

    degreeCourseType.addEventListener('change', function() {
        resetAndDisable(degreeCourse, 'Select a Course');
        resetAndDisable(degreeIntake, 'Select an Intake');
        resetSpecialization();
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        if (degreeLocation.value && this.value) {
            fetchDegreeCourses(degreeLocation.value, this.value);
        }
    });

    degreeCourse.addEventListener('change', function() {
        resetAndDisable(degreeIntake, 'Select an Intake');
        resetSpecialization();
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        if (degreeCourse.value && degreeLocation.value) {
            degreeIntake.disabled = false;
            fetchDegreeSpecializations();
            handleDegreeIntakeFetch();
        }
    });

    degreeIntake.addEventListener('change', function() {
        resetAndDisable(degreeSemester, 'Select a Semester');
        resetAndDisable(degreeModule, 'Select a Module');
        if (degreeIntake.value && degreeCourse.value) {
            fetchDegreeSemesters(degreeCourse.value, degreeIntake.value);
        }
    });

    degreeSemester.addEventListener('change', function() {
        resetAndDisable(degreeModule, 'Select a Module');
        if (degreeSemester.value && degreeIntake.value && degreeCourse.value && degreeLocation.value) {
            degreeModule.disabled = false;
            handleDegreeModuleFetch();
        }
    });

    degreeModule.addEventListener('change', function() {
        ensureTwoColumns();
        setHidden(degreeSaveAllBtnSection, true);
        if (allDegreeFilled()) {
            fetchDegreeStudentsForResultEntry();
        }
        updateDegreeResultsHeader();
    });

    degreeSpecialization.addEventListener('change', function() {
        const previousModuleId = degreeModule.value;
        ensureTwoColumns();
        setHidden(degreeSaveAllBtnSection, true);
        if (degreeSemester.value && degreeIntake.value && degreeCourse.value && degreeLocation.value) {
            degreeModule.disabled = false;
            handleDegreeModuleFetch(previousModuleId);
            return;
        }
        updateDegreeResultsHeader();
    });

    // CERTIFICATE TAB EVENT LISTENERS
    certLocation.addEventListener('change', function() {
        resetAndDisable(certCourse, 'Select a Course');
        resetAndDisable(certIntake, 'Select an Intake');
        if (certLocation.value) {
            fetchCertCourses(certLocation.value);
        }
    });

    certCourse.addEventListener('change', function() {
        resetAndDisable(certIntake, 'Select an Intake');
        if (certCourse.value && certLocation.value) {
            certIntake.disabled = false;
            handleCertIntakeFetch();
        }
    });

    certIntake.addEventListener('change', function() {
        ensureTwoColumns();
        setHidden(certSaveAllBtnSection, true);
        if (allCertFilled()) {
            fetchCertStudentsForResultEntry();
        }
        updateCertResultsHeader();
    });

    // Validation functions
    function allDegreeFilled() {
        return degreeLocation.value && degreeCourseType.value && degreeCourse.value &&
               degreeIntake.value && hasDegreeSpecializationSelection() && degreeSemester.value && degreeModule.value;
    }

    function allCertFilled() {
        return certLocation.value && certCourse.value && certIntake.value;
    }

    // Helper to populate dropdowns
    function populateDropdown(select, items, valueKey, textKey, defaultText) {
        if (!select) return;
        select.innerHTML = `<option selected disabled value="">Select ${defaultText}</option>`;
        (items || []).forEach(item => {
            const value = item[valueKey];
            let displayText = item[textKey];
            if (displayText && value) {
                select.add(new Option(displayText, value));
            }
        });
    }

    // Fetch functions for Degree tab
    function fetchDegreeCourses(location, courseType) {
        showSpinner(true);
        fetch(`/exam-results/get-courses-by-location?location=${encodeURIComponent(location)}&course_type=${encodeURIComponent(courseType)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.courses) {
                    populateDropdown(degreeCourse, data.courses, 'course_id', 'course_name', 'Course');
                    degreeCourse.disabled = false;
                } else {
                    resetAndDisable(degreeCourse, 'Select a Course');
                }
            })
            .catch(() => resetAndDisable(degreeCourse, 'Select a Course'))
            .finally(() => showSpinner(false));
    }

    function handleDegreeIntakeFetch() {
        showSpinner(true);
        fetch(`/exam-results/get-intakes/${degreeCourse.value}/${degreeLocation.value}`)
            .then(response => response.json())
            .then(data => {
                const intakes = !data.error && Array.isArray(data.intakes) ? data.intakes : [];
                if (optionCount(intakes, 'intake_id', 'batch') > 0) {
                    populateDropdown(degreeIntake, intakes, 'intake_id', 'batch', 'Intake');
                    degreeIntake.disabled = false;
                    return;
                }
                explainEmptyOptions(degreeIntake, 'No intakes are included for this course and location.');
            })
            .catch(() => explainEmptyOptions(degreeIntake, 'Unable to load intakes for this course and location.'))
            .finally(() => showSpinner(false));
    }

    function fetchDegreeSemesters(courseId, intakeId) {
        showSpinner(true);
        fetch(`/exam-results/get-semesters?course_id=${encodeURIComponent(courseId)}&intake_id=${encodeURIComponent(intakeId)}`)
            .then(response => response.json())
            .then(data => {
                const semesters = Array.isArray(data.semesters) ? data.semesters : [];
                if (optionCount(semesters, 'id', 'display_name') > 0) {
                    populateDropdown(degreeSemester, semesters, 'id', 'display_name', 'Semester');
                    degreeSemester.disabled = false;
                    return;
                }
                explainEmptyOptions(degreeSemester, 'No semesters are included for this course and intake.');
            })
            .catch(() => explainEmptyOptions(degreeSemester, 'Unable to load semesters for this course and intake.'))
            .finally(() => showSpinner(false));
    }

    function handleDegreeModuleFetch(restoreModuleId) {
        const data = {
            location: degreeLocation.value,
            course_id: degreeCourse.value,
            intake_id: degreeIntake.value,
            semester: degreeSemester.value,
            specialization: degreeSpecialization.value || ''
        };
        showSpinner(true);
        fetch('{{ route("exam.results.get.filtered.modules") }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            const modules = Array.isArray(data.modules) ? data.modules : [];
            if (optionCount(modules, 'module_id', 'module_name') > 0) {
                populateDropdown(degreeModule, modules, 'module_id', 'module_name', 'Module');
                degreeModule.disabled = false;
                if (restoreModuleId && Array.from(degreeModule.options).some(option => option.value === String(restoreModuleId))) {
                    degreeModule.value = restoreModuleId;
                    degreeModule.dispatchEvent(new Event('change', { bubbles: true }));
                    return;
                }
                updateDegreeResultsHeader();
                return;
            }
            const context = degreeSpecialization.value
                ? 'semester and specialization'
                : 'semester';
            explainEmptyOptions(degreeModule, `No modules are included for this ${context}.`);
        })
        .catch(() => explainEmptyOptions(degreeModule, 'Unable to load modules for this semester.'))
        .finally(() => showSpinner(false));
    }

    // Fetch functions for Certificate tab
    function fetchCertCourses(location) {
        showSpinner(true);
        fetch(`/exam-results/get-courses-by-location?location=${encodeURIComponent(location)}&course_type=certificate`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.courses) {
                    populateDropdown(certCourse, data.courses, 'course_id', 'course_name', 'Course');
                    certCourse.disabled = false;
                } else {
                    resetAndDisable(certCourse, 'Select a Course');
                }
            })
            .catch(() => resetAndDisable(certCourse, 'Select a Course'))
            .finally(() => showSpinner(false));
    }

    function handleCertIntakeFetch() {
        showSpinner(true);
        fetch(`/exam-results/get-intakes/${certCourse.value}/${certLocation.value}`)
            .then(response => response.json())
            .then(data => {
                const intakes = !data.error && Array.isArray(data.intakes) ? data.intakes : [];
                if (optionCount(intakes, 'intake_id', 'batch') > 0) {
                    populateDropdown(certIntake, intakes, 'intake_id', 'batch', 'Intake');
                    certIntake.disabled = false;
                    return;
                }
                explainEmptyOptions(certIntake, 'No intakes are included for this certificate course and location.');
            })
            .catch(() => explainEmptyOptions(certIntake, 'Unable to load intakes for this certificate course and location.'))
            .finally(() => showSpinner(false));
    }

    // Result header functions
    function updateDegreeResultsHeader() {
        const courseName = degreeCourse.options[degreeCourse.selectedIndex].text;
        const moduleName = degreeModule.options[degreeModule.selectedIndex].text;
        const semesterName = degreeSemester.options[degreeSemester.selectedIndex].text;
        degreeResultsTableHeader.textContent = `Exam Results for: ${courseName} - ${semesterName} (${moduleName})`;
        setHidden(degreeResultsTableHeader, false);
    }

    function updateCertResultsHeader() {
        const courseName = certCourse.options[certCourse.selectedIndex].text;
        const intakeName = certIntake.options[certIntake.selectedIndex].text;
        certResultsTableHeader.textContent = `Exam Results for: ${courseName} - ${intakeName}`;
        setHidden(certResultsTableHeader, false);
    }



    // Column management event listeners
    degreeAddMarksColumnBtn.addEventListener('click', function() {
        const { resultsTable, addMarksColumnBtn, removeMarksColumnBtn } = getTabElements();
        const tableHeader = resultsTable.querySelector('thead tr');
        const marksHeader = document.createElement('th');
        marksHeader.id = columnHeaderId('marks');
        marksHeader.textContent = 'Marks';
        tableHeader.appendChild(marksHeader);

        setHidden(addMarksColumnBtn, true);
        setHidden(removeMarksColumnBtn, false);

        updateExistingRows();
    });

    certAddMarksColumnBtn.addEventListener('click', function() {
        const { resultsTable, addMarksColumnBtn, removeMarksColumnBtn } = getTabElements();
        const tableHeader = resultsTable.querySelector('thead tr');
        const marksHeader = document.createElement('th');
        marksHeader.id = columnHeaderId('marks');
        marksHeader.textContent = 'Marks';
        tableHeader.appendChild(marksHeader);

        setHidden(addMarksColumnBtn, true);
        setHidden(removeMarksColumnBtn, false);

        updateExistingRows();
    });

    degreeRemoveMarksColumnBtn.addEventListener('click', function() {
        const { addMarksColumnBtn, removeMarksColumnBtn } = getTabElements();
        const marksHeader = getColumnHeader('marks');
        if (marksHeader) marksHeader.remove();

        setHidden(addMarksColumnBtn, false);
        setHidden(removeMarksColumnBtn, true);

        updateExistingRows();
    });

    certRemoveMarksColumnBtn.addEventListener('click', function() {
        const { addMarksColumnBtn, removeMarksColumnBtn } = getTabElements();
        const marksHeader = getColumnHeader('marks');
        if (marksHeader) marksHeader.remove();

        setHidden(addMarksColumnBtn, false);
        setHidden(removeMarksColumnBtn, true);

        updateExistingRows();
    });

    degreeAddGradeColumnBtn.addEventListener('click', function() {
        const { resultsTable, addGradeColumnBtn, removeGradeColumnBtn } = getTabElements();
        const tableHeader = resultsTable.querySelector('thead tr');
        const gradeHeader = document.createElement('th');
        gradeHeader.id = columnHeaderId('grade');
        gradeHeader.textContent = 'Grade';
        tableHeader.appendChild(gradeHeader);

        setHidden(addGradeColumnBtn, true);
        setHidden(removeGradeColumnBtn, false);

        updateExistingRows();
    });

    certAddGradeColumnBtn.addEventListener('click', function() {
        const { resultsTable, addGradeColumnBtn, removeGradeColumnBtn } = getTabElements();
        const tableHeader = resultsTable.querySelector('thead tr');
        const gradeHeader = document.createElement('th');
        gradeHeader.id = columnHeaderId('grade');
        gradeHeader.textContent = 'Grade';
        tableHeader.appendChild(gradeHeader);

        setHidden(addGradeColumnBtn, true);
        setHidden(removeGradeColumnBtn, false);

        updateExistingRows();
    });

    degreeRemoveGradeColumnBtn.addEventListener('click', function() {
        const { addGradeColumnBtn, removeGradeColumnBtn } = getTabElements();
        const gradeHeader = getColumnHeader('grade');
        if (gradeHeader) gradeHeader.remove();

        setHidden(addGradeColumnBtn, false);
        setHidden(removeGradeColumnBtn, true);

        updateExistingRows();
    });

    certRemoveGradeColumnBtn.addEventListener('click', function() {
        const { addGradeColumnBtn, removeGradeColumnBtn } = getTabElements();
        const gradeHeader = getColumnHeader('grade');
        if (gradeHeader) gradeHeader.remove();

        setHidden(addGradeColumnBtn, false);
        setHidden(removeGradeColumnBtn, true);

        updateExistingRows();
    });

    // Add Remarks Column Event Handler
    degreeAddRemarksColumnBtn.addEventListener('click', function() {
        const { resultsTable, addRemarksColumnBtn, removeRemarksColumnBtn } = getTabElements();
        const tableHeader = resultsTable.querySelector('thead tr');
        const remarksHeader = document.createElement('th');
        remarksHeader.id = columnHeaderId('remarks');
        remarksHeader.textContent = 'Remarks';
        tableHeader.appendChild(remarksHeader);

        setHidden(addRemarksColumnBtn, true);
        setHidden(removeRemarksColumnBtn, false);

        updateExistingRows();
    });

    certAddRemarksColumnBtn.addEventListener('click', function() {
        const { resultsTable, addRemarksColumnBtn, removeRemarksColumnBtn } = getTabElements();
        const tableHeader = resultsTable.querySelector('thead tr');
        const remarksHeader = document.createElement('th');
        remarksHeader.id = columnHeaderId('remarks');
        remarksHeader.textContent = 'Remarks';
        tableHeader.appendChild(remarksHeader);

        setHidden(addRemarksColumnBtn, true);
        setHidden(removeRemarksColumnBtn, false);

        updateExistingRows();
    });

    // Remove Remarks Column Event Handler
    degreeRemoveRemarksColumnBtn.addEventListener('click', function() {
        const { addRemarksColumnBtn, removeRemarksColumnBtn } = getTabElements();
        const remarksHeader = getColumnHeader('remarks');
        if (remarksHeader) remarksHeader.remove();

        setHidden(addRemarksColumnBtn, false);
        setHidden(removeRemarksColumnBtn, true);

        updateExistingRows();
    });

    certRemoveRemarksColumnBtn.addEventListener('click', function() {
        const { addRemarksColumnBtn, removeRemarksColumnBtn } = getTabElements();
        const remarksHeader = getColumnHeader('remarks');
        if (remarksHeader) remarksHeader.remove();

        setHidden(addRemarksColumnBtn, false);
        setHidden(removeRemarksColumnBtn, true);

        updateExistingRows();
    });

    // Function to update existing rows when columns are added/removed
    function appendInputCell(row, className, input, onChange) {
        const cell = document.createElement('td');
        cell.className = className;
        input.className = 'form-control';
        input.addEventListener('change', function () {
            onChange(this.value);
        });
        cell.appendChild(input);
        row.appendChild(cell);
    }

    function appendResultRow(tbody, result, index) {
        const row = document.createElement('tr');
        const idCell = document.createElement('td');
        idCell.textContent = result.registration_id || '';
        const nameCell = document.createElement('td');
        nameCell.textContent = result.name || '';
        row.appendChild(idCell);
        row.appendChild(nameCell);

        if (getColumnHeader('marks')) {
            const input = document.createElement('input');
            input.type = 'number';
            input.min = '0';
            input.max = '100';
            input.step = '0.01';
            input.placeholder = 'Marks';
            input.value = result.marks ?? '';
            appendInputCell(row, 'marks-cell', input, value => updateResultMark(index, value));
        }

        if (getColumnHeader('grade')) {
            const input = document.createElement('input');
            input.type = 'text';
            input.maxLength = 5;
            input.placeholder = 'Grade';
            input.value = result.grade ?? '';
            appendInputCell(row, 'grade-cell', input, value => updateResultGrade(index, value));
        }

        if (getColumnHeader('remarks')) {
            const input = document.createElement('input');
            input.type = 'text';
            input.maxLength = 255;
            input.placeholder = 'Remarks';
            input.value = result.remarks ?? '';
            appendInputCell(row, 'remarks-cell', input, value => updateResultRemarks(index, value));
        }

        tbody.appendChild(row);
    }

    function updateExistingRows() {
        const { resultsTableBody } = getTabElements();
        const results = getCurrentResults();
        resultsTableBody.replaceChildren();
        if (!results.length) {
            const empty = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 2;
            cell.className = 'text-center';
            cell.textContent = 'No students found for these filters.';
            empty.appendChild(cell);
            resultsTableBody.appendChild(empty);
            return;
        }
        results.forEach((result, index) => appendResultRow(resultsTableBody, result, index));
    }

    function renderTable() {
        updateExistingRows();
    }

    function renderEditableResultsTable(students) {
        const results = students.map(s => ({
            registration_id: s.registration_id || s.registration_number || s.student_id,
            student_id: s.student_id,
            name: s.name || s.name_with_initials,
            marks: s.marks || '',
            grade: s.grade || '',
            remarks: s.remarks || ''
        }));
        setCurrentResults(results);
        updateExistingRows();
    }

    function setResultsStatus(prefix, exists) {
        const alertEl = document.getElementById(prefix + 'ResultsStatusAlert');
        const textEl = document.getElementById(prefix + 'ResultsStatusText');
        if (!alertEl || !textEl) return;
        if (exists) {
            textEl.textContent = ' Results already exist for this selection. Saving will update existing records.';
            setHidden(alertEl, false);
        } else {
            textEl.textContent = ' No saved results yet for this selection.';
            setHidden(alertEl, false);
        }
    }







    // Event listeners
    degreeAddStudentBtn.addEventListener('click', handleAddStudent);
    certAddStudentBtn.addEventListener('click', handleAddStudent);
    degreeSaveAllBtn.addEventListener('click', handleSaveAll);
    certSaveAllBtn.addEventListener('click', handleSaveAll);

    let studentLookupTimer = null;
    let studentLookupAbort = null;

    function getStudentLookupFields() {
        const activeTab = getActiveTab();
        return {
            idInput: document.getElementById(activeTab === 'degree' ? 'degree_new_student_id' : 'cert_new_student_id'),
            nameInput: document.getElementById(activeTab === 'degree' ? 'degree_new_student_name' : 'cert_new_student_name')
        };
    }

    function lookupExamStudent(studentId, { fillName = true, signal = null } = {}) {
        const { nameInput } = getStudentLookupFields();
        const lookup = String(studentId || '').trim();
        if (!lookup) {
            if (fillName && nameInput) {
                nameInput.value = '';
            }
            return Promise.resolve(null);
        }

        const context = getAddStudentContext();
        const request = {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                student_id: lookup,
                course_id: context.course_id,
                intake_id: context.intake_id,
                location: context.location
            })
        };
        if (signal) {
            request.signal = signal;
        }

        return fetch('{{ route("get.student.name") }}', request)
        .then(response => response.json())
        .then(data => {
            if (data && data.success) {
                if (fillName && nameInput) {
                    nameInput.value = data.name || '';
                }
                return data;
            }
            if (fillName && nameInput) {
                nameInput.value = '';
            }
            return data || { success: false };
        })
        .catch(error => {
            if (error.name === 'AbortError') {
                return null;
            }
            if (fillName && nameInput) {
                nameInput.value = '';
            }
            return { success: false };
        });
    }

    function scheduleStudentNameLookup(value) {
        clearTimeout(studentLookupTimer);
        const lookup = String(value || '').trim();
        if (!lookup) {
            lookupExamStudent('');
            return;
        }
        studentLookupTimer = setTimeout(function () {
            if (studentLookupAbort) {
                studentLookupAbort.abort();
            }
            studentLookupAbort = new AbortController();
            lookupExamStudent(lookup, { fillName: true, signal: studentLookupAbort.signal });
        }, 300);
    }

    ['degree_new_student_id', 'cert_new_student_id'].forEach(function (fieldId) {
        const input = document.getElementById(fieldId);
        if (!input) {
            return;
        }
        input.addEventListener('input', function () {
            scheduleStudentNameLookup(this.value);
        });
        input.addEventListener('blur', function () {
            clearTimeout(studentLookupTimer);
            if (studentLookupAbort) {
                studentLookupAbort.abort();
            }
            studentLookupAbort = new AbortController();
            lookupExamStudent(this.value.trim(), { fillName: true, signal: studentLookupAbort.signal });
        });
    });

    // Bulk upload elements
    const degreeDownloadTemplateBtn = document.getElementById('degreeDownloadTemplateBtn');
    const degreeUploadResultsBtn = document.getElementById('degreeUploadResultsBtn');
    const degreeBulkUploadFile = document.getElementById('degreeBulkUploadFile');

    const certDownloadTemplateBtn = document.getElementById('certDownloadTemplateBtn');
    const certUploadResultsBtn = document.getElementById('certUploadResultsBtn');
    const certBulkUploadFile = document.getElementById('certBulkUploadFile');

    // Bulk upload event listeners
    degreeDownloadTemplateBtn.addEventListener('click', handleDownloadTemplate);
    degreeUploadResultsBtn.addEventListener('click', handleBulkUpload);
    certDownloadTemplateBtn.addEventListener('click', handleDownloadTemplate);
    certUploadResultsBtn.addEventListener('click', handleBulkUpload);

    function handleAddStudent() {
        const activeTab = getActiveTab();
        const studentIdField = activeTab === 'degree' ? 'degree_new_student_id' : 'cert_new_student_id';
        const studentId = document.getElementById(studentIdField).value.trim();
        const results = getCurrentResults();

        // Validate required fields
        if (!studentId) {
            showToast('Warning', 'Please enter Student ID or NIC.', 'warning');
            return;
        }

        if (results.some(r => String(r.student_id) === studentId || String(r.registration_id) === studentId)) {
            showToast('Warning', 'This student has already been added.', 'warning');
            return;
        }

        clearTimeout(studentLookupTimer);
        if (studentLookupAbort) {
            studentLookupAbort.abort();
        }
        showSpinner(true);
        lookupExamStudent(studentId, { fillName: true })
        .then(data => {
            if (!data) {
                return;
            }
            if (data.success) {
                const results = getCurrentResults();
                const resolvedId = data.student_id || studentId;
                if (results.some(r => String(r.student_id) === String(resolvedId))) {
                    showToast('Warning', 'This student has already been added.', 'warning');
                    return;
                }
                const studentData = {
                    student_id: resolvedId,
                    registration_id: data.registration_id || '',
                    name: data.name
                };
                results.push(studentData);
                setCurrentResults(results);
                renderTable();
                clearInputFields();
            } else {
                showToast('Error', data.message || 'Could not find student.', 'bg-danger');
            }
        })
        .catch(() => showToast('Error', 'An error occurred while fetching student details.', 'bg-danger'))
        .finally(() => showSpinner(false));
    }

    function handleSaveAll() {
        const filterData = getFilterData();
        const results = getCurrentResults();
        if (!filterData || results.length === 0) {
            showToast('Warning', 'Please select all filters and add at least one student result.', 'bg-warning');
            return;
        }

        // Filter out empty values and ensure at least one field is filled
        const filteredResults = results.map(result => {
            const filtered = { student_id: result.student_id };
            if (result.marks !== '' && result.marks !== null) {
                filtered.marks = result.marks;
            }
            if (result.grade !== '' && result.grade !== null) {
                filtered.grade = result.grade;
            }
            if (result.remarks !== '' && result.remarks !== null) {
                filtered.remarks = result.remarks;
            }
            return filtered;
        }).filter(result => result.marks !== undefined || result.grade !== undefined || result.remarks !== undefined);

        if (filteredResults.length === 0) {
            showToast('Warning', 'Please enter at least marks, grade, or remarks for at least one student.', 'bg-warning');
            return;
        }

        const payload = { ...filterData, results: filteredResults };

        showSpinner(true);
        fetch('{{ route("store.result") }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify(payload)
        })
        .then(async response => {
            const data = await response.json().catch(() => null);

            if (!response.ok) {
                let errorMsg = data?.message || `HTTP error! status: ${response.status}`;
                if (data?.errors) {
                    errorMsg = Object.values(data.errors).flat().join(' ');
                }
                throw new Error(errorMsg);
            }

            return data;
        })
        .then(data => {
            if (data.success) {
                showToast('Success', data.message, 'success');
                setTimeout(function() {
                    location.reload();
                }, 1500);
                setCurrentResults([]);
                renderTable();
                const activeTab = getActiveTab();
                const studentIdField = activeTab === 'degree' ? 'degree_new_student_id' : 'cert_new_student_id';
                const studentNameField = activeTab === 'degree' ? 'degree_new_student_name' : 'cert_new_student_name';
                document.getElementById(studentIdField).value = '';
                document.getElementById(studentNameField).value = '';
                updateResultsHeader();
            } else {
                let errorMsg = data.message || 'An error occurred.';
                if(data.errors) {
                    errorMsg = Object.values(data.errors).flat().join(' ');
                }
                showToast('Error', errorMsg, 'error');
            }
        })
        .catch(error => {
            showToast('Error', error.message || 'An error occurred while saving results.', 'error');
        })
        .finally(() => showSpinner(false));
    }

    function getAddStudentContext() {
        const activeTab = getActiveTab();
        if (activeTab === 'degree') {
            return {
                location: degreeLocation.value,
                course_id: degreeCourse.value,
                intake_id: degreeIntake.value
            };
        }

        return {
            location: certLocation.value,
            course_id: certCourse.value,
            intake_id: certIntake.value
        };
    }

    function getFilterData() {
        const activeTab = getActiveTab();
        if (activeTab === 'degree') {
            const data = {
                location: degreeLocation.value,
                course_type: degreeCourseType.value,
                course_id: degreeCourse.value,
                intake_id: degreeIntake.value,
                semester: degreeSemester.value,
                module_id: degreeModule.value,
                specialization: degreeSpecialization.value || ''
            };
            if (!data.location || !data.course_type || !data.course_id || !data.intake_id || !data.semester || !data.module_id) {
                return null;
            }
            if (!hasDegreeSpecializationSelection()) {
                return null;
            }
            return data;
        }

        const data = {
            location: certLocation.value,
            course_type: 'certificate',
            course_id: certCourse.value,
            intake_id: certIntake.value
        };
        return Object.values(data).some(v => !v) ? null : data;
    }

    function clearInputFields() {
        const activeTab = getActiveTab();
        const studentIdField = activeTab === 'degree' ? 'degree_new_student_id' : 'cert_new_student_id';
        const studentNameField = activeTab === 'degree' ? 'degree_new_student_name' : 'cert_new_student_name';

        document.getElementById(studentIdField).value = '';
        document.getElementById(studentNameField).value = '';
        document.getElementById(studentIdField).focus();
    }

    function updateResultsHeader() {
        const activeTab = getActiveTab();
        if (activeTab === 'degree') {
            updateDegreeResultsHeader();
        } else {
            updateCertResultsHeader();
        }
    }

    function fetchDegreeStudentsForResultEntry() {
        const data = {
            location: degreeLocation.value,
            course_type: degreeCourseType.value,
            course_id: degreeCourse.value,
            intake_id: degreeIntake.value,
            specialization: degreeSpecialization.value,
            semester: degreeSemester.value,
            module_id: degreeModule.value
        };
        showSpinner(true);
        fetch('/get-students-for-exam-result', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            setHidden(document.getElementById('degreeBulkUploadSection'), false);
            setHidden(document.getElementById('degreeResultsTableSection'), false);
            setResultsStatus('degree', !!data.results_exist);

            if (data.success && data.students && data.students.length > 0) {
                renderEditableResultsTable(data.students);
                setHidden(document.getElementById('degreeSaveAllBtnSection'), false);
            } else {
                degreeResults = [];
                updateExistingRows();
                setHidden(document.getElementById('degreeSaveAllBtnSection'), true);
                if (data.message) {
                    showToast('Error', data.message, 'error');
                }
            }
        })
        .catch(() => showToast('Error', 'Failed to fetch students.', 'error'))
        .finally(() => showSpinner(false));
    }

    function fetchCertStudentsForResultEntry() {
        const data = {
            location: certLocation.value,
            course_type: 'certificate',
            course_id: certCourse.value,
            intake_id: certIntake.value,
            semester: null,
            module_id: null
        };
        showSpinner(true);
        fetch('/get-students-for-exam-result', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            setHidden(document.getElementById('certBulkUploadSection'), false);
            setHidden(document.getElementById('certResultsTableSection'), false);
            setResultsStatus('cert', !!data.results_exist);

            if (data.success && data.students && data.students.length > 0) {
                renderEditableResultsTable(data.students);
                setHidden(document.getElementById('certSaveAllBtnSection'), false);
            } else {
                certResults = [];
                updateExistingRows();
                setHidden(document.getElementById('certSaveAllBtnSection'), true);
                if (data.message) {
                    showToast('Error', data.message, 'error');
                }
            }
        })
        .catch(() => showToast('Error', 'Failed to fetch students.', 'error'))
        .finally(() => showSpinner(false));
    }

    window.updateResultMark = function(index, value) {
        const results = getCurrentResults();
        if (results[index]) {
            results[index].marks = value === '' ? '' : parseFloat(value);
        }
    }

    window.updateResultGrade = function(index, value) {
        const results = getCurrentResults();
        if (results[index]) {
            results[index].grade = value;
        }
    }

    window.updateResultRemarks = function(index, value) {
        const results = getCurrentResults();
        if (results[index]) {
            results[index].remarks = value;
        }
    }



    // Bulk upload functions
    function downloadFile(blob, filename) {
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.rel = 'noopener';
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    function safeFilenamePart(value) {
        return String(value || 'file').replace(/[^\w\-]+/g, '_').replace(/_+/g, '_').replace(/^_|_$/g, '');
    }

    async function handleDownloadTemplate() {
        const filterData = getFilterData();
        if (!filterData) {
            showToast('Warning', 'Please select all filters first to download the template.', 'warning');
            return;
        }

        showSpinner(true);
        try {
            const response = await fetch('{{ route("download.exam.results.template") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'text/csv, application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(filterData)
            });

            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || data.error || 'Unable to download the template.');
            }

            if (!response.ok) {
                throw new Error('Unable to download the template.');
            }

            const blob = await response.blob();
            if (!blob || blob.size === 0 || (blob.type && blob.type.includes('text/html'))) {
                throw new Error('Unable to download the template.');
            }

            const activeTab = getActiveTab();
            const courseName = activeTab === 'degree'
                ? degreeCourse.options[degreeCourse.selectedIndex]?.text
                : certCourse.options[certCourse.selectedIndex]?.text;
            const moduleName = activeTab === 'degree'
                ? degreeModule.options[degreeModule.selectedIndex]?.text
                : 'Certificate';
            const intakeName = activeTab === 'degree'
                ? degreeIntake.options[degreeIntake.selectedIndex]?.text
                : certIntake.options[certIntake.selectedIndex]?.text;
            const cd = response.headers.get('Content-Disposition') || '';
            const match = cd.match(/filename="?([^"]+)"?/i);
            const filename = match
                ? match[1]
                : `exam_results_template_${safeFilenamePart(courseName)}_${safeFilenamePart(moduleName)}_${safeFilenamePart(intakeName)}.csv`;

            downloadFile(blob, filename);
            showToast('Success', 'Template downloaded successfully.', 'success');
        } catch (error) {
            showToast('Error', error.message || 'Failed to download template. Please try again.', 'error');
        } finally {
            showSpinner(false);
        }
    }

    function handleBulkUpload() {
        const activeTab = getActiveTab();
        const file = activeTab === 'degree' ? degreeBulkUploadFile.files[0] : certBulkUploadFile.files[0];
        if (!file) {
            showToast('Warning', 'Please select a file to upload.', 'bg-warning');
            return;
        }

        // Validate file type
        const allowedTypes = ['text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'];
        if (!allowedTypes.includes(file.type) && !file.name.toLowerCase().endsWith('.csv')) {
            showToast('Error', 'Please select a valid CSV file.', 'bg-danger');
            return;
        }

        // Validate file size (10MB limit)
        if (file.size > 10 * 1024 * 1024) {
            showToast('Error', 'File size must be less than 10MB.', 'bg-danger');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('format', 'csv');

        showSpinner(true);
        fetch('/data-import/exam-results', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const importedCount = data.imported_count || 0;
                const failedCount = data.failed_count || 0;

                let message = `Bulk upload completed successfully! ${importedCount} exam results imported.`;
                if (failedCount > 0) {
                    message += ` ${failedCount} records failed to import.`;
                }

                showToast('Success', message, '#ccffcc');

                // Clear the file input
                const activeTab = getActiveTab();
                if (activeTab === 'degree') {
                    degreeBulkUploadFile.value = '';
                } else {
                    certBulkUploadFile.value = '';
                }

                // Refresh the current view to show updated results
                const allFilled = activeTab === 'degree' ? allDegreeFilled() : allCertFilled();
                if (allFilled) {
                    if (activeTab === 'degree') {
                        fetchDegreeStudentsForResultEntry();
                    } else {
                        fetchCertStudentsForResultEntry();
                    }
                }
            } else {
                showToast('Error', 'Upload failed: ' + data.message, 'bg-danger');
            }
        })
        .catch(error => {
            console.error('Upload error:', error);
            showToast('Error', 'An error occurred during upload. Please try again.', 'bg-danger');
        })
        .finally(() => showSpinner(false));
    }
});
</script>

<style nonce="{{ $cspNonce }}">
.exam-results-page [class*="col-"] {
    min-width: 0;
}
.exam-results-page .form-select,
.exam-results-page .form-control,
.exam-results-page .nebula-select {
    max-width: 100%;
}
.exam-results-tabs {
    display: flex;
    flex-wrap: wrap;
    overflow: visible;
}
.exam-results-tabs .nav-item {
    flex: 0 0 auto;
}
.exam-results-section-header {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    justify-content: space-between;
    align-items: center;
}
.exam-results-column-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
}
.exam-results-table-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.exam-results-table-scroll table {
    min-width: 640px;
    margin-bottom: 0;
}
.exam-results-table-title {
    overflow-wrap: anywhere;
    word-break: break-word;
}
.exam-results-upload-row {
    display: flex;
    flex-wrap: nowrap;
    align-items: stretch;
    gap: 0.75rem;
}
.exam-results-upload-row .form-control {
    flex: 1 1 auto;
    min-width: 0;
}
.exam-results-upload-btn {
    flex: 0 0 auto;
    white-space: nowrap;
}
.exam-results-save-btn {
    width: 100%;
    max-width: 28rem;
}
.exam-results-spinner {
    position: fixed;
    inset: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
    z-index: 10900;
}
.exam-results-spinner[hidden] {
    display: none !important;
}
.exam-results-filters .form-label {
    font-weight: 500;
}
.lds-ring { display: inline-block; position: relative; width: 80px; height: 80px; }
.lds-ring div { box-sizing: border-box; display: block; position: absolute; width: 64px; height: 64px; margin: 8px; border: 8px solid #fff; border-radius: 50%; animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite; border-color: #fff transparent transparent transparent; }
.lds-ring div:nth-child(1) { animation-delay: -0.45s; }
.lds-ring div:nth-child(2) { animation-delay: -0.3s; }
.lds-ring div:nth-child(3) { animation-delay: -0.15s; }
@keyframes lds-ring { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
@media (max-width: 767.98px) {
    .exam-results-page .card-body {
        padding: 1rem 0.75rem;
    }
    .exam-results-page h2 {
        font-size: 1.25rem;
    }
    .exam-results-page .form-control,
    .exam-results-page .form-select,
    .exam-results-page .nebula-select-toggle {
        font-size: 16px;
    }
    .exam-results-column-actions .btn,
    .exam-results-section-header .btn,
    .exam-results-save-btn,
    .exam-results-upload-btn {
        width: 100%;
    }
    .exam-results-upload-row {
        flex-wrap: wrap;
    }
}
</style>
@endsection
