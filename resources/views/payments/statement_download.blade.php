@extends('inc.app')

@section('title', 'NEBULA | Payment Statement')

@section('content')
<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.css">
<style nonce="{{ $cspNonce }}">
.statement-download-page [class*="col-"] {
    min-width: 0;
}
.statement-download-page .form-select,
.statement-download-page .form-control,
.statement-download-page .nebula-select,
.statement-download-page .btn {
    max-width: 100%;
}
.statement-download-search {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.5rem;
    align-items: stretch;
}
.statement-download-search .form-control {
    flex: 1 1 auto;
    min-width: 0;
}
.statement-download-search .btn {
    flex: 0 0 auto;
    white-space: nowrap;
}
.statement-download-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
}
.statement-download-actions .btn {
    min-width: 10rem;
}
@media (max-width: 767.98px) {
    .statement-download-page .card-body {
        padding: 1rem 0.75rem;
    }
    .statement-download-search {
        flex-wrap: wrap;
    }
    .statement-download-search .form-control,
    .statement-download-search .btn,
    .statement-download-actions,
    .statement-download-actions .btn {
        width: 100%;
    }
}
</style>

<div class="container-fluid px-2 px-md-3 statement-download-page">
    <div class="card shadow border-0">
        <div class="card-body">
            <h2 class="text-center mb-3">
                <i class="ti ti-file-download me-2"></i>Payment Statement
            </h2>
            <p class="text-muted text-center mb-4">
                Enter the student NIC or Student ID, select a course, then download the payment statement.
            </p>
            <hr>

            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <form id="statementDownloadForm" action="{{ route('payment.downloadStatement') }}" method="POST" novalidate>
                @csrf
                <input type="hidden" name="student_nic" id="download-student-nic">
                <input type="hidden" name="course_id" id="download-course-id">

                <div class="row g-3">
                    <div class="col-12 col-lg-6">
                        <label for="statement-nic" class="form-label fw-bold">Student NIC / Student ID <span class="text-danger">*</span></label>
                        <div class="statement-download-search">
                            <input type="text" id="statement-nic" class="form-control" placeholder="Enter NIC or Student ID" required autocomplete="off" value="{{ old('student_nic') }}">
                            <button class="btn btn-primary" type="button" id="statementSearchBtn">
                                <i class="ti ti-search me-1"></i>Search
                            </button>
                        </div>
                    </div>
                    <div class="col-12 col-lg-6">
                        <label for="statement-course" class="form-label fw-bold">Course <span class="text-danger">*</span></label>
                        <select id="statement-course" class="form-select" required disabled>
                            <option value="" disabled selected>Select a Course</option>
                        </select>
                    </div>
                </div>

                <div class="statement-download-actions mt-4">
                    <button type="submit" class="btn btn-danger" id="statementDownloadBtn" disabled>
                        <i class="ti ti-download me-1"></i>Download PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.0/dist/sweetalert2.min.js"></script>
<script nonce="{{ $cspNonce }}">
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

function syncCustomSelect(select) {
    if (!select) return;
    select.dispatchEvent(new Event('change', { bubbles: true }));
}

function resetCourseSelect() {
    const courseSelect = document.getElementById('statement-course');
    const downloadBtn = document.getElementById('statementDownloadBtn');
    courseSelect.innerHTML = '<option value="" disabled selected>Select a Course</option>';
    courseSelect.disabled = true;
    downloadBtn.disabled = true;
    syncCustomSelect(courseSelect);
}

function formatSriLankaDate(value) {
    if (!value) return '';
    if (/^\d{4}-\d{2}-\d{2}$/.test(String(value))) {
        return String(value);
    }
    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return String(value);
    }
    return new Intl.DateTimeFormat('en-CA', {
        timeZone: 'Asia/Colombo',
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
    }).format(date);
}

function populateCourseSelect(courses) {
    const courseSelect = document.getElementById('statement-course');
    const downloadBtn = document.getElementById('statementDownloadBtn');
    courseSelect.innerHTML = '<option value="" disabled selected>Select a Course</option>';

    if (!courses || !courses.length) {
        courseSelect.innerHTML = '<option value="" disabled selected>No courses found for this student</option>';
        courseSelect.disabled = true;
        downloadBtn.disabled = true;
        syncCustomSelect(courseSelect);
        return false;
    }

    courses.forEach(course => {
        const option = document.createElement('option');
        option.value = course.course_id;
        const registeredDate = formatSriLankaDate(course.registration_date);
        const registered = registeredDate ? ` (Registered: ${registeredDate})` : '';
        option.textContent = `${course.course_name}${registered}`;
        courseSelect.appendChild(option);
    });

    courseSelect.disabled = false;
    if (courses.length === 1) {
        courseSelect.value = courses[0].course_id;
        downloadBtn.disabled = false;
    } else {
        downloadBtn.disabled = true;
    }
    syncCustomSelect(courseSelect);
    return courses.length === 1;
}

async function loadStudentCourses(studentNic) {
    const courseSelect = document.getElementById('statement-course');
    courseSelect.innerHTML = '<option value="" disabled selected>Loading courses...</option>';
    courseSelect.disabled = true;
    document.getElementById('statementDownloadBtn').disabled = true;
    syncCustomSelect(courseSelect);

    const response = await fetch(@json(route('payment.get.student.courses')), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({ student_nic: studentNic }),
    });
    const data = await response.json().catch(() => ({}));
    if (!response.ok || !data.success) {
        resetCourseSelect();
        throw new Error(data.message || 'No courses found for this student.');
    }
    return populateCourseSelect(data.courses || []);
}

function filenameFromDisposition(header, fallback) {
    if (!header) return fallback;
    const utfMatch = header.match(/filename\*=UTF-8''([^;]+)/i);
    if (utfMatch) {
        return decodeURIComponent(utfMatch[1]);
    }
    const asciiMatch = header.match(/filename="?([^";]+)"?/i);
    return asciiMatch ? asciiMatch[1] : fallback;
}

function downloadBlob(blob, filename) {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = filename;
    link.rel = 'noopener';
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 1000);
}

document.addEventListener('DOMContentLoaded', function () {
    const nicInput = document.getElementById('statement-nic');
    const courseSelect = document.getElementById('statement-course');
    const searchBtn = document.getElementById('statementSearchBtn');
    const form = document.getElementById('statementDownloadForm');
    const downloadBtn = document.getElementById('statementDownloadBtn');
    let debounceTimer;

    async function searchCourses() {
        const studentNic = nicInput.value.trim();
        if (!studentNic) {
            resetCourseSelect();
            await notify('warning', 'Missing student', 'Enter NIC or Student ID.');
            return;
        }
        searchBtn.disabled = true;
        try {
            const autoSelected = await loadStudentCourses(studentNic);
            if (!autoSelected) {
                await notify('info', 'Select a course', 'Choose a course, then download the PDF.');
            }
        } catch (error) {
            await notify('error', 'Search failed', error.message || 'Unable to load courses.');
        } finally {
            searchBtn.disabled = false;
        }
    }

    searchBtn.addEventListener('click', searchCourses);

    nicInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            searchCourses();
        }
    });

    nicInput.addEventListener('input', function () {
        const studentNic = this.value.trim();
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => {
            if (studentNic) {
                loadStudentCourses(studentNic).catch(() => {});
            } else {
                resetCourseSelect();
            }
        }, 500);
    });

    courseSelect.addEventListener('change', function () {
        downloadBtn.disabled = !this.value;
    });

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        const nic = nicInput.value.trim();
        const courseId = courseSelect.value;

        if (!nic || !courseId) {
            await notify('warning', 'Missing details', 'Please enter NIC or Student ID and select a course.');
            return;
        }

        document.getElementById('download-student-nic').value = nic;
        document.getElementById('download-course-id').value = courseId;

        downloadBtn.disabled = true;
        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                const data = await response.json().catch(() => ({}));
                throw new Error(data.message || (data.errors ? Object.values(data.errors).flat()[0] : 'Unable to download statement.'));
            }

            if (!response.ok) {
                throw new Error('Unable to download statement.');
            }

            const blob = await response.blob();
            if (!blob || blob.size === 0 || (blob.type && blob.type.includes('text/html'))) {
                throw new Error('Unable to download statement.');
            }

            const filename = filenameFromDisposition(
                response.headers.get('content-disposition'),
                `Payment_Statement_${nic}.pdf`
            );
            downloadBlob(blob, filename);
        } catch (error) {
            await notify('error', 'Download failed', error.message || 'Unable to download statement.');
        } finally {
            downloadBtn.disabled = !courseSelect.value;
        }
    });
});
</script>
@endsection
