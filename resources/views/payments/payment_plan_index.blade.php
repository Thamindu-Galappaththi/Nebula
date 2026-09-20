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
                <div class="col-12 col-lg-2">
                    <label class="form-label small d-none d-lg-block">&nbsp;</label>
                    <div class="d-flex gap-2 payment-plan-filter-actions">
                        <button type="submit" class="btn btn-primary btn-sm flex-fill">Filter</button>
                        <a href="{{ route('payment.plan.index') }}" id="clearFiltersBtn" class="btn btn-outline-secondary btn-sm flex-fill">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="card" id="paymentPlanResults">
        @include('payments.partials.payment_plan_results')
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
const paymentPlanListUrl = @json(route('payment.plan.index'));
const paymentPlanExcelUrl = @json(route('payment.plan.export.excel'));
const paymentPlanPdfUrl = @json(route('payment.plan.export.pdf'));
const paymentPlanCoursesUrl = @json(route('payment.plan.courses.byLocation'));
const paymentPlanIntakesUrl = @json(route('intakes.byCourse'));
let paymentPlanLoadToken = 0;

function jsonHeaders() {
    return {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'X-CSRF-TOKEN': '{{ csrf_token() }}',
        'X-Requested-With': 'XMLHttpRequest'
    };
}

function ajaxHeaders() {
    return {
        'Accept': 'application/json',
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

function setSelectValue(select, value) {
    if (!select) return;
    const next = value == null ? '' : String(value);
    select.value = next;
    if (select.value !== next) {
        select.selectedIndex = 0;
    }
    syncCustomSelect(select);
}

function currentFilterParams() {
    const form = document.getElementById('filterForm');
    const params = new URLSearchParams(form ? new FormData(form) : undefined);
    const perPage = document.getElementById('perPageSelect');
    if (perPage && perPage.value) {
        params.set('per_page', perPage.value);
    }
    Array.from(params.entries()).forEach(function ([key, value]) {
        if (!String(value).trim()) {
            params.delete(key);
        }
    });
    params.delete('page');
    return params;
}

function currentListUrl() {
    const query = currentFilterParams().toString();
    return query ? (paymentPlanListUrl + '?' + query) : paymentPlanListUrl;
}

function updateExportLinks(url) {
    const query = new URL(url, window.location.origin).search;
    const excel = document.querySelector('#payment-plan-index .payment-plan-header-actions a.btn-success');
    const pdf = document.querySelector('#payment-plan-index .payment-plan-header-actions a.btn-danger');
    if (excel) excel.setAttribute('href', paymentPlanExcelUrl + query);
    if (pdf) pdf.setAttribute('href', paymentPlanPdfUrl + query);
}

function syncPerPageHidden() {
    const perPage = document.getElementById('perPageSelect');
    const hidden = document.querySelector('#filterForm input[name="per_page"]');
    if (perPage && hidden) {
        hidden.value = perPage.value || '10';
    }
}

function loadPaymentPlans(url, pushUrl) {
    const results = document.getElementById('paymentPlanResults');
    if (!results) return Promise.resolve();

    const token = ++paymentPlanLoadToken;
    results.classList.add('opacity-50');

    return fetch(url, {
        method: 'GET',
        credentials: 'same-origin',
        headers: ajaxHeaders()
    })
    .then(function (res) {
        if (!res.ok) throw new Error('Failed to load payment plans');
        return res.json();
    })
    .then(function (payload) {
        if (token !== paymentPlanLoadToken) return;
        if (payload && payload.html) {
            results.innerHTML = payload.html;
        }
        if (pushUrl) {
            history.pushState({ paymentPlanAjax: true }, '', url);
        }
        updateExportLinks(url);
        syncPerPageHidden();
    })
    .catch(function () {
        if (token !== paymentPlanLoadToken) return;
        window.alert('Failed to load payment plans.');
    })
    .finally(function () {
        if (token === paymentPlanLoadToken) {
            results.classList.remove('opacity-50');
        }
    });
}

function fillSelectOptions(select, items, valueKey, labelFn) {
    if (!select) return;
    const current = select.value;
    select.innerHTML = '<option value="">All</option>';
    items.forEach(function (item) {
        const opt = document.createElement('option');
        opt.value = item[valueKey];
        opt.textContent = labelFn(item);
        select.appendChild(opt);
    });
    if (current && Array.from(select.options).some(function (opt) { return opt.value === current; })) {
        select.value = current;
    }
    select.disabled = false;
    syncCustomSelect(select);
}

function loadCourses(location, then) {
    const courseSelect = document.getElementById('filter-course');
    const intakeSelect = document.getElementById('filter-intake');
    if (!courseSelect || !intakeSelect) {
        if (then) then();
        return;
    }

    intakeSelect.innerHTML = '<option value="">All</option>';
    syncCustomSelect(intakeSelect);

    fetch(paymentPlanCoursesUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: jsonHeaders(),
        body: JSON.stringify({ location: location || '' })
    })
    .then(function (res) {
        if (!res.ok) throw new Error('Failed to load courses');
        return res.json();
    })
    .then(function (payload) {
        fillSelectOptions(courseSelect, courseListFromPayload(payload), 'course_id', function (course) {
            return course.course_name;
        });
        if (then) then();
    })
    .catch(function () {
        syncCustomSelect(courseSelect);
        if (then) then();
    });
}

function loadIntakes(courseId, location, then) {
    const intakeSelect = document.getElementById('filter-intake');
    if (!intakeSelect) {
        if (then) then();
        return;
    }

    if (!courseId) {
        intakeSelect.innerHTML = '<option value="">All</option>';
        intakeSelect.disabled = false;
        syncCustomSelect(intakeSelect);
        if (then) then();
        return;
    }

    intakeSelect.innerHTML = '<option value="">Loading...</option>';
    intakeSelect.disabled = true;
    syncCustomSelect(intakeSelect);

    fetch(paymentPlanIntakesUrl, {
        method: 'POST',
        credentials: 'same-origin',
        headers: jsonHeaders(),
        body: JSON.stringify({ course_id: courseId, location: location || '' })
    })
    .then(function (res) {
        if (!res.ok) throw new Error('Failed to load intakes');
        return res.json();
    })
    .then(function (data) {
        const intakes = Array.isArray(data.data) ? data.data : [];
        fillSelectOptions(intakeSelect, intakes, 'intake_id', function (intake) {
            return intake.batch ? intake.batch : ('Batch ' + intake.intake_id);
        });
        if (then) then();
    })
    .catch(function () {
        intakeSelect.innerHTML = '<option value="">All</option>';
        intakeSelect.disabled = false;
        syncCustomSelect(intakeSelect);
        if (then) then();
    });
}

function resetFilters() {
    setSelectValue(document.getElementById('filter-location'), '');
    setSelectValue(document.getElementById('sortSelect'), 'newest');
    const hidden = document.querySelector('#filterForm input[name="per_page"]');
    if (hidden) hidden.value = '10';
    const perPage = document.getElementById('perPageSelect');
    if (perPage) setSelectValue(perPage, '10');
    const courseSelect = document.getElementById('filter-course');
    const intakeSelect = document.getElementById('filter-intake');
    if (courseSelect) {
        courseSelect.innerHTML = '<option value="">All</option>';
        syncCustomSelect(courseSelect);
    }
    if (intakeSelect) {
        intakeSelect.innerHTML = '<option value="">All</option>';
        syncCustomSelect(intakeSelect);
    }
    loadCourses('');
    loadPaymentPlans(paymentPlanListUrl, true);
}

document.getElementById('filterForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    loadPaymentPlans(currentListUrl(), true);
});

document.getElementById('clearFiltersBtn')?.addEventListener('click', function (e) {
    e.preventDefault();
    resetFilters();
});

document.getElementById('filter-location')?.addEventListener('change', function () {
    const courseSelect = document.getElementById('filter-course');
    const intakeSelect = document.getElementById('filter-intake');
    if (courseSelect) {
        courseSelect.innerHTML = '<option value="">All</option>';
        syncCustomSelect(courseSelect);
    }
    if (intakeSelect) {
        intakeSelect.innerHTML = '<option value="">All</option>';
        syncCustomSelect(intakeSelect);
    }
    loadCourses(this.value);
});

document.getElementById('filter-course')?.addEventListener('change', function () {
    const location = document.getElementById('filter-location')?.value || '';
    loadIntakes(this.value, location);
});

document.addEventListener('change', function (e) {
    if (e.target.id === 'sortSelect') {
        loadPaymentPlans(currentListUrl(), true);
    }
    if (e.target.id === 'perPageSelect') {
        syncPerPageHidden();
        loadPaymentPlans(currentListUrl(), true);
    }
});

document.addEventListener('submit', function (e) {
    if (e.target && e.target.id === 'perPageForm') {
        e.preventDefault();
        syncPerPageHidden();
        loadPaymentPlans(currentListUrl(), true);
    }
});

document.addEventListener('click', function (e) {
    const link = e.target.closest('#paymentPlanResults .pagination a.page-link');
    if (!link) return;

    const href = link.getAttribute('href');
    const item = link.closest('.page-item');
    if (!href || href === '#' || (item && (item.classList.contains('disabled') || item.classList.contains('active')))) {
        e.preventDefault();
        return;
    }

    e.preventDefault();
    loadPaymentPlans(href, true);
});

window.addEventListener('popstate', function () {
    if (!document.getElementById('payment-plan-index')) return;
    loadPaymentPlans(window.location.href, false);
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
    overflow-wrap: break-word;
}
#paymentPlanResults {
    transition: opacity 0.15s ease;
}
#paymentPlanResults.opacity-50 {
    pointer-events: none;
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
.payment-plan-results-summary,
.payment-plan-results-count {
    overflow-wrap: break-word;
    word-break: normal;
}
.payment-plan-course-name,
.payment-plan-fee {
    overflow-wrap: break-word;
    word-break: normal;
    white-space: normal;
}
.payment-plan-installment-list {
    display: grid;
    gap: 0.65rem;
}
.payment-plan-installment-item {
    border: 1px solid #e5e7eb;
    border-radius: 10px;
    padding: 0.75rem 0.85rem;
    background: #fff;
}
.payment-plan-installment-item > div + div {
    margin-top: 0.4rem;
}
.payment-plan-installment-label {
    display: block;
    font-size: 0.75rem;
    font-weight: 700;
    color: #64748b;
    margin-bottom: 2px;
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
    .payment-plan-filter-actions {
        width: 100%;
    }
    .payment-plan-footer {
        flex-direction: column;
        align-items: stretch;
    }
    .payment-plan-pagination .pagination {
        justify-content: center;
    }
    .payment-plan-installments-toggle {
        white-space: normal;
        min-height: 2.4rem;
    }
}
</style>
@endsection
