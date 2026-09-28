@extends('inc.app')

@section('title', 'NEBULA | Add External Institute Student ID')

@section('content')
<style nonce="{{ $cspNonce }}">
    .uh-index-page,
    .uh-index-page .card,
    .uh-index-page .card-body {
        min-width: 0;
        max-width: 100%;
        overflow: visible;
    }
    body:has(.uh-index-page) .body-wrapper > .container-fluid {
        overflow: visible;
    }
    .uh-index-page [class*="col-"] {
        min-width: 0;
    }
    .uh-index-page .form-select,
    .uh-index-page .form-control,
    .uh-index-page .nebula-select,
    .uh-index-page .nebula-select-toggle {
        width: 100%;
        max-width: 100%;
    }
    .uh-index-page .table-responsive {
        width: 100%;
        max-width: 100%;
        -webkit-overflow-scrolling: touch;
    }
    .uh-students-table th,
    .uh-students-table td {
        word-break: break-word;
        vertical-align: middle;
    }
    .uh-toolbar {
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
    .uh-toast {
        max-width: min(360px, calc(100vw - 1.5rem));
    }
    @media (max-width: 767.98px) {
        .uh-index-page h2 {
            font-size: 1.25rem;
        }
        .uh-index-page .card-body {
            padding: 1rem 0.75rem;
        }
        .uh-index-page .form-control,
        .uh-index-page .form-select,
        .uh-index-page .nebula-select-toggle {
            font-size: 16px;
        }
        .uh-index-page .col-form-label {
            text-align: left !important;
            padding-bottom: 0.2rem;
        }
        .uh-toolbar {
            flex-direction: column;
            align-items: stretch;
        }
        #studentSearch,
        .uh-index-page .btn[type="submit"] {
            max-width: 100%;
            width: 100%;
        }
        .uh-students-table thead {
            display: none;
        }
        .uh-students-table,
        .uh-students-table tbody,
        .uh-students-table tr,
        .uh-students-table td {
            display: block;
            width: 100%;
        }
        .uh-students-table tbody tr[data-student-id] {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 8px 12px 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }
        .uh-students-table td {
            border: 0;
            padding: 0.45rem 0;
        }
        .uh-students-table td[data-label]::before {
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

<div class="container-fluid px-2 px-md-3 uh-index-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Add External Institute Student ID</h2>
            <hr>

            <div class="mb-3 row mx-0">
                <label for="locationSelect" class="col-md-2 col-form-label fw-bold">Location<span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select class="form-select" id="locationSelect" name="location">
                        <option selected disabled value="">Select a location</option>
                        <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                        <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                        <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                    </select>
                </div>
            </div>
            <div class="mb-3 row mx-0">
                <label for="courseSelect" class="col-md-2 col-form-label fw-bold">Course<span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select class="form-select" id="courseSelect" name="course" disabled>
                        <option selected disabled value="">Select a course</option>
                    </select>
                </div>
            </div>
            <div class="mb-3 row mx-0">
                <label for="intakeSelect" class="col-md-2 col-form-label fw-bold">Intake<span class="text-danger">*</span></label>
                <div class="col-md-10">
                    <select class="form-select" id="intakeSelect" name="intake" disabled>
                        <option selected disabled value="">Select an intake</option>
                    </select>
                </div>
            </div>

            <div id="studentsSection" class="d-none">
                <hr>
                <h4 class="mb-3">Students – External Institute ID</h4>
                <form id="uh-index-save-form">
                    <div class="uh-toolbar">
                        <strong id="studentCount">0 students</strong>
                        <input type="search" class="form-control" id="studentSearch" placeholder="Search students..." autocomplete="off">
                    </div>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover uh-students-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Course Registration ID</th>
                                    <th>Name</th>
                                    <th>NIC</th>
                                    <th>External Institute Student ID</th>
                                </tr>
                            </thead>
                            <tbody id="studentsTableBody"></tbody>
                        </table>
                    </div>
                    <div class="d-grid mt-3">
                        <button type="submit" class="btn btn-primary" id="saveUhIds">Save External Institute IDs</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<div class="toast-container position-fixed top-0 end-0 p-3 uh-toast" style="z-index: 9999"></div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}';
    const locationSelect = document.getElementById('locationSelect');
    const courseSelect = document.getElementById('courseSelect');
    const intakeSelect = document.getElementById('intakeSelect');
    const studentsBody = document.getElementById('studentsTableBody');
    const studentsSection = document.getElementById('studentsSection');
    const studentSearch = document.getElementById('studentSearch');
    const studentCount = document.getElementById('studentCount');
    const saveBtn = document.getElementById('saveUhIds');

    let loadedStudents = [];
    let inputValues = {};

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

    function hideStudents() {
        loadedStudents = [];
        inputValues = {};
        studentsBody.innerHTML = '';
        studentsSection.classList.add('d-none');
        studentSearch.value = '';
    }

    async function postJson(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
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

    function captureInputs() {
        studentsBody.querySelectorAll('tr[data-student-id]').forEach(function (row) {
            const input = row.querySelector('.external-id-input');
            if (input) {
                inputValues[String(row.getAttribute('data-student-id'))] = input.value;
            }
        });
    }

    function renderStudents() {
        const query = (studentSearch.value || '').trim().toLowerCase();
        const filtered = loadedStudents.filter(function (student) {
            if (!query) {
                return true;
            }
            return [student.course_registration_id, student.name, student.nic, student.uh_index_number, student.student_id]
                .join(' ')
                .toLowerCase()
                .includes(query);
        });

        if (!loadedStudents.length) {
            studentsBody.innerHTML = '<tr><td colspan="4" class="text-center">No registered students found for this intake.</td></tr>';
            studentCount.textContent = '0 students';
            saveBtn.disabled = true;
            return;
        }

        if (!filtered.length) {
            studentsBody.innerHTML = '<tr><td colspan="4" class="text-center">No students match this search.</td></tr>';
            saveBtn.disabled = false;
            return;
        }

        studentsBody.innerHTML = filtered.map(function (student) {
            const id = String(student.student_id);
            const value = Object.prototype.hasOwnProperty.call(inputValues, id)
                ? inputValues[id]
                : (student.uh_index_number || '');
            return '<tr data-student-id="' + escapeHtml(id) + '">' +
                '<td data-label="Course Registration ID">' + escapeHtml(student.course_registration_id || '-') + '</td>' +
                '<td data-label="Name">' + escapeHtml(student.name || '') + '</td>' +
                '<td data-label="NIC">' + escapeHtml(student.nic || '-') + '</td>' +
                '<td data-label="External Institute Student ID"><input type="text" class="form-control external-id-input" value="' + escapeHtml(value) + '" placeholder="Enter Pearson/UH/Other Institute ID" autocomplete="off"></td>' +
                '</tr>';
        }).join('');
        studentCount.textContent = loadedStudents.length + ' student' + (loadedStudents.length === 1 ? '' : 's');
        saveBtn.disabled = false;
    }

    async function loadCourses() {
        resetSelect(courseSelect, 'Select a course');
        resetSelect(intakeSelect, 'Select an intake');
        hideStudents();
        if (!locationSelect.value) {
            return;
        }
        const data = await postJson('{{ route('uh.index.courses') }}', { location: locationSelect.value });
        const courses = data.courses || [];
        if (!courses.length) {
            showToast(data.message || 'No courses found for this location.', 'warning');
            return;
        }
        courses.forEach(function (course) {
            const label = (course.course_type ? course.course_type + ' - ' : '') + (course.course_name || '');
            courseSelect.add(new Option(label, course.course_id));
        });
        courseSelect.disabled = false;
    }

    async function loadIntakes() {
        resetSelect(intakeSelect, 'Select an intake');
        hideStudents();
        if (!locationSelect.value || !courseSelect.value) {
            return;
        }
        const data = await postJson('{{ route('uh.index.intakes') }}', {
            location: locationSelect.value,
            course_id: courseSelect.value
        });
        const intakes = data.intakes || [];
        if (!intakes.length) {
            showToast('No intakes available for this course.', 'warning');
            return;
        }
        intakes.forEach(function (intake) {
            intakeSelect.add(new Option(intake.batch, intake.intake_id));
        });
        intakeSelect.disabled = false;
    }

    async function loadStudents() {
        hideStudents();
        if (!locationSelect.value || !courseSelect.value || !intakeSelect.value) {
            return;
        }
        const data = await postJson('{{ route('uh.index.students') }}', {
            location: locationSelect.value,
            course_id: courseSelect.value,
            intake_id: intakeSelect.value
        });
        loadedStudents = Array.isArray(data.students) ? data.students : [];
        inputValues = {};
        loadedStudents.forEach(function (student) {
            inputValues[String(student.student_id)] = student.uh_index_number || '';
        });
        renderStudents();
        studentsSection.classList.remove('d-none');
    }

    locationSelect.addEventListener('change', function () {
        loadCourses().catch(function (error) {
            showToast(error.message, 'error');
        });
    });
    courseSelect.addEventListener('change', function () {
        loadIntakes().catch(function (error) {
            showToast(error.message, 'error');
        });
    });
    intakeSelect.addEventListener('change', function () {
        loadStudents().catch(function (error) {
            showToast(error.message, 'error');
        });
    });
    studentSearch.addEventListener('input', function () {
        captureInputs();
        renderStudents();
    });
    studentsBody.addEventListener('input', function (event) {
        if (!event.target.classList.contains('external-id-input')) {
            return;
        }
        const row = event.target.closest('tr[data-student-id]');
        if (row) {
            inputValues[String(row.getAttribute('data-student-id'))] = event.target.value;
        }
    });

    document.getElementById('uh-index-save-form').addEventListener('submit', async function (event) {
        event.preventDefault();
        captureInputs();
        const students = loadedStudents.map(function (student) {
            const id = String(student.student_id);
            return {
                student_id: student.student_id,
                uh_index_number: Object.prototype.hasOwnProperty.call(inputValues, id) ? inputValues[id] : (student.uh_index_number || '')
            };
        });
        if (!students.length) {
            showToast('No students to save.', 'warning');
            return;
        }

        const originalText = saveBtn.textContent;
        saveBtn.disabled = true;
        saveBtn.textContent = 'Saving...';
        try {
            const data = await postJson('{{ route('uh.index.save') }}', {
                location: locationSelect.value,
                course_id: courseSelect.value,
                intake_id: intakeSelect.value,
                students: students
            });
            showToast(data.message || 'Saved.', 'success');
            await loadStudents();
        } catch (error) {
            showToast(error.message, 'error');
        } finally {
            saveBtn.disabled = false;
            saveBtn.textContent = originalText;
        }
    });
});
</script>
@endpush
