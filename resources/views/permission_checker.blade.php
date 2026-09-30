@extends('inc.app')

@section('title', 'NEBULA | Program Admin Level 1 - Permission Checker')

@section('content')
<style nonce="{{ $cspNonce }}">
.permission-card {
    transition: all 0.3s ease;
}
.permission-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}
.test-passed {
    background: #d4edda;
    border-left: 4px solid #28a745;
}
.test-failed {
    background: #f8d7da;
    border-left: 4px solid #dc3545;
}
.test-pending {
    background: #fff3cd;
    border-left: 4px solid #ffc107;
}
.route-badge {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
}
</style>

<div class="container-fluid">
    <div class="row justify-content-center mt-4">
        <div class="col-md-11">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <i class="ti ti-shield-check me-2"></i>
                            Program Administrator (Level 01) - Permission Checker
                        </h3>
                        <button class="btn btn-light btn-sm" onclick="runAllTests()">
                            <i class="ti ti-play me-1"></i>Run All Tests
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Summary Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card bg-primary text-white">
                                <div class="card-body text-center">
                                    <h5>Total Permissions</h5>
                                    <h2 id="totalPermissions">0</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-success text-white">
                                <div class="card-body text-center">
                                    <h5>Tests Passed</h5>
                                    <h2 id="testsPassed">0</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-danger text-white">
                                <div class="card-body text-center">
                                    <h5>Tests Failed</h5>
                                    <h2 id="testsFailed">0</h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card bg-warning text-white">
                                <div class="card-body text-center">
                                    <h5>Not Tested</h5>
                                    <h2 id="testsNotTested">0</h2>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="progress" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" id="progressBar" style="width: 0%">
                                <span id="progressText">0%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Current User Info -->
                    <div class="alert alert-info mb-4">
                        <strong>Current User:</strong> {{ Auth::user()->username }} 
                        <span class="badge bg-primary ms-2">{{ Auth::user()->role }}</span>
                        @if(Auth::user()->role !== 'Program Administrator (level 01)')
                            <span class="badge bg-warning ms-2">⚠️ Not testing with correct role!</span>
                        @endif
                    </div>

                    <!-- Permission Categories -->
                    <div class="accordion" id="permissionsAccordion">
                        <!-- HOME -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#home">
                                    <i class="ti ti-home me-2"></i> HOME
                                    <span class="badge bg-secondary ms-2" id="home-count">0</span>
                                </button>
                            </h2>
                            <div id="home" class="accordion-collapse collapse show" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body" id="home-content">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- USER MANAGEMENT -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#userManagement">
                                    <i class="ti ti-users me-2"></i> USER MANAGEMENT
                                    <span class="badge bg-secondary ms-2" id="userManagement-count">0</span>
                                </button>
                            </h2>
                            <div id="userManagement" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body" id="userManagement-content">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- STUDENT MANAGEMENT -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#studentManagement">
                                    <i class="ti ti-school me-2"></i> STUDENT MANAGEMENT
                                    <span class="badge bg-secondary ms-2" id="studentManagement-count">0</span>
                                </button>
                            </h2>
                            <div id="studentManagement" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body" id="studentManagement-content">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- REGISTRATIONS -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#registrations">
                                    <i class="ti ti-clipboard me-2"></i> REGISTRATIONS
                                    <span class="badge bg-secondary ms-2" id="registrations-count">0</span>
                                </button>
                            </h2>
                            <div id="registrations" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body" id="registrations-content">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- EXAMS & RESULTS -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#exams">
                                    <i class="ti ti-file-certificate me-2"></i> EXAMS & RESULTS
                                    <span class="badge bg-secondary ms-2" id="exams-count">0</span>
                                </button>
                            </h2>
                            <div id="exams" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body" id="exams-content">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- ATTENDANCE -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#attendance">
                                    <i class="ti ti-calendar-check me-2"></i> ATTENDANCE
                                    <span class="badge bg-secondary ms-2" id="attendance-count">0</span>
                                </button>
                            </h2>
                            <div id="attendance" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body" id="attendance-content">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- CLEARANCE -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#clearance">
                                    <i class="ti ti-check me-2"></i> CLEARANCE
                                    <span class="badge bg-secondary ms-2" id="clearance-count">0</span>
                                </button>
                            </h2>
                            <div id="clearance" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body" id="clearance-content">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- COURSES & MODULES -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#courses">
                                    <i class="ti ti-book me-2"></i> COURSES & MODULES
                                    <span class="badge bg-secondary ms-2" id="courses-count">0</span>
                                </button>
                            </h2>
                            <div id="courses" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body" id="courses-content">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- FINANCIAL -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#financial">
                                    <i class="ti ti-currency-rupee me-2"></i> FINANCIAL
                                    <span class="badge bg-secondary ms-2" id="financial-count">0</span>
                                </button>
                            </h2>
                            <div id="financial" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body" id="financial-content">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER -->
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#footer">
                                    <i class="ti ti-user me-2"></i> FOOTER
                                    <span class="badge bg-secondary ms-2" id="footer-count">0</span>
                                </button>
                            </h2>
                            <div id="footer" class="accordion-collapse collapse" data-bs-parent="#permissionsAccordion">
                                <div class="accordion-body" id="footer-content">
                                    <!-- Populated by JavaScript -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Results -->
                    <div class="mt-4 text-center">
                        <button class="btn btn-success" onclick="exportResults()">
                            <i class="ti ti-download me-1"></i>Export Test Results
                        </button>
                        <button class="btn btn-info ms-2" onclick="printReport()">
                            <i class="ti ti-printer me-1"></i>Print Report
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce }}">
// Program Administrator Level 1 Permissions (from RoleHelper.php)
const permissions = {
    home: [
        { name: 'dashboard', url: '/dashboard', description: 'Main Dashboard' }
    ],
    userManagement: [
        { name: 'create.user', url: '/create-user', description: 'Create New User' },
        { name: 'user.management', url: '/user-management', description: 'User Management' }
    ],
    studentManagement: [
        { name: 'student.registration', url: '/student-registration', description: 'Student Registration' },
        { name: 'missing.data.restore', url: '/missing-data/restore', description: 'Restore Missing Data' },
        { name: 'course.badge', url: '/badges', description: 'Course Badges' },
        { name: 'student.other.information', url: '/student-other-information', description: 'Student Other Information' },
        { name: 'student.list', url: '/student-list', description: 'Student List' },
        { name: 'student.profile', url: '/student-profile', description: 'Student Profile' },
        { name: 'student.view', url: '/student-view', description: 'Student View' }
    ],
    registrations: [
        { name: 'course.registration', url: '/course-registration', description: 'Course Registration' },
        { name: 'eligibility.registration', url: '/eligibility-registration', description: 'Eligibility & Registration' },
        { name: 'semester.registration', url: '/semester-registration', description: 'Semester Registration' },
        { name: 'module.management', url: '/module-management', description: 'Module Management' },
        { name: 'uh.index.page', url: '/uh-index', description: 'UH Index Management' },
        { name: 'course.change', url: '/course-change', description: 'Course Change' }
    ],
    exams: [
        { name: 'exam.results', url: '/exam-results', description: 'Exam Results' },
        { name: 'student.exam.result.management', url: '/student-exam-result-management', description: 'Student Exam Result Management' },
        { name: 'exam.results.view.edit', url: '/exam-results-view-edit', description: 'Exam Results View/Edit' },
        { name: 'repeat.students.management', url: '/repeat-students', description: 'Repeat Students Management' }
    ],
    attendance: [
        { name: 'attendance', url: '/attendance', description: 'Attendance Management' },
        { name: 'overall.attendance', url: '/overall-attendance', description: 'Overall Attendance' }
    ],
    clearance: [
        { name: 'all.clearance.management', url: '/clearance-management', description: 'All Clearance Management' }
    ],
    courses: [
        { name: 'module.creation', url: '/module-creation', description: 'Module Creation' },
        { name: 'course.management', url: '/course-management', description: 'Course Management' },
        { name: 'intake.create', url: '/intake-creation', description: 'Intake Creation' },
        { name: 'semesters.create', url: '/semester-creation', description: 'Semester Creation' },
        { name: 'timetable', url: '/timetable', description: 'Timetable Management' }
    ],
    financial: [
        { name: 'payment.dashboard', url: '/payment-dashboard', description: 'Payment Dashboard' }
    ],
    footer: [
        { name: 'user.profile', url: '/user-profile', description: 'User Profile' }
    ]
};

let testResults = {};
let totalTests = 0;
let completedTests = 0;

$(document).ready(function() {
    initializePermissionChecker();
});

function initializePermissionChecker() {
    // Count total permissions
    Object.keys(permissions).forEach(category => {
        totalTests += permissions[category].length;
    });
    $('#totalPermissions').text(totalTests);
    $('#testsNotTested').text(totalTests);

    // Render all permission cards
    renderPermissionCards();
}

function renderPermissionCards() {
    Object.keys(permissions).forEach(category => {
        const container = $(`#${category}-content`);
        const items = permissions[category];
        
        $(`#${category}-count`).text(items.length);
        
        items.forEach(permission => {
            const card = `
                <div class="card mb-2 permission-card test-pending" id="card-${permission.name}">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${permission.description}</h6>
                                <small class="text-muted">
                                    <span class="route-badge badge bg-secondary me-2">${permission.name}</span>
                                    <code>${permission.url}</code>
                                </small>
                            </div>
                            <div class="text-end">
                                <button class="btn btn-sm btn-primary" onclick="testPermission('${permission.name}', '${permission.url}', '${category}')">
                                    <i class="ti ti-test-pipe"></i> Test
                                </button>
                                <div class="mt-1" id="status-${permission.name}">
                                    <span class="badge bg-secondary">Not Tested</span>
                                </div>
                            </div>
                        </div>
                        <div class="mt-2" id="result-${permission.name}" style="display:none;"></div>
                    </div>
                </div>
            `;
            container.append(card);
        });
    });
}

function testPermission(permissionName, url, category) {
    const card = $(`#card-${permissionName}`);
    const statusDiv = $(`#status-${permissionName}`);
    const resultDiv = $(`#result-${permissionName}`);
    
    statusDiv.html('<span class="badge bg-info">Testing...</span>');
    
    $.ajax({
        url: url,
        method: 'GET',
        timeout: 5000,
        success: function(response, status, xhr) {
            const passed = xhr.status === 200;
            updateTestResult(permissionName, category, passed, xhr.status, 'Success');
        },
        error: function(xhr) {
            if (xhr.status === 404) {
                updateTestResult(permissionName, category, false, 404, 'Route not found');
            } else if (xhr.status === 403) {
                updateTestResult(permissionName, category, false, 403, 'Access denied (no permission)');
            } else if (xhr.status === 401) {
                updateTestResult(permissionName, category, false, 401, 'Not authenticated');
            } else if (xhr.status === 500) {
                updateTestResult(permissionName, category, false, 500, 'Server error');
            } else {
                updateTestResult(permissionName, category, false, xhr.status, xhr.statusText);
            }
        }
    });
}

function updateTestResult(permissionName, category, passed, statusCode, message) {
    const card = $(`#card-${permissionName}`);
    const statusDiv = $(`#status-${permissionName}`);
    const resultDiv = $(`#result-${permissionName}`);
    
    testResults[permissionName] = {
        category: category,
        passed: passed,
        statusCode: statusCode,
        message: message,
        timestamp: new Date().toISOString()
    };
    
    card.removeClass('test-pending test-passed test-failed');
    
    if (passed) {
        card.addClass('test-passed');
        statusDiv.html('<span class="badge bg-success"><i class="ti ti-check"></i> Passed</span>');
        resultDiv.html(`<div class="alert alert-success mb-0 p-2"><small>✓ Page loaded successfully (${statusCode})</small></div>`);
    } else {
        card.addClass('test-failed');
        statusDiv.html('<span class="badge bg-danger"><i class="ti ti-x"></i> Failed</span>');
        resultDiv.html(`<div class="alert alert-danger mb-0 p-2"><small>✗ ${statusCode}: ${message}</small></div>`);
    }
    
    resultDiv.show();
    completedTests++;
    updateStatistics();
}

function updateStatistics() {
    const passed = Object.values(testResults).filter(r => r.passed).length;
    const failed = Object.values(testResults).filter(r => !r.passed).length;
    const notTested = totalTests - completedTests;
    
    $('#testsPassed').text(passed);
    $('#testsFailed').text(failed);
    $('#testsNotTested').text(notTested);
    
    const percentage = Math.round((completedTests / totalTests) * 100);
    $('#progressBar').css('width', percentage + '%');
    $('#progressText').text(percentage + '%');
}

function runAllTests() {
    if (!confirm(`This will test all ${totalTests} permissions. This may take a few minutes. Continue?`)) {
        return;
    }
    
    completedTests = 0;
    testResults = {};
    updateStatistics();
    
    let delay = 0;
    Object.keys(permissions).forEach(category => {
        permissions[category].forEach(permission => {
            setTimeout(() => {
                testPermission(permission.name, permission.url, category);
            }, delay);
            delay += 500; // 500ms delay between tests
        });
    });
}

function exportResults() {
    const results = {
        timestamp: new Date().toISOString(),
        user: '{{ Auth::user()->username }}',
        role: '{{ Auth::user()->role }}',
        totalTests: totalTests,
        passed: Object.values(testResults).filter(r => r.passed).length,
        failed: Object.values(testResults).filter(r => !r.passed).length,
        details: testResults
    };
    
    const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(results, null, 2));
    const downloadAnchor = document.createElement('a');
    downloadAnchor.setAttribute("href", dataStr);
    downloadAnchor.setAttribute("download", "program-admin-level1-test-results.json");
    document.body.appendChild(downloadAnchor);
    downloadAnchor.click();
    downloadAnchor.remove();
    
    alert('Test results exported successfully!');
}

function printReport() {
    window.print();
}
</script>
@endsection
