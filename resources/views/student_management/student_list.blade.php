@extends('inc.app')

@section('title', 'NEBULA | Student List')

@section('content')
<style nonce="{{ $cspNonce }}">
  .student-list-filters [class*="col-"] {
    min-width: 0;
  }
  .student-list-filters .form-select {
    max-width: 100%;
    text-overflow: ellipsis;
  }
  .student-list-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
  }
  .student-list-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  @media (max-width: 767.98px) {
    .student-list-actions,
    .student-list-actions .btn {
      width: 100%;
    }
    #statusTabs {
      width: 100%;
      overflow-x: auto;
      flex-wrap: nowrap;
    }
    #statusTabs .nav-item {
      flex: 0 0 auto;
    }
  }
  .lds-ring { display:inline-block; position:relative; width:80px; height:80px; }
  .lds-ring div { box-sizing:border-box; display:block; position:absolute; width:64px; height:64px; margin:8px;
    border:8px solid #fff; border-radius:50%; animation:lds-ring 1.2s cubic-bezier(0.5,0,0.5,1) infinite;
    border-color:#fff transparent transparent transparent; }
  .lds-ring div:nth-child(1){animation-delay:-.45s}
  .lds-ring div:nth-child(2){animation-delay:-.3s}
  .lds-ring div:nth-child(3){animation-delay:-.15s}
  @keyframes lds-ring { 0%{transform:rotate(0)} 100%{transform:rotate(360deg)} }
  #spinner-overlay { position:fixed; inset:0; background:rgba(0,0,0,.5); display:flex; justify-content:center; align-items:center; z-index:9999; }
</style>
<div class="container-fluid px-2 px-md-3">
  <div class="card">
    <div class="card-body">
      <h2 class="text-center mb-4">Student List</h2>
      <hr>

      <div id="spinner-overlay" style="display:none;">
        <div class="lds-ring"><div></div><div></div><div></div><div></div></div>
      </div>

      <!-- Filters -->
      <div id="student-list-filters" class="student-list-filters mb-4">
        <div class="mb-3 row g-2">
          <label class="col-md-2 col-form-label" for="location">Location <span class="text-danger">*</span></label>
          <div class="col-md-10">
            <select class="form-select" id="location">
              <option value="" selected disabled>Select a Location</option>
              @foreach($locations as $loc)
                <option value="{{ $loc }}">Nebula Institute of Technology - {{ $loc }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="mb-3 row g-2">
          <label class="col-md-2 col-form-label" for="course">Course <span class="text-danger">*</span></label>
          <div class="col-md-10">
            <select class="form-select" id="course" disabled>
              <option value="" selected disabled>Select Course</option>
            </select>
          </div>
        </div>
        <div class="mb-3 row g-2">
          <label class="col-md-2 col-form-label" for="intake">Batch <span class="text-danger">*</span></label>
          <div class="col-md-10">
            <select class="form-select" id="intake" disabled>
              <option value="" selected disabled>Select Batch</option>
            </select>
          </div>
        </div>
        <div class="mb-3 row g-2" id="specializationRow" style="display:none;">
          <label class="col-md-2 col-form-label" for="specialization">Specialization <span class="text-danger">*</span></label>
          <div class="col-md-10">
            <select class="form-select" id="specialization" disabled>
              <option value="" selected disabled>Select Specialization</option>
            </select>
          </div>
        </div>
      </div>

      <hr class="my-4">

      <!-- Tabs + Table -->
      <div class="mt-4" id="studentTableSection" style="display:none;">
        <div class="student-list-toolbar mb-2">
          <ul class="nav nav-pills" id="statusTabs">
  <li class="nav-item">
    <button class="nav-link active" type="button" data-status="all" id="tab-all">
      All <span class="badge bg-secondary ms-1" id="count-all">0</span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" type="button" data-status="pending" id="tab-pending">
      Pending <span class="badge bg-secondary ms-1" id="count-pending">0</span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" type="button" data-status="registered" id="tab-registered">
      Registered <span class="badge bg-secondary ms-1" id="count-registered">0</span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" type="button" data-status="terminated" id="tab-terminated">
      Not Eligible <span class="badge bg-secondary ms-1" id="count-terminated">0</span>
    </button>
  </li>
  <li class="nav-item">
    <button class="nav-link" type="button" data-status="completed" id="tab-completed">
      Completed <span class="badge bg-secondary ms-1" id="count-completed">0</span>
    </button>
  </li>
</ul>

          <div class="student-list-actions">
            <button id="downloadListBtn" class="btn btn-primary" type="button">
              <i class="bi bi-download"></i> Download PDF
            </button>
            <button id="downloadListExcelBtn" class="btn btn-success" type="button">
              <i class="bi bi-file-earmark-spreadsheet"></i> Download Excel
            </button>
          </div>
        </div>

        <h4 class="text-center mb-3" id="studentListHeader"></h4>

        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Course Registration ID</th>
                <th>Student ID</th>
                <th>Student Name</th>
                <th id="specializationColumnHeader">Specialization</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="studentTableBody"></tbody>
          </table>
        </div>

        <div class="d-flex justify-content-end mt-2">
          <span id="studentTotalCount" class="fw-bold"></span>
        </div>
      </div>
    </div>
  </div>
</div>

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', () => {
  const locationSelect = document.getElementById('location');
  const courseSelect   = document.getElementById('course');
  const intakeSelect   = document.getElementById('intake');
  const specializationSelect = document.getElementById('specialization');
  const specializationRow = document.getElementById('specializationRow');
  const section        = document.getElementById('studentTableSection');
  const tbody          = document.getElementById('studentTableBody');
  const downloadBtn    = document.getElementById('downloadListBtn');
  const downloadExcelBtn = document.getElementById('downloadListExcelBtn');
  const headerEl       = document.getElementById('studentListHeader');

  let allStudents = [];
  let currentStatus = 'all';
  let specLoadToken = 0;

  function reset(select, placeholder){
    select.innerHTML = `<option selected disabled value="">${placeholder}</option>`;
    select.disabled = true;
  }

  function resetSpecialization(){
    delete specializationRow.dataset.loading;
    specializationRow.style.display = 'none';
    reset(specializationSelect, 'Select Specialization');
  }

  function loadSpecializations(courseId){
    const token = ++specLoadToken;
    resetSpecialization();
    if(!courseId) return;

    specializationRow.dataset.loading = '1';

    fetch(`/api/course/${encodeURIComponent(courseId)}/specializations`)
      .then(r => r.json())
      .then(data => {
        if(token !== specLoadToken) return;

        const named = [];
        if(data.success && Array.isArray(data.specializations)){
          data.specializations.forEach(spec => {
            const value = typeof spec === 'object' ? (spec.name || spec.value || spec.specialization || '') : spec;
            const label = String(value || '').trim();
            if(label && label.toLowerCase() !== 'common' && !named.includes(label)){
              named.push(label);
            }
          });
        }

        delete specializationRow.dataset.loading;

        if(!named.length){
          resetSpecialization();
          if(intakeSelect.value) fetchStudents();
          return;
        }

        specializationSelect.innerHTML = '';
        const placeholder = new Option('Select Specialization', '', true, true);
        placeholder.disabled = true;
        specializationSelect.add(placeholder);
        specializationSelect.add(new Option('Common', 'Common'));
        named.forEach(label => specializationSelect.add(new Option(label, label)));
        specializationSelect.disabled = false;
        specializationRow.style.display = '';
        section.style.display = 'none';
      })
      .catch(() => {
        if(token !== specLoadToken) return;
        resetSpecialization();
        if(intakeSelect.value) fetchStudents();
      });
  }

  function showSpinner(show){
    document.getElementById('spinner-overlay').style.display = show ? 'flex' : 'none';
  }

  function showToast(title, message, bg){
    const container = document.querySelector('.toast-container') || document.getElementById('toastContainer');
    if (!container) return;
    const el = document.createElement('div');
    el.className = `toast align-items-center text-white ${bg} border-0`;
    el.role = 'alert'; el.ariaLive = 'assertive'; el.ariaAtomic = 'true';
    el.innerHTML = `
      <div class="d-flex">
        <div class="toast-body"><strong>${title}:</strong> ${message}</div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
      </div>`;
    container.appendChild(el);
    new bootstrap.Toast(el).show();
    el.addEventListener('hidden.bs.toast', ()=> el.remove());
  }

  locationSelect.addEventListener('change', () => {
    const loc = locationSelect.value;
    reset(courseSelect,'Select Course');
    reset(intakeSelect,'Select Batch');
    resetSpecialization();
    section.style.display='none';
    if(!loc) return;

    showSpinner(true);
    fetch(`{{ url('/course-registration/get-courses-by-location') }}/${encodeURIComponent(loc)}`)
      .then(r=>r.json())
      .then(data=>{
        if(data.success && data.courses?.length){
          courseSelect.innerHTML = `<option selected disabled value="">Select Course</option>`;
          data.courses.forEach(c=>{
            courseSelect.add(new Option(c.course_name, c.course_id));
          });
          courseSelect.disabled=false;
        }else{
          showToast('Info','No courses found for this location.','bg-info');
        }
      })
      .catch(()=>showToast('Error','Failed to fetch courses.','bg-danger'))
      .finally(()=>showSpinner(false));
  });

  courseSelect.addEventListener('change', ()=>{
    const courseId = courseSelect.value;
    const loc = locationSelect.value;
    reset(intakeSelect,'Select Batch');
    resetSpecialization();
    section.style.display='none';
    if(!courseId || !loc) return;

    showSpinner(true);
    fetch(`/student/list/get-intakes/${encodeURIComponent(courseId)}/${encodeURIComponent(loc)}`)
      .then(r=>r.json())
      .then(data=>{
        if(data.intakes?.length){
          intakeSelect.innerHTML = `<option selected disabled value="">Select Batch</option>`;
          data.intakes.forEach(i=>{
            intakeSelect.add(new Option(i.batch, i.intake_id));
          });
          intakeSelect.disabled=false;
        }else{
          showToast('Info','No intakes for this course/location.','bg-info');
        }
      })
      .catch(()=>showToast('Error','Failed to fetch intakes.','bg-danger'))
      .finally(()=>showSpinner(false));

      loadSpecializations(courseId);
  });

  intakeSelect.addEventListener('change', () => {
    if (specializationRow.dataset.loading === '1') {
      section.style.display = 'none';
      return;
    }
    if (specializationRow.style.display === 'none' || specializationSelect.value) {
      fetchStudents();
    } else {
      section.style.display = 'none';
    }
  });
  specializationSelect.addEventListener('change', fetchStudents);

  function fetchStudents(){
    const location = locationSelect.value;
    const courseId = courseSelect.value;
    const intakeId = intakeSelect.value;
    const specialization = specializationSelect.value;
    if(!location || !courseId || !intakeId){ section.style.display='none'; return; }
    if(specializationRow.style.display !== 'none' && !specialization){ section.style.display='none'; return; }

    showSpinner(true);
    fetch('{{ route('student.getListData') }}', {
      method:'POST',
      headers:{'Content-Type':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
      body: JSON.stringify({location, course_id:courseId, intake_id:intakeId, specialization})
    })
    .then(r=>r.json())
    .then(data=>{
      if(data.success){
        allStudents = data.students ?? [];
        // counts
        document.getElementById('count-all').textContent        = allStudents.length;
        document.getElementById('count-pending').textContent    = allStudents.filter(s=>s.status==='pending').length;
        document.getElementById('count-registered').textContent = allStudents.filter(s=>s.status==='registered').length;
        document.getElementById('count-terminated').textContent = allStudents.filter(s=>s.status==='terminated').length;
        document.getElementById('count-completed').textContent  = allStudents.filter(s=>s.status==='completed').length;


        // header
        const locText = locationSelect.options[locationSelect.selectedIndex].text;
        const crsText = courseSelect.options[courseSelect.selectedIndex].text;
        const inText  = intakeSelect.options[intakeSelect.selectedIndex].text;
        const specText = showsSpecializationColumn() ? ` - ${specializationSelect.value}` : '';
        headerEl.innerHTML = `Student list - ${locText}<br>${crsText} - ${inText}${specText}`;

        renderTable();
        section.style.display='block';
      }else{
        section.style.display='none';
        showToast('Info','No students found for the selected criteria.','bg-info');
      }
    })
    .catch(()=>{ section.style.display='none'; showToast('Error','Error fetching students.','bg-danger'); })
    .finally(()=>showSpinner(false));
  }

  function escapeHtml(value){
    return String(value ?? '').replace(/[&<>"']/g, function(ch){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[ch]);
    });
  }

  function statusLabel(status){
    if (status === 'terminated') return 'Not Eligible';
    if (!status) return '';
    return status.charAt(0).toUpperCase() + status.slice(1);
  }

  function showsSpecializationColumn(){
    const value = String(specializationSelect.value || '').trim();
    return value !== '' && value.toLowerCase() !== 'common';
  }

  function renderTable(){
    const list = (currentStatus==='all')
      ? allStudents
      : allStudents.filter(s=>s.status===currentStatus);

    list.sort((a, b) => (a.course_registration_id || '').localeCompare(b.course_registration_id || '', undefined, { numeric: true, sensitivity: 'base' }));
    tbody.innerHTML = '';

    const showSpec = showsSpecializationColumn();
    const specHeader = document.getElementById('specializationColumnHeader');
    if (specHeader) specHeader.style.display = showSpec ? '' : 'none';

    list.forEach((s, idx) => {
      let trClass = '';
      if (s.status === 'pending') trClass = 'table-warning';
      else if (s.status === 'registered') trClass = 'table-success';
      else if (s.status === 'terminated') trClass = 'table-danger';
      else if (s.status === 'completed') trClass = 'table-info';

      const specializationCell = showSpec
        ? `<td>${escapeHtml(String(s.specialization || s.course_registration_specialization || s.course_registration?.specialization || '').trim())}</td>`
        : '';
      tbody.insertAdjacentHTML('beforeend', `
        <tr class="${trClass}">
          <td>${idx+1}</td>
          <td>${escapeHtml(s.course_registration_id)}</td>
          <td>${escapeHtml(s.student_id)}</td>
          <td>${escapeHtml(s.name)}</td>
          ${specializationCell}
          <td>${escapeHtml(statusLabel(s.status))}</td>
        </tr>
      `);
    });

    document.getElementById('studentTotalCount').textContent =
      `Total Students: ${list.length}`;
  }

  // Tabs
  document.getElementById('statusTabs').addEventListener('click', (e)=>{
    const btn = e.target.closest('button[data-status]');
    if(!btn) return;
    document.querySelectorAll('#statusTabs .nav-link').forEach(b=>b.classList.remove('active'));
    btn.classList.add('active');
    currentStatus = btn.dataset.status;
    renderTable();
  });

  function selectedFilters(){
    const location = locationSelect.value;
    const courseId = courseSelect.value;
    const intakeId = intakeSelect.value;
    const specialization = specializationSelect.value || '';
    if(!location || !courseId || !intakeId){
      showToast('Error','Please select all filters before downloading.','bg-danger');
      return null;
    }
    if(specializationRow.style.display !== 'none' && !specialization){
      showToast('Error','Please select a specialization before downloading.','bg-danger');
      return null;
    }
    return { location, courseId, intakeId, specialization };
  }

  function downloadWithFilters(url, fallbackName){
    const filters = selectedFilters();
    if(!filters) return;

    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('location', filters.location);
    formData.append('course_id', filters.courseId);
    formData.append('intake_id', filters.intakeId);
    formData.append('specialization', filters.specialization);
    formData.append('status', currentStatus);

    showSpinner(true);
    fetch(url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'Accept': 'application/octet-stream'
      },
      body: formData
    })
    .then(async response => {
      if(!response.ok){
        throw new Error('Download failed');
      }
      const blob = await response.blob();
      const disposition = response.headers.get('Content-Disposition') || '';
      const match = disposition.match(/filename="?([^"]+)"?/i);
      const filename = match ? match[1] : fallbackName;
      const objectUrl = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = objectUrl;
      link.download = filename;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(objectUrl);
    })
    .catch(() => showToast('Error','Failed to download the file.','bg-danger'))
    .finally(() => showSpinner(false));
  }

  downloadBtn.addEventListener('click', ()=>{
    downloadWithFilters('{{ route('student.downloadList') }}', 'student_list.pdf');
  });

  downloadExcelBtn.addEventListener('click', ()=>{
    downloadWithFilters('{{ route('student.downloadList.excel') }}', 'student_list.xlsx');
  });
});
</script>
@endpush
@endsection
