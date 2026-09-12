<link nonce="{{ $cspNonce }}" rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha384-3B6NwesSXE7YJlcLI9RpRqGf2p/EgVH8BgoKTaUrmKNDkHPStTQ3EyoYjCGXaOTS" crossorigin="anonymous">
<script nonce="{{ $cspNonce }}" src="{{ asset('libs/chartjs/chart.min.js') }}"></script>

<style nonce="{{ $cspNonce }}">
    .card-hover {
        transition: all 0.3s ease;
    }
    .card-hover:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.15) !important;
    }
    .kpi-card {
        border-left: 4px solid;
        min-height: 140px;
    }
    .stat-icon {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
    }
    .activity-item {
        padding: 12px;
        border-left: 3px solid #dee2e6;
        transition: all 0.2s;
    }
    .activity-item:hover {
        background: #f8f9fa;
        border-left-color: #667eea;
    }
    .action-badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .time-filter-btn {
        padding: 6px 16px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        background: white;
        transition: all 0.2s;
    }
    .time-filter-btn.active {
        background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-color: transparent;
    }
    .modal-backdrop.show {
        opacity: 0.7;
    }
    .kpi-card[onclick],
    .kpi-card.kpi-clickable {
        cursor: pointer;
    }
    .chart-container {
        position: relative;
        height: 300px;
    }
</style>

<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-white p-4 rounded shadow-sm">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h4 class="mb-1 fw-bold">📊 Program Administrator Dashboard</h4>
                            <p class="text-muted mb-0">Comprehensive system overview and management</p>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
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
    </div>

    <!-- Time Filter -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body py-3">
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="me-3 text-muted"><i class="fas fa-calendar-alt me-1"></i> Period:</span>
                        <button type="button" class="time-filter-btn" data-period="today" onclick="setTimePeriod('today', this)">Today</button>
                        <button type="button" class="time-filter-btn" data-period="week" onclick="setTimePeriod('week', this)">This Week</button>
                        <button type="button" class="time-filter-btn active" data-period="month" onclick="setTimePeriod('month', this)">This Month</button>
                        <button type="button" class="time-filter-btn" data-period="year" onclick="setTimePeriod('year', this)">This Year</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI Cards Row 1 - Students -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card kpi-card kpi-clickable card-hover border-left-primary" onclick="showStudentDetails()">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-users"></i>
                        </div>
                        <span class="badge bg-primary">Active</span>
                    </div>
                    <h5 class="text-muted text-uppercase fs-12 mb-2" id="studentsCardTitle">Total Students</h5>
                    <h2 class="fw-bold mb-1" id="totalStudents">-</h2>
                    <div class="text-muted fs-13" id="studentsSubtext">
                        -
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card kpi-card kpi-clickable card-hover border-left-success" onclick="showRegistrationDetails()">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <span class="badge bg-success">Live</span>
                    </div>
                    <h5 class="text-muted text-uppercase fs-12 mb-2" id="registrationsCardTitle">Course Registrations</h5>
                    <h2 class="fw-bold mb-1" id="totalRegistrations">-</h2>
                    <div class="text-muted fs-13" id="registrationsSubtext">
                        -
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card kpi-card kpi-clickable card-hover border-left-warning" onclick="showClearanceDetails()">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-clipboard-check"></i>
                        </div>
                        <span class="badge bg-warning">Action</span>
                    </div>
                    <h5 class="text-muted text-uppercase fs-12 mb-2" id="clearancesCardTitle">Clearance Requests</h5>
                    <h2 class="fw-bold mb-1" id="pendingClearances">-</h2>
                    <div class="text-muted fs-13" id="clearancesSubtext">
                        Pending approval
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
    <a href="{{ route('payment.summary') }}" style="text-decoration: none;">
        <div class="card kpi-card card-hover border-left-info">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="ti ti-chart-pie"></i>
                    </div>
                    <span class="badge bg-info">Payments</span>
                </div>

                <h5 class="text-muted text-uppercase fs-12 mb-2">Payment Dashboard</h5>

                <h2 class="fw-bold mb-1">View</h2>

                <div class="text-muted fs-13">
                    Go to payment summary
                </div>
            </div>
        </div>
    </a>
</div>

    </div>

    <!-- KPI Cards Row 2 - Academic & System -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card kpi-card card-hover border-left-danger">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon bg-danger bg-opacity-10 text-danger">
                            <i class="fas fa-book"></i>
                        </div>
                    </div>
                    <h5 class="text-muted text-uppercase fs-12 mb-2">Total Courses</h5>
                    <h2 class="fw-bold mb-1" id="totalCourses">-</h2>
                    <div class="text-muted fs-13">
                        <span id="activeIntakes">-</span> active intakes
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card kpi-card card-hover border-left-secondary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon bg-secondary bg-opacity-10 text-secondary">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                    </div>
                    <h5 class="text-muted text-uppercase fs-12 mb-2" id="attendanceCardTitle">Attendance Today</h5>
                    <h2 class="fw-bold mb-1" id="attendanceToday">-</h2>
                    <div class="text-muted fs-13" id="attendanceSubtext">
                        Records taken
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card kpi-card card-hover border-left-dark">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon bg-dark bg-opacity-10 text-dark">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    </div>
                    <h5 class="text-muted text-uppercase fs-12 mb-2">System Users</h5>
                    <h2 class="fw-bold mb-1" id="totalUsers">-</h2>
                    <div class="text-muted fs-13">
                        Active accounts
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card kpi-card card-hover border-left-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                            <i class="fas fa-user-plus"></i>
                        </div>
                    </div>
                    <h5 class="text-muted text-uppercase fs-12 mb-2" id="newStudentsCardTitle">New Students</h5>
                    <h2 class="fw-bold mb-1" id="newStudents">-</h2>
                    <div class="text-muted fs-13" id="newStudentsSubtext">
                        This period
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <div class="col-xl-6 mb-4">
            <div class="card card-hover h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-1">📈 Registration Trend</h5>
                            <p class="text-muted mb-0" id="registrationTrendSubtitle">For this month</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="registrationTrendChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card card-hover h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-1">🎓 Top Courses</h5>
                            <p class="text-muted mb-0" id="topCoursesSubtitle">By registrations in this month</p>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="topCoursesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Action Items & Activities -->
    <div class="row mb-4">
        <div class="col-xl-6 mb-4">
            <div class="card card-hover h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-1">⚡ Action Items</h5>
                            <p class="text-muted mb-0">Items requiring attention</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshActionItems()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div id="actionItemsContainer" style="max-height: 400px; overflow-y: auto;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6 mb-4">
            <div class="card card-hover h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h5 class="card-title mb-1">🔔 Recent Activities</h5>
                            <p class="text-muted mb-0">Latest system activities</p>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="refreshActivities()">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div id="activitiesContainer" style="max-height: 400px; overflow-y: auto;">
                        <div class="text-center py-5">
                            <div class="spinner-border text-primary"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row">
        <div class="col-12">
            <div class="card card-hover">
                <div class="card-body">
                    <h5 class="card-title mb-4">🔗 Quick Links</h5>
                    <div class="row g-3">
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('student_management.registration') }}" class="text-decoration-none">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-user-plus fa-2x text-primary mb-2"></i>
                                    <div class="fw-medium">Student Registration</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('course.registration') }}" class="text-decoration-none">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-graduation-cap fa-2x text-success mb-2"></i>
                                    <div class="fw-medium">Course Registration</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('payment.plan') }}" class="text-decoration-none">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-file-invoice-dollar fa-2x text-warning mb-2"></i>
                                    <div class="fw-medium">Payment Plans</div>
                                </div>
                            </a>
                        </div>
                        <div class="col-md-3 col-sm-6">
                            <a href="{{ route('all.clearance.management') }}" class="text-decoration-none">
                                <div class="p-3 bg-light rounded text-center">
                                    <i class="fas fa-clipboard-check fa-2x text-info mb-2"></i>
                                    <div class="fw-medium">Clearance Management</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Student Details Modal -->
<div class="modal fade" id="studentDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">👥 Student Statistics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="chart-container">
                    <canvas id="studentStatsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Registration Details Modal -->
<div class="modal fade" id="registrationDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">📝 Registration Statistics</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="chart-container">
                    <canvas id="registrationStatsChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Clearance Details Modal -->
<div class="modal fade" id="clearanceDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">✅ Pending Clearances</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="clearanceTableContainer"></div>
            </div>
        </div>
    </div>
</div>

<!-- Financial Details Modal -->
<div class="modal fade" id="financialDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">💰 Financial Overview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="text-muted">Total Revenue</h6>
                                <h3 id="modalTotalRevenue">-</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="text-muted">This Period</h6>
                                <h3 id="modalPeriodRevenue">-</h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card text-center">
                            <div class="card-body">
                                <h6 class="text-muted">Pending</h6>
                                <h3 id="modalPendingAmount">-</h3>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="latePaymentsContainer"></div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
const csrfMeta = document.querySelector('meta[name="csrf-token"]');
const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
let currentPeriod = 'month';
let chartInstances = {};

document.addEventListener('DOMContentLoaded', function() {
    loadDashboardData();
    setInterval(() => loadDashboardData(), 300000);
});

function loadDashboardData() {
    fetchOverviewMetrics();
    fetchStudentStats();
    fetchCourseRegistrationStats();
    fetchRecentActivities();
    fetchActionItems();
}

function setTimePeriod(period, buttonElement = null) {
    currentPeriod = period;
    document.querySelectorAll('.time-filter-btn').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.period === period);
    });
    if (buttonElement) buttonElement.blur();
    loadDashboardData();
}

function formatMetricValue(value) {
    return Number(value ?? 0).toLocaleString();
}

function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>"']/g, ch => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
    }[ch]));
}

function getPeriodLabels() {
    const labels = {
        today: { short: 'today', title: 'Today' },
        week: { short: 'this week', title: 'This Week' },
        month: { short: 'this month', title: 'This Month' },
        year: { short: 'this year', title: 'This Year' }
    };
    return labels[currentPeriod] || { short: 'this period', title: 'This Period' };
}

async function fetchJson(url) {
    const response = await fetch(url, {
        headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        credentials: 'same-origin'
    });
    if (!response.ok) {
        throw new Error('Request failed: ' + response.status);
    }
    return response.json();
}

function renderChart(key, canvasId, config) {
    const canvas = document.getElementById(canvasId);
    if (!canvas || typeof Chart === 'undefined') return;
    if (chartInstances[key]) {
        chartInstances[key].destroy();
    }
    chartInstances[key] = new Chart(canvas.getContext('2d'), config);
}

function openModalThen(modalId, callback) {
    const el = document.getElementById(modalId);
    if (!el || typeof bootstrap === 'undefined') return;
    const modal = bootstrap.Modal.getOrCreateInstance(el);
    const run = () => {
        el.removeEventListener('shown.bs.modal', run);
        callback();
    };
    el.addEventListener('shown.bs.modal', run);
    modal.show();
    if (el.classList.contains('show')) {
        run();
    }
}

async function fetchOverviewMetrics() {
    try {
        const data = await fetchJson(`/api/admin-l1/overview?period=${currentPeriod}`);
        const periodLabels = getPeriodLabels();

        document.getElementById('totalStudents').textContent = formatMetricValue(data.total_students);
        document.getElementById('studentsSubtext').textContent = `${formatMetricValue(data.active_students)} active • ${formatMetricValue(data.new_students_this_period)} joined ${periodLabels.short}`;

        document.getElementById('registrationsCardTitle').textContent = `Course Registrations (${periodLabels.title})`;
        document.getElementById('totalRegistrations').textContent = formatMetricValue(data.registrations_this_period);
        document.getElementById('registrationsSubtext').textContent = `${formatMetricValue(data.pending_registrations)} pending • ${formatMetricValue(data.total_registrations)} total`;

        document.getElementById('clearancesCardTitle').textContent = `Clearance Requests (${periodLabels.title})`;
        document.getElementById('pendingClearances').textContent = formatMetricValue(data.clearances_this_period);
        document.getElementById('clearancesSubtext').textContent = `${formatMetricValue(data.pending_clearances)} pending approval overall`;

        document.getElementById('totalCourses').textContent = formatMetricValue(data.total_courses);
        document.getElementById('activeIntakes').textContent = formatMetricValue(data.active_intakes);
        document.getElementById('attendanceCardTitle').textContent = `Attendance ${periodLabels.title}`;
        document.getElementById('attendanceToday').textContent = formatMetricValue(data.attendance_records_this_period);
        document.getElementById('attendanceSubtext').textContent = currentPeriod === 'today' ? 'Records taken today' : `Records taken ${periodLabels.short}`;
        document.getElementById('totalUsers').textContent = formatMetricValue(data.total_users);
        document.getElementById('newStudentsCardTitle').textContent = `New Students (${periodLabels.title})`;
        document.getElementById('newStudents').textContent = formatMetricValue(data.new_students_this_period);
        document.getElementById('newStudentsSubtext').textContent = `Joined ${periodLabels.short}`;
    } catch (error) {
        console.error('Error fetching overview:', error);
    }
}

async function fetchStudentStats() {
    try {
        const data = await fetchJson(`/api/admin-l1/student-stats?period=${currentPeriod}`);
        const periodLabels = getPeriodLabels();
        const trendSubtitle = document.getElementById('registrationTrendSubtitle');
        if (trendSubtitle) trendSubtitle.textContent = `For ${periodLabels.short}`;

        const trend = Array.isArray(data.registration_trend) ? data.registration_trend : [];
        renderChart('registrationTrend', 'registrationTrendChart', {
            type: 'line',
            data: {
                labels: trend.map(item => item.month),
                datasets: [{
                    label: 'Students',
                    data: trend.map(item => item.count),
                    borderColor: 'rgba(102, 126, 234, 1)',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    } catch (error) {
        console.error('Error fetching student stats:', error);
    }
}

async function fetchCourseRegistrationStats() {
    try {
        const data = await fetchJson(`/api/admin-l1/course-registration-stats?period=${currentPeriod}`);
        const periodLabels = getPeriodLabels();
        const topCoursesSubtitle = document.getElementById('topCoursesSubtitle');
        if (topCoursesSubtitle) topCoursesSubtitle.textContent = `By registrations in ${periodLabels.short}`;

        const courses = Array.isArray(data.top_courses) ? data.top_courses : [];
        renderChart('topCourses', 'topCoursesChart', {
            type: 'bar',
            data: {
                labels: courses.map(item => {
                    const name = item.course_name || 'Unknown';
                    return name.length > 20 ? name.substring(0, 20) + '...' : name;
                }),
                datasets: [{
                    label: 'Registrations',
                    data: courses.map(item => item.registrations),
                    backgroundColor: 'rgba(118, 75, 162, 0.8)'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }
            }
        });
    } catch (error) {
        console.error('Error fetching course stats:', error);
    }
}

async function fetchRecentActivities() {
    try {
        const data = await fetchJson(`/api/admin-l1/recent-activities?limit=20`);
        renderActivities(data);
    } catch (error) {
        console.error('Error fetching activities:', error);
        const container = document.getElementById('activitiesContainer');
        if (container) container.innerHTML = '<p class="text-danger text-center py-3">Could not load activities</p>';
    }
}

function renderActivities(activities) {
    const container = document.getElementById('activitiesContainer');
    if (!container) return;
    if (!Array.isArray(activities) || activities.length === 0) {
        container.innerHTML = '<p class="text-muted text-center py-3">No recent activities</p>';
        return;
    }

    const icons = {
        'Student Registration': 'fa-user-plus text-primary',
        'Course Registration': 'fa-graduation-cap text-success',
        'Payment': 'fa-money-bill-wave text-info',
        'Clearance Request': 'fa-clipboard-check text-warning'
    };

    container.innerHTML = activities.map(activity => `
        <div class="activity-item mb-2">
            <div class="d-flex align-items-start">
                <i class="fas ${icons[activity.type] || 'fa-circle'} me-3 mt-1"></i>
                <div class="flex-grow-1">
                    <div class="fw-medium">${escapeHtml(activity.description)}</div>
                    <small class="text-muted">${activity.created_at ? new Date(activity.created_at).toLocaleString() : ''}</small>
                </div>
            </div>
        </div>
    `).join('');
}

async function fetchActionItems() {
    try {
        const data = await fetchJson(`/api/admin-l1/action-items`);
        renderActionItems(data);
    } catch (error) {
        console.error('Error fetching action items:', error);
        const container = document.getElementById('actionItemsContainer');
        if (container) container.innerHTML = '<p class="text-danger text-center py-3">Could not load action items</p>';
    }
}

function renderActionItems(items) {
    const container = document.getElementById('actionItemsContainer');
    if (!container) return;
    let html = '';

    if (items && items.pending_registrations && items.pending_registrations.length > 0) {
        html += '<h6 class="text-muted mb-3">Pending Registrations</h6>';
        items.pending_registrations.forEach(reg => {
            html += `
                <div class="activity-item mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-medium">${escapeHtml(reg.student_name)}</div>
                            <small class="text-muted">${escapeHtml(reg.course_name)}</small>
                        </div>
                        <span class="action-badge bg-warning text-dark">Pending</span>
                    </div>
                </div>
            `;
        });
    }

    if (items && items.pending_clearances && items.pending_clearances.length > 0) {
        html += '<h6 class="text-muted mb-3 mt-4">Pending Clearances</h6>';
        items.pending_clearances.forEach(clearance => {
            html += `
                <div class="activity-item mb-2">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="fw-medium">${escapeHtml(clearance.student_name)}</div>
                            <small class="text-muted">${escapeHtml(clearance.clearance_type)} clearance</small>
                        </div>
                        <span class="action-badge bg-info text-white">Review</span>
                    </div>
                </div>
            `;
        });
    }

    container.innerHTML = html || '<p class="text-muted text-center py-3">No action items</p>';
}

function showStudentDetails() {
    openModalThen('studentDetailsModal', async () => {
        try {
            const data = await fetchJson(`/api/admin-l1/student-stats?period=${currentPeriod}`);
            const locations = Array.isArray(data.by_location) ? data.by_location : [];
            renderChart('studentStats', 'studentStatsChart', {
                type: 'doughnut',
                data: {
                    labels: locations.map(item => item.institute_location || 'Unknown'),
                    datasets: [{
                        data: locations.map(item => item.count),
                        backgroundColor: [
                            'rgba(102, 126, 234, 0.8)',
                            'rgba(118, 75, 162, 0.8)',
                            'rgba(59, 130, 246, 0.8)'
                        ]
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        } catch (error) {
            console.error('Error loading student details:', error);
        }
    });
}

function showRegistrationDetails() {
    openModalThen('registrationDetailsModal', async () => {
        try {
            const data = await fetchJson(`/api/admin-l1/course-registration-stats?period=${currentPeriod}`);
            const statuses = Array.isArray(data.by_status) ? data.by_status : [];
            renderChart('registrationStats', 'registrationStatsChart', {
                type: 'pie',
                data: {
                    labels: statuses.map(item => item.status || 'Unknown'),
                    datasets: [{
                        data: statuses.map(item => item.count),
                        backgroundColor: [
                            'rgba(16, 185, 129, 0.8)',
                            'rgba(245, 158, 11, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(59, 130, 246, 0.8)'
                        ]
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false }
            });
        } catch (error) {
            console.error('Error loading registration details:', error);
        }
    });
}

function showClearanceDetails() {
    openModalThen('clearanceDetailsModal', async () => {
        const container = document.getElementById('clearanceTableContainer');
        try {
            const data = await fetchJson(`/api/admin-l1/clearance-stats`);
            const list = Array.isArray(data.pending_list) ? data.pending_list : [];
            if (!list.length) {
                container.innerHTML = '<p class="text-muted text-center py-3">No pending clearances</p>';
                return;
            }

            let html = '<table class="table table-hover"><thead><tr>' +
                       '<th>Student</th><th>Type</th><th>Course</th><th>Date</th><th>Status</th>' +
                       '</tr></thead><tbody>';
            list.forEach(clearance => {
                html += `
                    <tr>
                        <td>${escapeHtml(clearance.student_name)}</td>
                        <td><span class="badge bg-info">${escapeHtml(clearance.clearance_type)}</span></td>
                        <td>${escapeHtml(clearance.course_name || '-')}</td>
                        <td>${clearance.created_at ? new Date(clearance.created_at).toLocaleDateString() : '-'}</td>
                        <td><span class="badge bg-warning">${escapeHtml(clearance.status)}</span></td>
                    </tr>
                `;
            });
            html += '</tbody></table>';
            container.innerHTML = html;
        } catch (error) {
            console.error('Error loading clearance details:', error);
            if (container) container.innerHTML = '<p class="text-danger text-center py-3">Could not load clearances</p>';
        }
    });
}

function refreshAllData() {
    loadDashboardData();
}

function refreshActionItems() {
    fetchActionItems();
}

function refreshActivities() {
    fetchRecentActivities();
}

function exportDashboard() {
    const period = getPeriodLabels().title;
    const rows = [
        ['Metric', 'Value'],
        ['Period', period],
        ['Total Students', document.getElementById('totalStudents')?.textContent || '0'],
        ['Course Registrations', document.getElementById('totalRegistrations')?.textContent || '0'],
        ['Clearance Requests', document.getElementById('pendingClearances')?.textContent || '0'],
        ['Total Courses', document.getElementById('totalCourses')?.textContent || '0'],
        ['Active Intakes', document.getElementById('activeIntakes')?.textContent || '0'],
        ['Attendance', document.getElementById('attendanceToday')?.textContent || '0'],
        ['System Users', document.getElementById('totalUsers')?.textContent || '0'],
        ['New Students', document.getElementById('newStudents')?.textContent || '0']
    ];
    const csv = rows.map(row => row.map(value => `"${String(value).replace(/"/g, '""')}"`).join(',')).join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = `program-admin-dashboard-${currentPeriod}.csv`;
    document.body.appendChild(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
}
</script>

