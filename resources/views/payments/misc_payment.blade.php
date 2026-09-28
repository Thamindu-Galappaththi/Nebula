@extends('inc.app')

@section('title', 'NEBULA | Miscellaneous Payments')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<style nonce="{{ $cspNonce }}">
  .misc-payment-page [class*="col-"] {
    min-width: 0;
  }
  .misc-payment-page .form-select,
  .misc-payment-page .form-control,
  .misc-payment-page .nebula-select,
  .misc-payment-page .input-group {
    max-width: 100%;
  }
  .misc-payment-page .input-group {
    flex-wrap: nowrap;
    align-items: stretch;
  }
  .misc-payment-search {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.5rem;
    align-items: stretch;
  }
  .misc-payment-search .form-control {
    flex: 1 1 auto;
    min-width: 0;
  }
  .misc-payment-search .btn {
    flex: 0 0 auto;
    white-space: nowrap;
  }
  .misc-payment-table-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .misc-payment-table-scroll table {
    min-width: 640px;
    margin-bottom: 0;
  }
  .misc-payment-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }
  .misc-payment-page-size {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .misc-payment-page-size select,
  .misc-payment-page-size .nebula-select,
  .misc-payment-page-size .nebula-select-sm {
    width: 5.75rem;
    max-width: 5.75rem;
    min-width: 4.5rem;
    flex: 0 0 5.75rem;
  }
  .misc-payment-pagination {
    max-width: 100%;
    overflow-x: auto;
  }
  .misc-payment-pagination .pagination {
    flex-wrap: wrap;
    margin-bottom: 0;
  }
  .misc-payment-student {
    word-break: break-word;
  }
  @media (max-width: 767.98px) {
    .misc-payment-page .card-body {
      padding: 1rem 0.75rem;
    }
    .misc-payment-search {
      flex-wrap: wrap;
    }
    .misc-payment-search .form-control,
    .misc-payment-search .btn,
    #saveMiscPaymentBtn {
      width: 100%;
    }
    .misc-pay-modal-footer {
      flex-direction: column;
    }
    .misc-pay-modal-footer .btn {
      width: 100%;
    }
  }
</style>

<div class="container-fluid px-2 px-md-3 misc-payment-page">
  <div class="card shadow border-0">
    <div class="card-body">
      <h2 class="text-center mb-4">Miscellaneous Payment Entry</h2>
      <hr>

      <form id="searchForm" class="mb-4" novalidate>
        <label class="form-label fw-bold" for="student_id">Student NIC / Student ID <span class="text-danger">*</span></label>
        <div class="misc-payment-search">
          <input type="text" class="form-control" id="student_id" name="student_id" placeholder="Enter NIC or Student ID" required autocomplete="off">
          <button class="btn btn-primary" type="submit" id="searchStudentBtn">
            <i class="ti ti-search me-1"></i>Search
          </button>
        </div>
      </form>

      <div id="studentInfoCard" class="alert alert-info misc-payment-student" style="display:none;"></div>

      <div id="paymentSection" style="display:none;">
        <form id="miscForm" novalidate>
          @csrf
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-6">
              <label class="form-label fw-bold" for="misc_category">Payment Category <span class="text-danger">*</span></label>
              <select name="misc_category" id="misc_category" class="form-select" required>
                <option value="" selected disabled>Select Category</option>
                <option value="Library Fine">Library Fine</option>
                <option value="Certificate Reprint">Certificate Reprint</option>
                <option value="ID Card Replacement">ID Card Replacement</option>
                <option value="Event / Exam Fee">Event / Exam Fee</option>
                <option value="Hostel Fee">Hostel Fee</option>
                <option value="other">Other</option>
              </select>
            </div>
            <div class="col-12 col-md-6" id="otherFieldContainer" style="display:none;">
              <label class="form-label fw-bold" for="otherField">Specify Other Category <span class="text-danger">*</span></label>
              <input type="text" id="otherField" class="form-control" placeholder="Enter custom category" maxlength="255" autocomplete="off">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <label class="form-label fw-bold" for="misc_amount">Amount (LKR) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0.01" name="amount" id="misc_amount" class="form-control" required>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-bold" for="misc_payment_method">Payment Method <span class="text-danger">*</span></label>
              <select name="payment_method" id="misc_payment_method" class="form-select" required>
                <option value="" selected disabled>Select Method</option>
                <option value="cash">Cash</option>
                <option value="bank_transfer">Bank Transfer</option>
                <option value="cheque">Cheque</option>
                <option value="card">Card</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label fw-bold" for="misc_transaction_id">Transaction ID</label>
              <input type="text" name="transaction_id" id="misc_transaction_id" class="form-control" placeholder="If applicable" maxlength="255" autocomplete="off">
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-bold" for="misc_remarks">Remarks</label>
            <textarea name="remarks" id="misc_remarks" class="form-control" rows="2" maxlength="500"></textarea>
          </div>

          <button type="submit" class="btn btn-success" id="saveMiscPaymentBtn">
            <i class="ti ti-device-floppy me-1"></i>Save Payment
          </button>
        </form>

        <hr>
        <h5 class="mt-4">Recent Miscellaneous Payments</h5>
        <div class="misc-payment-table-scroll">
          <table class="table table-bordered align-middle" id="paymentTable">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Category</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Date</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="misc-payment-footer mt-3" id="miscPaymentsPaginationBar" style="display:none;">
          <div class="misc-payment-page-size">
            <label class="form-label mb-0 small text-muted" for="miscPaymentsPerPage">Per page</label>
            <select id="miscPaymentsPerPage" class="form-select form-select-sm page-size-select">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <div class="text-muted small" id="miscPaymentsRange"></div>
          <nav class="misc-payment-pagination" aria-label="Miscellaneous payment pages">
            <ul class="pagination pagination-sm mb-0" id="miscPaymentsPagination"></ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title" id="paymentModalLabel">Payment Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <ul class="list-group" id="paymentDetailList"></ul>
      </div>
      <div class="modal-footer misc-pay-modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
<script nonce="{{ $cspNonce }}">
const fetchUrlTemplate = @json(url('/misc-payment/fetch/__ID__'));
window.miscPayments = [];
window.miscPaymentsPage = 1;

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

function studentFetchUrl(studentId) {
  return fetchUrlTemplate.replace('__ID__', encodeURIComponent(studentId));
}

function getMiscPaymentsPerPage() {
  const select = document.getElementById('miscPaymentsPerPage');
  const n = parseInt(select?.value || '10', 10);
  return Number.isFinite(n) && n > 0 ? n : 10;
}

function formatMoney(amount) {
  return Number(amount || 0).toLocaleString('en-LK', { style: 'currency', currency: 'LKR' });
}

function formatMethod(method) {
  return String(method || '-')
    .replaceAll('_', ' ')
    .replace(/\b\w/g, letter => letter.toUpperCase());
}

function goToMiscPaymentsPage(page) {
  const records = Array.isArray(window.miscPayments) ? window.miscPayments : [];
  const perPage = getMiscPaymentsPerPage();
  const lastPage = Math.max(1, Math.ceil(records.length / perPage) || 1);
  window.miscPaymentsPage = Math.min(Math.max(1, parseInt(page, 10) || 1), lastPage);
  renderTable(window.miscPayments);
}

function renderMiscPaymentsPagination(total, page, perPage) {
  const bar = document.getElementById('miscPaymentsPaginationBar');
  const rangeEl = document.getElementById('miscPaymentsRange');
  const pagesEl = document.getElementById('miscPaymentsPagination');
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

  let html = `<li class="page-item ${page <= 1 ? 'disabled' : ''}"><a class="page-link" href="#" data-misc-page="${page - 1}">Prev</a></li>`;
  const windowSize = 5;
  let start = Math.max(1, page - 2);
  let end = Math.min(lastPage, start + windowSize - 1);
  start = Math.max(1, end - windowSize + 1);
  for (let p = start; p <= end; p++) {
    html += `<li class="page-item ${p === page ? 'active' : ''}"><a class="page-link" href="#" data-misc-page="${p}">${p}</a></li>`;
  }
  html += `<li class="page-item ${page >= lastPage ? 'disabled' : ''}"><a class="page-link" href="#" data-misc-page="${page + 1}">Next</a></li>`;
  pagesEl.innerHTML = html;
}

function showStudentInfo(student) {
  const card = document.getElementById('studentInfoCard');
  if (!card) return;
  if (!student) {
    card.style.display = 'none';
    card.innerHTML = '';
    return;
  }
  card.innerHTML = `<strong>${escapeHtml(student.full_name || 'Student')}</strong><br>Student ID: ${escapeHtml(student.student_id)} &nbsp;|&nbsp; NIC: ${escapeHtml(student.id_value || '-')}`;
  card.style.display = 'block';
}

document.getElementById('searchForm').addEventListener('submit', async e => {
  e.preventDefault();
  const studentId = document.getElementById('student_id').value.trim();
  if (!studentId) {
    notify('warning', 'Missing student', 'Enter NIC or Student ID.');
    return;
  }

  const searchBtn = document.getElementById('searchStudentBtn');
  searchBtn.disabled = true;
  try {
    const res = await fetch(studentFetchUrl(studentId), { headers: { 'Accept': 'application/json' } });
    const data = await res.json();
    if (!res.ok || !data.success) {
      document.getElementById('paymentSection').style.display = 'none';
      showStudentInfo(null);
      notify('error', 'Not found', data.message || 'No records found.');
      return;
    }
    showStudentInfo(data.student);
    document.getElementById('paymentSection').style.display = 'block';
    window.miscPaymentsPage = 1;
    renderTable(data.payments || []);
  } catch (err) {
    notify('error', 'Search failed', 'Unable to search for this student.');
  } finally {
    searchBtn.disabled = false;
  }
});

const categorySelect = document.getElementById('misc_category');
const otherFieldContainer = document.getElementById('otherFieldContainer');
const otherField = document.getElementById('otherField');

categorySelect.addEventListener('change', () => {
  if (categorySelect.value === 'other') {
    otherFieldContainer.style.display = 'block';
    otherField.required = true;
  } else {
    otherFieldContainer.style.display = 'none';
    otherField.required = false;
    otherField.value = '';
  }
});

otherField.addEventListener('input', () => {
  const val = otherField.value;
  if (val.length > 0) {
    otherField.value = val.charAt(0).toUpperCase() + val.slice(1);
  }
});

document.getElementById('miscForm').addEventListener('submit', async e => {
  e.preventDefault();
  const studentId = document.getElementById('student_id').value.trim();
  if (!studentId) {
    notify('warning', 'Missing student', 'Search for a student first.');
    return;
  }

  if (categorySelect.value === 'other' && !otherField.value.trim()) {
    notify('warning', 'Missing category', 'Enter a custom category.');
    return;
  }

  const formData = new FormData(e.target);
  formData.append('student_id', studentId);

  if (formData.get('misc_category') === 'other') {
    formData.set('misc_category', otherField.value.trim());
  }

  const saveBtn = document.getElementById('saveMiscPaymentBtn');
  saveBtn.disabled = true;
  try {
    const res = await fetch(@json(route('misc.payment.store')), {
      method: 'POST',
      body: formData,
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json',
      },
    });
    const data = await res.json();
    if (!res.ok || !data.success) {
      notify('error', 'Save failed', data.message || 'Error saving payment.');
      return;
    }

    await notify('success', 'Saved', data.message || 'Miscellaneous payment recorded successfully.');
    const reload = await fetch(studentFetchUrl(studentId), { headers: { 'Accept': 'application/json' } });
    const newData = await reload.json();
    if (newData.success) {
      showStudentInfo(newData.student);
      window.miscPaymentsPage = 1;
      renderTable(newData.payments || []);
    }
    e.target.reset();
    otherFieldContainer.style.display = 'none';
    otherField.required = false;
    syncCustomSelect(categorySelect);
    syncCustomSelect(document.getElementById('misc_payment_method'));
  } catch (err) {
    notify('error', 'Save failed', 'An error occurred while saving the payment.');
  } finally {
    saveBtn.disabled = false;
  }
});

document.addEventListener('click', function (e) {
  const pageLink = e.target.closest('[data-misc-page]');
  if (pageLink) {
    e.preventDefault();
    if (pageLink.closest('.disabled')) return;
    goToMiscPaymentsPage(pageLink.dataset.miscPage);
    return;
  }

  const viewBtn = e.target.closest('.viewBtn');
  if (viewBtn) {
    const payment = (window.miscPayments || []).find(item => String(item.id) === String(viewBtn.dataset.id));
    if (payment) showPaymentModal(payment);
  }
});

document.getElementById('miscPaymentsPerPage')?.addEventListener('change', function () {
  window.miscPaymentsPage = 1;
  renderTable(window.miscPayments);
});

function renderTable(payments) {
  const tbody = document.querySelector('#paymentTable tbody');
  window.miscPayments = Array.isArray(payments) ? payments : [];
  tbody.innerHTML = '';

  const records = window.miscPayments;
  const perPage = getMiscPaymentsPerPage();
  const lastPage = Math.max(1, Math.ceil(records.length / perPage) || 1);
  const page = Math.min(Math.max(1, window.miscPaymentsPage || 1), lastPage);
  window.miscPaymentsPage = page;

  if (!records.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No payments found.</td></tr>';
    renderMiscPaymentsPagination(0, 1, perPage);
    return;
  }

  const start = (page - 1) * perPage;
  records.slice(start, start + perPage).forEach((p, offset) => {
    tbody.insertAdjacentHTML('beforeend', `
      <tr>
        <td>${start + offset + 1}</td>
        <td>${escapeHtml(p.misc_category || '-')}</td>
        <td>${escapeHtml(formatMoney(p.amount))}</td>
        <td>${escapeHtml(formatMethod(p.payment_method))}</td>
        <td>${escapeHtml(p.created_at ? new Date(p.created_at).toLocaleDateString() : '-')}</td>
        <td><button type="button" class="btn btn-sm btn-outline-primary viewBtn" data-id="${escapeHtml(p.id)}">View</button></td>
      </tr>`);
  });

  renderMiscPaymentsPagination(records.length, page, perPage);
}

function showPaymentModal(p) {
  const list = document.getElementById('paymentDetailList');
  list.innerHTML = `
    <li class="list-group-item"><strong>Category:</strong> ${escapeHtml(p.misc_category)}</li>
    <li class="list-group-item"><strong>Amount:</strong> ${escapeHtml(formatMoney(p.amount))}</li>
    <li class="list-group-item"><strong>Method:</strong> ${escapeHtml(formatMethod(p.payment_method))}</li>
    <li class="list-group-item"><strong>Date:</strong> ${escapeHtml(p.created_at ? new Date(p.created_at).toLocaleString() : '-')}</li>
    <li class="list-group-item"><strong>Transaction ID:</strong> ${escapeHtml(p.transaction_id ?? '-')}</li>
    <li class="list-group-item"><strong>Remarks:</strong> ${escapeHtml(p.description ?? p.remarks ?? '-')}</li>
  `;
  const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
  modal.show();
}
</script>
@endsection
