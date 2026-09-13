@extends('inc.app')

@section('title', 'NEBULA | Payment Plans')

@section('content')
<div id="payment-plan-index" class="container-fluid px-2 px-md-3">
    @php
        $campusLabel = function ($loc) {
            return in_array($loc, ['Welisara', 'Moratuwa', 'Peradeniya'], true)
                ? 'Nebula Institute of Technology - ' . $loc
                : ($loc ?: '—');
        };
    @endphp
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
                <div class="d-flex flex-wrap gap-2 payment-plan-header-actions">
                    <a href="{{ route('payment.plan.export.excel', request()->query()) }}" class="btn btn-success btn-sm">
                        <i class="bi bi-file-earmark-spreadsheet"></i> Excel
                    </a>
                    <a href="{{ route('payment.plan.export.pdf', request()->query()) }}" class="btn btn-danger btn-sm">
                        <i class="bi bi-file-earmark-pdf"></i> PDF
                    </a>
                </div>
            </div>

            <form method="GET" action="{{ route('payment.plan.index') }}" id="filterForm" class="row gy-2 gx-2 gx-md-3 align-items-end">
                <input type="hidden" name="per_page" value="{{ $plans->perPage() }}">
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label small">Location</label>
                    <select name="location" id="filter-location" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($locations as $loc)
                            <option value="{{ $loc }}" @selected(request('location')===$loc)>{{ $campusLabel($loc) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-3">
                    <label class="form-label small">Course</label>
                    <select name="course_id" id="filter-course" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($courses as $c)
                            <option value="{{ $c->course_id }}" @selected((string)request('course_id')===(string)$c->course_id)>
                                {{ $c->course_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small">Intake</label>
                    <select name="intake_id" id="filter-intake" class="form-select form-select-sm">
                        <option value="">All</option>
                        @foreach($intakes as $i)
                            <option value="{{ $i->intake_id }}" @selected((string)request('intake_id')===(string)$i->intake_id)>
                                {{ $i->batch ?? 'Batch ' . $i->intake_id }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2">
                    <label class="form-label small">Sort By</label>
                    <select name="sort" class="form-select form-select-sm" id="sortSelect">
                        <option value="newest" @selected(request('sort', 'newest')==='newest')>Newest First</option>
                        <option value="oldest" @selected(request('sort')==='oldest')>Oldest First</option>
                        <option value="location_asc" @selected(request('sort')==='location_asc')>Location A-Z</option>
                        <option value="location_desc" @selected(request('sort')==='location_desc')>Location Z-A</option>
                    </select>
                </div>
                <div class="col-12 col-sm-6 col-lg-2 d-grid">
                    <button class="btn btn-primary btn-sm w-100">Filter</button>
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
            <div class="d-none d-lg-block payment-plan-table-scroll">
                <table class="table table-bordered align-middle mb-0 payment-plan-table">
                    <thead class="table-light">
                        <tr>
                            <th class="col-id">#</th>
                            <th class="col-location">Location</th>
                            <th class="col-course">Course</th>
                            <th class="col-intake">Intake</th>
                            <th class="col-num text-end">Reg. Fee (LKR)</th>
                            <th class="col-num text-end">Local Fee (LKR)</th>
                            <th class="col-num text-end">Franchise</th>
                            <th class="col-discount">Discount</th>
                            <th class="col-installments">Installments</th>
                            <th class="col-date">Created</th>
                            <th class="col-actions">Actions</th>
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
                            <td class="col-id">{{ $plan->id }}</td>
                            <td class="col-location">{{ $campusLabel($plan->location) }}</td>
                            <td class="col-course">{{ optional($plan->course)->course_name ?? '—' }}</td>
                            <td class="col-intake">{{ optional($plan->intake)->batch ?? '—' }}</td>
                            <td class="col-num text-end">{{ number_format((float) $plan->registration_fee, 2) }}</td>
                            <td class="col-num text-end">{{ number_format((float) $plan->local_fee, 2) }}</td>
                            <td class="col-num text-end">
                                {{ number_format((float) $plan->international_fee, 2) }}
                                <span class="text-muted">{{ $plan->international_currency }}</span>
                            </td>
                            <td class="col-discount">
                                @if($plan->apply_discount)
                                    {{ rtrim(rtrim(number_format($plan->discount ?? 0, 2, '.', ''), '0'), '.') }}%
                                @else
                                    —
                                @endif
                            </td>
                            <td class="col-installments">
                                @if($plan->installment_plan)
                                    <button class="btn btn-sm btn-outline-secondary text-nowrap"
                                            type="button"
                                            data-bs-toggle="collapse"
                                            data-bs-target="#inst-{{ $plan->id }}">
                                        View {{ $count }}
                                    </button>
                                    @if($count)
                                        <div class="small text-muted mt-1 text-nowrap">{{ $firstDue }} → {{ $lastDue }}</div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="col-date">{{ $plan->created_at?->format('Y-m-d H:i') }}</td>
                            <td class="col-actions">
                                <a href="{{ route('payment.plan.edit',$plan->id) }}" class="btn btn-sm btn-warning">Edit</a>
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
                            Showing {{ $from }} to {{ $to }} of {{ $totalCount }}
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
                                <span class="badge bg-info text-wrap">{{ $campusLabel($plan->location) }}</span>
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
                                <strong class="text-nowrap">LKR {{ number_format((float) $plan->registration_fee, 2) }}</strong>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Local Fee</div>
                                <strong class="text-nowrap">LKR {{ number_format((float) $plan->local_fee, 2) }}</strong>
                            </div>
                            <div class="col-6">
                                <div class="text-muted">Franchise</div>
                                <strong class="text-nowrap">{{ number_format((float) $plan->international_fee, 2) }} {{ $plan->international_currency }}</strong>
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
                                    <table class="table table-sm table-striped mb-0 payment-plan-installments">
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
                                                    <td data-label="#">{{ $it['installment_number'] ?? '' }}</td>
                                                    <td class="small" data-label="Due Date">{{ $it['due_date'] ?? '' }}</td>
                                                    <td class="text-end small" data-label="Local">{{ number_format((float)($it['local_amount'] ?? 0), 0) }}</td>
                                                    <td class="text-end small" data-label="Intl">{{ number_format((float)($it['international_amount'] ?? 0), 0) }}</td>
                                                    <td data-label="Tax">
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
                                                <td colspan="2" data-label="Totals">Totals:</td>
                                                <td class="text-end" data-label="Local">{{ number_format($totalLocal, 0) }}</td>
                                                <td class="text-end" data-label="Intl">{{ number_format($totalIntl, 0) }}</td>
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
            @if($totalCount > 0)
                <div class="payment-plan-footer p-3">
                    <form method="GET" action="{{ route('payment.plan.index') }}" id="perPageForm" class="payment-plan-page-size">
                        @foreach(['location', 'course_id', 'intake_id', 'sort'] as $filterKey)
                            @if(request()->filled($filterKey))
                                <input type="hidden" name="{{ $filterKey }}" value="{{ request($filterKey) }}">
                            @endif
                        @endforeach
                        <label class="form-label mb-0 small text-muted" for="perPageSelect">Per page</label>
                        <select name="per_page" id="perPageSelect" class="form-select form-select-sm page-size-select">
                            <option value="10" @selected((int) $plans->perPage() === 10)>10</option>
                            <option value="25" @selected((int) $plans->perPage() === 25)>25</option>
                            <option value="50" @selected((int) $plans->perPage() === 50)>50</option>
                            <option value="100" @selected((int) $plans->perPage() === 100)>100</option>
                        </select>
                    </form>
                    <div class="small text-muted align-self-center">
                        Showing {{ $plans->firstItem() }} to {{ $plans->lastItem() }} of {{ $plans->total() }}
                    </div>
                    <nav class="payment-plan-pagination" aria-label="Payment plan pages">
                        {{ $plans->onEachSide(1)->links('pagination::bootstrap-5') }}
                    </nav>
                </div>
            @endif
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
document.addEventListener('change', function(e) {
    if (e.target.id === 'sortSelect') {
        e.target.form.submit();
    }
    if (e.target.id === 'perPageSelect') {
        e.target.form.submit();
    }
});

function jsonHeaders() {
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'X-Requested-With': 'XMLHttpRequest'
    };
}

function courseListFromPayload(payload) {
    if (payload && Array.isArray(payload.data)) return payload.data;
    if (payload && Array.isArray(payload.courses)) return payload.courses;
    return [];
}

function syncCustomSelect(select) {
    if (!select) return;
    const selected = select.options[select.selectedIndex];
    const wrap = select.closest('.nebula-select');
    const toggle = wrap ? wrap.querySelector('.nebula-select-toggle') : null;
    if (toggle) {
        toggle.textContent = selected ? selected.text : '';
        toggle.title = toggle.textContent;
        toggle.disabled = !!select.disabled;
        wrap.classList.toggle('is-disabled', !!select.disabled);
    }
}

document.getElementById('filter-location')?.addEventListener('change', function () {
    const location = this.value;
    const courseSelect = document.getElementById('filter-course');
    const intakeSelect = document.getElementById('filter-intake');

    courseSelect.innerHTML = '<option value="">All</option>';
    intakeSelect.innerHTML = '<option value="">All</option>';
    syncCustomSelect(courseSelect);
    syncCustomSelect(intakeSelect);

    fetch("{{ route('payment.plan.courses.byLocation') }}", {
        method: 'POST',
        credentials: 'same-origin',
        headers: jsonHeaders(),
        body: JSON.stringify({ location })
    })
    .then(res => {
        if (!res.ok) throw new Error('Failed to load courses');
        return res.json();
    })
    .then(payload => {
        courseListFromPayload(payload).forEach(course => {
            const opt = document.createElement('option');
            opt.value = course.course_id;
            opt.textContent = course.course_name;
            courseSelect.appendChild(opt);
        });
        syncCustomSelect(courseSelect);
    })
    .catch(() => syncCustomSelect(courseSelect));
});

document.getElementById('filter-course')?.addEventListener('change', function () {
    const courseId = this.value;
    const location = document.getElementById('filter-location')?.value || '';
    const intakeSelect = document.getElementById('filter-intake');

    intakeSelect.innerHTML = '<option value="">All</option>';
    syncCustomSelect(intakeSelect);

    if (!courseId) {
        return;
    }

    intakeSelect.innerHTML = '<option value="">Loading...</option>';
    intakeSelect.disabled = true;
    syncCustomSelect(intakeSelect);

    fetch("{{ route('intakes.byCourse') }}", {
        method: 'POST',
        credentials: 'same-origin',
        headers: jsonHeaders(),
        body: JSON.stringify({ course_id: courseId, location })
    })
    .then(res => {
        if (!res.ok) throw new Error('Failed to load intakes');
        return res.json();
    })
    .then(data => {
        intakeSelect.innerHTML = '<option value="">All</option>';
        const intakes = Array.isArray(data.data) ? data.data : [];
        intakes.forEach(intake => {
            const opt = document.createElement('option');
            opt.value = intake.intake_id;
            opt.textContent = intake.batch ? intake.batch : ('Batch ' + intake.intake_id);
            intakeSelect.appendChild(opt);
        });
        intakeSelect.disabled = false;
        syncCustomSelect(intakeSelect);
    })
    .catch(() => {
        intakeSelect.innerHTML = '<option value="">All</option>';
        intakeSelect.disabled = false;
        syncCustomSelect(intakeSelect);
    });
});
</script>

<style nonce="{{ $cspNonce }}">
#payment-plan-index,
#payment-plan-index .card,
#payment-plan-index .card-body,
#payment-plan-index .card-header {
    min-width: 0;
    max-width: 100%;
    overflow: visible;
    height: auto;
    transform: none !important;
}
body:has(#payment-plan-index) .body-wrapper > .container-fluid {
    overflow: visible;
}
#payment-plan-index [class*="col-"] {
    min-width: 0;
}
#payment-plan-index .form-select,
#payment-plan-index .form-control,
#payment-plan-index .nebula-select,
#payment-plan-index .nebula-select-toggle {
    width: 100%;
    max-width: 100%;
}
#payment-plan-index .nebula-select-sm {
    width: 100%;
    max-width: 100%;
    flex: 1 1 auto;
}
#payment-plan-index h2 {
    overflow-wrap: anywhere;
}
#payment-plan-index .card {
    transition: box-shadow 0.2s;
}
#payment-plan-index .card:hover {
    transform: none !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
}
.payment-plan-header-actions .btn {
    min-width: 0;
}
.payment-plan-table-scroll {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
}
.payment-plan-table-scroll::-webkit-scrollbar {
    height: 10px;
}
.payment-plan-table-scroll::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 8px;
}
.payment-plan-table-scroll::-webkit-scrollbar-thumb {
    background: #b0b0b0;
    border-radius: 8px;
}
.payment-plan-table {
    min-width: 1280px;
    width: 100%;
    table-layout: auto;
}
.payment-plan-table th,
.payment-plan-table td {
    vertical-align: middle;
    overflow-wrap: normal;
    word-break: normal;
}
.payment-plan-table th {
    white-space: nowrap;
}
.payment-plan-table .col-id,
.payment-plan-table .col-num,
.payment-plan-table .col-discount,
.payment-plan-table .col-date,
.payment-plan-table .col-actions,
.payment-plan-table .col-intake {
    white-space: nowrap;
}
.payment-plan-table .col-id {
    width: 1%;
    text-align: right;
}
.payment-plan-table .col-location {
    min-width: 220px;
    max-width: 280px;
    white-space: normal;
}
.payment-plan-table .col-course {
    min-width: 220px;
    max-width: 320px;
    white-space: normal;
}
.payment-plan-table .col-installments {
    min-width: 150px;
}
.payment-plan-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
    flex-wrap: wrap;
    min-width: 0;
}
.payment-plan-page-size {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin: 0;
}
.payment-plan-page-size select.page-size-select,
.payment-plan-page-size .nebula-select,
#payment-plan-index select#perPageSelect {
    width: 5.75rem;
    max-width: 5.75rem;
    flex: 0 0 5.75rem;
}
.payment-plan-pagination {
    min-width: 0;
    overflow-x: auto;
}
.payment-plan-pagination .pagination {
    flex-wrap: wrap;
    justify-content: flex-end;
    margin-bottom: 0;
}
#payment-plan-index .badge {
    white-space: normal;
    text-align: left;
}
@media (max-width: 767.98px) {
    #payment-plan-index h2 {
        font-size: 1.25rem;
    }
    #payment-plan-index .form-control,
    #payment-plan-index .form-select,
    #payment-plan-index .form-select-sm,
    #payment-plan-index .nebula-select-toggle {
        font-size: 16px;
    }
    .payment-plan-header-actions,
    .payment-plan-header-actions .btn {
        width: 100%;
    }
    .payment-plan-footer {
        flex-direction: column;
        align-items: stretch;
    }
    .payment-plan-pagination .pagination {
        justify-content: center;
    }
    #payment-plan-index .payment-plan-installments thead {
        display: none;
    }
    #payment-plan-index .payment-plan-installments,
    #payment-plan-index .payment-plan-installments tbody,
    #payment-plan-index .payment-plan-installments tfoot,
    #payment-plan-index .payment-plan-installments tr,
    #payment-plan-index .payment-plan-installments td {
        display: block;
        width: 100%;
    }
    #payment-plan-index .payment-plan-installments tr {
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 0.5rem;
        padding-bottom: 0.5rem;
    }
    #payment-plan-index .payment-plan-installments td {
        text-align: left !important;
        padding: 0.35rem 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }
    #payment-plan-index .payment-plan-installments td[data-label]::before {
        content: attr(data-label);
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 2px;
        text-transform: uppercase;
        letter-spacing: 0.02em;
    }
}
</style>
@endsection
