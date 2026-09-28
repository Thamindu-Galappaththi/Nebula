@extends('inc.app')
@section('title', 'NEBULA | Special Approval List')

@section('content')
<style nonce="{{ $cspNonce }}">
    .special-approval-page,
    .special-approval-page .card,
    .special-approval-page .card-body {
        min-width: 0;
        max-width: 100%;
        overflow: visible;
        height: auto;
    }
    body:has(.special-approval-page) .body-wrapper > .container-fluid {
        overflow: visible;
    }
    .special-approval-page .tab-content > .tab-pane:not(.active) {
        display: none !important;
        height: 0;
        overflow: hidden;
    }
    .special-approval-tabs {
        flex-wrap: wrap;
        overflow: hidden;
        row-gap: 0.25rem;
    }
    .special-approval-tabs .nav-link {
        border: none;
        border-bottom: 3px solid transparent;
        color: #6c757d;
        font-weight: 500;
        padding: 12px 16px;
    }
    .special-approval-tabs .nav-link:hover {
        border-color: #dee2e6;
        color: #495057;
    }
    .special-approval-tabs .nav-link.active {
        border-bottom-color: #0d6efd;
        color: #0d6efd;
        background-color: transparent;
    }
    .special-approval-table th,
    .special-approval-table td {
        word-break: break-word;
        vertical-align: middle;
    }
    .special-approval-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }
    .special-approval-toast {
        max-width: min(360px, calc(100vw - 1.5rem));
    }
    .status-badge {
        font-size: .75rem;
        padding: 4px 8px;
        white-space: normal;
    }
    @media (max-width: 767.98px) {
        .special-approval-page h2 {
            font-size: 1.25rem;
        }
        .special-approval-page .card-body {
            padding: 1rem 0.75rem;
        }
        .special-approval-page .form-control,
        .special-approval-page .form-select {
            font-size: 16px;
        }
        .special-approval-tabs .nav-link {
            width: 100%;
            text-align: left;
        }
        .special-approval-table thead {
            display: none;
        }
        .special-approval-table,
        .special-approval-table tbody,
        .special-approval-table tr,
        .special-approval-table td {
            display: block;
            width: 100%;
        }
        .special-approval-table tbody tr[data-row] {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 8px 12px 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }
        .special-approval-table td {
            border: 0;
            padding: 0.45rem 0;
        }
        .special-approval-table td[data-label]::before {
            content: attr(data-label);
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .special-approval-actions,
        .special-approval-actions .btn {
            width: 100%;
        }
        .special-approval-page .modal-footer .btn {
            width: 100%;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 special-approval-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Special Approval List</h2>
            <hr>

            <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3 special-approval-toast" aria-live="polite" aria-atomic="true" style="z-index: 1090;"></div>

            <ul class="nav nav-tabs mb-3 special-approval-tabs" id="specialApprovalTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="student-registration-tab" data-bs-toggle="tab" data-bs-target="#student-registration" type="button" role="tab" aria-controls="student-registration" aria-selected="true">
                        <i class="ti ti-user me-2"></i>Student Registration
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="semterm-tab" data-bs-toggle="tab" data-bs-target="#semterm" type="button" role="tab" aria-controls="semterm" aria-selected="false">
                        <i class="ti ti-rotate-2 me-2"></i>Semester Register Termination
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="specialApprovalTabContent">
                <div class="tab-pane fade show active" id="student-registration" role="tabpanel" aria-labelledby="student-registration-tab">
                    <div class="alert alert-info">
                        <i class="ti ti-info-circle me-2"></i>
                        <strong>Student Registration Approvals</strong>
                        <p class="mb-0 mt-2">Review and approve student registration requests that require special approval.</p>
                    </div>

                    <ul class="nav nav-tabs special-approval-tabs" id="studentSubTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="student-pending-tab" data-bs-toggle="tab" data-bs-target="#student-pending" type="button" role="tab">Pending</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="student-rejected-tab" data-bs-toggle="tab" data-bs-target="#student-rejected" type="button" role="tab">Rejected</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="studentSubTabContent">
                        <div class="tab-pane fade show active" id="student-pending" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered special-approval-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Registration Number</th>
                                            <th>Student Name</th>
                                            <th>Course</th>
                                            <th>Document</th>
                                            <th>Remarks</th>
                                            <th>DGM Comment</th>
                                            <th>Approval Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="specialApprovalTableBody">
                                        <tr><td colspan="8" class="text-center text-muted">Loading…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="student-rejected" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered special-approval-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Registration Number</th>
                                            <th>Student Name</th>
                                            <th>Course</th>
                                            <th>Intake</th>
                                            <th>Reason</th>
                                            <th>Rejected At</th>
                                        </tr>
                                    </thead>
                                    <tbody id="rejectedTableBody">
                                        <tr><td colspan="6" class="text-center text-muted">Loading…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="semterm" role="tabpanel" aria-labelledby="semterm-tab">
                    <div class="alert alert-warning">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <strong>Terminated → Re-Registration Requests</strong>
                        <p class="mb-0 mt-2">Review requests from terminated students who seek re-registration for a semester.</p>
                    </div>

                    <ul class="nav nav-tabs special-approval-tabs" id="semtermSubTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="semterm-pending-tab" data-bs-toggle="tab" data-bs-target="#semterm-pending" type="button" role="tab">Pending</button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="semterm-rejected-tab" data-bs-toggle="tab" data-bs-target="#semterm-rejected" type="button" role="tab">Rejected</button>
                        </li>
                    </ul>

                    <div class="tab-content pt-3" id="semtermSubTabContent">
                        <div class="tab-pane fade show active" id="semterm-pending" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered special-approval-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Course</th>
                                            <th>Intake</th>
                                            <th>Semester</th>
                                            <th>Current Status</th>
                                            <th>Reason</th>
                                            <th>Requested At</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="semtermTableBody">
                                        <tr><td colspan="9" class="text-center text-muted">Loading…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="semterm-rejected" role="tabpanel">
                            <div class="table-responsive">
                                <table class="table table-bordered special-approval-table">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Student ID</th>
                                            <th>Student Name</th>
                                            <th>Course</th>
                                            <th>Intake</th>
                                            <th>Semester</th>
                                            <th>Reason</th>
                                            <th>Rejected At</th>
                                        </tr>
                                    </thead>
                                    <tbody id="semtermRejectedTableBody">
                                        <tr><td colspan="7" class="text-center text-muted">Loading…</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editCommentModal" tabindex="-1" aria-labelledby="editCommentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editCommentModalLabel">Edit DGM Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editCommentForm">
                    <input type="hidden" id="editCommentRegistrationId">
                    <div class="mb-3">
                        <label for="editCommentText" class="form-label">DGM Comment</label>
                        <textarea class="form-control" id="editCommentText" rows="4" maxlength="1000" placeholder="Enter your comment for this special approval request..."></textarea>
                        <small class="text-muted">Maximum 1000 characters</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveDgmCommentBtn">Save Comment</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Re-register Reason</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body"><p id="viewReasonText" class="mb-0"></p></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="approveModal" tabindex="-1" aria-labelledby="approveModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <form id="approveForm" class="modal-content" enctype="multipart/form-data">
            <div class="modal-header">
                <h5 class="modal-title" id="approveModalLabel">Special Approval (DGM) Required</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="approve_registration_id" name="registration_id">
                <div class="mb-3">
                    <label class="form-label" for="approve_reason">Reason</label>
                    <textarea id="approve_reason" name="reason" class="form-control" rows="4" placeholder="Reason for approving this registration (optional)"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="approve_file">Attachment (optional)</label>
                    <input type="file" id="approve_file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png,.webp">
                    <small class="text-muted">Attach supporting document (max 5MB).</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Attach to Request</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <form id="rejectForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Reject Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reject_registration_id" name="registration_id">
                <div class="mb-3">
                    <label class="form-label" for="reject_reason">Reason <span class="text-danger">*</span></label>
                    <textarea id="reject_reason" name="reason" class="form-control" rows="4" placeholder="Why is this registration being rejected?" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="semtermRejectModal" tabindex="-1" aria-labelledby="semtermRejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-sm-down">
        <form id="semtermRejectForm" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="semtermRejectModalLabel">Reject Re-Registration Request</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="semterm_reject_student_id">
                <input type="hidden" id="semterm_reject_intake_id">
                <input type="hidden" id="semterm_reject_semester_id">
                <div class="mb-3">
                    <label class="form-label" for="semterm_reject_reason">Reason <span class="text-danger">*</span></label>
                    <textarea id="semterm_reject_reason" class="form-control" rows="4" placeholder="Why is this re-registration being rejected?" required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Reject</button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
function escapeHtml(text) {
    const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
    return String(text ?? '').replace(/[&<>"']/g, m => map[m]);
}

function truncateText(text, max) {
    const value = String(text ?? '');
    return value.length > max ? value.substring(0, max) + '…' : value;
}

function showToast(title, message, type) {
    const container = document.getElementById('toastContainer');
    if (!container) return;
    container.innerHTML = '';
    const normalized = (type || '').includes('success') ? 'success' : ((type || '').includes('warning') ? 'warning' : 'error');
    const toast = document.createElement('div');
    toast.className = 'toast show';
    toast.setAttribute('role', 'alert');
    const header = document.createElement('div');
    header.className = 'toast-header bg-' + (normalized === 'success' ? 'success' : (normalized === 'warning' ? 'warning' : 'danger')) + (normalized === 'warning' ? ' text-dark' : ' text-white');
    const strong = document.createElement('strong');
    strong.className = 'me-auto';
    strong.textContent = title || (normalized === 'success' ? 'Success' : (normalized === 'warning' ? 'Warning' : 'Error'));
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'btn-close' + (normalized === 'warning' ? '' : ' btn-close-white');
    closeBtn.setAttribute('data-bs-dismiss', 'toast');
    header.appendChild(strong);
    header.appendChild(closeBtn);
    const body = document.createElement('div');
    body.className = 'toast-body';
    body.textContent = message || '';
    toast.appendChild(header);
    toast.appendChild(body);
    container.appendChild(toast);
    bootstrap.Toast.getOrCreateInstance(toast, { delay: 4000 }).show();
    toast.addEventListener('hidden.bs.toast', () => toast.remove());
}

function emptyRow(colspan, message) {
    return '<tr><td colspan="' + colspan + '" class="text-center text-muted">' + escapeHtml(message) + '</td></tr>';
}

function parseJson(response) {
    return response.json().then(data => {
        if (!response.ok || data.success === false) {
            throw new Error(data.message || 'Request failed.');
        }
        return data;
    }).catch(error => {
        if (error instanceof Error && error.message && error.message !== 'Unexpected end of JSON input') {
            throw error;
        }
        throw new Error('Request failed.');
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('specialApprovalTableBody');
    const rejectedTableBody = document.getElementById('rejectedTableBody');
    const semtermTableBody = document.getElementById('semtermTableBody');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    const approveModalEl = document.getElementById('approveModal');
    const approveModal = approveModalEl ? new bootstrap.Modal(approveModalEl) : null;
    const approveForm = document.getElementById('approveForm');
    const approveRegIdEl = document.getElementById('approve_registration_id');
    const approveReasonEl = document.getElementById('approve_reason');
    const approveFileEl = document.getElementById('approve_file');

    const rejectModalEl = document.getElementById('rejectModal');
    const rejectModal = rejectModalEl ? new bootstrap.Modal(rejectModalEl) : null;
    const rejectForm = document.getElementById('rejectForm');
    const rejectRegIdEl = document.getElementById('reject_registration_id');
    const rejectReasonEl = document.getElementById('reject_reason');

    document.getElementById('student-registration-tab')?.addEventListener('shown.bs.tab', loadStudentRegistrationData);
    document.getElementById('semterm-tab')?.addEventListener('shown.bs.tab', loadSemTermRequests);
    document.getElementById('student-pending-tab')?.addEventListener('shown.bs.tab', loadStudentRegistrationData);
    document.getElementById('student-rejected-tab')?.addEventListener('shown.bs.tab', loadRejectedRegistrations);
    document.getElementById('semterm-pending-tab')?.addEventListener('shown.bs.tab', loadSemTermRequests);
    document.getElementById('semterm-rejected-tab')?.addEventListener('shown.bs.tab', loadSemTermRejected);

    loadStudentRegistrationData();

    function loadStudentRegistrationData() {
        tableBody.innerHTML = emptyRow(8, 'Loading…');
        fetch('/get-special-approval-list', { headers: { 'Accept': 'application/json' } })
            .then(parseJson)
            .then(data => {
                if (data.students && data.students.length) {
                    renderSpecialApprovalTable(data.students);
                } else {
                    tableBody.innerHTML = emptyRow(8, 'No pending requests.');
                }
            })
            .catch(() => {
                tableBody.innerHTML = emptyRow(8, 'Error loading data.');
            });
    }

    function renderSpecialApprovalTable(students) {
        tableBody.innerHTML = '';
        students.forEach(st => {
            const remarks = st.remarks || 'No remarks';
            const dgm = st.dgm_comment || 'No DGM comment';
            const filename = st.document_path ? String(st.document_path).split('/').pop() : '';
            const docHtml = filename
                ? '<a href="/special-approval-document/' + encodeURIComponent(filename) + '" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm">View Document</a>'
                : '<span class="text-muted">No document</span>';
            const approved = st.approval_status === 'Approved by manager';
            const statusHtml = approved
                ? '<span class="badge bg-success status-badge">Approved</span>'
                : '<span class="badge bg-warning status-badge">Pending</span>';
            const actionHtml = approved
                ? '<span class="badge bg-success status-badge">Approved</span>'
                : '<div class="special-approval-actions">' +
                    '<button type="button" class="btn btn-success btn-sm approve-btn" data-registration-id="' + escapeHtml(String(st.registration_id)) + '">Register</button>' +
                    '<button type="button" class="btn btn-outline-danger btn-sm reject-btn" data-registration-id="' + escapeHtml(String(st.registration_id)) + '">Reject</button>' +
                  '</div>';

            tableBody.insertAdjacentHTML('beforeend',
                '<tr data-row>' +
                    '<td data-label="Registration Number">' + escapeHtml(st.registration_number || '') + '</td>' +
                    '<td data-label="Student Name">' + escapeHtml(st.name || '') + '</td>' +
                    '<td data-label="Course">' + escapeHtml(st.course_name || '') + '</td>' +
                    '<td data-label="Document">' + docHtml + '</td>' +
                    '<td data-label="Remarks" title="' + escapeHtml(remarks) + '">' + escapeHtml(truncateText(remarks, 50)) + '</td>' +
                    '<td data-label="DGM Comment">' +
                        '<div class="special-approval-actions">' +
                            '<span title="' + escapeHtml(dgm) + '">' + escapeHtml(truncateText(dgm, 50)) + '</span>' +
                            '<button type="button" class="btn btn-sm btn-outline-primary edit-comment-btn" data-registration-id="' + escapeHtml(String(st.registration_id)) + '" data-current-comment="' + escapeHtml(st.dgm_comment || '') + '"><i class="ti ti-edit"></i></button>' +
                        '</div>' +
                    '</td>' +
                    '<td data-label="Approval Status">' + statusHtml + '</td>' +
                    '<td data-label="Action">' + actionHtml + '</td>' +
                '</tr>'
            );
        });
    }

    tableBody.addEventListener('click', function(e) {
        const approveBtn = e.target.closest('.approve-btn');
        if (approveBtn && approveRegIdEl && approveModal) {
            approveRegIdEl.value = approveBtn.getAttribute('data-registration-id') || '';
            approveReasonEl.value = '';
            if (approveFileEl) approveFileEl.value = '';
            approveModal.show();
            return;
        }

        const editBtn = e.target.closest('.edit-comment-btn');
        if (editBtn) {
            document.getElementById('editCommentRegistrationId').value = editBtn.getAttribute('data-registration-id') || '';
            document.getElementById('editCommentText').value = editBtn.getAttribute('data-current-comment') || '';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editCommentModal')).show();
            return;
        }

        const rejectBtn = e.target.closest('.reject-btn');
        if (rejectBtn && rejectRegIdEl && rejectModal) {
            rejectRegIdEl.value = rejectBtn.getAttribute('data-registration-id') || '';
            rejectReasonEl.value = '';
            rejectModal.show();
        }
    });

    if (approveForm) {
        approveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const btn = approveForm.querySelector('button[type="submit"]');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Saving…';
            fetch('{{ route('special.approval.approve') }}', {
                method: 'POST',
                body: new FormData(approveForm),
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            })
            .then(parseJson)
            .then(d => {
                showToast('Success', d.message || 'Approved successfully', 'success');
                if (approveModal) approveModal.hide();
                loadStudentRegistrationData();
            })
            .catch(err => showToast('Error', err.message || 'Failed to approve', 'error'))
            .finally(() => { btn.disabled = false; btn.textContent = original; });
        });
    }

    if (rejectForm) {
        rejectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const payload = {
                registration_id: rejectRegIdEl.value,
                reason: rejectReasonEl.value.trim()
            };
            if (!payload.reason) {
                showToast('Warning', 'Please enter a reason.', 'warning');
                return;
            }
            const btn = rejectForm.querySelector('button[type="submit"]');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Rejecting…';
            fetch('{{ route('special.approval.reject') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(parseJson)
            .then(d => {
                showToast('Success', d.message || 'Rejected', 'success');
                if (rejectModal) rejectModal.hide();
                loadStudentRegistrationData();
            })
            .catch(err => showToast('Error', err.message || 'Failed to reject', 'error'))
            .finally(() => { btn.disabled = false; btn.textContent = original; });
        });
    }

    function loadRejectedRegistrations() {
        if (!rejectedTableBody) return;
        rejectedTableBody.innerHTML = emptyRow(6, 'Loading…');
        fetch('{{ route('special.approval.rejected') }}', { headers: { 'Accept': 'application/json' } })
            .then(parseJson)
            .then(d => {
                if (!d.students || d.students.length === 0) {
                    rejectedTableBody.innerHTML = emptyRow(6, 'No rejected registrations.');
                    return;
                }
                rejectedTableBody.innerHTML = '';
                d.students.forEach(st => {
                    const reason = st.reason || '';
                    rejectedTableBody.insertAdjacentHTML('beforeend',
                        '<tr data-row>' +
                            '<td data-label="Registration Number">' + escapeHtml(st.registration_number || '') + '</td>' +
                            '<td data-label="Student Name">' + escapeHtml(st.name || '') + '</td>' +
                            '<td data-label="Course">' + escapeHtml(st.course_name || '') + '</td>' +
                            '<td data-label="Intake">' + escapeHtml(st.intake || '') + '</td>' +
                            '<td data-label="Reason" title="' + escapeHtml(reason) + '">' + escapeHtml(truncateText(reason, 80)) + '</td>' +
                            '<td data-label="Rejected At">' + escapeHtml(st.rejected_at || '') + '</td>' +
                        '</tr>'
                    );
                });
            })
            .catch(() => {
                rejectedTableBody.innerHTML = emptyRow(6, 'Error loading rejected list.');
            });
    }

    document.getElementById('saveDgmCommentBtn').addEventListener('click', function() {
        const rid = document.getElementById('editCommentRegistrationId').value;
        const txt = document.getElementById('editCommentText').value;
        if (!rid) {
            showToast('Error', 'Registration ID is required.', 'error');
            return;
        }
        const btn = this;
        btn.disabled = true;
        btn.textContent = 'Saving...';
        fetch('/update-dgm-comment', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ registration_id: rid, dgm_comment: txt })
        })
        .then(parseJson)
        .then(d => {
            const modal = bootstrap.Modal.getInstance(document.getElementById('editCommentModal'));
            if (modal) modal.hide();
            showToast('Success', d.message || 'Comment updated.', 'success');
            loadStudentRegistrationData();
        })
        .catch(err => showToast('Error', err.message || 'Failed to update comment.', 'error'))
        .finally(() => { btn.disabled = false; btn.textContent = 'Save Comment'; });
    });

    function loadSemTermRequests() {
        semtermTableBody.innerHTML = emptyRow(9, 'Loading…');
        fetch('/semester-registration/terminated-requests?status=pending', { headers: { 'Accept': 'application/json' } })
            .then(parseJson)
            .then(d => {
                if (!d.requests || !d.requests.length) {
                    semtermTableBody.innerHTML = emptyRow(9, 'No requests found.');
                    return;
                }
                renderSemTermTable(d.requests);
            })
            .catch(() => {
                semtermTableBody.innerHTML = emptyRow(9, 'Error loading requests.');
            });
    }

    function renderSemTermTable(rows) {
        semtermTableBody.innerHTML = '';
        rows.forEach(r => {
            const statusClass = r.current_status === 'terminated' ? 'bg-danger' : 'bg-secondary';
            semtermTableBody.insertAdjacentHTML('beforeend',
                '<tr data-row data-request-id="' + escapeHtml(String(r.id || '')) + '" data-student-id="' + escapeHtml(String(r.student_id || '')) + '" data-intake-id="' + escapeHtml(String(r.intake_id || '')) + '" data-semester-id="' + escapeHtml(String(r.semester_id || '')) + '">' +
                    '<td data-label="Student ID">' + escapeHtml(r.student_id || '') + '</td>' +
                    '<td data-label="Student Name">' + escapeHtml(r.student_name || '') + '</td>' +
                    '<td data-label="Course">' + escapeHtml(r.course_name || '') + '</td>' +
                    '<td data-label="Intake">' + escapeHtml(r.intake || '') + '</td>' +
                    '<td data-label="Semester">' + escapeHtml(r.semester_name || '') + '</td>' +
                    '<td data-label="Current Status"><span class="badge status-badge ' + statusClass + '">' + escapeHtml(r.current_status || '') + '</span></td>' +
                    '<td data-label="Reason"><button type="button" class="btn btn-outline-info btn-sm view-reason-btn">View</button></td>' +
                    '<td data-label="Requested At">' + escapeHtml(r.requested_at || '') + '</td>' +
                    '<td data-label="Action"><div class="special-approval-actions">' +
                        '<button type="button" class="btn btn-success btn-sm sem-approve-btn" data-id="' + escapeHtml(String(r.id || '')) + '">Approve</button>' +
                        '<button type="button" class="btn btn-outline-danger btn-sm sem-reject-btn" data-id="' + escapeHtml(String(r.id || '')) + '">Reject</button>' +
                    '</div></td>' +
                '</tr>'
            );
            const row = semtermTableBody.lastElementChild;
            const viewBtn = row.querySelector('.view-reason-btn');
            if (viewBtn) {
                viewBtn.dataset.reason = r.reason || '';
            }
        });
    }

    semtermTableBody.addEventListener('click', function(e) {
        const viewBtn = e.target.closest('.view-reason-btn');
        if (viewBtn) {
            document.getElementById('viewReasonText').textContent = viewBtn.dataset.reason || '—';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('viewReasonModal')).show();
            return;
        }

        const approve = e.target.closest('.sem-approve-btn');
        if (approve) {
            const id = approve.getAttribute('data-id');
            if (!confirm('Approve this re-registration?')) return;
            fetch('/semester-registration/approve-reregister', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ request_id: id })
            })
            .then(parseJson)
            .then(d => {
                showToast('Success', d.message || 'Updated successfully.', 'success');
                loadSemTermRequests();
            })
            .catch(err => showToast('Error', err.message || 'Failed to update.', 'error'));
            return;
        }

        const reject = e.target.closest('.sem-reject-btn');
        if (reject) {
            const row = reject.closest('tr');
            const modalEl = document.getElementById('semtermRejectModal');
            modalEl.querySelector('#semterm_reject_student_id').value = row?.dataset.studentId || '';
            modalEl.querySelector('#semterm_reject_intake_id').value = row?.dataset.intakeId || '';
            modalEl.querySelector('#semterm_reject_semester_id').value = row?.dataset.semesterId || '';
            modalEl.querySelector('#semterm_reject_reason').value = '';
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
        }
    });

    function loadSemTermRejected() {
        const rejectedBody = document.getElementById('semtermRejectedTableBody');
        if (!rejectedBody) return;
        rejectedBody.innerHTML = emptyRow(7, 'Loading…');
        fetch('/semester-registration/terminated-requests?status=rejected', { headers: { 'Accept': 'application/json' } })
            .then(parseJson)
            .then(d => {
                if (!d.requests || !d.requests.length) {
                    rejectedBody.innerHTML = emptyRow(7, 'No rejected requests.');
                    return;
                }
                rejectedBody.innerHTML = '';
                d.requests.forEach(r => {
                    const reason = r.reason || '';
                    rejectedBody.insertAdjacentHTML('beforeend',
                        '<tr data-row>' +
                            '<td data-label="Student ID">' + escapeHtml(r.student_id || '') + '</td>' +
                            '<td data-label="Student Name">' + escapeHtml(r.student_name || '') + '</td>' +
                            '<td data-label="Course">' + escapeHtml(r.course_name || '') + '</td>' +
                            '<td data-label="Intake">' + escapeHtml(r.intake || '') + '</td>' +
                            '<td data-label="Semester">' + escapeHtml(r.semester_name || '') + '</td>' +
                            '<td data-label="Reason" title="' + escapeHtml(reason) + '">' + escapeHtml(truncateText(reason, 80)) + '</td>' +
                            '<td data-label="Rejected At">' + escapeHtml(r.rejected_at || r.requested_at || '') + '</td>' +
                        '</tr>'
                    );
                });
            })
            .catch(() => {
                rejectedBody.innerHTML = emptyRow(7, 'Error loading rejected requests.');
            });
    }

    const semtermRejectForm = document.getElementById('semtermRejectForm');
    if (semtermRejectForm) {
        semtermRejectForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const sid = document.getElementById('semterm_reject_student_id').value;
            const iid = document.getElementById('semterm_reject_intake_id').value;
            const sem = document.getElementById('semterm_reject_semester_id').value;
            const reason = document.getElementById('semterm_reject_reason').value.trim();
            if (!reason) {
                showToast('Warning', 'Please enter a reason.', 'warning');
                return;
            }
            const btn = semtermRejectForm.querySelector('button[type="submit"]');
            const original = btn.textContent;
            btn.disabled = true;
            btn.textContent = 'Rejecting…';
            fetch('/semester-registration/reject-reregister', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
                body: JSON.stringify({ student_id: sid, intake_id: iid, semester_id: sem, comment: reason })
            })
            .then(parseJson)
            .then(d => {
                showToast('Success', d.message || 'Rejected', 'success');
                const m = bootstrap.Modal.getInstance(document.getElementById('semtermRejectModal'));
                if (m) m.hide();
                loadSemTermRequests();
            })
            .catch(err => showToast('Error', err.message || 'Reject failed', 'error'))
            .finally(() => { btn.disabled = false; btn.textContent = original; });
        });
    }
});
</script>
@endpush
