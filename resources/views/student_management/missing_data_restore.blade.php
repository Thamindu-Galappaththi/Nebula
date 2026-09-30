@extends('inc.app')

@section('title', 'NEBULA | Restore Missing Data')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<style nonce="{{ $cspNonce }}">
    .restore-page, .restore-page .card, .restore-page .card-body {
        max-width: 100%;
        min-width: 0;
    }
    .restore-table-scroll {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        max-width: 100%;
    }
    .restore-badge-insert { background: #d1e7dd; color: #0f5132; }
    .restore-badge-skip { background: #e2e3e5; color: #41464b; }
    .restore-badge-error { background: #f8d7da; color: #842029; }
    @media (max-width: 991.98px) {
        .restore-page h4 { overflow-wrap: anywhere; }
    }
</style>

<div class="container-fluid px-2 px-md-3 restore-page">
    <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
        <div>
            <h4 class="mb-1">Restore Missing Data</h4>
            <p class="text-muted mb-0">Insert-only upload. Existing students, registrations, and modules are skipped. Nothing is overwritten.</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-12 col-md-4">
                    <label class="form-label" for="restoreType">What to restore</label>
                    <select id="restoreType" class="form-select nebula-select">
                        <option value="students">Students + course registration</option>
                        <option value="modules">Modules</option>
                        <option value="semester_modules">Semester module links</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 student-only">
                    <label class="form-label" for="restoreLocation">Location</label>
                    <select id="restoreLocation" class="form-select nebula-select">
                        <option value="">Select location</option>
                        <option value="Welisara">Welisara</option>
                        <option value="Moratuwa">Moratuwa</option>
                        <option value="Peradeniya">Peradeniya</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 student-only">
                    <label class="form-label" for="restoreCourse">Course</label>
                    <select id="restoreCourse" class="form-select nebula-select">
                        <option value="">Select course</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 student-only semester-only">
                    <label class="form-label" for="restoreIntake">Intake / batch</label>
                    <select id="restoreIntake" class="form-select nebula-select">
                        <option value="">Select intake</option>
                    </select>
                </div>
                <div class="col-12 col-md-4 semester-only d-none" id="semesterWrap">
                    <label class="form-label" for="restoreSemester">Semester</label>
                    <select id="restoreSemester" class="form-select nebula-select">
                        <option value="">Select semester</option>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="restoreFile">Excel / CSV file</label>
                    <input type="file" id="restoreFile" class="form-control" accept=".xlsx,.xls,.csv,.txt">
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button type="button" class="btn btn-primary" id="previewBtn">Preview</button>
                    <button type="button" class="btn btn-success" id="commitBtn" disabled>Insert missing rows only</button>
                    <a class="btn btn-outline-secondary" id="templateLink" href="{{ route('missing.data.restore.template', ['type' => 'students']) }}">Download template</a>
                </div>
            </div>
            <p class="small text-muted mt-3 mb-0">
                Developer role only. Use the prepared files in
                <code>docs/Student details B 7 8 9/restore_uploads</code>
                — do not upload the original staff workbooks.
                For an existing student, NIC/passport is enough: the page adds only the missing registration.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <h5 class="mb-0">Preview</h5>
                <div class="small text-muted" id="restoreCounts">Upload a file to see insert / skip / error counts.</div>
            </div>
            <div class="restore-table-scroll">
                <table class="table table-sm table-bordered mb-0" id="restoreTable">
                    <thead>
                        <tr>
                            <th>Row</th>
                            <th>Action</th>
                            <th>Key</th>
                            <th>Name</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="5" class="text-muted">No preview yet.</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.all.min.js"></script>
<script nonce="{{ $cspNonce }}">
(function () {
    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    const typeEl = document.getElementById('restoreType');
    const locationEl = document.getElementById('restoreLocation');
    const courseEl = document.getElementById('restoreCourse');
    const intakeEl = document.getElementById('restoreIntake');
    const semesterEl = document.getElementById('restoreSemester');
    const semesterWrap = document.getElementById('semesterWrap');
    const fileEl = document.getElementById('restoreFile');
    const previewBtn = document.getElementById('previewBtn');
    const commitBtn = document.getElementById('commitBtn');
    const templateLink = document.getElementById('templateLink');
    const countsEl = document.getElementById('restoreCounts');
    const tbody = document.querySelector('#restoreTable tbody');
    let previewToken = null;

    function currentType() { return typeEl.value; }

    function syncTypeUi() {
        const type = currentType();
        document.querySelectorAll('.student-only').forEach((el) => {
            el.classList.toggle('d-none', type === 'modules');
        });
        semesterWrap.classList.toggle('d-none', type !== 'semester_modules');
        templateLink.href = @json(url('/missing-data/restore/template')) + '?type=' + encodeURIComponent(type);
        previewToken = null;
        commitBtn.disabled = true;
    }

    function optionHtml(value, label) {
        return '<option value="' + value + '">' + label + '</option>';
    }

    locationEl.addEventListener('change', function () {
        courseEl.innerHTML = optionHtml('', 'Select course');
        intakeEl.innerHTML = optionHtml('', 'Select intake');
        semesterEl.innerHTML = optionHtml('', 'Select semester');
        if (!locationEl.value) return;
        fetch(@json(url('/course-registration/get-courses-by-location')) + '/' + encodeURIComponent(locationEl.value), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then((r) => r.json()).then((data) => {
            (data.courses || []).forEach((c) => {
                courseEl.insertAdjacentHTML('beforeend', optionHtml(c.course_id, c.course_name));
            });
        });
    });

    courseEl.addEventListener('change', function () {
        intakeEl.innerHTML = optionHtml('', 'Select intake');
        semesterEl.innerHTML = optionHtml('', 'Select semester');
        if (!courseEl.value || !locationEl.value) return;
        fetch(@json(url('/student/list/get-intakes')) + '/' + courseEl.value + '/' + encodeURIComponent(locationEl.value), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then((r) => r.json()).then((data) => {
            (data.intakes || []).forEach((i) => {
                intakeEl.insertAdjacentHTML('beforeend', optionHtml(i.intake_id, i.batch));
            });
        });
    });

    intakeEl.addEventListener('change', function () {
        semesterEl.innerHTML = optionHtml('', 'Select semester');
        if (!intakeEl.value) return;
        fetch(@json(route('missing.data.restore.semesters')) + '?intake_id=' + encodeURIComponent(intakeEl.value), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        }).then((r) => r.json()).then((data) => {
            (data.semesters || []).forEach((s) => {
                semesterEl.insertAdjacentHTML('beforeend', optionHtml(s.id, s.name + ' (' + (s.start_date || '') + ')'));
            });
        });
    });

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
        });
    }

    function badge(action) {
        if (action === 'skip') return '<span class="badge restore-badge-skip">Skip</span>';
        if (action === 'error') return '<span class="badge restore-badge-error">Error</span>';
        return '<span class="badge restore-badge-insert">Insert</span>';
    }

    function rowKey(row) {
        return row.id_value || row.module_code || '';
    }
    function rowName(row) {
        return row.full_name || row.module_name || row.specialization || '';
    }

    previewBtn.addEventListener('click', function () {
        const type = currentType();
        if (!fileEl.files[0]) {
            Swal.fire('Choose a file', 'Select the filled template first.', 'warning');
            return;
        }
        if (type === 'students' && !intakeEl.value) {
            Swal.fire('Select intake', 'Students are attached to one intake only.', 'warning');
            return;
        }
        if (type === 'semester_modules' && !semesterEl.value) {
            Swal.fire('Select semester', 'Module links need a semester.', 'warning');
            return;
        }

        const form = new FormData();
        form.append('type', type);
        form.append('file', fileEl.files[0]);
        form.append('intake_id', intakeEl.value || '');
        form.append('semester_id', semesterEl.value || '');

        previewBtn.disabled = true;
        fetch(@json(route('missing.data.restore.preview')), {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            body: form
        }).then((r) => r.json().then((data) => ({ ok: r.ok, data })))
          .then(({ ok, data }) => {
            previewBtn.disabled = false;
            if (!ok || !data.success) {
                Swal.fire('Preview failed', (data && data.message) || 'Could not read the file.', 'error');
                return;
            }
            previewToken = data.token;
            commitBtn.disabled = !(data.counts && data.counts.insert > 0);
            countsEl.textContent = 'Insert ' + data.counts.insert + ' · Skip ' + data.counts.skip + ' · Error ' + data.counts.error;
            if (!data.rows.length) {
                tbody.innerHTML = '<tr><td colspan="5" class="text-muted">No data rows found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.rows.map((row) => (
                '<tr><td>' + escapeHtml(row.row) + '</td><td>' + badge(row.action) + '</td><td>' +
                escapeHtml(rowKey(row) || '-') + '</td><td>' + escapeHtml(rowName(row) || '-') + '</td><td>' +
                escapeHtml(row.message || '') + '</td></tr>'
            )).join('');
        }).catch(() => {
            previewBtn.disabled = false;
            Swal.fire('Preview failed', 'Network or server error.', 'error');
        });
    });

    commitBtn.addEventListener('click', function () {
        if (!previewToken) return;
        Swal.fire({
            title: 'Insert missing rows only?',
            text: 'Existing records will not be changed.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Insert'
        }).then((result) => {
            if (!result.isConfirmed) return;
            commitBtn.disabled = true;
            fetch(@json(route('missing.data.restore.commit')), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ token: previewToken })
            }).then((r) => r.json()).then((data) => {
                previewToken = null;
                Swal.fire(data.success ? 'Done' : 'Completed with issues', data.message || '', data.success ? 'success' : 'warning');
            }).catch(() => {
                commitBtn.disabled = false;
                Swal.fire('Insert failed', 'Network or server error.', 'error');
            });
        });
    });

    typeEl.addEventListener('change', syncTypeUi);
    syncTypeUi();
})();
</script>
@endsection
