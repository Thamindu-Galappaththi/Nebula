@extends('inc.app')

@section('title', 'NEBULA | Marketing Manager Dashboard')

@section('content')
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="{{ asset('css/styles.min.css') }}">
    <link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha384-3B6NwesSXE7YJlcLI9RpRqGf2p/EgVH8BgoKTaUrmKNDkHPStTQ3EyoYjCGXaOTS" crossorigin="anonymous">
    <script nonce="{{ $cspNonce }}" src="{{ asset('libs/chartjs/chart.min.js') }}"></script>
    <script nonce="{{ $cspNonce }}" src="{{ asset('libs/chartjs/chartjs-plugin-datalabels.min.js') }}"></script>

    <style nonce="{{ $cspNonce }}">
        .gradient-border {
            border-image: linear-gradient(90deg, #667eea 0%, #764ba2 100%) 1;
        }
        
        .card-hover {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
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
        
        .source-tag {
            display: inline-block;
            padding: 4px 10px;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .chart-container {
            position: relative;
            height: 300px;
        }
        
        .kpi-card {
            border-left: 4px solid;
            transition: all 0.3s ease;
        }
        
        .kpi-card:hover {
            border-left-width: 6px;
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
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
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
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: transparent;
        }
        
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        
        .avatar-initial {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .data-loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .pulse-animation {
            animation: pulse 1.5s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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
    </style>

    <div class="container-fluid">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="page-title-box d-flex align-items-center justify-content-between">
                    <div class="bg-white p-4 rounded shadow-sm mb-3">
    <div class="d-flex align-items-center">
        <div class="me-3">
            <div class="avatar-initial">
                <i class="fas fa-bullseye"></i>
            </div>
        </div>
        <div>
            <h4 class="mb-1 fw-bold text-dark">🎯 Marketing Manager Dashboard</h4>
            <p class="text-muted mb-0">Track campaign performance and student acquisition metrics</p>
        </div>
    </div>
</div>

                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="refreshAllData()">
                            <i class="fas fa-sync-alt me-1"></i> Refresh
                        </button>
                        <button type="button" class="btn btn-primary btn-sm" onclick="exportDashboard()">
                            <i class="fas fa-download me-1"></i> Export
                        </button>
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
                                <button type="button" class="time-filter-btn" data-period="today">Today</button>
                                <button type="button" class="time-filter-btn" data-period="week">This Week</button>
                                <button type="button" class="time-filter-btn active" data-period="month">This Month</button>
                                <button type="button" class="time-filter-btn" data-period="quarter">This Quarter</button>
                                <button type="button" class="time-filter-btn" data-period="year">This Year</button>
                                <div class="d-inline-block ms-2">
                                    <input type="date" id="customDate" class="form-control form-control-sm" style="width: 140px;">
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
                <div class="card kpi-card card-hover border-left-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="avatar-initial bg-primary bg-opacity-10 text-primary">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <span class="badge bg-primary bg-opacity-10 text-primary">Live</span>
                        </div>
                        <h5 class="card-title text-muted text-uppercase fs-12">Total Registered Students</h5>
                        <h2 class="fw-bold mb-1" id="totalRegistered">-</h2>
                        <div class="text-muted fs-13">
                            <i class="fas fa-chart-line me-1"></i> Active registrations
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card card-hover border-left-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="avatar-initial bg-success bg-opacity-10 text-success">
                                <i class="fas fa-rocket"></i>
                            </div>
                            <div id="growthIndicator" style="display: none;">
                                <span id="growthIcon" class="me-1"></span>
                                <span id="growthValue" class="badge"></span>
                            </div>
                        </div>
                        <h5 class="card-title text-muted text-uppercase fs-12" id="periodMetricTitle">This Month</h5>
                        <h2 class="fw-bold mb-1" id="thisMonth">-</h2>
                        <div class="text-muted fs-13" id="periodMetricSubtext">
                            <i class="fas fa-users me-1"></i> New registrations
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card card-hover border-left-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="avatar-initial bg-warning bg-opacity-10 text-warning">
                                <i class="fas fa-database"></i>
                            </div>
                            <span class="badge bg-warning bg-opacity-10 text-warning">All Time</span>
                        </div>
                        <h5 class="card-title text-muted text-uppercase fs-12">Total Students</h5>
                        <h2 class="fw-bold mb-1" id="totalStudents">-</h2>
                        <div class="text-muted fs-13">
                            <i class="fas fa-server me-1"></i> In database
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card kpi-card card-hover border-left-danger">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div class="avatar-initial bg-danger bg-opacity-10 text-danger">
                                <i class="fas fa-calendar-alt"></i>
                            </div>
                            <span class="badge bg-danger bg-opacity-10 text-danger">Previous</span>
                        </div>
                        <h5 class="card-title text-muted text-uppercase fs-12" id="previousMetricTitle">Last Month</h5>
                        <h2 class="fw-bold mb-1" id="lastMonth">-</h2>
                        <div class="text-muted fs-13" id="previousMetricSubtext">
                            <i class="fas fa-history me-1"></i> Previous period
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
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="card-title mb-1">📊 Marketing Survey Analysis</h5>
                                <p class="text-muted mb-0">Channel performance overview</p>
                            </div>
                            <select id="chartTypeSelect" class="form-select form-select-sm" style="width: auto;">
                                <option value="bar">Bar Chart</option>
                                <option value="line">Line Chart</option>
                                <option value="pie">Pie Chart</option>
                            </select>
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
                                <h5 class="card-title mb-1">📍 Location Distribution</h5>
                                <p class="text-muted mb-0">Student registrations by region</p>
                            </div>
                            <div class="btn-group btn-group-sm" id="locationChartToggles">
                                <button type="button" class="chart-toggle-btn active" data-chart-type="doughnut" onclick="toggleLocationChart('doughnut', this)">
                                    <i class="fas fa-dot-circle"></i>
                                </button>
                                <button type="button" class="chart-toggle-btn" data-chart-type="pie" onclick="toggleLocationChart('pie', this)">
                                    <i class="fas fa-chart-pie"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="locationChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 2 -->
        <div class="row mb-4">
            <div class="col-xl-6 mb-4">
                <div class="card card-hover h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="card-title mb-1" id="trendChartTitle">📈 Registration Trend</h5>
                                <p class="text-muted mb-0" id="trendChartSubtitle">Selected time period</p>
                            </div>
                            <div class="btn-group btn-group-sm" id="trendChartToggles">
                                <button type="button" class="chart-toggle-btn active" data-chart-type="line" onclick="toggleTrendChart('line', this)">
                                    <i class="fas fa-chart-line"></i>
                                </button>
                                <button type="button" class="chart-toggle-btn" data-chart-type="bar" onclick="toggleTrendChart('bar', this)">
                                    <i class="fas fa-chart-bar"></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="monthlyTrendChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-xl-6 mb-4">
                <div class="card card-hover h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="card-title mb-1">🏆 Top Performing Courses</h5>
                                <p class="text-muted mb-0">Most popular courses</p>
                            </div>
                        </div>
                        <div class="chart-container">
                            <canvas id="topCoursesChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row 3 -->
        <div class="row mb-4">
            <div class="col-xl-12">
                <div class="card card-hover">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="card-title mb-1">💰 Marketing ROI by Source</h5>
                                <p class="text-muted mb-0">Conversion rates by marketing channel</p>
                            </div>
                        </div>
                        <div class="chart-container" style="height: 250px;">
                            <canvas id="roiChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Registrations -->
        <div class="row">
            <div class="col-12">
                <div class="card card-hover">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h5 class="card-title mb-1">📋 Recent Registrations</h5>
                                <p class="text-muted mb-0">Latest student sign-ups</p>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Course</th>
                                        <th>Date</th>
                                        <th>Location</th>
                                        <th>Source</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="recentRegistrationsContainer">
                                    <tr>
                                        <td colspan="7" class="text-center py-5">
                                            <div class="spinner-border text-primary" role="status">
                                                <span class="visually-hidden">Loading...</span>
                                            </div>
                                            <p class="text-muted mt-2">Loading registrations...</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
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

        <!-- Quick Stats -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card bg-dark text-white">
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="fs-4 fw-bold" id="avgRegistration">-</div>
                                <div class="text-white-50 fs-13">Avg. Daily Registrations</div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="fs-4 fw-bold" id="bestSource">-</div>
                                <div class="text-white-50 fs-13">Best Performing Source</div>
                            </div>
                            <div class="col-md-3 mb-3 mb-md-0">
                                <div class="fs-4 fw-bold" id="topLocation">-</div>
                                <div class="text-white-50 fs-13">Top Location</div>
                            </div>
                            <div class="col-md-3">
                                <div class="fs-4 fw-bold" id="conversionRate">-</div>
                                <div class="text-white-50 fs-13" id="conversionRateLabel">Period Conversion Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="marketingToastContainer" class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 20000;"></div>

    <div class="modal fade contact-modal" id="contactStudentModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header contact-modal-header">
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
                        <button type="button" class="btn btn-outline-primary contact-row-copy" id="copyStudentEmailBtn">
                            <i class="fas fa-copy me-1"></i> Copy
                        </button>
                    </div>
                    <div class="contact-row">
                        <div class="contact-row-icon"><i class="fas fa-phone"></i></div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="text-muted fs-13 mb-1">Phone</div>
                            <div class="fw-semibold text-break" id="contactStudentMobile">-</div>
                        </div>
                        <button type="button" class="btn btn-outline-primary contact-row-copy" id="copyStudentMobileBtn">
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
        let chartInstances = {};
        
        document.addEventListener('DOMContentLoaded', function() {
            loadDashboardData();

            document.querySelectorAll('.time-filter-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    setTimePeriod(this.dataset.period, this);
                });
            });

            document.getElementById('chartTypeSelect')?.addEventListener('change', function() {
                fetchMarketingSurveyData(this.value);
            });

            document.getElementById('customDate')?.addEventListener('change', function() {
                if (this.value) {
                    setTimePeriod('custom');
                }
            });

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

            setInterval(loadDashboardData, 300000);
        });

        function getPeriodQuery() {
            const params = new URLSearchParams({ period: currentTimePeriod });
            const customDate = document.getElementById('customDate')?.value;
            if (currentTimePeriod === 'custom' && customDate) {
                params.set('date', customDate);
            }
            return params.toString();
        }

        function getPeriodMeta() {
            return {
                today: { title: 'Today', previous: 'Yesterday', short: 'today', trend: 'Today by day' },
                week: { title: 'This Week', previous: 'Last Week', short: 'this week', trend: 'Daily this week' },
                month: { title: 'This Month', previous: 'Last Month', short: 'this month', trend: 'Daily this month' },
                quarter: { title: 'This Quarter', previous: 'Previous Quarter', short: 'this quarter', trend: 'Monthly this quarter' },
                year: { title: 'This Year', previous: 'Last Year', short: 'this year', trend: 'Monthly this year' },
                custom: { title: 'Selected Day', previous: 'Previous Day', short: 'on the selected day', trend: 'Selected day' }
            }[currentTimePeriod] || { title: 'This Month', previous: 'Last Month', short: 'this month', trend: 'Daily this month' };
        }

        function buildChartColors(count) {
            const colors = [
                'rgba(102, 126, 234, 0.8)',
                'rgba(118, 75, 162, 0.8)',
                'rgba(59, 130, 246, 0.8)',
                'rgba(16, 185, 129, 0.8)',
                'rgba(245, 158, 11, 0.8)',
                'rgba(239, 68, 68, 0.8)'
            ];
            return Array.from({ length: count }, (_, index) => colors[index % colors.length]);
        }

        function truncateLabel(value, max = 28) {
            const text = String(value || 'Unknown');
            return text.length > max ? text.substring(0, max) + '...' : text;
        }

        async function loadDashboardData() {
            const surveyType = document.getElementById('chartTypeSelect')?.value || 'bar';
            const trendType = document.querySelector('#trendChartToggles .chart-toggle-btn.active')?.dataset.chartType || 'line';
            const locationType = document.querySelector('#locationChartToggles .chart-toggle-btn.active')?.dataset.chartType || 'doughnut';

            await Promise.all([
                fetchOverviewMetrics(),
                fetchMarketingSurveyData(surveyType),
                fetchMonthlyTrend(trendType),
                fetchLocationData(locationType),
                fetchTopCourses(),
                fetchROIData(),
                fetchRecentRegistrations(1)
            ]);
        }

        async function refreshAllData() {
            document.body.classList.add('data-loading');
            try {
                await loadDashboardData();
                showToast('Dashboard data refreshed', 'success');
            } catch (error) {
                showToast('Failed to refresh dashboard data', 'danger');
            } finally {
                document.body.classList.remove('data-loading');
            }
        }

        function setTimePeriod(period, buttonElement = null) {
            currentTimePeriod = period;

            document.querySelectorAll('.time-filter-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.period === period);
            });

            if (period !== 'custom') {
                const customDateInput = document.getElementById('customDate');
                if (customDateInput) customDateInput.value = '';
            }

            if (buttonElement) buttonElement.blur();
            const periodMeta = getPeriodMeta();
            const trendSubtitle = document.getElementById('trendChartSubtitle');
            if (trendSubtitle) trendSubtitle.textContent = periodMeta.trend;
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
                        ${escapeHtml(message)}
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;

            const container = document.getElementById('marketingToastContainer')
                || document.querySelector('.toast-container')
                || document.body;
            container.appendChild(toast);

            const bsToast = new bootstrap.Toast(toast, { delay: 3000 });
            bsToast.show();
            toast.addEventListener('hidden.bs.toast', () => toast.remove());
        }

        async function fetchOverviewMetrics() {
            try {
                const data = await fetchJson(`/api/marketing-manager/overview?${getPeriodQuery()}`);
                const periodMeta = getPeriodMeta();

                document.getElementById('totalRegistered').textContent = data.total_registered?.toLocaleString() || '0';
                document.getElementById('thisMonth').textContent = (data.period_registrations ?? data.this_month_registrations ?? 0).toLocaleString();
                document.getElementById('lastMonth').textContent = (data.previous_period_registrations ?? data.last_month_registrations ?? 0).toLocaleString();
                document.getElementById('totalStudents').textContent = data.total_students?.toLocaleString() || '0';

                const periodTitle = document.getElementById('periodMetricTitle');
                const periodSubtext = document.getElementById('periodMetricSubtext');
                const previousTitle = document.getElementById('previousMetricTitle');
                if (periodTitle) periodTitle.textContent = periodMeta.title;
                if (periodSubtext) periodSubtext.innerHTML = `<i class="fas fa-users me-1"></i> Registrations ${periodMeta.short}`;
                if (previousTitle) previousTitle.textContent = periodMeta.previous;

                const trendTitle = document.getElementById('trendChartTitle');
                const trendSubtitle = document.getElementById('trendChartSubtitle');
                if (trendTitle) trendTitle.textContent = '📈 Registration Trend';
                if (trendSubtitle) trendSubtitle.textContent = periodMeta.trend;

                const growthIndicator = document.getElementById('growthIndicator');
                const growthValue = document.getElementById('growthValue');
                const growthIcon = document.getElementById('growthIcon');

                if (data.growth_percentage && data.growth_percentage !== 0) {
                    growthIndicator.style.display = 'flex';
                    const isPositive = data.growth_percentage > 0;
                    growthIcon.innerHTML = `<i class="fas fa-arrow-${isPositive ? 'up' : 'down'}"></i>`;
                    growthValue.className = `badge bg-${isPositive ? 'success' : 'danger'} bg-opacity-10 text-${isPositive ? 'success' : 'danger'}`;
                    growthValue.textContent = (isPositive ? '+' : '') + data.growth_percentage + '%';
                } else if (growthIndicator) {
                    growthIndicator.style.display = 'none';
                }

                updateQuickStats(data);
            } catch (error) {
                console.error('Error fetching overview metrics:', error);
                showToast('Failed to load metrics', 'danger');
            }
        }

        function updateQuickStats(data) {
            document.getElementById('avgRegistration').textContent = data.avg_daily != null ? Number(data.avg_daily).toLocaleString() : '-';
            document.getElementById('bestSource').textContent = data.best_source || '-';
            document.getElementById('topLocation').textContent = data.top_location || '-';
            document.getElementById('conversionRate').textContent = data.conversion_rate != null ? data.conversion_rate + '%' : '-';
        }
        
        async function fetchMarketingSurveyData(chartType = 'bar') {
            try {
                const data = await fetchJson(`/api/marketing-manager/marketing-survey?${getPeriodQuery()}`);
                const rows = Array.isArray(data) ? data : [];
                const isCircular = chartType === 'pie' || chartType === 'doughnut';

                renderChart('marketingSurvey', 'marketingSurveyChart', {
                    type: chartType,
                    data: {
                        labels: rows.map(item => item.source),
                        datasets: [{
                            label: 'Number of Students',
                            data: rows.map(item => item.count),
                            backgroundColor: buildChartColors(rows.length),
                            borderColor: chartType === 'line' ? 'rgba(102, 126, 234, 1)' : 'transparent',
                            borderWidth: chartType === 'line' ? 2 : 0,
                            fill: chartType === 'line',
                            tension: 0.4,
                            borderRadius: chartType === 'bar' ? 6 : 0
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: isCircular,
                                position: 'bottom'
                            }
                        },
                        scales: isCircular ? {} : {
                            y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: 'rgba(0, 0, 0, 0.05)' } },
                            x: { grid: { display: false } }
                        }
                    }
                });
            } catch (error) {
                console.error('Error fetching marketing survey data:', error);
            }
        }
        
        async function fetchMonthlyTrend(chartType = 'line') {
            try {
                const data = await fetchJson(`/api/marketing-manager/monthly-trend?${getPeriodQuery()}`);
                const rows = Array.isArray(data) ? data : [];

                renderChart('monthlyTrend', 'monthlyTrendChart', {
                    type: chartType,
                    data: {
                        labels: rows.map(item => item.month),
                        datasets: [{
                            label: 'Registrations',
                            data: rows.map(item => item.count),
                            borderColor: 'rgba(102, 126, 234, 1)',
                            backgroundColor: chartType === 'line' ? 'rgba(102, 126, 234, 0.1)' : 'rgba(102, 126, 234, 0.8)',
                            borderWidth: 2,
                            fill: chartType === 'line',
                            tension: 0.4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { display: false } },
                        scales: { y: { beginAtZero: true } }
                    }
                });
            } catch (error) {
                console.error('Error fetching monthly trend:', error);
            }
        }

        function toggleTrendChart(type, buttonElement) {
            document.querySelectorAll('#trendChartToggles .chart-toggle-btn').forEach(btn => {
                btn.classList.toggle('active', btn === buttonElement || btn.dataset.chartType === type);
            });
            fetchMonthlyTrend(type);
        }
        
        async function fetchLocationData(chartType = 'doughnut') {
            try {
                const data = await fetchJson(`/api/marketing-manager/location-data?${getPeriodQuery()}`);
                const rows = Array.isArray(data) ? data : [];

                renderChart('locationChart', 'locationChart', {
                    type: chartType,
                    data: {
                        labels: rows.map(item => item.location),
                        datasets: [{
                            data: rows.map(item => item.count),
                            backgroundColor: buildChartColors(rows.length)
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

        function toggleLocationChart(type, buttonElement) {
            document.querySelectorAll('#locationChartToggles .chart-toggle-btn').forEach(btn => {
                btn.classList.toggle('active', btn === buttonElement || btn.dataset.chartType === type);
            });
            fetchLocationData(type);
        }
        
        async function fetchTopCourses() {
            try {
                const data = await fetchJson(`/api/marketing-manager/top-courses?${getPeriodQuery()}`);
                const rows = Array.isArray(data) ? data : [];

                renderChart('topCourses', 'topCoursesChart', {
                    type: 'bar',
                    data: {
                        labels: rows.map(item => truncateLabel(item.course_name)),
                        datasets: [{
                            label: 'Registrations',
                            data: rows.map(item => item.registrations),
                            backgroundColor: 'rgba(245, 158, 11, 0.8)',
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
                console.error('Error fetching top courses:', error);
            }
        }
        
        async function fetchROIData() {
            try {
                const data = await fetchJson(`/api/marketing-manager/roi-data?${getPeriodQuery()}`);
                const rows = Array.isArray(data) ? data : [];
                const maxRate = Math.max(100, ...rows.map(item => Number(item.conversion_rate) || 0));

                renderChart('roiChart', 'roiChart', {
                    type: 'bar',
                    data: {
                        labels: rows.map(item => item.source),
                        datasets: [{
                            label: 'Conversion Rate %',
                            data: rows.map(item => item.conversion_rate),
                            backgroundColor: 'rgba(16, 185, 129, 0.8)',
                            borderRadius: 4
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const item = rows[context.dataIndex] || {};
                                        return [
                                            'Students: ' + (item.students ?? 0),
                                            'Converted: ' + (item.registrations ?? 0),
                                            'Conversion: ' + (item.conversion_rate ?? 0) + '%'
                                        ];
                                    }
                                }
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                max: maxRate,
                                ticks: { callback: value => value + '%' }
                            }
                        }
                    }
                });
            } catch (error) {
                console.error('Error fetching ROI data:', error);
            }
        }
        
        async function fetchRecentRegistrations(page = 1) {
            currentPage = page;
            const container = document.getElementById('recentRegistrationsContainer');
            container.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2">Loading registrations...</p>
                    </td>
                </tr>
            `;

            try {
                const payload = await fetchJson(`/api/marketing-manager/recent-registrations?page=${page}&${getPeriodQuery()}`);
                renderRegistrationsTable(payload);
            } catch (error) {
                container.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5 text-danger">
                            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
                            <p>Failed to load registrations</p>
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="fetchRecentRegistrations(${currentPage})">
                                Retry
                            </button>
                        </td>
                    </tr>
                `;
            }
        }

        function renderRegistrationsTable(payload) {
            const container = document.getElementById('recentRegistrationsContainer');
            const rows = Array.isArray(payload) ? payload : (payload.data || []);
            currentPage = payload.current_page || currentPage;
            totalPages = payload.last_page || 1;
            const total = payload.total ?? rows.length;

            const prevBtn = document.getElementById('prevPageBtn');
            const nextBtn = document.getElementById('nextPageBtn');
            if (prevBtn) prevBtn.disabled = currentPage <= 1;
            if (nextBtn) nextBtn.disabled = currentPage >= totalPages;

            if (!rows.length) {
                container.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fas fa-inbox fa-2x mb-3 opacity-50"></i>
                            <p>No registrations found</p>
                            <p class="small">Try changing the time period filter</p>
                        </td>
                    </tr>
                `;
                document.getElementById('registrationsCount').textContent = 'Showing 0 registrations';
                return;
            }

            let html = '';
            rows.forEach(reg => {
                const status = String(reg.status || 'Pending');
                const statusClass = status.toLowerCase() === 'registered' ? 'status-registered' : 'status-pending';
                const studentId = Number(reg.student_id) || 0;

                html += `
                    <tr>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="avatar-initial me-3">
                                    ${escapeHtml((reg.student_name || 'S').charAt(0))}
                                </div>
                                <div>
                                    <div class="fw-medium">${escapeHtml(reg.student_name)}</div>
                                    <small class="text-muted">${escapeHtml(reg.email || '')}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="fw-medium">${escapeHtml(reg.course_name)}</div>
                            <small class="text-muted">${escapeHtml(reg.course_code || '')}</small>
                        </td>
                        <td>
                            <div class="fw-medium">${escapeHtml(reg.registration_date)}</div>
                            <small class="text-muted">${escapeHtml(reg.time || '')}</small>
                        </td>
                        <td>
                            <i class="fas fa-map-marker-alt text-muted me-1"></i>
                            ${escapeHtml(reg.location)}
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
                                <button type="button" class="btn btn-outline-primary btn-sm js-view-student" data-student-id="${studentId}" title="View profile">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button type="button" class="btn btn-outline-success btn-sm js-contact-student" data-name="${escapeHtml(reg.student_name || '')}" data-email="${escapeHtml(reg.email || '')}" data-mobile="${escapeHtml(reg.mobile || '')}" title="Contact">
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
            if (currentPage > 1) fetchRecentRegistrations(currentPage - 1);
        }

        function nextPage() {
            if (currentPage < totalPages) fetchRecentRegistrations(currentPage + 1);
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
            if (!ok) throw new Error('Copy command failed');
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
            if (!hint) return;
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

        function exportDashboard() {
            const period = getPeriodMeta();
            const rows = [
                ['Metric', 'Value'],
                ['Period', period.title],
                ['Total Registered Students', document.getElementById('totalRegistered')?.textContent || '0'],
                [period.title + ' Registrations', document.getElementById('thisMonth')?.textContent || '0'],
                ['Total Students', document.getElementById('totalStudents')?.textContent || '0'],
                [period.previous + ' Registrations', document.getElementById('lastMonth')?.textContent || '0'],
                ['Avg. Daily Registrations', document.getElementById('avgRegistration')?.textContent || '-'],
                ['Best Performing Source', document.getElementById('bestSource')?.textContent || '-'],
                ['Top Location', document.getElementById('topLocation')?.textContent || '-'],
                ['Overall Conversion Rate', document.getElementById('conversionRate')?.textContent || '-']
            ];
            const csv = rows.map(row => row.map(value => `"${String(value).replace(/"/g, '""')}"`).join(',')).join('\n');
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `marketing-manager-dashboard-${currentTimePeriod}.csv`;
            document.body.appendChild(link);
            link.click();
            link.remove();
            URL.revokeObjectURL(url);
        }
    </script>
@endsection
