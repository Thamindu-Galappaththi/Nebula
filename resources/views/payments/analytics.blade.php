@extends('inc.app')

@section('title', 'Advanced Payment Analytics')

@section('content')
<div id="payment-analytics" class="container-fluid px-2 px-md-3 mt-4 mb-5">
    {{-- Header --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
        <div>
            <h2 class="text-primary mb-1">📊 Advanced Analytics</h2>
            <p class="text-muted mb-0">Deep dive into payment performance metrics</p>
        </div>
        <a href="{{ route('payment.summary') }}" class="btn btn-outline-secondary analytics-back-btn">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>
    </div>

    <form id="analyticsFiltersForm" method="GET" action="{{ route('payment.analytics') }}" class="row mb-4 g-2 align-items-end">
        <div class="col-md-3">
            <label class="form-label fw-semibold text-dark" for="analyticsMonthFilter">Analytics month</label>
            <input type="month" id="analyticsMonthFilter" name="month" class="form-control analytics-month-filter" value="{{ $startOfMonth->format('Y-m') }}" aria-label="Analytics month">
        </div>
        <div class="col-md-3 analytics-filter-submit">
            <button type="submit" class="btn btn-primary">Apply filters</button>
        </div>
    </form>

    {{-- Summary KPI Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Total Revenue</p>
                            <h4 class="fw-bold mb-0">LKR {{ number_format($totalRevenue ?? 0, 2) }}</h4>
                        </div>
                        <div class="text-primary fs-3">
                            <i class="bi bi-currency-dollar"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Pending Payments</p>
                            <h4 class="fw-bold mb-0">LKR {{ number_format($totalPendingPayments ?? 0, 2) }}</h4>
                        </div>
                        <div class="text-warning fs-3">
                            <i class="bi bi-clock-history"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">Avg Paid Transaction</p>
                            <h4 class="fw-bold mb-0">LKR {{ number_format($averagePaidTransaction ?? 0, 2) }}</h4>
                        </div>
                        <div class="text-success fs-3">
                            <i class="bi bi-graph-up"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="text-muted small mb-1">SLT Recoveries</p>
                            <h4 class="fw-bold mb-0">{{ $totalSltLoanRecoveries ?? 0 }} items</h4>
                            <small class="text-muted">LKR {{ number_format($totalSltRecoveryAmount ?? 0, 2) }}</small>
                        </div>
                        <div class="text-danger fs-3">
                            <i class="bi bi-bank"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">📈 Daily Revenue Trend</h6>
                    <small class="text-muted">Paid revenue by day within selected range.</small>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="revenueChart" height="100"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0">🆕 New Registrations</h6>
                        <small class="text-muted">Current month paid registration-fee records (Update Records)</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a
                            id="newRegistrationsExportLink"
                            class="btn btn-sm btn-outline-primary"
                            data-metric="new_registrations"
                            href="{{ route('payment.analytics.export', ['metric' => 'new_registrations', 'month' => $startOfMonth->format('Y-m'), 'course_id' => $selectedNewRegistrationCourseId]) }}"
                        >
                            <i class="bi bi-file-earmark-pdf"></i> Export audit
                        </a>
                        <span class="badge bg-primary">{{ number_format($newRegistrationsCount ?? 0) }} regs</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4 analytics-kpi-header">
                        <div>
                            <div class="text-muted small mb-1">Selected month paid registration fees</div>
                            <div class="display-6 fw-bold text-primary mb-1 analytics-kpi-amount">LKR {{ number_format($newRegistrationsAmount ?? 0, 2) }}</div>
                            <div class="text-muted small">{{ number_format($newRegistrationsCount ?? 0) }} paid registration-fee records in {{ $startOfMonth->format('F Y') }}</div>
                        </div>
                        <div class="bg-primary bg-opacity-10 text-primary rounded-3 p-3">
                            <i class="bi bi-journal-plus fs-3"></i>
                        </div>
                    </div>

                    <label class="form-label small text-muted" for="newRegistrationCourseFilter">Filter by course</label>
                    <select class="form-select" id="newRegistrationCourseFilter" name="new_registration_course_id" form="analyticsFiltersForm">
                        <option value="">All courses</option>
                        @foreach(($courses ?? []) as $course)
                            <option value="{{ $course->course_id }}" {{ (isset($selectedNewRegistrationCourseId) && $selectedNewRegistrationCourseId == $course->course_id) ? 'selected' : '' }}>{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                    <div class="d-flex justify-content-between align-items-center gap-2 mt-2 analytics-filter-actions">
                        <small class="text-muted">Select one course to recalculate paid registration-fee totals for the selected month.</small>
                        <button type="submit" form="analyticsFiltersForm" class="btn btn-sm btn-outline-primary text-nowrap">Apply</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100 bg-white">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0">📚 Ongoing Courses</h6>
                        <small class="text-muted">Selected-month paid local course-fee records</small>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <a
                            id="ongoingCoursesExportLink"
                            class="btn btn-sm btn-outline-success"
                            data-metric="ongoing_courses"
                            href="{{ route('payment.analytics.export', ['metric' => 'ongoing_courses', 'month' => $startOfMonth->format('Y-m'), 'course_id' => $selectedOngoingCourseId]) }}"
                        >
                            <i class="bi bi-file-earmark-pdf"></i> Export audit
                        </a>
                        <span class="badge bg-success">{{ number_format($ongoingCoursesCount ?? 0) }} regs</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start gap-3 mb-4 analytics-kpi-header">
                        <div>
                            <div class="text-muted small mb-1">Selected month paid local course fees</div>
                            <div class="display-6 fw-bold text-success mb-1 analytics-kpi-amount">LKR {{ number_format($ongoingCoursesAmount ?? 0, 2) }}</div>
                            <div class="text-muted small">{{ number_format($ongoingCoursesCount ?? 0) }} registrations with paid local course fees in {{ $startOfMonth->format('F Y') }}</div>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success rounded-3 p-3">
                            <i class="bi bi-cash-coin fs-3"></i>
                        </div>
                    </div>

                    <label class="form-label small text-muted" for="ongoingCourseFilter">Filter by course</label>
                    <select class="form-select" id="ongoingCourseFilter" name="ongoing_course_id" form="analyticsFiltersForm">
                        <option value="">All courses</option>
                        @foreach(($courses ?? []) as $course)
                            <option value="{{ $course->course_id }}" {{ (isset($selectedOngoingCourseId) && $selectedOngoingCourseId == $course->course_id) ? 'selected' : '' }}>{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                    <div class="d-flex justify-content-between align-items-center gap-2 mt-2 analytics-filter-actions">
                        <small class="text-muted">Select one course to recalculate its local course-fee total for the selected month.</small>
                        <button type="submit" form="analyticsFiltersForm" class="btn btn-sm btn-outline-success text-nowrap">Apply</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0">📊 Course-wise Payment Summary</h6>
                        <small class="text-muted">Registrations, new registrations, and payments by course</small>
                    </div>
                </div>
                <div class="card-body pt-3 px-3 pb-2">
                    <div class="table-responsive">
                        <table class="table table-striped align-middle mb-0 analytics-course-table">
                            <thead class="table-light">
                                <tr>
                                    <th>Course</th>
                                    <th>Location</th>
                                    <th class="text-end">Total Reg.</th>
                                    <th class="text-end">New Reg.</th>
                                    <th class="text-end">Ongoing</th>
                                    <th class="text-end">Pending Reg.</th>
                                    <th class="text-end">Paid Amount</th>
                                    <th class="text-end">Pending Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(($courseWiseSummary ?? []) as $courseSummary)
                                    @php
                                        $courseLocation = $courseSummary['location'] ?? 'N/A';
                                        $courseLocationLabel = in_array($courseLocation, ['Welisara', 'Moratuwa', 'Peradeniya'], true)
                                            ? 'Nebula Institute of Technology - ' . $courseLocation
                                            : $courseLocation;
                                    @endphp
                                    <tr>
                                        <td data-label="Course">{{ $courseSummary['course_name'] ?? 'N/A' }}</td>
                                        <td data-label="Location">{{ $courseLocationLabel }}</td>
                                        <td class="text-end" data-label="Total Reg.">{{ number_format($courseSummary['total_registrations'] ?? 0) }}</td>
                                        <td class="text-end" data-label="New Reg.">{{ number_format($courseSummary['new_registrations'] ?? 0) }}</td>
                                        <td class="text-end" data-label="Ongoing">{{ number_format($courseSummary['ongoing_courses'] ?? 0) }}</td>
                                        <td class="text-end" data-label="Pending Reg.">{{ number_format($courseSummary['pending_registrations'] ?? 0) }}</td>
                                        <td class="text-end" data-label="Paid Amount">LKR {{ number_format($courseSummary['paid_amount'] ?? 0, 2) }}</td>
                                        <td class="text-end" data-label="Pending Amount">LKR {{ number_format($courseSummary['pending_amount'] ?? 0, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">No course-wise payment summary available</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 pt-0 pb-3">
                    <div class="border rounded-3 bg-light p-3">
                        <div class="fw-semibold mb-2">Column guide</div>
                        <div class="row g-3 small text-muted">
                            <div class="col-md-4">
                                <div class="fw-semibold text-dark">Total Reg.</div>
                                Shows all registrations counted for that course in the selected filters.
                            </div>
                            <div class="col-md-4">
                                <div class="fw-semibold text-dark">New Reg.</div>
                                Shows registrations created in the current month for that course.
                            </div>
                            <div class="col-md-4">
                                <div class="fw-semibold text-dark">Ongoing</div>
                                Shows registrations currently marked as active or ongoing.
                            </div>
                            <div class="col-md-4">
                                <div class="fw-semibold text-dark">Pending Reg.</div>
                                Shows registrations that still need payment completion.
                            </div>
                            <div class="col-md-4">
                                <div class="fw-semibold text-dark">Paid Amount</div>
                                Shows the total paid amount collected for the course.
                            </div>
                            <div class="col-md-4">
                                <div class="fw-semibold text-dark">Pending Amount</div>
                                Shows the outstanding amount still waiting to be collected.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Revenue Trend --}}
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="fw-bold mb-0">🏦 Pending SLT Loan Recoveries This Month</h6>
                <small class="text-muted">Students with SLT loan receivables expected this month.</small>
            </div>
            <span class="badge bg-warning">{{ $pendingSltLoanRecoveries->count() }} students</span>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-bordered analytics-slt-table">
                    <thead class="table-light">
                        <tr>
                            <th>Student Name</th>
                            <th>Course</th>
                            <th>Intake</th>
                            <th class="text-end">Loan Amount</th>
                            <th class="text-end">Installment Amount</th>
                            <th>Effective Date</th>
                            <th class="text-center">Update Records</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($pendingSltLoanRecoveries as $record)
                            <tr>
                                <td data-label="Student Name">{{ $record['student_name'] }}</td>
                                <td data-label="Course">{{ $record['course_name'] }}</td>
                                <td data-label="Intake">{{ $record['intake'] }}</td>
                                <td class="text-end" data-label="Loan Amount">LKR {{ number_format($record['loan_amount'], 2) }}</td>
                                <td class="text-end" data-label="Installment Amount">LKR {{ number_format($record['installment_amount'], 2) }}</td>
                                <td data-label="Effective Date">{{ $record['effective_date'] }}</td>
                                <td class="text-center" data-label="Update Records">
                                    @if($record['student_id_value'] && $record['course_id'])
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-primary btn-open-slt-update-records"
                                            data-student-nic="{{ $record['student_id_value'] }}"
                                            data-course-id="{{ $record['course_id'] }}"
                                            data-student-name="{{ $record['student_name'] }}"
                                            data-course-name="{{ $record['course_name'] }}"
                                            data-loan-installment="{{ $record['loan_installment_number'] ?? '' }}"
                                            data-effective-date="{{ $record['effective_date'] ?? '' }}"
                                        >
                                            Update Records
                                        </button>
                                    @else
                                        <span class="text-muted">Not available</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No pending SLT recoveries found for this month.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

<div class="modal fade" id="analyticsUpdateRecordsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Payment Records</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-3 mb-3">
                    <div><strong>Student NIC:</strong> <span id="analyticsUpdateStudentNic">-</span></div>
                    <div><strong>Student:</strong> <span id="analyticsUpdateStudentName">-</span></div>
                    <div><strong>Course:</strong> <span id="analyticsUpdateCourseName">-</span></div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle analytics-modal-table">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Inst.</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Remaining</th>
                                <th>Method</th>
                                <th>Paid Date</th>
                                <th>Receipt No</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="analyticsPaymentRecordsTableBody">
                            <tr>
                                <td colspan="10" class="text-center text-muted">Load a student record to edit payments.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="analyticsUpdatePaymentRecordsBtn">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="analyticsSltLoanUpdateRecordsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title text-white"><i class="bi bi-bank me-2"></i>Update SLT Loan Recovery Records</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-wrap gap-4 mb-3 p-3 bg-light rounded border">
                    <div><strong>Student NIC:</strong> <span id="analyticsSltStudentNic">-</span></div>
                    <div><strong>Student:</strong> <span id="analyticsSltStudentName">-</span></div>
                    <div><strong>Course:</strong> <span id="analyticsSltCourseName">-</span></div>
                    <div><strong>Total Loan Amount:</strong> <span id="analyticsSltLoanAmount" class="text-primary fw-bold">-</span></div>
                    <div><strong>Monthly Receivable:</strong> <span id="analyticsSltMonthlyReceivable" class="text-success fw-bold">-</span></div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm table-bordered align-middle analytics-modal-table">
                        <thead class="table-light">
                            <tr>
                                <th>Type</th>
                                <th>Inst.</th>
                                <th class="text-end">Total Loan</th>
                                <th class="text-end">Monthly Receivable</th>
                                <th>Method</th>
                                <th>Effective Date</th>
                                <th>Receipt No</th>
                                <th>Status</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody id="analyticsSltPaymentRecordsTableBody">
                            <tr>
                                <td colspan="9" class="text-center text-muted">Load a student record to edit SLT loan recovery payments.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" id="analyticsUpdateSltPaymentRecordsBtn">
                    Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="analyticsPayModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="analyticsPayPaymentId">

                <div class="mb-3">
                    <label class="form-label" for="analyticsPayAmount">Amount</label>
                    <input type="number" class="form-control" id="analyticsPayAmount" step="0.01" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="analyticsPayMethod">Payment Method</label>
                    <select class="form-select" id="analyticsPayMethod">
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="online">Online</option>
                        <option value="card">Card</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label" for="analyticsPayDate">Payment Date</label>
                    <input type="date" class="form-control" id="analyticsPayDate">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="analyticsPayRemarks">Remarks</label>
                    <textarea class="form-control" id="analyticsPayRemarks" rows="2"></textarea>
                </div>

                <div class="mb-0">
                    <label class="form-label" for="analyticsPaySlip">Slip (optional)</label>
                    <input type="file" class="form-control" id="analyticsPaySlip" accept=".jpg,.jpeg,.png,.pdf">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="analyticsSubmitPaymentBtn">Submit Payment</button>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}" src="{{ asset('libs/chartjs/chart.min.js') }}"></script>
<script nonce="{{ $cspNonce }}">
document.addEventListener("DOMContentLoaded", () => {
    const asArray = (value) => {
        if (Array.isArray(value)) return value;
        if (value && typeof value === 'object') return Object.values(value);
        return [];
    };
    const revenueByDay = asArray(@json($revenueByDay ?? []));
    const csrfToken = '{{ csrf_token() }}';
    const paymentRecordsUrl = "{{ route('payment.get.records') }}";
    const paymentUpdateUrl = "{{ route('payment.update.record') }}";
    const paymentMakeUrl = "{{ route('payment.make') }}";
    const sltRecordsUrl = "{{ route('payment.slt.get.records') }}";
    const sltUpdateUrl = "{{ route('payment.slt.update.record') }}";
    const paymentTypeLabels = {
        course_fee: 'Course Fee',
        franchise_fee: 'Franchise Fee',
        registration_fee: 'Registration Fee',
        other: 'Other'
    };

    const savedSltRecoveryMessage = window.sessionStorage.getItem('analytics-slt-recovery-saved');
    if (savedSltRecoveryMessage) {
        window.sessionStorage.removeItem('analytics-slt-recovery-saved');
        window.setTimeout(() => showAnalyticsToast(savedSltRecoveryMessage, 'success'), 0);
    }

    let analyticsCurrentStudentNic = null;
    let analyticsCurrentCourseId = null;
    let analyticsPaymentRecords = [];

    let analyticsSltStudentNic = null;
    let analyticsSltCourseId = null;
    let analyticsSltInstallmentNumber = null;
    let analyticsSltEffectiveDate = null;
    let analyticsSltPaymentRecords = [];
    function setTableMessage(tbody, colspan, message, className) {
        if (!tbody) return;
        tbody.replaceChildren();
        const row = document.createElement('tr');
        const cell = document.createElement('td');
        cell.colSpan = colspan;
        cell.className = className;
        cell.textContent = message;
        row.appendChild(cell);
        tbody.appendChild(row);
    }

    function appendSelect(parent, idx, field, value, options) {
        const select = document.createElement('select');
        select.className = 'form-select form-select-sm';
        select.dataset.idx = String(idx);
        select.dataset.field = field;
        options.forEach(([optionValue, label]) => {
            select.appendChild(new Option(label, optionValue, false, String(value || '') === optionValue));
        });
        parent.appendChild(select);
        return select;
    }

    function appendTextInput(parent, idx, field, value, extra) {
        const input = document.createElement('input');
        input.type = extra.type || 'text';
        input.className = extra.className || 'form-control form-control-sm';
        input.dataset.idx = String(idx);
        input.dataset.field = field;
        input.value = value || '';
        if (extra.placeholder) input.placeholder = extra.placeholder;
        parent.appendChild(input);
        return input;
    }

    function renderAnalyticsSltLoanRecords() {
        const tbody = document.getElementById('analyticsSltPaymentRecordsTableBody');
        if (!tbody) return;

        if (!analyticsSltPaymentRecords.length) {
            setTableMessage(tbody, 9, 'No SLT loan recovery records found.', 'text-center text-muted');
            return;
        }

        tbody.innerHTML = analyticsSltPaymentRecords.map((record, idx) => {
            const totalLoan = Number(record.total_loan_amount ?? 0);
            const monthlyReceivable = Number(record.monthly_receivable_amount ?? 0);
            const status = String(record.status || 'pending').toLowerCase();
            const method = String(record.payment_method || 'cash').toLowerCase();

            return `
                <tr>
                    <td><span class="badge bg-info text-dark">SLT Loan Recovery</span></td>
                    <td><strong>Inst. ${record.installment_number ?? '-'}</strong></td>
                    <td class="text-end">LKR ${totalLoan.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="text-end fw-bold text-success">LKR ${monthlyReceivable.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>
                        <select class="form-select form-select-sm" data-idx="${idx}" data-field="payment_method">
                            <option value="cash" ${method === 'cash' ? 'selected' : ''}>Cash</option>
                            <option value="cheque" ${method === 'cheque' ? 'selected' : ''}>Cheque</option>
                            <option value="bank_transfer" ${method === 'bank_transfer' ? 'selected' : ''}>Bank Transfer</option>
                            <option value="online" ${method === 'online' ? 'selected' : ''}>Online</option>
                            <option value="card" ${method === 'card' ? 'selected' : ''}>Card</option>
                        </select>
                    </td>
                    <td>
                        <input type="date" class="form-control form-control-sm" value="${record.payment_effective_date || ''}" data-idx="${idx}" data-field="payment_effective_date">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" value="${record.receipt_no || ''}" data-idx="${idx}" data-field="receipt_no" placeholder="Receipt / Ref">
                    </td>
                    <td>
                        <select class="form-select form-select-sm" data-idx="${idx}" data-field="status">
                            <option value="pending" ${status === 'pending' ? 'selected' : ''}>pending</option>
                            <option value="paid" ${status === 'paid' ? 'selected' : ''}>paid</option>
                            <option value="failed" ${status === 'failed' ? 'selected' : ''}>failed</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" value="${record.remarks || ''}" data-idx="${idx}" data-field="remarks" placeholder="Remarks">
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function loadAnalyticsSltLoanRecords(studentNic, courseId, installmentNumber = null, effectiveDate = null) {
        const response = await fetch(sltRecordsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                student_nic: studentNic,
                course_id: courseId,
                installment_number: installmentNumber || null,
                effective_date: effectiveDate || null,
            }),
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to load SLT loan recovery records.');
        }

        analyticsSltPaymentRecords = Array.isArray(data.records) ? data.records : [];
        if (data.total_loan_amount !== undefined) {
            document.getElementById('analyticsSltLoanAmount').textContent = 'LKR ' + Number(data.total_loan_amount).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        if (data.monthly_receivable !== undefined) {
            document.getElementById('analyticsSltMonthlyReceivable').textContent = 'LKR ' + Number(data.monthly_receivable).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        renderAnalyticsSltLoanRecords();
    }

    async function saveAnalyticsSltLoanUpdates() {
        const fields = document.querySelectorAll('#analyticsSltPaymentRecordsTableBody [data-field]');
        if (!fields.length) {
            showAnalyticsToast('No editable fields found.', 'error');
            return;
        }

        const updates = [];

        fields.forEach((fieldEl) => {
            const idx = Number(fieldEl.dataset.idx);
            const key = fieldEl.dataset.field;
            const value = fieldEl.value;
            const record = analyticsSltPaymentRecords[idx];

            if (!record?.student_payment_plan_id || !key) {
                return;
            }

            if (!updates[idx]) {
                updates[idx] = {
                    student_payment_plan_id: record.student_payment_plan_id,
                    installment_number: record.installment_number,
                };
            }

            updates[idx][key] = value;
        });

        const payload = updates.filter(Boolean);
        if (!payload.length) {
            showAnalyticsToast('No valid updates to submit.', 'error');
            return;
        }

        const response = await fetch(sltUpdateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ updates: payload }),
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to update SLT loan recovery records.');
        }

        const successMessage = data.message || 'SLT loan recovery records updated successfully!';
        bootstrap.Modal.getInstance(document.getElementById('analyticsSltLoanUpdateRecordsModal'))?.hide();
        window.sessionStorage.setItem('analytics-slt-recovery-saved', successMessage);
        // Reload the analytics page so paid records leave the pending list and all
        // counts and totals are recalculated from the data that was just saved.
        window.location.reload();
    }

    function showAnalyticsToast(message, type = 'success') {
        if (window.global_utils?.showToast) {
            window.global_utils.showToast(type, message);
            return;
        }

        const toastContainer = document.getElementById('analyticsToastContainer') || (() => {
            const container = document.createElement('div');
            container.id = 'analyticsToastContainer';
            container.className = 'toast-container position-fixed top-0 end-0 p-3';
            container.style.zIndex = '1090';
            document.body.appendChild(container);
            return container;
        })();
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-bg-${type === 'error' ? 'danger' : type} border-0`;
        toast.setAttribute('role', 'alert');
        toast.innerHTML = '<div class="d-flex"><div class="toast-body"></div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div>';
        toast.querySelector('.toast-body').textContent = message;
        toastContainer.appendChild(toast);
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
        bootstrap.Toast.getOrCreateInstance(toast, { delay: 3500 }).show();
    }

    function formatPaymentType(type) {
        if (!type) {
            return 'N/A';
        }

        return paymentTypeLabels[type] || type.replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
    }

    function renderAnalyticsPaymentRecords() {
        const tbody = document.getElementById('analyticsPaymentRecordsTableBody');
        if (!tbody) {
            return;
        }

        if (!analyticsPaymentRecords.length) {
            setTableMessage(tbody, 10, 'No payment records found.', 'text-center text-muted');
            return;
        }

        tbody.innerHTML = analyticsPaymentRecords.map((record, idx) => {
            const totalFee = Number(record.total_fee ?? 0);
            const remaining = Number(record.remaining_amount ?? 0);
            const isPaid = String(record.status || '').toLowerCase() === 'paid';

            return `
                <tr>
                    <td>${formatPaymentType(record.payment_type)}</td>
                    <td>${record.installment_number ?? '-'}</td>
                    <td class="text-end">${totalFee.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td class="text-end">${remaining.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })}</td>
                    <td>
                        <select class="form-select form-select-sm" data-idx="${idx}" data-field="payment_method">
                            <option value="cash" ${record.payment_method === 'cash' ? 'selected' : ''}>Cash</option>
                            <option value="cheque" ${record.payment_method === 'cheque' ? 'selected' : ''}>Cheque</option>
                            <option value="bank_transfer" ${record.payment_method === 'bank_transfer' ? 'selected' : ''}>Bank Transfer</option>
                            <option value="online" ${record.payment_method === 'online' ? 'selected' : ''}>Online</option>
                            <option value="card" ${record.payment_method === 'card' ? 'selected' : ''}>Card</option>
                        </select>
                    </td>
                    <td>
                        <input type="date" class="form-control form-control-sm" value="${record.payment_date || ''}" data-idx="${idx}" data-field="payment_date">
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" value="${record.receipt_no || ''}" data-idx="${idx}" data-field="receipt_no">
                    </td>
                    <td>
                        <select class="form-select form-select-sm" data-idx="${idx}" data-field="status">
                            <option value="pending" ${record.status === 'pending' ? 'selected' : ''}>pending</option>
                            <option value="paid" ${record.status === 'paid' ? 'selected' : ''}>paid</option>
                            <option value="failed" ${record.status === 'failed' ? 'selected' : ''}>failed</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm" value="${record.remarks || ''}" data-idx="${idx}" data-field="remarks">
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-outline-primary btn-analytics-pay" data-payment-id="${record.payment_id}" data-remaining="${remaining}" ${isPaid || remaining <= 0 ? 'disabled' : ''}>
                            Pay
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    async function loadAnalyticsPaymentRecords(studentNic, courseId) {
        const response = await fetch(paymentRecordsUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
                student_nic: studentNic,
                course_id: courseId,
            }),
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to load payment records.');
        }

        analyticsPaymentRecords = Array.isArray(data.records) ? data.records : [];
        renderAnalyticsPaymentRecords();
    }

    async function saveAnalyticsPaymentUpdates() {
        const fields = document.querySelectorAll('#analyticsPaymentRecordsTableBody [data-field]');
        if (!fields.length) {
            showAnalyticsToast('No editable fields found.', 'error');
            return;
        }

        const updates = [];

        fields.forEach((fieldEl) => {
            const idx = Number(fieldEl.dataset.idx);
            const key = fieldEl.dataset.field;
            const value = fieldEl.value;
            const record = analyticsPaymentRecords[idx];

            if (!record?.payment_id || !key) {
                return;
            }

            if (!updates[idx]) {
                updates[idx] = { id: record.payment_id };
            }

            updates[idx][key] = value;
        });

        const payload = updates.filter(Boolean);
        if (!payload.length) {
            showAnalyticsToast('No valid updates to submit.', 'error');
            return;
        }

        const response = await fetch(paymentUpdateUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({ updates: payload }),
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to update payment records.');
        }

        showAnalyticsToast(data.message || 'Payment records updated successfully!', 'success');
        await loadAnalyticsPaymentRecords(analyticsCurrentStudentNic, analyticsCurrentCourseId);
    }

    function openAnalyticsPayModal(paymentId, remaining) {
        const amount = Number(remaining || 0);
        if (amount <= 0) {
            showAnalyticsToast('No remaining amount to pay for this record.', 'error');
            return;
        }

        document.getElementById('analyticsPayPaymentId').value = paymentId;
        document.getElementById('analyticsPayAmount').value = amount.toFixed(2);
        document.getElementById('analyticsPayDate').value = window.toLocalDateString(new Date());
        document.getElementById('analyticsPayRemarks').value = '';
        document.getElementById('analyticsPaySlip').value = '';

        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('analyticsPayModal'));
        modal.show();
    }

    async function submitAnalyticsPayment() {
        const paymentId = document.getElementById('analyticsPayPaymentId').value;
        const amount = Number(document.getElementById('analyticsPayAmount').value || 0);
        const paymentMethod = document.getElementById('analyticsPayMethod').value;
        const paymentDate = document.getElementById('analyticsPayDate').value;
        const remarks = document.getElementById('analyticsPayRemarks').value;
        const slipFile = document.getElementById('analyticsPaySlip').files?.[0] || null;

        if (!paymentId || amount <= 0 || !paymentDate) {
            showAnalyticsToast('Missing payment details.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('payment_id', paymentId);
        formData.append('amount', amount.toString());
        formData.append('payment_method', paymentMethod);
        formData.append('payment_date', paymentDate);
        formData.append('remarks', remarks || '');
        if (slipFile) {
            formData.append('slip', slipFile);
        }

        const response = await fetch(paymentMakeUrl, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
            },
            body: formData,
        });

        const data = await response.json();
        if (!data.success) {
            throw new Error(data.message || 'Failed to submit payment.');
        }

        showAnalyticsToast(data.message || 'Payment submitted successfully!', 'success');
        bootstrap.Modal.getInstance(document.getElementById('analyticsPayModal'))?.hide();
        await loadAnalyticsPaymentRecords(analyticsCurrentStudentNic, analyticsCurrentCourseId);
    }
    const analyticsForm = document.getElementById('analyticsFiltersForm');
    const analyticsMonthFilter = document.getElementById('analyticsMonthFilter');
    const newRegistrationCourseFilter = document.getElementById('newRegistrationCourseFilter');
    const ongoingCourseFilter = document.getElementById('ongoingCourseFilter');
    const newRegistrationExportLink = document.getElementById('newRegistrationsExportLink');
    const ongoingCoursesExportLink = document.getElementById('ongoingCoursesExportLink');

    function updateAuditExportLink(linkEl, metric, courseFilterEl) {
        if (!linkEl) {
            return;
        }

        const url = new URL(linkEl.href, window.location.origin);
        url.searchParams.set('metric', metric);

        if (analyticsMonthFilter && analyticsMonthFilter.value) {
            url.searchParams.set('month', analyticsMonthFilter.value);
        } else {
            url.searchParams.delete('month');
        }

        if (courseFilterEl && courseFilterEl.value) {
            url.searchParams.set('course_id', courseFilterEl.value);
        } else {
            url.searchParams.delete('course_id');
        }

        linkEl.href = url.toString();
    }

    function updateAllAuditExportLinks() {
        updateAuditExportLink(newRegistrationExportLink, 'new_registrations', newRegistrationCourseFilter);
        updateAuditExportLink(ongoingCoursesExportLink, 'ongoing_courses', ongoingCourseFilter);
    }

    document.addEventListener('click', async (event) => {
        const openSltBtn = event.target.closest('.btn-open-slt-update-records');
        if (openSltBtn) {
            analyticsSltStudentNic = openSltBtn.dataset.studentNic || null;
            analyticsSltCourseId = openSltBtn.dataset.courseId || null;
            analyticsSltInstallmentNumber = openSltBtn.dataset.loanInstallment || null;
            analyticsSltEffectiveDate = openSltBtn.dataset.effectiveDate || null;

            document.getElementById('analyticsSltStudentNic').textContent = analyticsSltStudentNic || '-';
            document.getElementById('analyticsSltStudentName').textContent = openSltBtn.dataset.studentName || '-';
            document.getElementById('analyticsSltCourseName').textContent = openSltBtn.dataset.courseName || '-';
            document.getElementById('analyticsSltLoanAmount').textContent = '-';
            document.getElementById('analyticsSltMonthlyReceivable').textContent = '-';

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('analyticsSltLoanUpdateRecordsModal'));
            modal.show();

            document.getElementById('analyticsSltPaymentRecordsTableBody').innerHTML = '';
            setTableMessage(document.getElementById('analyticsSltPaymentRecordsTableBody'), 9, 'Loading SLT loan recovery records...', 'text-center text-muted');

            try {
                await loadAnalyticsSltLoanRecords(
                    analyticsSltStudentNic,
                    analyticsSltCourseId,
                    analyticsSltInstallmentNumber,
                    analyticsSltEffectiveDate
                );
            } catch (error) {
                setTableMessage(document.getElementById('analyticsSltPaymentRecordsTableBody'), 9, error.message, 'text-center text-danger');
                showAnalyticsToast(error.message, 'error');
            }

            return;
        }

        const openBtn = event.target.closest('.btn-open-update-records');
        if (openBtn) {
            analyticsCurrentStudentNic = openBtn.dataset.studentNic || null;
            analyticsCurrentCourseId = openBtn.dataset.courseId || null;

            document.getElementById('analyticsUpdateStudentNic').textContent = analyticsCurrentStudentNic || '-';
            document.getElementById('analyticsUpdateStudentName').textContent = openBtn.dataset.studentName || '-';
            document.getElementById('analyticsUpdateCourseName').textContent = openBtn.dataset.courseName || '-';

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('analyticsUpdateRecordsModal'));
            modal.show();

            setTableMessage(document.getElementById('analyticsPaymentRecordsTableBody'), 10, 'Loading payment records...', 'text-center text-muted');

            try {
                await loadAnalyticsPaymentRecords(analyticsCurrentStudentNic, analyticsCurrentCourseId);
            } catch (error) {
                setTableMessage(document.getElementById('analyticsPaymentRecordsTableBody'), 10, error.message, 'text-center text-danger');
                showAnalyticsToast(error.message, 'error');
            }

            return;
        }

        const payBtn = event.target.closest('.btn-analytics-pay');
        if (payBtn) {
            openAnalyticsPayModal(payBtn.dataset.paymentId, payBtn.dataset.remaining);
        }
    });

    const saveSltUpdatesButton = document.getElementById('analyticsUpdateSltPaymentRecordsBtn');
    if (saveSltUpdatesButton) {
        saveSltUpdatesButton.addEventListener('click', async () => {
            if (!analyticsSltStudentNic || !analyticsSltCourseId) {
                showAnalyticsToast('Missing student or course context.', 'error');
                return;
            }

            saveSltUpdatesButton.disabled = true;
            try {
                await saveAnalyticsSltLoanUpdates();
            } catch (error) {
                showAnalyticsToast(error.message, 'error');
            } finally {
                saveSltUpdatesButton.disabled = false;
            }
        });
    }

    const saveUpdatesButton = document.getElementById('analyticsUpdatePaymentRecordsBtn');
    if (saveUpdatesButton) {
        saveUpdatesButton.addEventListener('click', async () => {
            if (!analyticsCurrentStudentNic || !analyticsCurrentCourseId) {
                showAnalyticsToast('Missing student or course context.', 'error');
                return;
            }

            saveUpdatesButton.disabled = true;
            try {
                await saveAnalyticsPaymentUpdates();
            } catch (error) {
                showAnalyticsToast(error.message, 'error');
            } finally {
                saveUpdatesButton.disabled = false;
            }
        });
    }

    const submitPaymentButton = document.getElementById('analyticsSubmitPaymentBtn');
    if (submitPaymentButton) {
        submitPaymentButton.addEventListener('click', async () => {
            submitPaymentButton.disabled = true;
            try {
                await submitAnalyticsPayment();
            } catch (error) {
                showAnalyticsToast(error.message, 'error');
            } finally {
                submitPaymentButton.disabled = false;
            }
        });
    }

    function applyAnalyticsFilters() {
        const url = new URL(window.location.href);

        if (analyticsMonthFilter && analyticsMonthFilter.value) {
            url.searchParams.set('month', analyticsMonthFilter.value);
        } else if (analyticsMonthFilter) {
            url.searchParams.delete('month');
        }

        if (newRegistrationCourseFilter && newRegistrationCourseFilter.value) {
            url.searchParams.set('new_registration_course_id', newRegistrationCourseFilter.value);
        } else {
            url.searchParams.delete('new_registration_course_id');
        }

        if (ongoingCourseFilter && ongoingCourseFilter.value) {
            url.searchParams.set('ongoing_course_id', ongoingCourseFilter.value);
        } else {
            url.searchParams.delete('ongoing_course_id');
        }

        window.location.href = url.toString();
    }

    if (analyticsForm) {
        analyticsForm.addEventListener('submit', () => {
            updateAllAuditExportLinks();
        });
    }

    if (newRegistrationCourseFilter) {
        newRegistrationCourseFilter.addEventListener('change', () => {
            updateAllAuditExportLinks();
            applyAnalyticsFilters();
        });
    }

    if (ongoingCourseFilter) {
        ongoingCourseFilter.addEventListener('change', () => {
            updateAllAuditExportLinks();
            applyAnalyticsFilters();
        });
    }

    if (analyticsMonthFilter) {
        analyticsMonthFilter.addEventListener('change', () => {
            updateAllAuditExportLinks();
            applyAnalyticsFilters();
        });
    }

    updateAllAuditExportLinks();

    // Revenue Trend Chart
    const revenueChartElement = document.getElementById('revenueChart');
    if (revenueChartElement && typeof Chart !== 'undefined') {
        try {
            new Chart(revenueChartElement, {
            type: 'bar',
            data: {
                labels: revenueByDay.map((r) => r.date),
                datasets: [{
                    label: 'Daily Revenue',
                    data: revenueByDay.map((r) => r.revenue),
                    backgroundColor: 'rgba(102, 126, 234, 0.8)',
                    borderColor: '#667eea',
                    borderWidth: 2,
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        padding: 15,
                        callbacks: {
                            label: function(context) {
                                return 'Revenue: LKR ' + new Intl.NumberFormat().format(context.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        ticks: {
                            callback: function(value) {
                                return 'LKR ' + new Intl.NumberFormat().format(value);
                            }
                        }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
        } catch (error) {
            console.error('Analytics revenue chart failed:', error);
        }
    } else if (typeof Chart === 'undefined') {
        console.error('Chart.js failed to load');
    }
});
</script>

<style nonce="{{ $cspNonce }}">
#payment-analytics,
#payment-analytics .card,
#payment-analytics .card-body,
#payment-analytics .card-header {
    min-width: 0;
    max-width: 100%;
    overflow: visible;
    height: auto;
    transform: none !important;
}
body:has(#payment-analytics) .body-wrapper > .container-fluid {
    overflow: visible;
}
#payment-analytics [class*="col-"] {
    min-width: 0;
}
#payment-analytics h2,
#payment-analytics h4,
#payment-analytics .analytics-kpi-amount {
    overflow-wrap: anywhere;
    word-break: break-word;
}
#payment-analytics .nebula-select,
#payment-analytics .nebula-select-toggle,
#payment-analytics .form-select,
#payment-analytics .form-control {
    width: 100%;
    max-width: 100%;
}
#payment-analytics .chart-wrap {
    position: relative;
    width: 100%;
    min-height: 220px;
}
#payment-analytics .chart-wrap canvas {
    max-width: 100%;
}
#payment-analytics .card {
    transition: box-shadow 0.2s;
}
#payment-analytics .card:hover {
    transform: none !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
}
#payment-analytics .progress {
    background-color: rgba(0, 0, 0, 0.05);
}
#payment-analytics .table-hover tbody tr:hover {
    background-color: rgba(102, 126, 234, 0.05);
}
.analytics-month-filter {
    background-color: #fff !important;
    border: 1px solid #0d6efd;
    color: #212529 !important;
    min-height: 44px;
}
.analytics-month-filter::-webkit-calendar-picker-indicator {
    opacity: 1;
    cursor: pointer;
}
@media (max-width: 767.98px) {
    #payment-analytics h2 {
        font-size: 1.25rem;
    }
    #payment-analytics .analytics-back-btn,
    #payment-analytics .analytics-filter-submit .btn {
        width: 100%;
    }
    #payment-analytics .form-control,
    #payment-analytics .form-select,
    #payment-analytics .form-select-sm,
    #payment-analytics .nebula-select-toggle {
        font-size: 16px;
    }
    #payment-analytics .card-body {
        padding: 1rem 0.75rem;
    }
    .modal-footer .btn {
        width: 100%;
    }
}
@media (max-width: 991.98px) {
    .analytics-kpi-header {
        flex-direction: column;
        align-items: flex-start !important;
    }
    .analytics-kpi-header > div:last-child {
        align-self: flex-end;
    }
    .analytics-kpi-amount {
        font-size: clamp(1.65rem, 6vw, 2.3rem);
        line-height: 1.1;
        word-break: break-word;
    }
    .analytics-filter-actions {
        flex-direction: column;
        align-items: stretch !important;
    }
    .analytics-filter-actions .btn {
        width: 100%;
    }
    #payment-analytics .table-responsive {
        overflow: visible;
    }
    #payment-analytics .analytics-course-table thead,
    #payment-analytics .analytics-slt-table thead,
    .analytics-modal-table thead {
        display: none;
    }
    #payment-analytics .analytics-course-table,
    #payment-analytics .analytics-course-table tbody,
    #payment-analytics .analytics-course-table tr,
    #payment-analytics .analytics-course-table td,
    #payment-analytics .analytics-slt-table,
    #payment-analytics .analytics-slt-table tbody,
    #payment-analytics .analytics-slt-table tr,
    #payment-analytics .analytics-slt-table td,
    .analytics-modal-table,
    .analytics-modal-table tbody,
    .analytics-modal-table tr,
    .analytics-modal-table td {
        display: block;
        width: 100%;
    }
    #payment-analytics .analytics-course-table tbody tr,
    #payment-analytics .analytics-slt-table tbody tr,
    .analytics-modal-table tbody tr {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 12px;
        padding: 8px 12px 12px;
        background: #fff;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
    }
    #payment-analytics .analytics-course-table td,
    #payment-analytics .analytics-slt-table td,
    .analytics-modal-table td {
        border: 0;
        padding: 0.45rem 0;
        text-align: left !important;
    }
    #payment-analytics .analytics-course-table td[data-label]::before,
    #payment-analytics .analytics-slt-table td[data-label]::before,
    .analytics-modal-table td[data-label]::before {
        content: attr(data-label);
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 2px;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
    #payment-analytics .analytics-slt-table .btn,
    .analytics-modal-table .btn {
        width: 100%;
    }
}
</style>

@endsection
