@extends('inc.app')

@section('title', 'NEBULA | Specialization Registration')

@section('content')
<style nonce="{{ $cspNonce }}">
    .specialization-registration-page,
    .specialization-registration-page .card,
    .specialization-registration-page .card-body {
        min-width: 0;
        max-width: 100%;
        overflow: visible;
    }
    body:has(.specialization-registration-page) .body-wrapper > .container-fluid {
        overflow: visible;
    }
    .specialization-registration-page [class*="col-"] {
        min-width: 0;
    }
    .specialization-registration-page .form-select,
    .specialization-registration-page .form-control,
    .specialization-registration-page .nebula-select,
    .specialization-registration-page .nebula-select-toggle {
        width: 100%;
        max-width: 100%;
    }
    .specialization-registration-page .table-responsive {
        width: 100%;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }
    .specialization-students-table th,
    .specialization-students-table td {
        word-break: break-word;
        vertical-align: middle;
    }
    .specialization-toolbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
    }
    #studentSearch,
    #assignmentFilter {
        max-width: 18rem;
    }
    @media (max-width: 767.98px) {
        .specialization-registration-page h2 {
            font-size: 1.25rem;
        }
        .specialization-registration-page .card-body {
            padding: 1rem 0.75rem;
        }
        .specialization-registration-page .form-control,
        .specialization-registration-page .form-select,
        .specialization-registration-page .nebula-select-toggle {
            font-size: 16px;
        }
        .specialization-registration-page .col-form-label {
            text-align: left !important;
            padding-bottom: 0.2rem;
        }
        .specialization-toolbar {
            flex-direction: column;
            align-items: stretch;
        }
        .specialization-toolbar .btn {
            width: 100%;
        }
        #studentSearch,
        #assignmentFilter {
            max-width: 100%;
        }
        .specialization-students-table thead {
            display: none;
        }
        .specialization-students-table,
        .specialization-students-table tbody,
        .specialization-students-table tr,
        .specialization-students-table td {
            display: block;
            width: 100%;
        }
        .specialization-students-table tbody tr[data-student-id] {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 8px 12px 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }
        .specialization-students-table td {
            border: 0;
            padding: 0.45rem 0;
        }
        .specialization-students-table td[data-label]::before {
            content: attr(data-label);
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .specialization-students-table td.student-check-cell::before {
            display: none;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 specialization-registration-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-3">Degree &amp; Diploma Specialization Registration</h2>
            <p class="text-muted text-center mb-0">Only students already eligible and course-registered can be assigned to a specialization. Current Specialization is each student's existing assignment — it is not changed just by selecting a specialization above.</p>
            <hr>

            <div id="statusMessage"></div>

            <div class="mb-3 row mx-0">
                <label for="location" class="col-md-2 col-form-label">Location <span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select id="location" class="form-select">
                        <option value="" selected>Select location</option>
                        <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                        <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                        <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                    </select>
                </div>
            </div>

            <div class="mb-3 row mx-0">
                <label for="course" class="col-md-2 col-form-label">Course <span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select id="course" class="form-select" disabled>
                        <option value="" selected>Select course</option>
                    </select>
                </div>
            </div>

            <div class="mb-3 row mx-0">
                <label for="intake" class="col-md-2 col-form-label">Intake <span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select id="intake" class="form-select" disabled>
                        <option value="" selected>Select intake</option>
                    </select>
                </div>
            </div>

            <div class="mb-3 row mx-0">
                <label for="specialization" class="col-md-2 col-form-label">Specialization <span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select id="specialization" class="form-select" disabled>
                        <option value="" selected>Select specialization</option>
                    </select>
                </div>
            </div>

            <div id="studentArea" class="mt-4 d-none">
                <div class="specialization-toolbar mb-3">
                    <strong id="count">0 eligible students</strong>
                    <input type="search" class="form-control" id="studentSearch" placeholder="Search students..." autocomplete="off">
                    <select id="assignmentFilter" class="form-select">
                        <option value="all" selected>All students</option>
                        <option value="unassigned">Not assigned</option>
                        <option value="selected">Already in this specialization</option>
                        <option value="other">Assigned to another specialization</option>
                    </select>
                    <button type="button" id="save" class="btn btn-primary">Register Selected Students</button>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped specialization-students-table">
                        <thead class="table-light">
                            <tr>
                                <th><input id="selectAll" type="checkbox" aria-label="Select all students"></th>
                                <th>Course Registration ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>NIC</th>
                                <th>Current Specialization</th>
                            </tr>
                        </thead>
                        <tbody id="students"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    const locationSelect = document.getElementById('location');
    const courseSelect = document.getElementById('course');
    const intakeSelect = document.getElementById('intake');
    const specializationSelect = document.getElementById('specialization');
    const studentsBody = document.getElementById('students');
    const studentArea = document.getElementById('studentArea');
    const count = document.getElementById('count');
    const statusMessage = document.getElementById('statusMessage');
    const selectAll = document.getElementById('selectAll');
    const studentSearch = document.getElementById('studentSearch');
    const assignmentFilter = document.getElementById('assignmentFilter');
    const saveBtn = document.getElementById('save');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';

    let courseOptions = [];
    let loadedStudents = [];
    let checkedIds = new Set();

    function escapeHtml(text) {
        const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
        return String(text ?? '').replace(/[&<>"']/g, function (match) {
            return map[match];
        });
    }

    function showMessage(type, message) {
        statusMessage.textContent = '';
        if (!message) {
            return;
        }
        const alert = document.createElement('div');
        alert.className = 'alert alert-' + type + ' alert-dismissible fade show';
        alert.setAttribute('role', 'alert');
        alert.textContent = message;
        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.className = 'btn-close';
        closeBtn.setAttribute('data-bs-dismiss', 'alert');
        closeBtn.setAttribute('aria-label', 'Close');
        alert.appendChild(closeBtn);
        statusMessage.appendChild(alert);
    }

    function resetSelect(select, placeholder) {
        select.innerHTML = '';
        const option = new Option(placeholder, '', true, true);
        select.add(option);
        select.disabled = true;
    }

    function clearStudentTable() {
        loadedStudents = [];
        checkedIds = new Set();
        studentsBody.innerHTML = '';
        count.textContent = '0 eligible students';
        studentArea.classList.add('d-none');
        selectAll.checked = false;
        if (studentSearch) {
            studentSearch.value = '';
        }
    }

    async function postJson(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(data)
        });

        const payload = await response.json().catch(function () { return {}; });
        if (!response.ok || payload.success === false) {
            throw new Error(payload.message || 'Request failed.');
        }
        return payload;
    }

    function extractSpecializations(courseId) {
        let specs = courseOptions.find(function (item) {
            return String(item.course_id) === String(courseId);
        })?.specializations ?? [];

        for (let i = 0; i < 2 && typeof specs === 'string'; i += 1) {
            try {
                specs = JSON.parse(specs);
            } catch (_error) {
                specs = [];
                break;
            }
        }

        if (!Array.isArray(specs)) {
            return [];
        }

        return specs.map(function (spec) {
            if (typeof spec === 'string') {
                return spec.trim();
            }
            if (spec && typeof spec === 'object') {
                return String(spec.name || spec.title || spec.specialization || '').trim();
            }
            return '';
        }).filter(Boolean);
    }

    function visibleStudentRows() {
        return Array.from(studentsBody.querySelectorAll('tr[data-student-id]'));
    }

    function renderStudents() {
        const query = (studentSearch.value || '').trim().toLowerCase();
        const assignment = assignmentFilter ? assignmentFilter.value : 'all';
        const selectedSpec = specializationSelect.value;
        const filtered = loadedStudents.filter(function (student) {
            const currentSpec = student.specialization || '';
            if (assignment === 'unassigned' && currentSpec) {
                return false;
            }
            if (assignment === 'selected' && currentSpec !== selectedSpec) {
                return false;
            }
            if (assignment === 'other' && (!currentSpec || currentSpec === selectedSpec)) {
                return false;
            }
            if (!query) {
                return true;
            }
            return [student.course_registration_id, student.name, student.email, student.nic, student.specialization, student.student_id]
                .join(' ')
                .toLowerCase()
                .includes(query);
        });

        if (!loadedStudents.length) {
            studentsBody.innerHTML = '<tr><td colspan="6" class="text-center">No eligible students found.</td></tr>';
            studentArea.classList.remove('d-none');
            count.textContent = '0 eligible students';
            return;
        }

        if (!filtered.length) {
            studentsBody.innerHTML = '<tr><td colspan="6" class="text-center">No students match this search.</td></tr>';
            studentArea.classList.remove('d-none');
            return;
        }

        studentsBody.innerHTML = filtered.map(function (student) {
            const id = String(student.student_id);
            const checked = checkedIds.has(id) ? ' checked' : '';
            return '<tr data-student-id="' + escapeHtml(id) + '">' +
                '<td class="student-check-cell" data-label="Select"><input class="student-check" type="checkbox" value="' + escapeHtml(id) + '"' + checked + '></td>' +
                '<td data-label="Course Registration ID">' + escapeHtml(student.course_registration_id || '-') + '</td>' +
                '<td data-label="Name">' + escapeHtml(student.name || '') + '</td>' +
                '<td data-label="Email">' + escapeHtml(student.email || '') + '</td>' +
                '<td data-label="NIC">' + escapeHtml(student.nic || '-') + '</td>' +
                '<td data-label="Current Specialization">' + escapeHtml(student.specialization || '-') + '</td>' +
                '</tr>';
        }).join('');

        studentArea.classList.remove('d-none');
        count.textContent = loadedStudents.length + ' eligible student' + (loadedStudents.length === 1 ? '' : 's');
        syncSelectAll();
    }

    function syncSelectAll() {
        const boxes = visibleStudentRows().map(function (row) {
            return row.querySelector('.student-check');
        }).filter(Boolean);
        selectAll.checked = boxes.length > 0 && boxes.every(function (box) { return box.checked; });
    }

    async function loadStudents(preserveMessage) {
        clearStudentTable();
        if (!locationSelect.value || !courseSelect.value || !intakeSelect.value || !specializationSelect.value) {
            return;
        }

        try {
            const data = await postJson('{{ url('/specialization-registration/students') }}', {
                location: locationSelect.value,
                course_id: courseSelect.value,
                intake_id: intakeSelect.value
            });
            loadedStudents = Array.isArray(data.students) ? data.students : [];
            checkedIds = new Set();
            renderStudents();
            if (!preserveMessage) {
                showMessage('', '');
            }
        } catch (error) {
            showMessage('danger', error.message);
        }
    }

    locationSelect.addEventListener('change', async function () {
        resetSelect(courseSelect, 'Select course');
        resetSelect(intakeSelect, 'Select intake');
        resetSelect(specializationSelect, 'Select specialization');
        clearStudentTable();
        courseOptions = [];

        if (!locationSelect.value) {
            return;
        }

        try {
            const data = await postJson('{{ url('/specialization-registration/courses') }}', { location: locationSelect.value });
            courseOptions = Array.isArray(data.courses) ? data.courses : [];
            if (!courseOptions.length) {
                showMessage('warning', 'No degree or diploma courses found for this location.');
                return;
            }
            courseSelect.disabled = false;
            courseOptions.forEach(function (item) {
                courseSelect.add(new Option(item.course_name, item.course_id));
            });
            showMessage('', '');
        } catch (error) {
            showMessage('danger', error.message);
        }
    });

    courseSelect.addEventListener('change', async function () {
        resetSelect(intakeSelect, 'Select intake');
        resetSelect(specializationSelect, 'Select specialization');
        clearStudentTable();

        if (!courseSelect.value || !locationSelect.value) {
            return;
        }

        try {
            const data = await postJson('{{ url('/specialization-registration/intakes') }}', {
                location: locationSelect.value,
                course_id: courseSelect.value
            });
            const intakes = Array.isArray(data.intakes) ? data.intakes : [];
            if (!intakes.length) {
                showMessage('warning', 'No intakes available for this course.');
            } else {
                intakeSelect.disabled = false;
                intakes.forEach(function (item) {
                    intakeSelect.add(new Option(item.batch, item.intake_id));
                });
            }

            const specs = extractSpecializations(courseSelect.value);
            if (specs.length > 0) {
                specializationSelect.disabled = false;
                specs.forEach(function (item) {
                    specializationSelect.add(new Option(item, item));
                });
                if (intakes.length) {
                    showMessage('', '');
                }
            } else {
                showMessage('warning', 'This course has no specializations.');
            }
        } catch (error) {
            showMessage('danger', error.message);
        }
    });

    intakeSelect.addEventListener('change', function () {
        loadStudents(false);
    });
    specializationSelect.addEventListener('change', function () {
        loadStudents(false);
    });
    studentSearch.addEventListener('input', renderStudents);
    if (assignmentFilter) {
        assignmentFilter.addEventListener('change', renderStudents);
    }

    selectAll.addEventListener('change', function (event) {
        visibleStudentRows().forEach(function (row) {
            const box = row.querySelector('.student-check');
            if (!box) {
                return;
            }
            box.checked = event.target.checked;
            if (event.target.checked) {
                checkedIds.add(String(box.value));
            } else {
                checkedIds.delete(String(box.value));
            }
        });
    });

    studentsBody.addEventListener('change', function (event) {
        if (!event.target.classList.contains('student-check')) {
            return;
        }
        if (event.target.checked) {
            checkedIds.add(String(event.target.value));
        } else {
            checkedIds.delete(String(event.target.value));
        }
        syncSelectAll();
    });

    saveBtn.addEventListener('click', async function () {
        const studentIds = Array.from(checkedIds);

        if (studentIds.length === 0) {
            showMessage('warning', 'Select at least one student.');
            return;
        }
        if (!specializationSelect.value) {
            showMessage('warning', 'Please select a specialization.');
            return;
        }

        const originalText = saveBtn.textContent;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';

        try {
            const data = await postJson('{{ url('/specialization-registration/store') }}', {
                location: locationSelect.value,
                course_id: courseSelect.value,
                intake_id: intakeSelect.value,
                specialization: specializationSelect.value,
                student_ids: studentIds
            });
            await loadStudents(true);
            showMessage('success', data.message || 'Saved successfully.');
        } catch (error) {
            showMessage('danger', error.message);
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
        }
    });
});
</script>
@endpush
