@extends('inc.app')

@section('title', 'Generate Course Badge')

@section('content')
<style nonce="{{ $cspNonce }}">
  .badge-generate-page,
  .badge-generate-page .card,
  .badge-generate-page .card-body {
    min-width: 0;
    max-width: 100%;
    overflow: visible;
  }
  .badge-generate-page [class*="col-"] {
    min-width: 0;
  }
  .badge-generate-page .form-select {
    max-width: 100%;
    text-overflow: ellipsis;
  }
  .badge-generate-page .form-label { font-weight: 600; }
  .badge-generate-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.75rem;
    flex-wrap: wrap;
  }
  .badge-generate-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .badge-generate-table-scroll {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
  }
  .badge-generate-table-scroll::-webkit-scrollbar { height: 10px; }
  .badge-generate-table-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 8px;
  }
  .badge-generate-table-scroll::-webkit-scrollbar-thumb {
    background: #b0b0b0;
    border-radius: 8px;
  }
  .badge-generate-table-scroll table {
    min-width: 920px;
    width: 100%;
    margin-bottom: 0;
  }
  .badge-generate-table-scroll th { white-space: nowrap; }
  .badge-row-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
  }
  #searchBtn { width: 100%; }
  .badge-generate-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding-bottom: 0.25rem;
  }
  .badge-generate-page-size {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .badge-generate-page-size select {
    width: auto;
    min-width: 4.5rem;
  }
  .badge-generate-pagination {
    max-width: 100%;
    overflow-x: auto;
  }
  .badge-generate-pagination .pagination {
    flex-wrap: wrap;
    margin-bottom: 0;
  }
  .error-message,
  .success-message {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    color: white;
    padding: 15px 20px;
    border-radius: 10px;
    font-weight: 500;
    font-size: 14px;
    max-width: min(400px, calc(100vw - 32px));
    transform: translateX(120%);
    transition: transform 0.3s ease-in-out;
    border-left: 4px solid #fff;
  }
  .error-message {
    background: linear-gradient(135deg, #dc3545, #e74c3c);
    box-shadow: 0 4px 15px rgba(220, 53, 69, 0.3);
  }
  .success-message {
    background: linear-gradient(135deg, #198754, #20c997);
    box-shadow: 0 4px 15px rgba(25, 135, 84, 0.3);
  }
  .error-message.show,
  .success-message.show { transform: translateX(0); }
  .error-message .error-icon,
  .success-message .success-icon {
    margin-right: 10px;
    font-size: 18px;
  }
  @media (max-width: 767.98px) {
    .badge-generate-toolbar,
    .badge-generate-footer {
      flex-direction: column;
      align-items: stretch;
    }
    .badge-generate-actions,
    .badge-generate-actions .btn {
      width: 100%;
    }
    .badge-generate-page .card-body {
      padding: 1rem 0.75rem;
    }
    .badge-generate-page-size {
      width: 100%;
    }
    .badge-generate-page-size select {
      width: 5.75rem;
      min-width: 5.75rem;
      flex: 0 0 5.75rem;
    }
    .badge-generate-pagination .pagination {
      justify-content: center;
    }
    .badge-row-actions,
    .badge-row-actions .btn {
      width: 100%;
    }
    .error-message,
    .success-message {
      right: 16px;
      left: 16px;
      max-width: none;
    }
  }
</style>

<div class="container-fluid px-2 px-md-3 badge-generate-page">
  <div class="card shadow border-0">
    <div class="card-body">
      @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          {{ session('success') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          {{ session('error') }}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      @endif

      <h3 class="text-center mb-4">Course Completion &amp; Badge Generation</h3>
      <hr>

      <form id="searchForm" class="row g-3 mb-4">
        @csrf
        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label" for="student_id">Student ID / NIC</label>
          <input type="text" id="student_id" name="student_id" class="form-control" placeholder="Enter Student ID or NIC" autocomplete="off">
        </div>

        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label" for="courseSelect">Course</label>
          <select id="courseSelect" name="course_id" class="form-select">
            <option value="">All Courses</option>
            @foreach($courses as $course)
              <option value="{{ $course->course_id }}">{{ $course->course_name }} ({{ $course->location }})</option>
            @endforeach
          </select>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label" for="intakeSelect">Intake</label>
          <select id="intakeSelect" name="intake_id" class="form-select">
            <option value="">All Intakes</option>
          </select>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label" for="modeSelect">Mode</label>
          <select id="modeSelect" name="mode" class="form-select">
            <option value="">All</option>
            <option value="Online">Online</option>
            <option value="Physical">Physical</option>
            <option value="Hybrid">Hybrid</option>
          </select>
        </div>

        <div class="col-12 col-md-6 col-lg-4 d-flex align-items-end">
          <button id="searchBtn" class="btn btn-primary w-100" type="submit">
            <span class="spinner-border spinner-border-sm d-none" id="searchSpinner" role="status"></span>
            <span id="searchText">Search</span>
          </button>
        </div>
      </form>

      <div class="badge-generate-toolbar mb-3">
        <div class="text-muted small align-self-center" id="resultCount"></div>
        <div class="badge-generate-actions">
          <button class="btn btn-outline-secondary btn-sm" id="clearFilters" type="button">
            <i class="ti ti-refresh me-1"></i> Clear Filters
          </button>
        </div>
      </div>

      <div id="resultSection" style="display:none;">
        <h5 class="fw-bold text-secondary mb-3">Search Results</h5>
        <div class="badge-generate-table-scroll">
          <table class="table table-bordered table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Student</th>
                <th>Course</th>
                <th>Type</th>
                <th>Intake</th>
                <th>Mode</th>
                <th>Status</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="courseRows"></tbody>
          </table>
        </div>
        <div class="badge-generate-footer mt-3" id="paginationBar" style="display:none;">
          <div class="badge-generate-page-size">
            <label class="form-label mb-0 small text-muted" for="perPageSelect">Per page</label>
            <select id="perPageSelect" class="form-select form-select-sm page-size-select">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <div class="text-muted small align-self-center" id="resultRange"></div>
          <nav class="badge-generate-pagination" aria-label="Badge results pages">
            <ul class="pagination pagination-sm" id="badgePagination"></ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="viewCertModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="ti ti-certificate me-2"></i>Certificate Details</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="viewCertBody">
        <div class="text-center p-5">
          <div class="spinner-border text-primary"></div>
        </div>
      </div>
      <div class="modal-footer flex-column flex-sm-row align-items-stretch align-items-sm-center justify-content-between gap-2">
        <small class="text-muted fst-italic">Generated by Nebula Institute of Technology</small>
        <a id="viewCertLink" href="#" target="_blank" rel="noopener" class="btn btn-outline-primary">
          <i class="ti ti-external-link"></i> View Certificate
        </a>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
const allCourses = @json($courses);
let currentPage = 1;
let lastPage = 1;
let totalCount = 0;
let hasSearched = false;

const courseSelect = document.getElementById('courseSelect');
const intakeSelect = document.getElementById('intakeSelect');

function escapeHtml(text) {
  const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
  return String(text ?? '').replace(/[&<>"']/g, m => map[m]);
}

function showMessage(message, type) {
  document.querySelectorAll('.success-message,.error-message').forEach(m => m.remove());
  const n = document.createElement('div');
  n.className = type === 'success' ? 'success-message' : 'error-message';
  const icon = type === 'success' ? 'ti-check-circle success-icon' : 'ti-alert-circle error-icon';
  n.innerHTML = `<i class="ti ${icon}"></i>${escapeHtml(message)}`;
  document.body.appendChild(n);
  setTimeout(() => n.classList.add('show'), 100);
  setTimeout(() => {
    n.classList.remove('show');
    setTimeout(() => n.remove(), 300);
  }, type === 'success' ? 4000 : 5000);
}

function showSuccessMessage(message) { showMessage(message, 'success'); }
function showErrorMessage(message) { showMessage(message, 'error'); }

function resetIntakeFilter() {
  intakeSelect.innerHTML = '';
  intakeSelect.add(new Option('All Intakes', ''));
}

function fillCourseSelect(courses, emptyLabel = 'All Courses') {
  courseSelect.innerHTML = '';
  courseSelect.add(new Option(emptyLabel, ''));
  (courses || []).forEach(course => {
    if (!course) return;
    const label = `${course.course_name || ''} (${course.location || '-'})`;
    courseSelect.add(new Option(label, course.course_id));
  });
}

function currentFilters(page = currentPage) {
  return {
    student_id: document.getElementById('student_id').value.trim(),
    course_id: courseSelect.value,
    intake_id: intakeSelect.value,
    mode: document.getElementById('modeSelect').value,
    page,
    per_page: Number(document.getElementById('perPageSelect')?.value || 10)
  };
}

function setSearchBusy(isBusy) {
  const btn = document.getElementById('searchBtn');
  const spin = document.getElementById('searchSpinner');
  const text = document.getElementById('searchText');
  btn.disabled = isBusy;
  spin.classList.toggle('d-none', !isBusy);
  text.textContent = isBusy ? 'Searching...' : 'Search';
}

function formatStatus(status) {
  const value = String(status || '').trim();
  if (!value || value === '-') return '-';
  if (value.toLowerCase() === 'completed') return 'Completed';
  if (value.toLowerCase() === 'pending') return 'Pending';
  return value.charAt(0).toUpperCase() + value.slice(1);
}

function isCompleted(status) {
  return String(status || '').toLowerCase() === 'completed';
}

function actionButtons(row) {
  const allow = !!row.eligible_for_badge;
  const badgeCode = row.badge?.verification_code;
  const badgeId = row.badge?.id;

  if (!allow) {
    return '<span class="text-muted">Not Eligible</span>';
  }

  if (isCompleted(row.status)) {
    if (badgeCode) {
      return `<div class="badge-row-actions">
        <button class="btn btn-info btn-sm btn-view-cert" type="button" data-code="${escapeHtml(badgeCode)}">
          <i class="ti ti-eye"></i> View
        </button>
        <button class="btn btn-danger btn-sm btn-cancel-badge" type="button" data-badge-id="${escapeHtml(badgeId || '')}" data-reg-id="${escapeHtml(row.id)}">
          <i class="ti ti-trash"></i> Cancel
        </button>
      </div>`;
    }
    return `<div class="badge-row-actions">
      <button class="btn btn-secondary btn-sm" type="button" disabled><i class="ti ti-badge"></i> Badge Missing</button>
      <button class="btn btn-danger btn-sm btn-cancel-badge" type="button" data-reg-id="${escapeHtml(row.id)}">
        <i class="ti ti-trash"></i> Cancel
      </button>
    </div>`;
  }

  return `<div class="badge-row-actions">
    <button class="btn btn-success btn-sm btn-mark-complete" type="button" data-reg-id="${escapeHtml(row.id)}">
      <i class="ti ti-badge"></i> Mark Completed &amp; Generate Badge
    </button>
  </div>`;
}

function renderPagination(meta) {
  const bar = document.getElementById('paginationBar');
  const ul = document.getElementById('badgePagination');
  const rangeEl = document.getElementById('resultRange');
  ul.innerHTML = '';

  const total = Number(meta.total || 0);
  const page = Number(meta.current_page || 1);
  const pages = Math.max(1, Number(meta.last_page || 1));
  currentPage = page;
  lastPage = pages;
  totalCount = total;
  bar.style.display = hasSearched ? 'flex' : 'none';

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
    const btn = document.createElement(options.disabled || options.active ? 'span' : 'button');
    btn.className = 'page-link';
    btn.textContent = label;
    if (btn.tagName === 'BUTTON') {
      btn.type = 'button';
      btn.addEventListener('click', () => searchRecords(targetPage));
    }
    li.appendChild(btn);
    ul.appendChild(li);
  };

  addItem('Previous', page - 1, { disabled: page <= 1 });
  const start = Math.max(1, page - 2);
  const end = Math.min(pages, page + 2);
  if (start > 1) {
    addItem('1', 1);
    if (start > 2) addItem('...', page, { disabled: true });
  }
  for (let i = start; i <= end; i++) {
    addItem(String(i), i, { active: i === page });
  }
  if (end < pages) {
    if (end < pages - 1) addItem('...', page, { disabled: true });
    addItem(String(pages), pages);
  }
  addItem('Next', page + 1, { disabled: page >= pages });
}

function renderResults(items, meta = {}) {
  const table = document.getElementById('courseRows');
  const countEl = document.getElementById('resultCount');
  table.innerHTML = '';

  const total = Number(meta.total ?? items.length);
  const from = Number(meta.from || (items.length ? 1 : 0));

  if (!items.length) {
    table.innerHTML = `<tr><td colspan="8" class="text-center text-muted p-3">No records found.</td></tr>`;
    document.getElementById('resultSection').style.display = 'block';
    countEl.textContent = '0 records';
    renderPagination({ total: 0, current_page: 1, last_page: 1 });
    return;
  }

  table.innerHTML = items.map((row, i) => {
    const student = row.student?.full_name || row.student?.name_with_initials || '-';
    const course = row.course?.course_name || '-';
    const type = row.course?.course_type || '-';
    const intake = row.intake?.batch || '-';
    const mode = row.intake?.intake_mode || '-';
    const status = formatStatus(row.status);

    return `<tr id="row-${escapeHtml(row.id)}">
      <td>${from + i}</td>
      <td>${escapeHtml(student)}</td>
      <td>${escapeHtml(course)}</td>
      <td>${escapeHtml(type)}</td>
      <td>${escapeHtml(intake)}</td>
      <td>${escapeHtml(mode)}</td>
      <td id="status-${escapeHtml(row.id)}">${escapeHtml(status)}</td>
      <td id="action-${escapeHtml(row.id)}">${actionButtons(row)}</td>
    </tr>`;
  }).join('');

  document.getElementById('resultSection').style.display = 'block';
  countEl.textContent = total + (total === 1 ? ' record' : ' records');
  renderPagination(meta);
}

async function searchRecords(page = 1) {
  setSearchBusy(true);
  currentPage = page;

  try {
    const res = await fetch('{{ route("badges.search") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify(currentFilters(page))
    });

    if (!res.ok) {
      throw new Error('Search failed');
    }

    const data = await res.json();
    hasSearched = true;
    renderResults(data.data || data.courses || [], data);
  } catch (error) {
    totalCount = 0;
    showErrorMessage('Could not load records. Please try again.');
  } finally {
    setSearchBusy(false);
  }
}

async function loadIntakes(courseId) {
  resetIntakeFilter();
  if (!courseId) return;

  try {
    const res = await fetch('{{ route("badges.intakes") }}?course_id=' + encodeURIComponent(courseId));
    const data = await res.json();
    if (!data.success || !Array.isArray(data.intakes)) return;
    data.intakes.forEach(intake => {
      const mode = intake.intake_mode ? ` (${intake.intake_mode})` : '';
      const location = intake.location ? ` - ${intake.location}` : '';
      intakeSelect.add(new Option(`${intake.batch}${location}${mode}`, intake.intake_id));
    });
  } catch (error) {
    resetIntakeFilter();
  }
}

document.getElementById('searchForm').addEventListener('submit', e => {
  e.preventDefault();
  searchRecords(1);
});

document.getElementById('clearFilters').addEventListener('click', () => {
  document.getElementById('searchForm').reset();
  fillCourseSelect(allCourses);
  resetIntakeFilter();
  hasSearched = false;
  currentPage = 1;
  lastPage = 1;
  totalCount = 0;
  document.getElementById('courseRows').innerHTML = '';
  document.getElementById('resultSection').style.display = 'none';
  document.getElementById('resultCount').textContent = '';
  document.getElementById('paginationBar').style.display = 'none';
  document.getElementById('badgePagination').innerHTML = '';
  document.getElementById('resultRange').textContent = '';
});

document.getElementById('perPageSelect').addEventListener('change', () => {
  if (hasSearched) searchRecords(1);
});

document.getElementById('student_id').addEventListener('input', resetIntakeFilter);

courseSelect.addEventListener('change', () => loadIntakes(courseSelect.value));

async function markComplete(id, btn) {
  if (!confirm('Mark this course as completed and generate a badge?')) return;

  const original = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span> Processing...`;

  try {
    const res = await fetch('{{ route("badges.complete") }}', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ id })
    });
    const data = await res.json();

    if (data.success) {
      const code = data.verification_code || String(data.verification_url || '').split('/').pop();
      document.getElementById(`status-${id}`).innerHTML = `<span class="text-success fw-bold">Completed</span>`;
      document.getElementById(`action-${id}`).innerHTML = actionButtons({
        id,
        status: 'completed',
        eligible_for_badge: true,
        badge: { id: data.badge_id, verification_code: code }
      });
      const tr = document.getElementById(`row-${id}`);
      tr.classList.add('table-success');
      setTimeout(() => tr.classList.remove('table-success'), 1200);
      showSuccessMessage(data.message || 'Badge generated successfully.');
    } else {
      showErrorMessage(data.message || 'Could not generate the badge.');
      btn.disabled = false;
      btn.innerHTML = original;
    }
  } catch (error) {
    showErrorMessage('Could not generate the badge. Please try again.');
    btn.disabled = false;
    btn.innerHTML = original;
  }
}

async function cancelBadge(badgeId, registrationId, btn) {
  if (!confirm('Cancel this certificate?')) return;
  const original = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = `<span class="spinner-border spinner-border-sm"></span>`;

  try {
    const res = await fetch('{{ route("badges.cancel") }}', {
      method: 'DELETE',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/json'
      },
      body: JSON.stringify({ badge_id: badgeId, registration_id: registrationId })
    });
    const data = await res.json();

    if (data.success) {
      document.getElementById(`status-${registrationId}`).innerHTML = `<span class="text-warning fw-bold">Pending</span>`;
      document.getElementById(`action-${registrationId}`).innerHTML = actionButtons({
        id: registrationId,
        status: 'Pending',
        eligible_for_badge: true,
        badge: null
      });
      showSuccessMessage(data.message || 'Certificate cancelled.');
    } else {
      showErrorMessage(data.message || 'Could not cancel the certificate.');
      btn.disabled = false;
      btn.innerHTML = original;
    }
  } catch (error) {
    showErrorMessage('Could not cancel the certificate. Please try again.');
    btn.disabled = false;
    btn.innerHTML = original;
  }
}

async function viewCertificate(code) {
  const body = document.getElementById('viewCertBody');
  const link = document.getElementById('viewCertLink');
  body.innerHTML = `<div class="text-center p-5"><div class="spinner-border text-primary"></div></div>`;
  try {
    const res = await fetch(`{{ url('/badges/details') }}/${encodeURIComponent(code)}`);
    const html = await res.text();
    body.innerHTML = html;
    link.href = `/verify-badge/${encodeURIComponent(code)}`;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('viewCertModal')).show();
  } catch (error) {
    body.innerHTML = `<div class="text-danger text-center p-3 fw-bold">Could not load certificate details.</div>`;
    bootstrap.Modal.getOrCreateInstance(document.getElementById('viewCertModal')).show();
  }
}

document.addEventListener('click', e => {
  const viewBtn = e.target.closest('.btn-view-cert');
  if (viewBtn) {
    viewCertificate(viewBtn.dataset.code);
    return;
  }

  const cancelBtn = e.target.closest('.btn-cancel-badge');
  if (cancelBtn) {
    cancelBadge(cancelBtn.dataset.badgeId || null, cancelBtn.dataset.regId, cancelBtn);
    return;
  }

  const completeBtn = e.target.closest('.btn-mark-complete');
  if (completeBtn) {
    markComplete(completeBtn.dataset.regId, completeBtn);
  }
});
</script>
@endpush
