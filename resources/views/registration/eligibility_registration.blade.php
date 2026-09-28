@extends('inc.app')

@section('title', 'NEBULA | Eligibility & Registration')

@section('content')
<style nonce="{{ $cspNonce }}">
    .eligibility-registration-page,
    .eligibility-registration-page .card,
    .eligibility-registration-page .card-body {
        min-width: 0;
        max-width: 100%;
        overflow: visible;
    }
    body:has(.eligibility-registration-page) .body-wrapper > .container-fluid {
        overflow: visible;
    }
    .eligibility-registration-page [class*="col-"] {
        min-width: 0;
    }
    .eligibility-registration-page .form-select,
    .eligibility-registration-page .form-control,
    .eligibility-registration-page textarea.form-control,
    .eligibility-registration-page .nebula-select,
    .eligibility-registration-page .nebula-select-toggle {
        width: 100%;
        max-width: 100%;
    }
    .eligibility-registration-page .table-responsive {
        width: 100%;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }
    .eligibility-results-table {
        margin-bottom: 0;
    }
    .eligibility-results-table th,
    .eligibility-results-table td,
    .eligibility-exam-table th,
    .eligibility-exam-table td {
        word-break: break-word;
        vertical-align: middle;
    }
    .eligibility-actions,
    .eligibility-options {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    .eligibility-actions hr {
        flex: 1 0 100%;
        margin: 0.25rem 0 0.5rem;
    }
    .eligibility-toast {
        max-width: min(360px, calc(100vw - 1.5rem));
    }
    #registerForm .btn[type="submit"] {
        min-width: 10rem;
    }
    @media (max-width: 767.98px) {
        .eligibility-registration-page h2 {
            font-size: 1.35rem;
        }
        .eligibility-registration-page .card-body {
            padding: 1rem 0.75rem;
        }
        .eligibility-registration-page .card .card .card-body {
            padding: 0.85rem 0.7rem;
        }
        .eligibility-registration-page .form-control,
        .eligibility-registration-page .form-select,
        .eligibility-registration-page textarea.form-control,
        .eligibility-registration-page .nebula-select-toggle {
            font-size: 16px;
        }
        .eligibility-registration-page .col-form-label {
            text-align: left !important;
            padding-bottom: 0.2rem;
        }
        .eligibility-actions .btn,
        .eligibility-options .btn,
        #registerForm .btn[type="submit"],
        .eligibility-registration-page .modal-footer .btn {
            width: 100%;
        }
        .eligibility-results-table thead {
            display: none;
        }
        .eligibility-results-table,
        .eligibility-results-table tbody,
        .eligibility-results-table tr,
        .eligibility-results-table td {
            display: block;
            width: 100%;
        }
        .eligibility-results-table tbody tr {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 8px 12px 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }
        .eligibility-results-table td {
            border: 0;
            padding: 0.45rem 0;
        }
        .eligibility-results-table td[data-label]::before {
            content: attr(data-label);
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        #specialApprovalModal .modal-body {
            padding: 0.9rem;
        }
    }
</style>
    <div class="container-fluid px-2 px-md-3 eligibility-registration-page">
        <div class="card">
            <div class="card-body">
                <h2 class="text-center mb-4">Eligibility & Registration</h2>
                <hr>
                <form id="eligibilityForm" class="eligibility-search">
                    <h5 class="mb-3">Eligibility Search</h5>
                    <div class="row mx-0 align-items-center mb-3">
                        <label for="nic" class="col-md-2 col-form-label">NIC<span class="text-danger">*</span></label>
                        <div class="col-md-8">
                            <input type="text" class="form-control" id="nic" name="nic"
                                placeholder="Enter NIC" autocomplete="off">
                        </div>
                        <div class="col-md-2 mt-2 mt-md-0">
                            <button type="button" class="btn btn-primary w-100" id="searchEligibilityBtn">Search</button>
                        </div>
                    </div>
                    <div class="row mx-0 mb-3">
                        <label for="course" class="col-md-2 col-form-label">Course<span class="text-danger">*</span></label>
                        <div class="col-md-10">
                            <select class="form-select filter-param" id="course" name="course" disabled>
                                <option selected disabled value="">Select a course</option>
                            </select>
                        </div>
                    </div>
                    <hr class="my-4">
                </form>
                <div class="mt-4" id="resultsTableSection" style="display:none;">
                    <h5 class="mb-3">Eligibility Results</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered eligibility-results-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Registration Number</th>
                                    <th>Student Name</th>
                                    <th>Approval Status</th>
                                </tr>
                            </thead>
                            <tbody id="resultsTableBody">
                            </tbody>
                        </table>
                    </div>
                    <hr class="my-4">
                </div>
                <div class="mt-4" id="studentDetailsSection" style="display:none;">
                    <h5 class="mb-3">Student Details</h5>
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body bg-light">
                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Full Name</label>
                                <div class="col-md-10">
                                    <input type="text" class="form-control" id="studentFullNameInput" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">NIC</label>
                                <div class="col-md-10">
                                    <input type="text" class="form-control" id="studentNICInput" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div id="olExamSection" style="display:none;">
                    <hr class="my-4">
                    <h5 class="mb-3">O/L Exam Details</h5>
                    <div class="card mb-4 shadow-sm" id="olExamCard">
                        <div class="card-body bg-info-subtle">
                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Exam Type</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="olExamTypeInput" readonly>
                                </div>
                                <label class="col-md-2 col-form-label">Exam Year</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="olExamYearInput" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Subjects & Results</label>
                                <div class="col-md-10">
                                    <div class="table-responsive">
                                    <table class="table table-bordered mt-3 bg-white border mb-0 eligibility-exam-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Subject</th>
                                                <th>Result</th>
                                            </tr>
                                        </thead>
                                        <tbody id="olSubjectsTableBody"></tbody>
                                    </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    <div id="alExamSection" style="display:none;">
                    <hr class="my-4">
                    <h5 class="mb-3">A/L Exam Details</h5>
                    <div class="card mb-4 shadow-sm" id="alExamCard">
                        <div class="card-body bg-success-subtle">
                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Exam Type</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="alExamTypeInput" readonly>
                                </div>
                                <label class="col-md-2 col-form-label">Exam Year</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="alExamYearInput" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Stream</label>
                                <div class="col-md-4">
                                    <input type="text" class="form-control" id="alExamStreamInput" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Subjects & Results</label>
                                <div class="col-md-10">
                                    <div class="table-responsive">
                                    <table class="table table-bordered mt-3 bg-white border mb-0 eligibility-exam-table">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Subject</th>
                                                <th>Result</th>
                                            </tr>
                                        </thead>
                                        <tbody id="alSubjectsTableBody"></tbody>
                                    </table>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-md-2 col-form-label">Remarks</label>
                                <div class="col-md-10">
                                    <input type="text" class="form-control" id="alRemarksInput" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                    <div id="courseEntrySection" style="display:none;">
                    <hr class="my-4">
                    <h5 class="mb-3">Course & Entry Qualifications</h5>
                    <div class="card mb-4 shadow-sm">
                        <div class="card-body bg-light">
                            <div id="courseEntryFields">
                                <div class="row mb-3">
                                    <label class="col-md-2 col-form-label">Course</label>
                                    <div class="col-md-10">
                                        <input type="text" class="form-control" id="selectedCourseNameInput" readonly>
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <label class="col-md-2 col-form-label">Entry Qualifications</label>
                                    <div class="col-md-10">
                                        <textarea class="form-control" id="entryQualificationsInput" readonly rows="4"
                                            style="resize: vertical;"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="mb-4 eligibility-actions" id="eligibilityActionButtons" style="display:none;">
                    <hr class="w-100 my-2">
                    <button class="btn btn-success" id="eligibleBtn">Eligible</button>
                    <button class="btn btn-danger" id="notEligibleBtn">Not Eligible</button>
                </div>
                <div class="mb-4 eligibility-options" id="notEligibleOptions" style="display:none;">
                    <button class="btn btn-outline-primary" id="registerAnotherCourseBtn">Register for Another Course</button>
                    <button class="btn btn-outline-warning" id="specialApprovalBtn">Special Approval</button>
                </div>
                <div id="registerSection" class="card mb-4 shadow-sm" style="display:none;">
                    <div class="card-body bg-light">
                        <h5 class="mb-3 text-center">Student Register For Course</h5>
                        <form id="registerForm">
                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label">Student NIC</label>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" id="inlineStudentNIC" name="nic" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label">Student Registration Number</label>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" id="inlineStudentRegNo"
                                        name="registration_number" readonly>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label">Intake</label>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" id="intake" name="intake" readonly>
                                    <input type="hidden" id="inlineIntakeId" name="intake_id">
                                </div>
                            </div>
                            <div class="row mb-3">
                                <label class="col-md-4 col-form-label">Course Registration ID</label>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" id="inlineCourseRegId"
                                        name="course_registration_id" readonly>
                                </div>
                            </div>
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary px-5" id="registerSubmitBtn">Register</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script nonce="{{ $cspNonce }}">
            window.showToast = function (message, type = 'success') {
                const toastEl = document.getElementById('mainToast');
                const toastBody = document.getElementById('mainToastBody');
                if (!toastEl || !toastBody) {
                    return;
                }
                toastBody.textContent = message;
                toastEl.className = 'toast align-items-center border-0 text-bg-' + (type === 'success' ? 'success' : (type === 'danger' ? 'danger' : (type === 'warning' ? 'warning' : 'primary')));
                const toast = new bootstrap.Toast(toastEl, { delay: 2500 });
                toast.show();
            };

            document.addEventListener('DOMContentLoaded', function () {
                const nicInput = document.getElementById('nic');
                const courseSelect = document.getElementById('course');
                const searchBtn = document.getElementById('searchEligibilityBtn');
                const resultsTableSection = document.getElementById('resultsTableSection');
                const csrfToken = '{{ csrf_token() }}';
                let pendingCourseSelection = null;

                function escapeHtml(text) {
                    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
                    return String(text ?? '').replace(/[&<>"']/g, function (match) {
                        return map[match];
                    });
                }

                function shortLocation(value) {
                    const text = String(value || '');
                    if (/peradeniya/i.test(text)) return 'Peradeniya';
                    if (/moratuwa/i.test(text)) return 'Moratuwa';
                    if (/welisara/i.test(text)) return 'Welisara';
                    return text;
                }

                function resetAndDisable(select, placeholder) {
                    select.innerHTML = '<option selected disabled value="">' + placeholder + '</option>';
                    select.disabled = true;
                }

                function hideDetails() {
                    document.getElementById('studentDetailsSection').style.display = 'none';
                    document.getElementById('eligibilityActionButtons').style.display = 'none';
                    document.getElementById('notEligibleOptions').style.display = 'none';
                    document.getElementById('registerSection').style.display = 'none';
                    document.getElementById('courseEntrySection').style.display = 'none';
                    resultsTableSection.style.display = 'none';
                    document.getElementById('resultsTableBody').innerHTML = '';
                }

                function renderResults(students) {
                    const tbody = document.getElementById('resultsTableBody');
                    tbody.innerHTML = '';
                    if (!Array.isArray(students) || students.length === 0) {
                        resultsTableSection.style.display = 'none';
                        return;
                    }
                    students.forEach(function (student) {
                        tbody.insertAdjacentHTML('beforeend',
                            '<tr>' +
                            '<td data-label="Registration Number">' + escapeHtml(student.registration_number || 'N/A') + '</td>' +
                            '<td data-label="Student Name">' + escapeHtml(student.name || 'N/A') + '</td>' +
                            '<td data-label="Approval Status">' + escapeHtml(student.approval_status || 'N/A') + '</td>' +
                            '</tr>'
                        );
                    });
                    resultsTableSection.style.display = '';
                }

                function renderSubjects(tbodyId, subjects) {
                    const tbody = document.getElementById(tbodyId);
                    if (!tbody) {
                        return;
                    }
                    tbody.innerHTML = '';
                    if (!Array.isArray(subjects) || subjects.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="2">N/A</td></tr>';
                        return;
                    }
                    subjects.forEach(function (sub) {
                        tbody.insertAdjacentHTML('beforeend', '<tr><td>' + escapeHtml(sub.subject) + '</td><td>' + escapeHtml(sub.result) + '</td></tr>');
                    });
                }

                function jsonHeaders() {
                    return {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    };
                }

                function loadCoursesForNic(nic, courseIdToSelect) {
                    resetAndDisable(courseSelect, 'Select a course');
                    hideDetails();
                    if (!nic) {
                        showToast('Please enter a NIC.', 'warning');
                        return;
                    }

                    fetch('{{ url('/get-registered-courses-by-nic') }}?nic=' + encodeURIComponent(nic), {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (!data.success) {
                                showToast(data.message || 'Could not load courses.', 'warning');
                                return;
                            }
                            if (!data.courses || data.courses.length === 0) {
                                showToast('No registered courses found for this NIC.', 'warning');
                                return;
                            }

                            courseSelect.innerHTML = '<option selected disabled value="">Select a course</option>';
                            data.courses.forEach(function (course) {
                                const label = course.location
                                    ? course.course_name + ' (' + course.location + ')'
                                    : course.course_name;
                                courseSelect.add(new Option(label, course.course_id));
                            });
                            courseSelect.disabled = false;

                            const targetId = courseIdToSelect || pendingCourseSelection;
                            if (targetId) {
                                for (let i = 0; i < courseSelect.options.length; i += 1) {
                                    if (String(courseSelect.options[i].value) === String(targetId)) {
                                        courseSelect.selectedIndex = i;
                                        pendingCourseSelection = null;
                                        courseSelect.dispatchEvent(new Event('change', { bubbles: true }));
                                        break;
                                    }
                                }
                            }
                        })
                        .catch(function () {
                            showToast('Could not load courses. Please try again.', 'danger');
                        });
                }

                function loadStudentDetails(nic, courseId) {
                    fetch('{{ url('/get-student-exam-details-by-nic-course') }}', {
                        method: 'POST',
                        headers: jsonHeaders(),
                        body: JSON.stringify({ nic: nic, course_id: courseId })
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (!data.success || !data.student) {
                                hideDetails();
                                showToast(data.message || 'Student details not found.', 'warning');
                                return;
                            }

                            document.getElementById('studentFullNameInput').value = data.student.full_name || '';
                            document.getElementById('studentNICInput').value = data.student.nic || '';
                            document.getElementById('studentDetailsSection').style.display = '';
                            document.getElementById('eligibilityActionButtons').style.display = '';

                            if (data.student.ol) {
                                document.getElementById('olExamTypeInput').value = data.student.ol.type || '';
                                document.getElementById('olExamYearInput').value = data.student.ol.year || '';
                                renderSubjects('olSubjectsTableBody', data.student.ol.subjects || []);
                                document.getElementById('olExamSection').style.display = '';
                            } else {
                                document.getElementById('olExamSection').style.display = 'none';
                            }

                            if (data.student.al) {
                                document.getElementById('alExamTypeInput').value = data.student.al.type || '';
                                document.getElementById('alExamYearInput').value = data.student.al.year || '';
                                document.getElementById('alExamStreamInput').value = data.student.al.stream || '';
                                renderSubjects('alSubjectsTableBody', data.student.al.subjects || []);
                                document.getElementById('alRemarksInput').value = data.student.al.remarks || '';
                                document.getElementById('alExamSection').style.display = '';
                            } else {
                                document.getElementById('alExamSection').style.display = 'none';
                            }

                            fetch('{{ url('/get-course-entry-qualification') }}?course_id=' + encodeURIComponent(courseId), {
                                headers: { 'Accept': 'application/json' }
                            })
                                .then(function (response) { return response.json(); })
                                .then(function (courseData) {
                                    document.getElementById('selectedCourseNameInput').value = (courseData.course && courseData.course.course_name) || '';
                                    document.getElementById('entryQualificationsInput').value = (courseData.course && courseData.course.entry_qualification) || 'N/A';
                                    document.getElementById('courseEntrySection').style.display = '';
                                })
                                .catch(function () {
                                    document.getElementById('selectedCourseNameInput').value = '';
                                    document.getElementById('entryQualificationsInput').value = 'N/A';
                                    document.getElementById('courseEntrySection').style.display = '';
                                });

                            window.lastEligibleStudent = data.student;
                            window.lastEligibleStudent.course_id = courseId;
                            document.getElementById('inlineCourseRegId').value = data.student.course_registration_id || '';
                        })
                        .catch(function () {
                            hideDetails();
                            showToast('Failed to load student details.', 'danger');
                        });
                }

                function fillIntakeFields(student) {
                    const intakeInput = document.getElementById('intake');
                    const intakeIdInput = document.getElementById('inlineIntakeId');
                    intakeInput.readOnly = true;

                    if (student && student.intake_id) {
                        intakeIdInput.value = student.intake_id;
                        intakeInput.value = student.intake_batch || '';
                        return Promise.resolve(student.intake_id);
                    }

                    const courseId = courseSelect.value;
                    const location = shortLocation(student && student.location);
                    if (!courseId || !location) {
                        intakeInput.value = '';
                        intakeIdInput.value = '';
                        return Promise.resolve('');
                    }

                    return fetch('{{ url('/get-intakes') }}/' + encodeURIComponent(courseId) + '/' + encodeURIComponent(location), {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.intakes && data.intakes.length > 0) {
                                intakeInput.value = data.intakes[0].batch;
                                intakeIdInput.value = data.intakes[0].intake_id;
                                return data.intakes[0].intake_id;
                            }
                            intakeInput.value = '';
                            intakeIdInput.value = '';
                            return '';
                        })
                        .catch(function () {
                            intakeInput.value = '';
                            intakeIdInput.value = '';
                            return '';
                        });
                }

                function fillNextCourseRegId(intakeId) {
                    const courseRegIdInput = document.getElementById('inlineCourseRegId');
                    if (!intakeId) {
                        courseRegIdInput.value = '';
                        courseRegIdInput.readOnly = true;
                        return;
                    }
                    fetch('{{ url('/get-next-course-registration-id') }}?intake_id=' + encodeURIComponent(intakeId), {
                        headers: { 'Accept': 'application/json' }
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            courseRegIdInput.value = (data.success && data.next_id) ? data.next_id : '';
                            courseRegIdInput.readOnly = true;
                        })
                        .catch(function () {
                            courseRegIdInput.value = '';
                            courseRegIdInput.readOnly = true;
                        });
                }

                document.getElementById('eligibilityForm').addEventListener('submit', function (event) {
                    event.preventDefault();
                    loadCoursesForNic(nicInput.value.trim());
                });
                searchBtn.addEventListener('click', function () {
                    loadCoursesForNic(nicInput.value.trim());
                });
                nicInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Enter') {
                        event.preventDefault();
                        loadCoursesForNic(nicInput.value.trim());
                    }
                });

                courseSelect.addEventListener('change', function () {
                    const nic = nicInput.value.trim();
                    const courseId = courseSelect.value;
                    hideDetails();
                    if (!nic || !courseId) {
                        return;
                    }

                    fetch('{{ url('/get-eligible-students-by-nic') }}', {
                        method: 'POST',
                        headers: jsonHeaders(),
                        body: JSON.stringify({ nic: nic, course_id: courseId })
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (!data.success) {
                                showToast(data.message || 'No eligibility record found.', 'warning');
                                renderResults([]);
                            } else {
                                renderResults(data.students || []);
                            }
                            loadStudentDetails(nic, courseId);
                        })
                        .catch(function () {
                            loadStudentDetails(nic, courseId);
                        });
                });

                document.getElementById('notEligibleBtn').addEventListener('click', function () {
                    document.getElementById('notEligibleOptions').style.display = '';
                    document.getElementById('registerSection').style.display = 'none';
                });

                document.getElementById('eligibleBtn').addEventListener('click', function () {
                    document.getElementById('notEligibleOptions').style.display = 'none';
                    document.getElementById('inlineStudentNIC').value = document.getElementById('studentNICInput').value;
                    const student = window.lastEligibleStudent || {};
                    document.getElementById('inlineStudentRegNo').value = student.registration_number || student.student_id || '';
                    document.getElementById('registerSection').style.display = '';
                    const registerBtn = document.getElementById('registerSubmitBtn');
                    registerBtn.disabled = true;
                    fillIntakeFields(student).then(function (intakeId) {
                        if (student.course_registration_id) {
                            document.getElementById('inlineCourseRegId').value = student.course_registration_id;
                        } else {
                            fillNextCourseRegId(intakeId);
                        }
                        if (!intakeId) {
                            showToast('No intake found for this course and location.', 'warning');
                            registerBtn.disabled = false;
                            return;
                        }
                        registerBtn.disabled = false;
                    });
                });

                document.getElementById('registerForm').addEventListener('submit', function (e) {
                    e.preventDefault();
                    const nic = document.getElementById('inlineStudentNIC').value;
                    const courseId = courseSelect.value;
                    const intakeId = document.getElementById('inlineIntakeId').value;
                    if (!intakeId) {
                        showToast('Please ensure an intake is selected before registering.', 'danger');
                        return;
                    }

                    const submitBtn = this.querySelector('button[type="submit"]');
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Registering...';

                    fetch('{{ url('/register-eligible-student') }}', {
                        method: 'POST',
                        headers: jsonHeaders(),
                        body: JSON.stringify({ nic: nic, course_id: courseId, intake_id: intakeId })
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.success) {
                                showToast('Student registered successfully! Page will refresh.', 'success');
                                setTimeout(function () { window.location.reload(); }, 2000);
                            } else {
                                showToast(data.message || 'Registration failed. Please try again.', 'danger');
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = 'Register';
                            }
                        })
                        .catch(function () {
                            showToast('An unexpected error occurred. Please try again.', 'danger');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = 'Register';
                        });
                });

                document.getElementById('registerAnotherCourseBtn').addEventListener('click', function () {
                    window.location.href = '{{ route('course.registration') }}';
                });

                document.getElementById('specialApprovalBtn').addEventListener('click', function () {
                    const nic = document.getElementById('studentNICInput').value;
                    const courseId = courseSelect.value;
                    if (!nic || !courseId) {
                        showToast('Please enter NIC and select a course first', 'warning');
                        return;
                    }
                    document.getElementById('modalStudentNIC').value = nic;
                    document.getElementById('modalCourseName').value = document.getElementById('selectedCourseNameInput').value;
                    const modalElement = document.getElementById('specialApprovalModal');
                    bootstrap.Modal.getOrCreateInstance(modalElement).show();
                });

                document.getElementById('submitSpecialApprovalBtn').addEventListener('click', function () {
                    const form = document.getElementById('specialApprovalForm');
                    const formData = new FormData(form);
                    const nic = document.getElementById('modalStudentNIC').value;
                    const courseId = courseSelect.value;
                    if (!nic || !courseId) {
                        showToast('Please select a course first.', 'warning');
                        return;
                    }
                    if (!formData.get('special_approval_document') || !formData.get('special_approval_document').size) {
                        showToast('Please select a document to upload.', 'warning');
                        return;
                    }
                    formData.append('nic', nic);
                    formData.append('course_id', courseId);

                    const submitBtn = this;
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Submitting...';

                    fetch('{{ url('/send-special-approval-request') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': csrfToken },
                        body: formData
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (data) {
                            if (data.success) {
                                showToast('Special approval request has been sent successfully!', 'success');
                                bootstrap.Modal.getOrCreateInstance(document.getElementById('specialApprovalModal')).hide();
                                document.getElementById('eligibilityActionButtons').style.display = '';
                                document.getElementById('notEligibleOptions').style.display = 'none';
                                setTimeout(function () { window.location.reload(); }, 2000);
                            } else {
                                showToast(data.message || 'Failed to send special approval request', 'danger');
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = 'Submit Request';
                            }
                        })
                        .catch(function () {
                            showToast('An unexpected error occurred. Please try again.', 'danger');
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = 'Submit Request';
                        });
                });

                document.getElementById('specialApprovalModal').addEventListener('hidden.bs.modal', function () {
                    document.getElementById('specialApprovalForm').reset();
                    const submitBtn = document.getElementById('submitSpecialApprovalBtn');
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Submit Request';
                });

                const urlParams = new URLSearchParams(window.location.search);
                const nicFromUrl = urlParams.get('nic');
                const courseIdFromUrl = urlParams.get('course_id');
                if (nicFromUrl) {
                    nicInput.value = nicFromUrl;
                    pendingCourseSelection = courseIdFromUrl;
                    loadCoursesForNic(nicFromUrl, courseIdFromUrl);
                } else {
                    resetAndDisable(courseSelect, 'Select a course');
                }
            });
        </script>
    @endpush
    <!-- Toast Container -->
    <div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3 eligibility-toast" style="z-index: 9999">
        <div id="mainToast" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive"
            aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body" id="mainToastBody">
                    <!-- Message will go here -->
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                    aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Special Approval Document Upload Modal -->
    <div class="modal fade" id="specialApprovalModal" tabindex="-1" aria-labelledby="specialApprovalModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="specialApprovalModalLabel">Special Approval Request</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="specialApprovalForm">
                        <div class="row mb-3">
                            <label class="col-md-3 col-form-label fw-bold">Student NIC</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="modalStudentNIC" readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-md-3 col-form-label fw-bold">Course</label>
                            <div class="col-md-9">
                                <input type="text" class="form-control" id="modalCourseName" readonly>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-md-3 col-form-label fw-bold">Upload Document<span
                                    class="text-danger">*</span></label>
                            <div class="col-md-9">
                                <input type="file" class="form-control" id="specialApprovalDocument"
                                    name="special_approval_document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                                <small class="text-muted">Allowed formats: PDF, DOC, DOCX, JPG, JPEG, PNG (Max size:
                                    5MB)</small>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <label class="col-md-3 col-form-label fw-bold">Remarks</label>
                            <div class="col-md-9">
                                <textarea class="form-control" id="specialApprovalRemarks" name="remarks" rows="3"
                                    placeholder="Please provide any additional remarks for the special approval request..."></textarea>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="submitSpecialApprovalBtn">Submit Request</button>
                </div>
            </div>
        </div>
    </div>

@endsection
