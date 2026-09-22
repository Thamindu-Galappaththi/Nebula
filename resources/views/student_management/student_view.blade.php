@extends('inc.app')

@section('title', 'All Students View')

@section('content')
<style nonce="{{ $cspNonce }}">
  .student-view-page,
  .student-view-page .card,
  .student-view-page .card-body {
    min-width: 0;
    max-width: 100%;
    overflow: visible;
  }
  .student-view-page [class*="col-"] {
    min-width: 0;
  }
  .student-view-page .form-select {
    max-width: 100%;
    text-overflow: ellipsis;
  }
  .student-view-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 0.75rem;
    flex-wrap: wrap;
  }
  .student-view-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .student-view-columns {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem 1rem;
  }
  .student-view-columns .form-check {
    margin: 0;
    min-height: auto;
  }
  .student-view-table-scroll {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
  }
  .student-view-table-scroll::-webkit-scrollbar {
    height: 10px;
  }
  .student-view-table-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 8px;
  }
  .student-view-table-scroll::-webkit-scrollbar-thumb {
    background: #b0b0b0;
    border-radius: 8px;
  }
  .student-view-table-scroll table {
    min-width: 860px;
    width: 100%;
    margin-bottom: 0;
  }
  .student-view-table-scroll th {
    white-space: nowrap;
  }
  #searchBtn { width: 100%; }
  .student-view-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    padding-bottom: 0.25rem;
  }
  .student-view-page-size {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }
  .student-view-page-size select {
    width: auto;
    min-width: 4.5rem;
  }
  .student-view-pagination {
    max-width: 100%;
    overflow-x: auto;
  }
  .student-view-pagination .pagination {
    flex-wrap: wrap;
    margin-bottom: 0;
  }
  @media (max-width: 767.98px) {
    .student-view-toolbar {
      flex-direction: column;
      align-items: stretch;
    }
    .student-view-actions,
    .student-view-actions .btn {
      width: 100%;
    }
    .student-view-page .card-body {
      padding: 1rem 0.75rem;
    }
    .student-view-columns .form-check-input {
      width: 1.15em;
      height: 1.15em;
    }
    .student-view-footer {
      flex-direction: column;
      align-items: stretch;
    }
    .student-view-page-size {
      width: 100%;
    }
    .student-view-page-size select {
      width: 5.75rem;
      min-width: 5.75rem;
      flex: 0 0 5.75rem;
    }
    .student-view-pagination .pagination {
      justify-content: center;
    }
  }
</style>

<div class="container-fluid px-2 px-md-3 student-view-page">
  <div class="card shadow border-0">
    <div class="card-body">
      <h3 class="text-center mb-4">All Students View</h3>
      <hr>

      <form id="filterForm" class="row g-3 mb-4">
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

        <div class="col-12 col-md-6 col-lg-4" id="specializationFilterWrap" style="display:none;">
          <label class="form-label" for="specializationSelect">Specialization</label>
          <select id="specializationSelect" name="specialization" class="form-select">
            <option value="all">All</option>
          </select>
        </div>

        <div class="col-12 col-md-6 col-lg-4">
          <label class="form-label" for="statusSelect">Status</label>
          <select id="statusSelect" name="status" class="form-select">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="terminated">Terminated</option>
            <option value="suspended">Suspended</option>
            <option value="graduated">Graduated</option>
          </select>
        </div>

        <div class="col-12 col-md-6 col-lg-4 d-flex align-items-end">
          <button id="searchBtn" class="btn btn-primary w-100" type="submit">
            <span class="spinner-border spinner-border-sm d-none" id="searchSpinner" role="status"></span>
            <span id="searchText">Search</span>
          </button>
        </div>
      </form>

      <div class="mb-3">
        <h6 class="text-secondary fw-bold">Select Columns to Display:</h6>
        <div id="columnSelector" class="student-view-columns">
          <div class="form-check">
            <input type="checkbox" class="form-check-input colToggle" id="col-student" value="student" checked>
            <label class="form-check-label" for="col-student">Student</label>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input colToggle" id="col-nic" value="nic" checked>
            <label class="form-check-label" for="col-nic">NIC</label>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input colToggle" id="col-course" value="course" checked>
            <label class="form-check-label" for="col-course">Course</label>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input colToggle" id="col-intake" value="intake" checked>
            <label class="form-check-label" for="col-intake">Intake</label>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input colToggle" id="col-specialization" value="specialization" checked>
            <label class="form-check-label" for="col-specialization">Specialization</label>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input colToggle" id="col-location" value="location" checked>
            <label class="form-check-label" for="col-location">Location</label>
          </div>
          <div class="form-check">
            <input type="checkbox" class="form-check-input colToggle" id="col-status" value="status" checked>
            <label class="form-check-label" for="col-status">Status</label>
          </div>
        </div>
      </div>

      <div class="student-view-toolbar mb-3">
        <div class="text-muted small align-self-center" id="resultCount"></div>
        <div class="student-view-actions">
          <button class="btn btn-outline-secondary btn-sm" id="clearFilters" type="button">
            <i class="ti ti-refresh"></i> Clear Filters
          </button>
          <button class="btn btn-outline-success btn-sm" id="exportExcel" type="button">
            <i class="ti ti-file-spreadsheet"></i> Export Excel
          </button>
          <button class="btn btn-outline-danger btn-sm" id="exportPdf" type="button">
            <i class="ti ti-file-text"></i> Export PDF
          </button>
        </div>
      </div>

      <div id="resultSection" style="display:none;">
        <h5 class="fw-bold text-secondary mb-3">Search Results</h5>
        <div class="student-view-table-scroll">
          <table id="studentTable" class="table table-bordered table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th class="col-student">Student</th>
                <th class="col-nic">NIC</th>
                <th class="col-course">Course</th>
                <th class="col-intake">Intake</th>
                <th class="col-specialization">Specialization</th>
                <th class="col-location">Location</th>
                <th class="col-status">Status</th>
              </tr>
            </thead>
            <tbody id="studentRows"></tbody>
          </table>
        </div>
        <div class="student-view-footer mt-3" id="paginationBar" style="display:none;">
          <div class="student-view-page-size">
            <label class="form-label mb-0 small text-muted" for="perPageSelect">Per page</label>
            <select id="perPageSelect" class="form-select form-select-sm page-size-select">
              <option value="10" selected>10</option>
              <option value="25">25</option>
              <option value="50">50</option>
              <option value="100">100</option>
            </select>
          </div>
          <div class="text-muted small align-self-center" id="resultRange"></div>
          <nav class="student-view-pagination" aria-label="Student results pages">
            <ul class="pagination pagination-sm" id="studentPagination"></ul>
          </nav>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
const allCourses = @json($courses);
let tableData = [];
let currentPage = 1;
let lastPage = 1;
let totalCount = 0;
let hasSearched = false;
let lastSearchCourseId = '';
let lastSearchSpecialization = 'all';
let lastSearchHasNamedSpecs = false;

const specializationWrap = document.getElementById('specializationFilterWrap');
const specializationSelect = document.getElementById('specializationSelect');
const courseSelect = document.getElementById('courseSelect');
const intakeSelect = document.getElementById('intakeSelect');
const columnLabels = {
  student: 'Student',
  nic: 'NIC',
  course: 'Course',
  intake: 'Intake',
  specialization: 'Specialization',
  location: 'Location',
  status: 'Status'
};

function escapeHtml(text) {
  const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
  return String(text ?? '').replace(/[&<>"']/g, m => map[m]);
}

function resetIntakeFilter() {
  intakeSelect.innerHTML = '';
  intakeSelect.add(new Option('All Intakes', ''));
}

function resetSpecializationFilter() {
  specializationSelect.innerHTML = '';
  specializationSelect.add(new Option('All', 'all'));
  specializationWrap.style.display = 'none';
}

function namedSpecializationOptions() {
  return [...specializationSelect.options]
    .map(option => String(option.value || '').trim())
    .filter(value => value !== '' && value !== 'all' && value.toLowerCase() !== 'common');
}

function showsSpecializationColumn() {
  if (!hasSearched) {
    return true;
  }
  const value = String(lastSearchSpecialization || '').trim().toLowerCase();
  if (value === 'common') {
    return false;
  }
  if (lastSearchCourseId && !lastSearchHasNamedSpecs) {
    return false;
  }
  return true;
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

function visibleColumns() {
  return [...document.querySelectorAll('.colToggle:checked')]
    .map(c => c.value)
    .filter(value => value !== 'specialization' || showsSpecializationColumn());
}

function applyColumnVisibility() {
  const showSpec = showsSpecializationColumn();
  const specToggleWrap = document.getElementById('col-specialization')?.closest('.form-check');
  if (specToggleWrap) {
    specToggleWrap.style.display = showSpec ? '' : 'none';
  }

  document.querySelectorAll('.colToggle').forEach(checkbox => {
    const show = checkbox.value === 'specialization'
      ? (showSpec && checkbox.checked)
      : checkbox.checked;
    document.querySelectorAll(`.col-${checkbox.value}`).forEach(cell => {
      cell.style.display = show ? '' : 'none';
    });
  });
}

function currentFilters(page = currentPage) {
  return {
    student_id: document.getElementById('student_id').value.trim(),
    course_id: courseSelect.value,
    intake_id: intakeSelect.value,
    status: document.getElementById('statusSelect').value,
    specialization: specializationWrap.style.display === 'none' ? 'all' : specializationSelect.value,
    columns: visibleColumns(),
    page,
    per_page: Number(document.getElementById('perPageSelect')?.value || 10)
  };
}

function rowValues(s) {
  return {
    student: s.full_name || '-',
    nic: s.id_value || '-',
    course: s.course || s.course_registrations?.[0]?.course?.course_name || '-',
    intake: s.intake || s.course_registrations?.[0]?.intake?.batch || '-',
    specialization: s.specialization || s.course_registrations?.[0]?.specialization || '-',
    location: s.location || s.institute_location || '-',
    status: s.academic_status || '-'
  };
}

function getStatusColor(status) {
  switch (String(status || '').toLowerCase()) {
    case 'active': return 'success';
    case 'terminated': return 'danger';
    case 'suspended': return 'warning';
    case 'graduated': return 'info';
    default: return 'secondary';
  }
}

function setSearchBusy(isBusy) {
  const btn = document.getElementById('searchBtn');
  const spin = document.getElementById('searchSpinner');
  const text = document.getElementById('searchText');
  btn.disabled = isBusy;
  spin.classList.toggle('d-none', !isBusy);
  text.textContent = isBusy ? 'Loading...' : 'Search';
}

function loadSpecializations(courseId) {
  resetSpecializationFilter();
  if (!courseId) return;

  fetch(`/api/course/${encodeURIComponent(courseId)}/specializations`)
    .then(response => response.json())
    .then(data => {
      const named = [];
      if (data.success && Array.isArray(data.specializations)) {
        data.specializations.forEach(spec => {
          const value = typeof spec === 'object' ? (spec.name || spec.value || spec.specialization || '') : spec;
          const label = String(value || '').trim();
          if (label && label.toLowerCase() !== 'common' && !named.includes(label)) {
            named.push(label);
          }
        });
      }

      if (!named.length) {
        resetSpecializationFilter();
        return;
      }

      specializationSelect.innerHTML = '';
      specializationSelect.add(new Option('All', 'all'));
      specializationSelect.add(new Option('Common (No Specialization)', 'Common'));
      named.forEach(label => specializationSelect.add(new Option(label, label)));
      specializationWrap.style.display = '';
    })
    .catch(() => resetSpecializationFilter());
}

function renderPagination(meta) {
  const bar = document.getElementById('paginationBar');
  const ul = document.getElementById('studentPagination');
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
      btn.addEventListener('click', () => searchStudents(targetPage));
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
  const table = document.getElementById('studentRows');
  const countEl = document.getElementById('resultCount');
  table.innerHTML = '';

  const total = Number(meta.total ?? items.length);
  const from = Number(meta.from || (items.length ? 1 : 0));

  if (!items.length) {
    const colCount = visibleColumns().length + 1;
    table.innerHTML = `<tr><td colspan="${colCount}" class="text-center text-muted p-3">No records found.</td></tr>`;
    document.getElementById('resultSection').style.display = 'block';
    countEl.textContent = '0 students';
    renderPagination({ total: 0, current_page: 1, last_page: 1, from: null, to: null });
    return;
  }

  const rows = items.map((s, i) => {
    const values = rowValues(s);
    const statusText = values.status && values.status !== '-'
      ? String(values.status).charAt(0).toUpperCase() + String(values.status).slice(1)
      : '';
    const statusLabel = statusText
      ? `<span class="badge bg-${getStatusColor(values.status)}">${escapeHtml(statusText)}</span>`
      : '-';

    return `<tr>
      <td>${from + i}</td>
      <td class="col-student">${escapeHtml(values.student)}</td>
      <td class="col-nic">${escapeHtml(values.nic)}</td>
      <td class="col-course">${escapeHtml(values.course)}</td>
      <td class="col-intake">${escapeHtml(values.intake)}</td>
      <td class="col-specialization">${escapeHtml(values.specialization)}</td>
      <td class="col-location">${escapeHtml(values.location)}</td>
      <td class="col-status">${statusLabel}</td>
    </tr>`;
  });

  table.innerHTML = rows.join('');
  document.getElementById('resultSection').style.display = 'block';
  countEl.textContent = total + (total === 1 ? ' student' : ' students');
  renderPagination(meta);
  applyColumnVisibility();
}

async function searchStudents(page = 1) {
  setSearchBusy(true);
  currentPage = page;

  try {
    const res = await fetch('{{ route("student_management.filter") }}', {
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
    lastSearchCourseId = courseSelect.value;
    lastSearchSpecialization = specializationWrap.style.display === 'none' ? 'all' : specializationSelect.value;
    lastSearchHasNamedSpecs = namedSpecializationOptions().length > 0;
    tableData = data.data || [];
    renderResults(tableData, data);
  } catch (error) {
    tableData = [];
    totalCount = 0;
    alert('Could not load students. Please try again.');
  } finally {
    setSearchBusy(false);
  }
}

function downloadBlob(url, fallbackName, button) {
  if (!hasSearched || !totalCount) {
    alert('No data to export. Search first.');
    return;
  }

  const originalHtml = button.innerHTML;
  button.disabled = true;
  button.innerHTML = 'Preparing...';

  const formData = new FormData();
  formData.append('_token', '{{ csrf_token() }}');
  const filters = currentFilters();
  Object.keys(filters).forEach(key => {
    if (key === 'page' || key === 'per_page') {
      return;
    }
    if (key === 'columns') {
      filters.columns.forEach(col => formData.append('columns[]', col));
    } else {
      formData.append(key, filters[key] ?? '');
    }
  });

  fetch(url, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/octet-stream'
    },
    body: formData
  })
    .then(async response => {
      if (!response.ok) {
        throw new Error('Download failed');
      }
      const contentType = (response.headers.get('Content-Type') || '').toLowerCase();
      if (contentType.includes('text/html') || contentType.includes('application/json')) {
        throw new Error('Download failed');
      }
      const blob = await response.blob();
      const disposition = response.headers.get('Content-Disposition') || '';
      const match = disposition.match(/filename\*?=(?:UTF-8'')?"?([^\";]+)"?/i);
      const filename = match ? decodeURIComponent(match[1].replace(/['"]/g, '')) : fallbackName;
      const objectUrl = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = objectUrl;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(objectUrl);
    })
    .catch(() => alert('Failed to download the file.'))
    .finally(() => {
      button.disabled = false;
      button.innerHTML = originalHtml;
    });
}

document.getElementById('filterForm').addEventListener('submit', e => {
  e.preventDefault();
  searchStudents(1);
});

document.getElementById('clearFilters').addEventListener('click', () => {
  document.getElementById('filterForm').reset();
  fillCourseSelect(allCourses);
  resetIntakeFilter();
  resetSpecializationFilter();
  tableData = [];
  hasSearched = false;
  lastSearchCourseId = '';
  lastSearchSpecialization = 'all';
  lastSearchHasNamedSpecs = false;
  currentPage = 1;
  lastPage = 1;
  totalCount = 0;
  document.getElementById('studentRows').innerHTML = '';
  document.getElementById('resultSection').style.display = 'none';
  document.getElementById('resultCount').textContent = '';
  document.getElementById('paginationBar').style.display = 'none';
  document.getElementById('studentPagination').innerHTML = '';
  document.getElementById('resultRange').textContent = '';
  applyColumnVisibility();
});

document.getElementById('perPageSelect').addEventListener('change', () => {
  if (hasSearched) {
    searchStudents(1);
  }
});

document.querySelectorAll('.colToggle').forEach(checkbox => {
  checkbox.addEventListener('change', applyColumnVisibility);
});

document.getElementById('exportExcel').addEventListener('click', e => {
  downloadBlob('{{ route("student_management.view.export.excel") }}', 'all_students.xlsx', e.currentTarget);
});

document.getElementById('exportPdf').addEventListener('click', e => {
  downloadBlob('{{ route("student_management.view.export.pdf") }}', 'all_students.pdf', e.currentTarget);
});

document.getElementById('student_id').addEventListener('input', () => {
  resetIntakeFilter();
  if ([...specializationSelect.options].some(option => option.value === 'all')) {
    specializationSelect.value = 'all';
  }
});

document.getElementById('student_id').addEventListener('change', async e => {
  const studentId = e.target.value.trim();
  resetIntakeFilter();
  resetSpecializationFilter();

  if (!studentId) {
    fillCourseSelect(allCourses);
    return;
  }

  try {
    const res = await fetch('{{ route("student_management.courses") }}?student_id=' + encodeURIComponent(studentId));
    const data = await res.json();

    if (data.success && Array.isArray(data.courses) && data.courses.length > 0) {
      fillCourseSelect(data.courses);
      if (data.courses.length === 1 && data.courses[0]?.course_id) {
        courseSelect.value = String(data.courses[0].course_id);
        courseSelect.dispatchEvent(new Event('change'));
      }
    } else {
      fillCourseSelect([], 'No courses found');
    }
  } catch (error) {
    fillCourseSelect([], 'Error loading courses');
  }
});

courseSelect.addEventListener('change', () => {
  const courseId = courseSelect.value;
  resetIntakeFilter();
  loadSpecializations(courseId);

  if (!courseId) return;

  fetch(`{{ route('student_management.intakes') }}?course_id=${encodeURIComponent(courseId)}`)
    .then(response => response.json())
    .then(data => {
      if (!data.success || !Array.isArray(data.intakes)) return;
      data.intakes.forEach(intake => {
        intakeSelect.add(new Option(intake.batch, intake.intake_id));
      });
    })
    .catch(() => resetIntakeFilter());
});

resetSpecializationFilter();
</script>
@endpush
