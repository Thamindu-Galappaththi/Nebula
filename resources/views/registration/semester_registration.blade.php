@extends('inc.app')

@section('title', 'NEBULA | Semester Registration')

@section('content')
<style nonce="{{ $cspNonce }}">
    .semester-registration-page,
    .semester-registration-page .card,
    .semester-registration-page .card-body {
        min-width: 0;
        max-width: 100%;
        overflow: visible;
    }
    body:has(.semester-registration-page) .body-wrapper > .container-fluid {
        overflow: visible;
    }
    .semester-registration-page [class*="col-"] {
        min-width: 0;
    }
    .semester-registration-page .form-select,
    .semester-registration-page .form-control,
    .semester-registration-page textarea.form-control,
    .semester-registration-page .nebula-select,
    .semester-registration-page .nebula-select-toggle {
        width: 100%;
        max-width: 100%;
    }
    .semester-registration-page .table-responsive {
        width: 100%;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }
    .semester-status-tabs {
        flex-wrap: wrap;
        overflow: hidden;
        gap: 0.15rem;
    }
    .semester-status-tabs .nav-link {
        white-space: nowrap;
    }
    .semester-students-table th,
    .semester-students-table td {
        word-break: break-word;
        vertical-align: middle;
    }
    .semester-action-btns {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
    }
    .semester-toast {
        max-width: min(360px, calc(100vw - 1.5rem));
    }
    #studentSearch {
        max-width: 18rem;
    }
    @media (max-width: 767.98px) {
        .semester-registration-page h2 {
            font-size: 1.35rem;
        }
        .semester-registration-page .card-body {
            padding: 1rem 0.75rem;
        }
        .semester-registration-page .form-control,
        .semester-registration-page .form-select,
        .semester-registration-page textarea.form-control,
        .semester-registration-page .nebula-select-toggle {
            font-size: 16px;
        }
        .semester-registration-page .col-form-label {
            text-align: left !important;
            padding-bottom: 0.2rem;
        }
        #studentSearch {
            max-width: 100%;
        }
        .semester-status-tabs .nav-item {
            flex: 1 1 45%;
        }
        .semester-status-tabs .nav-link {
            text-align: center;
        }
        .semester-students-table thead {
            display: none;
        }
        .semester-students-table,
        .semester-students-table tbody,
        .semester-students-table tr,
        .semester-students-table td {
            display: block;
            width: 100%;
        }
        .semester-students-table tbody tr[data-student-id] {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 8px 12px 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }
        .semester-students-table td {
            border: 0;
            padding: 0.45rem 0;
        }
        .semester-students-table td[data-label]::before {
            content: attr(data-label);
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .semester-students-table td.semester-action {
            padding-top: 0.75rem;
        }
        .semester-action-btns .btn {
            flex: 1 1 calc(50% - 0.35rem);
            min-height: 38px;
        }
        .semester-registration-page .modal-footer .btn {
            width: 100%;
        }
        #clearanceCheckModal .modal-body,
        #specialApprovalModal .modal-body {
            padding: 0.9rem;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 semester-registration-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Semester Registration</h2>
            <hr>
            <form id="courseForm" method="POST" action="{{ route('semester.registration.store') }}">
                @csrf
                <input type="hidden" name="location" id="location_hidden">
                <input type="hidden" name="specialization" id="specialization_hidden">
                <div class="mb-3 row mx-0">
                    <label for="location" class="col-md-2 col-form-label">Location <span class="text-danger">*</span></label>
                    <div class="col-md-10">
                        <select class="form-select" id="location" name="location" required>
                            <option selected disabled value="">Select a Location</option>
                            <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                            <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                            <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3 row mx-0">
                    <label for="course_id" class="col-md-2 col-form-label">Course <span class="text-danger">*</span></label>
                    <div class="col-md-10">
                        <select class="form-select" id="course_id" name="course_id" required disabled>
                            <option value="">Select Course</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3 row mx-0">
                    <label for="intake_id" class="col-md-2 col-form-label">Intake <span class="text-danger">*</span></label>
                    <div class="col-md-10">
                        <select class="form-select" id="intake_id" name="intake_id" required disabled>
                            <option value="">Select Intake</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3 row mx-0">
                    <label for="semester_id" class="col-md-2 col-form-label">Semester <span class="text-danger">*</span></label>
                    <div class="col-md-10">
                        <select class="form-select" id="semester_id" name="semester_id" required disabled>
                            <option value="">Select Semester</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3 row mx-0" id="specialization_row" style="display:none;">
                    <label for="specialization" class="col-md-2 col-form-label">Specialization</label>
                    <div class="col-md-10">
                        <select class="form-select" id="specialization" name="specialization">
                            <option value="">Select Specialization</option>
                        </select>
                        <small class="text-muted">Select a specialization to load matching students.</small>
                    </div>
                </div>
                <div class="mb-3 row mx-0" id="students_table_row" style="display:none;">
                    <div class="col-12">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                            <ul class="nav nav-tabs semester-status-tabs mb-0" id="statusTabs">
                                <li class="nav-item">
                                    <a class="nav-link active" href="#" data-status="all">All</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-status="pending">Not Registered</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-status="registered">Registered</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-status="holding">Hold</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" href="#" data-status="terminated">Terminated</a>
                                </li>
                            </ul>
                            <input type="search" class="form-control" id="studentSearch" placeholder="Search students..." autocomplete="off">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-bordered semester-students-table" id="students_table">
                                <thead>
                                    <tr>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>NIC</th>
                                        <th>Status</th>
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
                    <button type="submit" class="btn btn-primary" id="updateRegistrationBtn">Update Registration</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3 semester-toast" style="z-index: 9999">
    <div class="toast-container"></div>
</div>

<div class="modal fade" id="clearanceCheckModal" tabindex="-1" aria-labelledby="clearanceCheckModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="clearanceCheckModalLabel">Student has existing clearances</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>The selected student has one or more clearance records. Please review them before terminating. Do you still want to proceed?</p>
                <ul id="clearanceList" class="list-unstyled"></ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" id="confirmTerminateBtn" class="btn btn-danger">Yes, Terminate</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="specialApprovalModal" tabindex="-1" aria-labelledby="specialApprovalModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down">
        <form id="specialApprovalForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="specialApprovalModalLabel">Special Approval (DGM) Required</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="sa_student_id">
                <div class="mb-3">
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea id="sa_reason" class="form-control" rows="4" required
                        placeholder="Explain why this terminated student should be re-registered"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Attachment (optional)</label>
                    <input type="file" id="sa_file" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.webp">
                    <small class="text-muted">Attach any supporting document (max ~2MB recommended).</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Attach to Request</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
    document.addEventListener('DOMContentLoaded', function () {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
        const locationSelect = document.getElementById('location');
        const courseSelect = document.getElementById('course_id');
        const intakeSelect = document.getElementById('intake_id');
        const semesterSelect = document.getElementById('semester_id');
        const specializationSelect = document.getElementById('specialization');
        const studentsTableBody = document.querySelector('#students_table tbody');
        const studentSearch = document.getElementById('studentSearch');
        const saModalEl = document.getElementById('specialApprovalModal');
        const saFormEl = document.getElementById('specialApprovalForm');
        const saStudentIdEl = document.getElementById('sa_student_id');
        const saReasonEl = document.getElementById('sa_reason');
        const saFileEl = document.getElementById('sa_file');
        const saModal = saModalEl ? bootstrap.Modal.getOrCreateInstance(saModalEl) : null;
        const specialApprovalPayload = {};
        let loadedStudents = [];
        let courseSpecializations = [];
        let specializationsPromise = Promise.resolve([]);
        let activeStatusFilter = 'all';

        const statusLabels = {
            pending: 'Not registered',
            registered: 'Registered',
            holding: 'Hold',
            terminated: 'Terminated'
        };

        function escapeHtml(text) {
            const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
            return String(text ?? '').replace(/[&<>"']/g, function (match) {
                return map[match];
            });
        }

        function resetAndDisable(select, placeholder) {
            select.innerHTML = '';
            const option = new Option(placeholder, '', true, true);
            option.disabled = true;
            select.add(option);
            select.disabled = true;
        }

        function resetSpecialization() {
            specializationSelect.innerHTML = '';
            specializationSelect.add(new Option('Select Specialization', '', true, true));
            document.getElementById('specialization_row').style.display = 'none';
            document.getElementById('specialization_hidden').value = '';
            courseSpecializations = [];
        }

        function hideStudents() {
            loadedStudents = [];
            studentsTableBody.innerHTML = '';
            document.getElementById('students_table_row').style.display = 'none';
            if (studentSearch) {
                studentSearch.value = '';
            }
        }

        function decodeSpecializations(value) {
            let list = [];
            if (Array.isArray(value)) {
                list = value;
            } else if (typeof value === 'string' && value !== '') {
                try {
                    const decoded = JSON.parse(value);
                    list = Array.isArray(decoded) ? decoded : [];
                } catch (e) {
                    list = [];
                }
            }
            return list.map(function (spec) {
                if (typeof spec === 'string') {
                    return spec.trim();
                }
                if (spec && typeof spec === 'object') {
                    return String(spec.name || spec.title || spec.specialization || '').trim();
                }
                return '';
            }).filter(Boolean);
        }

        function applySpecializations(specs) {
            courseSpecializations = specs;
            specializationSelect.innerHTML = '';
            specializationSelect.add(new Option('Select Specialization', '', true, true));
            if (!specs.length) {
                document.getElementById('specialization_row').style.display = 'none';
                document.getElementById('specialization_hidden').value = '';
                return;
            }
            specs.forEach(function (spec) {
                specializationSelect.add(new Option(spec, spec));
            });
            document.getElementById('specialization_row').style.display = '';
        }

        function loadCourseSpecializations(courseId) {
            return fetch('{{ url('/api/courses') }}/' + encodeURIComponent(courseId), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (String(courseSelect.value) !== String(courseId)) {
                        return courseSpecializations;
                    }
                    const specs = (data.success && data.course)
                        ? decodeSpecializations(data.course.specializations)
                        : [];
                    applySpecializations(specs);
                    return specs;
                })
                .catch(function () {
                    if (String(courseSelect.value) !== String(courseId)) {
                        return courseSpecializations;
                    }
                    applySpecializations([]);
                    return [];
                });
        }

        function showToast(message, type) {
            const container = document.querySelector('.semester-toast .toast-container') || document.querySelector('.toast-container');
            if (!container) {
                return;
            }
            container.innerHTML = '';
            const toast = document.createElement('div');
            toast.className = 'toast show';
            toast.setAttribute('role', 'alert');
            const header = document.createElement('div');
            header.className = 'toast-header bg-' + (type === 'success' ? 'success' : 'danger') + ' text-white';
            const strong = document.createElement('strong');
            strong.className = 'me-auto';
            strong.textContent = type === 'success' ? 'Success' : 'Error';
            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'btn-close btn-close-white';
            closeBtn.setAttribute('data-bs-dismiss', 'toast');
            header.appendChild(strong);
            header.appendChild(closeBtn);
            const body = document.createElement('div');
            body.className = 'toast-body';
            body.textContent = message;
            toast.appendChild(header);
            toast.appendChild(body);
            container.appendChild(toast);
            setTimeout(function () {
                const instance = bootstrap.Toast.getOrCreateInstance(toast, { delay: 4000 });
                instance.hide();
            }, 4000);
        }

        function fillSelect(select, placeholder, items, valueKey, labelKey) {
            select.innerHTML = '';
            const first = new Option(placeholder, '', true, true);
            first.disabled = true;
            select.add(first);
            items.forEach(function (item) {
                select.add(new Option(item[labelKey], item[valueKey]));
            });
            select.disabled = false;
        }

        locationSelect.addEventListener('change', function () {
            resetAndDisable(courseSelect, 'Select Course');
            resetAndDisable(intakeSelect, 'Select Intake');
            resetAndDisable(semesterSelect, 'Select Semester');
            hideStudents();
            resetSpecialization();
            document.getElementById('location_hidden').value = this.value;

            if (!locationSelect.value) {
                return;
            }

            fetch('{{ route('semester.registration.getCoursesByLocation') }}?location=' + encodeURIComponent(locationSelect.value), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success && data.courses && data.courses.length > 0) {
                        courseSelect.innerHTML = '';
                        const first = new Option('Select Course', '', true, true);
                        first.disabled = true;
                        courseSelect.add(first);
                        data.courses.forEach(function (course) {
                            courseSelect.add(new Option((course.course_type || '') + ' - ' + course.course_name, course.course_id));
                        });
                        courseSelect.disabled = false;
                    } else {
                        resetAndDisable(courseSelect, 'No courses available');
                        showToast(data.message || 'No courses available for this location.', 'error');
                    }
                })
                .catch(function () {
                    resetAndDisable(courseSelect, 'Select Course');
                    showToast('Could not load courses.', 'error');
                });
        });

        courseSelect.addEventListener('change', function () {
            resetAndDisable(intakeSelect, 'Select Intake');
            resetAndDisable(semesterSelect, 'Select Semester');
            hideStudents();
            resetSpecialization();

            if (!courseSelect.value || !locationSelect.value) {
                return;
            }

            specializationsPromise = loadCourseSpecializations(courseSelect.value);

            fetch('{{ route('semester.registration.getOngoingIntakes') }}?course_id=' + encodeURIComponent(courseSelect.value) + '&location=' + encodeURIComponent(locationSelect.value), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success && data.intakes && data.intakes.length > 0) {
                        fillSelect(intakeSelect, 'Select Intake', data.intakes, 'intake_id', 'batch');
                    } else {
                        resetAndDisable(intakeSelect, 'No intakes available');
                        showToast('No intakes available for this course.', 'error');
                    }
                })
                .catch(function () {
                    resetAndDisable(intakeSelect, 'Select Intake');
                    showToast('Could not load intakes.', 'error');
                });
        });

        intakeSelect.addEventListener('change', function () {
            resetAndDisable(semesterSelect, 'Select Semester');
            hideStudents();

            if (!courseSelect.value || !intakeSelect.value || !locationSelect.value) {
                return;
            }

            fetch('{{ route('semester.registration.getOpenSemesters') }}?course_id=' + encodeURIComponent(courseSelect.value) + '&intake_id=' + encodeURIComponent(intakeSelect.value) + '&location=' + encodeURIComponent(locationSelect.value), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (data.success && data.semesters && data.semesters.length > 0) {
                        semesterSelect.innerHTML = '';
                        const first = new Option('Select Semester', '', true, true);
                        first.disabled = true;
                        semesterSelect.add(first);
                        data.semesters.forEach(function (sem) {
                            const statusText = sem.status === 'active' ? ' (Active)' :
                                sem.status === 'upcoming' ? ' (Upcoming)' :
                                    sem.status === 'completed' ? ' (Completed)' : '';
                            semesterSelect.add(new Option((sem.semester_name || '') + statusText, sem.semester_id));
                        });
                        semesterSelect.disabled = false;
                    } else {
                        resetAndDisable(semesterSelect, 'No semesters available');
                        showToast('No semesters available for this intake.', 'error');
                    }
                })
                .catch(function () {
                    resetAndDisable(semesterSelect, 'Select Semester');
                    showToast('Could not load semesters.', 'error');
                });
        });

        semesterSelect.addEventListener('change', function () {
            hideStudents();
            if (!courseSelect.value || !intakeSelect.value || !semesterSelect.value) {
                return;
            }
            specializationsPromise.then(function (specs) {
                if (String(semesterSelect.value) === '') {
                    return;
                }
                if (specs.length > 0) {
                    document.getElementById('specialization_row').style.display = '';
                    if (specializationSelect.value) {
                        loadStudentsTable();
                    }
                    return;
                }
                loadStudentsTable();
            });
        });

        function renderStudents() {
            const query = (studentSearch.value || '').trim().toLowerCase();
            const filtered = loadedStudents.filter(function (student) {
                const matchesStatus = activeStatusFilter === 'all' || student.status === activeStatusFilter;
                if (!matchesStatus) {
                    return false;
                }
                if (!query) {
                    return true;
                }
                return [student.student_id, student.name, student.email, student.nic, student.status]
                    .join(' ')
                    .toLowerCase()
                    .includes(query);
            });

            if (!loadedStudents.length) {
                studentsTableBody.innerHTML = '<tr><td colspan="6" class="text-center">No eligible students found.</td></tr>';
                document.getElementById('students_table_row').style.display = '';
                return;
            }

            if (!filtered.length) {
                studentsTableBody.innerHTML = '<tr><td colspan="6" class="text-center">No students match this filter.</td></tr>';
                document.getElementById('students_table_row').style.display = '';
                return;
            }

            studentsTableBody.innerHTML = filtered.map(function (student) {
                const status = student.status || 'pending';
                const rowClass = status === 'terminated' ? 'table-danger' : (status === 'holding' ? 'table-warning' : '');
                return '<tr data-student-id="' + escapeHtml(student.student_id) + '" data-status="' + escapeHtml(status) + '" data-original-status="' + escapeHtml(student.original_status || status) + '" class="' + rowClass + '">' +
                    '<td data-label="Student ID">' + escapeHtml(student.student_id) + '</td>' +
                    '<td data-label="Name">' + escapeHtml(student.name) + '</td>' +
                    '<td data-label="Email">' + escapeHtml(student.email) + '</td>' +
                    '<td data-label="NIC">' + escapeHtml(student.nic) + '</td>' +
                    '<td data-label="Status" class="student-status">' + escapeHtml(statusLabels[status] || status) + '</td>' +
                    '<td data-label="Action" class="semester-action"><div class="semester-action-btns" role="group">' +
                    '<button type="button" class="btn btn-outline-success btn-sm toggle-selection' + (status === 'registered' ? ' active' : '') + '" data-action="registered">Register</button>' +
                    '<button type="button" class="btn btn-outline-secondary btn-sm toggle-selection' + (status === 'pending' ? ' active' : '') + '" data-action="pending">Not Register</button>' +
                    '<button type="button" class="btn btn-outline-warning btn-sm toggle-selection' + (status === 'holding' ? ' active' : '') + '" data-action="holding">Hold</button>' +
                    '<button type="button" class="btn btn-outline-danger btn-sm toggle-selection' + (status === 'terminated' ? ' active' : '') + '" data-action="terminated">Terminate</button>' +
                    '</div></td></tr>';
            }).join('');
            document.getElementById('students_table_row').style.display = '';
        }

        function loadStudentsTable() {
            hideStudents();
            if (!courseSelect.value || !intakeSelect.value || !semesterSelect.value) {
                return;
            }

            fetch('{{ route('semester.registration.getEligibleStudents') }}?course_id=' + encodeURIComponent(courseSelect.value) +
                '&intake_id=' + encodeURIComponent(intakeSelect.value) +
                '&semester_id=' + encodeURIComponent(semesterSelect.value) +
                '&location=' + encodeURIComponent(locationSelect.value) +
                '&specialization=' + encodeURIComponent(specializationSelect.value || ''), {
                headers: { 'Accept': 'application/json' }
            })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    loadedStudents = (data.success && Array.isArray(data.students) ? data.students : []).map(function (student) {
                        return Object.assign({}, student, { original_status: student.status });
                    });
                    renderStudents();
                })
                .catch(function () {
                    loadedStudents = [];
                    studentsTableBody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Error loading students.</td></tr>';
                    document.getElementById('students_table_row').style.display = '';
                    showToast('Could not load students.', 'error');
                });
        }

        document.querySelectorAll('#statusTabs .nav-link').forEach(function (tab) {
            tab.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelectorAll('#statusTabs .nav-link').forEach(function (item) {
                    item.classList.remove('active');
                });
                this.classList.add('active');
                activeStatusFilter = this.dataset.status || 'all';
                renderStudents();
            });
        });

        studentSearch.addEventListener('input', function () {
            renderStudents();
        });

        specializationSelect.addEventListener('change', function () {
            document.getElementById('specialization_hidden').value = this.value;
            if (semesterSelect.value) {
                loadStudentsTable();
            }
        });

        function applyRowStatus(row, action) {
            const studentId = row.dataset.studentId;
            const student = loadedStudents.find(function (item) {
                return String(item.student_id) === String(studentId);
            });
            if (student) {
                student.status = action;
            }
            row.querySelectorAll('.toggle-selection').forEach(function (button) {
                button.classList.remove('active');
            });
            const activeBtn = row.querySelector('.toggle-selection[data-action="' + action + '"]');
            if (activeBtn) {
                activeBtn.classList.add('active');
            }
            row.dataset.status = action;
            const statusCell = row.querySelector('.student-status');
            if (statusCell) {
                statusCell.textContent = statusLabels[action] || action;
            }
            row.classList.toggle('table-danger', action === 'terminated');
            row.classList.toggle('table-warning', action === 'holding');
        }

        document.addEventListener('click', function (e) {
            if (!e.target.classList.contains('toggle-selection')) {
                return;
            }

            const btn = e.target;
            const row = btn.closest('tr');
            const action = btn.dataset.action;
            const originalStatus = row.dataset.originalStatus;
            const studentId = row.dataset.studentId;

            if (action === 'registered' && originalStatus === 'terminated') {
                if (saModal) {
                    saStudentIdEl.value = studentId;
                    saReasonEl.value = '';
                    if (saFileEl) {
                        saFileEl.value = '';
                    }
                    saModal.show();
                }
                return;
            }

            if (action === 'terminated') {
                const payload = new FormData();
                payload.append('student_id', studentId);
                payload.append('course_id', courseSelect.value || '');
                payload.append('intake_id', intakeSelect.value || '');

                fetch('{{ route('semester.registration.checkClearances') }}', {
                    method: 'POST',
                    body: payload,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                })
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.success) {
                            showToast(data.message || 'Could not check clearances', 'error');
                            return;
                        }

                        const hasClearance = (data.clearances || []).some(function (item) {
                            return item.status && item.status !== 'none';
                        });
                        const hasPayments = data.pending_payments && data.pending_payments.length > 0;
                        if (!hasClearance && !hasPayments) {
                            applyRowStatus(row, 'terminated');
                            return;
                        }

                        const list = document.getElementById('clearanceList');
                        list.innerHTML = '';
                        (data.clearances || []).forEach(function (item) {
                            const li = document.createElement('li');
                            li.className = 'mb-2';
                            li.textContent = item.label + ': ' + item.status_text + (item.note ? ' (' + item.note + ')' : '');
                            list.appendChild(li);
                        });

                        if (hasPayments) {
                            const pTitle = document.createElement('li');
                            pTitle.className = 'mt-2';
                            pTitle.textContent = 'Pending Payments';
                            list.appendChild(pTitle);
                            data.pending_payments.forEach(function (payment) {
                                const item = document.createElement('li');
                                item.className = 'mb-2';
                                item.textContent = payment.formatted + ' — ' + payment.description + (payment.due_date ? ' (Due: ' + payment.due_date + ')' : '');
                                list.appendChild(item);
                            });
                        }

                        document.getElementById('confirmTerminateBtn').dataset.studentId = studentId;
                        bootstrap.Modal.getOrCreateInstance(document.getElementById('clearanceCheckModal')).show();
                    })
                    .catch(function () {
                        showToast('Failed to check clearances', 'error');
                    });
                return;
            }

            applyRowStatus(row, action);
        });

        document.getElementById('confirmTerminateBtn').addEventListener('click', function () {
            const studentId = this.dataset.studentId;
            if (!studentId) {
                return;
            }
            const row = document.querySelector('#students_table tbody tr[data-student-id="' + studentId + '"]');
            if (row) {
                applyRowStatus(row, 'terminated');
            }
            const modalEl = document.getElementById('clearanceCheckModal');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) {
                modal.hide();
            }
        });

        if (saFormEl) {
            saFormEl.addEventListener('submit', function (e) {
                e.preventDefault();
                const studentId = saStudentIdEl.value;
                const reason = saReasonEl.value.trim();
                const file = saFileEl && saFileEl.files && saFileEl.files[0] ? saFileEl.files[0] : null;
                if (!reason) {
                    showToast('Please provide a reason.', 'error');
                    return;
                }
                specialApprovalPayload[studentId] = { reason: reason, file: file };
                const row = document.querySelector('#students_table tbody tr[data-student-id="' + studentId + '"]');
                if (row) {
                    applyRowStatus(row, 'registered');
                    row.classList.remove('table-danger');
                    row.classList.add('table-warning');
                }
                saModal.hide();
            });
        }

        document.getElementById('courseForm').addEventListener('submit', function (e) {
            e.preventDefault();

            const selectedStudents = [];
            const allowedStatuses = ['pending', 'registered', 'holding', 'terminated'];
            loadedStudents.forEach(function (student) {
                const status = String(student.status || '').trim().toLowerCase();
                if (student.student_id && allowedStatuses.includes(status)) {
                    selectedStudents.push({
                        student_id: student.student_id,
                        status: status,
                        original_status: String(student.original_status || '').trim().toLowerCase()
                    });
                }
            });

            if (selectedStudents.length === 0) {
                showToast('Please select at least one student with a valid status.', 'error');
                return;
            }
            if (!locationSelect.value) {
                showToast('Please select a location.', 'error');
                return;
            }
            if (!courseSelect.value) {
                showToast('Please select a course.', 'error');
                return;
            }
            if (!intakeSelect.value) {
                showToast('Please select an intake.', 'error');
                return;
            }
            if (!semesterSelect.value) {
                showToast('Please select a semester.', 'error');
                return;
            }

            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Registering...';

            const formData = new FormData(this);
            formData.set('location', locationSelect.value || '');
            formData.set('specialization', specializationSelect.value || '');
            formData.set('course_id', courseSelect.value || '');
            formData.set('intake_id', intakeSelect.value || '');
            formData.set('semester_id', semesterSelect.value || '');
            formData.append('register_students', JSON.stringify(selectedStudents));

            Object.keys(specialApprovalPayload).forEach(function (studentId) {
                const payload = specialApprovalPayload[studentId];
                formData.append('sa_reasons[' + studentId + ']', payload.reason);
                if (payload.file) {
                    formData.append('sa_files[' + studentId + ']', payload.file);
                }
            });

            fetch('{{ route('semester.registration.store') }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
                .then(async function (response) {
                    const contentType = response.headers.get('content-type') || '';
                    if (!contentType.includes('application/json')) {
                        const text = await response.text();
                        throw new Error(text.slice(0, 300));
                    }
                    return response.json();
                })
                .then(function (data) {
                    if (data.success) {
                        showToast(data.message || 'Saved.', 'success');
                        setTimeout(function () {
                            document.getElementById('courseForm').reset();
                            hideStudents();
                            resetSpecialization();
                            resetAndDisable(courseSelect, 'Select Course');
                            resetAndDisable(intakeSelect, 'Select Intake');
                            resetAndDisable(semesterSelect, 'Select Semester');
                            Object.keys(specialApprovalPayload).forEach(function (key) {
                                delete specialApprovalPayload[key];
                            });
                        }, 1500);
                    } else {
                        showToast(data.message || 'An error occurred.', 'error');
                    }
                })
                .catch(function () {
                    showToast('An error occurred while saving. Please try again.', 'error');
                })
                .finally(function () {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                });
        });
    });
</script>
@endpush
