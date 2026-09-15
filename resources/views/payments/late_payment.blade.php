@extends('inc.app')

@section('title', 'NEBULA | Late Payment Management')

@section('content')
<style nonce="{{ $cspNonce }}">
.late-payment-page [class*="col-"] {
    min-width: 0;
}
.late-payment-page .form-select,
.late-payment-page .form-control,
.late-payment-page .nebula-select,
.late-payment-page .input-group {
    max-width: 100%;
}
.late-payment-search {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.5rem;
    align-items: stretch;
}
.late-payment-search .form-control {
    flex: 1 1 auto;
    min-width: 0;
}
.late-payment-search .btn {
    flex: 0 0 auto;
    white-space: nowrap;
}
.late-payment-info-value {
    word-break: break-word;
}
.late-payment-summary-card h5 {
    font-size: 0.95rem;
}
.late-payment-table-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
}
.late-payment-table-scroll table {
    min-width: 860px;
    margin-bottom: 0;
}
.late-payment-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
}
.late-payment-page-size {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.late-payment-page-size select,
.late-payment-page-size .nebula-select,
.late-payment-page-size .nebula-select-sm {
    width: 5.75rem;
    max-width: 5.75rem;
    min-width: 4.5rem;
    flex: 0 0 5.75rem;
}
.late-payment-pagination {
    max-width: 100%;
    overflow-x: auto;
}
.late-payment-pagination .pagination {
    flex-wrap: wrap;
    margin-bottom: 0;
}
.late-payment-toast-container {
    position: fixed;
    top: 16px;
    right: 16px;
    z-index: 10900;
    max-width: min(400px, calc(100vw - 24px));
}
.late-payment-toast {
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    margin-bottom: 10px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    border-left: 4px solid;
    min-width: 0;
}
.late-payment-toast.success { border-left-color: #10b981; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); }
.late-payment-toast.error { border-left-color: #ef4444; background: linear-gradient(135deg, #fef2f2 0%, #fee2e2 100%); }
.late-payment-toast.warning { border-left-color: #f59e0b; background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); }
.late-payment-toast.info { border-left-color: #3b82f6; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); }
.late-payment-toast-icon {
    width: 24px;
    height: 24px;
    margin-right: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    color: white;
    flex-shrink: 0;
}
.late-payment-toast.success .late-payment-toast-icon { background: #10b981; }
.late-payment-toast.error .late-payment-toast-icon { background: #ef4444; }
.late-payment-toast.warning .late-payment-toast-icon { background: #f59e0b; }
.late-payment-toast.info .late-payment-toast-icon { background: #3b82f6; }
.late-payment-toast-content { flex: 1; min-width: 0; }
.late-payment-toast-title { font-weight: 600; margin-bottom: 4px; color: #1f2937; }
.late-payment-toast-message { color: #6b7280; font-size: 14px; word-break: break-word; }
.late-payment-toast-close {
    background: none;
    border: none;
    color: #6b7280;
    cursor: pointer;
    padding: 4px;
    margin-left: 8px;
}
@keyframes latePaymentSlideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
.late-payment-toast.slide-in {
    animation: latePaymentSlideIn 0.3s ease-out;
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
#spinner-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    display: none;
    justify-content: center;
    align-items: center;
    z-index: 9999;
}
@media (max-width: 767.98px) {
    .late-payment-page .card-body {
        padding: 1rem 0.75rem;
    }
    .late-payment-search {
        flex-wrap: wrap;
    }
    .late-payment-search .form-control,
    .late-payment-search .btn {
        width: 100%;
    }
    .late-payment-toast {
        min-width: 0;
        width: 100%;
    }
}
</style>

<div class="container-fluid px-2 px-md-3 late-payment-page">
    <div class="card shadow border-0">
        <div class="card-body">
            <h2 class="text-center mb-4">Late Payment Management</h2>
            <hr>

            <div id="spinner-overlay" style="display:none;">
                <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
            </div>
            <div id="toastContainer" class="late-payment-toast-container" aria-live="polite" aria-atomic="true"></div>

            <form id="latePaymentSearchForm" class="mb-4" novalidate>
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-bold" for="student-nic">Student NIC / Student ID <span class="text-danger">*</span></label>
                        <div class="late-payment-search">
                            <input type="text" class="form-control" id="student-nic" placeholder="Enter Student NIC or Student ID" required autocomplete="off">
                            <button class="btn btn-primary" type="submit" id="latePaymentSearchBtn">
                                <i class="ti ti-search me-1"></i>Search
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label fw-bold" for="course-select">Course <span class="text-danger">*</span></label>
                        <select class="form-select" id="course-select" required disabled>
                            <option value="" selected disabled>Select Course</option>
                        </select>
                    </div>
                </div>
            </form>

            <div id="student-info" class="mt-4" style="display: none;">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="ti ti-user me-2"></i>Student Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="d-flex align-items-start">
                                    <i class="ti ti-user-circle text-primary flex-shrink-0" style="font-size: 2rem;"></i>
                                    <div class="ms-3 min-width-0">
                                        <h6 class="mb-1 text-muted">Student Name</h6>
                                        <h5 class="mb-0 late-payment-info-value" id="student-name-display">-</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="d-flex align-items-start">
                                    <i class="ti ti-id text-info flex-shrink-0" style="font-size: 2rem;"></i>
                                    <div class="ms-3 min-width-0">
                                        <h6 class="mb-1 text-muted">Student ID</h6>
                                        <h5 class="mb-0 late-payment-info-value" id="student-id-display">-</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="d-flex align-items-start">
                                    <i class="ti ti-book text-warning flex-shrink-0" style="font-size: 2rem;"></i>
                                    <div class="ms-3 min-width-0">
                                        <h6 class="mb-1 text-muted">Course</h6>
                                        <h5 class="mb-0 late-payment-info-value" id="course-name-display">-</h5>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-sm-6 col-lg-3">
                                <div class="d-flex align-items-start">
                                    <i class="ti ti-currency-dollar text-success flex-shrink-0" style="font-size: 2rem;"></i>
                                    <div class="ms-3 min-width-0">
                                        <h6 class="mb-1 text-muted">Total Course Fee</h6>
                                        <h5 class="mb-0 late-payment-info-value" id="total-course-fee-display">-</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="summary-cards" class="mt-4" style="display: none;">
                <div class="row g-3 mb-2">
                    <div class="col-6 col-lg-3">
                        <div class="card bg-primary text-white h-100 late-payment-summary-card">
                            <div class="card-body text-center">
                                <h5>Total Installments</h5>
                                <h3 id="total-installments">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card bg-success text-white h-100 late-payment-summary-card">
                            <div class="card-body text-center">
                                <h5>Paid Installments</h5>
                                <h3 id="paid-installments">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card bg-warning text-white h-100 late-payment-summary-card">
                            <div class="card-body text-center">
                                <h5>Late Installments</h5>
                                <h3 id="late-installments">0</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 col-lg-3">
                        <div class="card bg-danger text-white h-100 late-payment-summary-card">
                            <div class="card-body text-center">
                                <h5>Total Late Fees</h5>
                                <h3 id="total-late-fees">LKR 0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div id="payment-plan-section" class="mt-4" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ti ti-calendar me-2"></i>Payment Plan - Local Course Fee
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="late-payment-table-scroll">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center">Installment #</th>
                                        <th class="text-center">Due Date</th>
                                        <th class="text-center">Amount</th>
                                        <th class="text-center">Status</th>
                                        <th class="text-center">Days Late</th>
                                        <th class="text-center">Late Fee</th>
                                        <th class="text-center">Approved Late Fee</th>
                                        <th class="text-center">Total Due</th>
                                    </tr>
                                </thead>
                                <tbody id="payment-plan-table-body"></tbody>
                            </table>
                        </div>
                        <div class="late-payment-footer mt-3" id="planPaginationBar" style="display:none;">
                            <div class="late-payment-page-size">
                                <label class="form-label mb-0 small text-muted" for="planPerPage">Per page</label>
                                <select id="planPerPage" class="form-select form-select-sm page-size-select">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="text-muted small" id="planRange"></div>
                            <nav class="late-payment-pagination" aria-label="Payment plan pages">
                                <ul class="pagination pagination-sm mb-0" id="planPagination"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>

            <div id="paid-payments-section" class="mt-4" style="display: none;">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="ti ti-check me-2"></i>Paid Local Course Fee Payment Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="late-payment-table-scroll">
                            <table class="table table-bordered table-striped align-middle">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="text-center">Payment Date</th>
                                        <th class="text-center">Amount</th>
                                        <th class="text-center">Payment Method</th>
                                        <th class="text-center">Receipt No</th>
                                        <th class="text-center">Installment #</th>
                                        <th class="text-center">Due Date</th>
                                        <th class="text-center">Days Late</th>
                                        <th class="text-center">Late Fee Paid</th>
                                        <th class="text-center">Uploaded Receipt</th>
                                    </tr>
                                </thead>
                                <tbody id="paid-payments-table-body"></tbody>
                            </table>
                        </div>
                        <div class="late-payment-footer mt-3" id="paidPaginationBar" style="display:none;">
                            <div class="late-payment-page-size">
                                <label class="form-label mb-0 small text-muted" for="paidPerPage">Per page</label>
                                <select id="paidPerPage" class="form-select form-select-sm page-size-select">
                                    <option value="10" selected>10</option>
                                    <option value="25">25</option>
                                    <option value="50">50</option>
                                    <option value="100">100</option>
                                </select>
                            </div>
                            <div class="text-muted small" id="paidRange"></div>
                            <nav class="late-payment-pagination" aria-label="Paid payment pages">
                                <ul class="pagination pagination-sm mb-0" id="paidPagination"></ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script nonce="{{ $cspNonce }}">
let currentStudentData = null;
let currentPaymentPlan = null;
let currentPaidPayments = [];
let planPage = 1;
let paidPage = 1;

function csrfToken() {
    return '{{ csrf_token() }}';
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

function formatMoney(amount) {
    return 'LKR ' + Number(amount || 0).toLocaleString();
}

function formatDate(value) {
    if (!value) return 'N/A';
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return escapeHtml(value);
    return date.toLocaleDateString();
}

function wholeDays(value) {
    const days = Math.round(Number(value) || 0);
    return Number.isFinite(days) ? Math.max(0, days) : 0;
}

function daysLateBadge(days, lateClass) {
    const count = wholeDays(days);
    if (count <= 0) {
        return '<span class="text-muted">-</span>';
    }
    return `<span class="badge ${lateClass}">${count} days</span>`;
}

function getPerPage(selectId) {
    const n = parseInt(document.getElementById(selectId)?.value || '10', 10);
    return Number.isFinite(n) && n > 0 ? n : 10;
}

function showSpinner(show) {
    document.getElementById('spinner-overlay').style.display = show ? 'flex' : 'none';
}

function hideAllSections() {
    document.getElementById('student-info').style.display = 'none';
    document.getElementById('payment-plan-section').style.display = 'none';
    document.getElementById('paid-payments-section').style.display = 'none';
    document.getElementById('summary-cards').style.display = 'none';
}

function showToast(title, message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `late-payment-toast ${type} slide-in`;
    toast.innerHTML = `
        <div class="late-payment-toast-icon">
            <i class="ti ti-${type === 'success' ? 'check' : type === 'error' ? 'x' : type === 'warning' ? 'alert-triangle' : 'info'}"></i>
        </div>
        <div class="late-payment-toast-content">
            <div class="late-payment-toast-title">${escapeHtml(title)}</div>
            <div class="late-payment-toast-message">${escapeHtml(message)}</div>
        </div>
        <button type="button" class="late-payment-toast-close" aria-label="Close">
            <i class="ti ti-x"></i>
        </button>
    `;
    toastContainer.appendChild(toast);
    toast.querySelector('.late-payment-toast-close')?.addEventListener('click', () => toast.remove());
    setTimeout(() => {
        if (toast.parentElement) toast.remove();
    }, 5000);
}

async function postJson(url, body) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || data.success === false) {
        const error = new Error(data.message || 'Request failed.');
        error.status = response.status;
        throw error;
    }
    return data;
}

function renderPagination(barId, rangeId, pagesId, total, page, perPage, dataAttr) {
    const bar = document.getElementById(barId);
    const rangeEl = document.getElementById(rangeId);
    const pagesEl = document.getElementById(pagesId);
    if (!bar || !rangeEl || !pagesEl) return;

    if (!total) {
        bar.style.display = 'none';
        rangeEl.textContent = '';
        pagesEl.innerHTML = '';
        return;
    }

    const lastPage = Math.max(1, Math.ceil(total / perPage));
    const from = ((page - 1) * perPage) + 1;
    const to = Math.min(total, page * perPage);
    rangeEl.textContent = `Showing ${from}–${to} of ${total}`;
    bar.style.display = 'flex';

    let html = `<li class="page-item ${page <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" ${dataAttr}="${page - 1}">Prev</a></li>`;
    const windowSize = 5;
    let start = Math.max(1, page - 2);
    let end = Math.min(lastPage, start + windowSize - 1);
    start = Math.max(1, end - windowSize + 1);
    for (let p = start; p <= end; p++) {
        html += `<li class="page-item ${p === page ? 'active' : ''}"><a class="page-link" href="#" ${dataAttr}="${p}">${p}</a></li>`;
    }
    html += `<li class="page-item ${page >= lastPage ? 'disabled' : ''}"><a class="page-link" href="#" ${dataAttr}="${page + 1}">Next</a></li>`;
    pagesEl.innerHTML = html;
}

function resetCourseSelect() {
    const courseSelect = document.getElementById('course-select');
    courseSelect.innerHTML = '<option value="" selected disabled>Select Course</option>';
    courseSelect.disabled = true;
    syncCustomSelect(courseSelect);
}

function populateCourseSelect(courses) {
    const courseSelect = document.getElementById('course-select');
    courseSelect.innerHTML = '<option value="" selected disabled>Select Course</option>';

    if (courses && courses.length > 0) {
        courses.forEach(course => {
            const option = document.createElement('option');
            option.value = course.course_id;
            option.textContent = `${course.course_name} (Registered: ${course.registration_date})`;
            courseSelect.appendChild(option);
        });
        courseSelect.disabled = false;
        if (courses.length === 1) {
            courseSelect.value = courses[0].course_id;
        }
        syncCustomSelect(courseSelect);
        return courses.length === 1;
    }

    courseSelect.innerHTML = '<option value="" selected disabled>No courses found for this student</option>';
    courseSelect.disabled = true;
    syncCustomSelect(courseSelect);
    return false;
}

async function loadStudentCourses(studentNic, { autoLoad = false } = {}) {
    if (!studentNic) {
        resetCourseSelect();
        return false;
    }

    const courseSelect = document.getElementById('course-select');
    courseSelect.innerHTML = '<option value="" selected disabled>Loading courses...</option>';
    courseSelect.disabled = true;
    syncCustomSelect(courseSelect);

    try {
        const data = await postJson(@json(route('late.payment.get.student.courses')), {
            student_nic: studentNic,
        });
        const autoSelected = populateCourseSelect(data.courses || []);
        if (autoLoad && autoSelected) {
            return true;
        }
        if (autoLoad && (data.courses || []).length > 1) {
            showToast('Select a course', 'This student has more than one course. Choose one to continue.', 'info');
        }
        return autoSelected;
    } catch (error) {
        resetCourseSelect();
        showToast('Error', error.message || 'Unable to load courses.', 'error');
        return false;
    }
}

function displayStudentInfo(student) {
    document.getElementById('student-name-display').textContent = student.student_name || 'N/A';
    document.getElementById('student-id-display').textContent = student.student_id || 'N/A';
    document.getElementById('course-name-display').textContent = student.course_name || 'N/A';
    document.getElementById('total-course-fee-display').textContent = formatMoney(student.total_amount || student.course_fee || 0);
    document.getElementById('student-info').style.display = 'block';
}

function showSummaryCards(totalInstallments, paidInstallments, lateInstallments, totalLateFees) {
    document.getElementById('total-installments').textContent = totalInstallments;
    document.getElementById('paid-installments').textContent = paidInstallments;
    document.getElementById('late-installments').textContent = lateInstallments;
    document.getElementById('total-late-fees').textContent = formatMoney(totalLateFees);
    document.getElementById('summary-cards').style.display = 'block';
}

function displayPaymentPlan(paymentPlan) {
    currentPaymentPlan = paymentPlan;
    const installments = paymentPlan?.installments || [];
    const tbody = document.getElementById('payment-plan-table-body');
    const perPage = getPerPage('planPerPage');
    const lastPage = Math.max(1, Math.ceil(installments.length / perPage) || 1);
    planPage = Math.min(Math.max(1, planPage || 1), lastPage);

    if (!installments.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="text-center text-muted py-4">
                    <i class="ti ti-inbox" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0">No payment plan data available</p>
                </td>
            </tr>
        `;
        document.getElementById('payment-plan-section').style.display = 'block';
        showSummaryCards(0, 0, 0, 0);
        renderPagination('planPaginationBar', 'planRange', 'planPagination', 0, 1, perPage, 'data-plan-page');
        return;
    }

    const paidInstallments = installments.filter(i => i.status === 'paid').length;
    const lateInstallments = installments.filter(i => i.is_late).length;
    const totalLateFees = installments.reduce((sum, i) => sum + Number(i.effective_late_fee ?? i.late_fee ?? 0), 0);
    showSummaryCards(installments.length, paidInstallments, lateInstallments, totalLateFees);

    const start = (planPage - 1) * perPage;
    tbody.innerHTML = installments.slice(start, start + perPage).map(installment => `
        <tr>
            <td class="text-center">${escapeHtml(installment.installment_number)}</td>
            <td class="text-center">${formatDate(installment.due_date)}</td>
            <td class="text-center fw-bold">${escapeHtml(formatMoney(installment.amount))}</td>
            <td class="text-center">
                <span class="badge bg-${installment.status === 'paid' ? 'success' : 'warning'}">
                    ${escapeHtml(installment.status || 'pending')}
                </span>
            </td>
            <td class="text-center">
                ${installment.is_late ? daysLateBadge(installment.days_late, 'bg-danger') : '<span class="text-muted">-</span>'}
            </td>
            <td class="text-center">${escapeHtml(formatMoney(installment.late_fee || 0))}</td>
            <td class="text-center">
                ${Number(installment.approved_late_fee) > 0
                    ? `<span class="text-success">${escapeHtml(formatMoney(installment.approved_late_fee))}</span>`
                    : '<span class="text-muted">-</span>'}
            </td>
            <td class="text-center fw-bold">${escapeHtml(formatMoney(installment.total_due || 0))}</td>
        </tr>
    `).join('');

    document.getElementById('payment-plan-section').style.display = 'block';
    renderPagination('planPaginationBar', 'planRange', 'planPagination', installments.length, planPage, perPage, 'data-plan-page');
}

function displayPaidPayments(paidPayments) {
    currentPaidPayments = Array.isArray(paidPayments) ? paidPayments : [];
    const tbody = document.getElementById('paid-payments-table-body');
    const perPage = getPerPage('paidPerPage');
    const lastPage = Math.max(1, Math.ceil(currentPaidPayments.length / perPage) || 1);
    paidPage = Math.min(Math.max(1, paidPage || 1), lastPage);

    if (!currentPaidPayments.length) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center text-muted py-4">
                    <i class="ti ti-inbox" style="font-size: 2rem;"></i>
                    <p class="mt-2 mb-0">No paid payment data available</p>
                </td>
            </tr>
        `;
        document.getElementById('paid-payments-section').style.display = 'block';
        renderPagination('paidPaginationBar', 'paidRange', 'paidPagination', 0, 1, perPage, 'data-paid-page');
        return;
    }

    const start = (paidPage - 1) * perPage;
    tbody.innerHTML = currentPaidPayments.slice(start, start + perPage).map(payment => {
        const slip = payment.paid_slip_path
            ? `<a href="/storage/${encodeURI(String(payment.paid_slip_path).replace(/^\/+/, ''))}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary"><i class="ti ti-download me-1"></i>View</a>`
            : '<span class="text-muted">Not uploaded</span>';

        return `
            <tr>
                <td class="text-center">${formatDate(payment.payment_date)}</td>
                <td class="text-center fw-bold">${escapeHtml(formatMoney(payment.amount))}</td>
                <td class="text-center">${escapeHtml(payment.payment_method || 'N/A')}</td>
                <td class="text-center">${escapeHtml(payment.receipt_no || 'N/A')}</td>
                <td class="text-center">${escapeHtml(payment.installment_number || 'N/A')}</td>
                <td class="text-center">${payment.due_date ? formatDate(payment.due_date) : 'N/A'}</td>
                <td class="text-center">${daysLateBadge(payment.days_late, 'bg-warning')}</td>
                <td class="text-center">
                    ${Number(payment.late_fee_paid) > 0
                        ? `<span class="text-danger fw-bold">${escapeHtml(formatMoney(payment.late_fee_paid))}</span>`
                        : '<span class="text-muted">-</span>'}
                </td>
                <td class="text-center">${slip}</td>
            </tr>
        `;
    }).join('');

    document.getElementById('paid-payments-section').style.display = 'block';
    renderPagination('paidPaginationBar', 'paidRange', 'paidPagination', currentPaidPayments.length, paidPage, perPage, 'data-paid-page');
}

async function loadPaymentPlan(studentNic, courseId) {
    const data = await postJson(@json(route('late.payment.get.payment.plan')), {
        student_nic: studentNic,
        course_id: parseInt(courseId, 10) || courseId,
    });
    currentStudentData = data.student;
    displayStudentInfo(data.student);
    planPage = 1;
    displayPaymentPlan(data.payment_plan);
}

async function loadPaidPaymentDetails(studentNic, courseId) {
    try {
        const data = await postJson(@json(route('late.payment.get.paid.payments')), {
            student_nic: studentNic,
            course_id: parseInt(courseId, 10) || courseId,
        });
        paidPage = 1;
        displayPaidPayments(data.paid_payments || []);
    } catch (error) {
        paidPage = 1;
        displayPaidPayments([]);
    }
}

async function loadLatePaymentData() {
    const studentNic = document.getElementById('student-nic').value.trim();
    const courseId = document.getElementById('course-select').value;

    if (!studentNic) {
        showToast('Error', 'Please enter Student NIC or Student ID.', 'error');
        return;
    }
    if (!courseId) {
        showToast('Error', 'Please select a course.', 'error');
        return;
    }

    showSpinner(true);
    hideAllSections();
    try {
        await Promise.all([
            loadPaymentPlan(studentNic, courseId),
            loadPaidPaymentDetails(studentNic, courseId),
        ]);
        showToast('Success', 'Payment plan loaded successfully.', 'success');
    } catch (error) {
        displayPaymentPlan(null);
        displayPaidPayments([]);
        showToast('Error', error.message || 'An unexpected error occurred.', 'error');
    } finally {
        showSpinner(false);
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const studentNicInput = document.getElementById('student-nic');
    const courseSelect = document.getElementById('course-select');
    let debounceTimer;

    document.getElementById('latePaymentSearchForm').addEventListener('submit', async function (e) {
        e.preventDefault();
        const studentNic = studentNicInput.value.trim();
        if (!studentNic) {
            showToast('Error', 'Please enter Student NIC or Student ID.', 'error');
            return;
        }

        const searchBtn = document.getElementById('latePaymentSearchBtn');
        searchBtn.disabled = true;
        try {
            if (courseSelect.value) {
                await loadLatePaymentData();
            } else {
                await loadStudentCourses(studentNic, { autoLoad: true });
            }
        } finally {
            searchBtn.disabled = false;
        }
    });

    courseSelect.addEventListener('change', function () {
        if (this.value) {
            loadLatePaymentData();
        }
    });

    studentNicInput.addEventListener('input', function () {
        const studentNic = this.value.trim();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            if (studentNic) {
                loadStudentCourses(studentNic);
            } else {
                resetCourseSelect();
                hideAllSections();
            }
        }, 500);
    });

    document.getElementById('planPerPage')?.addEventListener('change', function () {
        planPage = 1;
        displayPaymentPlan(currentPaymentPlan);
    });
    document.getElementById('paidPerPage')?.addEventListener('change', function () {
        paidPage = 1;
        displayPaidPayments(currentPaidPayments);
    });
});

document.addEventListener('click', function (e) {
    const planLink = e.target.closest('[data-plan-page]');
    if (planLink) {
        e.preventDefault();
        if (planLink.closest('.disabled')) return;
        planPage = parseInt(planLink.getAttribute('data-plan-page'), 10) || 1;
        displayPaymentPlan(currentPaymentPlan);
        return;
    }

    const paidLink = e.target.closest('[data-paid-page]');
    if (paidLink) {
        e.preventDefault();
        if (paidLink.closest('.disabled')) return;
        paidPage = parseInt(paidLink.getAttribute('data-paid-page'), 10) || 1;
        displayPaidPayments(currentPaidPayments);
    }
});
</script>
@endsection
