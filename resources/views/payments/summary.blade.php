@extends('inc.app')

@section('title', 'Payment Dashboard - Advanced Analytics')

@section('content')
<div id="payment-summary" class="container-fluid px-2 px-md-3 mt-4 mb-5">
    {{-- Header with Actions --}}
    <div class="card shadow-sm border-0 mb-4" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="card-body d-flex flex-column flex-lg-row justify-content-between align-items-start align-items-lg-center gap-3 text-white">
            <div>
                <h2 class="mb-1">💰 Payment Analytics Dashboard</h2>
                <p class="text-white-50 mb-0">Real-time insights and comprehensive reports</p>
            </div>
            <div class="d-flex flex-wrap gap-2 payment-summary-header-actions">
                <a href="{{ route('payment.analytics') }}" class="btn btn-outline-light">
                    <i class="bi bi-graph-up"></i> Advanced Analytics
                </a>
                <a href="{{ route('payment.comparison') }}" class="btn btn-outline-light">
                    <i class="bi bi-bar-chart"></i> Comparison
                </a>
                <button class="btn btn-light btn-export-data">
                    <i class="bi bi-file-earmark-pdf"></i> Export PDF
                </button>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small text-muted">Time Range</label>
                    <select class="form-select" id="rangeFilter" name="range">
                        <option value="1w">Last Week</option>
                        <option value="1m">Last Month</option>
                        <option value="3m">Last 3 Months</option>
                        <option value="6m">Last 6 Months</option>
                        <option value="1y">Last Year</option>
                        <option value="2y">Last 2 Years</option>
                        <option value="5y">Last 5 Years</option>
                        <option value="10y" selected>All Time</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Payment Method</label>
                    <select class="form-select" id="methodFilter" name="payment_method">
                        <option value="">All Methods</option>
                        <option value="cash">Cash</option>
                        <option value="cheque">Cheque</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="online">Online</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status</label>
                    <select class="form-select" id="statusFilter" name="status">
                        <option value="">All Status</option>
                        <option value="paid">Paid</option>
                        <option value="pending">Pending</option>
                        <option value="failed">Failed</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Student ID / NIC</label>
                    <input type="text" class="form-control" id="studentFilter" name="student_id" placeholder="Enter Student ID or NIC">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">From Collection Date</label>
                    <input type="date" class="form-control" id="startDateFilter" name="start_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">To Collection Date</label>
                    <input type="date" class="form-control" id="endDateFilter" name="end_date">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Location</label>
                    <select class="form-select" id="locationFilter" name="location">
                        <option value="">All Locations</option>
                        <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                        <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                        <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Course</label>
                    <select class="form-select" id="courseFilter" name="course_id">
                        <option value="">All Courses</option>
                        @foreach(($courses ?? []) as $course)
                            <option value="{{ $course->course_id }}">{{ $course->course_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Intake</label>
                    <select class="form-select" id="intakeFilter" name="intake_id">
                        <option value="">All Intakes</option>
                        @foreach(($intakes ?? []) as $intake)
                            <option value="{{ $intake->intake_id }}">{{ $intake->batch }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Methods/Types Scope</label>
                    <select class="form-select" id="breakdownScopeFilter" name="breakdown_scope">
                        <option value="paid" {{ ($breakdownScope ?? 'paid') === 'paid' ? 'selected' : '' }}>Paid Only</option>
                        <option value="all" {{ ($breakdownScope ?? 'paid') === 'all' ? 'selected' : '' }}>All Statuses (Audit)</option>
                    </select>
                </div>
            </div>
            <div class="mt-3 payment-summary-filter-actions">
                <button class="btn btn-sm btn-primary btn-apply-filters">
                    <i class="bi bi-funnel"></i> Apply Filters
                </button>
                <button class="btn btn-sm btn-outline-secondary btn-reset-filters">
                    <i class="bi bi-x-circle"></i> Reset
                </button>
            </div>
        </div>
    </div>
    {{-- Primary KPIs --}}
    <div class="row g-3 mb-4" id="kpiSection">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-gradient" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small">Total Collected</p>
                            <h3 class="fw-bold mb-0">LKR {{ number_format($totalCollected, 2) }}</h3>
                            <small class="opacity-75">{{ $totalTransactions ?? 0 }} transactions</small>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded">
                            <i class="bi bi-cash-stack fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-gradient" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small">Pending Payments</p>
                            <h3 class="fw-bold mb-0">LKR {{ number_format($totalPending, 2) }}</h3>
                            <small class="opacity-75">Awaiting collection</small>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-gradient" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small">Average Transaction</p>
                            <h3 class="fw-bold mb-0">LKR {{ number_format($averageTransaction ?? 0, 2) }}</h3>
                            <small class="opacity-75">Per payment</small>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded">
                            <i class="bi bi-graph-up-arrow fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100 bg-gradient" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                <div class="card-body text-white">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <p class="mb-1 opacity-75 small">Late Fees</p>
                            <h3 class="fw-bold mb-0">LKR {{ number_format($totalLateFee, 2) }}</h3>
                            <small class="opacity-75">Total penalties</small>
                        </div>
                        <div class="bg-white bg-opacity-25 p-3 rounded">
                            <i class="bi bi-exclamation-triangle fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Secondary Metrics --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-tag text-success fs-3 mb-2"></i>
                    <h6 class="text-muted mb-1">Total Discounts</h6>
                    <h5 class="fw-bold text-success">LKR {{ number_format($totalDiscount, 2) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-percent text-info fs-3 mb-2"></i>
                    <h6 class="text-muted mb-1">SSCL Tax</h6>
                    <h5 class="fw-bold text-info">LKR {{ number_format($ssclTaxTotal ?? 0, 2) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-bank text-warning fs-3 mb-2"></i>
                    <h6 class="text-muted mb-1">Bank Charges</h6>
                    <h5 class="fw-bold text-warning">LKR {{ number_format($bankChargesTotal ?? 0, 2) }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <i class="bi bi-currency-exchange text-primary fs-3 mb-2"></i>
                    <h6 class="text-muted mb-1">Transactions</h6>
                    <h5 class="fw-bold text-primary">{{ $totalTransactions ?? 0 }}</h5>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Installment Amount Lookup KPI ────────────────────────────────────── --}}
    <div class="card shadow-sm border-0 mb-4" id="installmentKpiCard">
        <div class="card-header bg-white border-0 pt-3 pb-0 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2">
                <div class="rounded p-2" style="background:linear-gradient(135deg,#667eea 0%,#764ba2 100%);">
                    <i class="bi bi-layers-half text-white"></i>
                </div>
                <div>
                    <h6 class="fw-bold mb-0">Installment Amount Lookup</h6>
                    <small class="text-muted">Select filters to view paid &amp; pending totals for a specific installment</small>
                </div>
            </div>
        </div>
        <div class="card-body px-4 pb-4 pt-3">

            {{-- Filter row --}}
            <div class="row g-3 align-items-end mb-3" id="kpiFilterRow">

                {{-- Location --}}
                <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label small text-muted mb-1">Location</label>
                    <select class="form-select form-select-sm" id="kpiLocation">
                        <option value="">All Locations</option>
                        <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                        <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                        <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                    </select>
                </div>

                {{-- Course --}}
                <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                    <label class="form-label small text-muted mb-1">Course</label>
                    <select class="form-select form-select-sm" id="kpiCourse" disabled>
                        <option value="">— select location first —</option>
                    </select>
                </div>

                {{-- Intake --}}
                <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label small text-muted mb-1">Intake / Batch</label>
                    <select class="form-select form-select-sm" id="kpiIntake" disabled>
                        <option value="">— select course first —</option>
                    </select>
                </div>

                {{-- Payment Type --}}
                <div class="col-xl-2 col-lg-2 col-md-4 col-sm-6">
                    <label class="form-label small text-muted mb-1">Payment Type</label>
                    <select class="form-select form-select-sm" id="kpiPaymentType">
                        <option value="">All Types</option>
                        <option value="franchise_fee">Franchise Fee</option>
                        <option value="course_fee">Course Fee</option>
                        <option value="registration_fee">Registration Fee</option>
                    </select>
                </div>

                {{-- Installment No --}}
                <div class="col-xl-1 col-lg-1 col-md-4 col-sm-6">
                    <label class="form-label small text-muted mb-1">Installment No.</label>
                    <select class="form-select form-select-sm" id="kpiInstallmentNo">
                        <option value="">All</option>
                    </select>
                </div>

                {{-- Fetch Button --}}
                <div class="col-xl-2 col-lg-2 col-md-4">
                    <button class="btn btn-sm btn-primary w-100" id="kpiFetchBtn">
                        <i class="bi bi-search me-1"></i> Get Totals
                    </button>
                </div>
            </div>

            {{-- Placeholder --}}
            <div id="kpiPlaceholder" class="text-center py-3">
                <i class="bi bi-sliders text-muted fs-3 d-block mb-1"></i>
                <p class="text-muted small mb-0">Select filters above and click <strong>Get Totals</strong> to see the installment breakdown.</p>
            </div>

            {{-- Loading --}}
            <div id="kpiLoading" class="text-center py-3 d-none">
                <div class="spinner-border text-primary" role="status" style="width:1.75rem;height:1.75rem;">
                    <span class="visually-hidden">Loading…</span>
                </div>
                <p class="text-muted small mt-2 mb-0">Fetching data…</p>
            </div>

            {{-- Error --}}
            <div id="kpiError" class="d-none">
                <div class="alert alert-danger mb-0 py-2" role="alert">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    <span id="kpiErrorMsg">Something went wrong. Please try again.</span>
                </div>
            </div>

            {{-- Results --}}
            <div id="kpiResultArea" class="d-none">
                <div class="row g-3">

                    {{-- Paid --}}
                    <div class="col-xl-4 col-md-6">
                        <div class="card border-0 shadow-sm bg-light h-100 border-start border-success" style="border-left-width: 4px !important;">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-1 text-muted small fw-bold text-uppercase" style="letter-spacing: 0.05em;">Total Collected (Paid)</p>
                                        <h4 class="fw-bold text-dark mb-0" id="kpiPaidTotal">LKR 0.00</h4>
                                        <small class="text-muted"><span id="kpiPaidCount">0</span> paid transactions</small>
                                    </div>
                                    <div class="bg-success bg-opacity-10 text-success p-2 rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                                        <i class="bi bi-check-circle-fill fs-5"></i>
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <a href="#" class="btn btn-sm btn-outline-success kpi-pdf-btn" data-status="paid" style="font-size: 0.75rem;">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Pending --}}
                    <div class="col-xl-4 col-md-6">
                        <div class="card border-0 shadow-sm bg-light h-100 border-start border-danger" style="border-left-width: 4px !important;">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-1 text-muted small fw-bold text-uppercase" style="letter-spacing: 0.05em;">Total Pending</p>
                                        <h4 class="fw-bold text-dark mb-0" id="kpiPendingTotal">LKR 0.00</h4>
                                        <small class="text-muted">Awaiting collection</small>
                                    </div>
                                    <div class="bg-danger bg-opacity-10 text-danger p-2 rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                                        <i class="bi bi-clock-history fs-5"></i>
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <a href="#" class="btn btn-sm btn-outline-danger kpi-pdf-btn" data-status="pending" style="font-size: 0.75rem;">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Grand Total --}}
                    <div class="col-xl-4 col-md-6">
                        <div class="card border-0 shadow-sm bg-light h-100 border-start border-primary" style="border-left-width: 4px !important;">
                            <div class="card-body d-flex flex-column justify-content-between">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="mb-1 text-muted small fw-bold text-uppercase" style="letter-spacing: 0.05em;">Grand Total (Paid + Pending)</p>
                                        <h4 class="fw-bold text-dark mb-0" id="kpiGrandTotal">LKR 0.00</h4>
                                        <small class="text-muted"><span id="kpiTotalCount">0</span> records matched</small>
                                    </div>
                                    <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                                        <i class="bi bi-cash-coin fs-5"></i>
                                    </div>
                                </div>
                                <div class="mt-3 text-end">
                                    <a href="#" class="btn btn-sm btn-outline-primary kpi-pdf-btn" data-status="all" style="font-size: 0.75rem;">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Export PDF
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                {{-- Active filter badges --}}
                <div class="mt-3 d-flex flex-wrap gap-2" id="kpiFilterBadges"></div>
            </div>

        </div>
    </div>
    {{-- / Installment KPI Card --}}

    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <div>
                        <h6 class="fw-bold mb-0">Sri Lanka Student Distribution</h6>
                        <small class="text-muted">District-wise registered student count</small>
                    </div>
                    <div class="d-flex gap-3 small text-muted">
                        <span><strong id="district-map-total-students">0</strong> students</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="district-map-layout">
                        <div class="district-map-panel">
                            <div class="district-map-shell">
                                <img src="{{ asset('images/general/sri-lanka-location-map.svg') }}" class="district-real-map-image" alt="Sri Lanka map">
                                <div class="district-map-markers" id="districtMapMarkers"></div>
                            </div>
                            <div class="district-map-legend mt-3">
                                <span class="legend-title">Student count</span>
                                <div class="legend-scale">
                                    <span>Low</span>
                                    <div class="legend-bar"></div>
                                    <span>High</span>
                                </div>
                            </div>
                        </div>
                        <div class="district-map-insights">
                            <div class="district-highlight-card" id="districtHighlightCard">
                                <div class="district-highlight-label">Selected district</div>
                                <div class="district-highlight-name">All districts</div>
                                <div class="district-highlight-stats">
                                    <div>
                                        <span>Students</span>
                                        <strong id="district-highlight-students">0</strong>
                                    </div>
                                </div>
                            </div>
                            <div class="table-responsive mt-3">
                                <table class="table table-sm align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>District</th>
                                            <th class="text-end">Students</th>
                                        </tr>
                                    </thead>
                                    <tbody id="districtAnalyticsTableBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">Area Insights</h6>
                </div>
                <div class="card-body">
                    <div id="districtTopList" class="district-top-list"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row 1 --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">📊 Monthly Collection Trend</h6>
                </div>
                <div class="card-body">
                    {{-- Monthly Trend KPI Sub-cards --}}
                    <div class="row g-3 mb-4 text-dark" id="monthlyMetricsContainer">
                        <div class="col-sm-4">
                            <div class="p-3 rounded bg-light border-start border-primary border-3" style="border-left-width: 4px !important;">
                                <div class="text-muted small mb-1 text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">This Month</div>
                                <div class="fs-5 fw-bold text-dark" id="monthlyThisMonthVal">LKR 0.00</div>
                                <div class="small mt-1" id="monthlyGrowthBadge">-</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 rounded bg-light border-start border-secondary border-3" style="border-left-width: 4px !important;">
                                <div class="text-muted small mb-1 text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Last Month</div>
                                <div class="fs-5 fw-bold text-dark" id="monthlyLastMonthVal">LKR 0.00</div>
                                <div class="text-muted small mt-1" id="monthlyLastMonthName">-</div>
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="p-3 rounded bg-light border-start border-info border-3" style="border-left-width: 4px !important;">
                                <div class="text-muted small mb-1 text-uppercase fw-semibold" style="font-size: 0.72rem; letter-spacing: 0.05em;">Monthly Avg</div>
                                <div class="fs-5 fw-bold text-dark" id="monthlyAvgVal">LKR 0.00</div>
                                <div class="text-muted small mt-1" id="monthlySpanLabel">Over selected range</div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="monthlyChart" height="80"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">📈 Payment Status</h6>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="statusChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts Row 2 --}}
    <div class="row g-4 mb-4">
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">💳 Payment Methods</h6>
                    <small id="methodsScopeIndicator" class="text-muted d-block mt-1">Scope: Paid Only</small>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="methodChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">📋 Payment Types</h6>
                    <small id="typesScopeIndicator" class="text-muted d-block mt-1">Scope: Paid Only</small>
                </div>
                <div class="card-body">
                    <div class="chart-wrap">
                        <canvas id="typeChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0">📅 Weekly Trend</h6>
                </div>
                <div class="card-body">
                    {{-- Weekly Trend KPI Sub-cards --}}
                    <div class="row g-2 mb-3 text-dark" id="weeklyMetricsContainer">
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border-start border-primary border-3" style="border-left-width: 4px !important; padding: 0.75rem;">
                                <div class="text-muted small mb-1 text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">This Week</div>
                                <div class="fs-6 fw-bold text-dark" id="weeklyThisWeekVal">LKR 0.00</div>
                                <div class="small mt-1" id="weeklyGrowthBadge" style="font-size: 0.75rem;">-</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-2 rounded bg-light border-start border-secondary border-3" style="border-left-width: 4px !important; padding: 0.75rem;">
                                <div class="text-muted small mb-1 text-uppercase fw-semibold" style="font-size: 0.7rem; letter-spacing: 0.05em;">Last Week</div>
                                <div class="fs-6 fw-bold text-dark" id="weeklyLastWeekVal">LKR 0.00</div>
                                <div class="text-muted small mt-1" id="weeklyLastWeekName" style="font-size: 0.72rem;">-</div>
                            </div>
                        </div>
                    </div>
                    <div class="chart-wrap">
                        <canvas id="weeklyChart" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>



</div>

{{-- Chart.js --}}
<script nonce="{{ $cspNonce }}" src="{{ asset('libs/chartjs/chart.min.js') }}"></script>

<script nonce="{{ $cspNonce }}">
// Event delegation for buttons
document.addEventListener('click', function(e) {
    if (e.target.closest('.btn-export-data')) {
        exportData();
    } else if (e.target.closest('.btn-apply-filters')) {
        applyFilters();
    } else if (e.target.closest('.btn-reset-filters')) {
        resetFilters();
    }
});

// ========== FILTER FUNCTIONS (FIXED) ==========
function applyFilters() {
    const range = document.getElementById('rangeFilter').value;
    const method = document.getElementById('methodFilter').value;
    const status = document.getElementById('statusFilter').value;
    const studentId = document.getElementById('studentFilter').value;
    const startDate = document.getElementById('startDateFilter').value;
    const endDate = document.getElementById('endDateFilter').value;
    const location = document.getElementById('locationFilter').value;
    const courseId = document.getElementById('courseFilter').value;
    const intakeId = document.getElementById('intakeFilter').value;
    const breakdownScope = document.getElementById('breakdownScopeFilter').value;

    // Build query parameters
    const params = new URLSearchParams();
    params.append('range', range);
    if (method) params.append('payment_method', method);
    if (status) params.append('status', status);
    if (studentId) params.append('student_id', studentId);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (location) params.append('location', location);
    if (courseId) params.append('course_id', courseId);
    if (intakeId) params.append('intake_id', intakeId);
    params.append('breakdown_scope', breakdownScope || 'paid');

    // Show loading indicator
    showLoading();

    // Redirect to the same page with query parameters
    window.location.href = `{{ route('payment.summary') }}?${params.toString()}`;
}

function resetFilters() {
    document.getElementById('rangeFilter').value = '10y';
    document.getElementById('methodFilter').value = '';
    document.getElementById('statusFilter').value = '';
    document.getElementById('studentFilter').value = '';
    document.getElementById('startDateFilter').value = '';
    document.getElementById('endDateFilter').value = '';
    document.getElementById('locationFilter').value = '';
    document.getElementById('courseFilter').value = '';
    document.getElementById('intakeFilter').value = '';
    document.getElementById('breakdownScopeFilter').value = 'paid';
    
    // Redirect to clean URL
    window.location.href = '{{ route("payment.summary") }}';
}

function exportData() {
    const range = document.getElementById('rangeFilter').value;
    const method = document.getElementById('methodFilter').value;
    const status = document.getElementById('statusFilter').value;
    const startDate = document.getElementById('startDateFilter').value;
    const endDate = document.getElementById('endDateFilter').value;
    const location = document.getElementById('locationFilter').value;
    const courseId = document.getElementById('courseFilter').value;
    const intakeId = document.getElementById('intakeFilter').value;
    const breakdownScope = document.getElementById('breakdownScopeFilter').value;

    const params = new URLSearchParams();
    params.append('format', 'pdf');
    params.append('range', range);
    if (method) params.append('payment_method', method);
    if (status) params.append('status', status);
    if (startDate) params.append('start_date', startDate);
    if (endDate) params.append('end_date', endDate);
    if (location) params.append('location', location);
    if (courseId) params.append('course_id', courseId);
    if (intakeId) params.append('intake_id', intakeId);
    params.append('breakdown_scope', breakdownScope || 'paid');
    
    window.location.href = `{{ route('payment.export') }}?${params.toString()}`;
}

function showLoading() {
    const overlay = document.createElement('div');
    overlay.id = 'loading-overlay';
    overlay.innerHTML = '<div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div>';
    overlay.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(255,255,255,0.8);display:flex;align-items:center;justify-content:center;z-index:9999;';
    document.body.appendChild(overlay);
}

// ========== PRESERVE FILTERS ON PAGE LOAD ==========
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const locationFilter = document.getElementById('locationFilter');
    const courseFilter = document.getElementById('courseFilter');
    const intakeFilter = document.getElementById('intakeFilter');
    const initialCourseOptions = courseFilter ? courseFilter.innerHTML : '<option value="">All Courses</option>';
    const initialIntakeOptions = intakeFilter ? intakeFilter.innerHTML : '<option value="">All Intakes</option>';

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

    async function loadCoursesByLocation(location, selectedCourseId = '') {
        if (!courseFilter) return;

        courseFilter.innerHTML = '<option value="">Loading courses...</option>';
        courseFilter.disabled = true;
        syncCustomSelect(courseFilter);

        if (intakeFilter) {
            intakeFilter.innerHTML = '<option value="">All Intakes</option>';
            intakeFilter.disabled = true;
            syncCustomSelect(intakeFilter);
        }

        if (!location) {
            courseFilter.innerHTML = initialCourseOptions;
            courseFilter.disabled = false;
            syncCustomSelect(courseFilter);
            if (intakeFilter) {
                intakeFilter.innerHTML = initialIntakeOptions;
                intakeFilter.disabled = false;
                syncCustomSelect(intakeFilter);
            }
            return;
        }

        try {
            const response = await fetch(`{{ route('payment.summary.courses.by.location') }}?location=${encodeURIComponent(location)}`);
            const payload = await response.json();

            courseFilter.innerHTML = '<option value="">All Courses</option>';
            courseListFromPayload(payload).forEach(course => {
                const option = document.createElement('option');
                option.value = String(course.course_id);
                option.textContent = course.course_name;
                if (selectedCourseId && String(course.course_id) === String(selectedCourseId)) {
                    option.selected = true;
                }
                courseFilter.appendChild(option);
            });
            courseFilter.disabled = false;
            syncCustomSelect(courseFilter);
        } catch (error) {
            console.error('Failed to load courses by location:', error);
            courseFilter.innerHTML = '<option value="">All Courses</option>';
            courseFilter.disabled = false;
            syncCustomSelect(courseFilter);
        }
    }

    async function loadIntakesByLocationAndCourse(location, courseId, selectedIntakeId = '') {
        if (!intakeFilter) return;

        intakeFilter.innerHTML = '<option value="">Loading intakes...</option>';
        intakeFilter.disabled = true;
        syncCustomSelect(intakeFilter);

        if (!location || !courseId) {
            intakeFilter.innerHTML = initialIntakeOptions;
            intakeFilter.disabled = false;
            syncCustomSelect(intakeFilter);
            return;
        }

        try {
            const response = await fetch(`{{ route('payment.summary.intakes.by.location.course') }}?location=${encodeURIComponent(location)}&course_id=${encodeURIComponent(courseId)}`);
            const payload = await response.json();

            intakeFilter.innerHTML = '<option value="">All Intakes</option>';
            if (payload.success && Array.isArray(payload.data)) {
                payload.data.forEach(intake => {
                    const option = document.createElement('option');
                    option.value = String(intake.intake_id);
                    option.textContent = intake.intake_name;
                    if (selectedIntakeId && String(intake.intake_id) === String(selectedIntakeId)) {
                        option.selected = true;
                    }
                    intakeFilter.appendChild(option);
                });
            }
            intakeFilter.disabled = false;
            syncCustomSelect(intakeFilter);
        } catch (error) {
            console.error('Failed to load intakes by location and course:', error);
            intakeFilter.innerHTML = '<option value="">All Intakes</option>';
            intakeFilter.disabled = false;
            syncCustomSelect(intakeFilter);
        }
    }

    function updateBreakdownScopeIndicator() {
        const scopeSelect = document.getElementById('breakdownScopeFilter');
        const methodsIndicator = document.getElementById('methodsScopeIndicator');
        const typesIndicator = document.getElementById('typesScopeIndicator');
        if (!scopeSelect || !methodsIndicator || !typesIndicator) return;

        const isAll = scopeSelect.value === 'all';
        const scopeLabel = isAll ? 'All Statuses (Audit)' : 'Paid Only';
        methodsIndicator.textContent = 'Scope: ' + scopeLabel;
        typesIndicator.textContent = 'Scope: ' + scopeLabel;
    }
    
    if (urlParams.has('student_id')) {
        document.getElementById('studentFilter').value = urlParams.get('student_id');
    }
    if (urlParams.has('range')) {
        document.getElementById('rangeFilter').value = urlParams.get('range');
    }
    if (urlParams.has('payment_method')) {
        document.getElementById('methodFilter').value = urlParams.get('payment_method');
    }
    if (urlParams.has('status')) {
        document.getElementById('statusFilter').value = urlParams.get('status');
    }
    if (urlParams.has('start_date')) {
        document.getElementById('startDateFilter').value = urlParams.get('start_date');
    }
    if (urlParams.has('end_date')) {
        document.getElementById('endDateFilter').value = urlParams.get('end_date');
    }
    if (urlParams.has('location')) {
        document.getElementById('locationFilter').value = urlParams.get('location');
    }
    const selectedCourseId = urlParams.get('course_id') || '';
    const selectedIntakeId = urlParams.get('intake_id') || '';
    if (urlParams.has('breakdown_scope')) {
        document.getElementById('breakdownScopeFilter').value = urlParams.get('breakdown_scope');
    }

    [
        'rangeFilter', 'methodFilter', 'statusFilter', 'locationFilter',
        'courseFilter', 'intakeFilter', 'breakdownScopeFilter'
    ].forEach(function (id) {
        syncCustomSelect(document.getElementById(id));
    });

    (async function initializeDependentFilters() {
        const selectedLocation = locationFilter ? locationFilter.value : '';
        if (selectedLocation) {
            await loadCoursesByLocation(selectedLocation, selectedCourseId);
        } else if (selectedCourseId && courseFilter) {
            courseFilter.value = selectedCourseId;
        }

        if (selectedLocation && (selectedCourseId || (courseFilter && courseFilter.value))) {
            const courseValue = selectedCourseId || (courseFilter ? courseFilter.value : '');
            await loadIntakesByLocationAndCourse(selectedLocation, courseValue, selectedIntakeId);
        } else if (selectedIntakeId && intakeFilter) {
            intakeFilter.value = selectedIntakeId;
        }
    })();

    if (locationFilter) {
        locationFilter.addEventListener('change', async function() {
            await loadCoursesByLocation(this.value);
        });
    }

    if (courseFilter) {
        courseFilter.addEventListener('change', async function() {
            const location = locationFilter ? locationFilter.value : '';
            await loadIntakesByLocationAndCourse(location, this.value);
        });
    }


    updateBreakdownScopeIndicator();

    const rangeFilter = document.getElementById('rangeFilter');
    if (rangeFilter) {
        rangeFilter.addEventListener('change', function() {
            applyFilters();
        });
    }

    const breakdownScopeFilter = document.getElementById('breakdownScopeFilter');
    if (breakdownScopeFilter) {
        breakdownScopeFilter.addEventListener('change', updateBreakdownScopeIndicator);
    }
});

// ========== CHART INITIALIZATION ==========
document.addEventListener("DOMContentLoaded", () => {
    const asArray = (value) => {
        if (Array.isArray(value)) return value;
        if (value && typeof value === 'object') return Object.values(value);
        return [];
    };

    const paymentByMethod = asArray(@json($paymentByMethod ?? []));
    const paymentByType = asArray(@json($paymentByType ?? []));
    const paymentByStatus = asArray(@json($paymentByStatus ?? []));
    const monthlyIncome = asArray(@json($monthlyIncome ?? []));
    const weeklyTrend = asArray(@json($weeklyTrend ?? []));
    const districtAnalytics = asArray(@json($districtAnalytics ?? []));

    const districtCoordinates = {
        'Jaffna': { lat: 9.6615, lng: 80.0255 },
        'Kilinochchi': { lat: 9.3803, lng: 80.4088 },
        'Mannar': { lat: 8.9806, lng: 79.9042 },
        'Mullaitivu': { lat: 9.2671, lng: 80.8142 },
        'Vavuniya': { lat: 8.7514, lng: 80.4971 },
        'Trincomalee': { lat: 8.5874, lng: 81.2152 },
        'Anuradhapura': { lat: 8.3114, lng: 80.4037 },
        'Puttalam': { lat: 8.0362, lng: 79.8283 },
        'Kurunegala': { lat: 7.4863, lng: 80.3647 },
        'Polonnaruwa': { lat: 7.9403, lng: 81.0188 },
        'Matale': { lat: 7.4675, lng: 80.6234 },
        'Kandy': { lat: 7.2906, lng: 80.6337 },
        'Kegalle': { lat: 7.2513, lng: 80.3464 },
        'Nuwara Eliya': { lat: 6.9497, lng: 80.7891 },
        'Badulla': { lat: 6.9934, lng: 81.0550 },
        'Gampaha': { lat: 7.0873, lng: 79.9990 },
        'Colombo': { lat: 6.9271, lng: 79.8612 },
        'Kalutara': { lat: 6.5854, lng: 79.9607 },
        'Ratnapura': { lat: 6.6828, lng: 80.3992 },
        'Monaragala': { lat: 6.8728, lng: 81.3507 },
        'Ampara': { lat: 7.2917, lng: 81.6724 },
        'Batticaloa': { lat: 7.7102, lng: 81.6924 },
        'Galle': { lat: 6.0535, lng: 80.2210 },
        'Matara': { lat: 5.9549, lng: 80.5540 },
        'Hambantota': { lat: 6.1241, lng: 81.1185 }
    };

    const districtNames = [
        'Ampara', 'Anuradhapura', 'Badulla', 'Batticaloa', 'Colombo', 'Galle',
        'Gampaha', 'Hambantota', 'Jaffna', 'Kalutara', 'Kandy', 'Kegalle',
        'Kilinochchi', 'Kurunegala', 'Mannar', 'Matale', 'Matara', 'Monaragala',
        'Mullaitivu', 'Nuwara Eliya', 'Polonnaruwa', 'Puttalam', 'Ratnapura',
        'Trincomalee', 'Vavuniya'
    ];

    const districtMapData = districtNames.map(name => {
        const match = districtAnalytics.find(item => item.district === name);
        return {
            district: name,
            student_count: Number(match?.student_count || 0),
            coordinates: districtCoordinates[name]
        };
    });

    const totalDistrictStudents = districtMapData.reduce((sum, item) => sum + item.student_count, 0);

    // Bounds for Wikimedia Sri Lanka location map (equirectangular)
    const mapGeoBounds = {
        north: 10.2,
        south: 5.5,
        west: 79.2,
        east: 82.3
    };

    const nf = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

    function formatCurrency(value) {
        return `LKR ${nf.format(Number(value || 0))}`;
    }

    function renderDistrictMap(items) {
        const markerLayer = document.getElementById('districtMapMarkers');
        const tableBody = document.getElementById('districtAnalyticsTableBody');
        const topList = document.getElementById('districtTopList');
        if (!tableBody || !topList || !markerLayer) {
            return;
        }

        document.getElementById('district-map-total-students').textContent = totalDistrictStudents.toLocaleString();

        const maxStudents = Math.max(...items.map(item => item.student_count), 1);
        markerLayer.innerHTML = '';
        tableBody.innerHTML = '';

        items.forEach(item => {
            if (!item.coordinates || typeof item.coordinates.lat !== 'number' || typeof item.coordinates.lng !== 'number') {
                return;
            }

            const intensity = item.student_count > 0 ? (item.student_count / maxStudents) : 0;
            const xPct = ((item.coordinates.lng - mapGeoBounds.west) / (mapGeoBounds.east - mapGeoBounds.west)) * 100;
            const yPct = ((mapGeoBounds.north - item.coordinates.lat) / (mapGeoBounds.north - mapGeoBounds.south)) * 100;

            const size = item.student_count > 0 ? (10 + Math.round(intensity * 16)) : 8;
            const marker = document.createElement('button');
            marker.type = 'button';
            marker.className = 'district-marker';
            marker.style.left = `${xPct}%`;
            marker.style.top = `${yPct}%`;
            marker.style.width = `${size}px`;
            marker.style.height = `${size}px`;
            marker.style.background = item.student_count > 0
                ? `rgba(29, 78, 216, ${0.35 + intensity * 0.5})`
                : 'rgba(148, 163, 184, 0.5)';
            marker.style.borderColor = item.student_count > 0 ? '#1d4ed8' : '#94a3b8';
            marker.title = `${item.district}: ${item.student_count.toLocaleString()} students`;
            marker.setAttribute('aria-label', marker.title);
            marker.addEventListener('mouseenter', () => updateDistrictHighlight(item));
            marker.addEventListener('focus', () => updateDistrictHighlight(item));
            marker.addEventListener('click', () => updateDistrictHighlight(item));
            markerLayer.appendChild(marker);

            const row = document.createElement('tr');
            const districtCell = document.createElement('td');
            districtCell.textContent = item.district;
            const countCell = document.createElement('td');
            countCell.className = 'text-end';
            countCell.textContent = item.student_count.toLocaleString();
            row.appendChild(districtCell);
            row.appendChild(countCell);
            row.addEventListener('mouseenter', () => updateDistrictHighlight(item));
            row.addEventListener('click', () => updateDistrictHighlight(item));
            tableBody.appendChild(row);
        });

        const ranked = [...items]
            .filter(item => item.student_count > 0)
            .sort((a, b) => b.student_count - a.student_count)
            .slice(0, 6);

        topList.replaceChildren();
        if (!ranked.length) {
            const empty = document.createElement('div');
            empty.className = 'text-muted small';
            empty.textContent = 'No district activity found for the selected filters.';
            topList.appendChild(empty);
        } else {
            ranked.forEach((item, index) => {
                const wrap = document.createElement('div');
                wrap.className = 'district-top-item';
                const rank = document.createElement('div');
                rank.className = 'district-top-rank';
                rank.textContent = String(index + 1);
                const content = document.createElement('div');
                content.className = 'district-top-content';
                const name = document.createElement('div');
                name.className = 'district-top-name';
                name.textContent = item.district;
                const meta = document.createElement('div');
                meta.className = 'district-top-meta';
                meta.textContent = item.student_count + ' students';
                content.appendChild(name);
                content.appendChild(meta);
                const amount = document.createElement('div');
                amount.className = 'district-top-amount';
                amount.textContent = String(item.student_count);
                wrap.appendChild(rank);
                wrap.appendChild(content);
                wrap.appendChild(amount);
                topList.appendChild(wrap);
            });
        }

        const initial = ranked[0] || items[0] || {
            district: 'All districts',
            student_count: totalDistrictStudents
        };
        updateDistrictHighlight(initial);
    }

    function updateDistrictHighlight(item) {
        const nameEl = document.querySelector('.district-highlight-name');
        if (!nameEl) {
            return;
        }

        nameEl.textContent = item.district;
        document.getElementById('district-highlight-students').textContent = Number(item.student_count || 0).toLocaleString();
    }

    try {
        renderDistrictMap(districtMapData);
    } catch (error) {
        console.error('District map failed:', error);
    }

    const methodRows = paymentByMethod;
    const typeRows = paymentByType;
    const statusRows = paymentByStatus;
    const monthlyRows = monthlyIncome;
    const weeklyRows = weeklyTrend;

    // ========== MONTHLY & WEEKLY TREND METRIC COMPUTATIONS ==========
    // Calculate Monthly Trend Metrics
    if (monthlyRows.length > 0) {
        const len = monthlyRows.length;
        const currentMonthData = monthlyRows[len - 1];
        const prevMonthData = len > 1 ? monthlyRows[len - 2] : null;

        const thisMonthPaid = Number(currentMonthData.paid || 0);
        const thisMonthTotal = thisMonthPaid;
        const lastMonthPaid = prevMonthData ? Number(prevMonthData.paid || 0) : 0;

        document.getElementById('monthlyThisMonthVal').textContent = formatCurrency(thisMonthTotal);
        document.getElementById('monthlyLastMonthVal').textContent = formatCurrency(lastMonthPaid);
        document.getElementById('monthlyLastMonthName').textContent = prevMonthData ? `For ${prevMonthData.month}` : 'No previous data';

        let momGrowthHtml = '';
        if (lastMonthPaid > 0) {
            const pctChange = ((thisMonthTotal - lastMonthPaid) / lastMonthPaid) * 100;
            const sign = pctChange >= 0 ? '+' : '';
            const badgeClass = pctChange >= 0 ? 'text-success' : 'text-danger';
            const icon = pctChange >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-left';
            momGrowthHtml = `<span class="${badgeClass} fw-bold" style="font-size: 0.8rem;"><i class="bi ${icon}"></i> ${sign}${pctChange.toFixed(1)}% MoM</span>`;
        } else {
            momGrowthHtml = `<span class="text-muted small" style="font-size: 0.8rem;">New series</span>`;
        }
        document.getElementById('monthlyGrowthBadge').innerHTML = momGrowthHtml;

        const totalPaid = monthlyRows.reduce((sum, item) => sum + Number(item.paid || 0), 0);
        const avgPaid = totalPaid / len;
        document.getElementById('monthlyAvgVal').textContent = formatCurrency(avgPaid);
        document.getElementById('monthlySpanLabel').textContent = `Avg over ${len} month${len > 1 ? 's' : ''}`;
    } else {
        document.getElementById('monthlyThisMonthVal').textContent = 'LKR 0.00';
        document.getElementById('monthlyLastMonthVal').textContent = 'LKR 0.00';
        document.getElementById('monthlyAvgVal').textContent = 'LKR 0.00';
    }

    if (weeklyRows.length > 0) {
        const currentWeekData = weeklyRows[0];
        const prevWeekData = weeklyRows.length > 1 ? weeklyRows[1] : null;

        const thisWeekTotal = Number(currentWeekData.total || 0);
        const lastWeekTotal = prevWeekData ? Number(prevWeekData.total || 0) : 0;

        document.getElementById('weeklyThisWeekVal').textContent = formatCurrency(thisWeekTotal);
        document.getElementById('weeklyLastWeekVal').textContent = formatCurrency(lastWeekTotal);

        const formatWeekName = (weekStr) => {
            if (!weekStr) return '';
            const s = String(weekStr);
            if (s.length >= 6) {
                return `Week ${s.slice(4)} (${s.slice(0, 4)})`;
            }
            return 'Week ' + s;
        };

        document.getElementById('weeklyLastWeekName').textContent = prevWeekData ? formatWeekName(prevWeekData.week) : 'No previous data';

        let wowGrowthHtml = '';
        if (lastWeekTotal > 0) {
            const pctChange = ((thisWeekTotal - lastWeekTotal) / lastWeekTotal) * 100;
            const sign = pctChange >= 0 ? '+' : '';
            const badgeClass = pctChange >= 0 ? 'text-success' : 'text-danger';
            const icon = pctChange >= 0 ? 'bi-arrow-up-right' : 'bi-arrow-down-left';
            wowGrowthHtml = `<span class="${badgeClass} fw-bold" style="font-size: 0.8rem;"><i class="bi ${icon}"></i> ${sign}${pctChange.toFixed(1)}% WoW</span>`;
        } else {
            wowGrowthHtml = `<span class="text-muted small" style="font-size: 0.8rem;">New series</span>`;
        }
        document.getElementById('weeklyGrowthBadge').innerHTML = wowGrowthHtml;
    } else {
        document.getElementById('weeklyThisWeekVal').textContent = 'LKR 0.00';
        document.getElementById('weeklyLastWeekVal').textContent = 'LKR 0.00';
    }

    if (typeof Chart === 'undefined') {
        console.error('Chart.js failed to load');
        return;
    }

    Chart.defaults.font.family = "'Inter', sans-serif";
    Chart.defaults.color = '#6c757d';

    function initChart(canvasId, config) {
        const canvas = document.getElementById(canvasId);
        if (!canvas) return;
        try {
            new Chart(canvas, config);
        } catch (error) {
            console.error('Chart init failed for ' + canvasId, error);
        }
    }

    // ---- Monthly Collection Trend (Line + Bar) ----
    initChart('monthlyChart', {
        type: 'line',
        data: {
            labels: monthlyRows.map(p => p.month),
            datasets: [{
                label: 'Paid',
                data: monthlyRows.map(p => p.paid),
                borderColor: '#667eea',
                backgroundColor: 'rgba(102, 126, 234, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2
            }, {
                label: 'Pending',
                data: monthlyRows.map(p => p.pending),
                borderColor: '#f6c23e',
                backgroundColor: 'rgba(246, 194, 62, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            interaction: {
                mode: 'index',
                intersect: false,
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: { usePointStyle: true, padding: 15 }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.8)',
                    padding: 12,
                    titleFont: { size: 14 },
                    bodyFont: { size: 13 },
                    callbacks: {
                        label: function(context) {
                            return context.dataset.label + ': LKR ' + 
                                   new Intl.NumberFormat().format(context.parsed.y);
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

    // ---- Payment Status (Doughnut) ----
    initChart('statusChart', {
        type: 'doughnut',
        data: {
            labels: statusRows.map(p => p.status ? p.status.charAt(0).toUpperCase() + p.status.slice(1) : 'Unknown'),
            datasets: [{
                data: statusRows.map(p => p.total),
                backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b', '#858796'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '70%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 15, usePointStyle: true }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = ((context.parsed / total) * 100).toFixed(1);
                            return context.label + ': LKR ' + 
                                   new Intl.NumberFormat().format(context.parsed) + 
                                   ' (' + percentage + '%)';
                        }
                    }
                }
            }
        }
    });

    // ---- Payment Methods (Pie) ----
    initChart('methodChart', {
        type: 'pie',
        data: {
            labels: methodRows.map(p => {
                const methods = {
                    'cash': 'Cash',
                    'cheque': 'Cheque',
                    'bank_transfer': 'Bank Transfer',
                    'online': 'Online',
                    'card': 'Card'
                };
                return methods[p.payment_method] || p.payment_method || 'Unknown';
            }),
            datasets: [{
                data: methodRows.map(p => p.total),
                backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 10, usePointStyle: true, font: { size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': LKR ' + 
                                   new Intl.NumberFormat().format(context.parsed);
                        }
                    }
                }
            }
        }
    });

    // ---- Payment Types (Doughnut) ----
    initChart('typeChart', {
        type: 'doughnut',
        data: {
            labels: typeRows.map(p => p.type || 'Unknown'),
            datasets: [{
                data: typeRows.map(p => p.total),
                backgroundColor: ['#36b9cc', '#f6c23e', '#e74a3b', '#858796', '#20c997'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            cutout: '60%',
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { padding: 10, usePointStyle: true, font: { size: 11 } }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.label + ': LKR ' + 
                                   new Intl.NumberFormat().format(context.parsed);
                        }
                    }
                }
            }
        }
    });

    // ---- Weekly Trend (Bar) ----
    initChart('weeklyChart', {
        type: 'bar',
        data: {
            labels: weeklyRows.map(p => 'Week ' + p.week),
            datasets: [{
                label: 'Weekly Revenue',
                data: weeklyRows.map(p => p.total),
                backgroundColor: 'rgba(78, 115, 223, 0.8)',
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return 'LKR ' + new Intl.NumberFormat().format(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });
});
</script>

<script nonce="{{ $cspNonce }}">
// ============================================================
//  Installment KPI Card Logic
// ============================================================
(function () {
    'use strict';

    const kpiUrl  = "{{ route('payment.summary.installment.kpi') }}";
    const coursesUrl = "{{ route('payment.summary.courses.by.location') }}";
    const intakesUrl = "{{ route('payment.summary.intakes.by.location.course') }}";

    const elLoc     = document.getElementById('kpiLocation');
    const elCourse  = document.getElementById('kpiCourse');
    const elIntake  = document.getElementById('kpiIntake');
    const elType    = document.getElementById('kpiPaymentType');
    const elInstNo  = document.getElementById('kpiInstallmentNo');
    const elBtn     = document.getElementById('kpiFetchBtn');

    const elResult    = document.getElementById('kpiResultArea');
    const elHolder    = document.getElementById('kpiPlaceholder');
    const elLoading   = document.getElementById('kpiLoading');
    const elError     = document.getElementById('kpiError');
    const elErrMsg    = document.getElementById('kpiErrorMsg');
    const elPaid      = document.getElementById('kpiPaidTotal');
    const elPending   = document.getElementById('kpiPendingTotal');
    const elGrand     = document.getElementById('kpiGrandTotal');
    const elPaidCnt   = document.getElementById('kpiPaidCount');
    const elTotalCnt  = document.getElementById('kpiTotalCount');
    const elBadges    = document.getElementById('kpiFilterBadges');

    if (!elLoc) return; // guard if element missing

    function appendDashboardPeriodParams(params) {
        const range = document.getElementById('rangeFilter')?.value;
        const startDate = document.getElementById('startDateFilter')?.value;
        const endDate = document.getElementById('endDateFilter')?.value;
        if (range) params.append('range', range);
        if (startDate) params.append('start_date', startDate);
        if (endDate) params.append('end_date', endDate);
    }

    const fmt = new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const fmtLKR = v => `LKR ${fmt.format(Number(v || 0))}`;

    function showState(state) {
        elHolder.classList.add('d-none');
        elLoading.classList.add('d-none');
        elError.classList.add('d-none');
        elResult.classList.add('d-none');
        if (state === 'placeholder') elHolder.classList.remove('d-none');
        else if (state === 'loading')  elLoading.classList.remove('d-none');
        else if (state === 'error')    elError.classList.remove('d-none');
        else if (state === 'result')   elResult.classList.remove('d-none');
    }

    // ── Dependent: Location → load courses ──────────────────────────────
    elLoc.addEventListener('change', async function () {
        const loc = this.value;

        // Reset downstream selects
        elCourse.innerHTML  = '<option value="">All Courses</option>';
        elIntake.innerHTML  = '<option value="">All Intakes</option>';
        elInstNo.innerHTML  = '<option value="">All</option>';
        elCourse.disabled   = !loc;
        elIntake.disabled   = true;

        if (!loc) return;

        elCourse.innerHTML = '<option value="">Loading…</option>';
        elCourse.disabled  = true;

        try {
            const res  = await fetch(`${coursesUrl}?location=${encodeURIComponent(loc)}`);
            const json = await res.json();
            elCourse.innerHTML = '<option value="">All Courses</option>';
            const courses = Array.isArray(json.data) ? json.data : (Array.isArray(json.courses) ? json.courses : []);
            courses.forEach(c => {
                    const o = document.createElement('option');
                    o.value = c.course_id;
                    o.textContent = c.course_name;
                    elCourse.appendChild(o);
            });
            elCourse.disabled = false;
            const courseToggle = elCourse.closest('.nebula-select')?.querySelector('.nebula-select-toggle');
            if (courseToggle) {
                courseToggle.textContent = elCourse.options[elCourse.selectedIndex]?.text || '';
                courseToggle.disabled = false;
            }
        } catch (e) {
            elCourse.innerHTML = '<option value="">Error loading courses</option>';
            elCourse.disabled  = false;
        }
    });

    // ── Dependent: Course → load intakes ─────────────────────────────────
    elCourse.addEventListener('change', async function () {
        const loc      = elLoc.value;
        const courseId = this.value;

        elIntake.innerHTML = '<option value="">All Intakes</option>';
        elInstNo.innerHTML = '<option value="">All</option>';
        elIntake.disabled  = !courseId;

        if (!loc || !courseId) return;

        elIntake.innerHTML = '<option value="">Loading…</option>';
        elIntake.disabled  = true;

        try {
            const res  = await fetch(`${intakesUrl}?location=${encodeURIComponent(loc)}&course_id=${encodeURIComponent(courseId)}`);
            const json = await res.json();
            elIntake.innerHTML = '<option value="">All Intakes</option>';
            if (json.success && Array.isArray(json.data)) {
                json.data.forEach(i => {
                    const o = document.createElement('option');
                    o.value = i.intake_id;
                    o.textContent = i.intake_name;
                    elIntake.appendChild(o);
                });
            }
            elIntake.disabled = false;
        } catch (e) {
            elIntake.innerHTML = '<option value="">Error loading intakes</option>';
            elIntake.disabled  = false;
        }
    });

    // ── When intake or type changes, refresh available installment numbers ─
    async function refreshInstallmentNos() {
        const loc      = elLoc.value;
        const courseId = elCourse.value;
        const intakeId = elIntake.value;
        const ptype    = elType.value;

        const params = new URLSearchParams();
        if (loc)      params.append('location',     loc);
        if (courseId) params.append('course_id',    courseId);
        if (intakeId) params.append('intake_id',    intakeId);
        if (ptype)    params.append('payment_type', ptype);
        appendDashboardPeriodParams(params);

        try {
            const res  = await fetch(`${kpiUrl}?${params.toString()}`);
            const json = await res.json();
            const current = elInstNo.value;
            elInstNo.innerHTML = '<option value="">All</option>';
            if (json.success && Array.isArray(json.available_installment_nos)) {
                json.available_installment_nos.forEach(n => {
                    const o = document.createElement('option');
                    o.value = n;
                    o.textContent = `Installment ${n}`;
                    if (String(n) === String(current)) o.selected = true;
                    elInstNo.appendChild(o);
                });
            }
        } catch (_) { /* silent */ }
    }

    elIntake.addEventListener('change', refreshInstallmentNos);
    elType.addEventListener('change', refreshInstallmentNos);

    // ── Fetch & render KPI totals ─────────────────────────────────────────
    elBtn.addEventListener('click', async function () {
        showState('loading');

        const params = new URLSearchParams();
        const loc      = elLoc.value;
        const courseId = elCourse.value;
        const intakeId = elIntake.value;
        const ptype    = elType.value;
        const instNo   = elInstNo.value;

        if (loc)      params.append('location',       loc);
        if (courseId) params.append('course_id',      courseId);
        if (intakeId) params.append('intake_id',      intakeId);
        if (ptype)    params.append('payment_type',   ptype);
        if (instNo)   params.append('installment_no', instNo);
        appendDashboardPeriodParams(params);

        try {
            const res  = await fetch(`${kpiUrl}?${params.toString()}`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const json = await res.json();
            if (!json.success) throw new Error('Server returned an error');

            elPaid.textContent    = fmtLKR(json.paid_total);
            elPending.textContent = fmtLKR(json.pending_total);
            elGrand.textContent   = fmtLKR(json.paid_total + json.pending_total);
            elPaidCnt.textContent = (json.paid_count || 0).toLocaleString();
            elTotalCnt.textContent= (json.total_count || 0).toLocaleString();

            // Filter badges
            const typeLabels = {
                franchise_fee: 'Franchise Fee',
                course_fee: 'Course Fee',
                registration_fee: 'Registration Fee',
            };
            const badges = [];
            const f = json.filters || {};
            if (f.location)       badges.push(['📍 Location',   f.location]);
            if (f.course_id)      badges.push(['📚 Course',     elCourse.options[elCourse.selectedIndex]?.text || f.course_id]);
            if (f.intake_id)      badges.push(['📋 Intake',     elIntake.options[elIntake.selectedIndex]?.text || f.intake_id]);
            if (f.payment_type)   badges.push(['💳 Type',       typeLabels[f.payment_type] || f.payment_type]);
            if (f.installment_no !== null && f.installment_no !== undefined)
                                  badges.push(['#️⃣ Installment', `No. ${f.installment_no}`]);

            elBadges.replaceChildren();
            badges.forEach(([label, val]) => {
                const span = document.createElement('span');
                span.className = 'badge bg-light text-dark border border-secondary-subtle rounded-pill px-3 py-2 fw-normal small';
                const muted = document.createElement('span');
                muted.className = 'text-muted';
                muted.textContent = label + ':';
                const strong = document.createElement('strong');
                strong.textContent = String(val ?? '');
                span.appendChild(muted);
                span.appendChild(document.createTextNode(' '));
                span.appendChild(strong);
                elBadges.appendChild(span);
            });

            // Update PDF export links
            const pdfUrlBase = "{{ route('payment.summary.installment.pdf') }}";
            document.querySelectorAll('.kpi-pdf-btn').forEach(btn => {
                const status = btn.getAttribute('data-status');
                const pdfParams = new URLSearchParams(params);
                pdfParams.set('status', status);
                btn.href = `${pdfUrlBase}?${pdfParams.toString()}`;
            });

            showState('result');
        } catch (err) {
            elErrMsg.textContent = err.message || 'Failed to fetch data. Please try again.';
            showState('error');
        }
    });

    // Initialise state
    showState('placeholder');
})();
</script>

<style nonce="{{ $cspNonce }}">
.payment-summary-reset{} /* anchor for quick search */
#payment-summary,
#payment-summary .card,
#payment-summary .card-body,
#payment-summary .card-header {
    min-width: 0;
    max-width: 100%;
    overflow: visible;
    height: auto;
    transform: none !important;
}
body:has(#payment-summary) .body-wrapper > .container-fluid {
    overflow: visible;
}
#payment-summary [class*="col-"] {
    min-width: 0;
}
#payment-summary .form-select,
#payment-summary .form-control {
    width: 100%;
    max-width: 100%;
}
#payment-summary h2,
#payment-summary h3,
#payment-summary h4,
#payment-summary h5 {
    overflow-wrap: anywhere;
    word-break: break-word;
}
.payment-summary-filter-actions,
.payment-summary-header-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: flex-end;
}
.payment-summary-filter-actions .btn,
.payment-summary-header-actions .btn,
.payment-summary-header-actions a {
    min-width: 0;
}
#payment-summary .nebula-select,
#payment-summary .nebula-select-toggle {
    width: 100%;
    max-width: 100%;
}
#payment-summary .chart-wrap {
    position: relative;
    width: 100%;
    min-height: 220px;
}
#payment-summary .chart-wrap canvas {
    max-width: 100%;
}
@media (max-width: 767.98px) {
    #payment-summary h2 {
        font-size: 1.25rem;
    }
    #payment-summary #kpiSection h3,
    #payment-summary #kpiResultArea h4 {
        font-size: 1.1rem;
    }
    #payment-summary .form-control,
    #payment-summary .form-select,
    #payment-summary .form-select-sm,
    #payment-summary .nebula-select-toggle {
        font-size: 16px;
    }
    .payment-summary-filter-actions,
    .payment-summary-filter-actions .btn,
    .payment-summary-header-actions,
    .payment-summary-header-actions .btn,
    .payment-summary-header-actions a {
        width: 100%;
        justify-content: stretch;
    }
    #payment-summary .card-body {
        padding: 1rem 0.75rem;
    }
}
/* ---- Page background: force clean white and remove any image for this page only ---- */
/* body, .app-content, .content, .content-wrapper, main, #payment-summary {
    background-color: #ffffff !important;
    background-image: none !important;
} */

/* ---- Make all sections/cards light and airy ---- */
#payment-summary .card {
    background-color: #ffffff;
    border: 1px solid #eef2f7;
    box-shadow: 0 2px 10px rgba(0,0,0,0.04);
}

#payment-summary .card-header {
    background-color: #ffffff !important;
    border-bottom: 1px solid #eef2f7;
}

/* ---- KPI section: replace heavy gradients with light tones ---- */
#payment-summary #kpiSection .card.bg-gradient {
    background: #f8fafc !important;
}

/* Ensure text is dark on light KPI cards (override .text-white) */
#payment-summary #kpiSection .card.bg-gradient .card-body,
#payment-summary #kpiSection .card.bg-gradient .card-body h3,
#payment-summary #kpiSection .card.bg-gradient .card-body p,
#payment-summary #kpiSection .card.bg-gradient .card-body small {
    color: #0f172a !important;
}

/* Subtle muted copy inside KPI cards */
#payment-summary #kpiSection .card.bg-gradient .opacity-75,
#payment-summary #kpiSection .card.bg-gradient small {
    color: #64748b !important;
    opacity: 1 !important;
}

/* Icon bubbles and accent borders per KPI tile */
#payment-summary #kpiSection > .col-xl-3:nth-child(1) .card { border-left: 3px solid #0d6efd; }
#payment-summary #kpiSection > .col-xl-3:nth-child(1) .bg-white.bg-opacity-25 { background: rgba(13,110,253,.12) !important; color: #0d6efd !important; }

#payment-summary #kpiSection > .col-xl-3:nth-child(2) .card { border-left: 3px solid #f59f00; }
#payment-summary #kpiSection > .col-xl-3:nth-child(2) .bg-white.bg-opacity-25 { background: rgba(245,159,0,.12) !important; color: #f59f00 !important; }

#payment-summary #kpiSection > .col-xl-3:nth-child(3) .card { border-left: 3px solid #0dcaf0; }
#payment-summary #kpiSection > .col-xl-3:nth-child(3) .bg-white.bg-opacity-25 { background: rgba(13,202,240,.12) !important; color: #0dcaf0 !important; }

#payment-summary #kpiSection > .col-xl-3:nth-child(4) .card { border-left: 3px solid #dc3545; }
#payment-summary #kpiSection > .col-xl-3:nth-child(4) .bg-white.bg-opacity-25 { background: rgba(220,53,69,.12) !important; color: #dc3545 !important; }

/* Tables: keep headers light */
#payment-summary table thead {
    background-color: #f8f9fc;
}

.bg-gradient {
    background-size: cover;
}

.table-hover tbody tr:hover {
    background-color: rgba(0, 0, 0, 0.02);
}

.district-map-layout {
    display: grid;
    grid-template-columns: minmax(0, 1.25fr) minmax(280px, 0.75fr);
    gap: 1.5rem;
    align-items: start;
}

.district-map-shell {
    position: relative;
    width: 100%;
    max-width: 520px;
    margin: 0 auto;
}

.district-real-map-image {
    width: 100%;
    height: auto;
    display: block;
    border-radius: 20px;
    border: 1px solid #c7d2fe;
    box-shadow: 0 18px 35px rgba(59, 130, 246, 0.12);
    background: #f8fafc;
}

.district-map-markers {
    position: absolute;
    inset: 0;
    border-radius: 20px;
}

.district-marker {
    position: absolute;
    transform: translate(-50%, -50%);
    border: 2px solid transparent;
    border-radius: 999px;
    box-shadow: 0 8px 18px rgba(30, 41, 59, 0.14);
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    cursor: pointer;
}

.district-marker:hover,
.district-marker:focus {
    transform: translate(-50%, -50%) scale(1.12);
    box-shadow: 0 12px 26px rgba(30, 41, 59, 0.2);
    outline: none;
}

.district-map-legend {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
}

.district-map-legend .legend-title {
    font-size: 0.85rem;
    color: #64748b;
}

.district-map-legend .legend-scale {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    flex: 1;
    justify-content: flex-end;
    color: #64748b;
    font-size: 0.78rem;
}

.district-map-legend .legend-bar {
    width: 140px;
    height: 10px;
    border-radius: 999px;
    background: linear-gradient(90deg, rgba(148, 163, 184, 0.3) 0%, rgba(13, 110, 253, 0.95) 100%);
}

.district-highlight-card {
    background: linear-gradient(135deg, #eff6ff 0%, #f8fafc 100%);
    border: 1px solid #dbeafe;
    border-radius: 18px;
    padding: 1rem 1.1rem;
}

.district-highlight-label {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    margin-bottom: 0.35rem;
}

.district-highlight-name {
    font-size: 1.3rem;
    font-weight: 700;
    color: #0f172a;
    margin-bottom: 0.9rem;
}

.district-highlight-stats {
    display: grid;
    grid-template-columns: repeat(1, minmax(0, 1fr));
    gap: 0.75rem;
}

.district-highlight-stats span {
    display: block;
    font-size: 0.74rem;
    text-transform: uppercase;
    color: #64748b;
    letter-spacing: 0.05em;
    margin-bottom: 0.2rem;
}

.district-highlight-stats strong {
    display: block;
    color: #0f172a;
    font-size: 1rem;
}

.district-top-list {
    display: grid;
    gap: 0.85rem;
}

.district-top-item {
    display: grid;
    grid-template-columns: 40px minmax(0, 1fr) auto;
    gap: 0.8rem;
    align-items: center;
    padding: 0.85rem 0.9rem;
    border-radius: 14px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
}

.district-top-rank {
    width: 40px;
    height: 40px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: #dbeafe;
    color: #1d4ed8;
    font-weight: 700;
}

.district-top-name {
    font-weight: 600;
    color: #0f172a;
}

.district-top-meta {
    font-size: 0.82rem;
    color: #64748b;
}

.district-top-amount {
    font-weight: 700;
    color: #0f766e;
    white-space: nowrap;
}

@media (max-width: 991.98px) {
    .district-map-layout {
        grid-template-columns: 1fr;
    }

    .district-highlight-stats {
        grid-template-columns: 1fr;
    }

    .district-map-legend {
        flex-wrap: wrap;
    }

    .district-map-legend .legend-bar {
        width: 100%;
        max-width: 180px;
    }

    .district-top-item {
        grid-template-columns: 36px minmax(0, 1fr);
    }

    .district-top-amount {
        grid-column: 2;
        justify-self: start;
        white-space: normal;
    }
}

#payment-summary .card {
    transition: box-shadow 0.2s;
}

#payment-summary .card:hover {
    transform: none !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1) !important;
}

.sticky-top {
    position: sticky;
    top: 0;
    z-index: 10;
}
</style>

@endsection