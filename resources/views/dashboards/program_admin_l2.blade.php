@extends('inc.app')

@section('title', 'NEBULA | Program Administrator (Level 02) Dashboard')

@section('content')
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="{{ asset('css/styles.min.css') }}">
    <link nonce="{{ $cspNonce }}" rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
        integrity="sha384-3B6NwesSXE7YJlcLI9RpRqGf2p/EgVH8BgoKTaUrmKNDkHPStTQ3EyoYjCGXaOTS" crossorigin="anonymous">
    <script nonce="{{ $cspNonce }}" src="{{ asset('libs/chartjs/chart.min.js') }}"></script>
    <script nonce="{{ $cspNonce }}" src="{{ asset('libs/chartjs/chartjs-plugin-datalabels.min.js') }}"></script>

    <style nonce="{{ $cspNonce }}">
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1) !important;
        }

        .kpi-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .kpi-card:hover {
            border-left-width: 6px;
        }

        .avatar-initial {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 16px;
        }

        .time-filter-btn {
            padding: 6px 16px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            background: white;
            color: #6c757d;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.2s ease;
            margin-right: 8px;
            margin-bottom: 8px;
        }

        .time-filter-btn:hover {
            background: #f8f9fa;
            border-color: #adb5bd;
        }

        .time-filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .chart-toggle-btn {
            padding: 8px;
            border-radius: 6px;
            border: 1px solid #dee2e6;
            background: white;
            color: #6c757d;
            transition: all 0.2s ease;
        }

        .chart-toggle-btn:hover {
            background: #f8f9fa;
        }

        .chart-toggle-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }

        .chart-container {
            position: relative;
            height: 300px;
        }

        .analytics-filter-field {
            flex: 0 0 260px;
            width: 260px;
        }

        .analytics-filter-field.location {
            flex-basis: 352px;
            width: 352px;
        }

        .analytics-filter-field.module {
            flex-basis: 282px;
            width: 282px;
        }

        .analytics-filter-field .nebula-select-sm {
            width: 100%;
            max-width: 100%;
            flex: 1 1 auto;
        }

        @media (max-width: 575.98px) {
            .analytics-filter-field,
            .analytics-filter-field.location,
            .analytics-filter-field.module {
                flex: 1 1 100%;
                width: 100%;
                min-width: 0;
            }

            .analytics-filter-actions {
                width: 100%;
            }

            .analytics-filter-actions .btn {
                flex: 1 1 0;
            }
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .status-registered {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-special {
            background: #d1ecf1;
            color: #0c5460;
        }

        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            transform: scale(1.1);
        }

        .nav-tabs-custom {
            border-bottom: 2px solid #dee2e6;
        }

        .nav-tabs-custom .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 500;
            padding: 12px 24px;
            border-radius: 0;
            position: relative;
        }

        .nav-tabs-custom .nav-link.active {
            color: #667eea;
            border-bottom: 3px solid #667eea;
            background: none;
        }

        .badge-purple {
            background-color: #667eea;
            color: white;
        }

        .badge-primary {
            background-color: #007bff;
            color: white;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
        }

        .badge-danger {
            background-color: #dc3545;
            color: white;
        }
    </style>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="bg-white p-4 rounded shadow-sm mb-4">
                    <div class="page-title-box d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <div class="avatar-initial">
                                    <i class="fas fa-user-tie"></i>
                                </div>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold text-dark">Program Administrator (Level 02) Dashboard</h4>
                                <p class="text-muted mb-0">Operational management, student headcount, academic performance,
                                    and course status</p>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-2">
                            <div class="input-group input-group-sm" style="width: 220px;">
                                <input type="text" class="form-control" placeholder="Search tables..." id="searchInput">
                                <button class="btn btn-outline-secondary" type="button" id="dashboardSearchBtn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>

                            <button class="btn btn-outline-primary btn-sm" onclick="refreshAllData()">
                                <i class="fas fa-sync-alt me-1"></i> Refresh
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Time Filter -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex flex-wrap align-items-center">
                            <span class="me-3 text-muted"><i class="fas fa-calendar-alt me-1"></i> Time Period:</span>
                            <div class="d-flex flex-wrap">
                                <button type="button" class="time-filter-btn" data-period="today"
                                    onclick="setTimePeriod('today', this)">Today</button>
                                <button type="button" class="time-filter-btn" data-period="week"
                                    onclick="setTimePeriod('week', this)">This Week</button>
                                <button type="button" class="time-filter-btn active" data-period="month"
                                    onclick="setTimePeriod('month', this)">This Month</button>
                                <button type="button" class="time-filter-btn" data-period="quarter"
                                    onclick="setTimePeriod('quarter', this)">Last 3 Months</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Academic & Attendance Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body py-3">
                        <div class="d-flex flex-wrap align-items-end gap-3">
                            <div class="analytics-filter-field location">
                                <label for="analyticsLocationFilter" class="form-label mb-1 text-muted">Location</label>
                                <select id="analyticsLocationFilter" class="form-select form-select-sm">
                                    <option value="">Default Location</option>
                                    <option value="Welisara">Nebula Institute of Technology - Welisara</option>
                                    <option value="Moratuwa">Nebula Institute of Technology - Moratuwa</option>
                                    <option value="Peradeniya">Nebula Institute of Technology - Peradeniya</option>
                                </select>
                            </div>
                            <div class="analytics-filter-field">
                                <label for="analyticsCourseFilter" class="form-label mb-1 text-muted">Course</label>
                                <select id="analyticsCourseFilter" class="form-select form-select-sm" disabled>
                                    <option value="">All Courses</option>
                                </select>
                            </div>
                            <div class="analytics-filter-field">
                                <label for="analyticsIntakeFilter" class="form-label mb-1 text-muted">Intake</label>
                                <select id="analyticsIntakeFilter" class="form-select form-select-sm" disabled>
                                    <option value="">All Intakes</option>
                                </select>
                            </div>
                            <div class="analytics-filter-field module">
                                <label for="analyticsModuleFilter" class="form-label mb-1 text-muted">Module
                                    (Attendance)</label>
                                <select id="analyticsModuleFilter" class="form-select form-select-sm" disabled>
                                    <option value="">All Modules</option>
                                </select>
                            </div>
                            <div class="analytics-filter-actions d-flex gap-2">
                                <button type="button" class="btn btn-primary btn-sm" id="applyAnalyticsFiltersBtn">
                                    <i class="fas fa-filter me-1"></i> Apply
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm"
                                    id="clearAnalyticsFiltersBtn">
                                    Clear
                                </button>
                            </div>
                        </div>
                        <div id="appliedFiltersBanner" class="alert alert-info d-none py-2 mb-0 mt-3">
                            Filtered results are shown on the <strong>Academic Performance</strong> and <strong>Attendance</strong> tabs.
                            Module applies to Attendance only.
                            <span id="appliedFiltersText"></span>
                        </div>
                        <div class="small text-muted mt-2">Choose location/course/intake/module, then click Apply to filter Academic Performance and Attendance. Time Period updates Overview period cards, Attendance, and Payments period cards. Academic grades and Clearance status are current snapshots, not date-filtered.</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Dashboard Tabs -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <ul class="nav nav-tabs nav-tabs-custom mb-4" id="dashboardTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="overview-tab" data-bs-toggle="tab"
                                    data-bs-target="#overview" type="button" role="tab">
                                    <i class="fas fa-chart-pie me-2"></i> Overview
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="approvals-tab" data-bs-toggle="tab" data-bs-target="#approvals"
                                    type="button" role="tab">
                                    <i class="fas fa-check-circle me-2"></i> Approvals
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="academic-tab" data-bs-toggle="tab" data-bs-target="#academic"
                                    type="button" role="tab">
                                    <i class="fas fa-graduation-cap me-2"></i> Academic Performance
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="attendance-tab" data-bs-toggle="tab"
                                    data-bs-target="#attendance" type="button" role="tab">
                                    <i class="fas fa-calendar-check me-2"></i> Attendance
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="clearance-tab" data-bs-toggle="tab" data-bs-target="#clearance"
                                    type="button" role="tab">
                                    <i class="fas fa-clipboard-check me-2"></i> Clearance Status
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="payments-tab" data-bs-toggle="tab" data-bs-target="#payments"
                                    type="button" role="tab">
                                    <i class="fas fa-credit-card me-2"></i> Payments
                                </button>
                            </li>
                        </ul>

                        <div class="tab-content" id="dashboardTabsContent">
                            <!-- Overview Tab -->
                            <div class="tab-pane fade show active" id="overview" role="tabpanel">
                                <!-- KPI Cards -->
                                <div class="row mb-4">
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card kpi-card card-hover border-left-purple">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="avatar-initial bg-purple bg-opacity-10 text-purple">
                                                        <i class="fas fa-users"></i>
                                                    </div>
                                                    <span class="badge bg-purple bg-opacity-10 text-purple">Total</span>
                                                </div>
                                                <h5 class="card-title text-muted text-uppercase fs-12">Total Active Students
                                                </h5>
                                                <h2 class="fw-bold text-purple mb-1" id="totalActiveStudents">-</h2>
                                                <div class="text-muted fs-13">
                                                    <i class="fas fa-user-graduate me-1"></i> Enrolled Students
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card kpi-card card-hover border-left-primary">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="avatar-initial bg-primary bg-opacity-10 text-primary">
                                                        <i class="fas fa-layer-group"></i>
                                                    </div>
                                                    <div id="batchGrowth" style="display: none;">
                                                        <span id="batchGrowthIcon" class="me-1"></span>
                                                        <span id="batchGrowthValue" class="badge"></span>
                                                    </div>
                                                </div>
                                                <h5 class="card-title text-muted text-uppercase fs-12">Active Batches</h5>
                                                <h2 class="fw-bold text-primary mb-1" id="activeBatches">-</h2>
                                                <div class="text-muted fs-13">
                                                    <i class="fas fa-calendar-alt me-1"></i> Running Intakes
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card kpi-card card-hover border-left-success">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="avatar-initial bg-success bg-opacity-10 text-success">
                                                        <i class="fas fa-clock"></i>
                                                    </div>
                                                    <span class="badge bg-success bg-opacity-10 text-success">Pending</span>
                                                </div>
                                                <h5 class="card-title text-muted text-uppercase fs-12">Pending Approvals
                                                </h5>
                                                <h2 class="fw-bold text-success mb-1" id="pendingApprovals">-</h2>
                                                <div class="text-muted fs-13">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Awaiting action
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card kpi-card card-hover border-left-warning">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="avatar-initial bg-warning bg-opacity-10 text-warning">
                                                        <i class="fas fa-percentage"></i>
                                                    </div>
                                                    <span
                                                        class="badge bg-warning bg-opacity-10 text-warning">Performance</span>
                                                </div>
                                                <h5 class="card-title text-muted text-uppercase fs-12">Avg Attendance Rate
                                                </h5>
                                                <h2 class="fw-bold text-warning mb-1" id="avgAttendanceRate">-</h2>
                                                <div class="text-muted fs-13">
                                                    <i class="fas fa-chart-line me-1"></i> Overall attendance
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Charts Row -->
                                <div class="row mb-4">
                                    <div class="col-xl-8 mb-4">
                                        <div class="card card-hover h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="card-title mb-1">📊 Student Count by Batch</h5>
                                                        <p class="text-muted mb-0">Monitor capacity distribution</p>
                                                    </div>
                                                    <select id="batchChartType" class="form-select form-select-sm"
                                                        style="width: auto;">
                                                        <option value="bar">Bar Chart</option>
                                                        <option value="horizontalBar">Horizontal Bar</option>
                                                    </select>
                                                </div>
                                                <div class="chart-container">
                                                    <canvas id="batchStudentChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-4 mb-4">
                                        <div class="card card-hover h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="card-title mb-1" id="periodPerformanceTitle">🎯 This Month's Performance</h5>
                                                        <p class="text-muted mb-0" id="periodPerformanceSubtitle">Selected time period</p>
                                                    </div>
                                                    <button class="btn btn-outline-secondary btn-sm"
                                                        onclick="refreshOverview()">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                </div>
                                                <div class="row">
                                                    <div class="col-12 mb-3">
                                                        <div
                                                            class="d-flex justify-content-between align-items-center p-3 bg-light-primary rounded">
                                                            <div>
                                                                <div class="text-muted fs-12" id="periodRegistrationsLabel">This Month's Registrations</div>
                                                                <div class="fw-bold fs-18" id="todayRegistrations">0</div>
                                                            </div>
                                                            <div class="text-end">
                                                                <div class="text-muted fs-12">Growth</div>
                                                                <div class="fw-bold fs-14" id="growthPercentage">0%</div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 mb-2">
                                                        <div class="p-3 bg-light-success rounded">
                                                            <div class="text-muted fs-12">Pending Clearances</div>
                                                            <div class="fw-bold fs-16" id="pendingClearances">0</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6 mb-2">
                                                        <div class="p-3 bg-light-warning rounded">
                                                            <div class="text-muted fs-12">Special Approvals</div>
                                                            <div class="fw-bold fs-16" id="specialApprovalNeeded">0</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-3 bg-light-info rounded">
                                                            <div class="text-muted fs-12">Exam Pass Rate</div>
                                                            <div class="fw-bold fs-16" id="passRate">0%</div>
                                                        </div>
                                                    </div>
                                                    <div class="col-6">
                                                        <div class="p-3 bg-light-purple rounded">
                                                            <div class="text-muted fs-12">Semester Reg.</div>
                                                            <div class="fw-bold fs-16" id="monthSemesterReg">0</div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Active Semesters -->
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card card-hover">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="card-title mb-1">📅 Active Semesters</h5>
                                                        <p class="text-muted mb-0">Currently running semesters</p>
                                                    </div>
                                                    <button class="btn btn-outline-primary btn-sm"
                                                        onclick="viewAllSemesters()">
                                                        <i class="fas fa-eye me-1"></i> View All
                                                    </button>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-hover" id="activeSemestersTable">
                                                        <thead>
                                                            <tr>
                                                                <th>Semester</th>
                                                                <th>Course</th>
                                                                <th>Start Date</th>
                                                                <th>End Date</th>
                                                                <th>Registered Students</th>
                                                                <th>Status</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="activeSemestersBody">
                                                            <tr>
                                                                <td colspan="7" class="text-center py-4">
                                                                    <div class="spinner-border spinner-border-sm text-primary"
                                                                        role="status"></div>
                                                                    <span class="ms-2">Loading active semesters...</span>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Approvals Tab -->
                            <div class="tab-pane fade" id="approvals" role="tabpanel">
                                <div class="row">
                                    <div class="col-12">
                                        <div class="card card-hover">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="card-title mb-1">✅ Pending Registration Approvals</h5>
                                                        <p class="text-muted mb-0">Approve or reject student registrations
                                                        </p>
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-outline-success btn-sm"
                                                            onclick="approveAll()">
                                                            <i class="fas fa-check-double me-1"></i> Approve All
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm" onclick="rejectAll()">
                                                            <i class="fas fa-times me-1"></i> Reject All
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Student</th>
                                                                <th>Course</th>
                                                                <th>Batch</th>
                                                                <th>Registration Date</th>
                                                                <th>Fee (LKR)</th>
                                                                <th>Counselor</th>
                                                                <th>Remarks</th>
                                                                <th>Submitted</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="pendingApprovalsBody">
                                                            <tr>
                                                                <td colspan="9" class="text-center py-5">
                                                                    <div class="spinner-border text-primary" role="status">
                                                                        <span class="visually-hidden">Loading...</span>
                                                                    </div>
                                                                    <p class="text-muted mt-2">Loading pending approvals...
                                                                    </p>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Academic Performance Tab -->
                            <div class="tab-pane fade" id="academic" role="tabpanel">
                                <div class="row mb-4">
                                    <div class="col-xl-8 mb-4">
                                        <div class="card card-hover h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="card-title mb-1">📈 Grade Distribution</h5>
                                                        <p class="text-muted mb-0">Overall academic performance</p>
                                                    </div>
                                                    <select id="gradeChartType" class="form-select form-select-sm"
                                                        style="width: auto;">
                                                        <option value="bar">Bar Chart</option>
                                                        <option value="pie">Pie Chart</option>
                                                        <option value="doughnut">Doughnut Chart</option>
                                                    </select>
                                                </div>
                                                <div class="chart-container">
                                                    <canvas id="gradeDistributionChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-4 mb-4">
                                        <div class="card card-hover h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="card-title mb-1">🏆 Top Performing Courses</h5>
                                                        <p class="text-muted mb-0">Course-wise pass rates</p>
                                                    </div>
                                                    <span class="badge badge-purple">Pass Rate</span>
                                                </div>
                                                <div id="coursePerformanceList" class="list-group list-group-flush">
                                                    <div
                                                        class="list-group-item d-flex justify-content-between align-items-center">
                                                        <div class="text-muted">Loading...</div>
                                                        <span class="badge bg-secondary">0%</span>
                                                    </div>
                                                </div>
                                                <div class="mt-3 pt-3 border-top">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div class="text-muted">Repeat Students</div>
                                                        <div class="fw-bold fs-16" id="repeatStudents">0</div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Attendance Tab -->
                            <div class="tab-pane fade" id="attendance" role="tabpanel">
                                <div class="row mb-4">
                                    <div class="col-12">
                                        <div class="card card-hover">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="card-title mb-1">📊 Attendance Overview</h5>
                                                        <p class="text-muted mb-0">Daily attendance trends</p>
                                                    </div>
                                                    <div class="d-flex gap-2">
                                                        <button class="btn btn-outline-secondary btn-sm"
                                                            onclick="exportAttendance()">
                                                            <i class="fas fa-download me-1"></i> Export
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="chart-container">
                                                    <canvas id="attendanceTrendChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="card card-hover">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="card-title mb-1">📋 Course-wise Attendance</h5>
                                                        <p class="text-muted mb-0">Attendance rates by course</p>
                                                    </div>
                                                    <div class="text-end">
                                                        <div class="text-muted fs-12">Overall Attendance</div>
                                                        <div class="fw-bold fs-18" id="overallAttendanceRate">0%</div>
                                                    </div>
                                                </div>
                                                <div class="table-responsive">
                                                    <table class="table table-hover">
                                                        <thead>
                                                            <tr>
                                                                <th>Course</th>
                                                                <th>Attendance Rate</th>
                                                                <th>Total Records</th>
                                                                <th>Status</th>
                                                                <th>Action</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="courseAttendanceBody">
                                                            <tr>
                                                                <td colspan="5" class="text-center py-4">
                                                                    Loading attendance data...
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Clearance Status Tab -->
                            <div class="tab-pane fade" id="clearance" role="tabpanel">
                                <div class="row mb-4">
                                    <div class="col-xl-6 mb-4">
                                        <div class="card card-hover h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="card-title mb-1">📊 Clearance Request Status</h5>
                                                        <p class="text-muted mb-0">By clearance type</p>
                                                    </div>
                                                    <select id="clearanceChartType" class="form-select form-select-sm"
                                                        style="width: auto;">
                                                        <option value="bar">Stacked Bar</option>
                                                        <option value="stackedBar">Grouped Bar</option>
                                                    </select>
                                                </div>
                                                <div class="chart-container">
                                                    <canvas id="clearanceStatusChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 mb-4">
                                        <div class="card card-hover h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="card-title mb-1">🔄 Recent Clearance Requests</h5>
                                                        <p class="text-muted mb-0">Latest 10 requests</p>
                                                    </div>
                                                    <button class="btn btn-outline-primary btn-sm"
                                                        onclick="viewAllClearances()">
                                                        <i class="fas fa-eye me-1"></i> View All
                                                    </button>
                                                </div>
                                                <div class="table-responsive" style="max-height: 300px; overflow-y: auto;">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Student</th>
                                                                <th>Type</th>
                                                                <th>Course</th>
                                                                <th>Status</th>
                                                                <th>Requested</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="recentClearancesBody">
                                                            <tr>
                                                                <td colspan="5" class="text-center py-3">
                                                                    Loading clearance requests...
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Payments Tab -->
                            <div class="tab-pane fade" id="payments" role="tabpanel">
                                <div class="row mb-3">
                                    <div class="col-lg-4 col-md-6 ms-auto">
                                        <label for="paymentCourseFilter" class="form-label fw-semibold text-muted">Course Filter</label>
                                        <select id="paymentCourseFilter" class="form-select form-select-sm">
                                            <option value="">All Courses</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card kpi-card card-hover border-left-success">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="avatar-initial bg-success bg-opacity-10 text-success">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </div>
                                                    <span class="badge bg-success bg-opacity-10 text-success">Total</span>
                                                </div>
                                                <h5 class="card-title text-muted text-uppercase fs-12">Total Revenue</h5>
                                                <h2 class="fw-bold text-success mb-1" id="totalRevenue">-</h2>
                                                <div class="text-muted fs-13">
                                                    <i class="fas fa-coins me-1"></i> Collected amount
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card kpi-card card-hover border-left-warning">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="avatar-initial bg-warning bg-opacity-10 text-warning">
                                                        <i class="fas fa-clock"></i>
                                                    </div>
                                                    <span class="badge bg-warning bg-opacity-10 text-warning">Pending</span>
                                                </div>
                                                <h5 class="card-title text-muted text-uppercase fs-12">Pending Payments</h5>
                                                <h2 class="fw-bold text-warning mb-1" id="pendingPayments">-</h2>
                                                <div class="text-muted fs-13">
                                                    <i class="fas fa-exclamation-circle me-1"></i> Awaiting payment
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card kpi-card card-hover border-left-primary">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="avatar-initial bg-primary bg-opacity-10 text-primary">
                                                        <i class="fas fa-chart-line"></i>
                                                    </div>
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">Monthly</span>
                                                </div>
                                                <h5 class="card-title text-muted text-uppercase fs-12" id="periodRevenueTitle">This Month</h5>
                                                <h2 class="fw-bold text-primary mb-1" id="thisMonthRevenue">-</h2>
                                                <div class="text-muted fs-13" id="periodRevenueSubtitle">
                                                    <i class="fas fa-calendar me-1"></i> Period collection
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-3 col-md-6 mb-4">
                                        <div class="card kpi-card card-hover border-left-purple">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div class="avatar-initial bg-purple bg-opacity-10 text-purple">
                                                        <i class="fas fa-percentage"></i>
                                                    </div>
                                                    <span class="badge bg-purple bg-opacity-10 text-purple">Rate</span>
                                                </div>
                                                <h5 class="card-title text-muted text-uppercase fs-12">Collection Rate</h5>
                                                <h2 class="fw-bold text-purple mb-1" id="collectionRate">-</h2>
                                                <div class="text-muted fs-13">
                                                    <i class="fas fa-chart-pie me-1"></i> Payment efficiency
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-xl-6 mb-4">
                                        <div class="card card-hover h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div>
                                                        <h5 class="card-title mb-1">🆕 New Registrations</h5>
                                                        <p class="text-muted mb-0">Current month course registrations</p>
                                                    </div>
                                                    <span class="badge bg-primary" id="newRegistrationsCount">0</span>
                                                </div>
                                                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                                    <table class="table table-sm align-middle mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Student</th>
                                                                <th>Course</th>
                                                                <th>Date</th>
                                                                <th>Fee</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="newRegistrationsBody">
                                                            <tr>
                                                                <td colspan="4" class="text-center py-3 text-muted">Loading new registrations...</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-xl-6 mb-4">
                                        <div class="card card-hover h-100">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-3">
                                                    <div>
                                                        <h5 class="card-title mb-1">📚 Ongoing Courses</h5>
                                                        <p class="text-muted mb-0">Registered course-wise payment summary</p>
                                                    </div>
                                                    <span class="badge bg-success" id="ongoingCoursesCount">0</span>
                                                </div>
                                                <div class="table-responsive" style="max-height: 320px; overflow-y: auto;">
                                                    <table class="table table-sm align-middle mb-0">
                                                        <thead>
                                                            <tr>
                                                                <th>Course</th>
                                                                <th>New Reg.</th>
                                                                <th>Ongoing</th>
                                                                <th>Paid</th>
                                                                <th>Pending</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="courseWisePaymentsBody">
                                                            <tr>
                                                                <td colspan="5" class="text-center py-3 text-muted">Loading course summary...</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12">
                                        <div class="card card-hover">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-center mb-4">
                                                    <div>
                                                        <h5 class="card-title mb-1">📈 Monthly Revenue Trend</h5>
                                                        <p class="text-muted mb-0">Last 6 months performance</p>
                                                    </div>
                                                    <div class="btn-group btn-group-sm">
                                                        <button class="chart-toggle-btn active"
                                                            onclick="toggleRevenueChart('line')">
                                                            <i class="fas fa-chart-line"></i>
                                                        </button>
                                                        <button class="chart-toggle-btn"
                                                            onclick="toggleRevenueChart('bar')">
                                                            <i class="fas fa-chart-bar"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                <div class="chart-container">
                                                    <canvas id="revenueTrendChart"></canvas>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Rejection Modal -->
    <div class="modal fade" id="rejectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reject Registration</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label">Reason for Rejection</label>
                        <textarea class="form-control" id="rejectionReason" rows="3"
                            placeholder="Enter reason for rejection..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" onclick="confirmReject()">Reject Registration</button>
                </div>
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const dashboardRoutes = {
            studentProfile: @json(url('/student/profile')),
            semesterEditBase: @json(url('/semesters')),
            semestersIndex: @json(route('semesters.index')),
            clearance: @json(route('all.clearance.management')),
            attendance: @json(route('attendance')),
        };
        let currentTimePeriod = 'month';
        let currentRejectId = null;
        let currentSearchQuery = '';
        let chartInstances = {};
        const emptyChartPlugin = {
            id: 'emptyChartMessage',
            afterDraw(chart) {
                const values = (chart.data.datasets || []).flatMap(dataset => dataset.data || []);
                const hasData = values.some(value => Number(value) !== 0);
                if (hasData) {
                    return;
                }
                const { ctx, chartArea } = chart;
                if (!chartArea) {
                    return;
                }
                ctx.save();
                ctx.fillStyle = '#6c757d';
                ctx.font = '13px sans-serif';
                ctx.textAlign = 'center';
                ctx.textBaseline = 'middle';
                ctx.fillText('No data for this period', (chartArea.left + chartArea.right) / 2, (chartArea.top + chartArea.bottom) / 2);
                ctx.restore();
            }
        };
        let analyticsFilters = {
            location: '',
            course_id: '',
            intake_id: '',
            module_id: ''
        };
        const analyticsLocationOptionsCache = new Map();
        let analyticsLocationRequestId = 0;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function () {
            initializeAnalyticsFilters();
            updatePeriodLabels();
            const paymentCourseFilter = document.getElementById('paymentCourseFilter');
            if (paymentCourseFilter) {
                paymentCourseFilter.addEventListener('change', function () {
                    fetchPaymentOverview();
                });
            }
            loadDashboardData();

            const searchButton = document.getElementById('dashboardSearchBtn');
            if (searchButton) {
                searchButton.addEventListener('click', function () {
                    searchDashboard(document.getElementById('searchInput')?.value || '');
                });
            }

            document.getElementById('searchInput')?.addEventListener('keydown', function (e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    searchDashboard(this.value);
                }
            });

            // Load data for active tab
            refreshAllData(false);

            // Tab change event
            document.querySelectorAll('#dashboardTabs button').forEach(tab => {
                tab.addEventListener('shown.bs.tab', function (event) {
                    resizeDashboardCharts();
                    setTimeout(resizeDashboardCharts, 50);
                    const tabId = event.target.id;
                    if (tabId === 'overview-tab') {
                        fetchOverviewMetrics();
                        fetchActiveSemesters();
                    } else if (tabId === 'approvals-tab') {
                        fetchPendingApprovals();
                    } else if (tabId === 'academic-tab') {
                        fetchAcademicPerformance();
                    } else if (tabId === 'attendance-tab') {
                        fetchAttendanceOverview();
                    } else if (tabId === 'clearance-tab') {
                        fetchClearanceStatus();
                    } else if (tabId === 'payments-tab') {
                        fetchPaymentOverview();
                    }
                    applyDashboardSearch();
                });
            });

            // Chart type changes
            document.getElementById('batchChartType').addEventListener('change', function () {
                updateBatchStudentChart(window.lastBatchStudentData || []);
            });

            document.getElementById('gradeChartType').addEventListener('change', function () {
                fetchAcademicPerformance(this.value);
            });

            document.getElementById('clearanceChartType').addEventListener('change', function () {
                fetchClearanceStatus(this.value);
            });

            // Search functionality
            document.getElementById('searchInput').addEventListener('input', function (e) {
                searchDashboard(e.target.value);
            });

            // Auto-refresh every 5 minutes
            setInterval(refreshOverview, 300000);
        });

        function loadDashboardData() {
            updatePeriodLabels();
        }

        async function refreshAllData(showSuccessToast = true) {
            document.body.classList.add('data-loading');

            try {
                await Promise.all([
                    fetchOverviewMetrics(),
                    fetchActiveSemesters(),
                    fetchPendingApprovals(),
                    fetchAcademicPerformance(),
                    fetchAttendanceOverview(),
                    fetchClearanceStatus(),
                    fetchPaymentOverview()
                ]);
                resizeDashboardCharts();
                setTimeout(resizeDashboardCharts, 150);
                applyDashboardSearch();
                if (showSuccessToast) {
                    showToast('Dashboard data refreshed successfully', 'success');
                }
            } catch (error) {
                console.error('Error refreshing dashboard:', error);
                if (showSuccessToast) {
                    showToast('Failed to refresh some dashboard data', 'danger');
                }
            } finally {
                document.body.classList.remove('data-loading');
            }
        }

        function setTimePeriod(period, buttonElement = null) {
            currentTimePeriod = period;

            // Update active button
            document.querySelectorAll('.time-filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.period === period);
            });

            if (buttonElement) {
                buttonElement.blur();
            }

            // Refresh data for the selected period
            updatePeriodLabels();
            refreshAllData();
        }

        function initializeAnalyticsFilters() {
            const locationDropdown = document.getElementById('analyticsLocationFilter');
            const courseDropdown = document.getElementById('analyticsCourseFilter');
            const intakeDropdown = document.getElementById('analyticsIntakeFilter');
            const moduleDropdown = document.getElementById('analyticsModuleFilter');
            const applyButton = document.getElementById('applyAnalyticsFiltersBtn');
            const clearButton = document.getElementById('clearAnalyticsFiltersBtn');

            if (!locationDropdown || !courseDropdown || !intakeDropdown || !moduleDropdown || !applyButton || !clearButton) {
                return;
            }

            locationDropdown.addEventListener('change', async function () {
                const requestId = ++analyticsLocationRequestId;
                courseDropdown.innerHTML = '<option value="">All Courses</option>';
                courseDropdown.disabled = true;
                intakeDropdown.innerHTML = '<option value="">All Intakes</option>';
                intakeDropdown.disabled = true;
                moduleDropdown.innerHTML = '<option value="">All Modules</option>';
                moduleDropdown.disabled = true;

                if (!this.value) {
                    return;
                }

                await loadLocationOptionsForAnalyticsFilter(this.value, requestId);
            });

            courseDropdown.addEventListener('change', async function () {
                intakeDropdown.innerHTML = '<option value="">All Intakes</option>';
                intakeDropdown.disabled = true;
                moduleDropdown.innerHTML = '<option value="">All Modules</option>';
                moduleDropdown.disabled = true;

                const selectedLocation = locationDropdown.value;
                if (!selectedLocation) {
                    return;
                }

                const loaders = [loadIntakesForAnalyticsFilter(selectedLocation, this.value || null)];
                if (this.value) {
                    loaders.push(loadModulesForAnalyticsFilter(this.value));
                }
                await Promise.all(loaders);
            });

            intakeDropdown.addEventListener('change', async function () {
                const courseId = document.getElementById('analyticsCourseFilter').value;
                const intakeId = this.value;

                moduleDropdown.innerHTML = '<option value="">All Modules</option>';
                moduleDropdown.disabled = true;

                if (courseId) {
                    await loadModulesForAnalyticsFilter(courseId, intakeId);
                }
            });

            applyButton.addEventListener('click', function () {
                analyticsFilters.location = locationDropdown.value || '';
                analyticsFilters.course_id = courseDropdown.value || '';
                analyticsFilters.intake_id = intakeDropdown.value || '';
                analyticsFilters.module_id = moduleDropdown.value || '';

                updateAppliedFiltersBanner();

                const academicTab = document.getElementById('academic-tab');
                if (academicTab && window.bootstrap) {
                    bootstrap.Tab.getOrCreateInstance(academicTab).show();
                }

                fetchAcademicPerformance();
                fetchAttendanceOverview();
                fetchClearanceStatus();
                fetchPaymentOverview();
                fetchOverviewMetrics();
                fetchPendingApprovals();
                showToast('Filters applied to Academic Performance and Attendance.', 'success');
            });

            clearButton.addEventListener('click', function () {
                ++analyticsLocationRequestId;
                locationDropdown.value = '';
                courseDropdown.innerHTML = '<option value="">All Courses</option>';
                courseDropdown.disabled = true;
                intakeDropdown.innerHTML = '<option value="">All Intakes</option>';
                intakeDropdown.disabled = true;
                moduleDropdown.innerHTML = '<option value="">All Modules</option>';
                moduleDropdown.disabled = true;

                analyticsFilters = { location: '', course_id: '', intake_id: '', module_id: '' };
                updateAppliedFiltersBanner();

                fetchAcademicPerformance();
                fetchAttendanceOverview();
                fetchClearanceStatus();
                fetchPaymentOverview();
                fetchOverviewMetrics();
                fetchPendingApprovals();
                showToast('Filters cleared', 'info');
            });
        }

        async function loadLocationOptionsForAnalyticsFilter(location, requestId) {
            const courseDropdown = document.getElementById('analyticsCourseFilter');
            const intakeDropdown = document.getElementById('analyticsIntakeFilter');
            if (!courseDropdown || !intakeDropdown) {
                return;
            }

            courseDropdown.innerHTML = '<option value="">Loading courses...</option>';
            courseDropdown.disabled = true;
            intakeDropdown.innerHTML = '<option value="">Loading intakes...</option>';
            intakeDropdown.disabled = true;

            try {
                let payload = analyticsLocationOptionsCache.get(location);
                if (!payload) {
                    const response = await fetch(`{{ route('api.program.admin.l2.courses.by.location') }}?location=${encodeURIComponent(location)}`, {
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });
                    if (!response.ok) {
                        throw new Error(`Request failed with status ${response.status}`);
                    }
                    payload = await response.json();
                    analyticsLocationOptionsCache.set(location, payload);
                }

                if (requestId !== analyticsLocationRequestId) {
                    return;
                }

                courseDropdown.innerHTML = '<option value="">All Courses</option>';
                intakeDropdown.innerHTML = '<option value="">All Intakes</option>';

                const courses = Array.isArray(payload.courses)
                    ? payload.courses
                    : (Array.isArray(payload.data) ? payload.data : []);
                const intakes = Array.isArray(payload.intakes) ? payload.intakes : [];

                if (payload.success && courses.length > 0) {
                    courses.forEach(course => {
                        const option = document.createElement('option');
                        option.value = course.course_id;
                        option.textContent = course.course_name;
                        courseDropdown.appendChild(option);
                    });
                    courseDropdown.disabled = false;
                } else {
                    courseDropdown.innerHTML = '<option value="">No courses found</option>';
                }

                if (payload.success && intakes.length > 0) {
                    intakes.forEach(intake => {
                        const option = document.createElement('option');
                        option.value = intake.intake_id;
                        option.textContent = intake.intake_name || intake.batch || `Intake ${intake.intake_id}`;
                        intakeDropdown.appendChild(option);
                    });
                    intakeDropdown.disabled = false;
                } else {
                    intakeDropdown.innerHTML = '<option value="">No intakes found</option>';
                }
            } catch (error) {
                if (requestId !== analyticsLocationRequestId) {
                    return;
                }
                console.error('Error loading courses for analytics filter:', error);
                courseDropdown.innerHTML = '<option value="">Failed to load courses</option>';
                intakeDropdown.innerHTML = '<option value="">Failed to load intakes</option>';
            }
        }

        async function loadIntakesForAnalyticsFilter(location, courseId = null) {
            const intakeDropdown = document.getElementById('analyticsIntakeFilter');
            if (!intakeDropdown) {
                return;
            }

            intakeDropdown.innerHTML = '<option value="">Loading intakes...</option>';
            intakeDropdown.disabled = true;

            try {
                const params = new URLSearchParams({ location });
                if (courseId) {
                    params.append('course_id', courseId);
                }

                const response = await fetch(`{{ route('api.program.admin.l2.intakes') }}?${params.toString()}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const payload = await response.json();
                const intakes = Array.isArray(payload.data)
                    ? payload.data
                    : (Array.isArray(payload.intakes) ? payload.intakes : []);

                intakeDropdown.innerHTML = '<option value="">All Intakes</option>';

                if (payload.success !== false && intakes.length > 0) {
                    intakes.forEach(intake => {
                        const option = document.createElement('option');
                        option.value = intake.intake_id;
                        option.textContent = intake.intake_name || intake.batch || `Intake ${intake.intake_id}`;
                        intakeDropdown.appendChild(option);
                    });
                    intakeDropdown.disabled = false;
                    return;
                }

                intakeDropdown.innerHTML = '<option value="">No intakes found</option>';
                intakeDropdown.disabled = true;
            } catch (error) {
                console.error('Error loading intakes for analytics filter:', error);
                intakeDropdown.innerHTML = '<option value="">Failed to load intakes</option>';
                intakeDropdown.disabled = true;
            }
        }

        async function loadModulesForAnalyticsFilter(courseId, intakeId = null) {
            const moduleDropdown = document.getElementById('analyticsModuleFilter');
            if (!moduleDropdown) {
                return;
            }

            moduleDropdown.innerHTML = '<option value="">Loading modules...</option>';
            moduleDropdown.disabled = true;

            try {
                // Build query with both course_id and intake_id
                let url = `{{ route('api.program.admin.l2.modules.by.course') }}?course_id=${encodeURIComponent(courseId)}`;
                if (intakeId) {
                    url += `&intake_id=${encodeURIComponent(intakeId)}`;
                }

                const response = await fetch(url, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const payload = await response.json();

                moduleDropdown.innerHTML = '<option value="">All Modules</option>';

                if (payload.success && Array.isArray(payload.data) && payload.data.length > 0) {
                    payload.data.forEach(module => {
                        const option = document.createElement('option');
                        option.value = module.module_id;
                        option.textContent = module.display_name || module.module_name;
                        moduleDropdown.appendChild(option);
                    });
                    moduleDropdown.disabled = false;
                    return;
                }

                moduleDropdown.innerHTML = '<option value="">No modules found</option>';
            } catch (error) {
                console.error('Error loading modules for analytics filter:', error);
                moduleDropdown.innerHTML = '<option value="">Failed to load modules</option>';
            }
        }

        function updateAppliedFiltersBanner() {
            const banner = document.getElementById('appliedFiltersBanner');
            const text = document.getElementById('appliedFiltersText');
            if (!banner || !text) {
                return;
            }

            const locationEl = document.getElementById('analyticsLocationFilter');
            const courseEl = document.getElementById('analyticsCourseFilter');
            const intakeEl = document.getElementById('analyticsIntakeFilter');
            const moduleEl = document.getElementById('analyticsModuleFilter');

            const parts = [];
            if (analyticsFilters.location) {
                parts.push(locationEl?.selectedOptions[0]?.text || analyticsFilters.location);
            }
            if (analyticsFilters.course_id) {
                parts.push(courseEl?.selectedOptions[0]?.text || 'Selected course');
            }
            if (analyticsFilters.intake_id) {
                parts.push(intakeEl?.selectedOptions[0]?.text || 'Selected intake');
            }
            if (analyticsFilters.module_id) {
                parts.push(moduleEl?.selectedOptions[0]?.text || 'Selected module');
            }

            if (parts.length === 0) {
                banner.classList.add('d-none');
                text.textContent = '';
                return;
            }

            text.textContent = ' Current filter: ' + parts.join(' / ') + '.';
            banner.classList.remove('d-none');
        }

        function periodLabel(period) {
            return {
                today: 'Today',
                week: 'This Week',
                month: 'This Month',
                quarter: 'Last 3 Months'
            }[period] || 'This Month';
        }

        function updatePeriodLabels() {
            const label = periodLabel(currentTimePeriod);
            const title = document.getElementById('periodPerformanceTitle');
            const subtitle = document.getElementById('periodPerformanceSubtitle');
            const regs = document.getElementById('periodRegistrationsLabel');
            const revenueTitle = document.getElementById('periodRevenueTitle');
            const revenueSubtitle = document.getElementById('periodRevenueSubtitle');
            if (title) title.textContent = `🎯 ${label} Performance`;
            if (subtitle) subtitle.textContent = `Figures for ${label.toLowerCase()}`;
            if (regs) regs.textContent = `${label} Registrations`;
            if (revenueTitle) revenueTitle.textContent = label;
            if (revenueSubtitle) revenueSubtitle.innerHTML = `<i class="fas fa-calendar me-1"></i> ${label} collection`;
        }

        function destroyChart(key) {
            if (chartInstances[key]) {
                chartInstances[key].destroy();
                chartInstances[key] = null;
            }
        }

        function createChart(key, canvasId, config) {
            const canvas = document.getElementById(canvasId);
            if (!canvas || typeof Chart === 'undefined') {
                return;
            }
            destroyChart(key);
            chartInstances[key] = new Chart(canvas.getContext('2d'), {
                ...config,
                plugins: [...(config.plugins || []), emptyChartPlugin]
            });
        }

        function resizeDashboardCharts() {
            Object.values(chartInstances).forEach(chart => {
                if (chart && typeof chart.resize === 'function') {
                    chart.resize();
                }
            });
        }

        function formatKpiNumber(value, fallback = '0') {
            if (value === null || value === undefined || value === '') {
                return fallback;
            }
            const number = Number(value);
            return Number.isNaN(number) ? String(value) : number.toLocaleString();
        }

        function formatKpiPercent(value) {
            const number = Number(value);
            return `${Number.isNaN(number) ? 0 : number}%`;
        }

        function lastSixMonthLabels() {
            const labels = [];
            const now = new Date();
            for (let i = 5; i >= 0; i--) {
                const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
                labels.push(date.toLocaleString('en-US', { month: 'short', year: 'numeric' }));
            }
            return labels;
        }

        function filterTableRows(tbodyId, query) {
            const body = document.getElementById(tbodyId);
            if (!body) {
                return;
            }

            body.querySelectorAll('tr[data-search-placeholder="1"]').forEach(row => row.remove());

            const q = (query || '').trim().toLowerCase();
            let visible = 0;
            body.querySelectorAll('tr').forEach(row => {
                const match = !q || row.textContent.toLowerCase().includes(q);
                row.style.display = match ? '' : 'none';
                if (match) {
                    visible += 1;
                }
            });

            if (q && visible === 0) {
                const cols = body.closest('table')?.querySelectorAll('thead th').length || 1;
                const placeholder = document.createElement('tr');
                placeholder.dataset.searchPlaceholder = '1';
                placeholder.innerHTML = `<td colspan="${cols}" class="text-center py-3 text-muted">No matching results</td>`;
                body.appendChild(placeholder);
            }
        }

        function applyDashboardSearch() {
            const query = currentSearchQuery;
            [
                'activeSemestersBody',
                'pendingApprovalsBody',
                'courseAttendanceBody',
                'recentClearancesBody',
                'newRegistrationsBody',
                'courseWisePaymentsBody'
            ].forEach(id => filterTableRows(id, query));

            const list = document.getElementById('coursePerformanceList');
            if (list) {
                const q = (query || '').trim().toLowerCase();
                list.querySelectorAll('.list-group-item').forEach(item => {
                    item.style.display = !q || item.textContent.toLowerCase().includes(q) ? '' : 'none';
                });
            }
        }

        function buildDashboardParams(includeModule = false) {
            const params = buildAnalyticsFilterParams(includeModule);
            params.append('period', currentTimePeriod);
            return params;
        }

        function buildAnalyticsFilterParams(includeModule = false) {
            const params = new URLSearchParams();

            if (analyticsFilters.location) {
                params.append('location', analyticsFilters.location);
            }
            if (analyticsFilters.course_id) {
                params.append('course_id', analyticsFilters.course_id);
            }
            if (analyticsFilters.intake_id) {
                params.append('intake_id', analyticsFilters.intake_id);
            }
            if (includeModule && analyticsFilters.module_id) {
                params.append('module_id', analyticsFilters.module_id);
            }

            return params;
        }

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');

            toast.innerHTML = `
                            <div class="d-flex">
                                <div class="toast-body">
                                    <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                                    ${message}
                                </div>
                                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                            </div>
                        `;

            const container = document.querySelector('.toast-container') || document.body;
            container.appendChild(toast);

            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();

            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }

        // Overview Metrics
        async function fetchOverviewMetrics() {
            try {
                const response = await fetch(`/api/program-admin-l2/overview?${buildDashboardParams(false).toString()}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    // Update KPI cards
                    document.getElementById('totalActiveStudents').textContent =
                        formatKpiNumber(data.data.total_active_students);
                    document.getElementById('activeBatches').textContent =
                        formatKpiNumber(data.data.active_batches);
                    document.getElementById('pendingApprovals').textContent =
                        formatKpiNumber(data.data.pending_approvals);
                    document.getElementById('avgAttendanceRate').textContent =
                        formatKpiPercent(data.data.avg_attendance_rate);

                    // Update today's metrics
                    document.getElementById('todayRegistrations').textContent =
                        formatKpiNumber(data.data.today_registrations);
                    document.getElementById('growthPercentage').textContent =
                        formatKpiPercent(data.data.growth_percentage);
                    document.getElementById('pendingClearances').textContent =
                        formatKpiNumber(data.data.pending_clearances);
                    document.getElementById('specialApprovalNeeded').textContent =
                        formatKpiNumber(data.data.special_approval_needed);
                    document.getElementById('passRate').textContent =
                        formatKpiPercent(data.data.pass_rate);
                    document.getElementById('monthSemesterReg').textContent =
                        formatKpiNumber(data.data.month_semester_reg);

                    // Update student count by batch chart
                    window.lastBatchStudentData = data.data.student_count_by_batch || [];
                    updateBatchStudentChart(window.lastBatchStudentData);
                    applyDashboardSearch();

                } else {
                    updateBatchStudentChart([]);
                    showToast(data.message || 'Failed to load overview metrics', 'danger');
                }
            } catch (error) {
                console.error('Error fetching overview metrics:', error);
                updateBatchStudentChart([]);
                showToast('Failed to load dashboard metrics', 'danger');
            }
        }

        function updateBatchStudentChart(data) {
            const chartType = document.getElementById('batchChartType')?.value || 'bar';
            const hasData = Array.isArray(data) && data.length > 0;
            const labels = hasData
                ? data.map(item => {
                    const batch = item.batch || 'N/A';
                    const course = item.course_name ? String(item.course_name).substring(0, 20) : '';
                    return course ? `${batch} (${course})` : batch;
                })
                : ['No batches'];
            const counts = hasData ? data.map(item => Number(item.count) || 0) : [0];

            createChart('batchStudent', 'batchStudentChart', {
                type: chartType === 'horizontalBar' ? 'bar' : chartType,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Student Count',
                        data: counts,
                        backgroundColor: 'rgba(102, 126, 234, 0.8)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 1,
                        borderRadius: chartType === 'bar' ? 6 : 0
                    }]
                },
                options: {
                    indexAxis: chartType === 'horizontalBar' ? 'y' : 'x',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function (context) {
                                    return `Students: ${context.raw}`;
                                }
                            }
                        }
                    },
                    scales: chartType === 'pie' || chartType === 'doughnut' ? {} : {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            });
        }

        // Active Semesters
        async function fetchActiveSemesters() {
            try {
                const response = await fetch(`/api/program-admin-l2/active-semesters?${buildDashboardParams(false).toString()}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                const body = document.getElementById('activeSemestersBody');

                if (data.success && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(semester => {
                        const statusBadge = semester.status === 'active' ?
                            '<span class="badge badge-success">Active</span>' :
                            '<span class="badge badge-warning">' + semester.status + '</span>';

                        html += `
                                        <tr>
                                            <td>${semester.name}</td>
                                            <td>${semester.course_name}</td>
                                            <td>${semester.start_date}</td>
                                            <td>${semester.end_date}</td>
                                            <td>
                                                <span class="badge badge-purple">${semester.registered_count}</span>
                                            </td>
                                            <td>${statusBadge}</td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="viewSemester(${semester.id})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                    });

                    body.innerHTML = html;
                    applyDashboardSearch();
                } else {
                    body.innerHTML = `
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            <i class="fas fa-inbox fa-2x mb-3 opacity-50"></i>
                                            <p>No active semesters found</p>
                                        </td>
                                    </tr>
                                `;
                }
            } catch (error) {
                console.error('Error fetching active semesters:', error);
                const body = document.getElementById('activeSemestersBody');
                body.innerHTML = `
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-danger">
                                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                        <p>Failed to load active semesters</p>
                                    </td>
                                </tr>
                            `;
            }
        }

        // Pending Approvals
        async function fetchPendingApprovals() {
            try {
                const response = await fetch(`/api/program-admin-l2/pending-approvals?${buildDashboardParams(false).toString()}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                const body = document.getElementById('pendingApprovalsBody');

                if (data.success && data.data.length > 0) {
                    let html = '';
                    data.data.forEach(approval => {
                        html += `
                                        <tr>
                                            <td>
                                                <div class="fw-medium">${approval.student_name}</div>
                                                <small class="text-muted">ID: ${approval.student_id}</small>
                                            </td>
                                            <td>${approval.course_name}</td>
                                            <td>${approval.batch}</td>
                                            <td>${approval.registration_date}</td>
                                            <td>${parseFloat(approval.registration_fee).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                                            <td>${approval.counselor_name || 'N/A'}</td>
                                            <td>${approval.remarks || '-'}</td>
                                            <td>${approval.created_at}</td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <button class="btn btn-sm btn-success" onclick="approveRegistration(${approval.id})">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="showRejectionModal(${approval.id})">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-info" onclick="viewStudent(${approval.student_id})">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    `;
                    });

                    body.innerHTML = html;
                    applyDashboardSearch();
                } else {
                    body.innerHTML = `
                                    <tr>
                                        <td colspan="9" class="text-center py-5 text-muted">
                                            <i class="fas fa-check-circle fa-2x mb-3 opacity-50"></i>
                                            <p>No pending approvals</p>
                                            <p class="small">All registrations have been processed</p>
                                        </td>
                                    </tr>
                                `;
                }
            } catch (error) {
                console.error('Error fetching pending approvals:', error);
                const body = document.getElementById('pendingApprovalsBody');
                body.innerHTML = `
                                <tr>
                                    <td colspan="9" class="text-center py-5 text-danger">
                                        <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                                        <p>Failed to load pending approvals</p>
                                        <button class="btn btn-sm btn-outline-danger mt-2" onclick="fetchPendingApprovals()">
                                            Retry
                                        </button>
                                    </td>
                                </tr>
                            `;
            }
        }

        // Academic Performance
        async function fetchAcademicPerformance(chartType = null) {
            try {
                const selectedChartType = chartType || document.getElementById('gradeChartType')?.value || 'bar';
                const params = buildDashboardParams(false);
                const response = await fetch(`/api/program-admin-l2/academic-performance?${params.toString()}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    updateGradeDistributionChart(data.data.grade_distribution, selectedChartType);
                    updateCoursePerformanceList(data.data.course_performance);
                    document.getElementById('repeatStudents').textContent =
                        formatKpiNumber(data.data.repeat_students);
                    applyDashboardSearch();
                } else {
                    updateGradeDistributionChart([], selectedChartType);
                    updateCoursePerformanceList([]);
                }
            } catch (error) {
                console.error('Error fetching academic performance:', error);
                updateGradeDistributionChart([], document.getElementById('gradeChartType')?.value || 'bar');
                updateCoursePerformanceList([]);
            }
        }

        function updateGradeDistributionChart(data, chartType = 'bar') {
            const hasData = Array.isArray(data) && data.length > 0;
            const labels = hasData ? data.map(item => item.grade || 'No Grade') : ['A', 'B', 'C', 'D', 'F'];
            const counts = hasData ? data.map(item => Number(item.count) || 0) : [0, 0, 0, 0, 0];

            createChart('gradeDistribution', 'gradeDistributionChart', {
                type: chartType,
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Number of Students',
                        data: counts,
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(0, 123, 255, 0.8)',
                            'rgba(255, 193, 7, 0.8)',
                            'rgba(220, 53, 69, 0.8)',
                            'rgba(108, 117, 125, 0.8)',
                            'rgba(102, 126, 234, 0.8)'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: chartType === 'pie' || chartType === 'doughnut',
                            position: 'bottom'
                        }
                    },
                    scales: chartType === 'pie' || chartType === 'doughnut' ? {} : {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        }

        function updateCoursePerformanceList(data) {
            const container = document.getElementById('coursePerformanceList');

            if (!data || data.length === 0) {
                container.innerHTML = `
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div class="text-muted">No course performance data</div>
                                    <span class="badge bg-secondary">-</span>
                                </div>
                            `;
                return;
            }

            let html = '';
            data.slice(0, 5).forEach(course => {
                const badgeClass = course.pass_rate >= 70 ? 'badge-success' :
                    course.pass_rate >= 50 ? 'badge-warning' : 'badge-danger';

                html += `
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-medium">${course.course_name}</div>
                                        <small class="text-muted">${course.passed}/${course.total} students</small>
                                    </div>
                                    <span class="badge ${badgeClass}">${course.pass_rate}%</span>
                                </div>
                            `;
            });

            container.innerHTML = html;
        }

        // Attendance Overview
        async function fetchAttendanceOverview() {
            try {
                const params = buildDashboardParams(true);

                const response = await fetch(`/api/program-admin-l2/attendance-overview?${params.toString()}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    updateAttendanceTrendChart(data.data.daily_attendance);
                    updateCourseAttendanceTable(data.data.course_attendance);
                    document.getElementById('overallAttendanceRate').textContent =
                        formatKpiPercent(data.data.overall_stats?.overall_rate);
                    applyDashboardSearch();
                } else {
                    updateAttendanceTrendChart([]);
                    updateCourseAttendanceTable([]);
                }
            } catch (error) {
                console.error('Error fetching attendance overview:', error);
                updateAttendanceTrendChart([]);
                updateCourseAttendanceTable([]);
            }
        }

        function updateAttendanceTrendChart(data) {
            const hasData = Array.isArray(data) && data.length > 0;
            const labels = hasData ? data.map(item => item.attendance_date) : ['No records'];
            const rates = hasData ? data.map(item => Math.round(Number(item.attendance_rate) || 0)) : [0];

            createChart('attendanceTrend', 'attendanceTrendChart', {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Attendance Rate (%)',
                        data: rates,
                        borderColor: 'rgba(102, 126, 234, 1)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function (value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateCourseAttendanceTable(data) {
            const body = document.getElementById('courseAttendanceBody');

            if (!data || data.length === 0) {
                body.innerHTML = `
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        No attendance records found
                                    </td>
                                </tr>
                            `;
                return;
            }

            let html = '';
            data.forEach(course => {
                const statusClass = course.attendance_rate >= 80 ? 'badge-success' :
                    course.attendance_rate >= 60 ? 'badge-warning' : 'badge-danger';
                const statusText = course.attendance_rate >= 80 ? 'Good' :
                    course.attendance_rate >= 60 ? 'Average' : 'Poor';

                html += `
                                <tr>
                                    <td>${course.course_name}</td>
                                    <td>
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar ${course.attendance_rate >= 80 ? 'bg-success' : course.attendance_rate >= 60 ? 'bg-warning' : 'bg-danger'}" 
                                                 style="width: ${course.attendance_rate}%">
                                                ${course.attendance_rate}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>${course.total_records}</td>
                                    <td><span class="badge ${statusClass}">${statusText}</span></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewCourseAttendance('${course.course_name}')">
                                            <i class="fas fa-chart-bar"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
            });

            body.innerHTML = html;
        }

        // Clearance Status
        async function fetchClearanceStatus(chartType = null) {
            const selectedChartType = chartType || document.getElementById('clearanceChartType')?.value || 'bar';
            try {
                const response = await fetch(`/api/program-admin-l2/clearance-status?${buildDashboardParams(false).toString()}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    updateClearanceStatusChart(data.data.clearance_by_type, selectedChartType);
                    updateRecentClearances(data.data.recent_requests);
                    applyDashboardSearch();
                } else {
                    updateClearanceStatusChart({}, selectedChartType);
                    updateRecentClearances([]);
                }
            } catch (error) {
                console.error('Error fetching clearance status:', error);
                updateClearanceStatusChart({}, selectedChartType);
                updateRecentClearances([]);
            }
        }

        function updateClearanceStatusChart(data, chartType = 'bar') {
            const source = data && Object.keys(data).length ? data : {
                'Library Clearance': { pending: 0, approved: 0, rejected: 0 },
                'Hostel Clearance': { pending: 0, approved: 0, rejected: 0 },
                'Payment Clearance': { pending: 0, approved: 0, rejected: 0 },
                'Project Clearance': { pending: 0, approved: 0, rejected: 0 }
            };
            const types = Object.keys(source);
            const pendingData = types.map(type => Number(source[type].pending) || 0);
            const approvedData = types.map(type => Number(source[type].approved) || 0);
            const rejectedData = types.map(type => Number(source[type].rejected) || 0);

            createChart('clearanceStatus', 'clearanceStatusChart', {
                type: 'bar',
                data: {
                    labels: types,
                    datasets: [
                        {
                            label: 'Pending',
                            data: pendingData,
                            backgroundColor: 'rgba(255, 193, 7, 0.8)'
                        },
                        {
                            label: 'Approved',
                            data: approvedData,
                            backgroundColor: 'rgba(40, 167, 69, 0.8)'
                        },
                        {
                            label: 'Rejected',
                            data: rejectedData,
                            backgroundColor: 'rgba(220, 53, 69, 0.8)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    scales: {
                        x: chartType === 'stackedBar' ? {
                            stacked: true
                        } : {},
                        y: chartType === 'stackedBar' ? {
                            stacked: true,
                            beginAtZero: true
                        } : {
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function updateRecentClearances(data) {
            const body = document.getElementById('recentClearancesBody');

            if (!data || data.length === 0) {
                body.innerHTML = `
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">
                                        No recent clearance requests
                                    </td>
                                </tr>
                            `;
                return;
            }

            let html = '';
            data.forEach(request => {
                const status = String(request.status || 'pending').toLowerCase();
                const statusBadge = status === 'approved' ? 'badge-success' :
                    status === 'rejected' ? 'badge-danger' : 'badge-warning';
                const statusText = status.charAt(0).toUpperCase() + status.slice(1);

                html += `
                                <tr>
                                    <td>${request.student_name}</td>
                                    <td><span class="badge bg-info">${request.clearance_type}</span></td>
                                    <td>${request.course_name}</td>
                                    <td><span class="badge ${statusBadge}">${statusText}</span></td>
                                    <td>${request.requested_at}</td>
                                </tr>
                            `;
            });

            body.innerHTML = html;
        }

        // Payment Overview
        async function fetchPaymentOverview() {
            try {
                const paymentCourseFilter = document.getElementById('paymentCourseFilter');
                const courseId = paymentCourseFilter ? paymentCourseFilter.value : '';
                const params = buildDashboardParams(false);
                params.delete('course_id');
                params.delete('intake_id');
                params.delete('module_id');
                if (courseId) {
                    params.set('course_id', courseId);
                }

                const response = await fetch(`/api/program-admin-l2/payment-overview?${params.toString()}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });
                const data = await response.json();

                if (data.success) {
                    populatePaymentCourseFilter(data.data.available_courses || [], courseId);

                    document.getElementById('totalRevenue').textContent =
                        'LKR ' + (Number(data.data.total_revenue || 0).toLocaleString('en-US', { minimumFractionDigits: 2 }));
                    document.getElementById('pendingPayments').textContent =
                        formatKpiNumber(data.data.pending_payments);

                    const periodRevenue = Number(data.data.period_revenue ?? 0);
                    document.getElementById('thisMonthRevenue').textContent =
                        'LKR ' + periodRevenue.toLocaleString('en-US', { minimumFractionDigits: 2 });

                    const paidCount = data.data.payment_stats?.find(p => String(p.status).toLowerCase() === 'paid')?.count || 0;
                    const pendingCount = Number(data.data.pending_payments) || 0;
                    const totalCount = paidCount + pendingCount;
                    const collectionRate = totalCount > 0 ? Math.round((paidCount / totalCount) * 100) : 0;
                    document.getElementById('collectionRate').textContent = collectionRate + '%';

                    document.getElementById('newRegistrationsCount').textContent =
                        formatKpiNumber(data.data.new_registrations_count);
                    document.getElementById('ongoingCoursesCount').textContent =
                        formatKpiNumber(data.data.ongoing_courses_count);

                    updateRevenueTrendChart(data.data.monthly_revenue);
                    updateNewRegistrationsTable(data.data.new_registrations || []);
                    updateCourseWisePaymentsTable(data.data.course_wise_summary || []);
                    applyDashboardSearch();
                } else {
                    updateRevenueTrendChart([]);
                    updateNewRegistrationsTable([]);
                    updateCourseWisePaymentsTable([]);
                }
            } catch (error) {
                console.error('Error fetching payment overview:', error);
                updateRevenueTrendChart([]);
                updateNewRegistrationsTable([]);
                updateCourseWisePaymentsTable([]);
            }
        }

        function populatePaymentCourseFilter(courses, selectedCourseId = '') {
            const dropdown = document.getElementById('paymentCourseFilter');

            if (!dropdown) {
                return;
            }

            const currentValue = dropdown.value;
            dropdown.innerHTML = '<option value="">All Courses</option>';

            (courses || []).forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = course.course_name;
                dropdown.appendChild(option);
            });

            if (selectedCourseId) {
                dropdown.value = selectedCourseId;
            } else if (currentValue) {
                dropdown.value = currentValue;
            }
        }

        function updateNewRegistrationsTable(data) {
            const body = document.getElementById('newRegistrationsBody');

            if (!body) {
                return;
            }

            if (!data || data.length === 0) {
                body.innerHTML = `
                    <tr>
                        <td colspan="4" class="text-center py-3 text-muted">No new registrations found</td>
                    </tr>
                `;
                return;
            }

            body.innerHTML = data.map(item => `
                <tr>
                    <td>${item.student_name || 'N/A'}</td>
                    <td>${item.course_name || 'N/A'}</td>
                    <td>${item.registration_date || 'N/A'}</td>
                    <td>LKR ${(Number(item.registration_fee) || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                </tr>
            `).join('');
        }

        function updateCourseWisePaymentsTable(data) {
            const body = document.getElementById('courseWisePaymentsBody');

            if (!body) {
                return;
            }

            if (!data || data.length === 0) {
                body.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center py-3 text-muted">No course-wise payment data available</td>
                    </tr>
                `;
                return;
            }

            body.innerHTML = data.map(item => `
                <tr>
                    <td>${item.course_name || 'N/A'}</td>
                    <td>${Number(item.new_registrations || 0).toLocaleString()}</td>
                    <td>${Number(item.ongoing_courses || 0).toLocaleString()}</td>
                    <td>LKR ${(Number(item.paid_amount) || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                    <td>LKR ${(Number(item.pending_amount) || 0).toLocaleString('en-US', { minimumFractionDigits: 2 })}</td>
                </tr>
            `).join('');
        }

        function updateRevenueTrendChart(data) {
            const hasData = Array.isArray(data) && data.length > 0;
            const labels = hasData ? data.map(item => item.month) : lastSixMonthLabels();
            const revenues = hasData ? data.map(item => Number(item.revenue) || 0) : labels.map(() => 0);

            createChart('revenueTrend', 'revenueTrendChart', {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue (LKR)',
                        data: revenues,
                        borderColor: 'rgba(40, 167, 69, 1)',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function (value) {
                                    return 'LKR ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        }

        function toggleRevenueChart(type) {
            // This would switch between line and bar chart for revenue
            // Implementation depends on your data structure
        }

        // Approval Actions
        async function approveRegistration(id) {
            if (!confirm('Are you sure you want to approve this registration?')) return;

            try {
                const response = await fetch(`/api/program-admin-l2/approve-registration/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    showToast('Registration approved successfully', 'success');
                    fetchPendingApprovals();
                    fetchOverviewMetrics(); // Refresh KPI
                } else {
                    showToast(data.message || 'Failed to approve registration', 'danger');
                }
            } catch (error) {
                showToast('Failed to approve registration', 'danger');
            }
        }

        function showRejectionModal(id) {
            currentRejectId = id;
            const modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
            modal.show();
        }

        async function confirmReject() {
            const reason = document.getElementById('rejectionReason').value;
            if (!reason.trim()) {
                alert('Please enter a reason for rejection');
                return;
            }

            try {
                const isBulk = currentRejectId === 'all';
                const url = isBulk
                    ? `{{ route('api.program.admin.l2.reject.all') }}?${buildDashboardParams(false).toString()}`
                    : `/api/program-admin-l2/reject-registration/${currentRejectId}`;

                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ reason: reason })
                });

                const data = await response.json();

                if (data.success) {
                    showToast(data.message || 'Registration rejected successfully', 'success');
                    document.getElementById('rejectionReason').value = '';
                    const modal = bootstrap.Modal.getInstance(document.getElementById('rejectionModal'));
                    modal.hide();
                    fetchPendingApprovals();
                    fetchOverviewMetrics(); // Refresh KPI
                } else {
                    showToast(data.message || 'Failed to reject registration', 'danger');
                }
            } catch (error) {
                showToast('Failed to reject registration', 'danger');
            }
        }

        async function approveAll() {
            if (!confirm('Are you sure you want to approve ALL pending registrations?')) return;

            try {
                const response = await fetch(`{{ route('api.program.admin.l2.approve.all') }}?${buildDashboardParams(false).toString()}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    }
                });
                const data = await response.json();
                if (data.success) {
                    showToast(data.message || 'Registrations approved', 'success');
                    fetchPendingApprovals();
                    fetchOverviewMetrics();
                } else {
                    showToast(data.message || 'Failed to approve registrations', 'danger');
                }
            } catch (error) {
                showToast('Failed to approve registrations', 'danger');
            }
        }

        function rejectAll() {
            currentRejectId = 'all';
            const modal = new bootstrap.Modal(document.getElementById('rejectionModal'));
            modal.show();
        }

        // Navigation functions
        function viewAllSemesters() {
            window.location.href = dashboardRoutes.semestersIndex;
        }

        function viewAllClearances() {
            window.location.href = dashboardRoutes.clearance;
        }

        function viewStudent(studentId) {
            window.location.href = `${dashboardRoutes.studentProfile}/${studentId}`;
        }

        function viewSemester(semesterId) {
            window.location.href = `${dashboardRoutes.semesterEditBase}/${semesterId}/edit`;
        }

        function viewCourseAttendance(courseName) {
            window.location.href = `${dashboardRoutes.attendance}?course=${encodeURIComponent(courseName)}`;
        }

        function exportAttendance() {
            showToast('Export feature is under development', 'info');
        }

        function searchDashboard(query) {
            currentSearchQuery = query || '';
            applyDashboardSearch();
        }

        function refreshOverview() {
            fetchOverviewMetrics();
            fetchActiveSemesters();
        }
    </script>
@endsection