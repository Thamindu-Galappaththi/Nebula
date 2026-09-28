@extends('inc.app')

@section('title', 'NEBULA | Timetable Management')

@section('content')
<style nonce="{{ $cspNonce }}">
    .timetable-page,
    .timetable-page .card,
    .timetable-page .card-body {
        min-width: 0;
        max-width: 100%;
        overflow: visible;
        height: auto;
    }
    body:has(.timetable-page) .body-wrapper > .container-fluid {
        overflow: visible;
    }
    .timetable-page [class*="col-"] {
        min-width: 0;
    }
    .timetable-page .form-select,
    .timetable-page .form-control {
        width: 100%;
        max-width: 100%;
    }
    .timetable-page .tab-content > .tab-pane:not(.active) {
        display: none !important;
        height: 0;
        overflow: hidden;
    }
    .timetable-tabs {
        flex-wrap: wrap;
        overflow: hidden;
        row-gap: 0.25rem;
    }
    .timetable-tabs .nav-link {
        white-space: nowrap;
    }
    .timetable-filter-actions,
    .timetable-download-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: stretch;
    }
    .timetable-download-actions {
        display: none;
    }
    .timetable-download-actions.is-visible {
        display: flex;
    }
    .timetable-filter-actions .btn,
    .timetable-download-actions .btn {
        flex: 1 1 140px;
        min-width: 0;
    }
    .timetable-calendar-wrap {
        min-width: 0;
        max-width: 100%;
        overflow-x: auto;
    }
    .timetable-page .fc {
        min-width: 0;
        max-width: 100%;
    }
    .timetable-page .fc-event {
        width: auto !important;
    }
    .timetable-toast {
        max-width: min(360px, calc(100vw - 1.5rem));
    }
    .timetable-page .modal-body .d-flex.time-row {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    @media (max-width: 767.98px) {
        .timetable-page h2 {
            font-size: 1.25rem;
        }
        .timetable-page .card-body {
            padding: 1rem 0.75rem;
        }
        .timetable-page .form-control,
        .timetable-page .form-select {
            font-size: 16px;
        }
        .timetable-page .col-form-label {
            text-align: left !important;
            padding-bottom: 0.2rem;
        }
        .timetable-tabs .nav-link {
            width: 100%;
            text-align: left;
        }
        .timetable-filter-actions .btn,
        .timetable-download-actions .btn,
        .timetable-page .modal-footer .btn {
            width: 100%;
            flex: 1 1 100%;
        }
        .timetable-page .fc-header-toolbar {
            display: flex;
            flex-direction: column;
            align-items: stretch;
            gap: 0.5rem;
        }
        .timetable-page .fc-toolbar-chunk {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 0.25rem;
        }
        .timetable-page .fc-toolbar-title {
            font-size: 1.05rem;
            text-align: center;
        }
        .timetable-page .modal-dialog {
            margin: 0.5rem;
        }
    }
    .timetable-modal .modal-header {
        align-items: center;
        gap: 0.75rem;
        padding: 0.9rem 1rem;
    }
    .timetable-modal .modal-title {
        flex: 1 1 auto;
        min-width: 0;
        margin: 0;
        line-height: 1.3;
        overflow-wrap: anywhere;
    }
    .timetable-modal .btn-close {
        flex: 0 0 auto;
        width: 2.5rem;
        height: 2.5rem;
        padding: 0.7rem;
        margin: 0;
        background-size: 0.85em;
        opacity: 0.7;
    }
    @media (max-width: 767.98px) {
        .timetable-modal .modal-title {
            font-size: 1.05rem;
        }
        .timetable-modal .btn-close {
            width: 2.75rem;
            height: 2.75rem;
            padding: 0.8rem;
        }
    }
</style>

    <div class="container-fluid px-2 px-md-3 timetable-page">
        <div class="card">
            <div class="card-body">
                <h2 class="text-center mb-4">Timetable Management</h2>
                <hr>

                <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3 timetable-toast" aria-live="polite" aria-atomic="true" style="z-index: 1090;"></div>

                <!-- Tab Navigation -->
                <ul class="nav nav-tabs mb-4 timetable-tabs" id="timetableTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="degree-tab" data-bs-toggle="tab"
                            data-bs-target="#degree-timetable" type="button" role="tab" aria-controls="degree-timetable"
                            aria-selected="true">
                            Degree Programs
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="certificate-tab" data-bs-toggle="tab"
                            data-bs-target="#certificate-timetable" type="button" role="tab"
                            aria-controls="certificate-timetable" aria-selected="false">
                            Certificate Programs
                        </button>
                    </li>
                </ul>

                <!-- Degree Programs Tab -->
                <div class="tab-content" id="timetableTabsContent">
                    <div class="tab-pane fade show active" id="degree-timetable" role="tabpanel"
                        aria-labelledby="degree-tab">
                        <!-- Degree Filters -->
                        <div id="degree-filters" class="mb-4">
                            <div class="mb-3 row align-items-center">
                                <label for="degree_location" class="col-md-3 col-form-label fw-bold">Location<span
                                        class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <select class="form-select" id="degree_location" name="location" required>
                                        <option value="" selected disabled>Select Location</option>
                                        <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                                        <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                                        <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <label for="degree_course" class="col-md-3 col-form-label fw-bold">Course<span
                                        class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <select class="form-select" id="degree_course" name="course_id" required>
                                        <option selected disabled value="">Select Course</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <label for="degree_intake" class="col-md-3 col-form-label fw-bold">Intake<span
                                        class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <select class="form-select" id="degree_intake" name="intake_id" required>
                                        <option selected disabled value="">Select Intake</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center" id="degree_specialization_row" style="display:none;">
                                <label for="degree_specialization" class="col-md-3 col-form-label fw-bold">Specialization<span class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <select class="form-select" id="degree_specialization" name="specialization" disabled>
                                        <option selected disabled value="">Select Specialization</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <label for="degree_semester" class="col-md-3 col-form-label fw-bold">Semester<span
                                        class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <select class="form-select" id="degree_semester" name="semester" required>
                                        <option selected disabled value="">Select Semester</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <label for="degree_start_date" class="col-md-3 col-form-label fw-bold">Semester Start
                                    Date<span class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <input type="date" class="form-control" id="degree_start_date" name="start_date"
                                        required readonly>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <label for="degree_end_date" class="col-md-3 col-form-label fw-bold">End Date<span
                                        class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <input type="date" class="form-control" id="degree_end_date" name="end_date" required
                                        readonly>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <div class="col-md-9 offset-md-3">
                                    <p class="text-muted small mb-2">For Degree/Diploma, select Location, Course, Intake and Semester. Dates will be filled automatically.</p>
                                    <div class="timetable-filter-actions">
                                        <button type="button" class="btn btn-primary" id="showTimetableBtn">Show Timetable</button>
                                        <div id="degree_download_buttons" class="timetable-download-actions">
                                            <button type="button" class="btn btn-outline-secondary" id="downloadPdfBtn">Download PDF</button>
                                            <button type="button" class="btn btn-success" id="downloadWeekPdfBtn">Download Week PDF</button>
                                            <button type="button" class="btn btn-dark" id="downloadMonthPdfBtn">Download Month PDF</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- FullCalendar Container -->
                        <div class="mt-4 timetable-calendar-wrap" id="degreeTimetableSection" style="display:none;">
                            <div id="calendar"></div>
                        </div>
                    </div>
                    <!-- Certificate Programs Tab Pane (mirrors degree UI) -->
                    <div class="tab-pane fade" id="certificate-timetable" role="tabpanel" aria-labelledby="certificate-tab">
                        <div id="certificate-filters" class="mb-4">
                            <div class="mb-3 row align-items-center">
                                <label for="certificate_location" class="col-md-3 col-form-label fw-bold">Location<span class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <select class="form-select" id="certificate_location" name="location">
                                        <option value="" selected disabled>Select Location</option>
                                        <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                                        <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                                        <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <label for="certificate_course" class="col-md-3 col-form-label fw-bold">Course<span class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <select class="form-select" id="certificate_course" name="course_id">
                                        <option selected disabled value="">Select Course</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <label for="certificate_intake" class="col-md-3 col-form-label fw-bold">Intake<span class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <select class="form-select" id="certificate_intake" name="intake_id">
                                        <option selected disabled value="">Select Intake</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <label for="certificate_start_date" class="col-md-3 col-form-label fw-bold">Course Start Date<span class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <input type="date" class="form-control" id="certificate_start_date" name="start_date" readonly>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <label for="certificate_end_date" class="col-md-3 col-form-label fw-bold">Course End Date<span class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <input type="date" class="form-control" id="certificate_end_date" name="end_date" readonly>
                                </div>
                            </div>

                            <div class="mb-3 row align-items-center">
                                <div class="col-md-9 offset-md-3">
                                    <p class="text-muted small mb-2">For Certificate, select Location, Course and Intake. The course dates will be filled automatically.</p>
                                    <div class="timetable-filter-actions">
                                        <button type="button" class="btn btn-primary" id="certificate_showTimetableBtn">Show Timetable</button>
                                        <div id="certificate_download_buttons" class="timetable-download-actions">
                                            <button type="button" class="btn btn-outline-secondary" id="certificate_downloadPdfBtn">Download PDF</button>
                                            <button type="button" class="btn btn-success" id="certificate_downloadWeekPdfBtn">Download Week PDF</button>
                                            <button type="button" class="btn btn-dark" id="certificate_downloadMonthPdfBtn">Download Month PDF</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4 timetable-calendar-wrap" id="certificateTimetableSection" style="display:none;">
                            <div id="certificate_calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for subject selection -->
    <div id="subjectSelectionModal" class="modal fade timetable-modal" tabindex="-1" role="dialog" aria-labelledby="subjectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="subjectModalLabel">Select Subjects and Duration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Hidden input to store the selected date -->
                    <input type="hidden" id="selectedDate">
                    <input type="hidden" id="selectedTimetableMode" value="degree">

                    <!-- Display selected date -->
                    <div class="mb-3 row">
                        <label for="selected_date_display" class="col-md-3 col-form-label fw-bold">Date</label>
                        <div class="col-md-9">
                            <input type="date" class="form-control" id="selected_date_display">
                        </div>
                    </div>

                    <!-- Multi-subject and Duration Form -->
                    <div id="subjectList">
                        <!-- Each block groups subject + duration + time -->
                        <div class="subject-block">
                            <div class="mb-3 row align-items-center">
                                <label for="degree_subject_0" class="col-md-3 col-form-label fw-bold">Subject <span
                                        class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <select class="form-select subject-select" id="degree_subject_0" name="subject_ids[]"
                                        required>
                                        <option selected disabled value="">Select Subject</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3 row align-items-center">
                                <label for="degree_duration_0" class="col-md-3 col-form-label fw-bold">Duration (Hours) <span
                                        class="text-danger">*</span></label>
                                <div class="col-md-9">
                                    <input type="number" step="0.01" min="0" class="form-control duration-input" id="degree_duration_0"
                                        name="durations[]" placeholder="Hours (e.g. 1.5)" required>
                                </div>
                            </div>
                            <div class="mb-3 row align-items-center">
                                <label for="degree_time_0" class="col-md-3 col-form-label fw-bold">Time <span
                                        class="text-danger">*</span></label>
                                <div class="col-md-9 time-row d-flex">
                                    <input type="time" class="form-control time-input" id="degree_time_0" name="times[]"
                                        required>
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-subject-btn" style="display:none;">Remove</button>
                                </div>
                            </div>
                            <div class="mb-3 row align-items-center">
                                <label for="degree_classroom_0" class="col-md-3 col-form-label fw-bold">Classroom</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control classroom-input" id="degree_classroom_0" name="classrooms[]" placeholder="Optional">
                                </div>
                            </div>
                            <div class="mb-3 row align-items-center">
                                <label for="degree_lecturer_0" class="col-md-3 col-form-label fw-bold">Lecturer</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control lecturer-input" id="degree_lecturer_0" name="lecturers[]" placeholder="Optional">
                                </div>
                            </div>
                        </div>
                    </div>
                    {{-- <button type="button" class="btn btn-secondary" id="addSubjectBtn">Add Another Subject</button> --}}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="assignSubjectBtn">Assign Subjects</button>
                </div>
            </div>
        </div>
    </div>


    <!-- PDF Filter Modal -->
    <div id="downloadPdfModal" class="modal fade timetable-modal" tabindex="-1" role="dialog" aria-labelledby="downloadPdfLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Download Timetable PDF (apply filters)</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
          <div class="modal-body">
            <div class="mb-3">
              <label class="form-label">Location</label>
              <select class="form-select" id="pdf_location">
                <option value="">All</option>
                <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">Course</label>
              <select class="form-select" id="pdf_course">
                <option value="">All</option>
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label">From</label>
              <input type="date" class="form-control" id="pdf_from">
            </div>
            <div class="mb-3">
              <label class="form-label">To</label>
              <input type="date" class="form-control" id="pdf_to">
            </div>
          </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                        <button id="generatePdfBtn" type="button" class="btn btn-primary">Generate PDF</button>
                    </div>
        </div>
      </div>
    </div>

        <!-- Event Details Modal -->
        <div id="eventDetailsModal" class="modal fade timetable-modal" tabindex="-1" role="dialog" aria-labelledby="eventDetailsLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Event Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-2"><strong>Subject:</strong> <span id="ev_subject"></span></div>
                        <div class="mb-2"><strong>Date:</strong> <span id="ev_date"></span></div>
                        <div class="mb-2"><strong>Time:</strong> <span id="ev_time"></span></div>
                        <div class="mb-2"><strong>Classroom:</strong> <span id="ev_classroom">-</span></div>
                        <div class="mb-2"><strong>Lecturer:</strong> <span id="ev_lecturer">-</span></div>
                        <input type="hidden" id="ev_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-danger" id="ev_delete_btn">Delete</button>
                    </div>
                </div>
            </div>
        </div>

    <!-- FullCalendar v5 (modern build) -->
    <link nonce="{{ $cspNonce }}" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css" rel="stylesheet" integrity="sha384-39yVKLsD9lMelmY+ij49KZgE+Mfk6hjdUPNE8yKHqdMPceLXzhlCJAK81xlD5jDj" crossorigin="anonymous" />

    <!-- Moment.js (used in other parts of this page) -->
    <script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/moment@2.29.1/moment.min.js" crossorigin="anonymous"></script>

    <!-- FullCalendar v5 bundle -->
    <script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js" integrity="sha384-5/vsv56401Wf+RP3yE5/aIKW4wutk4nLY3HjueTXN0rA+DmweMtrYaN6RSjdv31b" crossorigin="anonymous"></script>

    <!-- jsPDF for PDF export -->
    <script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js" integrity="sha384-JcnsjUPPylna1s1fvi1u12X5qjY5OL56iySh75FdtrwhO/SWXgMjoVqcKyIIWOLk" crossorigin="anonymous"></script>

    <!-- html2canvas (needed to snapshot table) -->
    <script nonce="{{ $cspNonce }}" src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js" integrity="sha384-ZZ1pncU3bQe8y31yfZdMFdSpttDoPmOZg2wguVK9almUodir1PghgT0eY7Mrty8H" crossorigin="anonymous"></script>

@push('scripts')
    <script nonce="{{ $cspNonce }}">
        $(document).ready(function () {
            var latestEventsRaw = []; // raw server rows
            var latestFcEvents = [];  // mapped fullcalendar events
            var timetableEventsUrl = @json(route('timetable.events'));
            var timetableModulesUrl = @json(route('timetable.modules.by.semester'));

            function showTimetableToast(title, message, type) {
                var container = document.getElementById('toastContainer');
                if (!container) return;
                container.innerHTML = '';
                var normalized = (type || '').includes('success') ? 'success' : ((type || '').includes('warning') ? 'warning' : 'error');
                var toast = document.createElement('div');
                toast.className = 'toast show';
                toast.setAttribute('role', 'alert');
                var header = document.createElement('div');
                header.className = 'toast-header bg-' + (normalized === 'success' ? 'success' : (normalized === 'warning' ? 'warning' : 'danger')) + (normalized === 'warning' ? ' text-dark' : ' text-white');
                var strong = document.createElement('strong');
                strong.className = 'me-auto';
                strong.textContent = title || (normalized === 'success' ? 'Success' : (normalized === 'warning' ? 'Warning' : 'Error'));
                var closeBtn = document.createElement('button');
                closeBtn.type = 'button';
                closeBtn.className = 'btn-close' + (normalized === 'warning' ? '' : ' btn-close-white');
                closeBtn.setAttribute('data-bs-dismiss', 'toast');
                header.appendChild(strong);
                header.appendChild(closeBtn);
                var body = document.createElement('div');
                body.className = 'toast-body';
                body.textContent = message || '';
                toast.appendChild(header);
                toast.appendChild(body);
                container.appendChild(toast);
                if (window.bootstrap && bootstrap.Toast) {
                    bootstrap.Toast.getOrCreateInstance(toast, { delay: 4000 }).show();
                }
                toast.addEventListener('hidden.bs.toast', function () { toast.remove(); });
            }

            function alert(message) {
                showTimetableToast('Notice', String(message || ''), 'warning');
            }

            function escapeHtml(value) {
                return String(value == null ? '' : value)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;')
                    .replace(/'/g, '&#039;');
            }

            function resetSelect($el, placeholder) {
                var select = $el.get(0);
                if (!select) return;
                select.innerHTML = '';
                var opt = new Option(placeholder || 'Select', '', true, true);
                opt.disabled = true;
                select.appendChild(opt);
            }

            function addSelectOption($el, value, label, dataset) {
                var select = $el.get(0);
                if (!select) return;
                var opt = new Option(label, value);
                if (dataset) {
                    Object.keys(dataset).forEach(function (key) {
                        if (dataset[key] !== undefined && dataset[key] !== null) {
                            opt.setAttribute('data-' + key, dataset[key]);
                        }
                    });
                }
                select.appendChild(opt);
            }

            function setDownloadButtonsVisible(selector, visible) {
                $(selector).toggleClass('is-visible', !!visible);
            }

            function refreshCalendarsSize() {
                try { if (window.__nebulaCalendar) window.__nebulaCalendar.updateSize(); } catch (e) {}
                try { if (window.__nebulaCertificateCalendar) window.__nebulaCertificateCalendar.updateSize(); } catch (e) {}
            }

            // build available periods (weeks/months) based on semester start/end
            function buildPeriodsFromSemester() {
                var s = $('#degree_start_date').val();
                var e = $('#degree_end_date').val();
                $('#tablePeriod').empty().append('<option value="">Select period</option>');
                if (!s || !e || !moment(s).isValid() || !moment(e).isValid()) return;
                var start = moment(s).startOf('day');
                var end = moment(e).endOf('day');
                var days = end.diff(start, 'days') + 1;
                // weeks
                var weeks = Math.ceil(days / 7);
                for (var i = 1; i <= weeks; i++) {
                    $('#tablePeriod').append('<option data-type="week" value="' + i + '">Week ' + i + '</option>');
                }
                // months
                var months = end.diff(start, 'months') + 1;
                for (var m = 1; m <= months; m++) {
                    $('#tablePeriod').append('<option data-type="month" value="' + m + '">Month ' + m + '</option>');
                }
                // auto-select first available period and mark UI
                var first = $('#tablePeriod option').not('[value=""]').first().val();
                if (first) {
                    $('#tablePeriod').val(first);
                    $('#tableViewType').val('week'); // default to week view for clarity
                }
            }

            // recalc periods when semester changes
            $(document).on('change', '#degree_semester, #degree_start_date, #degree_end_date', function () {
                buildPeriodsFromSemester();
            });

            // render week-grid view (Week N of semester)
            function renderWeekTable(weekIndex) {
                var s = $('#degree_start_date').val();
                if (!s) { alert('Set semester start date'); return; }
                var start = moment(s).startOf('day').add((weekIndex - 1) * 7, 'days');
                var days = [];
                for (var d = 0; d < 7; d++) days.push(start.clone().add(d, 'days'));
                var html = '<div id="tableViewWrapper" style="overflow:auto;"><table class="table table-bordered" style="min-width:100%;">';
                html += '<thead><tr><th style="width:100px">Time</th>';
                days.forEach(function (dt) { html += '<th style="text-align:center;">' + dt.format('dddd') + '</th>'; });
                html += '</tr></thead><tbody>';
                var startHour = 7, endHour = 18;
                for (var h = startHour; h <= endHour; h++) {
                    html += '<tr>';
                    html += '<td style="border:1px solid #000;padding:6px;font-weight:600;">' + moment({ hour: h }).format('HH:mm') + '</td>';
                    for (var c = 0; c < 7; c++) {
                        var day = days[c];
                        var cellEvents = latestFcEvents.filter(function (ev) {
                            try {
                                var evStart = moment(ev.start);
                                var evEnd = ev.end ? moment(ev.end) : evStart.clone().add(1, 'minutes');
                                var rowStart = day.clone().hour(h).minute(0).second(0);
                                var rowEnd = rowStart.clone().add(1, 'hour');
                                return evStart.isBefore(rowEnd) && evEnd.isAfter(rowStart);
                            } catch (e) { return false; }
                        });
                        html += '<td class="timetable-cell" data-date="' + day.format('YYYY-MM-DD') + '" data-time="' + moment({ hour: h }).format('HH:mm') + '" style="border:1px solid #000;padding:6px;vertical-align:top;min-height:40px;cursor:pointer;">';
                        if (cellEvents.length) {
                            cellEvents.forEach(function (ce) {
                                var st = moment(ce.start).format('HH:mm'), en = ce.end ? moment(ce.end).format('HH:mm') : '';
                        html += '<div class="badge bg-primary text-white mb-1" style="display:block;">' + escapeHtml(ce.title) + '</div>';
                                html += '<div style="font-size:0.85em;color:#333;">' + st + (en ? ' - ' + en : '') + '</div>';
                            });
                        } else {
                            html += '&nbsp;';
                        }
                        html += '</td>';
                    }
                    html += '</tr>';
                }
                html += '</tbody></table></div>';
                $('#degreeTimetableSection').hide();
                // show a new container for table view (create if not exists)
                if (!$('#tableViewSection').length) {
                    $('<div id="tableViewSection" class="mt-4 card p-3"><h5 id="tableViewTitle"></h5><div id="tableViewContainer"></div></div>').insertAfter('#degreeTimetableSection');
                }
                $('#tableViewTitle').text('Week ' + weekIndex);
                $('#tableViewContainer').html(html);
                $('#tableViewSection').show();
            }

            // render month-grid view (Month N of semester)
            function renderMonthTable(monthIndex) {
                var s = $('#degree_start_date').val();
                if (!s) { alert('Set semester start date'); return; }
                var monthStart = moment(s).startOf('day').add((monthIndex - 1), 'months');
                var monthEnd = monthStart.clone().endOf('month');
                // build matrix weeks for this month
                var gridStart = monthStart.clone().startOf('week'); // sunday-start
                var gridEnd = monthEnd.clone().endOf('week');
                var curr = gridStart.clone();
                var html = '<div id="tableViewWrapper" style="overflow:auto;"><table class="table table-bordered" style="min-width:100%;">';
                // header Mon-Sun
                html += '<thead><tr><th style="width:100px">Week</th>';
                var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                days.forEach(function (d) { html += '<th style="text-align:center;">' + d + '</th>'; });
                html += '</tr></thead><tbody>';
                var weekNo = 1;
                while (curr.isSameOrBefore(gridEnd)) {
                    html += '<tr><td style="font-weight:600;">Week ' + weekNo + '</td>';
                    for (var i = 0; i < 7; i++) {
                        var cellDate = curr.clone();
                        var cellEvents = latestFcEvents.filter(function (ev) {
                            return moment(ev.start).isSame(cellDate, 'day');
                        });
                        html += '<td class="timetable-cell" data-date="' + cellDate.format('YYYY-MM-DD') + '" data-time="07:00" style="vertical-align:top;min-width:140px;padding:6px;cursor:pointer;">';
                        if (cellEvents.length) {
                            cellEvents.forEach(function (ce) {
                                var st = moment(ce.start).format('HH:mm'), en = ce.end ? moment(ce.end).format('HH:mm') : '';
                        html += '<div class="badge bg-primary text-white mb-1" style="display:block;">' + escapeHtml(ce.title) + '</div>';
                                html += '<div style="font-size:0.85em;color:#333;">' + st + (en ? ' - ' + en : '') + '</div>';
                            });
                        } else {
                            html += '&nbsp;';
                        }
                        html += '</td>';
                        curr.add(1, 'day');
                    }
                    html += '</tr>';
                    weekNo++;
                }
                html += '</tbody></table></div>';
                $('#degreeTimetableSection').hide();
                if (!$('#tableViewSection').length) {
                    $('<div id="tableViewSection" class="mt-4 card p-3"><h5 id="tableViewTitle"></h5><div id="tableViewContainer"></div></div>').insertAfter('#degreeTimetableSection');
                }
                $('#tableViewTitle').text('Month ' + monthIndex);
                $('#tableViewContainer').html(html);
                $('#tableViewSection').show();
            }

            // when user clicks render table
            $('#renderTableBtn').on('click', function () {
                var sel = $('#tablePeriod').val();
                if (!sel) { alert('Choose a period'); return; }
                var dtype = $('#tablePeriod option:selected').data('type') || $('#tableViewType').val();
                if (dtype === 'week') renderWeekTable(parseInt(sel, 10));
                else renderMonthTable(parseInt(sel, 10));
            });

            // allow clicking empty cells in the timetable table view to assign subjects
            $(document).on('click', '.timetable-cell', function () {
                var date = $(this).data('date');
                var time = $(this).data('time') || '07:00';
                if (!date) return;
                $('#selectedDate').val(date);
                $('#selected_date_display').val(date);
                $('#degree_time_0').val(time);
                $('#degree_duration_0').val('');
                $('#degree_subject_0').val('');
                $('#subjectSelectionModal').modal('show');
            });

            // Download visible table as PDF
            $('#downloadTablePdfBtn').on('click', function () {
                var container = document.getElementById('tableViewContainer');
                if (!container || $('#tableViewSection').is(':hidden')) { alert('Render a table first'); return; }
                html2canvas(container, { scale: 2 }).then(function (canvas) {
                    var img = canvas.toDataURL('image/png');
                    const { jsPDF } = window.jspdf;
                    var pdf = new jsPDF('l', 'pt', 'a4');
                    var pw = pdf.internal.pageSize.getWidth() - 40;
                    var ph = pdf.internal.pageSize.getHeight() - 40;
                    var ih = canvas.height * (pw / canvas.width);
                    if (ih <= ph) {
                        pdf.addImage(img, 'PNG', 20, 20, pw, ih);
                    } else {
                        // split across pages
                        var ratio = pw / canvas.width;
                        var total = canvas.height;
                        var rendered = 0;
                        while (rendered < total) {
                            var tmpCanvas = document.createElement('canvas');
                            var tmpCtx = tmpCanvas.getContext('2d');
                            tmpCanvas.width = canvas.width;
                            tmpCanvas.height = Math.min(canvas.height - rendered, Math.floor(ph / ratio));
                            tmpCtx.drawImage(canvas, 0, rendered, canvas.width, tmpCanvas.height, 0, 0, tmpCanvas.width, tmpCanvas.height);
                            var tmpImg = tmpCanvas.toDataURL('image/png');
                            if (rendered > 0) pdf.addPage();
                            pdf.addImage(tmpImg, 'PNG', 20, 20, pw, tmpCanvas.height * ratio);
                            rendered += tmpCanvas.height;
                        }
                    }
                    var title = $('#tableViewTitle').text() || 'timetable';
                    pdf.save(title.replace(/\s+/g, '_') + '.pdf');
                }).catch(function (err) {
                    console.error(err); alert('Failed to generate PDF.');
                });
            });
            // — end additions
            // Fetch courses based on location
            $('#degree_location').change(function () {
                var location = $(this).val();
                if (location) {
                    $.ajax({
                        url: @json(route('timetable.courses.by.location')),
                        type: 'GET',
                        data: { location: location, course_type: 'Degree' },
                        success: function (data) {
                            resetSelect($('#degree_course'), 'Select Course');
                            if (data.success && data.courses && data.courses.length > 0) {
                                $.each(data.courses, function (index, course) {
                                    addSelectOption($('#degree_course'), course.course_id, course.course_name);
                                });
                                $('#degree_course').prop('disabled', false);
                            } else {
                                resetSelect($('#degree_course'), 'No courses found');
                                $('#degree_course').prop('disabled', true);
                                showTimetableToast('Info', (data && data.message) ? data.message : 'No courses found for this location.', 'warning');
                            }
                        },
                        error: function () {
                            showTimetableToast('Error', 'Could not load courses.', 'error');
                        }
                    });
                }
            });

            // Certificate: Fetch courses based on certificate location
            $('#certificate_location').change(function () {
                var location = $(this).val();
                if (location) {
                    $.ajax({
                        url: @json(route('timetable.courses.by.location')),
                        type: 'GET',
                        data: { location: location, course_type: 'Certificate' },
                        success: function (data) {
                            resetSelect($('#certificate_course'), 'Select Course');
                            if (data && data.courses && data.courses.length > 0) {
                                $.each(data.courses, function (i, course) {
                                    addSelectOption($('#certificate_course'), course.course_id, course.course_name);
                                });
                                $('#certificate_course').prop('disabled', false);
                            } else {
                                resetSelect($('#certificate_course'), 'No courses found');
                                $('#certificate_course').prop('disabled', true);
                                showTimetableToast('Info', 'No courses found for this location.', 'warning');
                            }
                        },
                        error: function () {
                            showTimetableToast('Error', 'Could not load courses.', 'error');
                        }
                    });
                }
            });

            // Fetch intakes, reset downstream, sync pdf_course, and load specializations — single unified handler
            $('#degree_course').on('change', function () {
                var courseId = $(this).val();
                var location = $('#degree_location').val();

                // Reset all downstream controls
                $('#degree_semester').val('').empty()
                    .append('<option selected disabled value="">Select Semester</option>')
                    .prop('disabled', true);
                $('#degree_specialization').val('');
                $('#degree_specialization_row').hide();
                $('#degree_start_date').val('');
                $('#degree_end_date').val('');
                setDownloadButtonsVisible('#degree_download_buttons', false);
                $('#degreeTimetableSection').hide();

                // Sync pdf_course options
                $('#pdf_course').empty().append('<option value="">All</option>');
                $(this).find('option').each(function () {
                    var v = $(this).attr('value'), t = $(this).text();
                    if (v) $('#pdf_course').append('<option value="' + v + '">' + t + '</option>');
                });
                if (courseId) $('#pdf_course').val(courseId);

                // Load intakes
                if (courseId && location) {
                    $.ajax({
                        url: '/timetable/get-intakes/' + courseId + '/' + location,
                        type: 'GET',
                        success: function (data) {
                            console.log('Intakes data received:', data);
                            resetSelect($('#degree_intake'), 'Select Intake');
                            if (data.intakes && data.intakes.length > 0) {
                                $.each(data.intakes, function (index, intake) {
                                    addSelectOption($('#degree_intake'), intake.intake_id, intake.batch, {
                                        start: intake.start_date || '',
                                        end: intake.end_date || ''
                                    });
                                });
                                $('#degree_intake').prop('disabled', false);
                            } else {
                                resetSelect($('#degree_intake'), 'No intakes found');
                                $('#degree_intake').prop('disabled', true);
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error fetching intakes:', error);
                        }
                    });
                }

                // Load specializations
                loadDegreeSpecializations();
            });

            // Certificate: Fetch intakes when certificate course changes
            $('#certificate_course').change(function () {
                var courseId = $(this).val();
                var location = $('#certificate_location').val();
                $('#certificate_start_date').val('');
                $('#certificate_end_date').val('');
                if (courseId && location) {
                    $.ajax({
                        url: '/timetable/get-intakes/' + courseId + '/' + location,
                        type: 'GET',
                        success: function (data) {
                            resetSelect($('#certificate_intake'), 'Select Intake');
                            if (data.intakes && data.intakes.length > 0) {
                                $.each(data.intakes, function (index, intake) {
                                    addSelectOption($('#certificate_intake'), intake.intake_id, intake.batch, {
                                        start: intake.start_date || '',
                                        end: intake.end_date || ''
                                    });
                                });
                                $('#certificate_intake').prop('disabled', false);
                            } else {
                                resetSelect($('#certificate_intake'), 'No intakes found');
                                $('#certificate_intake').prop('disabled', true);
                            }
                        },
                        error: function () { console.error('Error fetching certificate intakes'); }
                    });
                }
            });

            // Fetch semesters based on course and intake
            $('#degree_intake').change(function () {
                var intakeId = $(this).val();
                var courseId = $('#degree_course').val();
                var selected = $(this).find('option:selected');
                var start = selected.data('start') || '';
                var end = selected.data('end') || '';

                if (start && moment(start).isValid()) start = moment(start).format('YYYY-MM-DD');
                if (end && moment(end).isValid()) end = moment(end).format('YYYY-MM-DD');

                $('#degree_start_date').val(start);
                $('#degree_end_date').val(end);
                setDownloadButtonsVisible('#degree_download_buttons', !!start && !!end);

                if (intakeId && courseId) {
                    $.ajax({
                        url: '/timetable/get-semesters',
                        type: 'GET',
                        data: { course_id: courseId, intake_id: intakeId },
                        success: function (data) {
                            console.log("Semesters data received:", data); // Debug log for semesters

                            resetSelect($('#degree_semester'), 'Select Semester');

                            if (data.semesters && data.semesters.length > 0) {
                                $.each(data.semesters, function (index, semester) {
                                    var label = semester.display_name || semester.semester_name || semester.name;
                                    addSelectOption($('#degree_semester'), semester.id, label, {
                                        start: semester.start_date || '',
                                        end: semester.end_date || ''
                                    });
                                });
                                $('#degree_semester').prop('disabled', false);
                            } else {
                                resetSelect($('#degree_semester'), 'No semesters found');
                                $('#degree_semester').prop('disabled', true);

                                if ($('#degree_location').val() && $('#degree_course').val() && $('#degree_intake').val() && start && end) {
                                    $('#showTimetableBtn').trigger('click', [{ autoOpenIfEmpty: false }]);
                                }
                            }
                        },
                        error: function (xhr, status, error) {
                            console.error('Error fetching semesters:', error);
                        }
                    });
                }
            });

            // Certificate: set course start/end dates when intake changes
            $('#certificate_intake').change(function () {
                var selected = $(this).find('option:selected');
                var start = selected.data('start') || '';
                var end = selected.data('end') || '';
                if (start && moment(start).isValid()) start = moment(start).format('YYYY-MM-DD');
                if (end && moment(end).isValid()) end = moment(end).format('YYYY-MM-DD');
                $('#certificate_start_date').val(start);
                $('#certificate_end_date').val(end);
                setDownloadButtonsVisible('#certificate_download_buttons', !!start && !!end);

                // Auto-show timetable after all certificate fields are selected
                if ($('#certificate_location').val() && $('#certificate_course').val() && $('#certificate_intake').val() && start && end) {
                    $('#certificate_showTimetableBtn').trigger('click', [{ autoOpenIfEmpty: true }]);
                }

                // Note: subject list is populated on modal open (openNewTimetablePopup), not eagerly here,
                // to avoid writing certificate modules into #degree_subject_0 while on the degree tab.
            });

            // Removed: the delegated $(document).on('change','#degree_semester') handler that duplicated
            // the direct handler below. Logic is now consolidated into the single direct handler.

            // XHR abort handles — cancels stale in-flight requests on rapid selection changes
            var _degreeSubjectXhr      = null;
            var _certificateSubjectXhr = null;

            // Fetch available subjects based on semester (Fix 4: aborts stale XHR)
            function loadDegreeSubjects() {
                var semesterId = $('#degree_semester').val();
                var courseId   = $('#degree_course').val();
                var specialization = $('#degree_specialization').val();
                if ($('#degree_specialization_row').is(':visible') && !specialization) {
                    resetSelect($('#degree_subject_0'), 'Select Specialization first');
                    $('#degree_subject_0').prop('disabled', true);
                    return;
                }
                if (semesterId && courseId) {
                    if (_degreeSubjectXhr) { _degreeSubjectXhr.abort(); _degreeSubjectXhr = null; }
                    _degreeSubjectXhr = $.ajax({
                        url: timetableModulesUrl,
                        type: 'GET',
                        data: { semester_id: semesterId, course_id: courseId, specialization: specialization },
                        success: function (data) {
                            _degreeSubjectXhr = null;
                            resetSelect($('#degree_subject_0'), 'Select Subject');
                            if (data.modules && data.modules.length > 0) {
                                $.each(data.modules, function (index, module) {
                                    addSelectOption($('#degree_subject_0'), module.module_id, module.module_name + ' (' + module.module_code + ')');
                                });
                                $('#degree_subject_0').prop('disabled', false);
                            } else {
                                resetSelect($('#degree_subject_0'), 'No subjects found');
                                $('#degree_subject_0').prop('disabled', true);
                            }
                        },
                        error: function (xhr, status) {
                            if (status !== 'abort') console.error('Error fetching degree subjects:', status);
                            _degreeSubjectXhr = null;
                        }
                    });
                }
            }

            // Single consolidated #degree_semester handler — auto-fills dates, manages buttons,
            // loads subjects, and auto-triggers timetable display.
            $('#degree_semester').on('change', function () {
                var selected = $(this).find('option:selected');
                var start = selected.data('start') || '';
                var end   = selected.data('end')   || '';
                if (start && moment(start).isValid()) start = moment(start).format('YYYY-MM-DD');
                if (end   && moment(end).isValid())   end   = moment(end).format('YYYY-MM-DD');
                $('#degree_start_date').val(start);
                $('#degree_end_date').val(end);
                if ($(this).val()) {
                    setDownloadButtonsVisible('#degree_download_buttons', true);
                } else {
                    setDownloadButtonsVisible('#degree_download_buttons', false);
                }
                loadDegreeSubjects();
                if ($('#degree_location').val() && $('#degree_course').val() && $('#degree_intake').val() && start && end) {
                    $('#showTimetableBtn').trigger('click', [{ autoOpenIfEmpty: true }]);
                }
            });

            function loadDegreeSpecializations() {
                var courseId = $('#degree_course').val();
                var $specialization = $('#degree_specialization');
                if (!courseId) {
                    $('#degree_specialization_row').hide();
                    $specialization.empty().append('<option selected disabled value="">Select Specialization</option>').prop('disabled', true);
                    return;
                }

                $.ajax({
                    url: '{{ route('timetable.specializations.for.course') }}',
                    type: 'GET',
                    data: { course_id: courseId },
                    success: function (response) {
                    $specialization.empty();
                    resetSelect($specialization, 'Select Specialization');
                    if (response && Array.isArray(response.specializations) && response.specializations.length) {
                        response.specializations.forEach(function (specialization) {
                            var value = typeof specialization === 'object' ? (specialization.name || specialization.value || specialization.specialization || '') : specialization;
                            if (value) {
                                addSelectOption($specialization, value, value);
                            }
                        });
                            $specialization.prop('disabled', false);
                            $('#degree_specialization_row').show();
                            return;
                        }

                        $('#degree_specialization_row').hide();
                        $specialization.prop('disabled', true);
                    },
                    error: function () {
                        $('#degree_specialization_row').hide();
                        $specialization.prop('disabled', true);
                    }
                });
            }

            // Removed: duplicate #degree_course handler (specialization reset + loadDegreeSpecializations).
            // This logic is now part of the single unified #degree_course handler above.

            $('#degree_specialization').on('change', function () {
                loadDegreeSubjects();
            });

            // Fix 3 + Fix 4: certificate subject loader — aborts stale XHR, populates the shared
            // #degree_subject_0 only when called from the modal-open path (openNewTimetablePopup).
            // It is no longer called eagerly on intake change, preventing cross-tab pollution.
            function loadCertificateSubjects() {
                var courseId = $('#certificate_course').val();
                var intakeId = $('#certificate_intake').val();
                if (!courseId || !intakeId) return;

                if (_certificateSubjectXhr) { _certificateSubjectXhr.abort(); _certificateSubjectXhr = null; }
                _certificateSubjectXhr = $.ajax({
                    url: '/timetable/get-intake-modules',
                    type: 'GET',
                    data: { course_id: courseId, intake_id: intakeId },
                    success: function (data) {
                        _certificateSubjectXhr = null;
                        // Guard: only update the subject dropdown when the modal is currently
                        // in certificate mode (prevents a late response from clobbering degree options).
                        if ($('#selectedTimetableMode').val() !== 'certificate') return;
                        resetSelect($('#degree_subject_0'), 'Select Subject');
                        if (data.modules && data.modules.length > 0) {
                            $.each(data.modules, function (index, module) {
                                addSelectOption($('#degree_subject_0'), module.module_id, module.module_name + ' (' + module.module_code + ')');
                            });
                            $('#degree_subject_0').prop('disabled', false);
                        } else {
                            resetSelect($('#degree_subject_0'), 'No subjects found');
                            $('#degree_subject_0').prop('disabled', true);
                        }
                    },
                    error: function (xhr, status) {
                        if (status !== 'abort') console.error('Error fetching certificate subjects:', status);
                        _certificateSubjectXhr = null;
                    }
                });
            }

            // Initialize FullCalendar v5
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'timeGridWeek',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                height: 'auto',
                handleWindowResize: true,
                allDaySlot: false,
                editable: true,
                selectable: true,
                // show hours from 07:00 to 18:00
                slotMinTime: '07:00:00',
                slotMaxTime: '18:00:00',
                slotDuration: '00:15:00',
                nowIndicator: true,
                expandRows: true,
                firstDay: 0, // Sunday
                events: [],
                eventDidMount: function(info) {
                    // custom styling similar to previous implementation
                    info.el.style.backgroundColor = '#007bff';
                    info.el.style.borderColor = '#0056b3';
                    info.el.style.color = '#fff';
                    info.el.style.opacity = '0.95';
                    info.el.querySelector('.fc-event-title')?.style && (info.el.querySelector('.fc-event-title').style.fontWeight = '600');
                },
                eventClick: function(info) {
                    var event = info.event;
                    if (!event) return;

                    // populate details modal
                    $('#ev_id').val(event.id || '');
                    $('#ev_subject').text(event.title || (event.extendedProps && (event.extendedProps.module_name || event.extendedProps.subject_name)) || '');
                    $('#ev_date').text(event.start ? moment(event.start).format('YYYY-MM-DD') : '');
                    $('#ev_time').text(event.start ? moment(event.start).format('HH:mm') + (event.end ? ' - ' + moment(event.end).format('HH:mm') : '') : '');

                    // show optional props if present
                    var classroom = (event.extendedProps && (event.extendedProps.classroom || event.extendedProps.room || event.extendedProps.location_name)) || '';
                    var lecturer = (event.extendedProps && (event.extendedProps.lecturer || event.extendedProps.lecturer_name)) || '';
                    $('#ev_classroom').text(classroom || '-');
                    $('#ev_lecturer').text(lecturer || '-');

                    $('#eventDetailsModal').modal('show');
                },
                select: function(selectionInfo) {
                    var startDate = moment(selectionInfo.start).format('YYYY-MM-DD');
                    var startTime = moment(selectionInfo.start).format('HH:mm');
                    $('#selectedTimetableMode').val('degree');
                    $('#selectedDate').val(startDate);
                    $('#selected_date_display').val(startDate);
                    $('#degree_time_0').val(startTime);
                    $('#degree_duration_0').val('');
                    $('#degree_subject_0').val('');
                    $('#subjectSelectionModal').modal('show');
                    calendar.unselect();
                },
                dateClick: function(info) {
                    var d = moment(info.date).format('YYYY-MM-DD');
                    var t = moment(info.date).format('HH:mm');
                    $('#selectedTimetableMode').val('degree');
                    $('#selectedDate').val(d);
                    $('#selected_date_display').val(d);
                    $('#degree_time_0').val(t);
                    $('#degree_duration_0').val('');
                    $('#degree_subject_0').val('');
                    $('#subjectSelectionModal').modal('show');
                },
                eventOverlap: true,
            });

            // attach calendar instance to window for debug access
            window.__nebulaCalendar = calendar;
            calendar.render();

            $('#timetableTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
                refreshCalendarsSize();
            });
            $(window).on('resize', function () {
                refreshCalendarsSize();
            });

            function applyCalendarDateRange(calendarInstance, startDate, endDate) {
                if (!calendarInstance || !startDate || !endDate) {
                    return;
                }

                var startMoment = moment(startDate, 'YYYY-MM-DD', true);
                var endMoment = moment(endDate, 'YYYY-MM-DD', true);

                if (!startMoment.isValid() || !endMoment.isValid() || endMoment.isBefore(startMoment)) {
                    return;
                }

                calendarInstance.setOption('validRange', {
                    start: startMoment.format('YYYY-MM-DD'),
                    end: endMoment.clone().add(1, 'day').format('YYYY-MM-DD')
                });
                calendarInstance.gotoDate(startMoment.format('YYYY-MM-DD'));
            }

            // Removed: duplicate #degree_course handler (pdf_course sync).
            // pdf_course syncing is now part of the single unified #degree_course handler above.

            function openNewTimetablePopup(mode) {
                var isCertificate = mode === 'certificate';
                var startDate = isCertificate ? $('#certificate_start_date').val() : $('#degree_start_date').val();

                if (!startDate || !moment(startDate).isValid()) {
                    return;
                }

                $('#selectedTimetableMode').val(mode);
                $('#selectedDate').val(moment(startDate).format('YYYY-MM-DD'));
                $('#selected_date_display').val(moment(startDate).format('YYYY-MM-DD'));
                $('#degree_time_0').val('07:00');
                $('#degree_duration_0').val('');
                $('#degree_subject_0').val('');

                if (isCertificate) {
                    loadCertificateSubjects();
                } else {
                    loadDegreeSubjects();
                }

                $('#subjectSelectionModal').modal('show');
            }

            function showTimetableEmptyNotice(sectionId, message) {
                var noticeId = sectionId + '_notice';
                var $notice = $('#' + noticeId);
                if (!$notice.length) {
                    $notice = $('<div id="' + noticeId + '" class="alert alert-info mt-2 py-2"></div>');
                    $('#' + sectionId).prepend($notice);
                }
                $notice.text(message).show();
            }

            function hideTimetableEmptyNotice(sectionId) {
                $('#' + sectionId + '_notice').hide();
            }

            // Show Timetable button click event to load events
            $('#showTimetableBtn').click(function (event, options) {
                options = options || {};
                if ($('#degree_specialization_row').is(':visible') && !$('#degree_specialization').val()) {
                    alert('Please select a specialization first.');
                    return;
                }
                var data = {
                    location: $('#degree_location').val(),
                    course_id: $('#degree_course').val(),
                    intake_id: $('#degree_intake').val(),
                    specialization: $('#degree_specialization').val(),
                    semester: $('#degree_semester').val(),
                    start_date: $('#degree_start_date').val(),
                    end_date: $('#degree_end_date').val()
                };
                console.log("Timetable data being sent:", data);

                applyCalendarDateRange(window.__nebulaCalendar, data.start_date, data.end_date);

                $.ajax({
                    url: timetableEventsUrl,
                    type: 'GET',
                    data: data,
                    success: function (response) {
                        console.log("Timetable events received (raw):", response);

                        var eventsArray = [];
                        if (Array.isArray(response)) {
                            eventsArray = response;
                        } else if (response && Array.isArray(response.events)) {
                            eventsArray = response.events;
                        } else if (response && response.data && Array.isArray(response.data.events)) {
                            eventsArray = response.data.events;
                        } else if (response && response.data && Array.isArray(response.data)) {
                            eventsArray = response.data;
                        }

                        // show calendar section first so layout/scroll exists
                        $('#degreeTimetableSection').show();
                        // ensure calendar re-render (important when it was hidden)
                        setTimeout(function () {
                            if (window.__nebulaCalendar) {
                                applyCalendarDateRange(window.__nebulaCalendar, data.start_date, data.end_date);
                                window.__nebulaCalendar.render();
                            }
                        }, 40);

                        // remove previous events safely
                        try {
                            if (window.__nebulaCalendar) {
                                try { window.__nebulaCalendar.removeAllEvents(); } catch(e) { console.warn('removeAllEvents failed', e); }
                            }
                        } catch (err) {
                            console.warn('Safe removeAllEvents error:', err);
                        }

                        if (!eventsArray || !eventsArray.length) {
                            console.info('No events returned.');
                            latestEventsRaw = [];
                            latestFcEvents = [];
                            $('#degreeTimetableSection').show();
                            showTimetableEmptyNotice('degreeTimetableSection', 'No timetable has been created for these filters yet. Click a date slot to add your first timetable entry.');
                            if (window.__nebulaCalendar) {
                                try {
                                    applyCalendarDateRange(window.__nebulaCalendar, data.start_date, data.end_date);
                                    window.__nebulaCalendar.render();
                                } catch (e) { console.warn('Degree calendar render failed', e); }
                            }
                            if (options.autoOpenIfEmpty) {
                                openNewTimetablePopup('degree');
                            } else {
                                refreshCalendarsSize();
                            }
                            return;
                        }

                        // build robust FullCalendar events array, skip invalid rows
                        var fcEvents = [];
                        eventsArray.forEach(function (e, idx) {
                            var title = e.module_name || e.subject_name || e.title || 'Class';
                            var datePart = e.date || e.day || '';
                            var startTime = (e.time || '').toString().trim();
                            var endTime = (e.end_time || '').toString().trim();

                                    // compute endTime from duration if needed
                                    if (!endTime && e.duration) {
                                        var mStartTmp = moment(startTime, ['HH:mm:ss','HH:mm','h:mm A']);
                                        if (mStartTmp.isValid()) {
                                            // incoming stored duration may already be minutes; if it's a small float or integer and looks like hours (<=24),
                                            // conservatively treat values <=24 with decimal point as hours and convert to minutes. Otherwise assume minutes.
                                            var rawDur = parseFloat(e.duration);
                                            var durMinutes = 0;
                                            if (!isNaN(rawDur)) {
                                                if (Math.abs(rawDur) <= 24 && String(e.duration).indexOf('.') !== -1) {
                                                    // treat as hours with decimals
                                                    durMinutes = Math.round(rawDur * 60);
                                                } else if (Math.abs(rawDur) <= 24 && String(e.duration).indexOf('.') === -1 && rawDur <= 8) {
                                                    // if small integer and <=8, user might have entered hours without decimal; treat as hours
                                                    durMinutes = Math.round(rawDur * 60);
                                                } else {
                                                    // otherwise assume minutes already
                                                    durMinutes = Math.round(rawDur);
                                                }
                                            }
                                            endTime = mStartTmp.clone().add(durMinutes || 0, 'minutes').format('HH:mm');
                                        } else {
                                            endTime = startTime;
                                        }
                                    }

                            // require date and startTime to build ISO datetimes
                            if (!datePart || !startTime) {
                                console.warn('Skipping event missing date or start time', e);
                                return;
                            }

                            var mStart = moment(datePart + ' ' + startTime, ['YYYY-MM-DD HH:mm:ss','YYYY-MM-DD HH:mm','YYYY-MM-DD h:mm A', 'YYYY-MM-DD H:mm']);
                            var mEnd = null;
                            if (endTime) {
                                mEnd = moment(datePart + ' ' + endTime, ['YYYY-MM-DD HH:mm:ss','YYYY-MM-DD HH:mm','YYYY-MM-DD h:mm A', 'YYYY-MM-DD H:mm']);
                            } else {
                                // default duration 1 minute to avoid same-start/end problems
                                mEnd = mStart.clone().add(1, 'minutes');
                            }

                            if (!mStart.isValid() || !mEnd.isValid()) {
                                console.warn('Skipping invalid datetime event', e);
                                return;
                            }

                           // debug: log parsed start/end for each incoming row
                           console.log('Parsed event datetimes:', {
                               raw: e,
                               parsedStart: mStart.format('YYYY-MM-DD HH:mm:ss'),
                               parsedEnd: mEnd.format('YYYY-MM-DD HH:mm:ss')
                           });

                            // use local-formatted datetimes (no trailing Z) so FullCalendar places events correctly in week/day views
                            // use Date objects to avoid ISO parsing/timezone edge-cases in agendaWeek/Day
                            fcEvents.push({
                                id: (e.id !== undefined && e.id !== null) ? e.id : idx,
                                title: title,
                                // pass native Date objects to avoid parsing ambiguity
                                start: mStart.toDate(),
                                end: mEnd.toDate(),
                                allDay: false,
                                extendedProps: e,
                                overlap: true
                            });
                        });

                        // add events after short delay so calendar layout is ready (fix scrollTop errors)
                        setTimeout(function () {
                            console.log('Adding events to FullCalendar, count:', fcEvents.length, fcEvents);
                            // dedupe events by id (server may accidentally return duplicates)
                            var seen = {};
                            var uniqueFc = [];
                            fcEvents.forEach(function(ev){
                                var key = ev.id || (ev.start && ev.start.toString()) || JSON.stringify(ev);
                                if (!seen[key]) { seen[key] = true; uniqueFc.push(ev); }
                            });
                            console.log('Unique events after dedupe:', uniqueFc.length, uniqueFc);
                            try {
                                if (window.__nebulaCalendar) {
                                    try { window.__nebulaCalendar.removeAllEvents(); } catch(e) { console.warn('removeAllEvents failed', e); }
                                    try { var sources = window.__nebulaCalendar.getEventSources(); sources.forEach(function(s){ try { s.remove(); } catch(e){} }); } catch(e) { /* ignore */ }
                                }
                            } catch (ex) { console.warn('Error clearing previous events:', ex); }

                            try { if (window.__nebulaCalendar) window.__nebulaCalendar.addEventSource(uniqueFc); } catch(e) { console.warn('addEventSource failed', e); }
                            try { if (window.__nebulaCalendar) window.__nebulaCalendar.refetchEvents(); } catch(e) { /* ignore */ }

                            // debug: inspect what FullCalendar actually stored
                            setTimeout(function () {
                                try {
                                    var stored = (window.__nebulaCalendar) ? window.__nebulaCalendar.getEvents() : [];
                                    console.log('Client events in calendar (count):', stored.length);
                                    stored.forEach(function (ev, i) {
                                        try {
                                            var stType = ev.start ? Object.prototype.toString.call(ev.start) : 'null';
                                            var enType = ev.end ? Object.prototype.toString.call(ev.end) : 'null';
                                            var stStr = ev.start ? (moment(ev.start).isValid() ? moment(ev.start).format('YYYY-MM-DD HH:mm:ss') : ev.start.toString()) : 'null';
                                            var enStr = ev.end ? (moment(ev.end).isValid() ? moment(ev.end).format('YYYY-MM-DD HH:mm:ss') : ev.end.toString()) : 'null';
                                            console.log('Stored event', i, 'id:', ev.id, 'title:', ev.title, 'startType:', stType, 'endType:', enType, 'start:', stStr, 'end:', enStr, ev);
                                        } catch (inner) { console.warn('Error inspecting event', inner); }
                                    });
                                } catch (e) { console.warn('Failed to read client events', e); }
                            }, 150);
                        }, 120);

                        hideTimetableEmptyNotice('degreeTimetableSection');

                        // keep raw server data
                        latestEventsRaw = eventsArray;
                        // keep fc events
                        latestFcEvents = fcEvents;

                        // AUTO UX: build periods and auto-render first period so users don't need to pick
                        buildPeriodsFromSemester();
                        // select first period option (if exists) and render automatically
                        var sel = $('#tablePeriod option').not('[value=""]').first().val();
                        if (sel) {
                            var dtype = $('#tablePeriod option:selected').data('type') || $('#tableViewType').val() || 'week';
                            $('#tablePeriod').val(sel);
                            // render selected period automatically
                            if (dtype === 'week') renderWeekTable(parseInt(sel, 10));
                            else renderMonthTable(parseInt(sel, 10));
                        }
                    },
                    error: function (xhr, status, error) {
                        alert('Error occurred while fetching the timetable');
                    }
                });
            });

            // Download PDF button shows filter modal
            $('#downloadPdfBtn').on('click', function () {
                // copy current values into modal
                $('#pdf_location').val($('#degree_location').val() || '');
                $('#pdf_course').empty().append('<option value="">All</option>');
                $('#degree_course option').each(function () {
                    var v = $(this).val();
                    var t = $(this).text();
                    if (v) $('#pdf_course').append('<option value="' + v + '">' + t + '</option>');
                });
                $('#pdf_course').val($('#degree_course').val() || '');
                // autofill date range if chosen
                $('#pdf_from').val($('#degree_start_date').val() || '');
                $('#pdf_to').val($('#degree_end_date').val() || '');
                $('#downloadPdfModal').modal('show');
            });

            // Generate PDF from filtered events (simple list PDF)
            $('#generatePdfBtn').off('click').on('click', function () {
                // include semester (fall back to main form) — server validation needs this
                if ($('#degree_specialization_row').is(':visible') && !$('#degree_specialization').val()) {
                    alert('Please select a specialization first.');
                    return;
                }
                var filters = {
                    location: $('#pdf_location').val() || $('#degree_location').val() || '',
                    course_id: $('#pdf_course').val() || $('#degree_course').val() || '',
                    intake_id: $('#pdf_intake').val() || $('#degree_intake').val() || '',
                    specialization: $('#degree_specialization').val() || '',
                    semester: $('#pdf_semester')?.val() || $('#degree_semester').val() || '',
                    start_date: $('#pdf_from').val() || $('#degree_start_date').val() || '',
                    end_date: $('#pdf_to').val() || $('#degree_end_date').val() || ''
                };

                // required validation client-side to avoid 422
                if (!filters.course_id || !filters.intake_id || !filters.semester) {
                    alert('Please select Course, Intake and Semester before generating PDF.');
                    return;
                }

                $('#downloadPdfModal').modal('hide');

                $.ajax({
                    url: timetableEventsUrl,
                    type: 'GET',
                    data: filters,
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '' },
                    success: function (response) {
                        var events = [];
                        if (Array.isArray(response)) events = response;
                        else if (response && Array.isArray(response.events)) events = response.events;
                        else if (response && response.data && Array.isArray(response.data.events)) events = response.data.events;
                        else if (response && response.data && Array.isArray(response.data)) events = response.data;

                        if (!events || !events.length) {
                            alert('No events found for selected filters.');
                            return;
                        }

                        const { jsPDF } = window.jspdf;
                        var doc = new jsPDF({ unit: 'pt', format: 'a4' });
                        var y = 40;
                        doc.setFontSize(14);
                        doc.text('Timetable Export', 40, y);
                        doc.setFontSize(10);
                        y += 20;
                        events.forEach(function (ev, i) {
                            var date = ev.date || ev.day || '';
                            var start = ev.time || '';
                            var dur = ev.duration ? (ev.duration + ' min') : (ev.end_time ? (start + ' - ' + ev.end_time) : '');
                            var title = ev.module_name || ev.subject_name || ev.title || 'Class';
                            var line = (i+1) + '. ' + (date ? (date + ' ') : '') + (start ? (start + ' ') : '') + '- ' + title + ' (' + dur + ')';
                            var split = doc.splitTextToSize(line, 520);
                            doc.text(split, 40, y);
                            y += (split.length * 12) + 6;
                            if (y > 740) { doc.addPage(); y = 40; }
                        });

                        doc.save('timetable.pdf');
                    },
                    error: function (xhr) {
                        var msg = 'Failed to fetch events for PDF.';
                        try {
                            var json = xhr.responseJSON || JSON.parse(xhr.responseText || '{}');
                            if (json && json.errors) {
                                var first = Object.values(json.errors)[0];
                                msg = Array.isArray(first) ? first[0] : first;
                            } else if (json && json.message) {
                                msg = json.message;
                            } else if (xhr.responseText) {
                                msg = xhr.responseText;
                            }
                        } catch (e) {}
                        alert(msg);
                        console.error('PDF fetch error:', xhr);
                    }
                });
            });

            // --- Simplified download: Week / Month PDF (no period selector) ---
            function getSemesterStart() {
                var s = $('#degree_start_date').val();
                if (!s || !moment(s).isValid()) return null;
                return moment(s).startOf('day');
            }

            function computeWeekIndexForDate(dtMoment) {
                var start = getSemesterStart();
                if (!start) return 1;
                var diffDays = dtMoment.startOf('day').diff(start, 'days');
                if (diffDays < 0) return 1;
                return Math.floor(diffDays / 7) + 1;
            }

            function computeMonthIndexForDate(dtMoment) {
                var start = getSemesterStart();
                if (!start) return 1;
                return dtMoment.startOf('month').diff(start.startOf('month'), 'months') + 1;
            }

            // build week table HTML (no raw dates in output); weekIndex = 1..N
            function buildWeekHtml(weekIndex) {
                var start = getSemesterStart();
                if (!start) { alert('Semester start date required'); return ''; }
                var weekStart = start.clone().add((weekIndex - 1) * 7, 'days');
                var days = [];
                for (var d = 0; d < 7; d++) days.push(weekStart.clone().add(d, 'days'));
                var html = '<div style="padding:10px;font-family:Helvetica,Arial,sans-serif;"><h3 style="margin:0 0 10px 0;">Week ' + weekIndex + '</h3>';
                html += '<table style="width:100%;border-collapse:collapse;font-size:10pt;"><thead><tr>';
                html += '<th style="width:90px;border:1px solid #000;padding:6px;background:#f7f7f7;">Time</th>';
                days.forEach(function (dt) { html += '<th style="border:1px solid #000;padding:6px;text-align:center;background:#f7f7f7;">' + dt.format('dddd') + '</th>'; });
                html += '</tr></thead><tbody>';
                var startHour = 7, endHour = 18;
                for (var h = startHour; h <= endHour; h++) {
                    html += '<tr>';
                    html += '<td style="border:1px solid #000;padding:6px;font-weight:600;">' + moment({ hour: h }).format('HH:mm') + '</td>';
                    for (var c = 0; c < 7; c++) {
                        var day = days[c];
                        var cellEvents = latestFcEvents.filter(function (ev) {
                            try {
                                var evStart = moment(ev.start);
                                var evEnd = ev.end ? moment(ev.end) : evStart.clone().add(1, 'minutes');
                                var rowStart = day.clone().hour(h).minute(0).second(0);
                                var rowEnd = rowStart.clone().add(1, 'hour');
                                return evStart.isBefore(rowEnd) && evEnd.isAfter(rowStart);
                            } catch (e) { return false; }
                        });
                        html += '<td class="timetable-cell" data-date="' + day.format('YYYY-MM-DD') + '" data-time="' + moment({ hour: h }).format('HH:mm') + '" style="border:1px solid #000;padding:6px;vertical-align:top;min-height:40px;cursor:pointer;">';
                        if (cellEvents.length) {
                            cellEvents.forEach(function (ce) {
                                var st = moment(ce.start).format('HH:mm'), en = ce.end ? moment(ce.end).format('HH:mm') : '';
                                html += '<div style="background:#2b8cff;color:#fff;padding:4px 6px;margin-bottom:4px;font-weight:600;">' + ce.title + '</div>';
                                html += '<div style="font-size:9pt;color:#222;margin-bottom:6px;">' + st + (en ? ' - ' + en : '') + '</div>';
                            });
                        } else {
                            html += '&nbsp;';
                        }
                        html += '</td>';
                    }
                    html += '</tr>';
                }
                html += '</tbody></table></div>';
                return html;
            }

            // build month table HTML (no raw dates) — monthIndex relative to semester start
            function buildMonthHtml(monthIndex) {
                var start = getSemesterStart();
                if (!start) { alert('Semester start date required'); return ''; }
                var monthStart = start.clone().add(monthIndex - 1, 'months').startOf('month');
                var monthEnd = monthStart.clone().endOf('month');
                var gridStart = monthStart.clone().startOf('week');
                var gridEnd = monthEnd.clone().endOf('week');
                var curr = gridStart.clone();
                var html = '<div style="padding:10px;font-family:Helvetica,Arial,sans-serif;"><h3 style="margin:0 0 10px 0;">Month ' + monthIndex + '</h3>';
                html += '<table style="width:100%;border-collapse:collapse;font-size:10pt;"><thead><tr>';
                html += '<th style="width:90px;border:1px solid #000;padding:6px;background:#f7f7f7;">Week</th>';
                var days = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                days.forEach(function (d) { html += '<th style="border:1px solid #000;padding:6px;background:#f7f7f7;text-align:center;">' + d + '</th>'; });
                html += '</tr></thead><tbody>';
                var weekNo = 1;
                while (curr.isSameOrBefore(gridEnd)) {
                    html += '<tr><td style="font-weight:600;">Week ' + weekNo + '</td>';
                    for (var i = 0; i < 7; i++) {
                        var cellDate = curr.clone();
                        var cellEvents = latestFcEvents.filter(function (ev) {
                            return moment(ev.start).isSame(cellDate, 'day');
                        });
                        html += '<td class="timetable-cell" data-date="' + cellDate.format('YYYY-MM-DD') + '" data-time="07:00" style="vertical-align:top;min-width:140px;padding:6px;cursor:pointer;">';
                        if (cellEvents.length) {
                            cellEvents.forEach(function (ce) {
                                var st = moment(ce.start).format('HH:mm'), en = ce.end ? moment(ce.end).format('HH:mm') : '';
                        html += '<div class="badge bg-primary text-white mb-1" style="display:block;">' + escapeHtml(ce.title) + '</div>';
                                html += '<div style="font-size:0.85em;color:#333;">' + st + (en ? ' - ' + en : '') + '</div>';
                            });
                        } else {
                            html += '&nbsp;';
                        }
                        html += '</td>';
                        curr.add(1, 'day');
                    }
                    html += '</tr>';
                    weekNo++;
                }
                html += '</tbody></table></div>';
                return html;
            }

            function downloadHtmlAsA4Pdf(htmlContent, filename) {
                // create temp container
                var temp = $('<div></div>').css({ position: 'fixed', left: '-9999px', top: '0', width: '1122px' }).html(htmlContent);
                $('body').append(temp);
                html2canvas(temp.get(0), { scale: 2 }).then(function (canvas) {
                    const { jsPDF } = window.jspdf;
                    // A4 landscape dimensions in points
                    var pdf = new jsPDF('l', 'pt', 'a4');
                    var pageWidth = pdf.internal.pageSize.getWidth();
                    var pageHeight = pdf.internal.pageSize.getHeight();
                    var imgWidth = pageWidth - 40;
                    var imgHeight = canvas.height * (imgWidth / canvas.width);
                    var imgData = canvas.toDataURL('image/png');
                    if (imgHeight <= pageHeight - 40) {
                        pdf.addImage(imgData, 'PNG', 20, 20, imgWidth, imgHeight);
                    } else {
                        // split long image across pages
                        var ratio = imgWidth / canvas.width;
                        var totalHeight = canvas.height;
                        var rendered = 0;
                        while (rendered < totalHeight) {
                            var chunkHeight = Math.min(Math.floor((pageHeight - 40) / ratio), totalHeight - rendered);
                            var tmpCanvas = document.createElement('canvas');
                            tmpCanvas.width = canvas.width;
                            tmpCanvas.height = chunkHeight;
                            tmpCanvas.getContext('2d').drawImage(canvas, 0, rendered, canvas.width, chunkHeight, 0, 0, canvas.width, chunkHeight);
                            var tmpImg = tmpCanvas.toDataURL('image/png');
                            if (rendered > 0) pdf.addPage();
                            pdf.addImage(tmpImg, 'PNG', 20, 20, imgWidth, chunkHeight * ratio);
                            rendered += chunkHeight;
                        }
                    }
                    pdf.save(filename);
                    temp.remove();
                }).catch(function (err) {
                    console.error(err);
                    temp.remove();
                    alert('Failed to generate PDF.');
                });
            }

                // Build a generic grid HTML for an arbitrary date range (start..end inclusive)
                // This replaces the fixed-week/month builders for the PDF download so we can
                // export the calendar's visible range (next/selected week or month).
                function buildGridHtmlForRange(startMoment, endMoment, title) {
                    if (!startMoment || !endMoment || !startMoment.isValid() || !endMoment.isValid()) return '';
                    // build list of days from start to end (inclusive)
                    var days = [];
                    var cursor = startMoment.clone().startOf('day');
                    var last = endMoment.clone().startOf('day');
                    while (cursor.isSameOrBefore(last)) { days.push(cursor.clone()); cursor.add(1, 'day'); }

                    // compute dynamic hour range from latestFcEvents
                    var minHour = 23, maxHour = 0;
                    if (latestFcEvents && latestFcEvents.length) {
                        latestFcEvents.forEach(function(ev) {
                            try {
                                var ms = moment(ev.start);
                                var me = ev.end ? moment(ev.end) : null;
                                if (ms.isValid()) minHour = Math.min(minHour, ms.hour());
                                if (me && me.isValid()) maxHour = Math.max(maxHour, me.hour());
                            } catch (e) {}
                        });
                    }
                    // fallback default if no events or hours out of expected range
                    if (minHour > maxHour) { minHour = 7; maxHour = 18; }
                    // add padding rows for readability
                    minHour = Math.max(0, minHour - 1);
                    maxHour = Math.min(23, maxHour + 1);
                    // Ensure PDF grid covers at least 07:00 - 18:00 per requirement
                    minHour = Math.min(minHour, 7);
                    maxHour = Math.max(maxHour, 18);

                    var html = '<div style="padding:10px;font-family:Helvetica,Arial,sans-serif;"><h3 style="margin:0 0 10px 0;">' + (title || '') + '</h3>';
                    html += '<table style="width:100%;border-collapse:collapse;font-size:10pt;"><thead><tr>';
                    html += '<th style="width:90px;border:1px solid #000;padding:6px;background:#f7f7f7;">Time</th>';
                    days.forEach(function(dt){ html += '<th style="border:1px solid #000;padding:6px;background:#f7f7f7;text-align:center;">' + dt.format('ddd DD/MM') + '</th>'; });
                    html += '</tr></thead><tbody>';

                    // Precompute events per day and compute row spans so multi-hour events
                    // render as a single <td> with rowspan in the exported table.
                    var eventsByDay = [];
                    for (var i = 0; i < days.length; i++) eventsByDay.push([]);

                    latestFcEvents.forEach(function(ev) {
                        try {
                            var ms = moment(ev.start);
                            var me = ev.end ? moment(ev.end) : ms.clone().add(1,'minutes');
                            if (!ms.isValid() || !me.isValid()) return;
                            // find which day column(s) this event intersects
                            for (var di = 0; di < days.length; di++) {
                                var day = days[di].clone().startOf('day');
                                var dayStart = day.clone().hour(minHour).minute(0).second(0);
                                var dayEnd = day.clone().hour(maxHour+1).minute(0).second(0);
                                // check overlap with the visible day range
                                if (me.isAfter(dayStart) && ms.isBefore(dayEnd)) {
                                    // clamp to visible hours
                                    var evStartClamped = ms.isBefore(dayStart) ? dayStart.clone() : ms.clone();
                                    var evEndClamped = me.isAfter(dayEnd) ? dayEnd.clone() : me.clone();
                                    var fractionalStart = evStartClamped.hour() + (evStartClamped.minute()/60);
                                    var fractionalEnd = evEndClamped.hour() + (evEndClamped.minute()/60);
                                    var startRow = Math.floor(fractionalStart);
                                    var endRow = Math.ceil(fractionalEnd) - 1;
                                    // clamp within minHour..maxHour
                                    startRow = Math.max(minHour, Math.min(maxHour, startRow));
                                    endRow = Math.max(minHour, Math.min(maxHour, endRow));
                                    if (startRow <= endRow) {
                                        eventsByDay[di].push({
                                            id: ev.id || Math.random().toString(36).substr(2,6),
                                            title: ev.title || (ev.extendedProps && (ev.extendedProps.module_name || ev.extendedProps.subject_name)) || 'Class',
                                            startRow: startRow,
                                            rowSpan: (endRow - startRow + 1),
                                            startText: moment(ev.start).format('HH:mm'),
                                            endText: ev.end ? moment(ev.end).format('HH:mm') : ''
                                        });
                                    }
                                }
                            }
                        } catch (e) { /* ignore malformed event */ }
                    });

                    // For quick lookup of events that start at a given row per day
                    var eventsStartingAt = [];
                    for (var di = 0; di < days.length; di++) {
                        eventsStartingAt[di] = {};
                        // sort by startRow to make deterministic
                        eventsByDay[di].sort(function(a,b){ return a.startRow - b.startRow; });
                        eventsByDay[di].forEach(function(ev){
                            if (!eventsStartingAt[di][ev.startRow]) eventsStartingAt[di][ev.startRow] = [];
                            eventsStartingAt[di][ev.startRow].push(ev);
                        });
                    }

                    // track active rowspans per day (remaining rows covered)
                    var activeSpans = new Array(days.length).fill(0);

                    for (var h = minHour; h <= maxHour; h++) {
                        html += '<tr>';
                        html += '<td style="border:1px solid #000;padding:6px;font-weight:600;">' + moment({ hour: h }).format('HH:mm') + '</td>';
                        for (var c = 0; c < days.length; c++) {
                            if (activeSpans[c] > 0) {
                                // this cell is covered by a rowspan started earlier; decrement counter and skip adding a <td>
                                activeSpans[c] = activeSpans[c] - 1;
                                continue;
                            }

                            var evs = eventsStartingAt[c][h] || [];
                            if (evs.length) {
                                // compute rowspan as the maximum rows any of the events need
                                var rowspan = evs.reduce(function(acc, it){ return Math.max(acc, it.rowSpan || 1); }, 1);
                                if (rowspan > 1) activeSpans[c] = rowspan - 1;
                                html += '<td rowspan="' + rowspan + '" style="border:1px solid #000;padding:6px;vertical-align:top;min-height:40px;">';
                                // render events side-by-side inside the cell
                                var itemWidth = Math.floor(100 / evs.length) - 1; // percent, reserve small gap
                                html += '<div style="display:flex;gap:6px;align-items:flex-start;">';
                                evs.forEach(function(evst){
                                    html += '<div style="flex:1 1 0;min-width:0;background:#2b8cff;color:#fff;padding:6px;box-sizing:border-box;">';
                                    html += '<div style="font-weight:600;">' + evst.title + '</div>';
                                    html += '<div style="font-size:9pt;color:#222;margin-top:4px;">' + evst.startText + (evst.endText ? ' - ' + evst.endText : '') + '</div>';
                                    html += '</div>';
                                });
                                html += '</div>';
                                html += '</td>';
                            } else {
                                html += '<td style="border:1px solid #000;padding:6px;vertical-align:top;min-height:40px;">&nbsp;</td>';
                            }
                        }
                        html += '</tr>';
                    }

                    html += '</tbody></table></div>';
                    return html;
                }

                // build a month-grid calendar HTML (month grid with day boxes)
                function buildMonthGridHtml(monthStartMoment, title) {
                    if (!monthStartMoment || !monthStartMoment.isValid()) return '';
                    var monthStart = monthStartMoment.clone().startOf('month');
                    var monthEnd = monthStart.clone().endOf('month');
                    var gridStart = monthStart.clone().startOf('week');
                    var gridEnd = monthEnd.clone().endOf('week');

                    var days = [];
                    var cursor = gridStart.clone();
                    while (cursor.isSameOrBefore(gridEnd)) { days.push(cursor.clone()); cursor.add(1, 'day'); }

                    // map events to day index (relative to gridStart)
                    var eventsByDay = {};
                    for (var i = 0; i < days.length; i++) eventsByDay[i] = [];
                    if (latestFcEvents && latestFcEvents.length) {
                        latestFcEvents.forEach(function(ev) {
                            try {
                                var es = moment(ev.start);
                                var ee = ev.end ? moment(ev.end) : es.clone();
                                if (!es.isValid() || !ee.isValid()) return;
                                // iterate days and push if event intersects the day
                                for (var di = 0; di < days.length; di++) {
                                    var day = days[di].clone().startOf('day');
                                    var dayStart = day.clone().startOf('day');
                                    var dayEnd = day.clone().endOf('day');
                                    if (ee.isBefore(dayStart) || es.isAfter(dayEnd)) continue;
                                    // event intersects this day
                                    eventsByDay[di].push({
                                        id: ev.id || Math.random().toString(36).substr(2,6),
                                        title: ev.title || (ev.extendedProps && (ev.extendedProps.module_name || ev.extendedProps.subject_name)) || 'Event',
                                        start: es.clone(),
                                        end: ee.clone()
                                    });
                                }
                            } catch (e) { /* ignore */ }
                        });
                    }

                    // sort events in each day by start time
                    for (var k = 0; k < days.length; k++) {
                        eventsByDay[k].sort(function(a,b){ return a.start.isBefore(b.start) ? -1 : (a.start.isAfter(b.start) ? 1 : 0); });
                    }

                    var html = '<div style="padding:10px;font-family:Helvetica,Arial,sans-serif;">';
                    html += '<h3 style="margin:0 0 10px 0;">' + (title || monthStart.format('MMMM YYYY')) + '</h3>';
                    html += '<table style="width:100%;border-collapse:collapse;font-size:9pt;table-layout:fixed;">';
                    html += '<thead><tr>';
                    var dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                    dayNames.forEach(function(d){ html += '<th style="border:1px solid #000;padding:6px;background:#f7f7f7;text-align:center;">' + d + '</th>'; });
                    html += '</tr></thead><tbody>';

                    for (var r = 0; r < days.length / 7; r++) {
                        html += '<tr>';
                        for (var c = 0; c < 7; c++) {
                            var idx = r * 7 + c;
                            var cellDate = days[idx].clone();
                            var inMonth = cellDate.isSame(monthStart, 'month');
                            var cellEvents = eventsByDay[idx] || [];
                            html += '<td style="vertical-align:top;border:1px solid #000;padding:6px;min-height:90px;overflow:hidden;background:' + (inMonth ? '#fff' : '#f5f5f5') + ';">';
                            // day number
                            html += '<div style="font-size:10pt;font-weight:700;text-align:right;color:#333;">' + cellDate.date() + '</div>';
                            // list events (compact badges). show up to 4 items; collapse the rest with +n more
                            var maxShow = 4;
                            for (var ei = 0; ei < Math.min(cellEvents.length, maxShow); ei++) {
                                var ev = cellEvents[ei];
                                var st = ev.start ? ev.start.format('HH:mm') : '';
                                var en = ev.end ? ev.end.format('HH:mm') : '';
                                html += '<div style="display:block;background:#2b8cff;color:#fff;padding:4px 6px;margin-top:6px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">';
                                html += '<div style="font-size:9pt;font-weight:700;">' + ev.title + '</div>';
                                html += '<div style="font-size:8pt;color:#fff;opacity:0.9;">' + (st || '') + (en ? ' - ' + en : '') + '</div>';
                                html += '</div>';
                            }
                            if (cellEvents.length > maxShow) {
                                html += '<div style="margin-top:4px;color:#666;font-size:9pt;">+' + (cellEvents.length - maxShow) + ' more</div>';
                            }
                            // empty filler
                            if (!cellEvents.length) html += '<div style="height:6px;">&nbsp;</div>';
                            html += '</td>';
                        }
                        html += '</tr>';
                    }

                    html += '</tbody></table></div>';
                    return html;
                }

            // click handlers
            $('#downloadWeekPdfBtn').on('click', function () {
                if (!latestFcEvents || !latestFcEvents.length) { alert('Please load timetable first.'); return; }
                try {
                    var start = null, end = null, title = 'Week Timetable';
                    if (window.__nebulaCalendar && window.__nebulaCalendar.view) {
                        // calendar.view.activeStart and activeEnd are Dates
                        start = moment(window.__nebulaCalendar.view.activeStart);
                        // activeEnd is exclusive; subtract 1ms to include previous day
                        end = moment(new Date(window.__nebulaCalendar.view.activeEnd.getTime() - 1));
                        title = 'Week ' + start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');
                    } else {
                        var today = moment();
                        start = today.clone().startOf('week');
                        end = today.clone().endOf('week');
                    }
                    var html = buildGridHtmlForRange(start, end, title);
                    downloadHtmlAsA4Pdf(html, 'Week_' + start.format('YYYY-MM-DD') + '_Timetable.pdf');
                } catch (e) {
                    console.error('Week PDF generation failed', e);
                    alert('Failed to generate week PDF');
                }
            });

            $('#downloadMonthPdfBtn').on('click', function () {
                if (!latestFcEvents || !latestFcEvents.length) { alert('Please load timetable first.'); return; }
                try {
                    var start = null, end = null, title = 'Month Timetable';
                    if (window.__nebulaCalendar && window.__nebulaCalendar.view) {
                        // Prefer calendar.getDate() which returns the calendar's current date/focus.
                        // That date belongs to the canonical month the user is viewing (regardless of leading/trailing grid days).
                        try {
                            var focused = null;
                            if (typeof window.__nebulaCalendar.getDate === 'function') {
                                focused = moment(window.__nebulaCalendar.getDate());
                            } else {
                                // fallback to view.activeStart (may be outside month)
                                focused = moment(window.__nebulaCalendar.view.currentStart || window.__nebulaCalendar.view.activeStart);
                            }
                            var monthAnchor = focused.clone().startOf('month');
                            start = monthAnchor.clone();
                            end = monthAnchor.clone().endOf('month');
                            title = 'Month ' + monthAnchor.format('YYYY-MM');
                        } catch (e) {
                            // last-resort fallback
                            var today = moment();
                            start = today.clone().startOf('month');
                            end = today.clone().endOf('month');
                            title = 'Month ' + start.format('YYYY-MM');
                        }
                    } else {
                        var today = moment();
                        start = today.clone().startOf('month');
                        end = today.clone().endOf('month');
                    }

                    // Build a month-grid calendar (single month layout)
                    var monthHtml = buildMonthGridHtml(start, title);
                    downloadHtmlAsA4Pdf(monthHtml, 'Month_' + start.format('YYYY-MM') + '_Timetable.pdf');
                } catch (e) {
                    console.error('Month PDF generation failed', e);
                    alert('Failed to generate month PDF');
                }
            });

            // Certificate: Week PDF Download
            $('#certificate_downloadWeekPdfBtn').on('click', function () {
                if (!latestCertificateFcEvents || !latestCertificateFcEvents.length) { alert('Please load certificate timetable first.'); return; }
                try {
                    var start = null, end = null, title = 'Week Timetable';
                    if (window.__nebulaCertificateCalendar && window.__nebulaCertificateCalendar.view) {
                        start = moment(window.__nebulaCertificateCalendar.view.activeStart);
                        end = moment(new Date(window.__nebulaCertificateCalendar.view.activeEnd.getTime() - 1));
                        title = 'Week ' + start.format('YYYY-MM-DD') + ' - ' + end.format('YYYY-MM-DD');
                    } else {
                        var today = moment();
                        start = today.clone().startOf('week');
                        end = today.clone().endOf('week');
                    }
                    var html = buildGridHtmlForRange(start, end, title);
                    downloadHtmlAsA4Pdf(html, 'Certificate_Week_' + start.format('YYYY-MM-DD') + '_Timetable.pdf');
                } catch (e) {
                    console.error('Certificate Week PDF generation failed', e);
                    alert('Failed to generate week PDF');
                }
            });

            // Certificate: Month PDF Download
            $('#certificate_downloadMonthPdfBtn').on('click', function () {
                if (!latestCertificateFcEvents || !latestCertificateFcEvents.length) { alert('Please load certificate timetable first.'); return; }
                try {
                    var start = null, end = null, title = 'Month Timetable';
                    if (window.__nebulaCertificateCalendar && window.__nebulaCertificateCalendar.view) {
                        try {
                            var focused = null;
                            if (typeof window.__nebulaCertificateCalendar.getDate === 'function') {
                                focused = moment(window.__nebulaCertificateCalendar.getDate());
                            } else {
                                focused = moment(window.__nebulaCertificateCalendar.view.currentStart || window.__nebulaCertificateCalendar.view.activeStart);
                            }
                            var monthAnchor = focused.clone().startOf('month');
                            start = monthAnchor.clone();
                            end = monthAnchor.clone().endOf('month');
                            title = 'Month ' + monthAnchor.format('YYYY-MM');
                        } catch (e) {
                            var today = moment();
                            start = today.clone().startOf('month');
                            end = today.clone().endOf('month');
                            title = 'Month ' + start.format('YYYY-MM');
                        }
                    } else {
                        var today = moment();
                        start = today.clone().startOf('month');
                        end = today.clone().endOf('month');
                    }

                    var monthHtml = buildMonthGridHtml(start, title);
                    downloadHtmlAsA4Pdf(monthHtml, 'Certificate_Month_' + start.format('YYYY-MM') + '_Timetable.pdf');
                } catch (e) {
                    console.error('Certificate Month PDF generation failed', e);
                    alert('Failed to generate month PDF');
                }
            });
            // --- end simplified download functions ---

            // safe: download-week button handler is attached below only if button exists
            (function () {
                var dlWeekBtn = document.getElementById('download-week-btn');
                if (!dlWeekBtn) return;
                dlWeekBtn.addEventListener('click', function () {
                    try {
                        var start = moment().format('YYYY-MM-DD'), end = start;
                        if (typeof calendar !== 'undefined' && calendar && calendar.view && calendar.view.activeStart) {
                            start = calendar.view.activeStart.toISOString().split('T')[0];
                            end = calendar.view.activeEnd ? new Date(calendar.view.activeEnd.getTime() - 1).toISOString().split('T')[0] : start;
                        }
                        fetch('/timetable/download-week-pdf', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: JSON.stringify({ start: start, end: end })
                        }).then(function (res) { return res.blob(); })
                          .then(function (blob) {
                             var url = window.URL.createObjectURL(blob);
                             var a = document.createElement('a');
                             a.href = url;
                             a.download = 'timetable_' + start + '_to_' + end + '.pdf';
                             a.click();
                             window.URL.revokeObjectURL(url);
                          }).catch(function (err) {
                             console.error('Week PDF download failed', err);
                          });
                    } catch (e) {
                        console.warn('Download-week handler skipped due to error', e);
                    }
                });
            })();

            // Add another subject block
            $('#addSubjectBtn').on('click', function () {
                // clone the first subject-block template
                var idx = $('#subjectList .subject-block').length;
                var $first = $('#subjectList .subject-block').first();
                var $clone = $first.clone();

                // update ids and values
                $clone.find('select.subject-select').each(function () {
                    var newId = 'degree_subject_' + idx;
                    $(this).attr('id', newId);
                    $(this).val('');
                });
                $clone.find('input.duration-input').each(function () {
                    var newId = 'degree_duration_' + idx;
                    $(this).attr('id', newId);
                    $(this).val('');
                });
                $clone.find('input.time-input').each(function () {
                    var newId = 'degree_time_' + idx;
                    $(this).attr('id', newId);
                    $(this).val('');
                });

                // show remove button on clones
                $clone.find('.remove-subject-btn').show().off('click').on('click', function () {
                    $(this).closest('.subject-block').remove();
                });

                $('#subjectList').append($clone);
            });

            // Remove button for dynamically created blocks (in case user added and wants to remove)
            $(document).on('click', '.remove-subject-btn', function () {
                $(this).closest('.subject-block').remove();
            });

            // Assign subjects handler - POST to server and refresh calendar
            $('#assignSubjectBtn').on('click', function () {
                var date = $('#selectedDate').val();
                if (!date) { alert('No date selected'); return; }

                // Validate date is within the start and end date range
                var mode = $('#selectedTimetableMode').val() || 'degree';
                var isCertificate = mode === 'certificate';
                var startDateStr = isCertificate ? $('#certificate_start_date').val() : $('#degree_start_date').val();
                var endDateStr = isCertificate ? $('#certificate_end_date').val() : $('#degree_end_date').val();

                var selectedMoment = moment(date, 'YYYY-MM-DD');
                var startMoment = moment(startDateStr, 'YYYY-MM-DD');
                var endMoment = moment(endDateStr, 'YYYY-MM-DD');

                if (!isCertificate && $('#degree_specialization_row').is(':visible') && !$('#degree_specialization').val()) {
                    alert('Please select a specialization first.');
                    return;
                }

                if (!selectedMoment.isValid() || !startMoment.isValid() || !endMoment.isValid()) {
                    alert('Invalid date range. Please try again.');
                    return;
                }

                if (selectedMoment.isBefore(startMoment) || selectedMoment.isAfter(endMoment)) {
                    alert('You can only add timetable entries between ' + startMoment.format('YYYY-MM-DD') + ' and ' + endMoment.format('YYYY-MM-DD'));
                    return;
                }

                    var subject_ids = [], durations = [], times = [], end_times = [];
                var valid = true;

                $('#subjectList .subject-block').each(function () {
                    var subj = $(this).find('.subject-select').val();
                    var dur = $(this).find('.duration-input').val();
                    var timeVal = $(this).find('.time-input').val(); // "HH:mm"
                    if (!subj || !dur || !timeVal) {
                        valid = false;
                        return false; // break
                    }

                    // parse and normalize time; produce "h:mm A" for controller validation
                    var m = moment(timeVal, ['HH:mm','HH:mm:ss','h:mm A']);
                    if (!m.isValid()) {
                        valid = false;
                        return false;
                    }
                    // use 24-hour format to keep server/store consistent and easier to parse later
                    var startFormatted = m.format('HH:mm');
                    // dur input is in HOURS (can be decimal). Convert to minutes for storage and calculations.
                    var durFloat = parseFloat(dur);
                    var durMinutes = 0;
                    if (!isNaN(durFloat)) {
                        // treat numeric input as hours when reasonable (e.g. 1.5 => 90), otherwise fallback to 0
                        durMinutes = Math.round(durFloat * 60);
                    }
                    var endMoment = m.clone().add(durMinutes || 0, 'minutes');
                    var endFormatted = endMoment.format('HH:mm');

                    subject_ids.push(subj);
                    // store durations as integer minutes on payload so backend remains unchanged
                    durations.push(durMinutes);
                    times.push(startFormatted);
                    end_times.push(endFormatted);
                });

                if (!valid) { alert('Please fill subject, duration and valid time for all entries.'); return; }

                var classroomsArr = [], lecturersArr = [];
                $('#subjectList .subject-block').each(function () {
                    classroomsArr.push($(this).find('.classroom-input').val() || '');
                    lecturersArr.push($(this).find('.lecturer-input').val() || '');
                });

                var payload = {
                    date: date,
                    subject_ids: subject_ids,
                    durations: durations,
                    times: times,
                    end_times: end_times,
                    classrooms: classroomsArr,
                    lecturers: lecturersArr,
                    location: isCertificate ? ($('#certificate_location').val() || '') : ($('#degree_location').val() || ''),
                    course_id: isCertificate ? ($('#certificate_course').val() || '') : ($('#degree_course').val() || ''),
                    intake_id: isCertificate ? ($('#certificate_intake').val() || '') : ($('#degree_intake').val() || ''),
                    semester: isCertificate ? '1' : ($('#degree_semester').val() || ''),
                    specialization: isCertificate ? '' : ($('#degree_specialization').val() || '')
                };

                $('#assignSubjectBtn').prop('disabled', true).text('Assigning...');

                $.ajax({
                    url: '/timetable/assign-subjects',
                    type: 'POST',
                    data: JSON.stringify(payload),
                    contentType: 'application/json',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '' },
                    success: function (res) {
                        $('#assignSubjectBtn').prop('disabled', false).text('Assign Subjects');
                        $('#subjectSelectionModal').modal('hide');

                        // Show success notification
                        var subjectCount = subject_ids.length;
                        showTimetableToast('Success', subjectCount + ' timetable entry/entries have been saved successfully.', 'success');

                        // delay reload to allow modal to fully close and calendar to be ready
                        setTimeout(function() {
                            // reload events from server so week/day views show timed events correctly
                            if (isCertificate) {
                                $('#certificate_showTimetableBtn').trigger('click');
                            } else {
                                $('#showTimetableBtn').trigger('click');
                            }
                        }, 500);
                    },
                    error: function (xhr) {
                        $('#assignSubjectBtn').prop('disabled', false).text('Assign Subjects');
                        var msg = 'Failed to assign subjects.';
                        try {
                            var json = xhr.responseJSON || JSON.parse(xhr.responseText || '{}');
                            if (json && json.message) msg = json.message;
                        } catch (e) {}
                        alert(msg);
                        console.error('Assign error:', xhr);
                    }
                });
            });

            // delete from event details modal
            $('#ev_delete_btn').on('click', function () {
                var id = $('#ev_id').val();
                if (!id) { alert('No event id'); return; }
                if (!confirm('Delete this timetable event?')) return;
                $(this).prop('disabled', true).text('Deleting...');

                $.ajax({
                    url: '/timetable/delete-event',
                    type: 'POST',
                    data: { id: id },
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') || '' },
                    success: function (res) {
                        $('#eventDetailsModal').modal('hide');
                        $('#ev_delete_btn').prop('disabled', false).text('Delete');

                        // remove event from degree calendar client-side
                        try {
                            var cal = window.__nebulaCalendar;
                            if (cal) {
                                var evObj = cal.getEventById(id);
                                if (evObj) evObj.remove();
                            }
                        } catch (e) { console.warn('Error removing event from degree calendar', e); }

                        // remove event from certificate calendar client-side (if present)
                        try {
                            var ccal = window.__nebulaCertificateCalendar;
                            if (ccal) {
                                var evc = ccal.getEventById(id);
                                if (evc) evc.remove();
                            }
                        } catch (e) { console.warn('Error removing event from certificate calendar', e); }

                        // update local cached arrays so table render uses updated data
                        try {
                            latestFcEvents = (latestFcEvents || []).filter(function(it){ return String(it.id) !== String(id); });
                            latestCertificateFcEvents = (latestCertificateFcEvents || []).filter(function(it){ return String(it.id) !== String(id); });
                        } catch (e) { /* ignore */ }

                        // if the table view is visible, re-render current period only (fast)
                        try {
                            if ($('#tableViewSection').length && $('#tableViewSection').is(':visible')) {
                                var sel = $('#tablePeriod').val();
                                var dtype = $('#tablePeriod option:selected').data('type') || $('#tableViewType').val() || 'week';
                                if (sel) {
                                    if (dtype === 'week') renderWeekTable(parseInt(sel, 10));
                                    else renderMonthTable(parseInt(sel, 10));
                                }
                            }
                        } catch (e) { console.warn('Failed to re-render table view after delete', e); }
                    },
                    error: function (xhr) {
                        $('#eventDetailsModal').modal('hide');
                        $('#ev_delete_btn').prop('disabled', false).text('Delete');
                        alert('Delete failed');
                    }
                });
            });
            // === Certificate calendar & showTimetable handler ===
            var latestCertificateFcEvents = [];
            var latestCertificateEventsRaw = [];
            var certificateCalendarEl = document.getElementById('certificate_calendar');
            if (certificateCalendarEl) {
                var certificateCalendar = new FullCalendar.Calendar(certificateCalendarEl, {
                    initialView: 'timeGridWeek',
                    headerToolbar: { left: 'prev,next today', center: 'title', right: 'timeGridWeek,timeGridDay,dayGridMonth' },
                    height: 'auto',
                    handleWindowResize: true,
                    selectable: true,
                    navLinks: true,
                    eventTimeFormat: { hour: '2-digit', minute: '2-digit', hour12: false },
                    events: [],
                    select: function(selectionInfo) {
                        var startDate = moment(selectionInfo.start).format('YYYY-MM-DD');
                        var startTime = moment(selectionInfo.start).format('HH:mm');
                        $('#selectedTimetableMode').val('certificate');
                        $('#selectedDate').val(startDate);
                        $('#selected_date_display').val(startDate);
                        $('#degree_time_0').val(startTime);
                        $('#degree_duration_0').val('');
                        $('#degree_subject_0').val('');
                        loadCertificateSubjects();
                        $('#subjectSelectionModal').modal('show');
                        certificateCalendar.unselect();
                    },
                    dateClick: function(info) {
                        var d = moment(info.date).format('YYYY-MM-DD');
                        var t = moment(info.date).format('HH:mm');
                        $('#selectedTimetableMode').val('certificate');
                        $('#selectedDate').val(d);
                        $('#selected_date_display').val(d);
                        $('#degree_time_0').val(t);
                        $('#degree_duration_0').val('');
                        $('#degree_subject_0').val('');
                        loadCertificateSubjects();
                        $('#subjectSelectionModal').modal('show');
                    },
                    eventOverlap: true
                });
                window.__nebulaCertificateCalendar = certificateCalendar;
                certificateCalendar.render();
            }

            $('#certificate_showTimetableBtn').on('click', function (event, options) {
                options = options || {};
                var location = $('#certificate_location').val();
                var course_id = $('#certificate_course').val();
                var intake_id = $('#certificate_intake').val();
                var start_date = $('#certificate_start_date').val();
                var end_date = $('#certificate_end_date').val();

                // Validate required fields
                if (!location || !course_id || !intake_id || !start_date || !end_date) {
                    alert('Please select Location, Course, and Intake');
                    return;
                }

                var data = {
                    location: location,
                    course_id: course_id,
                    intake_id: intake_id,
                    start_date: start_date,
                    end_date: end_date
                };

                console.log("Certificate Timetable data being sent:", data);

                applyCalendarDateRange(window.__nebulaCertificateCalendar, start_date, end_date);

                $.ajax({
                    url: timetableEventsUrl,
                    type: 'GET',
                    data: data,
                    success: function (response) {
                        var eventsArray = [];
                        if (Array.isArray(response)) eventsArray = response;
                        else if (response && Array.isArray(response.events)) eventsArray = response.events;
                        else if (response && response.data && Array.isArray(response.data.events)) eventsArray = response.data.events;
                        else if (response && response.data && Array.isArray(response.data)) eventsArray = response.data;

                        // show certificate calendar section (must exist in DOM)
                        try { $('#certificateTimetableSection').show(); } catch (e) { /* ignore if not present */ }
                        setTimeout(function () {
                            if (window.__nebulaCertificateCalendar) {
                                applyCalendarDateRange(window.__nebulaCertificateCalendar, start_date, end_date);
                                window.__nebulaCertificateCalendar.render();
                            }
                        }, 40);

                        try { if (window.__nebulaCertificateCalendar) { try { window.__nebulaCertificateCalendar.removeAllEvents(); } catch(e){} } } catch(e){}

                        if (!eventsArray || !eventsArray.length) {
                            console.info('No events returned for certificate timetable. Showing empty calendar.');
                            latestCertificateEventsRaw = [];
                            latestCertificateFcEvents = [];
                            $('#certificateTimetableSection').show();
                            showTimetableEmptyNotice('certificateTimetableSection', 'No timetable has been created for these filters yet. Click a date slot to add your first timetable entry.');
                            if (window.__nebulaCertificateCalendar) {
                                try {
                                    applyCalendarDateRange(window.__nebulaCertificateCalendar, start_date, end_date);
                                    window.__nebulaCertificateCalendar.render();
                                } catch (e) { console.warn('Certificate calendar render failed', e); }
                            }
                            if (options.autoOpenIfEmpty) {
                                openNewTimetablePopup('certificate');
                            } else {
                                refreshCalendarsSize();
                            }
                            return;
                        }

                        var fcEvents = [];
                        eventsArray.forEach(function (e, idx) {
                            var title = e.module_name || e.subject_name || e.title || 'Class';
                            var datePart = e.date || e.day || '';
                            var startTime = (e.time || '').toString().trim();
                            var endTime = (e.end_time || '').toString().trim();

                            if (!endTime && e.duration) {
                                var mStartTmp = moment(startTime, ['HH:mm:ss','HH:mm','h:mm A']);
                                if (mStartTmp.isValid()) {
                                    endTime = mStartTmp.clone().add(parseInt(e.duration,10) || 0, 'minutes').format('HH:mm');
                                } else {
                                    endTime = startTime;
                                }
                            }

                            if (!datePart || !startTime) return;

                            var mStart = moment(datePart + ' ' + startTime, ['YYYY-MM-DD HH:mm:ss','YYYY-MM-DD HH:mm','YYYY-MM-DD h:mm A','YYYY-MM-DD H:mm']);
                            var mEnd = endTime ? moment(datePart + ' ' + endTime, ['YYYY-MM-DD HH:mm:ss','YYYY-MM-DD HH:mm','YYYY-MM-DD h:mm A','YYYY-MM-DD H:mm']) : mStart.clone().add(1,'minutes');
                            if (!mStart.isValid() || !mEnd.isValid()) return;

                            fcEvents.push({
                                id: (e.id !== undefined && e.id !== null) ? e.id : idx,
                                title: title,
                                start: mStart.toDate(),
                                end: mEnd.toDate(),
                                allDay: false,
                                extendedProps: e,
                                overlap: true
                            });
                        });

                        hideTimetableEmptyNotice('certificateTimetableSection');

                        latestCertificateEventsRaw = eventsArray;
                        latestCertificateFcEvents = fcEvents;

                        try {
                            if (window.__nebulaCertificateCalendar) {
                                try { window.__nebulaCertificateCalendar.removeAllEvents(); } catch(e){}
                                window.__nebulaCertificateCalendar.addEventSource(fcEvents);
                                window.__nebulaCertificateCalendar.refetchEvents();
                            }
                        } catch (ex) { console.warn('Failed to add certificate events', ex); }

                        // show download buttons once certificate timetable is loaded
                        if ($('#certificate_intake').val()) {
                            setDownloadButtonsVisible('#certificate_download_buttons', true);
                        }
                    },
                    error: function () {
                        alert('Error occurred while fetching certificate timetable.');
                    }
                });
            });

            // Copy certificate filters into PDF modal for simple PDF generation
            $('#certificate_downloadPdfBtn').on('click', function () {
                $('#pdf_location').val($('#certificate_location').val() || '');
                $('#pdf_course').empty().append('<option value="">All</option>');
                $('#certificate_course option').each(function () {
                    var v = $(this).val();
                    var t = $(this).text();
                    if (v) $('#pdf_course').append('<option value="' + v + '">' + t + '</option>');
                });
                $('#pdf_course').val($('#certificate_course').val() || '');
                $('#pdf_from').val($('#certificate_start_date').val() || '');
                $('#pdf_to').val($('#certificate_end_date').val() || '');
                $('#downloadPdfModal').modal('show');
            });

            // JS fallback for modal close buttons: some projects use Bootstrap 5 (data-bs-dismiss)
            // while others rely on data-dismiss; ensure both work by hooking clicks.
            $(document).on('click', '[data-bs-dismiss="modal"], [data-dismiss="modal"]', function (e) {
                var $btn = $(this);
                // find closest modal
                var $modal = $btn.closest('.modal');
                if ($modal.length) {
                    try {
                        // try Bootstrap 5 modal hide via JS
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            var modalInstance = bootstrap.Modal.getInstance($modal[0]);
                            if (!modalInstance) modalInstance = new bootstrap.Modal($modal[0]);
                            modalInstance.hide();
                        } else {
                            $modal.hide();
                            $modal.removeClass('show');
                            $('.modal-backdrop').remove();
                        }
                    } catch (ex) {
                        // fallback jQuery hide
                        $modal.hide();
                        $modal.removeClass('show');
                        $('.modal-backdrop').remove();
                    }
                }
            });

        });
    </script>
@endpush
@endsection
