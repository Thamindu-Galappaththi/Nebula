@php
    $campusLabel = $campusLabel ?? function ($loc) {
        return in_array($loc, ['Welisara', 'Moratuwa', 'Peradeniya'], true)
            ? 'Nebula Institute of Technology - ' . $loc
            : ($loc ?: '—');
    };
    $allPlans = $plans->items();
    $totalCount = $plans->total();
    $currentPage = $plans->currentPage();
    $perPage = $plans->perPage();
    $from = ($currentPage - 1) * $perPage + 1;
    $to = min($currentPage * $perPage, $totalCount);
@endphp

<div class="card-body p-0 p-md-3">
    @if($totalCount > 0)
        <div class="d-none d-lg-block px-3 py-2 bg-light border-bottom payment-plan-results-summary">
            <small class="text-muted">
                Showing {{ $from }}–{{ $to }} of {{ $totalCount }} results
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
            <div class="px-3 py-2 bg-light border-bottom payment-plan-results-summary">
                <small class="text-muted">
                    Showing {{ $from }}–{{ $to }} of {{ $totalCount }} results
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
            <div class="border-bottom p-3 payment-plan-mobile-card">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                    <div class="d-flex flex-wrap gap-1 min-w-0">
                        <span class="badge bg-secondary">#{{ $plan->id }}</span>
                        <span class="badge bg-info">{{ $campusLabel($plan->location) }}</span>
                    </div>
                    <a href="{{ route('payment.plan.edit',$plan->id) }}" class="btn btn-sm btn-warning flex-shrink-0">Edit</a>
                </div>

                <div class="mb-2 min-w-0">
                    <strong class="d-block payment-plan-course-name">{{ optional($plan->course)->course_name ?? '—' }}</strong>
                    <small class="text-muted">{{ optional($plan->intake)->batch ?? '—' }}</small>
                </div>

                <div class="row g-2 small mb-2">
                    <div class="col-6 min-w-0">
                        <div class="text-muted">Reg. Fee</div>
                        <strong class="payment-plan-fee">LKR {{ number_format((float) $plan->registration_fee, 2) }}</strong>
                    </div>
                    <div class="col-6 min-w-0">
                        <div class="text-muted">Local Fee</div>
                        <strong class="payment-plan-fee">LKR {{ number_format((float) $plan->local_fee, 2) }}</strong>
                    </div>
                    <div class="col-6 min-w-0">
                        <div class="text-muted">Franchise</div>
                        <strong class="payment-plan-fee">{{ number_format((float) $plan->international_fee, 2) }} {{ $plan->international_currency }}</strong>
                    </div>
                    <div class="col-6 min-w-0">
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
                    <button class="btn btn-sm btn-outline-secondary w-100 payment-plan-installments-toggle"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#inst-mobile-{{ $plan->id }}"
                            aria-expanded="false"
                            aria-controls="inst-mobile-{{ $plan->id }}">
                        View {{ $count }} Installments
                    </button>
                    @if($count)
                        <div class="small text-muted mt-1">{{ $firstDue }} → {{ $lastDue }}</div>
                    @endif

                    <div class="collapse mt-2" id="inst-mobile-{{ $plan->id }}">
                        <div class="payment-plan-installment-list">
                            @forelse($items as $it)
                                <div class="payment-plan-installment-item">
                                    <div>
                                        <span class="payment-plan-installment-label">#</span>
                                        {{ $it['installment_number'] ?? '—' }}
                                    </div>
                                    <div>
                                        <span class="payment-plan-installment-label">Due Date</span>
                                        {{ $it['due_date'] ?? '—' }}
                                    </div>
                                    <div>
                                        <span class="payment-plan-installment-label">Local (LKR)</span>
                                        {{ number_format((float)($it['local_amount'] ?? 0), 2) }}
                                    </div>
                                    <div>
                                        <span class="payment-plan-installment-label">Intl ({{ $plan->international_currency }})</span>
                                        {{ number_format((float)($it['international_amount'] ?? 0), 2) }}
                                    </div>
                                    <div>
                                        <span class="payment-plan-installment-label">Tax</span>
                                        @if(!empty($it['apply_tax']))
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted small">No installments found</div>
                            @endforelse
                            @if($count)
                                <div class="payment-plan-installment-item fw-semibold">
                                    <div>
                                        <span class="payment-plan-installment-label">Local total</span>
                                        {{ number_format($totalLocal, 2) }}
                                    </div>
                                    <div>
                                        <span class="payment-plan-installment-label">Intl total</span>
                                        {{ number_format($totalIntl, 2) }}
                                    </div>
                                </div>
                            @endif
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
            <div class="small text-muted align-self-center payment-plan-results-count">
                Showing {{ $plans->firstItem() }}–{{ $plans->lastItem() }} of {{ $plans->total() }} results
            </div>
            <nav class="payment-plan-pagination" aria-label="Payment plan pages">
                {{ $plans->onEachSide(1)->links('pagination::bootstrap-5') }}
            </nav>
        </div>
    @endif
</div>
