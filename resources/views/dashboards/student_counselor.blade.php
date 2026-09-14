@extends('inc.app')

@section('title', 'NEBULA | Student Counselor Dashboard')

@section('content')
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="{{ asset('css/styles.min.css') }}">
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha384-3B6NwesSXE7YJlcLI9RpRqGf2p/EgVH8BgoKTaUrmKNDkHPStTQ3EyoYjCGXaOTS" crossorigin="anonymous">
    <script nonce="{{ $cspNonce }}" src="{{ asset('libs/chartjs/chart.min.js') }}"></script>
    <script nonce="{{ $cspNonce }}" src="{{ asset('libs/chartjs/chartjs-plugin-datalabels.min.js') }}"></script>

    <style nonce="{{ $cspNonce }}">
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
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
            white-space: nowrap;
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
        
        .counselor-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #f8f9fa;
            color: #667eea;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
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

        .dashboard-filter-card {
            overflow: hidden;
        }

        .dashboard-filter-bar {
            display: flex;
            align-items: center;
            gap: 12px;
            min-width: 0;
        }

        .dashboard-filter-label {
            flex: 0 0 auto;
            white-space: nowrap;
        }

        .dashboard-filter-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            min-width: 0;
            width: 100%;
        }

        .dashboard-filter-date {
            width: 150px;
            max-width: 100%;
        }

        .kpi-card {
            height: 100%;
        }

        .kpi-card .card-body,
        .kpi-card .card-title,
        .kpi-card .text-muted {
            min-width: 0;
        }

        @media (max-width: 767.98px) {
            .student-counselor-page {
                overflow-x: hidden;
            }

            .page-title-box {
                align-items: stretch !important;
                flex-direction: column;
                gap: 16px;
            }

            .dashboard-page-heading h4 {
                font-size: 1.15rem;
            }

            .dashboard-page-actions {
                width: 100%;
            }

            .dashboard-page-actions .btn {
                width: 100%;
            }

            .dashboard-filter-bar {
                align-items: stretch;
                flex-direction: column;
                gap: 10px;
            }

            .dashboard-filter-controls {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 8px;
            }

            .time-filter-btn {
                width: 100%;
                padding-inline: 10px;
            }

            .dashboard-filter-date-wrap {
                grid-column: 1 / -1;
            }

            .dashboard-filter-date {
                width: 100%;
            }

            .kpi-card h2 {
                font-size: 1.4rem;
            }

            .chart-container {
                height: 220px;
            }

            .card-hover:hover {
                transform: none;
            }

            .dashboard-chart-filter {
                flex: 1 1 100%;
                width: 100%;
            }

            .table-responsive table {
                min-width: 760px;
            }

            .registrations-pagination {
                flex-direction: column;
                align-items: stretch !important;
                gap: 12px;
            }

            .registrations-pagination .d-flex {
                width: 100%;
            }

            .registrations-pagination .btn {
                flex: 1 1 0;
            }

            .registration-filter-group .btn {
                flex: 1 1 auto;
            }
        }

        @media (max-width: 420px) {
            .dashboard-filter-controls {
                grid-template-columns: 1fr;
            }
        }

        .contact-modal .modal-content {
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 24px 48px rgba(102, 126, 234, 0.22);
        }

        .contact-modal .modal-header.contact-modal-header {
            position: relative;
            display: block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-bottom: 0;
            padding: 28px 24px 24px;
            text-align: center;
        }

        .contact-modal-header .btn-close {
            position: absolute;
            top: 16px;
            right: 16px;
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .contact-modal-avatar {
            width: 64px;
            height: 64px;
            margin: 0 auto 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.18);
            border: 2px solid rgba(255, 255, 255, 0.35);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 700;
        }

        .contact-modal-header .modal-title {
            font-size: 1.15rem;
            font-weight: 700;
        }

        .contact-row {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border: 1px solid #eef0f5;
            border-radius: 14px;
            background: #f8f9ff;
        }

        .contact-row + .contact-row {
            margin-top: 12px;
        }

        .contact-row-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .contact-row-copy {
            min-width: 96px;
            border-radius: 10px;
            font-weight: 600;
            flex-shrink: 0;
        }

        .contact-row-copy.is-copied {
            background: #198754;
            border-color: #198754;
            color: #fff;
        }

        .contact-row-copy.is-unavailable {
            opacity: 0.55;
            pointer-events: none;
        }

        .contact-copy-hint {
            min-height: 20px;
            font-size: 13px;
            font-weight: 600;
            color: #198754;
        }

        .min-width-0 {
            min-width: 0;
        }

        .dashboard-page-heading {
            min-width: 0;
        }

        .dashboard-page-heading h4,
        .dashboard-page-heading p {
            overflow-wrap: anywhere;
        }

        .dashboard-chart-filter {
            flex: 0 0 190px;
            width: 190px;
            max-width: 100%;
        }

        .dashboard-chart-filter > .nebula-select,
        .dashboard-chart-filter > .form-select,
        .dashboard-chart-filter .nebula-select-sm,
        .dashboard-chart-filter .nebula-select-toggle {
            width: 100% !important;
            max-width: 100% !important;
            flex: 1 1 auto !important;
        }

        .dashboard-chart-filter .nebula-select-menu {
            position: absolute !important;
            top: calc(100% + 4px) !important;
            right: auto !important;
            bottom: auto !important;
            left: 0 !important;
            width: 100% !important;
            max-width: 100% !important;
            max-height: 240px !important;
            z-index: 2050 !important;
        }

        .chart-container {
            max-width: 100%;
        }

        .table-responsive {
            -webkit-overflow-scrolling: touch;
        }

        .student-counselor-page .card-body > .d-flex.justify-content-between {
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .registration-filter-group {
            flex-wrap: wrap;
        }

        @media (max-width: 575.98px) {
            .bg-white.p-4 {
                padding: 1rem !important;
            }

            .card-body {
                padding: 1rem;
            }
        }

        @media (max-width: 480px) {
            .contact-row {
                flex-wrap: wrap;
            }

            .contact-row-copy {
                width: 100%;
                min-width: 0;
            }
        }
    </style>

    <div class="container-fluid student-counselor-page">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="bg-white p-4 rounded shadow-sm">
                    <div class="page-title-box d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div class="d-flex align-items-start dashboard-page-heading">
                            <div class="me-3">
                                <div class="avatar-initial">
                                    <i class="fas fa-user-graduate"></i>
                                </div>
                            </div>
                            <div>
                                <h4 class="mb-1 fw-bold text-dark">Student Counselor Dashboard</h4>
                                <p class="text-muted mb-0">Monitor student intake and marketing effectiveness</p>
                            </div>
                        </div>
                        <div class="dashboard-page-actions d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm text-nowrap" onclick="refreshAllData()">
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
                <div class="card dashboard-filter-card">
                    <div class="card-body py-3">
                        <div class="dashboard-filter-bar">
                            <span class="dashboard-filter-label text-muted"><i class="fas fa-calendar-alt me-1"></i> Time Period:</span>
                            <div class="dashboard-filter-controls">
                                <button type="button" class="time-filter-btn" data-period="today" onclick="setTimePeriod('today', this)">Today</button>
                                <button type="button" class="time-filter-btn" data-period="week" onclick="setTimePeriod('week', this)">This Week</button>
                                <button type="button" class="time-filter-btn active" data-period="month" onclick="setTimePeriod('month', this)">This Month</button>
                                <button type="button" class="time-filter-btn" data-period="quarter" onclick="setTimePeriod('quarter', this)">Last 3 Months</button>
                                <div class="dashboard-filter-date-wrap">
                                    <input type="date" id="customDate" class="form-control form-control-sm dashboard-filter-date" title="Filter dashboard by a specific date">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card card-hover border-left-purple">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="avatar-initial bg-purple bg-opacity-10 text-purple">
                                <i class="fas fa-users"></i>
                            </div>
                            <span class="badge bg-purple bg-opacity-10 text-purple">All Time</span>
                        </div>
                        <h5 class="card-title text-muted text-uppercase fs-12">Total Registrations</h5>
                        <h2 class="fw-bold text-purple mb-1" id="totalRegistered">-</h2>
                        <div class="text-muted fs-13">
                            <i class="fas fa-user-graduate me-1"></i> All-time registration records
                        </div>
                        <div class="progress mt-3" style="height: 4px;">
                            <div class="progress-bar bg-purple" id="totalProgress" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card card-hover border-left-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="avatar-initial bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-calendar-day"></i>
                            </div>
                            <div id="todayGrowth" style="display: none;">
                                <span id="todayGrowthIcon" class="me-1"></span>
                                <span id="todayGrowthValue" class="badge"></span>
                            </div>
                        </div>
                        <h5 class="card-title text-muted text-uppercase fs-12" id="periodMetricTitle">This Month Registrations</h5>
                        <h2 class="fw-bold text-primary mb-1" id="periodRegistrationsCard">-</h2>
                        <div class="text-muted fs-13" id="periodMetricSubtext">
                            <i class="fas fa-bolt me-1"></i> Registrations this month
                        </div>
                        <div class="progress mt-3" style="height: 4px;">
                            <div class="progress-bar bg-primary" id="periodProgress" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card card-hover border-left-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="avatar-initial bg-success bg-opacity-10 text-success">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <span class="badge bg-success bg-opacity-10 text-success">Today</span>
                        </div>
                        <h5 class="card-title text-muted text-uppercase fs-12">Today's Registrations</h5>
                        <h2 class="fw-bold text-success mb-1" id="todayRegistrations">-</h2>
                        <div class="text-muted fs-13">
                            <i class="fas fa-chart-line me-1"></i> New today
                        </div>
                        <div class="progress mt-3" style="height: 4px;">
                            <div class="progress-bar bg-success" id="todayProgress" style="width: 0%"></div>
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
                            <span class="badge bg-warning bg-opacity-10 text-warning">Action Needed</span>
                        </div>
                        <h5 class="card-title text-muted text-uppercase fs-12" id="pendingCardTitle">Pending</h5>
                        <h2 class="fw-bold text-warning mb-1" id="pendingRegistrations">-</h2>
                        <div class="text-muted fs-13" id="pendingCardSubtext">
                            <i class="fas fa-exclamation-circle me-1"></i> Awaiting approval in this month
                        </div>
                        <div class="progress mt-3" style="height: 4px;">
                            <div class="progress-bar bg-warning" id="pendingProgress" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 1 -->
        <div class="row mb-4">
            <div class="col-xl-8 mb-4">
                <div class="card card-hover h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                            <div>
                                <h5 class="card-title mb-1">📊 Marketing Survey Analysis</h5>
                                <p class="text-muted mb-0">Lead sources overview</p>
                            </div>
                            <div class="dashboard-chart-filter">
                                <select id="surveyChartType" class="form-select form-select-sm">
                                    <option value="bar">Bar Chart</option>
                                    <option value="pie">Pie Chart</option>
                                    <option value="doughnut">Doughnut Chart</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="marketingSurveyChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-4 mb-4">
                <div class="card card-hover h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="card-title mb-1">📈 Registration Trend</h5>
                                <p class="text-muted mb-0" id="trendChartSubtitle">Last 7 days performance</p>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="chart-toggle-btn active" data-chart-type="line" onclick="toggleTrendChart('line', this)">
                                    <i class="fas fa-chart-line"></i>
                                </button>
                                <button type="button" class="chart-toggle-btn" data-chart-type="bar" onclick="toggleTrendChart('bar', this)">
                                    <i class="fas fa-chart-bar"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="dailyTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Student Distribution -->
        <div class="row mb-4">
            <div class="col-xl-6 mb-4">
                <div class="card card-hover h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="card-title mb-1">📍 Student Location Distribution</h5>
                                <p class="text-muted mb-0">Geographic spread</p>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="locationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-6 mb-4">
                <div class="card card-hover h-100">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                            <div>
                                <h5 class="card-title mb-1">🎯 Counselor Performance</h5>
                                <p class="text-muted mb-0">Top performing counselors</p>
                            </div>
                            <div class="dashboard-chart-filter">
                                <select id="performancePeriod" class="form-select form-select-sm">
                                    <option value="week">This Week</option>
                                    <option value="month" selected>This Month</option>
                                    <option value="quarter">This Quarter</option>
                                </select>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="counselorChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Registrations -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card card-hover">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                            <div>
                                <h5 class="card-title mb-1">📋 Recent Student Registrations</h5>
                                <p class="text-muted mb-0">Latest student intake</p>
                            </div>
                            <div class="d-flex gap-2 registration-filter-group">
                                <button type="button" class="btn btn-outline-secondary btn-sm registration-filter-btn active" onclick="filterRegistrations('all', this)">
                                    All
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm registration-filter-btn" onclick="filterRegistrations('pending', this)">
                                    <i class="fas fa-clock me-1"></i> Pending
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm registration-filter-btn" onclick="filterRegistrations('registered', this)">
                                    <i class="fas fa-check me-1"></i> Registered
                                </button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Date & Time</th>
                                        <th>Location</th>
                                        <th>Counselor</th>
                                        <th>Source</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="recentRegistrationsContainer">
                                    <tr>
                                        <td colspan="8" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="text-muted mt-2">Loading student registrations...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 pt-3 border-top registrations-pagination">
                            <div class="text-muted fs-13" id="registrationsCount">
                                Showing 0 registrations
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="prevPageBtn" onclick="previousPage()">
                                    <i class="fas fa-chevron-left"></i> Previous
                                </button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="nextPageBtn" onclick="nextPage()">
                                    Next <i class="fas fa-chevron-right"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        
    </div>

    <div id="counselorToastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 20000;"></div>

    <div class="modal fade contact-modal" id="contactStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header contact-modal-header flex-column">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="contact-modal-avatar" id="contactStudentInitial">S</div>
                    <h5 class="modal-title w-100" id="contactStudentName">Student</h5>
                    <p class="mb-0 mt-1" style="opacity: .85; font-size: 13px;">Copy email or phone to contact this student</p>
                </div>
                <div class="modal-body p-4">
                    <div class="contact-row">
                        <div class="contact-row-icon"><i class="fas fa-envelope"></i></div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="text-muted fs-13 mb-1">Email</div>
                            <div class="fw-semibold text-break" id="contactStudentEmail">-</div>
                        </div>
                        <button type="button" class="btn btn-outline-primary contact-row-copy" id="copyStudentEmailBtn" data-copy-target="contactStudentEmail">
                            <i class="fas fa-copy me-1"></i> Copy
                        </button>
                    </div>
                    <div class="contact-row">
                        <div class="contact-row-icon"><i class="fas fa-phone"></i></div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="text-muted fs-13 mb-1">Phone</div>
                            <div class="fw-semibold text-break" id="contactStudentMobile">-</div>
                        </div>
                        <button type="button" class="btn btn-outline-primary contact-row-copy" id="copyStudentMobileBtn" data-copy-target="contactStudentMobile">
                            <i class="fas fa-copy me-1"></i> Copy
                        </button>
                    </div>
                    <div class="contact-copy-hint mt-3 text-center" id="contactCopyHint" aria-live="polite"></div>
                </div>
            </div>
        </div>
    </div>

    <script nonce="{{ $cspNonce }}">
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        const studentProfileBase = @json(url('/student/profile'));
        let currentPage = 1;
        let totalPages = 1;
        let currentTimePeriod = 'month';
        let currentFilter = 'all';
        let chartInstances = {};
        
        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();
            
            // Add event listeners
            document.getElementById('surveyChartType').addEventListener('change', function() {
                fetchMarketingSurveyData(this.value);
            });
            
            document.getElementById('performancePeriod').addEventListener('change', function() {
                fetchCounselorPerformanceData(this.value);
            });

            document.getElementById('customDate').addEventListener('change', function() {
                if (this.value) {
                    setTimePeriod('custom');
                }
            });
            
            // Auto-refresh every 3 minutes
            setInterval(loadDashboardData, 180000);

            const registrationsContainer = document.getElementById('recentRegistrationsContainer');
            if (registrationsContainer) {
                registrationsContainer.addEventListener('click', function (e) {
                    const contactBtn = e.target.closest('.js-contact-student');
                    if (contactBtn) {
                        e.preventDefault();
                        contactStudent(
                            contactBtn.getAttribute('data-email') || '',
                            contactBtn.getAttribute('data-mobile') || '',
                            contactBtn.getAttribute('data-name') || ''
                        );
                        return;
                    }

                    const viewBtn = e.target.closest('.js-view-student');
                    if (viewBtn) {
                        e.preventDefault();
                        viewStudentDetails(viewBtn.getAttribute('data-student-id'));
                    }
                });
            }

            document.getElementById('copyStudentEmailBtn')?.addEventListener('click', function () {
                copyContactValue(this, document.getElementById('contactStudentEmail')?.textContent, 'Email copied');
            });
            document.getElementById('copyStudentMobileBtn')?.addEventListener('click', function () {
                copyContactValue(this, document.getElementById('contactStudentMobile')?.textContent, 'Phone number copied');
            });
        });
        
        async function loadDashboardData() {
            const surveyType = document.getElementById('surveyChartType')?.value || 'bar';
            if (['week', 'month', 'quarter'].includes(currentTimePeriod) && document.getElementById('performancePeriod')) {
                document.getElementById('performancePeriod').value = currentTimePeriod;
            }

            const trendSubtitle = document.getElementById('trendChartSubtitle');
            if (trendSubtitle) {
                const meta = getPeriodMeta();
                trendSubtitle.textContent = `Registrations ${meta.short}`;
            }

            await Promise.all([
                fetchOverviewMetrics(),
                fetchMarketingSurveyData(surveyType),
                fetchDailyTrend(document.querySelector('.chart-toggle-btn.active')?.dataset.chartType || 'line'),
                fetchLocationData(),
                fetchCounselorPerformanceData(currentTimePeriod),
                fetchRecentRegistrations(1)
            ]);
        }

        async function refreshAllData() {
            try {
                await loadDashboardData();
                showToast('Dashboard data refreshed', 'success');
            } catch (error) {
                showToast('Failed to refresh dashboard data', 'danger');
            }
        }
        
        function setTimePeriod(period, buttonElement = null) {
            currentTimePeriod = period;

            document.querySelectorAll('.time-filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.period === period);
            });

            if (period !== 'custom') {
                const customDateInput = document.getElementById('customDate');
                if (customDateInput) {
                    customDateInput.value = '';
                }
            }

            if (buttonElement) {
                buttonElement.blur();
            }
            
            loadDashboardData();
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, ch => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[ch]));
        }

        async function fetchJson(url) {
            const response = await fetch(url, {
                cache: 'no-store',
                credentials: 'same-origin',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            if (!response.ok) {
                throw new Error('Request failed: ' + response.status);
            }
            return response.json();
        }

        function renderChart(key, canvasId, config) {
            const canvas = document.getElementById(canvasId);
            if (!canvas || typeof Chart === 'undefined') return;
            if (chartInstances[key]) chartInstances[key].destroy();
            chartInstances[key] = new Chart(canvas.getContext('2d'), config);
        }

        function getPeriodMeta() {
            const customDateValue = document.getElementById('customDate')?.value;

            if (currentTimePeriod === 'custom' && customDateValue) {
                const formattedDate = new Date(customDateValue).toLocaleDateString();
                return {
                    title: formattedDate,
                    badge: 'Custom',
                    short: `for ${formattedDate}`
                };
            }

            const meta = {
                today: { title: 'Today', badge: 'Today', short: 'today' },
                week: { title: 'This Week', badge: 'Weekly', short: 'this week' },
                month: { title: 'This Month', badge: 'Monthly', short: 'this month' },
                quarter: { title: 'Last 3 Months', badge: 'Quarter', short: 'in the last 3 months' }
            };

            return meta[currentTimePeriod] || { title: 'This Period', badge: 'Period', short: 'in this period' };
        }

        function buildPeriodQuery(additionalParams = {}, period = currentTimePeriod) {
            const params = new URLSearchParams({ ...additionalParams, period, _t: Date.now().toString() });
            const customDateValue = document.getElementById('customDate')?.value;

            if (period === 'custom' && customDateValue) {
                params.set('date', customDateValue);
            }

            return params.toString();
        }

        function filterRegistrations(filter, buttonElement = null) {
            currentFilter = filter;
            
            document.querySelectorAll('.registration-filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            if (buttonElement) {
                buttonElement.classList.add('active');
                buttonElement.blur();
            }
            
            fetchRecentRegistrations(1);
        }
        
        function showToast(message, type = 'info') {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.setAttribute('aria-live', 'assertive');
            toast.setAttribute('aria-atomic', 'true');
            
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">
                        <i class="fas fa-${type === 'success' ? 'check-circle' : 'info-circle'} me-2"></i>
                        ${escapeHtml(message)}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            const container = document.getElementById('counselorToastContainer')
                || document.querySelector('.toast-container')
                || document.body;
            container.appendChild(toast);
            
            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            
            // Remove after hidden
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }
        
        // Overview Metrics
        async function fetchOverviewMetrics() {
            try {
                const data = await fetchJson(`/api/student-counselor/overview?${buildPeriodQuery()}`);
                const periodMeta = getPeriodMeta();
                
                document.getElementById('totalRegistered').textContent = data.total_registered?.toLocaleString() || '0';
                document.getElementById('periodRegistrationsCard').textContent = (data.selected_period_registrations ?? data.period_registrations ?? 0).toLocaleString();
                document.getElementById('todayRegistrations').textContent = data.today_registrations?.toLocaleString() || '0';
                document.getElementById('pendingRegistrations').textContent = data.period_pending_registrations?.toLocaleString() || '0';

                const periodMetricTitle = document.getElementById('periodMetricTitle');
                const periodMetricSubtext = document.getElementById('periodMetricSubtext');
                const pendingCardTitle = document.getElementById('pendingCardTitle');
                const pendingCardSubtext = document.getElementById('pendingCardSubtext');

                if (periodMetricTitle) periodMetricTitle.textContent = `${periodMeta.title} Registrations`;
                if (periodMetricSubtext) periodMetricSubtext.innerHTML = `<i class="fas fa-bolt me-1"></i> Registrations ${periodMeta.short}`;
                if (pendingCardTitle) pendingCardTitle.textContent = `Pending (${periodMeta.title})`;
                if (pendingCardSubtext) pendingCardSubtext.innerHTML = `<i class="fas fa-exclamation-circle me-1"></i> Awaiting approval ${periodMeta.short}`;
                
                updateProgressBars(data);
                
                const todayGrowth = document.getElementById('todayGrowth');
                const todayGrowthValue = document.getElementById('todayGrowthValue');
                const todayGrowthIcon = document.getElementById('todayGrowthIcon');
                const growthValue = data.period_growth_percentage ?? data.today_growth_percentage ?? 0;
                
                if (growthValue && growthValue !== 0) {
                    todayGrowth.style.display = 'flex';
                    const isPositive = growthValue > 0;
                    
                    todayGrowthIcon.innerHTML = `<i class="fas fa-arrow-${isPositive ? 'up' : 'down'}"></i>`;
                    todayGrowthValue.className = `badge bg-${isPositive ? 'success' : 'danger'} bg-opacity-10 text-${isPositive ? 'success' : 'danger'}`;
                    todayGrowthValue.textContent = (isPositive ? '+' : '') + growthValue + '%';
                } else {
                    todayGrowth.style.display = 'none';
                }
                
                updateQuickStats(data);
            } catch (error) {
                console.error('Error fetching overview metrics:', error);
                showToast('Failed to load dashboard metrics', 'danger');
            }
        }
        
        function updateProgressBars(data) {
            const totalProgress = Math.min(((data.total_registered ?? 0) / 600) * 100, 100);
            const periodProgress = Math.min((((data.selected_period_registrations ?? data.period_registrations ?? 0)) / 200) * 100, 100);
            const todayProgress = Math.min(((data.today_registrations ?? 0) / 50) * 100, 100);
            const pendingProgress = Math.min(((data.period_pending_registrations ?? 0) / 30) * 100, 100);
            
            document.getElementById('totalProgress').style.width = `${totalProgress}%`;
            document.getElementById('periodProgress').style.width = `${periodProgress}%`;
            document.getElementById('todayProgress').style.width = `${todayProgress}%`;
            document.getElementById('pendingProgress').style.width = `${pendingProgress}%`;
        }
        
        function updateQuickStats(data) {
            const avgDaily = document.getElementById('avgDaily');
            const conversionRate = document.getElementById('conversionRate');
            const topCourse = document.getElementById('topCourse');
            const responseTime = document.getElementById('responseTime');

            if (avgDaily && data.avg_daily !== undefined) avgDaily.textContent = Number(data.avg_daily).toLocaleString();
            if (conversionRate && data.conversion_rate !== undefined) conversionRate.textContent = data.conversion_rate + '%';
            if (topCourse && data.top_course) topCourse.textContent = data.top_course;
            if (responseTime && data.avg_response_time) responseTime.textContent = data.avg_response_time;
        }
        
        // Marketing Survey Chart
        async function fetchMarketingSurveyData(chartType = 'bar') {
            try {
                const data = await fetchJson(`/api/student-counselor/marketing-survey?${buildPeriodQuery()}`);
                const rows = Array.isArray(data) ? data : [];

                renderChart('marketingSurvey', 'marketingSurveyChart', {
                    type: chartType,
                    data: {
                        labels: rows.map(item => item.source || 'Unknown'),
                        datasets: [{
                            label: 'Number of Students',
                            data: rows.map(item => item.count),
                            backgroundColor: rows.map((_, index) => {
                                const colors = [
                                    'rgba(102, 126, 234, 0.8)',
                                    'rgba(118, 75, 162, 0.8)',
                                    'rgba(59, 130, 246, 0.8)',
                                    'rgba(16, 185, 129, 0.8)',
                                    'rgba(245, 158, 11, 0.8)',
                                    'rgba(239, 68, 68, 0.8)'
                                ];
                                return colors[index % colors.length];
                            }),
                            borderWidth: 2,
                            borderRadius: chartType === 'bar' ? 6 : 0
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
                        scales: chartType === 'bar' ? {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } },
                            x: { grid: { display: false } }
                        } : {}
                    }
                });
            } catch (error) {
                console.error('Error fetching marketing survey data:', error);
            }
        }
        
        // Daily Trend Chart
        async function fetchDailyTrend(chartType = 'line') {
            try {
                const data = await fetchJson(`/api/student-counselor/daily-trend?${buildPeriodQuery()}`);
                const rows = Array.isArray(data) ? data : [];

                renderChart('dailyTrend', 'dailyTrendChart', {
                    type: chartType,
                    data: {
                        labels: rows.map(item => item.date),
                        datasets: [{
                            label: 'Registrations',
                            data: rows.map(item => item.count),
                            borderColor: 'rgba(118, 75, 162, 1)',
                            backgroundColor: chartType === 'line' ? 'rgba(118, 75, 162, 0.1)' : 'rgba(118, 75, 162, 0.8)',
                            borderWidth: 2,
                            fill: chartType === 'line',
                            tension: 0.4,
                            pointRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: {
                            y: { beginAtZero: true, ticks: { stepSize: 1 } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            } catch (error) {
                console.error('Error fetching daily trend:', error);
            }
        }
        
        function toggleTrendChart(type, buttonElement = null) {
            document.querySelectorAll('.chart-toggle-btn').forEach(btn => {
                btn.classList.remove('active');
            });

            if (buttonElement) {
                buttonElement.classList.add('active');
            }
            
            fetchDailyTrend(type);
        }
        
        // Location Data
        async function fetchLocationData() {
            try {
                const data = await fetchJson(`/api/student-counselor/location-data?${buildPeriodQuery()}`);
                const rows = Array.isArray(data) ? data : [];

                renderChart('locationChart', 'locationChart', {
                    type: 'doughnut',
                    data: {
                        labels: rows.map(item => item.location || 'Unknown'),
                        datasets: [{
                            data: rows.map(item => item.count),
                            backgroundColor: [
                                'rgba(102, 126, 234, 0.8)',
                                'rgba(118, 75, 162, 0.8)',
                                'rgba(59, 130, 246, 0.8)',
                                'rgba(16, 185, 129, 0.8)',
                                'rgba(245, 158, 11, 0.8)'
                            ],
                            borderWidth: 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } }
                    }
                });
            } catch (error) {
                console.error('Error fetching location data:', error);
            }
        }
        
        // Counselor Performance Chart
        async function fetchCounselorPerformanceData(period = 'month') {
            try {
                const data = await fetchJson(`/api/student-counselor/counselor-performance?${buildPeriodQuery({}, period)}`);
                const rows = Array.isArray(data) ? data : [];

                renderChart('counselorChart', 'counselorChart', {
                    type: 'bar',
                    data: {
                        labels: rows.length ? rows.map(item => (item.counselor_name || 'Unknown').substring(0, 15)) : ['No data'],
                        datasets: [{
                            label: 'Students Assisted',
                            data: rows.length ? rows.map(item => item.student_count) : [0],
                            backgroundColor: 'rgba(16, 185, 129, 0.8)',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { x: { beginAtZero: true } }
                    }
                });
            } catch (error) {
                console.error('Error fetching counselor performance data:', error);
            }
        }
        
        // Recent Registrations
        async function fetchRecentRegistrations(page = 1) {
            currentPage = page;
            
            const container = document.getElementById('recentRegistrationsContainer');
            container.innerHTML = `
                <tr>
                    <td colspan="8" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Loading student registrations...</p>
                    </td>
                </tr>
            `;
            
            try {
                const payload = await fetchJson(`/api/student-counselor/recent-registrations?${buildPeriodQuery({ page, filter: currentFilter })}`);
                renderRegistrationsTable(payload);
            } catch (error) {
                container.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5 text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <p>Failed to load student registrations</p>
                            <button class="btn btn-sm btn-outline-danger mt-2" onclick="fetchRecentRegistrations(${currentPage})">
                                Retry
                            </button>
                        </td>
                    </tr>
                `;
            }
        }
        
        function renderRegistrationsTable(payload) {
            const container = document.getElementById('recentRegistrationsContainer');
            const rows = Array.isArray(payload) ? payload : (payload?.data || []);
            currentPage = payload?.current_page || currentPage;
            totalPages = payload?.last_page || 1;
            const total = payload?.total ?? rows.length;

            const prevBtn = document.getElementById('prevPageBtn');
            const nextBtn = document.getElementById('nextPageBtn');
            if (prevBtn) prevBtn.disabled = currentPage <= 1;
            if (nextBtn) nextBtn.disabled = currentPage >= totalPages;

            if (!rows.length) {
                container.innerHTML = `
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x mb-3 opacity-50"></i>
                            <p>No student registrations found</p>
                            <p class="small">Try changing the time period or filter</p>
                        </td>
                    </tr>
                `;
                document.getElementById('registrationsCount').textContent = 'Showing 0 registrations';
                return;
            }

            let html = '';
            rows.forEach(reg => {
                const status = reg.status || 'Pending';
                const statusClass = status.toLowerCase() === 'registered' ? 'status-registered' :
                                  status.toLowerCase() === 'pending' ? 'status-pending' : 'status-special';
                const email = escapeHtml(reg.email || '');
                const studentId = Number(reg.student_id) || 0;

                html += `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-initial me-3" style="width: 36px; height: 36px;">
                                    ${escapeHtml((reg.student_name || 'S').charAt(0))}
                                </div>
                                <div>
                                    <div class="fw-medium">${escapeHtml(reg.student_name)}</div>
                                    <small class="text-muted">${email}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-medium">${escapeHtml(reg.course_name)}</div>
                        </td>
                        <td>
                            <div class="fw-medium">${escapeHtml(reg.registration_date)}</div>
                            <small class="text-muted">${escapeHtml(reg.registration_time || '')}</small>
                        </td>
                        <td>
                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                            ${escapeHtml(reg.location)}
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="counselor-avatar me-2">
                                    ${escapeHtml((reg.counselor_name || 'C').charAt(0))}
                                </div>
                                <div>
                                    <div class="fw-medium">${escapeHtml(reg.counselor_name)}</div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-secondary">
                                ${escapeHtml(reg.marketing_source || 'Direct')}
                            </span>
                        </td>
                        <td>
                            <span class="status-badge ${statusClass}">
                                ${escapeHtml(status)}
                            </span>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button type="button" class="action-btn btn btn-outline-primary btn-sm js-view-student" data-student-id="${studentId}" title="View profile">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="action-btn btn btn-outline-info btn-sm js-contact-student" data-name="${escapeHtml(reg.student_name || '')}" data-email="${escapeHtml(reg.email || '')}" data-mobile="${escapeHtml(reg.mobile || '')}" title="Contact">
                                    <i class="fas fa-envelope"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            });

            container.innerHTML = html;
            document.getElementById('registrationsCount').textContent =
                `Showing ${rows.length} of ${total} registration${total === 1 ? '' : 's'} (page ${currentPage} of ${totalPages})`;
        }
        
        function previousPage() {
            if (currentPage > 1) {
                fetchRecentRegistrations(currentPage - 1);
            }
        }
        
        function nextPage() {
            if (currentPage < totalPages) {
                fetchRecentRegistrations(currentPage + 1);
            }
        }
        
        function viewStudentDetails(studentId) {
            if (!studentId) {
                showToast('Student profile is not available', 'danger');
                return;
            }
            window.location.href = studentProfileBase + '/' + studentId;
        }

        function copyContactValue(button, value, successMessage) {
            const text = String(value || '').trim();
            if (!text || text === '-' || text === 'Not available') {
                setCopyHint('Nothing to copy', false);
                return;
            }

            const copied = () => markCopied(button, successMessage);
            const failed = () => setCopyHint('Could not copy. Select the text and copy it manually.', false);

            if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
                navigator.clipboard.writeText(text).then(copied).catch(() => {
                    try {
                        fallbackCopy(text);
                        copied();
                    } catch (error) {
                        failed();
                    }
                });
                return;
            }

            try {
                fallbackCopy(text);
                copied();
            } catch (error) {
                failed();
            }
        }

        function fallbackCopy(text) {
            const input = document.createElement('textarea');
            input.value = text;
            input.setAttribute('readonly', '');
            input.style.position = 'fixed';
            input.style.top = '0';
            input.style.left = '0';
            input.style.opacity = '0';
            document.body.appendChild(input);
            input.focus();
            input.select();
            input.setSelectionRange(0, text.length);
            const ok = document.execCommand('copy');
            input.remove();
            if (!ok) {
                throw new Error('Copy command failed');
            }
        }

        function markCopied(button, successMessage) {
            if (button) {
                button.classList.add('is-copied');
                button.innerHTML = '<i class="fas fa-check me-1"></i> Copied';
                window.clearTimeout(button._copyReset);
                button._copyReset = window.setTimeout(() => {
                    button.classList.remove('is-copied');
                    button.innerHTML = '<i class="fas fa-copy me-1"></i> Copy';
                }, 2000);
            }
            setCopyHint(successMessage, true);
        }

        function setCopyHint(message, success) {
            const hint = document.getElementById('contactCopyHint');
            if (!hint) {
                return;
            }
            hint.textContent = message || '';
            hint.style.color = success ? '#198754' : '#dc3545';
        }

        function setContactField(id, value, copyBtnId) {
            const el = document.getElementById(id);
            const btn = document.getElementById(copyBtnId);
            const text = String(value || '').trim();
            if (el) el.textContent = text || 'Not available';
            if (btn) {
                btn.classList.toggle('is-unavailable', !text);
                btn.classList.remove('is-copied');
                btn.innerHTML = '<i class="fas fa-copy me-1"></i> Copy';
                btn.disabled = !text;
            }
        }

        function contactStudent(email, mobile, name) {
            email = String(email || '').trim();
            mobile = String(mobile || '').trim();
            name = String(name || '').trim() || 'Student';

            if (!email && !mobile) {
                showToast('No email or phone number is available for this student', 'danger');
                return;
            }

            const nameEl = document.getElementById('contactStudentName');
            const initialEl = document.getElementById('contactStudentInitial');
            if (nameEl) nameEl.textContent = name;
            if (initialEl) initialEl.textContent = name.charAt(0).toUpperCase();

            setContactField('contactStudentEmail', email, 'copyStudentEmailBtn');
            setContactField('contactStudentMobile', mobile, 'copyStudentMobileBtn');
            setCopyHint('', true);

            const modalEl = document.getElementById('contactStudentModal');
            if (modalEl && typeof bootstrap !== 'undefined') {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        }
    </script>
@endsection
