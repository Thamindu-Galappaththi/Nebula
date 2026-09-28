@extends('inc.app')

@section('title', 'NEBULA | Repeat Student Payment Plan')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<style nonce="{{ $cspNonce }}">
.repeat-payment-page [class*="col-"] {
    min-width: 0;
}
.repeat-payment-page .form-select,
.repeat-payment-page .form-control,
.repeat-payment-page .nebula-select,
.repeat-payment-page .btn {
    max-width: 100%;
}
.repeat-payment-filters .form-label {
    display: block;
    margin-bottom: 0.35rem;
    line-height: 1.5;
    min-height: 1.5rem;
}
.repeat-payment-filters .form-control,
.repeat-payment-search,
.repeat-payment-search .form-select,
.repeat-payment-search .nebula-select,
.repeat-payment-search .btn {
    min-height: 38px;
}
.repeat-payment-filters .form-control,
.repeat-payment-search .form-select,
.repeat-payment-search .btn {
    height: 38px;
}
.repeat-payment-search {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.5rem;
    align-items: stretch;
}
.repeat-payment-search .form-control,
.repeat-payment-search .form-select,
.repeat-payment-search .nebula-select {
    flex: 1 1 auto;
    min-width: 0;
}
.repeat-payment-search .btn {
    flex: 0 0 auto;
    white-space: nowrap;
}
.repeat-payment-student {
    word-break: break-word;
}
.repeat-payment-table-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.repeat-payment-table-scroll table {
    min-width: 720px;
    margin-bottom: 0;
}
.repeat-payment-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.repeat-payment-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.repeat-payment-page-size {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.repeat-payment-page-size select,
.repeat-payment-page-size .nebula-select,
.repeat-payment-page-size .nebula-select-sm {
    width: 5.75rem;
    max-width: 5.75rem;
    min-width: 4.5rem;
    flex: 0 0 5.75rem;
}
.repeat-payment-pagination .pagination {
    flex-wrap: wrap;
    margin-bottom: 0;
}
@media (max-width: 767.98px) {
    .repeat-payment-page .card-body {
        padding: 1rem 0.75rem;
    }
    .repeat-payment-search {
        flex-wrap: wrap;
    }
    .repeat-payment-search .form-control,
    .repeat-payment-search .form-select,
    .repeat-payment-search .nebula-select,
    .repeat-payment-search .btn,
    .repeat-payment-actions,
    .repeat-payment-actions .btn {
        width: 100%;
    }
}
</style>

<div class="container-fluid px-2 px-md-3 repeat-payment-page">
    <div class="card shadow border-0">
        <div class="card-body">
            <h2 class="text-center mb-4">Repeat Student Payment Plan</h2>
            <hr>

            <form id="searchForm" class="mb-4" novalidate>
                <div class="row g-3 align-items-start repeat-payment-filters">
                    <div class="col-12 col-lg-5">
                        <label class="form-label fw-bold" for="nic">Student NIC / Student ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nic" placeholder="Enter NIC or Student ID" required autocomplete="off">
                    </div>
                    <div class="col-12 col-lg-7">
                        <label class="form-label fw-bold" for="courseSelect">Course <span class="text-danger">*</span></label>
                        <div class="repeat-payment-search">
                            <select id="courseSelect" class="form-select" disabled>
                                <option value="" selected disabled>Enter NIC or Student ID</option>
                            </select>
                            <button class="btn btn-primary" type="submit" id="searchStudentBtn" disabled>
                                <i class="ti ti-search me-1"></i>Load Plan
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-text mt-0">Courses load automatically after you enter NIC or Student ID. If there is more than one course, choose it and the plan will load.</div>
                    </div>
                </div>
            </form>

            <div id="studentInfoCard" class="alert alert-info repeat-payment-student" style="display:none;"></div>

            <div id="paymentSection" style="display:none;">
                <input type="hidden" id="student_id" name="student_id">
                <input type="hidden" id="course_id" name="course_id">

                <h5 class="fw-bold text-secondary mb-3">Archived Payment Plan</h5>
                <div class="repeat-payment-table-scroll mb-4">
                    <table class="table table-bordered align-middle" id="archivedTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Due Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <h5 class="fw-bold text-success mb-3">Current Payment Plan (Latest Intake)</h5>
                <div class="repeat-payment-table-scroll mb-4">
                    <table class="table table-bordered align-middle" id="currentTable">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>Due Date</th>
                                <th>Local Amount (LKR)</th>
                                <th>International Amount (<span id="currencyLabel">Currency</span>)</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>

                <h5 class="fw-bold text-primary mb-3">Create New Payment Plan</h5>
                <form id="newPlanForm" novalidate>
                    @csrf
                    <div class="repeat-payment-table-scroll">
                        <table class="table table-bordered align-middle" id="newPlanTable">
                            <thead class="table-primary">
                                <tr>
                                    <th>No</th>
                                    <th>Due Date</th>
                                    <th>Local Amount (LKR)</th>
                                    <th>International Amount</th>
                                    <th>Currency</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="repeat-payment-actions mt-3">
                        <button type="button" id="addRow" class="btn btn-secondary">
                            <i class="ti ti-plus me-1"></i>Add Row
                        </button>
                        <button type="submit" class="btn btn-success" id="savePlanBtn">
                            <i class="ti ti-device-floppy me-1"></i>Save Plan
                        </button>
                    </div>
                </form>

                <hr>
                <div id="createdPlansSection" class="mt-4">
                    <h5 class="fw-bold text-info mb-3">Created Payment Plans</h5>
                    <div class="repeat-payment-table-scroll">
                        <table class="table table-bordered align-middle" id="createdPlansTable">
                            <thead class="table-info">
                                <tr>
                                    <th>#</th>
                                    <th>Due Date</th>
                                    <th>Local Amount (LKR)</th>
                                    <th>International Amount</th>
                                    <th>Currency</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                    <div class="repeat-payment-footer mt-3" id="createdPlansPaginationBar" style="display:none;">
                        <div class="repeat-payment-page-size">
                            <label class="form-label mb-0 small text-muted" for="createdPlansPerPage">Per page</label>
                            <select id="createdPlansPerPage" class="form-select form-select-sm page-size-select">
                                <option value="10" selected>10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="text-muted small" id="createdPlansRange"></div>
                        <nav class="repeat-payment-pagination" aria-label="Created plan pages">
                            <ul class="pagination pagination-sm mb-0" id="createdPlansPagination"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
<script nonce="{{ $cspNonce }}">
let globalCurrency = 'USD';
let createdPlanRows = [];
let createdPlansPage = 1;
let ignoreCourseChange = false;
let lookupTimer = null;
let lookupAbort = null;

function notify(icon, title, text) {
    if (typeof Swal === 'undefined') {
        return Promise.resolve();
    }
    return Swal.fire({
        icon,
        title,
        text,
        confirmButtonColor: '#0d6efd',
    });
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, char => ({
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#39;',
    }[char]));
}

function syncCustomSelect(select) {
    if (!select) return;
    select.dispatchEvent(new Event('change', { bubbles: true }));
}

function formatDate(value) {
    if (!value) return '-';
    const text = String(value);
    if (/^\d{4}-\d{2}-\d{2}$/.test(text)) return text;
    const date = new Date(text);
    if (Number.isNaN(date.getTime())) return text.split('T')[0] || '-';
    return new Intl.DateTimeFormat('en-CA', { timeZone: 'Asia/Colombo' }).format(date);
}

function formatMoney(value) {
    return Number(value || 0).toLocaleString();
}

function setLoadPlanEnabled(enabled) {
    const btn = document.getElementById('searchStudentBtn');
    if (btn) btn.disabled = !enabled;
}

function resetCourseSelect(placeholder = 'Enter NIC or Student ID') {
    const courseSelect = document.getElementById('courseSelect');
    courseSelect.innerHTML = `<option value="" selected disabled>${escapeHtml(placeholder)}</option>`;
    courseSelect.disabled = true;
    setLoadPlanEnabled(false);
    syncCustomSelect(courseSelect);
}

function populateCourseSelect(courses) {
    const courseSelect = document.getElementById('courseSelect');
    ignoreCourseChange = true;
    courseSelect.innerHTML = '<option value="" selected disabled>Select a Course</option>';
    (courses || []).forEach(course => {
        const option = document.createElement('option');
        option.value = course.course_id;
        option.textContent = course.course_name;
        courseSelect.appendChild(option);
    });
    courseSelect.disabled = !(courses || []).length;
    if (courses.length === 1) {
        courseSelect.value = courses[0].course_id;
    }
    setLoadPlanEnabled(Boolean(courseSelect.value));
    syncCustomSelect(courseSelect);
    ignoreCourseChange = false;
    return Boolean(courseSelect.value);
}

function emptyRow(colspan, message) {
    return `<tr><td colspan="${colspan}" class="text-center text-muted">${escapeHtml(message)}</td></tr>`;
}

function statusBadge(status) {
    const value = String(status || 'pending').toLowerCase();
    const color = value === 'paid' || value === 'active' ? 'success' : (value === 'pending' ? 'warning' : 'secondary');
    return `<span class="badge bg-${color}">${escapeHtml(status || 'pending')}</span>`;
}

function renderArchived(installments) {
    const tbody = document.querySelector('#archivedTable tbody');
    if (!installments || !installments.length) {
        tbody.innerHTML = emptyRow(4, 'No archived plan found.');
        return;
    }
    tbody.innerHTML = installments.map((inst, i) => `
        <tr>
            <td>${i + 1}</td>
            <td>${escapeHtml(formatDate(inst.due_date))}</td>
            <td>${escapeHtml(formatMoney(inst.amount ?? inst.base_amount))}</td>
            <td>${statusBadge(inst.status)}</td>
        </tr>
    `).join('');
}

function renderCurrent(planData) {
    const tbody = document.querySelector('#currentTable tbody');
    const installments = planData?.installments || [];
    globalCurrency = planData?.currency || planData?.plan?.international_currency || installments[0]?.currency || 'USD';
    document.getElementById('currencyLabel').textContent = globalCurrency;

    if (!installments.length) {
        tbody.innerHTML = emptyRow(5, 'No current plan found.');
        return;
    }

    tbody.innerHTML = installments.map((inst, i) => `
        <tr>
            <td>${i + 1}</td>
            <td>${escapeHtml(formatDate(inst.due_date))}</td>
            <td>${escapeHtml(formatMoney(inst.base_amount ?? inst.amount))} LKR</td>
            <td>${escapeHtml(formatMoney(inst.international_amount))} ${escapeHtml(inst.currency || globalCurrency)}</td>
            <td>${statusBadge(inst.status || 'active')}</td>
        </tr>
    `).join('');
}

function buildPlanRow(i, due = '', local = 0, intl = 0, intlCur = globalCurrency) {
    const localVal = Number(local) || 0;
    const intlVal = Number(intl) || 0;
    return `
        <tr>
            <td>${i + 1}</td>
            <td><input type="date" class="form-control" name="installments[${i}][due_date]" value="${escapeHtml(formatDate(due) === '-' ? '' : formatDate(due))}" required></td>
            <td><input type="number" step="0.01" min="0" class="form-control local" name="installments[${i}][local_amount]" value="${localVal || ''}" placeholder="Local Fee"></td>
            <td><input type="number" step="0.01" min="0" class="form-control intl" name="installments[${i}][international_amount]" value="${intlVal || ''}" placeholder="Intl Fee"></td>
            <td>
                <input type="text" class="form-control currency-display" value="${escapeHtml(localVal > 0 ? 'LKR' : (intlVal > 0 ? intlCur : ''))}" readonly>
                <input type="hidden" name="installments[${i}][currency]" class="currency-hidden" value="${escapeHtml(intlVal > 0 ? intlCur : 'LKR')}">
            </td>
            <td><button type="button" class="btn btn-danger btn-sm removeRow"><i class="ti ti-trash me-1"></i>Remove</button></td>
        </tr>`;
}

function renderNewPlan(installments) {
    const tbody = document.querySelector('#newPlanTable tbody');
    tbody.innerHTML = '';
    if (!installments || !installments.length) {
        tbody.insertAdjacentHTML('beforeend', buildPlanRow(0));
        return;
    }
    installments.forEach((inst, i) => {
        tbody.insertAdjacentHTML('beforeend', buildPlanRow(
            i,
            inst.due_date,
            inst.base_amount ?? inst.amount ?? 0,
            inst.international_amount ?? 0,
            inst.currency ?? globalCurrency
        ));
    });
}

function reindexRows() {
    document.querySelectorAll('#newPlanTable tbody tr').forEach((row, index) => {
        const firstCell = row.querySelector('td:first-child');
        if (firstCell) firstCell.textContent = index + 1;
        row.querySelectorAll('input[name]').forEach(input => {
            input.name = input.name.replace(/\[\d+\]/, `[${index}]`);
        });
    });
}

function getCreatedPerPage() {
    const n = parseInt(document.getElementById('createdPlansPerPage')?.value || '10', 10);
    return Number.isFinite(n) && n > 0 ? n : 10;
}

function renderCreatedPagination(total, page, perPage) {
    const bar = document.getElementById('createdPlansPaginationBar');
    const rangeEl = document.getElementById('createdPlansRange');
    const pagesEl = document.getElementById('createdPlansPagination');
    if (!total) {
        bar.style.display = 'none';
        rangeEl.textContent = '';
        pagesEl.innerHTML = '';
        return;
    }
    const lastPage = Math.max(1, Math.ceil(total / perPage));
    rangeEl.textContent = `Showing ${((page - 1) * perPage) + 1}–${Math.min(total, page * perPage)} of ${total}`;
    bar.style.display = 'flex';
    let html = `<li class="page-item ${page <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-created-page="${page - 1}">Prev</a></li>`;
    const windowSize = 5;
    let start = Math.max(1, page - 2);
    let end = Math.min(lastPage, start + windowSize - 1);
    start = Math.max(1, end - windowSize + 1);
    for (let p = start; p <= end; p++) {
        html += `<li class="page-item ${p === page ? 'active' : ''}"><a class="page-link" href="#" data-created-page="${p}">${p}</a></li>`;
    }
    html += `<li class="page-item ${page >= lastPage ? 'disabled' : ''}"><a class="page-link" href="#" data-created-page="${page + 1}">Next</a></li>`;
    pagesEl.innerHTML = html;
}

function renderCreatedPlans(installments) {
    createdPlanRows = Array.isArray(installments) ? installments : [];
    const tbody = document.querySelector('#createdPlansTable tbody');
    const perPage = getCreatedPerPage();
    const lastPage = Math.max(1, Math.ceil(createdPlanRows.length / perPage) || 1);
    createdPlansPage = Math.min(Math.max(1, createdPlansPage || 1), lastPage);

    if (!createdPlanRows.length) {
        tbody.innerHTML = emptyRow(7, 'No created plans found.');
        renderCreatedPagination(0, 1, perPage);
        return;
    }

    const start = (createdPlansPage - 1) * perPage;
    tbody.innerHTML = createdPlanRows.slice(start, start + perPage).map((inst, offset) => `
        <tr>
            <td>${start + offset + 1}</td>
            <td>${escapeHtml(formatDate(inst.due_date))}</td>
            <td>${escapeHtml(formatMoney(inst.base_amount ?? inst.amount))}</td>
            <td>${escapeHtml(formatMoney(inst.international_amount))}</td>
            <td>${escapeHtml(inst.international_currency || 'LKR')}</td>
            <td>${escapeHtml(inst.installment_type || '-')}</td>
            <td>${statusBadge(inst.status)}</td>
        </tr>
    `).join('');
    renderCreatedPagination(createdPlanRows.length, createdPlansPage, perPage);
}

async function lookupStudent(nic, { showAlerts = false, loadPlanIfReady = false } = {}) {
    const key = String(nic || '').trim();
    if (!key) {
        if (lookupAbort) {
            lookupAbort.abort();
            lookupAbort = null;
        }
        document.getElementById('studentInfoCard').style.display = 'none';
        document.getElementById('paymentSection').style.display = 'none';
        document.getElementById('student_id').value = '';
        document.getElementById('course_id').value = '';
        resetCourseSelect('Enter NIC or Student ID');
        return null;
    }

    if (lookupAbort) {
        lookupAbort.abort();
    }
    lookupAbort = new AbortController();
    resetCourseSelect('Loading courses...');

    try {
        const res = await fetch(@json(route('repeat.payment.search')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ student_nic: key }),
            signal: lookupAbort.signal,
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
            document.getElementById('studentInfoCard').style.display = 'none';
            document.getElementById('paymentSection').style.display = 'none';
            document.getElementById('student_id').value = '';
            resetCourseSelect('No student found');
            if (showAlerts) {
                await notify('error', 'Not found', data.message || 'Student not found.');
            }
            return null;
        }

        const card = document.getElementById('studentInfoCard');
        card.innerHTML = `<strong>${escapeHtml(data.student.full_name || 'Student')}</strong><br>Student ID: ${escapeHtml(data.student.student_id)} &nbsp;|&nbsp; NIC: ${escapeHtml(data.student.id_value || '-')}`;
        card.style.display = 'block';
        document.getElementById('student_id').value = data.student.student_id;

        const courses = data.courses || [];
        if (!courses.length) {
            resetCourseSelect('No courses found for this student');
            document.getElementById('paymentSection').style.display = 'none';
            if (showAlerts) {
                await notify('warning', 'No courses', 'This student has no course registrations.');
            }
            return data;
        }

        const autoSelected = populateCourseSelect(courses);
        if (loadPlanIfReady && (autoSelected || document.getElementById('courseSelect').value)) {
            await loadPlan(data.student.student_id, document.getElementById('courseSelect').value);
        } else if (loadPlanIfReady && showAlerts) {
            document.getElementById('paymentSection').style.display = 'none';
            await notify('info', 'Select a course', 'Choose a course, then the payment plan will load.');
        }
        return data;
    } catch (error) {
        if (error.name === 'AbortError') {
            return null;
        }
        resetCourseSelect('Unable to load courses');
        if (showAlerts) {
            await notify('error', 'Search failed', error.message || 'Unable to search for this student.');
        }
        return null;
    }
}

async function loadPlan(studentId, courseId) {
    document.getElementById('student_id').value = studentId;
    document.getElementById('course_id').value = courseId;

    const [planRes, createdRes] = await Promise.all([
        fetch(@json(url('/api/repeat-payment-plan')) + `/${encodeURIComponent(studentId)}/${encodeURIComponent(courseId)}`, {
            headers: { 'Accept': 'application/json' },
        }),
        fetch(@json(url('/api/repeat-created-plans')) + `/${encodeURIComponent(studentId)}/${encodeURIComponent(courseId)}`, {
            headers: { 'Accept': 'application/json' },
        }),
    ]);

    const planData = await planRes.json().catch(() => ({}));
    const createdData = await createdRes.json().catch(() => ({}));
    if (!planRes.ok || !planData.success) {
        throw new Error(planData.message || 'Error fetching plan.');
    }

    document.getElementById('paymentSection').style.display = 'block';
    renderArchived(planData.archived_installments || []);
    renderCurrent(planData.current_plan);
    renderNewPlan(planData.current_plan?.installments || []);
    createdPlansPage = 1;
    renderCreatedPlans(createdData.installments || []);
}

document.getElementById('searchForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const nic = document.getElementById('nic').value.trim();
    const courseId = document.getElementById('courseSelect').value;
    const studentId = document.getElementById('student_id').value;
    if (!nic) {
        await notify('warning', 'Missing student', 'Enter NIC or Student ID.');
        return;
    }
    if (!courseId) {
        await notify('info', 'Select a course', 'Choose a course, then load the payment plan.');
        return;
    }

    const searchBtn = document.getElementById('searchStudentBtn');
    searchBtn.disabled = true;
    try {
        if (!studentId) {
            await lookupStudent(nic, { showAlerts: true, loadPlanIfReady: true });
        } else {
            await loadPlan(studentId, courseId);
        }
    } catch (error) {
        await notify('error', 'Load failed', error.message || 'Unable to load the payment plan.');
    } finally {
        setLoadPlanEnabled(Boolean(document.getElementById('courseSelect').value));
    }
});

document.getElementById('nic').addEventListener('input', function () {
    const nic = this.value.trim();
    clearTimeout(lookupTimer);
    document.getElementById('paymentSection').style.display = 'none';

    if (!nic) {
        lookupStudent('');
        return;
    }

    resetCourseSelect('Loading courses...');
    lookupTimer = setTimeout(() => {
        lookupStudent(nic, { showAlerts: false, loadPlanIfReady: true });
    }, 250);
});

document.getElementById('courseSelect').addEventListener('change', async function () {
    if (ignoreCourseChange) return;
    const studentId = document.getElementById('student_id').value;
    setLoadPlanEnabled(Boolean(this.value));
    if (!studentId || !this.value) return;
    try {
        await loadPlan(studentId, this.value);
    } catch (error) {
        await notify('error', 'Load failed', error.message || 'Unable to load the payment plan.');
    }
});

document.addEventListener('input', e => {
    if (!e.target.classList.contains('local') && !e.target.classList.contains('intl')) return;
    const row = e.target.closest('tr');
    const localInput = row.querySelector('.local');
    const intlInput = row.querySelector('.intl');
    const currencyDisplay = row.querySelector('.currency-display');
    const currencyHidden = row.querySelector('.currency-hidden');

    if (e.target.classList.contains('local') && e.target.value !== '') {
        intlInput.value = '';
        intlInput.readOnly = true;
        localInput.readOnly = false;
        currencyDisplay.value = 'LKR';
        currencyHidden.value = 'LKR';
    } else if (e.target.classList.contains('intl') && e.target.value !== '') {
        localInput.value = '';
        localInput.readOnly = true;
        intlInput.readOnly = false;
        currencyDisplay.value = globalCurrency;
        currencyHidden.value = globalCurrency;
    } else {
        localInput.readOnly = false;
        intlInput.readOnly = false;
        currencyDisplay.value = '';
        currencyHidden.value = 'LKR';
    }
});

document.getElementById('addRow').addEventListener('click', () => {
    const tbody = document.querySelector('#newPlanTable tbody');
    tbody.insertAdjacentHTML('beforeend', buildPlanRow(tbody.rows.length));
    reindexRows();
});

document.addEventListener('click', async e => {
    const pageLink = e.target.closest('[data-created-page]');
    if (pageLink) {
        e.preventDefault();
        if (pageLink.closest('.disabled')) return;
        createdPlansPage = parseInt(pageLink.getAttribute('data-created-page'), 10) || 1;
        renderCreatedPlans(createdPlanRows);
        return;
    }

    const removeBtn = e.target.closest('.removeRow');
    if (!removeBtn) return;
    const result = await Swal.fire({
        icon: 'warning',
        title: 'Remove this row?',
        text: 'This installment will be removed from the new plan.',
        showCancelButton: true,
        confirmButtonText: 'Remove',
        cancelButtonText: 'Cancel',
        confirmButtonColor: '#dc3545',
    });
    if (!result.isConfirmed) return;
    removeBtn.closest('tr').remove();
    const tbody = document.querySelector('#newPlanTable tbody');
    if (!tbody.rows.length) {
        tbody.insertAdjacentHTML('beforeend', buildPlanRow(0));
    }
    reindexRows();
});

document.getElementById('createdPlansPerPage')?.addEventListener('change', function () {
    createdPlansPage = 1;
    renderCreatedPlans(createdPlanRows);
});

document.getElementById('newPlanForm').addEventListener('submit', async function (e) {
    e.preventDefault();
    const studentId = document.getElementById('student_id').value;
    const courseId = document.getElementById('course_id').value;
    if (!studentId || !courseId) {
        await notify('warning', 'Missing details', 'Search for a student and select a course first.');
        return;
    }

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('student_id', studentId);
    formData.append('course_id', courseId);

    const rows = [...document.querySelectorAll('#newPlanTable tbody tr')];
    let count = 0;
    rows.forEach(row => {
        const due = row.querySelector('input[type="date"]')?.value;
        const local = row.querySelector('.local')?.value;
        const intl = row.querySelector('.intl')?.value;
        const currency = row.querySelector('.currency-hidden')?.value || 'LKR';
        if (!due) return;
        formData.append(`installments[${count}][due_date]`, due);
        formData.append(`installments[${count}][local_amount]`, local || 0);
        formData.append(`installments[${count}][international_amount]`, intl || 0);
        formData.append(`installments[${count}][currency]`, currency);
        count += 1;
    });

    if (!count) {
        await notify('warning', 'Missing installments', 'Add at least one installment with a due date.');
        return;
    }

    const saveBtn = document.getElementById('savePlanBtn');
    saveBtn.disabled = true;
    try {
        const res = await fetch(@json(route('repeat.payment.save')), {
            method: 'POST',
            body: formData,
            headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || !data.success) {
            await notify('error', 'Save failed', data.message || 'Error occurred while saving.');
            return;
        }
        await notify('success', 'Saved', data.message || 'New payment plan created successfully.');
        await loadPlan(studentId, courseId);
    } catch (error) {
        await notify('error', 'Save failed', 'Error occurred while saving.');
    } finally {
        saveBtn.disabled = false;
    }
});
</script>
@endsection
