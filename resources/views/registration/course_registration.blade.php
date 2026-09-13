@extends('inc.app')

@section('title', 'NEBULA | Course Registration')

@section('content')
<style nonce="{{ $cspNonce }}">
    .course-registration-page,
    .course-registration-page .card,
    .course-registration-page .card-body {
        min-width: 0;
        max-width: 100%;
        overflow: visible;
    }
    .course-registration-page [class*="col-"] {
        min-width: 0;
    }
    .course-registration-page .form-label {
        font-weight: 600;
    }
    .course-registration-page .form-select,
    .course-registration-page .form-control,
    .course-registration-page .nebula-select,
    .course-registration-page .nebula-select-toggle {
        width: 100%;
        max-width: 100%;
    }
    .course-registration-page .input-group {
        flex-wrap: nowrap;
        width: 100%;
        max-width: 100%;
    }
    .course-registration-page .input-group > .form-control,
    .course-registration-page .input-group > .form-select {
        width: 1%;
        min-width: 0;
        flex: 1 1 auto;
        max-width: 100%;
    }
    .course-registration-page .input-group-text {
        flex: 0 0 auto;
        white-space: nowrap;
    }
    .course-registration-page .form-control:focus,
    .course-registration-page .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    .course-reg-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: flex-start;
        gap: 0.75rem;
        margin-bottom: 1rem;
    }
    .course-reg-section {
        background: #dc3545;
        color: #fff;
        padding: 0.65rem 0.85rem;
        border-radius: 8px;
        font-size: 1rem;
        word-break: break-word;
    }
    .course-reg-table {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    .course-reg-table table {
        margin-bottom: 0;
        min-width: 280px;
    }
    .course-reg-actions .btn {
        min-height: 42px;
        white-space: normal;
    }
    .terminated-disabled {
        opacity: 0.6;
        filter: grayscale(100%);
        pointer-events: none;
    }
    #spinner-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
    }
    .lds-ring {
        display: inline-block;
        position: relative;
        width: 80px;
        height: 80px;
    }
    .lds-ring div {
        box-sizing: border-box;
        display: block;
        position: absolute;
        width: 64px;
        height: 64px;
        margin: 8px;
        border: 8px solid #fff;
        border-radius: 50%;
        animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
        border-color: #fff transparent transparent transparent;
    }
    .lds-ring div:nth-child(1) { animation-delay: -0.45s; }
    .lds-ring div:nth-child(2) { animation-delay: -0.3s; }
    .lds-ring div:nth-child(3) { animation-delay: -0.15s; }
    @keyframes lds-ring {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .is-invalid { border-color: #dc3545 !important; }
    .is-valid { border-color: #198754 !important; }
    @media (max-width: 767.98px) {
        .course-registration-page h2 {
            font-size: 1.35rem;
        }
        .course-registration-page .card-body {
            padding: 1rem 0.75rem;
        }
        .course-registration-page .form-control,
        .course-registration-page .form-select,
        .course-registration-page .nebula-select-toggle {
            font-size: 16px;
        }
        .course-reg-search .btn {
            width: 100%;
        }
        .course-reg-radios {
            flex-wrap: wrap;
            gap: 0.5rem 1rem;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 course-registration-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Course Registration</h2>
            <hr>
            <div id="spinner-overlay" style="display:none;">
                <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
            </div>

            <form id="searchForm" class="course-reg-search mb-3">
                @csrf
                <div class="row mx-0 mx-sm-3 align-items-center">
                    <label for="studentNicSearch" class="col-sm-2 col-form-label">Student NIC<span class="text-danger">*</span></label>
                    <div class="col-sm-8">
                        <input type="text" class="form-control bg-white" id="studentNicSearch" name="studentNicSearch" placeholder="Enter Student ID (NIC)" autocomplete="off">
                    </div>
                    <div class="col-sm-2 mt-2 mt-sm-0">
                        <button type="button" class="btn btn-primary w-100" id="searchNicBtn">Search</button>
                    </div>
                </div>
            </form>

            <div id="messageContainer" class="mb-3"></div>
            <div id="searchMessageContainer"></div>

            <div id="studentDetailsSection" style="display: none;">
                <div class="row g-3 mb-3">
                    <div class="col-12 col-md-6">
                        <label for="studentName" class="form-label">Name</label>
                        <input type="text" class="form-control bg-white" id="studentName" name="studentName" readonly>
                    </div>
                    <div class="col-12 col-md-6">
                        <label for="studentNIC" class="form-label">NIC</label>
                        <input type="text" class="form-control bg-white" id="studentNIC" name="studentNIC" readonly>
                    </div>
                </div>

                <h5 class="course-reg-section mb-3"><strong>O/L Exam Details</strong></h5>
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6">
                        <label for="olExamType" class="form-label">Exam Type</label>
                        <input type="text" class="form-control bg-white" id="olExamType" name="olExamType" readonly>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="olExamYear" class="form-label">Exam Year</label>
                        <input type="text" class="form-control bg-white" id="olExamYear" name="olExamYear" readonly>
                    </div>
                </div>
                <h6 class="mb-2">O/L Exam Subjects and Grades</h6>
                <div class="course-reg-table table-responsive mb-4">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="bg-primary text-white" scope="col">Subject</th>
                                <th class="bg-primary text-white" scope="col">Grade</th>
                            </tr>
                        </thead>
                        <tbody id="olExamSubjectsAndGradesTableBody">
                            @foreach($olSubjects as $subject)
                                <tr>
                                    <td>{{ $subject['subject'] ?? 'N/A' }}</td>
                                    <td>{{ $subject['result'] ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <h5 class="course-reg-section mb-3"><strong>A/L Exam Details</strong></h5>
                <div class="row g-3 mb-3">
                    <div class="col-12 col-sm-6">
                        <label for="alExamType" class="form-label">Exam Type</label>
                        <input type="text" class="form-control bg-white" id="alExamType" name="alExamType" readonly>
                    </div>
                    <div class="col-12 col-sm-6">
                        <label for="alExamYear" class="form-label">Exam Year</label>
                        <input type="text" class="form-control bg-white" id="alExamYear" name="alExamYear" readonly>
                    </div>
                    <div class="col-12">
                        <label for="alExamStream" class="form-label">Exam Stream</label>
                        <input type="text" class="form-control bg-white" id="alExamStream" name="alExamStream" readonly>
                    </div>
                </div>
                <h6 class="mb-2">A/L Exam Subjects and Grades</h6>
                <div class="course-reg-table table-responsive mb-4">
                    <table class="table table-bordered table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="bg-primary text-white" scope="col">Subject</th>
                                <th class="bg-primary text-white" scope="col">Grade</th>
                            </tr>
                        </thead>
                        <tbody id="alExamSubjectsAndGradesTableBody">
                            @foreach($alSubjects as $subject)
                                <tr>
                                    <td>{{ $subject['subject'] ?? 'N/A' }}</td>
                                    <td>{{ $subject['result'] ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <hr>
                <input type="hidden" id="studentId" name="studentId">
                <input type="hidden" id="studentRegistrationId" name="studentRegistrationId">

                <div id="registrationFields">
                    <div class="mb-3">
                        <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                        <select class="form-select" id="location" name="location" required>
                            <option selected disabled value="">Choose a location...</option>
                            <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                            <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                            <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="courseSearch" class="form-label">Course <span class="text-danger">*</span></label>
                        <select class="form-select bg-white" id="courseSearch" name="courseSearch" required disabled>
                            <option selected disabled>Select a location first</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="intakeId" class="form-label">Intake <span class="text-danger">*</span></label>
                        <select class="form-select" id="intakeId" name="intakeId" required disabled>
                            <option value="" selected disabled>Select a course first</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="registrationFee" class="form-label">Registration Fee <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white">LKR</span>
                            <input type="number" class="form-control bg-white" id="registrationFee" name="registrationFee" placeholder="Enter registration fee" min="0" step="0.01" required>
                        </div>
                    </div>

                    <hr class="mt-4">
                    <fieldset class="mb-3">
                        <legend class="h5 mb-3">Student Counsellor Details</legend>
                        <div class="mb-3">
                            <div class="form-label">SLT Employee</div>
                            <div class="d-flex course-reg-radios align-items-center">
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="slt_employee" id="sltYes" value="yes">
                                    <label class="form-check-label" for="sltYes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline mb-0">
                                    <input class="form-check-input" type="radio" name="slt_employee" id="sltNo" value="no" checked>
                                    <label class="form-check-label" for="sltNo">No</label>
                                </div>
                            </div>
                        </div>
                        <div id="serviceNoField" style="display: none;">
                            <div class="mb-3">
                                <label for="serviceNo" class="form-label">Service No <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="serviceNo" name="service_no" placeholder="Enter service number">
                            </div>
                        </div>
                        <div id="externalCounselorFields" style="display: none;">
                            <div class="mb-3">
                                <label for="counselorName" class="form-label">Counselor Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="counselorName" name="counselor_name" placeholder="Enter counselor's name">
                            </div>
                            <div class="mb-3">
                                <label for="counselorNic" class="form-label">Counselor NIC <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="counselorNic" name="counselor_nic" placeholder="Enter counselor's NIC number">
                                <div class="invalid-feedback"><span class="text-danger">✖</span> Invalid NIC. Use 12 digits or 9 digits + 1 letter.</div>
                                <div class="valid-feedback"><span class="text-success">✔</span> Valid NIC.</div>
                            </div>
                            <div class="mb-3">
                                <label for="counselorPhone" class="form-label">Counselor Phone <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="counselorPhone" name="counselor_phone" placeholder="Enter counselor's phone number">
                                <div class="invalid-feedback"><span class="text-danger">✖</span> Invalid phone. Use "07x xxxxxxx" or "+94 xxxxxxxxx".</div>
                                <div class="valid-feedback"><span class="text-success">✔</span> Valid phone.</div>
                            </div>
                        </div>
                    </fieldset>

                    <hr class="mt-4">
                    <h4 class="mb-3 fw-bold">Course Details</h4>
                    <div class="mb-3">
                        <label for="courseStartDate" class="form-label">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="courseStartDate" name="courseStartDate" min="{{ date('Y-m-d') }}" required>
                    </div>

                    <hr class="mt-4">
                    <fieldset class="mb-3">
                        <legend class="h5 mb-3">Marketing Survey</legend>
                        <p class="mb-2"><strong>How did you hear about our institute?</strong></p>
                        <div class="row g-2">
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="LinkedIn" id="checkboxLinkedIn">
                                    <label class="form-check-label" for="checkboxLinkedIn">LinkedIn</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Facebook" id="checkboxFacebook">
                                    <label class="form-check-label" for="checkboxFacebook">Facebook</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Radio Advertisement" id="checkboxRadio">
                                    <label class="form-check-label" for="checkboxRadio">Radio Advertisement</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="TV advertisement" id="checkboxTV">
                                    <label class="form-check-label" for="checkboxTV">TV advertisement</label>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" value="Other" id="checkboxOther">
                                    <label class="form-check-label" for="checkboxOther">Other</label>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3" id="otherMarketingSurveyRow" style="display: none;">
                            <input type="text" class="form-control" id="marketing_survey_other" name="marketing_survey_other" placeholder="Please describe how you heard about us">
                        </div>
                    </fieldset>

                    <div class="d-flex flex-column gap-2 mt-4 course-reg-actions">
                        <button id="finalRegister" type="button" class="btn btn-primary w-100">Pre Register</button>
                        <button id="checkEligibility" type="button" class="btn btn-dark w-100" onclick="redirectToEligibility()">Check Eligibility</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    const searchBtn = document.getElementById('searchNicBtn');
    const nicInput = document.getElementById('studentNicSearch');
    const studentDetailsSection = document.getElementById('studentDetailsSection');
    const messageContainer = document.getElementById('messageContainer');
    const searchMessageContainer = document.getElementById('searchMessageContainer');
    const spinnerOverlay = document.getElementById('spinner-overlay');
    const olTableBody = document.getElementById('olExamSubjectsAndGradesTableBody');
    const alTableBody = document.getElementById('alExamSubjectsAndGradesTableBody');
    const baseUrl = "{{ url('/api/course-registration/student-by-nic') }}";
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';

    function escapeHtml(text) {
        const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
        return String(text ?? '').replace(/[&<>"']/g, function (match) {
            return map[match];
        });
    }

    function setLoading(isLoading) {
        if (spinnerOverlay) {
            spinnerOverlay.style.display = isLoading ? 'flex' : 'none';
        }
    }

    function showMessage(type, message) {
        const alertContainer = messageContainer || searchMessageContainer;
        if (!alertContainer) {
            return;
        }
        alertContainer.innerHTML = '<div class="alert alert-' + type + ' alert-dismissible fade show" role="alert">' +
            message +
            '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
            '</div>';
        alertContainer.style.display = 'block';
        alertContainer.scrollIntoView({ behavior: 'smooth', block: 'center' });

        setTimeout(() => {
            const alert = alertContainer.querySelector('.alert');
            if (alert) {
                alert.classList.remove('show');
                setTimeout(() => {
                    alertContainer.innerHTML = '';
                }, 150);
            }
        }, 5000);
    }

    function renderSubjects(tbody, subjects) {
        if (!tbody) {
            return;
        }
        tbody.innerHTML = '';
        if (!Array.isArray(subjects) || subjects.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2">N/A</td></tr>';
            return;
        }
        subjects.forEach(function (subject) {
            const name = subject.subject || subject.name || subject.title || 'N/A';
            const result = subject.result || subject.grade || subject.mark || 'N/A';
            const row = document.createElement('tr');
            row.innerHTML = '<td>' + escapeHtml(name) + '</td><td>' + escapeHtml(result) + '</td>';
            tbody.appendChild(row);
        });
    }

    function isValidNic(value) {
        return /^(?:\d{12}|\d{9}[vVxX])$/.test(String(value || '').trim());
    }

    function isValidPhone(value) {
        const phone = String(value || '').replace(/\s+/g, '');
        return /^(?:0\d{9}|\+94\d{9})$/.test(phone);
    }

    async function searchStudent() {
        const nic = nicInput ? nicInput.value.trim() : '';
        if (!nic) {
            showMessage('warning', 'Please enter a NIC.');
            if (studentDetailsSection) {
                studentDetailsSection.style.display = 'none';
            }
            return;
        }

        setLoading(true);
        showMessage('info', 'Searching student...');

        try {
            const response = await fetch(baseUrl + '/' + encodeURIComponent(nic), {
                headers: {
                    'Accept': 'application/json'
                }
            });

            const contentType = response.headers.get('content-type') || '';
            if (contentType.indexOf('application/json') === -1) {
                const text = await response.text();
                throw new Error('Unexpected response: ' + text.substring(0, 200));
            }

            const data = await response.json();
            if (!response.ok || !data.success) {
                showMessage('danger', escapeHtml((data && data.message) ? data.message : 'Student not found.'));
                if (studentDetailsSection) {
                    studentDetailsSection.style.display = 'none';
                }
                return;
            }

            const student = data.student || {};
            const olExam = Array.isArray(data.ol_exams) && data.ol_exams.length ? data.ol_exams[0] : null;
            const alExam = Array.isArray(data.al_exams) && data.al_exams.length ? data.al_exams[0] : null;

            const studentNameInput = document.getElementById('studentName');
            const studentNicInput = document.getElementById('studentNIC');
            const studentIdInput = document.getElementById('studentId');
            const studentRegIdInput = document.getElementById('studentRegistrationId');
            const olExamTypeInput = document.getElementById('olExamType');
            const olExamYearInput = document.getElementById('olExamYear');
            const alExamTypeInput = document.getElementById('alExamType');
            const alExamYearInput = document.getElementById('alExamYear');
            const alExamStreamInput = document.getElementById('alExamStream');

            if (studentNameInput) {
                studentNameInput.value = student.name_with_initials || '';
            }
            if (studentNicInput) {
                studentNicInput.value = student.id_value || nic;
            }
            if (studentIdInput) {
                studentIdInput.value = student.student_id || '';
            }
            if (studentRegIdInput) {
                studentRegIdInput.value = student.registration_id || student.student_id || '';
            }

            if (olExamTypeInput) {
                olExamTypeInput.value = olExam ? (olExam.exam_type && olExam.exam_type.exam_type ? olExam.exam_type.exam_type : (olExam.exam_type || '')) : '';
            }
            if (olExamYearInput) {
                olExamYearInput.value = olExam ? (olExam.exam_year || '') : '';
            }
            if (alExamTypeInput) {
                alExamTypeInput.value = alExam ? (alExam.exam_type && alExam.exam_type.exam_type ? alExam.exam_type.exam_type : (alExam.exam_type || '')) : '';
            }
            if (alExamYearInput) {
                alExamYearInput.value = alExam ? (alExam.exam_year || '') : '';
            }
            if (alExamStreamInput) {
                alExamStreamInput.value = alExam ? (alExam.stream && alExam.stream.stream ? alExam.stream.stream : (alExam.stream || '')) : '';
            }

            renderSubjects(olTableBody, olExam ? olExam.subjects : []);
            renderSubjects(alTableBody, alExam ? alExam.subjects : []);

            if (studentDetailsSection) {
                studentDetailsSection.style.display = 'block';
            }
            showMessage('success', 'Student found.');
        } catch (error) {
            console.error('NIC search failed:', error);
            showMessage('danger', 'Failed to fetch student details.');
            if (studentDetailsSection) {
                studentDetailsSection.style.display = 'none';
            }
        } finally {
            setLoading(false);
        }
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', searchStudent);
    }

    if (nicInput) {
        nicInput.addEventListener('keypress', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                searchStudent();
            }
        });
    }

    document.getElementById('searchForm')?.addEventListener('submit', function (event) {
        event.preventDefault();
        searchStudent();
    });

    const locationSelect = document.getElementById('location');
    const courseSelect = document.getElementById('courseSearch');
    const intakeSelect = document.getElementById('intakeId');

    if (locationSelect) {
        locationSelect.addEventListener('change', function() {
            const location = this.value;
            if (!location) {
                return;
            }

            fetch('{{ url('/course-registration/get-courses-by-location') }}/' + encodeURIComponent(location), {
                headers: { 'Accept': 'application/json' }
            })
                .then(response => response.json())
                .then(data => {
                    if (courseSelect) {
                        courseSelect.innerHTML = '<option selected disabled value="">Choose a course...</option>';
                        if (data.success && data.courses && data.courses.length > 0) {
                            data.courses.forEach(course => {
                                const option = document.createElement('option');
                                option.value = course.course_id;
                                option.textContent = course.course_name;
                                option.dataset.courseName = course.course_name;
                                courseSelect.appendChild(option);
                            });
                            courseSelect.disabled = false;
                        } else {
                            courseSelect.innerHTML = '<option selected disabled>No courses available</option>';
                            courseSelect.disabled = true;
                        }
                    }
                    if (intakeSelect) {
                        intakeSelect.innerHTML = '<option value="" selected disabled>Select a course first</option>';
                        intakeSelect.disabled = true;
                    }
                })
                .catch(error => {
                    console.error('Error fetching courses:', error);
                    if (courseSelect) {
                        courseSelect.innerHTML = '<option selected disabled>Error loading courses</option>';
                        courseSelect.disabled = true;
                    }
                    if (intakeSelect) {
                        intakeSelect.innerHTML = '<option value="" selected disabled>Select a course first</option>';
                        intakeSelect.disabled = true;
                    }
                });
        });
    }

    if (courseSelect) {
        courseSelect.addEventListener('change', function() {
            const courseId = this.value;
            const location = locationSelect ? locationSelect.value : '';

            if (!courseId || !location) {
                return;
            }

            fetch('{{ url('/course-registration/get-intakes') }}/' + encodeURIComponent(courseId) + '/' + encodeURIComponent(location), {
                headers: { 'Accept': 'application/json' }
            })
                .then(response => response.json())
                .then(data => {
                    if (intakeSelect) {
                        intakeSelect.innerHTML = '<option value="" selected disabled>Choose an intake...</option>';
                        if (data.success && data.intakes && data.intakes.length > 0) {
                            data.intakes.forEach(intake => {
                                const option = document.createElement('option');
                                option.value = intake.intake_id;
                                option.textContent = intake.batch;
                                option.setAttribute('data-start-date', intake.start_date);
                                option.setAttribute('data-registration-fee', intake.registration_fee);
                                intakeSelect.appendChild(option);
                            });
                            intakeSelect.disabled = false;
                        } else {
                            intakeSelect.innerHTML = '<option selected disabled>No intakes available</option>';
                            intakeSelect.disabled = true;
                        }
                    }
                })
                .catch(error => {
                    console.error('Error fetching intakes:', error);
                    if (intakeSelect) {
                        intakeSelect.innerHTML = '<option selected disabled>Error loading intakes</option>';
                        intakeSelect.disabled = true;
                    }
                });
        });
    }

    function normalizeDateForInput(dateString) {
        if (!dateString) {
            return '';
        }

        const dateOnlyMatch = dateString.match(/^\d{4}-\d{2}-\d{2}/);
        if (dateOnlyMatch) {
            return dateOnlyMatch[0];
        }

        const parsed = new Date(dateString);
        if (!isNaN(parsed.getTime())) {
            const year = parsed.getFullYear();
            const month = String(parsed.getMonth() + 1).padStart(2, '0');
            const day = String(parsed.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }

        return '';
    }

    if (intakeSelect) {
        intakeSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const registrationFeeInput = document.getElementById('registrationFee');
            const startDateInput = document.getElementById('courseStartDate');

            if (selectedOption && selectedOption.value) {
                const startDateRaw = selectedOption.getAttribute('data-start-date');
                const registrationFee = selectedOption.getAttribute('data-registration-fee');
                const normalizedStartDate = normalizeDateForInput(startDateRaw);

                if (registrationFeeInput) {
                    registrationFeeInput.value = registrationFee || '';
                    registrationFeeInput.readOnly = Boolean(registrationFee);
                }

                if (startDateInput) {
                    startDateInput.value = normalizedStartDate;
                    startDateInput.readOnly = Boolean(normalizedStartDate);
                }
            }
        });
    }

    const sltYesRadio = document.getElementById('sltYes');
    const sltNoRadio = document.getElementById('sltNo');
    const serviceNoField = document.getElementById('serviceNoField');
    const externalCounselorFields = document.getElementById('externalCounselorFields');
    const serviceNoInput = document.getElementById('serviceNo');
    const counselorNameInput = document.getElementById('counselorName');
    const counselorNicInput = document.getElementById('counselorNic');
    const counselorPhoneInput = document.getElementById('counselorPhone');

    function handleSltEmployeeChange() {
        if (sltYesRadio && sltYesRadio.checked) {
            if (serviceNoField) serviceNoField.style.display = 'block';
            if (externalCounselorFields) externalCounselorFields.style.display = 'none';
            if (serviceNoInput) serviceNoInput.required = true;
            if (counselorNameInput) counselorNameInput.required = false;
            if (counselorNicInput) counselorNicInput.required = false;
            if (counselorPhoneInput) counselorPhoneInput.required = false;
        } else if (sltNoRadio && sltNoRadio.checked) {
            if (serviceNoField) serviceNoField.style.display = 'none';
            if (externalCounselorFields) externalCounselorFields.style.display = 'block';
            if (serviceNoInput) serviceNoInput.required = false;
            if (counselorNameInput) counselorNameInput.required = true;
            if (counselorNicInput) counselorNicInput.required = true;
            if (counselorPhoneInput) counselorPhoneInput.required = true;
        }
    }

    if (sltYesRadio) {
        sltYesRadio.addEventListener('change', handleSltEmployeeChange);
    }
    if (sltNoRadio) {
        sltNoRadio.addEventListener('change', handleSltEmployeeChange);
    }

    handleSltEmployeeChange();

    function syncFieldValidity(input, isValid) {
        if (!input) {
            return;
        }
        input.classList.toggle('is-valid', isValid);
        input.classList.toggle('is-invalid', !isValid);
    }

    counselorNicInput?.addEventListener('input', function () {
        syncFieldValidity(counselorNicInput, isValidNic(counselorNicInput.value));
    });
    counselorPhoneInput?.addEventListener('input', function () {
        syncFieldValidity(counselorPhoneInput, isValidPhone(counselorPhoneInput.value));
    });

    const checkboxOther = document.getElementById('checkboxOther');
    const otherMarketingSurveyRow = document.getElementById('otherMarketingSurveyRow');

    if (checkboxOther) {
        checkboxOther.addEventListener('change', function() {
            if (otherMarketingSurveyRow) {
                otherMarketingSurveyRow.style.display = this.checked ? 'block' : 'none';
            }
        });
    }

    const finalRegisterBtn = document.getElementById('finalRegister');
    if (finalRegisterBtn) {
        finalRegisterBtn.addEventListener('click', async function(e) {
            e.preventDefault();

            const studentId = document.getElementById('studentId')?.value;
            const location = document.getElementById('location')?.value;
            const selectedCourse = document.getElementById('courseSearch');
            const courseId = selectedCourse?.value;
            const intakeId = document.getElementById('intakeId')?.value;
            const registrationFee = document.getElementById('registrationFee')?.value;
            const courseStartDate = document.getElementById('courseStartDate')?.value;

            if (!studentId) {
                showMessage('warning', 'Please search for a student first.');
                return;
            }

            if (!location || !courseId || !intakeId || !registrationFee || !courseStartDate) {
                showMessage('warning', 'Please fill in all required course fields.');
                return;
            }

            const sltEmployee = document.querySelector('input[name="slt_employee"]:checked')?.value;
            if (sltEmployee === 'yes') {
                const serviceNo = document.getElementById('serviceNo')?.value;
                if (!serviceNo) {
                    showMessage('warning', 'Please enter the service number.');
                    return;
                }
            } else if (sltEmployee === 'no') {
                const counselorName = document.getElementById('counselorName')?.value;
                const counselorNic = document.getElementById('counselorNic')?.value;
                const counselorPhone = document.getElementById('counselorPhone')?.value;

                if (!counselorName || !counselorNic || !counselorPhone) {
                    showMessage('warning', 'Please fill in all counselor details.');
                    return;
                }
                if (!isValidNic(counselorNic)) {
                    syncFieldValidity(counselorNicInput, false);
                    showMessage('warning', 'Please enter a valid counselor NIC.');
                    return;
                }
                if (!isValidPhone(counselorPhone)) {
                    syncFieldValidity(counselorPhoneInput, false);
                    showMessage('warning', 'Please enter a valid counselor phone number.');
                    return;
                }
            }

            const marketingOptions = [];
            const marketingCheckboxes = document.querySelectorAll('#checkboxLinkedIn, #checkboxFacebook, #checkboxRadio, #checkboxTV, #checkboxOther');
            marketingCheckboxes.forEach(checkbox => {
                if (checkbox.checked && checkbox.value) {
                    if (checkbox.id === 'checkboxOther') {
                        const otherValue = document.getElementById('marketing_survey_other')?.value;
                        if (otherValue) {
                            marketingOptions.push(otherValue);
                        }
                    } else {
                        marketingOptions.push(checkbox.value);
                    }
                }
            });

            const formData = {
                studentId: studentId,
                course: courseId,
                location: location,
                sltEmployee: sltEmployee,
                serviceNo: document.getElementById('serviceNo')?.value || '',
                counselorName: document.getElementById('counselorName')?.value || '',
                counselorNic: document.getElementById('counselorNic')?.value || '',
                counselorPhone: document.getElementById('counselorPhone')?.value || '',
                options: marketingOptions.join(', ').trim(),
                surveyNo: marketingOptions.length,
                registrationFee: registrationFee,
                courseStartDate: courseStartDate,
                intakeId: intakeId
            };

            setLoading(true);
            showMessage('info', 'Submitting registration...');

            try {
                const response = await fetch('{{ url('/store-course-registration') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify(formData)
                });

                let data = null;
                let responseText = null;

                try {
                    data = await response.json();
                } catch (parseError) {
                    responseText = await response.text();
                }

                if (response.ok && data && data.success) {
                    showMessage('success', escapeHtml(data.message || 'Registration completed successfully!'));
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    let errorMessage = 'Registration failed. Please try again.';
                    let errorDetails = '';

                    if (data) {
                        if (data.message) {
                            errorMessage = data.message;
                        }
                        if (data.errors) {
                            errorDetails = Object.values(data.errors)
                                .flat()
                                .map(item => escapeHtml(String(item)))
                                .join('<br>');
                        }
                    } else if (responseText) {
                        errorDetails = escapeHtml(responseText);
                    }

                    if (!errorMessage && response.statusText) {
                        errorMessage = response.statusText;
                    }

                    showMessage('danger', escapeHtml(errorMessage) + (errorDetails ? '<br>' + errorDetails : ''));
                    console.error('Registration failed:', { status: response.status, data, responseText });
                }
            } catch (error) {
                console.error('Registration error:', error);
                showMessage('danger', 'An error occurred while submitting the registration. Please try again.');
            } finally {
                setLoading(false);
            }
        });
    }

    window.redirectToEligibility = function () {
        const nic = nicInput ? nicInput.value.trim() : '';
        const courseId = courseSelect && courseSelect.value ? courseSelect.value : '';
        const courseName = courseSelect && courseSelect.selectedIndex >= 0 ? courseSelect.options[courseSelect.selectedIndex].text : '';
        const params = new URLSearchParams();
        if (nic) {
            params.set('nic', nic);
        }
        if (courseId) {
            params.set('course_id', courseId);
        }
        if (courseName) {
            params.set('course_name', courseName);
        }
        const query = params.toString();
        window.location.href = '{{ url('/eligibility-registration') }}' + (query ? ('?' + query) : '');
    };
});
</script>
@endpush

@endsection
