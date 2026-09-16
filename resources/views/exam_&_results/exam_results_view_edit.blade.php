@extends('inc.app')

@section('title', 'NEBULA | View & Edit Exam Results')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<div class="container-fluid exam-results-page px-2 px-md-3 mt-3 mb-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="text-center mb-4">View &amp; Edit Exam Results</h2>
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
                <div class="tab-pane fade show active" id="degree-panel" role="tabpanel">
                    <div class="exam-results-filters mb-4">
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

                    <div class="mt-4" id="degreeResultsTableSection" hidden>
                        <h4 id="degreeResultsTableHeader" class="text-center mb-3 exam-results-table-title" hidden></h4>

                        <div class="row g-3 mb-4" id="degreeStatisticsCards" hidden>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="card exam-results-stat-card bg-primary text-white h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Students</h5>
                                        <h3 id="degreeTotalStudents">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="card exam-results-stat-card bg-success text-white h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Average Marks</h5>
                                        <h3 id="degreeAverageMarks">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="card exam-results-stat-card bg-info text-white h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Pass Rate</h5>
                                        <h3 id="degreePassRate">0%</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="card exam-results-stat-card bg-warning text-white h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Last Updated</h5>
                                        <h6 id="degreeLastUpdated" class="mb-0">-</h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="exam-results-table-scroll">
                            <table class="table table-bordered" id="degreeResultsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Registration Number</th>
                                        <th>Student Name</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Remarks</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody id="degreeResultsTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="text-center mt-4" id="degreeUpdateAllBtnSection" hidden>
                        <button type="button" id="degreeUpdateAllBtn" class="btn btn-primary exam-results-save-btn py-2">Update All Results</button>
                    </div>
                </div>

                <div class="tab-pane fade" id="certificate-panel" role="tabpanel">
                    <div class="exam-results-filters mb-4">
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

                    <div class="mt-4" id="certResultsTableSection" hidden>
                        <h4 id="certResultsTableHeader" class="text-center mb-3 exam-results-table-title" hidden></h4>

                        <div class="row g-3 mb-4" id="certStatisticsCards" hidden>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="card exam-results-stat-card bg-primary text-white h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Students</h5>
                                        <h3 id="certTotalStudents">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="card exam-results-stat-card bg-success text-white h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Average Marks</h5>
                                        <h3 id="certAverageMarks">0</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="card exam-results-stat-card bg-info text-white h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Pass Rate</h5>
                                        <h3 id="certPassRate">0%</h3>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="card exam-results-stat-card bg-warning text-white h-100">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Last Updated</h5>
                                        <h6 id="certLastUpdated" class="mb-0">-</h6>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="exam-results-table-scroll">
                            <table class="table table-bordered" id="certResultsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>Registration Number</th>
                                        <th>Student Name</th>
                                        <th>Marks</th>
                                        <th>Grade</th>
                                        <th>Remarks</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody id="certResultsTableBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <div class="text-center mt-4" id="certUpdateAllBtnSection" hidden>
                        <button type="button" id="certUpdateAllBtn" class="btn btn-primary exam-results-save-btn py-2">Update All Results</button>
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

    const degreeLocation = document.getElementById('degree_location');
    const degreeCourseType = document.getElementById('degree_course_type');
    const degreeCourse = document.getElementById('degree_course');
    const degreeIntake = document.getElementById('degree_intake');
    const degreeSpecialization = document.getElementById('degree_specialization');
    const degreeSpecializationRow = document.getElementById('degree_specialization_row');
    const degreeSemester = document.getElementById('degree_semester');
    const degreeModule = document.getElementById('degree_module');

    const certLocation = document.getElementById('cert_location');
    const certCourse = document.getElementById('cert_course');
    const certIntake = document.getElementById('cert_intake');

    const degreeUpdateAllBtn = document.getElementById('degreeUpdateAllBtn');
    const degreeResultsTableBody = document.getElementById('degreeResultsTableBody');
    const degreeResultsTableHeader = document.getElementById('degreeResultsTableHeader');
    const degreeStatisticsCards = document.getElementById('degreeStatisticsCards');

    const certUpdateAllBtn = document.getElementById('certUpdateAllBtn');
    const certResultsTableBody = document.getElementById('certResultsTableBody');
    const certResultsTableHeader = document.getElementById('certResultsTableHeader');
    const certStatisticsCards = document.getElementById('certStatisticsCards');

    function setHidden(el, hidden) {
        if (!el) return;
        el.hidden = !!hidden;
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
                    fetchExistingExamResults();
                }
            })
            .catch(() => {
                resetSpecialization();
                degreeSpecializationsLoaded = true;
            })
            .finally(() => showSpinner(false));
    }

    resetAndDisable(degreeCourse, 'Select a Course');
    resetAndDisable(degreeIntake, 'Select an Intake');
    resetAndDisable(degreeSemester, 'Select a Semester');
    resetAndDisable(degreeModule, 'Select a Module');
    resetAndDisable(certCourse, 'Select a Course');
    resetAndDisable(certIntake, 'Select an Intake');

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
        if (allDegreeFilled()) {
            fetchExistingExamResults();
        }
        updateResultsHeader();
    });

    degreeSpecialization.addEventListener('change', function() {
        const previousModuleId = degreeModule.value;
        if (degreeSemester.value && degreeIntake.value && degreeCourse.value && degreeLocation.value) {
            degreeModule.disabled = false;
            handleDegreeModuleFetch(previousModuleId);
            return;
        }
        updateResultsHeader();
    });

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
        if (allCertFilled()) {
            fetchExistingExamResults();
        }
        updateResultsHeader();
    });

    function allDegreeFilled() {
        return degreeLocation.value && degreeCourseType.value && degreeCourse.value &&
               degreeIntake.value && hasDegreeSpecializationSelection() && degreeSemester.value && degreeModule.value;
    }

    function allCertFilled() {
        return certLocation.value && certCourse.value && certIntake.value;
    }

    function populateDropdown(select, items, valueKey, textKey, defaultText) {
        if (!select) return;
        select.innerHTML = `<option selected disabled value="">Select ${defaultText}</option>`;
        (items || []).forEach(item => {
            const value = item[valueKey];
            const displayText = item[textKey];
            if (displayText && value) {
                select.add(new Option(displayText, value));
            }
        });
    }

    degreeUpdateAllBtn.addEventListener('click', handleUpdateAll);
    certUpdateAllBtn.addEventListener('click', handleUpdateAll);

    function handleUpdateAll() {
        const resultsTableBody = getActiveTab() === 'degree' ? degreeResultsTableBody : certResultsTableBody;
        const filterData = getFilterData();
        if (!filterData) {
            showToast('Warning', 'Please select all filters and ensure results are loaded.', 'warning');
            return;
        }

        const updatedResults = [];
        resultsTableBody.querySelectorAll('tr[data-result-id]').forEach(row => {
            const marksInput = row.querySelector('input[name="marks"]');
            const gradeInput = row.querySelector('input[name="grade"]');
            const remarksInput = row.querySelector('input[name="remarks"]');
            const filtered = { id: parseInt(row.dataset.resultId, 10) };
            if (marksInput && marksInput.value !== '') {
                filtered.marks = parseFloat(marksInput.value);
            }
            if (gradeInput && gradeInput.value.trim()) {
                filtered.grade = gradeInput.value.trim();
            }
            if (remarksInput && remarksInput.value.trim()) {
                filtered.remarks = remarksInput.value.trim();
            }
            if (filtered.marks !== undefined || filtered.grade !== undefined || filtered.remarks !== undefined) {
                updatedResults.push(filtered);
            }
        });

        if (updatedResults.length === 0) {
            showToast('Warning', 'Please enter at least marks, grade, or remarks for at least one student.', 'warning');
            return;
        }

        showSpinner(true);
        fetch('{{ route("update.result") }}', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}'},
            body: JSON.stringify({ ...filterData, results: updatedResults })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('Success', data.message, 'success');
                fetchExistingExamResults({ notifyFound: false });
                return;
            }
            let errorMsg = data.message || 'An error occurred.';
            if (data.errors) {
                errorMsg = Object.values(data.errors).flat().join(' ');
            }
            showToast('Error', errorMsg, 'error');
            showSpinner(false);
        })
        .catch(() => {
            showToast('Error', 'An error occurred while updating results.', 'error');
            showSpinner(false);
        });
    }

    function getFilterData() {
        const activeTab = getActiveTab();
        if (activeTab === 'degree') {
            const data = {
                location: degreeLocation.value,
                course_type: degreeCourseType.value,
                course_id: degreeCourse.value,
                intake_id: degreeIntake.value,
                specialization: degreeSpecialization.value || '',
                semester: degreeSemester.value,
                module_id: degreeModule.value
            };
            return allDegreeFilled() ? data : null;
        }

        const data = {
            location: certLocation.value,
            course_type: 'certificate',
            course_id: certCourse.value,
            intake_id: certIntake.value
        };
        return Object.values(data).some(v => !v) ? null : data;
    }

    function renderTable() {
        const resultsTableBody = getActiveTab() === 'degree' ? degreeResultsTableBody : certResultsTableBody;
        const results = getCurrentResults();
        resultsTableBody.replaceChildren();

        if (!results.length) {
            const empty = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 6;
            cell.className = 'text-center';
            cell.textContent = 'No existing results found for these filters.';
            empty.appendChild(cell);
            resultsTableBody.appendChild(empty);
            return;
        }

        results.forEach((result, index) => {
            const row = document.createElement('tr');
            row.dataset.resultId = String(result.id || '');

            const idCell = document.createElement('td');
            idCell.textContent = result.registration_id || '';
            const nameCell = document.createElement('td');
            nameCell.textContent = result.student_name || '';

            const marksInput = document.createElement('input');
            marksInput.type = 'number';
            marksInput.className = 'form-control';
            marksInput.name = 'marks';
            marksInput.min = '0';
            marksInput.max = '100';
            marksInput.step = '0.01';
            marksInput.value = result.marks ?? '';
            marksInput.addEventListener('change', function () {
                updateResultMark(index, this.value);
            });

            const gradeInput = document.createElement('input');
            gradeInput.type = 'text';
            gradeInput.className = 'form-control';
            gradeInput.name = 'grade';
            gradeInput.maxLength = 5;
            gradeInput.value = result.grade || '';
            gradeInput.addEventListener('change', function () {
                updateResultGrade(index, this.value);
            });

            const remarksInput = document.createElement('input');
            remarksInput.type = 'text';
            remarksInput.className = 'form-control';
            remarksInput.name = 'remarks';
            remarksInput.maxLength = 255;
            remarksInput.placeholder = 'Enter remarks';
            remarksInput.value = result.remarks || '';
            remarksInput.addEventListener('change', function () {
                updateResultRemarks(index, this.value);
            });

            const marksCell = document.createElement('td');
            marksCell.appendChild(marksInput);
            const gradeCell = document.createElement('td');
            gradeCell.appendChild(gradeInput);
            const remarksCell = document.createElement('td');
            remarksCell.appendChild(remarksInput);
            const updatedCell = document.createElement('td');
            updatedCell.className = 'text-nowrap';
            updatedCell.textContent = formatColomboDateTime(result.updated_at);

            row.append(idCell, nameCell, marksCell, gradeCell, remarksCell, updatedCell);
            resultsTableBody.appendChild(row);
        });
    }

    function updateResultsHeader() {
        const activeTab = getActiveTab();
        if (activeTab === 'degree') {
            if (!degreeCourse.value || !degreeSemester.value || !degreeModule.value) {
                return;
            }
            const courseName = degreeCourse.options[degreeCourse.selectedIndex].text;
            const specializationName = degreeSpecializationRow && !degreeSpecializationRow.hidden && degreeSpecialization.value
                ? ` - ${degreeSpecialization.options[degreeSpecialization.selectedIndex]?.text || ''}`
                : '';
            const semesterName = degreeSemester.options[degreeSemester.selectedIndex].text;
            const moduleName = degreeModule.options[degreeModule.selectedIndex].text;
            degreeResultsTableHeader.textContent = `Exam Results for: ${courseName}${specializationName} - ${semesterName} (${moduleName})`;
            setHidden(degreeResultsTableHeader, false);
            return;
        }

        if (certCourse.value && certIntake.value) {
            const courseName = certCourse.options[certCourse.selectedIndex].text;
            const intakeName = certIntake.options[certIntake.selectedIndex].text;
            certResultsTableHeader.textContent = `Exam Results for: ${courseName} - ${intakeName}`;
            setHidden(certResultsTableHeader, false);
        }
    }

    function toDateSafe(dateText) {
        if (!dateText) {
            return null;
        }
        const date = new Date(String(dateText).replace(' ', 'T'));
        return Number.isNaN(date.getTime()) ? null : date;
    }

    function formatColomboDateTime(dateText) {
        const date = toDateSafe(dateText);
        if (!date) {
            return '-';
        }
        return date.toLocaleString('en-GB', {
            timeZone: 'Asia/Colombo',
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    function isResultPassed(result) {
        const grade = String(result?.grade ?? '').trim().toUpperCase();
        if (grade) {
            return !['F', 'FAIL', 'AB', 'ABSENT'].includes(grade);
        }
        const marks = Number(result?.marks);
        return Number.isFinite(marks) && marks >= 40;
    }

    function updateStatistics(activeTab, results) {
        const totalStudentsId = activeTab === 'degree' ? 'degreeTotalStudents' : 'certTotalStudents';
        const averageMarksId = activeTab === 'degree' ? 'degreeAverageMarks' : 'certAverageMarks';
        const passRateId = activeTab === 'degree' ? 'degreePassRate' : 'certPassRate';
        const lastUpdatedId = activeTab === 'degree' ? 'degreeLastUpdated' : 'certLastUpdated';

        const totalStudents = results.length;
        const marksList = results.map(r => Number(r?.marks)).filter(m => Number.isFinite(m));
        const averageMarks = marksList.length > 0 ? (marksList.reduce((sum, value) => sum + value, 0) / marksList.length) : 0;
        const passedCount = results.filter(isResultPassed).length;
        const passRate = totalStudents > 0 ? ((passedCount / totalStudents) * 100) : 0;
        const latestDate = results.map(r => toDateSafe(r?.updated_at)).filter(Boolean).sort((a, b) => b - a)[0] || null;

        document.getElementById(totalStudentsId).textContent = String(totalStudents);
        document.getElementById(averageMarksId).textContent = averageMarks.toFixed(1);
        document.getElementById(passRateId).textContent = `${passRate.toFixed(0)}%`;
        document.getElementById(lastUpdatedId).textContent = latestDate
            ? formatColomboDateTime(latestDate.toISOString())
            : '-';
    }

    function updateResultMark(index, value) {
        const results = getCurrentResults();
        if (results[index]) {
            results[index].marks = value === '' ? '' : parseFloat(value);
        }
    }

    function updateResultGrade(index, value) {
        const results = getCurrentResults();
        if (results[index]) {
            results[index].grade = value;
        }
    }

    function updateResultRemarks(index, value) {
        const results = getCurrentResults();
        if (results[index]) {
            results[index].remarks = value;
        }
    }

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
                updateResultsHeader();
                return;
            }
            const context = degreeSpecialization.value ? 'semester and specialization' : 'semester';
            explainEmptyOptions(degreeModule, `No modules are included for this ${context}.`);
        })
        .catch(() => explainEmptyOptions(degreeModule, 'Unable to load modules for this semester.'))
        .finally(() => showSpinner(false));
    }

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

    function fetchExistingExamResults({ notifyFound = true } = {}) {
        const filterData = getFilterData();
        if (!filterData) {
            return;
        }

        showSpinner(true);
        fetch('{{ route("get.existing.exam.results") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(filterData)
        })
        .then(response => response.json())
        .then(data => {
            const activeTab = getActiveTab();
            const statisticsCards = activeTab === 'degree' ? degreeStatisticsCards : certStatisticsCards;
            const resultsTableSection = activeTab === 'degree' ? 'degreeResultsTableSection' : 'certResultsTableSection';
            const updateAllBtnSection = activeTab === 'degree' ? 'degreeUpdateAllBtnSection' : 'certUpdateAllBtnSection';

            setHidden(document.getElementById(resultsTableSection), false);
            updateResultsHeader();

            if (data.success && data.results && data.results.length > 0) {
                updateStatistics(activeTab, data.results);
                setHidden(statisticsCards, false);
                setCurrentResults(data.results);
                renderTable();
                setHidden(document.getElementById(updateAllBtnSection), false);
                if (notifyFound) {
                    showToast('Info', `Found ${data.results.length} existing result(s).`, 'info');
                }
            } else {
                setCurrentResults([]);
                renderTable();
                setHidden(document.getElementById(updateAllBtnSection), true);
                setHidden(statisticsCards, true);
                if (data.message) {
                    showToast('Info', data.message, 'info');
                } else {
                    showToast('Info', 'No existing results found for these filters.', 'info');
                }
            }
        })
        .catch(() => showToast('Error', 'Failed to fetch existing results.', 'error'))
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
.exam-results-table-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.exam-results-table-scroll table {
    min-width: 720px;
    margin-bottom: 0;
}
.exam-results-table-title {
    overflow-wrap: anywhere;
    word-break: break-word;
}
.exam-results-save-btn {
    width: 100%;
    max-width: 28rem;
}
.exam-results-stat-card .card-title {
    font-size: 0.95rem;
}
.exam-results-stat-card h3,
.exam-results-stat-card h6 {
    overflow-wrap: anywhere;
    word-break: break-word;
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
    .exam-results-save-btn {
        width: 100%;
        max-width: none;
    }
}
</style>
@endsection
