@extends('inc.app')

@section('title', 'NEBULA | Late Fee Approval')

@section('content')
<style nonce="{{ $cspNonce }}">
    .late-fee-approval-page,
    .late-fee-approval-page .card,
    .late-fee-approval-page .card-body {
        min-width: 0;
        max-width: 100%;
        overflow: visible;
        height: auto;
    }
    body:has(.late-fee-approval-page) .body-wrapper > .container-fluid {
        overflow: visible;
    }
    .late-fee-approval-page [class*="col-"] {
        min-width: 0;
    }
    .late-fee-approval-page .form-select,
    .late-fee-approval-page .form-control {
        width: 100%;
        max-width: 100%;
    }
    .late-fee-filter-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: stretch;
    }
    .late-fee-filter-actions .btn {
        flex: 1 1 120px;
        min-width: 0;
    }
    .late-fee-table th,
    .late-fee-table td {
        word-break: break-word;
        vertical-align: middle;
    }
    .late-fee-table th:last-child,
    .late-fee-table td:last-child {
        min-width: 220px;
        width: 240px;
    }
    .late-fee-action-form {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
        min-width: 0;
        width: 100%;
    }
    .late-fee-action-form .form-label {
        margin-bottom: 0.15rem;
        font-size: 0.75rem;
        font-weight: 600;
        color: #64748b;
    }
    .late-fee-action-form input[type="number"] {
        -moz-appearance: textfield;
        appearance: textfield;
    }
    .late-fee-action-form input[type="number"]::-webkit-outer-spin-button,
    .late-fee-action-form input[type="number"]::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .late-fee-toast {
        max-width: min(360px, calc(100vw - 1.5rem));
    }
    @media (max-width: 767.98px) {
        .late-fee-approval-page h2 {
            font-size: 1.25rem;
        }
        .late-fee-approval-page .card-body {
            padding: 1rem 0.75rem;
        }
        .late-fee-approval-page .form-control,
        .late-fee-approval-page .form-select {
            font-size: 16px;
        }
        .late-fee-approval-page .col-form-label {
            text-align: left !important;
            padding-bottom: 0.2rem;
        }
        .late-fee-filter-actions,
        .late-fee-filter-actions .btn {
            width: 100%;
            flex: 1 1 100%;
        }
        .late-fee-global-submit {
            width: 100%;
        }
    }
    @media (max-width: 1199.98px) {
        .late-fee-approval-page .form-control,
        .late-fee-approval-page .form-select {
            font-size: 16px;
        }
        .late-fee-table thead {
            display: none;
        }
        .late-fee-table,
        .late-fee-table tbody,
        .late-fee-table tr,
        .late-fee-table td {
            display: block;
            width: 100%;
        }
        .late-fee-table th:last-child,
        .late-fee-table td:last-child {
            min-width: 0;
            width: 100%;
        }
        .late-fee-table tbody tr[data-row] {
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            margin-bottom: 12px;
            padding: 8px 12px 12px;
            background: #fff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
            text-align: left !important;
        }
        .late-fee-table td {
            border: 0;
            padding: 0.45rem 0;
        }
        .late-fee-table td[data-label]::before {
            content: attr(data-label);
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            margin-bottom: 2px;
            text-transform: uppercase;
            letter-spacing: 0.02em;
        }
        .late-fee-table td[data-label="Action"]::before {
            display: none;
        }
        .late-fee-table .btn,
        .late-fee-action-form .btn {
            width: 100%;
        }
        .late-fee-approval-page .table-responsive {
            overflow: visible;
        }
    }
</style>

<div class="container-fluid px-2 px-md-3 late-fee-approval-page">
    <div class="card">
        <div class="card-body">
            <h2 class="text-center mb-4">Late Fee Approval</h2>
            <hr>

            <div id="toastContainer" class="toast-container position-fixed top-0 end-0 p-3 late-fee-toast" aria-live="polite" aria-atomic="true" style="z-index: 1090;"></div>

            <div class="mb-4">
                <h5 class="mb-3">Select Student & Course</h5>
                <form method="GET" action="{{ route('latefee.approval.index') }}" id="late-fee-form">
                    <div class="mb-3 row mx-0">
                        <label for="student-nic" class="col-md-2 col-form-label fw-bold">Student NIC <span class="text-danger">*</span></label>
                        <div class="col-md-10">
                            <input type="text" id="student-nic" name="student_nic" class="form-control" placeholder="Enter NIC" value="{{ $studentNic ?? '' }}" required>
                        </div>
                    </div>
                    <div class="mb-3 row mx-0">
                        <label for="course_id" class="col-md-2 col-form-label fw-bold">Course <span class="text-danger">*</span></label>
                        <div class="col-md-10">
                            <select id="course_id" name="course_id" class="form-select" required>
                                <option value="">Select a Course</option>
                                @foreach($courses ?? [] as $c)
                                    <option value="{{ $c['course_id'] }}" {{ (string) ($courseId ?? '') === (string) $c['course_id'] ? 'selected' : '' }}>
                                        {{ $c['course_name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="late-fee-filter-actions">
                        <button type="submit" class="btn btn-primary">Load</button>
                        <a href="{{ route('latefee.approval.index') }}" class="btn btn-outline-secondary">Clear</a>
                    </div>
                </form>
            </div>

            @isset($student)
                <div class="alert alert-light border mb-4">
                    <strong>{{ $student->name_with_initials ?: $student->full_name }}</strong>
                    <span class="text-muted"> · NIC {{ $studentNic }}</span>
                </div>
            @endisset

            @isset($error)
                <div class="alert alert-warning">{{ $error }}</div>
            @endisset

            @isset($installments)
                @php
                    $hasOverdue = $installments->contains(fn ($inst) => ($inst->calculated_late_fee ?? 0) > 0 && $inst->status !== 'paid');
                @endphp

                @if($hasOverdue)
                    <div class="mb-4">
                        <h5 class="mb-3">Global Reduction</h5>
                        <p class="text-muted">Apply an approved late-fee amount across overdue unpaid installments, starting with the earliest due date.</p>
                        <form method="POST" action="{{ route('latefee.approve.global', [$student->id_value ?? $studentNic, $courseId]) }}">
                            @csrf
                            <div class="mb-3 row mx-0">
                                <label for="reduction_amount" class="col-md-2 col-form-label fw-bold">Reduction Amount</label>
                                <div class="col-md-10">
                                    <input type="number" step="0.01" min="0.01" name="reduction_amount" id="reduction_amount" class="form-control" required>
                                </div>
                            </div>
                            <div class="mb-3 row mx-0">
                                <label for="approval_note_global" class="col-md-2 col-form-label fw-bold">Approval Note</label>
                                <div class="col-md-10">
                                    <input type="text" name="approval_note" id="approval_note_global" class="form-control" maxlength="1000">
                                </div>
                            </div>
                            <button class="btn btn-success late-fee-global-submit" type="submit">Apply Global Reduction</button>
                        </form>
                    </div>
                @endif

                <div class="mb-2">
                    <h5 class="mb-3">Installment-wise Approval</h5>
                    <div class="table-responsive">
                        <table class="table table-bordered late-fee-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Installment #</th>
                                    <th>Due Date</th>
                                    <th>Final Amount</th>
                                    <th>Calculated Late Fee</th>
                                    <th>Approved Late Fee</th>
                                    <th>Overdue (Calc - Approved)</th>
                                    <th>Approval Note</th>
                                    <th>History</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($installments as $installment)
                                    @php
                                        $calcFee = $installment->calculated_late_fee ?? 0;
                                        $approvedFee = $installment->approved_late_fee ?? 0;
                                        $overdue = max(0, $calcFee - $approvedFee);
                                        $histories = is_array($installment->approval_history)
                                            ? $installment->approval_history
                                            : (json_decode($installment->approval_history ?? '[]', true) ?: []);
                                        $canApprove = $installment->status !== 'paid'
                                            && \Carbon\Carbon::parse($installment->due_date)->isPast()
                                            && $calcFee > 0;
                                    @endphp
                                    <tr data-row>
                                        <td data-label="Installment #">{{ $installment->installment_number }}</td>
                                        <td data-label="Due Date">{{ $installment->formatted_due_date }}</td>
                                        <td data-label="Final Amount">{{ $installment->formatted_amount }}</td>
                                        <td data-label="Calculated Late Fee">LKR {{ number_format($calcFee, 2) }}</td>
                                        <td data-label="Approved Late Fee">
                                            @if($installment->approved_late_fee !== null)
                                                <span class="badge bg-success">LKR {{ number_format($installment->approved_late_fee, 2) }}</span>
                                            @else
                                                <span class="badge bg-secondary">Not approved</span>
                                            @endif
                                        </td>
                                        <td data-label="Overdue">
                                            @if($overdue > 0)
                                                <span class="text-danger fw-bold">LKR {{ number_format($overdue, 2) }}</span>
                                            @else
                                                <span class="text-success fw-bold">LKR 0.00</span>
                                            @endif
                                        </td>
                                        <td data-label="Approval Note">{{ $installment->approval_note ?? '—' }}</td>
                                        <td data-label="History">
                                            <button class="btn btn-sm btn-outline-info" type="button" data-bs-toggle="collapse" data-bs-target="#history-{{ $installment->id }}">
                                                View History
                                            </button>
                                            <div id="history-{{ $installment->id }}" class="collapse mt-2 text-start">
                                                @if(empty($histories))
                                                    <small class="text-muted fst-italic">No history yet</small>
                                                @else
                                                    <ul class="list-group list-group-flush small">
                                                        @foreach($histories as $h)
                                                            <li class="list-group-item py-1">
                                                                <strong>LKR {{ number_format($h['approved_late_fee'] ?? 0, 2) }}</strong>
                                                                ({{ $h['approval_note'] ?? 'No note' }})
                                                                by <span class="fw-semibold">{{ $h['approved_by'] ?? 'System' }}</span>
                                                                <small class="text-muted d-block">on {{ $h['approved_at'] ?? '—' }}</small>
                                                            </li>
                                                        @endforeach
                                                    </ul>
                                                @endif
                                            </div>
                                        </td>
                                        <td data-label="Action">
                                            <form method="POST" action="{{ route('latefee.approve.installment', $installment->id) }}" class="late-fee-action-form">
                                                @csrf
                                                <div>
                                                    <label class="form-label" for="approved-late-fee-{{ $installment->id }}">Approved fee (LKR)</label>
                                                    <input type="number" step="0.01" min="0" max="{{ $calcFee }}" name="approved_late_fee"
                                                        id="approved-late-fee-{{ $installment->id }}"
                                                        class="form-control"
                                                        inputmode="decimal"
                                                        placeholder="0.00"
                                                        value="{{ $installment->approved_late_fee ?? '' }}"
                                                        {{ $canApprove ? '' : 'disabled' }}>
                                                </div>
                                                <div>
                                                    <label class="form-label" for="approval-note-{{ $installment->id }}">Note</label>
                                                    <input type="text" name="approval_note"
                                                        id="approval-note-{{ $installment->id }}"
                                                        class="form-control"
                                                        placeholder="Optional note"
                                                        maxlength="1000"
                                                        value="{{ $installment->approval_note ?? '' }}"
                                                        {{ $canApprove ? '' : 'disabled' }}>
                                                </div>
                                                @if($canApprove)
                                                    <button class="btn btn-primary" type="submit">Approve</button>
                                                @else
                                                    <button class="btn btn-secondary" type="button" disabled
                                                        title="Approval is only allowed for overdue unpaid installments">
                                                        Approve
                                                    </button>
                                                @endif
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr data-row>
                                        <td colspan="9" class="text-center text-muted py-3">
                                            No installments found for this student and course.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endisset
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script nonce="{{ $cspNonce }}">
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

document.addEventListener('DOMContentLoaded', function () {
    const studentNicInput = document.getElementById('student-nic');
    const courseSelect = document.getElementById('course_id');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const prefilledCourseId = @json((string) ($courseId ?? ''));

    function resetCourses(placeholder) {
        courseSelect.innerHTML = '';
        courseSelect.add(new Option(placeholder || 'Select a Course', '', true, true));
        courseSelect.options[0].disabled = true;
    }

    function fetchCoursesForNIC(selectCourseId) {
        const nic = studentNicInput.value.trim();
        if (!nic) {
            resetCourses('Select a Course');
            return;
        }

        fetch(@json(route('latefee.get.courses')), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: JSON.stringify({ student_nic: nic })
        })
        .then(res => res.json())
        .then(data => {
            resetCourses('Select a Course');
            if (data.success && Array.isArray(data.courses) && data.courses.length) {
                data.courses.forEach(c => {
                    const opt = new Option(c.course_name, c.course_id);
                    if (String(c.course_id) === String(selectCourseId || '')) {
                        opt.selected = true;
                    }
                    courseSelect.add(opt);
                });
                courseSelect.options[0].disabled = true;
            } else {
                resetCourses(data.message || 'No courses found');
                showToast('Info', data.message || 'No courses found for this NIC.', 'warning');
            }
        })
        .catch(() => {
            showToast('Error', 'Could not load courses.', 'error');
        });
    }

    studentNicInput.addEventListener('blur', function () {
        fetchCoursesForNIC(courseSelect.value);
    });
    studentNicInput.addEventListener('change', function () {
        fetchCoursesForNIC('');
    });

    document.getElementById('late-fee-form').addEventListener('submit', function (e) {
        if (!studentNicInput.value.trim() || !courseSelect.value) {
            e.preventDefault();
            showToast('Info', 'Enter a NIC and select a course.', 'warning');
        }
    });

    if (studentNicInput.value.trim()) {
        fetchCoursesForNIC(prefilledCourseId);
    } else {
        resetCourses('Select a Course');
    }

    @if(session('success'))
        showToast('Success', @json(session('success')), 'success');
    @endif
    @if(session('error'))
        showToast('Error', @json(session('error')), 'error');
    @endif
    @if($errors->any())
        showToast('Error', @json($errors->first()), 'error');
    @endif
});
</script>
@endpush
