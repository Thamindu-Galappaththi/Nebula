@extends('inc.app')

@section('title', 'NEBULA | Repeat Students Management')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<div class="container-fluid repeat-students-page px-2 px-md-3 mt-3 mb-5">
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <h2 class="text-center mb-4">Repeat Students Management</h2>
            <hr>

            <div id="spinner-overlay" class="repeat-students-spinner" hidden>
                <div class="lds-ring" aria-hidden="true"><div></div><div></div><div></div><div></div></div>
                <p class="text-white mt-3 mb-0 small">Please wait…</p>
            </div>

            <form id="nicSearchForm" autocomplete="off" class="repeat-students-search mb-4">
                <input type="text" class="form-control" id="nicInput" name="nic" placeholder="Enter NIC or Student ID" required>
                <button class="btn btn-primary" type="submit">
                    <i class="ti ti-search me-1"></i>Search
                </button>
            </form>

            <div class="repeat-students-profile" id="profileSection" hidden>
                <input type="hidden" id="studentIdHidden" value="">

                <ul class="nav nav-tabs exam-results-tabs mb-3" id="studentTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="profile-tab" data-bs-toggle="tab" data-bs-target="#profile" type="button" role="tab">Profile Info</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="tab" data-bs-target="#register" type="button" role="tab">Re-Register Intake</button>
                    </li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="profile" role="tabpanel">
                        <div class="row g-3 mb-3">
                            <div class="col-12 col-md-6">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h5 class="fw-bold mb-3"><i class="ti ti-user"></i> Student Profile</h5>
                                        <div class="mb-2"><strong>Name:</strong> <span id="studentName"></span></div>
                                        <div class="mb-2"><strong>Email:</strong> <span id="studentEmail"></span></div>
                                        <div class="mb-2"><strong>Mobile:</strong> <span id="studentMobile"></span></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <div class="card border-0 bg-light h-100">
                                    <div class="card-body">
                                        <h5 class="fw-bold mb-3"><i class="ti ti-school"></i> Academic Info</h5>
                                        <div class="mb-2"><strong>Institute:</strong> <span id="studentInstitute"></span></div>
                                        <div class="mb-2"><strong>Date of Birth:</strong> <span id="studentDOB"></span></div>
                                        <div class="mb-2"><strong>Gender:</strong> <span id="studentGender"></span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card border-0 bg-light">
                            <div class="card-body">
                                <h5 class="fw-bold mb-3"><i class="ti ti-list-details"></i> Holding Courses</h5>
                                <div class="exam-results-table-scroll">
                                    <table class="table table-bordered table-striped mb-0 repeat-students-holding-table">
                                        <thead class="table-primary">
                                            <tr>
                                                <th>Course</th>
                                                <th>Intake</th>
                                                <th>Specialization</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody id="holdingTableBody"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="register" role="tabpanel">
                        <h5 class="fw-bold mb-3">Re-Register Intake</h5>
                        <p class="text-muted" id="registerEmptyHint" hidden>No holding semester registrations are included for this student.</p>
                        <form id="reRegisterForm">
                            @csrf
                            <input type="hidden" name="registration_id" id="registration_id">
                            <div class="exam-results-filters">
                                <div class="row g-2 g-md-3 align-items-md-center mb-3">
                                    <label for="location" class="col-12 col-md-3 col-lg-2 col-form-label">Location <span class="text-danger">*</span></label>
                                    <div class="col-12 col-md-9 col-lg-10">
                                        <select class="form-select" id="location" name="location" required>
                                            <option value="">Select a Location</option>
                                            <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                                            <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                                            <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2 g-md-3 align-items-md-center mb-3">
                                    <label for="course_id" class="col-12 col-md-3 col-lg-2 col-form-label">Course <span class="text-danger">*</span></label>
                                    <div class="col-12 col-md-9 col-lg-10">
                                        <select class="form-select" id="course_id" name="course_id" required>
                                            <option value="">Select a Course</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2 g-md-3 align-items-md-center mb-3">
                                    <label for="intake_id" class="col-12 col-md-3 col-lg-2 col-form-label">Intake <span class="text-danger">*</span></label>
                                    <div class="col-12 col-md-9 col-lg-10">
                                        <select class="form-select" id="intake_id" name="intake_id" required>
                                            <option value="">Select an Intake</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2 g-md-3 align-items-md-center mb-3">
                                    <label for="semester_id" class="col-12 col-md-3 col-lg-2 col-form-label">Semester <span class="text-danger">*</span></label>
                                    <div class="col-12 col-md-9 col-lg-10">
                                        <select class="form-select" id="semester_id" name="semester_id" required>
                                            <option value="">Select a Semester</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="row g-2 g-md-3 align-items-md-center mb-3" id="specialization_row" hidden>
                                    <label for="specialization" class="col-12 col-md-3 col-lg-2 col-form-label">Specialization</label>
                                    <div class="col-12 col-md-9 col-lg-10">
                                        <select class="form-select" id="specialization" name="specialization">
                                            <option value="">Select a Specialization</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="text-center mt-4">
                                    <button type="submit" class="btn btn-success exam-results-save-btn py-2" id="updateBtn">Update</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    const nicInput = document.getElementById('nicInput');
    const locationSelect = document.getElementById('location');
    const courseSelect = document.getElementById('course_id');
    const intakeSelect = document.getElementById('intake_id');
    const semesterSelect = document.getElementById('semester_id');
    const specSelect = document.getElementById('specialization');
    const specRow = document.getElementById('specialization_row');
    const profileSection = document.getElementById('profileSection');
    const registerEmptyHint = document.getElementById('registerEmptyHint');
    const reRegisterForm = document.getElementById('reRegisterForm');
    const updateBtn = document.getElementById('updateBtn');
    let fillingForm = false;

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

    function showToast(title, message, type) {
        const icon = type === 'bg-danger' || type === 'error' ? 'error'
            : (type === 'bg-warning' || type === 'warning' ? 'warning'
            : (type === 'success' || type === 'bg-success' ? 'success' : 'info'));
        return notify(icon, title, String(message || '').replace(/<br\s*\/?>/gi, '\n'));
    }

    function showSpinner(show) {
        const overlay = document.getElementById('spinner-overlay');
        if (!overlay) return;
        overlay.hidden = !show;
    }

    function resetAndDisable(select, placeholder, disable = true) {
        if (!select) return;
        select.innerHTML = `<option value="">${placeholder}</option>`;
        select.disabled = disable;
    }

    function explainEmptyOptions(select, message) {
        resetAndDisable(select, message, true);
        showToast('Info', message, 'info');
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        if (typeof window.toLocalDateString === 'function') {
            return window.toLocalDateString(dateStr);
        }
        const d = new Date(dateStr);
        return Number.isNaN(d.getTime()) ? String(dateStr).split('T')[0] : d.toLocaleDateString('en-GB', { timeZone: 'Asia/Colombo' });
    }

    function fillStudentProfile(student) {
        document.getElementById('studentName').textContent = student.full_name || student.name_with_initials || '';
        document.getElementById('studentEmail').textContent = student.email || '';
        document.getElementById('studentMobile').textContent = student.mobile_phone || '';
        document.getElementById('studentInstitute').textContent = student.institute_location || '';
        document.getElementById('studentDOB').textContent = formatDate(student.birthday);
        document.getElementById('studentGender').textContent = student.gender || '';
    }

    function fillHoldingTable(holdingHistory) {
        const tbody = document.getElementById('holdingTableBody');
        tbody.replaceChildren();
        if (!holdingHistory.length) {
            const empty = document.createElement('tr');
            const cell = document.createElement('td');
            cell.colSpan = 4;
            cell.className = 'text-center text-muted';
            cell.textContent = 'No holding semester registrations found.';
            empty.appendChild(cell);
            tbody.appendChild(empty);
            return;
        }

        holdingHistory.forEach(function (item) {
            const row = document.createElement('tr');
            [item.course_name, item.intake, item.specialization].forEach(function (value) {
                const cell = document.createElement('td');
                cell.textContent = value || '';
                row.appendChild(cell);
            });
            const statusCell = document.createElement('td');
            const badge = document.createElement('span');
            badge.className = 'badge bg-warning text-dark';
            badge.textContent = item.status || '';
            statusCell.appendChild(badge);
            row.appendChild(statusCell);
            tbody.appendChild(row);
        });
    }

    function normalizeLocation(rawLoc) {
        const value = String(rawLoc || '');
        if (/welisara/i.test(value)) return 'Welisara';
        if (/moratuwa/i.test(value)) return 'Moratuwa';
        if (/peradeniya/i.test(value)) return 'Peradeniya';
        return value;
    }

    function fillReRegisterForm(reg) {
        fillingForm = true;
        document.getElementById('registration_id').value = reg.id || '';
        locationSelect.value = normalizeLocation(reg.location);
        locationSelect.dispatchEvent(new Event('change', { bubbles: true }));

        courseSelect.innerHTML = '<option value="">Select a Course</option>';
        if (reg.course_id) {
            courseSelect.add(new Option(reg.course_name || String(reg.course_id), reg.course_id, true, true));
            courseSelect.disabled = false;
            courseSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
        fillingForm = false;

        populateIntakes(reg.course_id, reg.intake_id || null, reg.semester_id || null, locationSelect.value);
        loadSpecializations(reg.course_id, reg.specialization || '');
        setRegisterFormEnabled(true);
    }

    function setRegisterFormEnabled(enabled) {
        [locationSelect, courseSelect, intakeSelect, semesterSelect, specSelect, updateBtn].forEach(function (el) {
            if (el) el.disabled = !enabled;
        });
        setHidden(registerEmptyHint, enabled);
        setHidden(reRegisterForm, !enabled);
    }

    function populateIntakes(courseId, selectedIntakeId, selectedSemesterId, location) {
        if (!courseId) {
            resetAndDisable(intakeSelect, 'Select an Intake');
            resetAndDisable(semesterSelect, 'Select a Semester');
            return;
        }

        const loc = location || locationSelect.value;
        const query = `?course_id=${encodeURIComponent(courseId)}${loc ? '&location=' + encodeURIComponent(loc) : ''}`;
        fetch(`/api/intakes${query}`)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                const intakes = Array.isArray(data.intakes) ? data.intakes : [];
                if (!intakes.length) {
                    explainEmptyOptions(intakeSelect, 'No intakes are included for this course and location.');
                    resetAndDisable(semesterSelect, 'Select a Semester');
                    return;
                }

                intakeSelect.innerHTML = '<option value="">Select an Intake</option>';
                intakes.forEach(function (intake) {
                    let label = intake.batch || intake.intake_no || intake.label || '';
                    if (data.next_intake_id && String(intake.intake_id) === String(data.next_intake_id)) {
                        label += ' — next';
                    }
                    const option = new Option(label, intake.intake_id);
                    if (selectedIntakeId && String(intake.intake_id) === String(selectedIntakeId)) {
                        option.selected = true;
                    }
                    intakeSelect.add(option);
                });
                intakeSelect.disabled = false;

                if (selectedIntakeId || intakeSelect.value) {
                    populateSemesters(courseId, selectedIntakeId || intakeSelect.value, selectedSemesterId || null);
                } else {
                    resetAndDisable(semesterSelect, 'Select a Semester', false);
                }
            })
            .catch(function () {
                explainEmptyOptions(intakeSelect, 'Unable to load intakes for this course and location.');
                resetAndDisable(semesterSelect, 'Select a Semester');
            });
    }

    function populateSemesters(courseId, intakeId, selectedSemesterId) {
        if (!courseId || !intakeId) {
            resetAndDisable(semesterSelect, 'Select a Semester');
            return;
        }

        fetch(`/api/semesters?course_id=${encodeURIComponent(courseId)}&intake_id=${encodeURIComponent(intakeId)}`)
            .then(function (response) { return response.json(); })
            .then(function (data) {
                const semesters = data.success && Array.isArray(data.semesters) ? data.semesters : [];
                if (!semesters.length) {
                    explainEmptyOptions(semesterSelect, 'No semesters are included for this course and intake.');
                    return;
                }

                semesterSelect.innerHTML = '<option value="">Select a Semester</option>';
                semesters.forEach(function (semester) {
                    const label = semester.display_name || semester.name || `Semester ${semester.id}`;
                    const option = new Option(label, semester.id);
                    if (selectedSemesterId && String(semester.id) === String(selectedSemesterId)) {
                        option.selected = true;
                    }
                    semesterSelect.add(option);
                });
                semesterSelect.disabled = false;
            })
            .catch(function () {
                explainEmptyOptions(semesterSelect, 'Unable to load semesters for this course and intake.');
            });
    }

    function loadSpecializations(courseId, selected) {
        if (!courseId) {
            resetAndDisable(specSelect, 'Select a Specialization');
            setHidden(specRow, true);
            return;
        }

        fetch(`/api/course/${encodeURIComponent(courseId)}/specializations`)
            .then(function (r) { return r.json(); })
            .then(function (data) {
                const named = [];
                if (data.success && Array.isArray(data.specializations)) {
                    data.specializations.forEach(function (spec) {
                        const label = String(typeof spec === 'object' ? (spec.name || spec.value || spec.specialization || '') : spec).trim();
                        if (label && !named.includes(label)) {
                            named.push(label);
                        }
                    });
                }

                if (!named.length && !selected) {
                    resetAndDisable(specSelect, 'Select a Specialization');
                    setHidden(specRow, true);
                    return;
                }

                specSelect.innerHTML = '<option value="">Select a Specialization</option>';
                named.forEach(function (label) {
                    specSelect.add(new Option(label, label));
                });
                if (selected && !named.includes(selected)) {
                    specSelect.add(new Option(selected, selected));
                }
                specSelect.value = selected || '';
                specSelect.disabled = false;
                setHidden(specRow, false);
            })
            .catch(function () {
                resetAndDisable(specSelect, 'Select a Specialization');
                setHidden(specRow, true);
            });
    }

    function performStudentSearch() {
        const nic = nicInput.value.trim();
        if (!nic) {
            showToast('Warning', 'Please enter a NIC or Student ID to search.', 'warning');
            return;
        }

        showSpinner(true);
        fetch(`/api/repeat-student-by-nic?nic=${encodeURIComponent(nic)}`)
            .then(function (response) { return response.json(); })
            .then(function (res) {
                if (res.success && res.student) {
                    fillStudentProfile(res.student);
                    fillHoldingTable(res.holding_history || []);
                    setHidden(profileSection, false);
                    document.getElementById('studentIdHidden').value = res.student.student_id || '';

                    if (res.holding_history && res.holding_history.length) {
                        fillReRegisterForm(res.holding_history[0]);
                    } else {
                        document.getElementById('registration_id').value = '';
                        resetAndDisable(courseSelect, 'Select a Course');
                        resetAndDisable(intakeSelect, 'Select an Intake');
                        resetAndDisable(semesterSelect, 'Select a Semester');
                        setRegisterFormEnabled(false);
                        showToast('Info', 'No holding semester registrations are included for this student.', 'info');
                    }

                    if (res.student.academic_status === 'holding') {
                        showToast('Warning', 'This student is currently on hold.', 'warning');
                    } else {
                        showToast('Success', 'Student found and data loaded successfully.', 'success');
                    }
                } else {
                    setHidden(profileSection, true);
                    showToast('Error', res.message || 'Student not found.', 'error');
                }
            })
            .catch(function () {
                setHidden(profileSection, true);
                showToast('Error', 'Error fetching student details.', 'error');
            })
            .finally(function () { showSpinner(false); });
    }

    document.getElementById('nicSearchForm').addEventListener('submit', function (e) {
        e.preventDefault();
        performStudentSearch();
    });

    locationSelect.addEventListener('change', function () {
        if (fillingForm) return;
        if (courseSelect.value) {
            populateIntakes(courseSelect.value, null, null, locationSelect.value);
        }
    });

    courseSelect.addEventListener('change', function () {
        if (fillingForm) return;
        populateIntakes(this.value, null, null, locationSelect.value);
        loadSpecializations(this.value, specSelect.value || '');
    });

    intakeSelect.addEventListener('change', function () {
        populateSemesters(courseSelect.value, this.value);
    });

    reRegisterForm.addEventListener('submit', function (e) {
        e.preventDefault();
        if (!document.getElementById('registration_id').value) {
            showToast('Warning', 'No holding registration is available to update.', 'warning');
            return;
        }

        const formData = new FormData(this);
        updateBtn.disabled = true;
        updateBtn.textContent = 'Updating...';
        fetch('{{ route("repeat.students.updateSemesterRegistration") }}', {
            method: 'POST',
            body: formData,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        })
        .then(function (r) { return r.json(); })
        .then(function (res) {
            if (res.success) {
                showToast('Success', res.message, 'success');
                performStudentSearch();
            } else {
                showToast('Error', res.message || 'Update failed.', 'error');
            }
        })
        .catch(function () {
            showToast('Error', 'Error updating registration.', 'error');
        })
        .finally(function () {
            updateBtn.disabled = false;
            updateBtn.textContent = 'Update';
        });
    });
});
</script>

<style nonce="{{ $cspNonce }}">
.repeat-students-page [class*="col-"] {
    min-width: 0;
}
.repeat-students-page .form-select,
.repeat-students-page .form-control,
.repeat-students-page .nebula-select {
    max-width: 100%;
}
.repeat-students-search {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.5rem;
    align-items: stretch;
    max-width: 42rem;
    margin: 0 auto;
}
.repeat-students-search .form-control {
    flex: 1 1 auto;
    min-width: 0;
}
.repeat-students-search .btn {
    flex: 0 0 auto;
    white-space: nowrap;
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
    min-width: 560px;
    margin-bottom: 0;
}
.exam-results-save-btn {
    width: 100%;
    max-width: 28rem;
}
.repeat-students-holding-table thead th {
    background-color: #cfe2ff;
    color: #084298;
    font-weight: 600;
}
.repeat-students-page .card-body span {
    overflow-wrap: anywhere;
    word-break: break-word;
}
.repeat-students-spinner {
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
.repeat-students-spinner[hidden] {
    display: none !important;
}
.lds-ring { display: inline-block; position: relative; width: 80px; height: 80px; }
.lds-ring div { box-sizing: border-box; display: block; position: absolute; width: 64px; height: 64px; margin: 8px; border: 8px solid #fff; border-radius: 50%; animation: lds-ring 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite; border-color: #fff transparent transparent transparent; }
.lds-ring div:nth-child(1) { animation-delay: -0.45s; }
.lds-ring div:nth-child(2) { animation-delay: -0.3s; }
.lds-ring div:nth-child(3) { animation-delay: -0.15s; }
@keyframes lds-ring { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
@media (max-width: 767.98px) {
    .repeat-students-page .card-body {
        padding: 1rem 0.75rem;
    }
    .repeat-students-page h2 {
        font-size: 1.25rem;
    }
    .repeat-students-page .form-control,
    .repeat-students-page .form-select,
    .repeat-students-page .nebula-select-toggle {
        font-size: 16px;
    }
    .repeat-students-search {
        flex-wrap: wrap;
        max-width: none;
    }
    .repeat-students-search .btn,
    .exam-results-save-btn {
        width: 100%;
        max-width: none;
    }
}
</style>
@endsection
