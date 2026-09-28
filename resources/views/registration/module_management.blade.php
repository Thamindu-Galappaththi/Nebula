@extends('inc.app')

@section('title', 'NEBULA | Elective Module Registration')

@section('content')
<style nonce="{{ $cspNonce }}">
    .module-management-page,
    .module-management-page .card,
    .module-management-page .card-body {
        min-width: 0;
        max-width: 100%;
        overflow: visible;
    }
    body:has(.module-management-page) .body-wrapper > .container-fluid {
        overflow: visible;
    }
    .module-management-page [class*="col-"] {
        min-width: 0;
    }
    .module-management-page .form-select,
    .module-management-page .form-control,
    .module-management-page .nebula-select,
    .module-management-page .nebula-select-toggle {
        width: 100%;
        max-width: 100%;
    }
    .module-management-page .table-responsive {
        width: 100%;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }
    .module-students-table th,
    .module-students-table td {
        word-break: break-word;
        vertical-align: middle;
    }
    .module-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 0.75rem;
    }
    #studentSearch {
        max-width: 18rem;
    }
    .module-toast {
        max-width: min(360px, calc(100vw - 1.5rem));
    }
    @media (max-width: 767.98px) {
        .module-management-page h2 {
            font-size: 1.25rem;
        }
        .module-management-page .card-body {
            padding: 1rem 0.75rem;
        }
        .module-management-page .form-control,
        .module-management-page .form-select,
        .module-management-page .nebula-select-toggle {
            font-size: 16px;
        }
        .module-management-page .col-form-label {
            text-align: left !important;
            padding-bottom: 0.2rem;
        }
        .module-toolbar {
            flex-direction: column;
            align-items: stretch;
        }
        #studentSearch,
        .module-management-page .btn[type="submit"] {
            max-width: 100%;
            width: 100%;
        }
        .module-students-table thead {
            display: none;
        }
        .module-students-table,
        .module-students-table tbody,
        .module-students-table tr,
        .module-students-table td {
            display: block;
            width: 100%;
        }
        .module-students-table tbody tr[data-student-id] {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 8px 12px 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }
        .module-students-table td {
            border: 0;
            padding: 0.45rem 0;
        }
        .module-students-table td[data-label]::before {
            content: attr(data-label);
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .module-students-table td.student-check-cell::before {
            display: none;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 module-management-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Module Registration (Elective)</h2>
            <hr>
            <div class="mb-3 row mx-0">
                <label for="elective_location" class="col-md-2 col-form-label fw-bold">Location<span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select class="form-select" id="elective_location" name="elective_location">
                        <option selected disabled value="">Select a location</option>
                        <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                        <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                        <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                    </select>
                </div>
            </div>
            <div class="mb-3 row mx-0">
                <label for="elective_course" class="col-md-2 col-form-label fw-bold">Course<span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select class="form-select" id="elective_course" name="elective_course" disabled>
                        <option selected disabled value="">Select a course</option>
                        @foreach($degreeCourses as $course)
                            <option value="{{ $course->course_id }}" data-location="{{ $course->location }}" data-type="degree">
                                degree - {{ $course->course_name }}
                            </option>
                        @endforeach
                        @foreach($diplomaCourses as $course)
                            <option value="{{ $course->course_id }}" data-location="{{ $course->location }}" data-type="diploma">
                                diploma - {{ $course->course_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="mb-3 row mx-0">
                <label for="elective_intake" class="col-md-2 col-form-label fw-bold">Intake<span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select class="form-select" id="elective_intake" name="elective_intake" disabled>
                        <option selected disabled value="">Select an Intake</option>
                    </select>
                </div>
            </div>
            <div class="mb-3 row mx-0">
                <label for="elective_semester" class="col-md-2 col-form-label fw-bold">Ongoing Semester<span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select class="form-select" id="elective_semester" name="elective_semester" disabled>
                        <option selected disabled value="">Select an ongoing semester</option>
                    </select>
                </div>
            </div>
            <div class="mb-3 row mx-0" id="elective_specialization_row" style="display:none;">
                <label for="elective_specialization" class="col-md-2 col-form-label fw-bold">Specialization</label>
                <div class="col-md-10">
                    <select class="form-select" id="elective_specialization" name="elective_specialization">
                        <option selected disabled value="">Select Specialization</option>
                    </select>
                </div>
            </div>
            <div class="mb-3 row mx-0">
                <label for="elective_module" class="col-md-2 col-form-label fw-bold">Module<span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select class="form-select" id="elective_module" name="elective_module" disabled>
                        <option selected disabled value="">Select a module</option>
                    </select>
                </div>
            </div>

            <div id="electiveRegistrationSection" style="display: none;">
                <hr>
                <h4 class="mb-3">Elective Module Registration</h4>
                <form method="POST" action="{{ route('module.management.registerElectiveModules') }}" id="electiveRegistrationForm">
                    @csrf
                    <input type="hidden" name="semester_id" id="elective_semester_hidden">
                    <input type="hidden" name="course_id" id="elective_course_hidden">
                    <input type="hidden" name="intake_id" id="elective_intake_hidden">
                    <input type="hidden" name="location" id="elective_location_hidden">
                    <input type="hidden" name="module_id" id="elective_module_hidden">
                    <input type="hidden" name="specialization" id="elective_specialization_hidden">

                    <div class="module-toolbar">
                        <label class="d-flex align-items-center gap-2 mb-0">
                            <input id="selectAll" type="checkbox" aria-label="Select all students">
                            <strong id="studentCount">0 students</strong>
                        </label>
                        <input type="search" class="form-control" id="studentSearch" placeholder="Search students..." autocomplete="off">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover module-students-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Select</th>
                                    <th>Course Registration ID</th>
                                    <th>Name</th>
                                    <th>Specialization</th>
                                    <th>Email</th>
                                    <th>NIC</th>
                                </tr>
                            </thead>
                            <tbody id="electiveStudentsTable"></tbody>
                        </table>
                    </div>
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary">Register Elective Modules</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="toast-container position-fixed top-0 end-0 p-3 module-toast" style="z-index: 9999"></div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const locationSelect = document.getElementById('elective_location');
    const courseSelect = document.getElementById('elective_course');
    const intakeSelect = document.getElementById('elective_intake');
    const semesterSelect = document.getElementById('elective_semester');
    const specializationSelect = document.getElementById('elective_specialization');
    const moduleSelect = document.getElementById('elective_module');
    const studentsBody = document.getElementById('electiveStudentsTable');
    const studentSearch = document.getElementById('studentSearch');
    const selectAll = document.getElementById('selectAll');
    const studentCount = document.getElementById('studentCount');
    const allCourses = Array.from(courseSelect.querySelectorAll('option[data-location]')).map(function (option) {
        return {
            id: option.value,
            label: option.textContent.trim(),
            location: option.getAttribute('data-location')
        };
    });

    let loadedStudents = [];
    let checkedIds = new Set();

    function escapeHtml(text) {
        const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
        return String(text ?? '').replace(/[&<>"']/g, function (match) {
            return map[match];
        });
    }

    function showToast(message, type) {
        const container = document.querySelector('.toast-container');
        if (!container) {
            return;
        }
        container.innerHTML = '';
        const toast = document.createElement('div');
        toast.className = 'toast show';
        toast.setAttribute('role', 'alert');
        const header = document.createElement('div');
        header.className = 'toast-header bg-' + (type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'danger')) + (type === 'warning' ? ' text-dark' : ' text-white');
        const strong = document.createElement('strong');
        strong.className = 'me-auto';
        strong.textContent = type === 'success' ? 'Success' : (type === 'warning' ? 'Warning' : 'Error');
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close' + (type === 'warning' ? '' : ' btn-close-white');
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
            bootstrap.Toast.getOrCreateInstance(toast, { delay: 4000 }).hide();
        }, 4000);
    }

    function resetSelect(select, placeholder) {
        select.innerHTML = '';
        const option = new Option(placeholder, '', true, true);
        option.disabled = true;
        select.add(option);
        select.disabled = true;
    }

    function fillCourses(location) {
        resetSelect(courseSelect, 'Select a course');
        allCourses.filter(function (course) {
            return course.location === location;
        }).forEach(function (course) {
            courseSelect.add(new Option(course.label, course.id));
        });
        courseSelect.disabled = courseSelect.options.length <= 1;
        if (courseSelect.options.length <= 1) {
            showToast('No degree or diploma courses found for this location.', 'warning');
        }
    }

    function hideStudents() {
        loadedStudents = [];
        checkedIds = new Set();
        studentsBody.innerHTML = '';
        document.getElementById('electiveRegistrationSection').style.display = 'none';
        if (studentSearch) {
            studentSearch.value = '';
        }
        if (selectAll) {
            selectAll.checked = false;
        }
    }

    function syncHiddenFields() {
        document.getElementById('elective_location_hidden').value = locationSelect.value || '';
        document.getElementById('elective_course_hidden').value = courseSelect.value || '';
        document.getElementById('elective_intake_hidden').value = intakeSelect.value || '';
        document.getElementById('elective_semester_hidden').value = semesterSelect.value || '';
        document.getElementById('elective_module_hidden').value = moduleSelect.value || '';
        document.getElementById('elective_specialization_hidden').value = specializationSelect.value || '';
    }

    function specializationRequired() {
        return document.getElementById('elective_specialization_row').style.display !== 'none';
    }

    function allFieldsSelected() {
        const basic = locationSelect.value && courseSelect.value && intakeSelect.value && semesterSelect.value && moduleSelect.value;
        if (specializationRequired()) {
            return basic && specializationSelect.value;
        }
        return basic;
    }

    async function postForm(url, data) {
        const body = new URLSearchParams();
        Object.keys(data).forEach(function (key) {
            if (data[key] !== undefined && data[key] !== null) {
                body.append(key, data[key]);
            }
        });
        body.append('_token', csrfToken);
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: body
        });
        const payload = await response.json().catch(function () { return {}; });
        if (!response.ok || payload.success === false) {
            throw new Error(payload.message || 'Request failed.');
        }
        return payload;
    }

    function renderStudents() {
        const query = (studentSearch.value || '').trim().toLowerCase();
        const filtered = loadedStudents.filter(function (student) {
            if (!query) {
                return true;
            }
            return [student.course_registration_id, student.name, student.specialization, student.email, student.nic, student.student_id]
                .join(' ')
                .toLowerCase()
                .includes(query);
        });

        if (!loadedStudents.length) {
            studentsBody.innerHTML = '<tr><td colspan="6" class="text-center">No eligible students found.</td></tr>';
            studentCount.textContent = '0 students';
            return;
        }
        if (!filtered.length) {
            studentsBody.innerHTML = '<tr><td colspan="6" class="text-center">No students match this search.</td></tr>';
            return;
        }

        studentsBody.innerHTML = filtered.map(function (student) {
            const id = String(student.student_id);
            const already = !!student.already_registered;
            const checked = already || checkedIds.has(id);
            return '<tr data-student-id="' + escapeHtml(id) + '">' +
                '<td class="student-check-cell" data-label="Register"><input class="student-check" type="checkbox" name="register_students[]" value="' + escapeHtml(id) + '"' + (checked ? ' checked' : '') + (already ? ' disabled' : '') + '> ' +
                (already ? '<small class="text-muted">Registered</small>' : '') + '</td>' +
                '<td data-label="Course Registration ID">' + escapeHtml(student.course_registration_id || '-') + '</td>' +
                '<td data-label="Name">' + escapeHtml(student.name || '') + '</td>' +
                '<td data-label="Specialization">' + escapeHtml(student.specialization || '-') + '</td>' +
                '<td data-label="Email">' + escapeHtml(student.email || '') + '</td>' +
                '<td data-label="NIC">' + escapeHtml(student.nic || '-') + '</td>' +
                '</tr>';
        }).join('');
        studentCount.textContent = loadedStudents.length + ' student' + (loadedStudents.length === 1 ? '' : 's');
        syncSelectAll();
    }

    function visibleChecks() {
        return Array.from(studentsBody.querySelectorAll('.student-check:not(:disabled)'));
    }

    function syncSelectAll() {
        const boxes = visibleChecks();
        selectAll.checked = boxes.length > 0 && boxes.every(function (box) { return box.checked; });
    }

    async function loadIntakes() {
        resetSelect(intakeSelect, 'Select an Intake');
        resetSelect(semesterSelect, 'Select an ongoing semester');
        resetSelect(moduleSelect, 'Select a module');
        hideStudents();
        const data = await postForm('{{ route('module.management.getIntakes') }}', {
            course_id: courseSelect.value,
            location: locationSelect.value
        });
        const intakes = data.data || [];
        if (!intakes.length) {
            showToast('No intakes available for this course.', 'warning');
            return;
        }
        intakes.forEach(function (intake) {
            intakeSelect.add(new Option(intake.intake_name, intake.intake_id));
        });
        intakeSelect.disabled = false;
    }

    async function loadSemesters() {
        resetSelect(semesterSelect, 'Select an ongoing semester');
        resetSelect(moduleSelect, 'Select a module');
        hideStudents();
        const data = await postForm('{{ route('module.management.getOngoingSemesters') }}', {
            course_id: courseSelect.value,
            intake_id: intakeSelect.value,
            location: locationSelect.value
        });
        const semesters = data.data || [];
        if (!semesters.length) {
            showToast('No ongoing semesters found.', 'warning');
            return;
        }
        semesters.forEach(function (semester) {
            const statusText = semester.status === 'active' ? ' (Active)' : (semester.status === 'upcoming' ? ' (Upcoming)' : '');
            semesterSelect.add(new Option((semester.name || '') + statusText, semester.id));
        });
        semesterSelect.disabled = false;
    }

    async function loadModules() {
        resetSelect(moduleSelect, 'Select a module');
        hideStudents();
        if (!semesterSelect.value) {
            return;
        }
        const data = await postForm('{{ route('module.management.getElectiveModules') }}', {
            semester_id: semesterSelect.value,
            course_id: courseSelect.value,
            specialization: specializationSelect.value || ''
        });
        if (!specializationSelect.value) {
            resetSelect(specializationSelect, 'Select Specialization');
            specializationSelect.disabled = false;
            if (data.common_available) {
                specializationSelect.add(new Option('Common', 'Common'));
            }
            (data.available_specializations || []).forEach(function (spec) {
                specializationSelect.add(new Option(spec, spec));
            });
            if (data.common_available || (data.available_specializations || []).length) {
                document.getElementById('elective_specialization_row').style.display = '';
                if (data.common_available && !(data.available_specializations || []).length) {
                    specializationSelect.value = 'Common';
                    specializationSelect.dispatchEvent(new Event('change', { bubbles: true }));
                }
                return;
            }
            document.getElementById('elective_specialization_row').style.display = 'none';
        }
        const modules = data.data || [];
        if (!modules.length) {
            showToast('No elective modules found for this selection.', 'warning');
            return;
        }
        modules.forEach(function (module) {
            moduleSelect.add(new Option(module.module_name, module.module_id));
        });
        moduleSelect.disabled = false;
    }

    async function loadStudents() {
        hideStudents();
        if (!allFieldsSelected()) {
            return;
        }
        syncHiddenFields();
        const data = await postForm('{{ route('module.management.getElectiveStudents') }}', {
            course_id: courseSelect.value,
            intake_id: intakeSelect.value,
            semester_id: semesterSelect.value,
            location: locationSelect.value,
            specialization: specializationSelect.value || '',
            module_id: moduleSelect.value
        });
        loadedStudents = Array.isArray(data.students) ? data.students : [];
        checkedIds = new Set();
        renderStudents();
        document.getElementById('electiveRegistrationSection').style.display = '';
    }

    locationSelect.addEventListener('change', function () {
        fillCourses(locationSelect.value);
        resetSelect(intakeSelect, 'Select an Intake');
        resetSelect(semesterSelect, 'Select an ongoing semester');
        resetSelect(moduleSelect, 'Select a module');
        resetSelect(specializationSelect, 'Select Specialization');
        document.getElementById('elective_specialization_row').style.display = 'none';
        hideStudents();
        syncHiddenFields();
    });

    courseSelect.addEventListener('change', function () {
        resetSelect(intakeSelect, 'Select an Intake');
        resetSelect(semesterSelect, 'Select an ongoing semester');
        resetSelect(moduleSelect, 'Select a module');
        resetSelect(specializationSelect, 'Select Specialization');
        document.getElementById('elective_specialization_row').style.display = 'none';
        hideStudents();
        if (!locationSelect.value || !courseSelect.value) {
            return;
        }
        loadIntakes().catch(function (error) {
            showToast(error.message, 'error');
        });
    });

    intakeSelect.addEventListener('change', function () {
        if (!intakeSelect.value) {
            return;
        }
        loadSemesters().catch(function (error) {
            showToast(error.message, 'error');
        });
    });

    semesterSelect.addEventListener('change', function () {
        if (!semesterSelect.value) {
            hideStudents();
            return;
        }
        resetSelect(specializationSelect, 'Select Specialization');
        document.getElementById('elective_specialization_row').style.display = 'none';
        loadModules().catch(function (error) {
            showToast(error.message, 'error');
        });
    });

    specializationSelect.addEventListener('change', function () {
        document.getElementById('elective_specialization_hidden').value = specializationSelect.value || '';
        if (!semesterSelect.value) {
            return;
        }
        loadModules().catch(function (error) {
            showToast(error.message, 'error');
        });
    });

    moduleSelect.addEventListener('change', function () {
        if (allFieldsSelected()) {
            loadStudents().catch(function (error) {
                showToast(error.message, 'error');
            });
        } else {
            hideStudents();
        }
    });

    studentSearch.addEventListener('input', renderStudents);
    selectAll.addEventListener('change', function (event) {
        visibleChecks().forEach(function (box) {
            box.checked = event.target.checked;
            if (event.target.checked) {
                checkedIds.add(String(box.value));
            } else {
                checkedIds.delete(String(box.value));
            }
        });
    });
    studentsBody.addEventListener('change', function (event) {
        if (!event.target.classList.contains('student-check') || event.target.disabled) {
            return;
        }
        if (event.target.checked) {
            checkedIds.add(String(event.target.value));
        } else {
            checkedIds.delete(String(event.target.value));
        }
        syncSelectAll();
    });

    document.getElementById('electiveRegistrationForm').addEventListener('submit', function (event) {
        event.preventDefault();
        const selectedStudents = Array.from(checkedIds);
        if (!selectedStudents.length) {
            showToast('Please select at least one student to register.', 'warning');
            return;
        }
        syncHiddenFields();
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Registering...';

        const formData = new FormData(this);
        selectedStudents.forEach(function (id) {
            formData.append('register_students[]', id);
        });

        fetch(this.action, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: formData
        })
            .then(function (response) { return response.json(); })
            .then(function (data) {
                if (data.success) {
                    showToast(data.message || 'Registered successfully.', 'success');
                    loadStudents().catch(function () {});
                } else {
                    showToast(data.message || 'Registration failed.', 'error');
                }
            })
            .catch(function () {
                showToast('An error occurred while registering elective modules.', 'error');
            })
            .finally(function () {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
    });

    resetSelect(courseSelect, 'Select a course');
});
</script>
@endpush
