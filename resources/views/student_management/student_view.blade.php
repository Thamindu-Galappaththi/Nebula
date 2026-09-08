@extends('inc.app')

@section('title', 'All Students View')

@section('content')
<div class="container mt-5 mb-5">
  <div class="card shadow border-0">
    <div class="card-body">
      <h3 class="text-primary mb-4">All Students View</h3>

      <!-- 🔹 Filter Form -->
      <form id="filterForm" class="row g-3 mb-4">
        @csrf
        <div class="col-md-3">
          <label class="form-label">Student ID / NIC</label>
          <input type="text" id="student_id" name="student_id" class="form-control" placeholder="Enter Student ID or NIC">
        </div>

        <div class="col-md-3">
          <label class="form-label">Course</label>
          <select id="courseSelect" name="course_id" class="form-select">
            <option value="">All Courses</option>
            @foreach(\App\Models\Course::orderBy('course_name')->get() as $course)
              <option value="{{ $course->course_id }}">{{ $course->course_name }} ({{ $course->location }})</option>
            @endforeach
          </select>
        </div>

        <div class="col-md-3">
          <label class="form-label">Intake</label>
          <select id="intakeSelect" name="intake_id" class="form-select">
            <option value="">All Intakes</option>
          </select>
        </div>

        <div class="col-md-3" id="specializationFilterWrap" style="display:none;">
          <label class="form-label">Specialization</label>
          <select id="specializationSelect" name="specialization" class="form-select">
            <option value="all">All Specializations</option>
          </select>
        </div>

        <div class="col-md-2">
          <label class="form-label">Status</label>
          <select id="statusSelect" name="status" class="form-select">
            <option value="">All</option>
            <option value="active">Active</option>
            <option value="terminated">Terminated</option>
            <option value="suspended">Suspended</option>
            <option value="graduated">Graduated</option>
          </select>
        </div>

        <div class="col-md-1 d-flex align-items-end">
          <button id="searchBtn" class="btn btn-primary w-auto" type="submit">
            <span class="spinner-border spinner-border-sm d-none" id="searchSpinner" role="status"></span>
            <span id="searchText">Search</span>
          </button>
        </div>
      </form>

      <!-- 🔹 Column Selector -->
      <div class="mb-3">
        <h6 class="text-secondary fw-bold">Select Columns to Display:</h6>
        <div id="columnSelector" class="d-flex flex-wrap gap-3">
          <div><input type="checkbox" class="colToggle" value="student" checked> Student</div>
          <div><input type="checkbox" class="colToggle" value="nic" checked> NIC</div>
          <div><input type="checkbox" class="colToggle" value="course" checked> Course</div>
          <div><input type="checkbox" class="colToggle" value="intake" checked> Intake</div>
          <div><input type="checkbox" class="colToggle" value="specialization" checked> Specialization</div>
          <div><input type="checkbox" class="colToggle" value="location" checked> Location</div>
          <div><input type="checkbox" class="colToggle" value="status" checked> Status</div>
        </div>
      </div>

      <div class="text-end mb-3">
        <button class="btn btn-outline-secondary btn-sm me-2" id="clearFilters">
          <i class="ti ti-refresh"></i> Clear Filters
        </button>
        <button class="btn btn-outline-success btn-sm me-2" id="exportCsv">
          <i class="ti ti-file-spreadsheet"></i> Export CSV
        </button>
        <button class="btn btn-outline-danger btn-sm" id="exportPdf">
          <i class="ti ti-file-text"></i> Export PDF
        </button>
      </div>

      <!-- 📋 Results -->
      <div id="resultSection" style="display:none;">
        <h5 class="fw-bold text-secondary mb-3">Search Results</h5>
        <div class="table-responsive">
          <table id="studentTable" class="table table-bordered align-middle">
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
      </div>
    </div>
  </div>
</div>

<!-- 🔹 JS Section -->
<script nonce="{{ $cspNonce }}" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js" integrity="sha384-JcnsjUPPylna1s1fvi1u12X5qjY5OL56iySh75FdtrwhO/SWXgMjoVqcKyIIWOLk" crossorigin="anonymous"></script>
<script nonce="{{ $cspNonce }}" src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.8.3/jspdf.plugin.autotable.min.js" integrity="sha384-Zj5NAMJ45tB1L13yWiQlFjFjlyyeUBZTWQKktGXeW303njR3jSLmfN16iUgF8I8n" crossorigin="anonymous"></script>

<script nonce="{{ $cspNonce }}">
let tableData = [];

const specializationWrap = document.getElementById('specializationFilterWrap');
const specializationSelect = document.getElementById('specializationSelect');

function resetSpecializationFilter() {
  specializationSelect.innerHTML = '<option value="all">All Specializations</option>';
  specializationWrap.style.display = 'none';
}

function loadSpecializations(courseId) {
  resetSpecializationFilter();
  if (!courseId) {
    return;
  }

  fetch(`/api/course/${encodeURIComponent(courseId)}/specializations`)
    .then(response => response.json())
    .then(data => {
      if (!data.success || !Array.isArray(data.specializations) || !data.specializations.length) {
        return;
      }

      let html = '<option value="all">All Specializations</option>';
      data.specializations.forEach(spec => {
        const value = typeof spec === 'object' ? (spec.name || spec.value || spec.specialization || '') : spec;
        if (value) {
          html += `<option value="${escapeHtml(value)}">${escapeHtml(value)}</option>`;
        }
      });
      specializationSelect.innerHTML = html;
      specializationWrap.style.display = 'block';
    })
    .catch(() => resetSpecializationFilter());
}

/* HTML Escape Helper Function */
function escapeHtml(text) {
  const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
  return String(text).replace(/[&<>"']/g, m => map[m]);
}

/* HTML Escape Helper Function */
function escapeHtml(text) {
  const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
  return String(text).replace(/[&<>"']/g, m => map[m]);
}

/* -------------------------------
   🔹 Filter Form Submit
--------------------------------*/
document.getElementById('filterForm').addEventListener('submit', async e => {
  e.preventDefault();
  const btn = document.getElementById('searchBtn');
  const spin = document.getElementById('searchSpinner');
  const text = document.getElementById('searchText');
  btn.disabled = true; spin.classList.remove('d-none'); text.textContent = 'Loading...';

  const payload = {
    student_id: document.getElementById('student_id').value.trim(),
    course_id: document.getElementById('courseSelect').value,
    intake_id: document.getElementById('intakeSelect').value,
    status: document.getElementById('statusSelect').value,
    specialization: document.getElementById('specializationSelect').value,
  };

  const res = await fetch('{{ route("student_management.filter") }}', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
    body: JSON.stringify(payload)
  });

  const data = await res.json();
  tableData = data.data || [];
  renderResults(tableData);

  btn.disabled = false; spin.classList.add('d-none'); text.textContent = 'Search';
});

/* -------------------------------
   🔹 Clear Filters
--------------------------------*/
document.getElementById('clearFilters').addEventListener('click', () => {
  document.getElementById('filterForm').reset();
  resetSpecializationFilter();
  document.getElementById('studentRows').innerHTML = '';
  document.getElementById('resultSection').style.display = 'none';
});

/* -------------------------------
   🔹 Render Results
--------------------------------*/
function renderResults(items) {
  const table = document.getElementById('studentRows');
  table.innerHTML = '';

  if (!items.length) {
    table.innerHTML = `<tr><td colspan="8" class="text-center text-muted p-3">No records found.</td></tr>`;
    document.getElementById('resultSection').style.display = 'block';
    return;
  }

  items.forEach((s, i) => {
    const course = escapeHtml(s.course_registrations?.[0]?.course?.course_name || '-');
    const intake = escapeHtml(s.course_registrations?.[0]?.intake?.batch || '-');
    const specialization = escapeHtml(s.course_registrations?.[0]?.specialization || s.specialization || '-');
    const location = escapeHtml(s.institute_location || '-');
    const status = s.academic_status
      ? `<span class="badge bg-${getStatusColor(s.academic_status)}">${escapeHtml(s.academic_status)}</span>`
      : '-';

    table.innerHTML += `
      <tr>
        <td>${i + 1}</td>
        <td class="col-student">${escapeHtml(s.full_name)}</td>
        <td class="col-nic">${escapeHtml(s.id_value || '-')}</td>
        <td class="col-course">${course}</td>
        <td class="col-intake">${intake}</td>
        <td class="col-specialization">${specialization}</td>
        <td class="col-location">${location}</td>
        <td class="col-status">${status}</td>
      </tr>`;
  });

  document.getElementById('resultSection').style.display = 'block';
}

/* -------------------------------
   🔹 Toggle Column Visibility
--------------------------------*/
document.querySelectorAll('.colToggle').forEach(checkbox => {
  checkbox.addEventListener('change', e => {
    const val = e.target.value;
    const show = e.target.checked;
    document.querySelectorAll(`.col-${val}`).forEach(td => {
      td.style.display = show ? '' : 'none';
    });
  });
});

/* -------------------------------
   🔹 CSV Export
--------------------------------*/
document.getElementById('exportCsv').addEventListener('click', () => {
  if (!tableData.length) return alert('No data to export');
  const visibleCols = [...document.querySelectorAll('.colToggle:checked')].map(c => c.value);

  let csv = 'No,';
  csv += visibleCols.join(',') + '\n';

  tableData.forEach((s, i) => {
    const c = s.course_registrations?.[0]?.course?.course_name || '-';
    const inb = s.course_registrations?.[0]?.intake?.batch || '-';
    const spec = s.course_registrations?.[0]?.specialization || s.specialization || '-';
    const row = {
      student: s.full_name,
      nic: s.id_value || '-',
      course: c,
      intake: inb,
      specialization: spec,
      location: s.institute_location || '-',
      status: s.academic_status || '-'
    };
    csv += `${i + 1},${visibleCols.map(col => row[col] || '-').join(',')}\n`;
  });

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = 'students.csv';
  link.click();
});

/* -------------------------------
   🔹 PDF Export
--------------------------------*/
document.getElementById('exportPdf').addEventListener('click', () => {
  if (!tableData.length) return alert('No data to export');
  const { jsPDF } = window.jspdf;
  const doc = new jsPDF('l', 'pt', 'a4');
  const visibleCols = [...document.querySelectorAll('.colToggle:checked')].map(c => c.value);

  const headers = ['No', ...visibleCols.map(v => v.toUpperCase())];
  const body = tableData.map((s, i) => {
    const c = s.course_registrations?.[0]?.course?.course_name || '-';
    const inb = s.course_registrations?.[0]?.intake?.batch || '-';
    const spec = s.course_registrations?.[0]?.specialization || s.specialization || '-';
    const row = {
      student: s.full_name,
      nic: s.id_value || '-',
      course: c,
      intake: inb,
      specialization: spec,
      location: s.institute_location || '-',
      status: s.academic_status || '-'
    };
    return [i + 1, ...visibleCols.map(col => row[col] || '-')];
  });

  doc.text('All Students Report', 40, 40);
  doc.autoTable({
    head: [headers],
    body,
    startY: 60,
    styles: { fontSize: 9, cellPadding: 4, valign: 'middle' },
    headStyles: { fillColor: [0, 123, 255], textColor: 255 }
  });
  doc.save('students.pdf');
});

document.getElementById('student_id').addEventListener('change', async e => {
  const studentId = e.target.value.trim();

  if (!studentId) {
    // Reset to all courses
    document.getElementById('courseSelect').innerHTML = '<option value="">All Courses</option>';
    @foreach(\App\Models\Course::orderBy('course_name')->get() as $course)
      document.getElementById('courseSelect').innerHTML += '<option value="{{ $course->course_id }}">{{ $course->course_name }} ({{ $course->location }})</option>';
    @endforeach
    return;
  }

  try {
    const res = await fetch('{{ route("student_management.courses") }}?student_id=' + encodeURIComponent(studentId));
    const data = await res.json();

    if (data.success && data.courses.length > 0) {
      let html = '<option value="">All Courses</option>';
      data.courses.forEach(course => {
        const courseName = escapeHtml(String(course.course_name || ''));
        const courseLocation = escapeHtml(String(course.location || '-'));
        html += `<option value="${escapeHtml(String(course.course_id))}">${courseName} (${courseLocation})</option>`;
      });
      document.getElementById('courseSelect').innerHTML = html;
    } else {
      document.getElementById('courseSelect').innerHTML = '<option value="">No courses found</option>';
    }
  } catch (error) {
    console.error('Error fetching courses:', error);
    document.getElementById('courseSelect').innerHTML = '<option value="">Error loading courses</option>';
  }
});

document.getElementById('courseSelect').addEventListener('change', () => {
  const courseId = document.getElementById('courseSelect').value;
  const intakeSelect = document.getElementById('intakeSelect');
  intakeSelect.innerHTML = '<option value="">All Intakes</option>';
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
    .catch(() => {
      intakeSelect.innerHTML = '<option value="">All Intakes</option>';
    });
});

resetSpecializationFilter();

/* -------------------------------
   🔹 Status Color Helper
--------------------------------*/
function getStatusColor(status) {
  switch(status) {
    case 'active': return 'success';
    case 'terminated': return 'danger';
    case 'suspended': return 'warning';
    case 'graduated': return 'info';
    default: return 'secondary';
  }
}
</script>
@endsection
