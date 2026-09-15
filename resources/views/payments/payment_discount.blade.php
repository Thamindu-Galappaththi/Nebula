@extends('inc.app')

@section('title', 'NEBULA | Payment Discount')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<style nonce="{{ $cspNonce }}">
  .payment-discount-page [class*="col-"] {
    min-width: 0;
  }
  .payment-discount-page .form-select,
  .payment-discount-page .form-control {
    max-width: 100%;
  }
  .payment-discount-tabs {
    flex-wrap: wrap;
    overflow: visible;
  }
  .payment-discount-tabs .nav-link {
    white-space: nowrap;
  }
  .payment-discount-table-scroll {
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  .payment-discount-table-scroll table {
    min-width: 720px;
    margin-bottom: 0;
  }
  .payment-discount-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
  }
  .payment-discount-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }
  .payment-discount-page-size {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .payment-discount-page-size select {
    width: auto;
    min-width: 4.5rem;
  }
  .payment-discount-pagination {
    max-width: 100%;
    overflow-x: auto;
  }
  .payment-discount-pagination .pagination {
    flex-wrap: wrap;
    margin-bottom: 0;
  }
  @media (max-width: 767.98px) {
    .payment-discount-page .card-body {
      padding: 1rem 0.75rem;
    }
    .payment-discount-add-btn,
    #saveEditDiscount {
      width: 100%;
    }
    .edit-discount-footer {
      flex-direction: column;
    }
    .edit-discount-footer .btn {
      width: 100%;
    }
  }
</style>

<div class="container-fluid px-2 px-md-3 payment-discount-page">
  <div class="card">
    <div class="card-body">
      <h2 class="text-center mb-4">Payment Discount</h2>
      <hr>
      <ul class="nav nav-tabs mb-4 payment-discount-tabs" id="discountTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active bg-primary text-white" id="local-course-discounts-tab" data-bs-toggle="tab" data-bs-target="#local-course-discounts" type="button" role="tab" aria-controls="local-course-discounts" aria-selected="true">Discounts for Local Course Fee</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="registration-discounts-tab" data-bs-toggle="tab" data-bs-target="#registration-discounts" type="button" role="tab" aria-controls="registration-discounts" aria-selected="false">Discounts for Registration Fee</button>
        </li>
      </ul>

      <div class="tab-content" id="discountTabsContent">
        <div class="tab-pane fade show active" id="local-course-discounts" role="tabpanel" aria-labelledby="local-course-discounts-tab">
          <form id="local-course-discount-form" novalidate>
            <input type="hidden" id="localCourseDiscountCategory" value="local_course_fee">
            <div class="row g-2 mb-3 align-items-center">
              <label class="col-md-3 col-form-label fw-bold" for="localCourseDiscountName">Name of Discount <span class="text-danger">*</span></label>
              <div class="col-md-9">
                <input type="text" class="form-control" id="localCourseDiscountName" name="discount_name" required maxlength="255" autocomplete="off">
              </div>
            </div>
            <div class="row g-2 mb-3 align-items-center">
              <label class="col-md-3 col-form-label fw-bold" for="localCourseDiscountType">Discount Type <span class="text-danger">*</span></label>
              <div class="col-md-9">
                <select class="form-select" id="localCourseDiscountType" name="discount_type" required>
                  <option value="" selected disabled>Select Type</option>
                  <option value="amount">Amount</option>
                  <option value="percentage">Percentage</option>
                </select>
              </div>
            </div>
            <div class="row g-2 mb-3 align-items-center">
              <label class="col-md-3 col-form-label fw-bold" id="localCourseDiscountValueLabel" for="localCourseDiscountValue">Amount <span class="text-danger">*</span></label>
              <div class="col-md-9">
                <input type="number" class="form-control" id="localCourseDiscountValue" name="discount_value" min="0.01" step="0.01" required>
              </div>
            </div>
            <div class="row mb-4">
              <div class="col-12 col-md-9 offset-md-3">
                <button type="submit" class="btn btn-success payment-discount-add-btn" id="addLocalCourseDiscount">
                  <i class="ti ti-plus"></i> Add Discount
                </button>
              </div>
            </div>
          </form>

          <h5 class="mb-3">Created Discounts for Local Course Fee</h5>
          <div class="payment-discount-table-scroll">
            <table class="table table-bordered table-hover align-middle" id="localCourseDiscountsTable">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Discount Name</th>
                  <th>Type</th>
                  <th>Value</th>
                  <th>Created Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <div class="payment-discount-footer mt-3" data-pagination="local_course_fee">
            <div class="payment-discount-page-size">
              <label class="form-label mb-0 small text-muted" for="localPerPage">Per page</label>
              <select id="localPerPage" class="form-select form-select-sm page-size-select" data-category="local_course_fee">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>
            <div class="text-muted small" data-range="local_course_fee"></div>
            <nav class="payment-discount-pagination" aria-label="Local course discounts pages">
              <ul class="pagination pagination-sm mb-0" data-pages="local_course_fee"></ul>
            </nav>
          </div>
        </div>

        <div class="tab-pane fade" id="registration-discounts" role="tabpanel" aria-labelledby="registration-discounts-tab">
          <form id="registration-discount-form" novalidate>
            <input type="hidden" id="registrationDiscountCategory" value="registration_fee">
            <div class="row g-2 mb-3 align-items-center">
              <label class="col-md-3 col-form-label fw-bold" for="registrationDiscountName">Name of Discount <span class="text-danger">*</span></label>
              <div class="col-md-9">
                <input type="text" class="form-control" id="registrationDiscountName" name="discount_name" required maxlength="255" autocomplete="off">
              </div>
            </div>
            <div class="row g-2 mb-3 align-items-center">
              <label class="col-md-3 col-form-label fw-bold" for="registrationDiscountType">Discount Type <span class="text-danger">*</span></label>
              <div class="col-md-9">
                <select class="form-select" id="registrationDiscountType" name="discount_type" required>
                  <option value="" selected disabled>Select Type</option>
                  <option value="amount">Amount</option>
                  <option value="percentage">Percentage</option>
                </select>
              </div>
            </div>
            <div class="row g-2 mb-3 align-items-center">
              <label class="col-md-3 col-form-label fw-bold" id="registrationDiscountValueLabel" for="registrationDiscountValue">Amount <span class="text-danger">*</span></label>
              <div class="col-md-9">
                <input type="number" class="form-control" id="registrationDiscountValue" name="discount_value" min="0.01" step="0.01" required>
              </div>
            </div>
            <div class="row mb-4">
              <div class="col-12 col-md-9 offset-md-3">
                <button type="submit" class="btn btn-success payment-discount-add-btn" id="addRegistrationDiscount">
                  <i class="ti ti-plus"></i> Add Discount
                </button>
              </div>
            </div>
          </form>

          <h5 class="mb-3">Created Discounts for Registration Fee</h5>
          <div class="payment-discount-table-scroll">
            <table class="table table-bordered table-hover align-middle" id="registrationDiscountsTable">
              <thead class="table-light">
                <tr>
                  <th>#</th>
                  <th>Discount Name</th>
                  <th>Type</th>
                  <th>Value</th>
                  <th>Created Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>
          </div>
          <div class="payment-discount-footer mt-3" data-pagination="registration_fee">
            <div class="payment-discount-page-size">
              <label class="form-label mb-0 small text-muted" for="registrationPerPage">Per page</label>
              <select id="registrationPerPage" class="form-select form-select-sm page-size-select" data-category="registration_fee">
                <option value="10" selected>10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
              </select>
            </div>
            <div class="text-muted small" data-range="registration_fee"></div>
            <nav class="payment-discount-pagination" aria-label="Registration discounts pages">
              <ul class="pagination pagination-sm mb-0" data-pages="registration_fee"></ul>
            </nav>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="editDiscountModal" tabindex="-1" aria-labelledby="editDiscountModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form id="edit-discount-form" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="editDiscountModalLabel">Edit Discount</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="editDiscountId">
          <input type="hidden" id="editDiscountCategory">
          <div class="mb-3">
            <label class="form-label fw-bold" for="editDiscountName">Name of Discount <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="editDiscountName" required maxlength="255" autocomplete="off">
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold" for="editDiscountType">Discount Type <span class="text-danger">*</span></label>
            <select class="form-select" id="editDiscountType" required>
              <option value="" disabled>Select Type</option>
              <option value="amount">Amount</option>
              <option value="percentage">Percentage</option>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label fw-bold" id="editDiscountValueLabel" for="editDiscountValue">Amount <span class="text-danger">*</span></label>
            <input type="number" class="form-control" id="editDiscountValue" min="0.01" step="0.01" required>
          </div>
        </div>
        <div class="modal-footer edit-discount-footer">
          <button type="submit" class="btn btn-primary" id="saveEditDiscount">
            <i class="ti ti-device-floppy"></i> Update Discount
          </button>
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', () => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const editModalEl = document.getElementById('editDiscountModal');
  const editModal = editModalEl && typeof bootstrap !== 'undefined'
    ? bootstrap.Modal.getOrCreateInstance(editModalEl)
    : null;

  const state = {
    local_course_fee: { page: 1, perPage: 10 },
    registration_fee: { page: 1, perPage: 10 },
  };

  const tables = {
    local_course_fee: document.querySelector('#localCourseDiscountsTable tbody'),
    registration_fee: document.querySelector('#registrationDiscountsTable tbody'),
  };

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
  }

  function showToast(title, message, bg) {
    const container = document.querySelector('.toast-container');
    if (!container) return;
    const el = document.createElement('div');
    el.className = `toast align-items-center text-white ${bg} border-0`;
    el.role = 'alert';
    el.ariaLive = 'assertive';
    el.ariaAtomic = 'true';
    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body"><strong>${escapeHtml(title)}:</strong> ${escapeHtml(message)}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>`;
    container.appendChild(el);
    if (typeof bootstrap !== 'undefined') {
      new bootstrap.Toast(el).show();
    }
    el.addEventListener('hidden.bs.toast', () => el.remove());
  }

  function jsonHeaders() {
    return {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrfToken,
      'Accept': 'application/json',
    };
  }

  function requiredStarHtml(label) {
    return `${label} <span class="text-danger">*</span>`;
  }

  function syncValueField(typeSelect, valueInput, labelEl) {
    const isPercentage = typeSelect.value === 'percentage';
    labelEl.innerHTML = requiredStarHtml(isPercentage ? 'Percentage' : 'Amount');
    valueInput.min = '0.01';
    if (isPercentage) {
      valueInput.max = '100';
    } else {
      valueInput.removeAttribute('max');
    }
  }

  function readForm(form) {
    const name = form.querySelector('[name="discount_name"], #editDiscountName').value.trim();
    const type = form.querySelector('[name="discount_type"], #editDiscountType').value;
    const value = parseFloat(form.querySelector('[name="discount_value"], #editDiscountValue').value);
    return { name, type, value };
  }

  function validateDiscount({ name, type, value }) {
    if (!name) return 'Please enter a discount name.';
    if (!type) return 'Please select a discount type.';
    if (!Number.isFinite(value) || value <= 0) return 'Please enter a valid discount value.';
    if (type === 'percentage' && value > 100) return 'Percentage cannot be greater than 100.';
    return null;
  }

  function typeLabel(type) {
    return type === 'percentage' ? 'Percentage' : 'Amount';
  }

  function valueDisplay(discount) {
    const numericValue = parseFloat(discount.value) || 0;
    return discount.type === 'percentage'
      ? `${numericValue}%`
      : `LKR ${numericValue.toFixed(2)}`;
  }

  function confirmDelete() {
    if (typeof Swal === 'undefined') {
      return Promise.resolve(false);
    }
    return Swal.fire({
      title: 'Delete discount?',
      text: 'This discount will no longer be available.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      cancelButtonColor: '#6c757d',
      confirmButtonText: 'Yes, delete it',
      cancelButtonText: 'Cancel',
      reverseButtons: true,
      focusCancel: true,
    }).then(result => result.isConfirmed);
  }

  function renderPagination(category, meta) {
    const rangeEl = document.querySelector(`[data-range="${category}"]`);
    const pagesEl = document.querySelector(`[data-pages="${category}"]`);
    if (!rangeEl || !pagesEl) return;

    const total = Number(meta.total || 0);
    const page = Number(meta.current_page || 1);
    const lastPage = Math.max(1, Number(meta.last_page || 1));
    pagesEl.innerHTML = '';

    if (!total) {
      rangeEl.textContent = '';
      return;
    }

    rangeEl.textContent = `Showing ${meta.from} to ${meta.to} of ${total}`;

    const addItem = (label, targetPage, options = {}) => {
      const li = document.createElement('li');
      li.className = 'page-item';
      if (options.disabled) li.classList.add('disabled');
      if (options.active) li.classList.add('active');
      const el = document.createElement(options.disabled || options.active ? 'span' : 'button');
      el.className = 'page-link';
      el.textContent = label;
      if (el.tagName === 'BUTTON') {
        el.type = 'button';
        el.addEventListener('click', () => loadDiscounts(category, targetPage));
      }
      li.appendChild(el);
      pagesEl.appendChild(li);
    };

    addItem('Previous', page - 1, { disabled: page <= 1 });
    const start = Math.max(1, page - 2);
    const end = Math.min(lastPage, page + 2);
    if (start > 1) {
      addItem('1', 1);
      if (start > 2) addItem('...', page, { disabled: true });
    }
    for (let i = start; i <= end; i++) {
      addItem(String(i), i, { active: i === page });
    }
    if (end < lastPage) {
      if (end < lastPage - 1) addItem('...', page, { disabled: true });
      addItem(String(lastPage), lastPage);
    }
    addItem('Next', page + 1, { disabled: page >= lastPage });
  }

  function renderTable(category, discounts, meta) {
    const tbody = tables[category];
    if (!tbody) return;
    const from = Number(meta.from || 1);

    if (!discounts.length) {
      tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No discounts found</td></tr>';
      renderPagination(category, meta);
      return;
    }

    tbody.innerHTML = discounts.map((discount, index) => {
      const status = String(discount.status || 'active');
      const statusClass = status.toLowerCase() === 'active' ? 'success' : 'secondary';
      return `<tr>
        <td>${from + index}</td>
        <td>${escapeHtml(discount.name)}</td>
        <td><span class="badge bg-${discount.type === 'percentage' ? 'info' : 'primary'}">${escapeHtml(typeLabel(discount.type))}</span></td>
        <td>${escapeHtml(valueDisplay(discount))}</td>
        <td>${discount.created_at ? escapeHtml(new Date(discount.created_at).toLocaleDateString()) : '-'}</td>
        <td><span class="badge bg-${statusClass}">${escapeHtml(status.charAt(0).toUpperCase() + status.slice(1))}</span></td>
        <td>
          <div class="payment-discount-actions">
            <button type="button" class="btn btn-sm btn-warning edit-discount"
              data-id="${escapeHtml(discount.id)}"
              data-name="${escapeHtml(discount.name)}"
              data-type="${escapeHtml(discount.type)}"
              data-value="${escapeHtml(discount.value)}"
              data-category="${escapeHtml(category)}">
              <i class="ti ti-edit"></i>
            </button>
            <button type="button" class="btn btn-sm btn-danger delete-discount"
              data-id="${escapeHtml(discount.id)}"
              data-category="${escapeHtml(category)}">
              <i class="ti ti-trash"></i>
            </button>
          </div>
        </td>
      </tr>`;
    }).join('');
    renderPagination(category, meta);
  }

  async function loadDiscounts(category, page) {
    const current = state[category];
    current.page = page || current.page;
    try {
      const response = await fetch('{{ route('payment.discount.get.discounts.by.category') }}', {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({
          category,
          page: current.page,
          per_page: current.perPage,
        }),
      });
      const data = await response.json();
      if (!data.success) {
        showToast('Error', data.message || 'Failed to load discounts.', 'bg-danger');
        return;
      }
      if (Array.isArray(data.discounts) && data.discounts.length === 0 && Number(data.current_page) > 1) {
        return loadDiscounts(category, Number(data.current_page) - 1);
      }
      current.page = Number(data.current_page || 1);
      renderTable(category, data.discounts || [], data);
    } catch (error) {
      showToast('Error', 'Error loading discounts. Please try again.', 'bg-danger');
    }
  }

  async function saveDiscount(payload, isUpdate = false) {
    const url = isUpdate
      ? '{{ route('payment.discount.update.discount') }}'
      : '{{ route('payment.discount.save.discount') }}';
    const response = await fetch(url, {
      method: 'POST',
      headers: jsonHeaders(),
      body: JSON.stringify(payload),
    });
    const data = await response.json().catch(() => ({ success: false, message: 'Request failed.' }));
    if (!response.ok || !data.success) {
      throw new Error(data.message || 'Request failed.');
    }
    return data;
  }

  function bindTypeSync(typeSelect, valueInput, labelEl) {
    typeSelect.addEventListener('change', () => syncValueField(typeSelect, valueInput, labelEl));
    syncValueField(typeSelect, valueInput, labelEl);
  }

  function bindCreateForm(form, category) {
    const typeSelect = form.querySelector('[name="discount_type"]');
    const valueInput = form.querySelector('[name="discount_value"]');
    const labelEl = form.querySelector('label[id$="DiscountValueLabel"]');
    bindTypeSync(typeSelect, valueInput, labelEl);

    form.addEventListener('submit', async (event) => {
      event.preventDefault();
      if (!form.reportValidity()) return;
      const values = readForm(form);
      const error = validateDiscount(values);
      if (error) {
        showToast('Error', error, 'bg-danger');
        return;
      }
      const submitBtn = form.querySelector('button[type="submit"]');
      submitBtn.disabled = true;
      try {
        await saveDiscount({
          name: values.name,
          type: values.type,
          discount_category: category,
          value: values.value,
        });
        showToast('Success', 'Discount saved successfully.', 'bg-success');
        form.reset();
        typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
        syncValueField(typeSelect, valueInput, labelEl);
        state[category].page = 1;
        loadDiscounts(category, 1);
      } catch (err) {
        showToast('Error', err.message, 'bg-danger');
      } finally {
        submitBtn.disabled = false;
      }
    });
  }

  bindCreateForm(document.getElementById('local-course-discount-form'), 'local_course_fee');
  bindCreateForm(document.getElementById('registration-discount-form'), 'registration_fee');
  bindTypeSync(
    document.getElementById('editDiscountType'),
    document.getElementById('editDiscountValue'),
    document.getElementById('editDiscountValueLabel')
  );

  document.getElementById('edit-discount-form').addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    if (!form.reportValidity()) return;
    const values = {
      name: document.getElementById('editDiscountName').value.trim(),
      type: document.getElementById('editDiscountType').value,
      value: parseFloat(document.getElementById('editDiscountValue').value),
    };
    const error = validateDiscount(values);
    if (error) {
      showToast('Error', error, 'bg-danger');
      return;
    }
    const submitBtn = document.getElementById('saveEditDiscount');
    submitBtn.disabled = true;
    try {
      await saveDiscount({
        id: document.getElementById('editDiscountId').value,
        name: values.name,
        type: values.type,
        discount_category: document.getElementById('editDiscountCategory').value,
        value: values.value,
      }, true);
      showToast('Success', 'Discount updated successfully.', 'bg-success');
      editModal?.hide();
      loadDiscounts(document.getElementById('editDiscountCategory').value);
    } catch (err) {
      showToast('Error', err.message, 'bg-danger');
    } finally {
      submitBtn.disabled = false;
    }
  });

  document.addEventListener('click', async (event) => {
    const editBtn = event.target.closest('.edit-discount');
    if (editBtn) {
      document.getElementById('editDiscountId').value = editBtn.dataset.id || '';
      document.getElementById('editDiscountCategory').value = editBtn.dataset.category || '';
      document.getElementById('editDiscountName').value = editBtn.dataset.name || '';
      const typeSelect = document.getElementById('editDiscountType');
      typeSelect.value = editBtn.dataset.type || '';
      typeSelect.dispatchEvent(new Event('change', { bubbles: true }));
      document.getElementById('editDiscountValue').value = editBtn.dataset.value || '';
      syncValueField(typeSelect, document.getElementById('editDiscountValue'), document.getElementById('editDiscountValueLabel'));
      editModal?.show();
      return;
    }

    const deleteBtn = event.target.closest('.delete-discount');
    if (!deleteBtn) return;
    const confirmed = await confirmDelete();
    if (!confirmed) return;
    try {
      const response = await fetch('{{ route('payment.discount.delete.discount') }}', {
        method: 'POST',
        headers: jsonHeaders(),
        body: JSON.stringify({ id: Number(deleteBtn.dataset.id) }),
      });
      const data = await response.json().catch(() => ({ success: false, message: 'Request failed.' }));
      if (!response.ok || !data.success) {
        throw new Error(data.message || 'Error deleting discount.');
      }
      showToast('Success', 'Discount deleted successfully.', 'bg-success');
      loadDiscounts(deleteBtn.dataset.category);
    } catch (err) {
      showToast('Error', err.message, 'bg-danger');
    }
  });

  document.querySelectorAll('.page-size-select[data-category]').forEach(select => {
    select.addEventListener('change', () => {
      const category = select.dataset.category;
      state[category].perPage = Number(select.value || 10);
      loadDiscounts(category, 1);
    });
  });

  document.querySelectorAll('#discountTabs .nav-link').forEach(tab => {
    tab.addEventListener('shown.bs.tab', (event) => {
      document.querySelectorAll('#discountTabs .nav-link').forEach(link => link.classList.remove('bg-primary', 'text-white'));
      event.target.classList.add('bg-primary', 'text-white');
    });
  });

  loadDiscounts('local_course_fee', 1);
  loadDiscounts('registration_fee', 1);
});
</script>
@endpush
