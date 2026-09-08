@extends('inc.app')

@section('title', 'NEBULA | Payment Plans')

@section('content')
<div class="container-fluid px-2 px-md-3">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <h2 class="mb-0 fs-4 fs-md-3">Payment Plans</h2>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success btn-sm btn-export-csv" data-format="csv">
                        <i class="bi bi-file-earmark-spreadsheet"></i> CSV
                    </button>
                    <button type="button" class="btn btn-danger btn-sm btn-export-pdf" data-format="pdf">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </button>
                </div>
            </div>

            <form method="GET" action="{{ route('payment.plan.index') }}" id="filterForm" class="row gy-2 gx-2 gx-md-3 align-items-end">
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small">Location</label>
                    <select name="location" class="form-select form-select-sm form-select-md">
                        <option value="">All</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc }}" @selected(request('location')===$loc)>{{ $loc }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-3">
                    <label class="form-label small">Course</label>
                    <select name="course_id" id="filter-course" class="form-select form-select-sm form-select-md">
                        <option value="">All</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->course_id }}" @selected((string)request('course_id')===(string)$c->course_id)>
                                {{ $c->course_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small">Intake</label>
                    <select name="intake_id" id="filter-intake" class="form-select form-select-sm form-select-md">
                        <option value="">All</option>
                        @foreach($intakes as $i)
                            <option value="{{ $i->intake_id }}" @selected((string)request('intake_id')===(string)$i->intake_id)>
                                {{ $i->batch ?? 'Batch ' . $i->intake_id }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-2">
                    <label class="form-label small">Sort By</label>
                    <select name="sort" class="form-select form-select-sm form-select-md" id="sortSelect">
                        <option value="newest" @selected(request('sort', 'newest')==='newest')>Newest First</option>
                        <option value="oldest" @selected(request('sort')==='oldest')>Oldest First</option>
                        <option value="location_asc" @selected(request('sort')==='location_asc')>Location A-Z</option>
                        <option value="location_desc" @selected(request('sort')==='location_desc')>Location Z-A</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-1">
                    <label class="form-label small">Show</label>
                    <select name="per_page" class="form-select form-select-sm form-select-md" id="perPageSelect">
                        <option value="10" @selected(request('per_page', 10)==10)>10</option>
                        <option value="25" @selected(request('per_page', 10)==25)>25</option>
                        <option value="50" @selected(request('per_page', 10)==50)>50</option>
                        <option value="100" @selected(request('per_page', 10)==100)>100</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-md-1 d-grid">
                    <button class="btn btn-primary btn-sm btn-md-md">Filter</button>
                </div>
            </form>

            @if(request()->hasAny(['location', 'course_id', 'intake_id']))
                <div class="mt-2">
                    <a href="{{ route('payment.plan.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </a>
                </div>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0 p-md-3">
            <!-- Results Summary -->
            @php
                $allPlans = $plans->items();
                $totalCount = $plans->total();
                $currentPage = $plans->currentPage();
                $perPage = $plans->perPage();
                $from = ($currentPage - 1) * $perPage + 1;
                $to = min($currentPage * $perPage, $totalCount);
            @endphp

            @if($totalCount > 0)
                <div class="d-none d-lg-block px-3 py-2 bg-light border-bottom">
                    <small class="text-muted">
                        Showing {{ $from }} to {{ $to }} of {{ $totalCount }} results
                    </small>
                </div>
            @endif

            <!-- Desktop Table View -->
            <div class="d-none d-lg-block table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Location</th>
                            <th>Course</th>
                            <th>Intake</th>
                            <th class="text-end">Reg. Fee (LKR)</th>
                            <th class="text-end">Local Fee (LKR)</th>
                            <th class="text-end">Franchise</th>
                            <th>Full Payment Discount (%)</th>
                            <th>Installments</th>
                            <th>Created</th>
                            <th style="width: 130px;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($allPlans as $plan)
                        @php
                            $items = is_array($plan->installments) ? $plan->installments : (json_decode($plan->installments, true) ?? []);
                            $count = count($items);
                            $firstDue = $count ? ($items[0]['due_date'] ?? null) : null;
                            $lastDue  = $count ? ($items[array_key_last($items)]['due_date'] ?? null) : null;
                            $totalLocal = 0; $totalIntl = 0;
                            foreach ($items as $it) {
                                $totalLocal += (float)($it['local_amount'] ?? 0);
                                $totalIntl  += (float)($it['international_amount'] ?? 0);
                            }
                        @endphp
                        <tr>
                            <td>{{ $plan->id }}</td>
                            <td>{{ $plan->location }}</td>
                            <td>{{ optional($plan->course)->course_name ?? '—' }}</td>
                            <td>{{ optional($plan->intake)->batch ?? '—' }}</td>
                            <td class="text-end">{{ number_format($plan->registration_fee, 2, '.', ',') }}</td>
                            <td class="text-end">{{ number_format($plan->local_fee, 2, '.', ',') }}</td>
                            <td class="text-end">
                                {{ number_format($plan->international_fee, 2, '.', ',') }}
                                <small class="text-muted">{{ $plan->international_currency }}</small>
                            </td>
                            <td>
                                @if($plan->apply_discount)
                                    {{ rtrim(rtrim(number_format($plan->discount ?? 0, 2, '.', ''), '0'), '.') }}%
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                @if($plan->installment_plan)
                                    <button class="btn btn-sm btn-outline-secondary"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#inst-{{ $plan->id }}">
                                        View {{ $count }} Installments
                                    </button>
                                    <div class="small text-muted mt-1">
                                        @if($count)
                                            {{ $firstDue }} → {{ $lastDue }}
                                        @endif
                                    </div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>
                                <div class="small text-muted">{{ $plan->created_at?->format('Y-m-d H:i') }}</div>
                            </td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="{{ route('payment.plan.edit',$plan->id) }}" class="btn btn-sm btn-warning">Edit</a>
                                </div>
                            </td>
                        </tr>
                        @if($plan->installment_plan)
                            <tr class="collapse" id="inst-{{ $plan->id }}">
                                <td colspan="11">
                                    <div class="table-responsive">
                                        <table class="table table-sm table-striped mb-2">
                                            <thead class="table-secondary">
                                                <tr>
                                                    <th>#</th>
                                                    <th>Due Date</th>
                                                    <th class="text-end">Local (LKR)</th>
                                                    <th class="text-end">International ({{ $plan->international_currency }})</th>
                                                    <th>Tax?</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($items as $it)
                                                    <tr>
                                                        <td>{{ $it['installment_number'] ?? '' }}</td>
                                                        <td>{{ $it['due_date'] ?? '' }}</td>
                                                        <td class="text-end">{{ number_format((float)($it['local_amount'] ?? 0), 2, '.', ',') }}</td>
                                                        <td class="text-end">{{ number_format((float)($it['international_amount'] ?? 0), 2, '.', ',') }}</td>
                                                        <td>
                                                            @if(!empty($it['apply_tax']))
                                                                <span class="badge bg-success">Yes</span>
                                                            @else
                                                                <span class="badge bg-secondary">No</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr><td colspan="5" class="text-center text-muted">No installments found</td></tr>
                                                @endforelse
                                            </tbody>
                                            <tfoot>
                                                <tr class="fw-semibold">
                                                    <td colspan="2" class="text-end">Totals:</td>
                                                    <td class="text-end">{{ number_format($totalLocal, 2, '.', ',') }}</td>
                                                    <td class="text-end">{{ number_format($totalIntl, 2, '.', ',') }}</td>
                                                    <td></td>
                                                </tr>
                                                <tr class="small text-muted">
                                                    <td colspan="2" class="text-end">Required:</td>
                                                    <td class="text-end">{{ number_format((float)$plan->local_fee, 2, '.', ',') }}</td>
                                                    <td class="text-end">{{ number_format((float)$plan->international_fee, 2, '.', ',') }}</td>
                                                    <td></td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="11" class="text-center text-muted py-4">No payment plans found.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Mobile Card View -->
            <div class="d-lg-none">
                @if($totalCount > 0)
                    <div class="px-3 py-2 bg-light border-bottom">
                        <small class="text-muted">
                            Showing {{ $from }}-{{ $to }} of {{ $totalCount }}
                        </small>
                    </div>
                @endif

                @forelse($allPlans as $plan)
                    @php
                        $items = is_array($plan->installments) ? $plan->installments : (json_decode($plan->installments, true) ?? []);
                        $count = count($items);
                        $firstDue = $count ? ($items[0]['due_date'] ?? null) : null;
                        $lastDue  = $count ? ($items[array_key_last($items)]['due_date'] ?? null) : null;
                        $totalLocal = 0; $totalIntl = 0;
                        foreach ($items as $it) {
                            $totalLocal += (float)($it['local_amount'] ?? 0);
                            $totalIntl  += (float)($it['international_amount'] ?? 0);
                        }
                    @endphp
                    <div class="border-bottom p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <span class="badge bg-secondary me-1">#{{ $plan->id }}</span>
                                <span class="badge bg-info">{{ $plan->location }}</span>
                            </div>
                            <a href="{{ route('payment.plan.edit',$plan->id) }}" class="btn btn-sm btn-warning">Edit</a>
                        </div>

                        <div class="mb-2">
                            <strong class="d-block text-truncate">{{ optional($plan->course)->course_name ?? '—' }}</strong>
                            <small class="text-muted">{{ optional($plan->intake)->batch ?? '—' }}</small>
                        </div>

                        <div class="row g-2 small mb-2">
                            <div class="col-6">
                                <div class="text-muted">Reg. Fee</div>
                                <strong>LKR {{ number_format($plan->registration_fee, 2, '.', ',') }}</strong>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Local Fee</div>
                                <strong>LKR {{ number_format($plan->local_fee, 2, '.', ',') }}</strong>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Franchise</div>
                                <strong>{{ number_format($plan->international_fee, 2, '.', ',') }} {{ $plan->international_currency }}</strong>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Discount</div>
                                <strong>
                                    @if($plan->apply_discount)
                                        {{ rtrim(rtrim(number_format($plan->discount ?? 0, 2, '.', ''), '0'), '.') }}%
                                    @else
                                        —
                                    @endif
                                </strong>
                            </div>
                        </div>

                        @if($plan->installment_plan)
                            <button class="btn btn-sm btn-outline-secondary w-100"
                                    type="button"
                                    data-bs-toggle="collapse"
                                    data-bs-target="#inst-mobile-{{ $plan->id }}">
                                View {{ $count }} Installments
                                @if($count)
                                    <small class="text-muted d-block">({{ $firstDue }} → {{ $lastDue }})</small>
                                @endif
                            </button>

                            <div class="collapse mt-2" id="inst-mobile-{{ $plan->id }}">
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped mb-0">
                                        <thead class="table-secondary">
                                            <tr>
                                                <th>#</th>
                                                <th>Due Date</th>
                                                <th class="text-end">Local</th>
                                                <th class="text-end">Intl</th>
                                                <th>Tax</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($items as $it)
                                                <tr>
                                                    <td>{{ $it['installment_number'] ?? '' }}</td>
                                                    <td class="small">{{ $it['due_date'] ?? '' }}</td>
                                                    <td class="text-end small">{{ number_format((float)($it['local_amount'] ?? 0), 0) }}</td>
                                                    <td class="text-end small">{{ number_format((float)($it['international_amount'] ?? 0), 0) }}</td>
                                                    <td>
                                                        @if(!empty($it['apply_tax']))
                                                            <span class="badge bg-success">Y</span>
                                                        @else
                                                            <span class="badge bg-secondary">N</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr><td colspan="5" class="text-center text-muted">No installments</td></tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot class="small">
                                            <tr class="fw-semibold">
                                                <td colspan="2">Totals:</td>
                                                <td class="text-end">{{ number_format($totalLocal, 0) }}</td>
                                                <td class="text-end">{{ number_format($totalIntl, 0) }}</td>
                                                <td></td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        @endif

                        <div class="small text-muted mt-2">
                            Created: {{ $plan->created_at?->format('Y-m-d H:i') }}
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-5">No payment plans found.</div>
                @endforelse
            </div>

            <!-- Pagination -->
<div class="flex flex-col sm:flex-row justify-between items-center p-3 gap-2">
    <div class="text-sm text-gray-500">
        Showing {{ $plans->firstItem() ?? 0 }}–{{ $plans->lastItem() ?? 0 }} of {{ $plans->total() }}
    </div>

    <div id="pagination-wrapper" class="text-sm" style="display:flex;align-items:center;gap:4px;line-height:1;">
        {{ $plans->withQueryString()->links() }}
    </div>

    <script nonce="{{ $cspNonce }}">
        window.addEventListener('load', () => {
            // Select all pagination icons and normalize their size
            document.querySelectorAll('#pagination-wrapper svg').forEach(svg => {
                svg.style.width = '14px';
                svg.style.height = '14px';
                svg.style.margin = '0 2px';
                svg.style.verticalAlign = 'middle';
                svg.style.display = 'inline-block';
            });

            // Center pagination items horizontally
            const pag = document.querySelector('#pagination-wrapper nav');
            if (pag) {
                pag.style.display = 'flex';
                pag.style.alignItems = 'center';
                pag.style.gap = '4px';
            }
        });
    </script>
</div>


        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
// Event delegation for export buttons
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-export-csv') || e.target.closest('.btn-export-pdf')) {
        const btn = e.target.closest('.btn-export-csv, .btn-export-pdf');
        const format = btn.dataset.format;
        exportData(format);
    }
});

// Event delegation for select changes
document.addEventListener('change', function(e) {
    if (e.target.id === 'sortSelect' || e.target.id === 'perPageSelect') {
        e.target.form.submit();
    }
});

// CLIENT-SIDE EXPORT FUNCTIONS (Frontend Only)
function exportData(format) {
    // Get current filter parameters
    const params = new URLSearchParams(window.location.search);

    // Get all plans data from the page
    const plans = @json($plans->items());

    if (format === 'csv') {
        exportToCSV(plans);
    } else if (format === 'pdf') {
        exportToPDF(plans);
    }
}

function exportToCSV(plans) {
    // CSV Headers
    const headers = ['ID', 'Location', 'Course', 'Intake', 'Reg. Fee (LKR)', 'Local Fee (LKR)',
                     'International Fee', 'Currency', 'Discount %', 'Installment Plan', 'Created At'];

    // Build CSV content
    let csv = headers.join(',') + '\n';

    plans.forEach(plan => {
        const row = [
            plan.id,
            `"${plan.location}"`,
            `"${plan.course?.course_name || '—'}"`,
            `"${plan.intake?.batch || '—'}"`,
            plan.registration_fee,
            plan.local_fee,
            plan.international_fee,
            plan.international_currency,
            plan.apply_discount ? plan.discount : 'N/A',
            plan.installment_plan ? 'Yes' : 'No',
            `"${plan.created_at}"`
        ];
        csv += row.join(',') + '\n';
    });

    // Download CSV
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    const url = URL.createObjectURL(blob);
    link.setAttribute('href', url);
    link.setAttribute('download', `payment_plans_${new Date().toISOString().slice(0,10)}.csv`);
    link.style.visibility = 'hidden';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

function exportToPDF(plans) {
    // Create a printable HTML version
    let printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Payment Plans Report</title>
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; margin: 20px; }
                h1 { text-align: center; color: #333; margin-bottom: 10px; }
                .info { text-align: center; margin-bottom: 20px; color: #666; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .text-right { text-align: right; }
                @media print {
                    body { margin: 0; }
                }
            </style>
        </head>
        <body>
            <h1>Payment Plans Report</h1>
            <div class="info">Generated: ${new Date().toLocaleString()}</div>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Location</th>
                        <th>Course</th>
                        <th>Intake</th>
                        <th class="text-right">Reg. Fee</th>
                        <th class="text-right">Local Fee</th>
                        <th class="text-right">Int'l Fee</th>
                        <th>Discount</th>
                    </tr>
                </thead>
                <tbody>
    `;

    plans.forEach(plan => {
        printContent += `
            <tr>
                <td>${plan.id}</td>
                <td>${plan.location}</td>
                <td>${plan.course?.course_name || '—'}</td>
                <td>${plan.intake?.batch || '—'}</td>
                <td class="text-right">${Number(plan.registration_fee).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="text-right">${Number(plan.local_fee).toLocaleString('en-US', {minimumFractionDigits: 2})}</td>
                <td class="text-right">${Number(plan.international_fee).toLocaleString('en-US', {minimumFractionDigits: 2})} ${plan.international_currency}</td>
                <td>${plan.apply_discount ? plan.discount + '%' : '—'}</td>
            </tr>
        `;
    });

    printContent += `
                </tbody>
            </table>
        </body>
        </html>
    `;

    // Open print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();

    // Trigger print after content loads
    setTimeout(() => {
        printWindow.print();
    }, 250);
}

// 1. When Location changes → update courses
document.querySelector('select[name="location"]')?.addEventListener('change', function () {
    const location = this.value;
    const courseSelect = document.getElementById('filter-course');
    const intakeSelect = document.getElementById('filter-intake');

    courseSelect.innerHTML = '<option value="">All</option>';
    intakeSelect.innerHTML = '<option value="">All</option>';
    intakeSelect.disabled = true;

    if (!location) return;

    fetch("{{ route('courses.byLocation') }}", {
        method: 'POST',
        headers: {
            'Content-Type':'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ location })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.data.length) {
            data.data.forEach(course => {
                const opt = document.createElement('option');
                opt.value = course.course_id;
                opt.textContent = course.course_name;
                courseSelect.appendChild(opt);
            });
        }
    });
});

// 2. When Course changes → update intakes
document.getElementById('filter-course')?.addEventListener('change', function () {
    const courseId = this.value;
    const location = document.querySelector('select[name="location"]').value;
    const intakeSelect = document.getElementById('filter-intake');

    intakeSelect.disabled = true;
    intakeSelect.innerHTML = '<option value="">Loading...</option>';

    if (!courseId || !location) {
        intakeSelect.innerHTML = '<option value="">All</option>';
        intakeSelect.disabled = false;
        return;
    }

    fetch("{{ route('intakes.byCourse') }}", {
        method: 'POST',
        headers: {
            'Content-Type':'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ course_id: courseId, location })
    })
    .then(res => res.json())
    .then(data => {
        console.log("Intake API response:", data);
        intakeSelect.innerHTML = '<option value="">All</option>';
        if (data.success && data.data.length) {
            data.data.forEach(intake => {
                const opt = document.createElement('option');
                opt.value = intake.intake_id;
                opt.textContent = intake.batch ? intake.batch : `Batch ${intake.intake_id}`;
                intakeSelect.appendChild(opt);
            });
        }
        intakeSelect.disabled = false;
    })
    .catch(() => {
        intakeSelect.innerHTML = '<option value="">All</option>';
        intakeSelect.disabled = false;
    });
});
</script>
@endsection
