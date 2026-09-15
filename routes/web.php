<?php

use Illuminate\Support\Facades\Route;
use App\Models\Intake;
use Illuminate\Http\Request;
use App\Http\Controllers\{
    AllClearanceController,
    AttendanceController,
    UserProfileController,
    CourseManagementController,
    CourseRegistraionController,
    DashboardController,
    DataExportImportController,
    EligibilityCheckingAndRegistrationController,
    FileManagementController,
    HostelClearanceController,
    IntakeCreationController,
    LoginController,
    LogoutController,
    LibraryClearanceController,
    ModuleManagementController,
    ReportingController,
    RepeatStudentsController,
    SpreadsheetController,
    StudentClearanceFormManagementController,
    ExamResultController,
    StudentListController,
    StudentOtherInformationController,
    StudentProfileController,
    StudentRegistraionController,
    TimetableController,
    ProjectClearanceController,
    ModuleCreationController,
    SemesterCreationController,
    SpecialApprovalController,
    UhIndexController,
    PaymentDiscountController,
    PaymentPlanController,
    PaymentController,
    LatePaymentController,
    SemesterRegistrationController,
    SpecializationRegistrationController,
    LateFeeApprovalController,
    MiscPaymentController,
    PaymentSummaryController,
    BadgeController,
    StudentViewController,
    DGMDashboardController,
    TeamPhaseController,
    CourseChangeController,
    MarketingManagerDashboardController,
    StudentCounselorDashboardController,
    StudentCounselorTraineeDashboardController,
    HostelManagerDashboardController,
    AdminL1DashboardController,
    ProgramAdminL2DashboardController,
    ProgramAdminL2TraineeDashboardController,
    ProjectTutorDashboardController,
    BursarDashboardController,
    LibrarianDashboardController,
    DeveloperDashboardController,
    PaymentClearanceController,
    RepeatStudentPaymentController,
    AcademicDetailsController,
    TerminationTrackingController
};

/*
|--------------------------------------------------------------------------
| Web Routes - Nebula Institute Management System
|--------------------------------------------------------------------------
|
| All routes organized by feature/module for easy maintenance.
| Each section contains all related routes grouped together.
|
*/

// ============================================================================
// DEFAULT REDIRECT
// ============================================================================
Route::redirect('/', 'login');

// ============================================================================
// AUTHENTICATION ROUTES (Public)
// ============================================================================
Route::get('/login', [LoginController::class, 'showLogin'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate'])->middleware('login.throttle')->name('login.authenticate');

// ============================================================================
// SPREADSHEET SECTION (Public - No Auth Required)
// ============================================================================
Route::get('/spreadsheet', [SpreadSheetController::class, 'showSpreadsheet'])->name('spreadsheet.section');
Route::post('/spreadsheet/store-attendance', [SpreadSheetController::class, 'storeAttendance'])->name('spreadsheet.store.attendance');

// ============================================================================
// BADGE VERIFICATION (Public)
// ============================================================================
Route::get('/verify-badge/{code}', [BadgeController::class, 'verify'])->name('badges.verify');

// ============================================================================
// AUTHENTICATED ROUTES
// ============================================================================
Route::middleware(['auth', 'prevent-back-history'])->group(function () {

    // ========================================================================
    // DASHBOARD
    // ========================================================================
    Route::get('/dashboard', [DashboardController::class, 'showDashboard'])->name('dashboard');

    // Dashboard API Routes
    Route::get('/yearly-revenue', [DashboardController::class, 'getYearlyRevenue']);
    Route::get('/monthly-earnings', [DashboardController::class, 'getMonthlyEarnings']);
    Route::get('/students-per-course', [DashboardController::class, 'getStudentsPerCourse']);
    Route::get('/marketing-survey-country-reg', [DashboardController::class, 'getCountrySurveyData']);
    Route::get('/dropdown-options', [DashboardController::class, 'getDropdownOptions']);
    Route::get('/registration-data', [DashboardController::class, 'getRegistrationData']);
    Route::get('/courses', [DashboardController::class, 'getCourses']);
    Route::get('/course-revenue/{courseId}', [DashboardController::class, 'getRevenueByCourse']);
    Route::get('/revenue-data', [DashboardController::class, 'getRevenueData']);

    // ========================================================================
    // LOGOUT
    // ========================================================================
    Route::get('/logout', [LogoutController::class, 'logout'])->name('logout');

    // ========================================================================
    // USER PROFILE & MANAGEMENT
    // ========================================================================
    // User Profile (All authenticated users)
    Route::get('/user', [UserProfileController::class, 'showUserProfile'])->name('user.profile');
    Route::post('/user/change-password', [UserProfileController::class, 'changePassword'])->name('user.changePassword');
    Route::post('/user/update-profile-picture', [UserProfileController::class, 'updateProfilePicture'])->name('user.updateProfilePicture');

    // User Management - Create/Update/Delete Users
    Route::middleware(['role:DGM,Program Administrator (level 01),Developer'])->group(function () {
        Route::get('/user/create', [UserProfileController::class, 'showCreateUserForm'])->name('create.user');
        Route::post('/user/create', [UserProfileController::class, 'createUser'])->name('user.create');
        Route::post('/user/update-status', [UserProfileController::class, 'updateUserStatus'])->name('user.updateStatus');
        Route::post('/user/delete', [UserProfileController::class, 'deleteUser'])->name('user.delete');
        Route::post('/user/get-details', [UserProfileController::class, 'getUserDetails'])->name('user.getDetails');
        Route::post('/user/reset-password', [UserProfileController::class, 'resetPassword'])->name('user.resetPassword');
    });

    // User Management Page
    Route::middleware(['role:Program Administrator (level 01),Developer'])->group(function () {
        Route::get('/dgm-user-management', [UserProfileController::class, 'showDGMUserManagement'])->name('dgm.user.management');
    });

    // ========================================================================
    // STUDENT MANAGEMENT
    // ========================================================================

    // Student Registration
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Student Counselor,Bursar,Marketing Manager,Developer'])->group(function () {
        Route::get('/student/register', [StudentRegistraionController::class, 'showStudentRegistration'])->name('student_management.registration');
        Route::post('/student/register', [StudentRegistraionController::class, 'register'])->name('student_management.register');
        Route::get('/student/subjects/{examTypeId}', [StudentRegistraionController::class, 'getSubjectsByExamType']);
        Route::get('/student/streams/{examTypeId}', [StudentRegistraionController::class, 'getStreamsByExamType']);
    });

    // Student Other Information
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Student Counselor,Bursar,Marketing Manager,Developer'])->group(function () {
        Route::get('/student/other-information', [StudentOtherInformationController::class, 'showStudentOtherInformation'])->name('student_management.other.information');
        Route::post('/student/other-information/save', [StudentOtherInformationController::class, 'storeOtherInformations'])->name('student_management.store.other.informations');
        Route::post('/student/other-information/get', [StudentOtherInformationController::class, 'getStudentDetails'])->name('student_management.retrieve.details');
        Route::post('/student/other-information/reinstate', [StudentOtherInformationController::class, 'reinstateStudent'])->name('student.other.reinstate');
    });

    // Student List
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Student Counselor,Bursar,Marketing Manager,Developer'])->group(function () {
        Route::get('/student/list', [StudentListController::class, 'showStudentList'])->name('student_management.list');
        Route::get('/student/list/get-intakes/{courseId}/{location}', [StudentListController::class, 'getIntakesForCourseAndLocation'])->name('student.list.getIntakes');
        Route::post('/get-student-list-data', [StudentListController::class, 'getStudentListData'])->name('student.getListData');
        Route::post('/download-student-list', [StudentListController::class, 'downloadStudentList'])->name('student.downloadList');
        Route::post('/download-student-list-excel', [StudentListController::class, 'downloadStudentListExcel'])->name('student.downloadList.excel');
        Route::get('/student/export', [StudentListController::class, 'exportStudentList'])->name('student.export');
        Route::post('/student/filter', [StudentListController::class, 'filterStudents'])->name('student.filter');
        Route::get('/download-student-list-template', [StudentListController::class, 'downloadTemplate'])->name('download.student.list.template');
        Route::post('/import-student-list', [StudentListController::class, 'importStudentList'])->name('import.student.list');
        Route::get('/student-list-excel', [StudentListController::class, 'downloadExcel'])->name('student.list.excel');
        Route::get('/student/blacklist-check', [StudentListController::class, 'checkBlacklistStatus'])->name('student.blacklist.check');
    });

    // Student View (All Students)
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Student Counselor,Bursar,Marketing Manager,Developer'])->group(function () {
        Route::get('/students/view', [StudentViewController::class, 'index'])->name('student_management.view');
        Route::post('/students/filter', [StudentViewController::class, 'filter'])->name('student_management.filter');
        Route::post('/students/view/export-excel', [StudentViewController::class, 'exportExcel'])->name('student_management.view.export.excel');
        Route::post('/students/view/export-pdf', [StudentViewController::class, 'exportPdf'])->name('student_management.view.export.pdf');
        Route::get('/students/courses', [StudentViewController::class, 'getStudentCourses'])->name('student_management.courses');
        Route::get('/students/intakes', [StudentViewController::class, 'getCourseIntakes'])->name('student_management.intakes');
    });

    // Student Profile
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Student Counselor,Bursar,Marketing Manager,Developer'])->group(function () {
        Route::get('/student/profile/{studentId}', [StudentProfileController::class, 'showStudentProfile'])->name('student_management.profile');
        Route::post('/student/profile/update-personal', [StudentProfileController::class, 'updatePersonalInfoAjax'])->name('student_management.update.personal.info');
        Route::post('/student/profile/update-parent', [StudentProfileController::class, 'updateParentInfoAjax'])->name('student_management.update.parent.info');
        Route::post('/student/profile/update-photo/{studentId}', [StudentProfileController::class, 'updateStudentProfilePicture'])->name('student.updateProfilePicture');
        Route::post('/student/profile/{studentId}/update-ol-results', [StudentProfileController::class, 'updateOLResults'])->name('student.updateOLResults');
        Route::post('/student/profile/{studentId}/update-al-results', [StudentProfileController::class, 'updateALResults'])->name('student.updateALResults');
        Route::post('/student/terminate', [StudentProfileController::class, 'terminate'])->name('student_management.terminate');
        Route::post('/student/reinstate', [StudentProfileController::class, 'reinstate'])->name('student_management.reinstate');
        Route::get('/api/student/{studentId}/history', [StudentProfileController::class, 'getCourseRegistrationHistory']);
        Route::get('/api/student/{studentId}/course-registration-history', [StudentProfileController::class, 'getCourseRegistrationHistory']);
        Route::get('/api/student/{studentId}/administration-history', [StudentProfileController::class, 'getStudentStatusHistory']);
        Route::get('/api/student-details-by-nic', [StudentProfileController::class, 'getStudentDetailsByNic']);
        Route::get('/api/student/{studentId}/courses', [StudentProfileController::class, 'getRegisteredCourses']);
        Route::get('/api/student/{studentId}/course/{courseId}/semesters', [StudentProfileController::class, 'getSemesters']);
        Route::get('/api/student/{studentId}/course/{courseId}/attendance-semesters', [StudentProfileController::class, 'getAttendanceSemesters']);
        Route::get('/api/student/{studentId}/course/{courseId}/semester/{semester}/results', [StudentProfileController::class, 'getModuleResults']);
        Route::get('/api/student/{studentId}/course/{courseId}/payment-summary', [StudentProfileController::class, 'getPaymentSummary']);
        Route::get('/api/student/{studentId}/course/{courseId}/semester/{semester}/attendance', [StudentProfileController::class, 'getAttendance']);
        Route::get('/api/student/{studentId}/course/{courseId}/attendance', [StudentProfileController::class, 'getAttendanceForCertificate']);
        Route::get('/api/student/{studentId}/clearances', [StudentProfileController::class, 'getStudentClearances']);
        Route::get('/api/student/{studentId}/status-history', [StudentProfileController::class, 'getStudentStatusHistory']);
        Route::get('/student/{studentId}/certificates', [StudentProfileController::class, 'getStudentCertificates']);
        Route::get('/api/student/{studentId}/certificates', [StudentProfileController::class, 'getStudentCertificates']);
        Route::post('/student/{studentId}/upload-ol-certificate', [StudentProfileController::class, 'uploadOLCertificate'])->name('student.uploadOLCertificate');
        Route::post('/student/{studentId}/upload-al-certificate', [StudentProfileController::class, 'uploadALCertificate'])->name('student.uploadALCertificate');
        Route::get('/api/course/{courseId}/specializations', [StudentProfileController::class, 'getCourseSpecializations']);
        Route::post('/api/course-registration/{id}/update-grade', [StudentProfileController::class, 'updateCourseRegistrationGrade']);
        Route::get('/api/student/{studentId}/course/{courseId}/intakes', [StudentProfileController::class, 'getIntakesForCourse'])->name('student.intakes.for.course');
        Route::get('/api/student/{studentId}/course/{courseId}/intake/{intake}/payment-details', [StudentProfileController::class, 'getPaymentDetails'])->name('student.payment.details');
        Route::get('/api/student/{studentId}/course/{courseId}/intake/{intake}/payment-history', [StudentProfileController::class, 'getPaymentHistory'])->name('student.payment.history');
        Route::get('/api/student/{studentId}/course/{courseId}/intake/{intake}/payment-schedule', [StudentProfileController::class, 'getPaymentSchedule'])->name('student.payment.schedule');
    });

    Route::middleware(['auth', 'role:DGM,Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::get('/termination-tracking', [TerminationTrackingController::class, 'index'])->name('termination.tracking');
    });

    // ========================================================================
    // COURSE MANAGEMENT
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::get('/course-management', [CourseManagementController::class, 'showCourseManagement'])->name('course.management');
        Route::post('/store-course-data', [CourseManagementController::class, 'storeCourseData'])->name('course.store');
        Route::get('/api/courses/{courseId}', [CourseManagementController::class, 'getCourseById']);
    });

    // ========================================================================
    // COURSE REGISTRATION
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Student Counselor,Bursar,Marketing Manager,Developer'])->group(function () {
        Route::get('/course-registration', [CourseRegistraionController::class, 'showCourseRegistration'])->name('course.registration');
        Route::get('/course-registration/get-courses-by-location/{location}', [CourseRegistraionController::class, 'getCoursesByLocation'])->name('course.registration.courses.by.location');
        Route::get('/course-registration/get-intakes/{courseName}/{location}', [CourseRegistraionController::class, 'getIntakesForCourseAndLocation'])->name('course.registration.intakes.by.course.location');
        Route::post('/check-student-exists', [CourseRegistraionController::class, 'checkStudentExists'])->name('check.student.exists');
        Route::post('/store-course-registration', [CourseRegistraionController::class, 'storeCourseRegistration'])->name('store.course.registration');
        Route::post('/check-students', [CourseRegistraionController::class, 'checkStudents'])->name('check.students');
        Route::post('/batch-dropdown-options', [CourseRegistraionController::class, 'batchDropdownOptions'])->name('batch.dropdown.options');
        Route::get('/check-blacklist-status', [CourseRegistraionController::class, 'checkBlacklistStatus']);
        Route::post('/intakes/get', [CourseRegistraionController::class, 'getIntakes'])->name('intakes.get');
        Route::post('/students/find', [CourseRegistraionController::class, 'findStudent'])->name('students.find');
        Route::get('/api/course-registration/student-by-nic/{nic}', [CourseRegistraionController::class, 'getStudentByNic'])->name('course.registration.student.by.nic');
        Route::post('/api/course-registration', [CourseRegistraionController::class, 'storeCourseRegistrationAPI'])->name('register.course.api');
        Route::post('/api/course-registration-eligibility', [CourseRegistraionController::class, 'storeCourseRegistrationForEligibilityAPI'])->name('register.course.eligibility.api');
    });

        // ========================================================================
    // COURSE CHANGE MANAGEMENT
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Developer,Marketing Manager,Student Counselor'])->group(function () {
        Route::prefix('registration/course-change')->group(function () {
            // Course Change Interface
            Route::get('/', [CourseChangeController::class, 'index'])
                 ->name('course.change.index');

            // Student Search
            Route::post('/find-student', [CourseChangeController::class, 'findStudent'])
                 ->name('course.change.find.student');

            // Course & Intake Selection
            Route::get('/courses', [CourseChangeController::class, 'getCourses'])
                 ->name('course.change.courses');

            Route::post('/new-intakes', [CourseChangeController::class, 'getNewIntakes'])
                 ->name('course.change.new.intakes');

            // ID Generation
            Route::post('/generate-id', [CourseChangeController::class, 'generateNewCourseRegId'])
                 ->name('course.change.generateId');

            // Payment Check
            Route::post('/check-payment', [CourseChangeController::class, 'checkPaymentStatus'])
                 ->name('course.change.check.payment');

            // Submit Change
            Route::post('/submit', [CourseChangeController::class, 'submitChange'])
                 ->name('course.change.submit');

            // Payment Summary
            Route::get('/payment-summary/{studentId}/{courseId}', [CourseChangeController::class, 'getPaymentSummary'])
                 ->name('course.change.payment.summary');

            // Course Change Logs
            Route::get('/change-logs/{studentId}', [CourseChangeController::class, 'getChangeLogs'])
                 ->name('course.change.logs');

            // Cancelled Payments Remarks
            Route::get('/cancelled-payments/{studentId}', [CourseChangeController::class, 'getCancelledPayments'])
                 ->name('course.change.cancelled.payments');

            // Update Cancelled Payment Status
            Route::post('/cancelled-payments/{paymentDetailId}/status', [CourseChangeController::class, 'updateCancelledPaymentStatus'])
                 ->name('course.change.cancelled.payments.status');

            // Course Change History (Logs + Payments)
            Route::get('/change-history/{studentId}', [CourseChangeController::class, 'getCourseChangeHistory'])
                 ->name('course.change.history');
        });
    });
    // ========================================================================
    // ELIGIBILITY CHECKING & REGISTRATION
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Student Counselor,Bursar,Marketing Manager,Developer'])->group(function () {
        Route::get('/eligibility-registration', [EligibilityCheckingAndRegistrationController::class, 'showEligibilityRegistration'])->name('eligibility.registration');
        Route::get('/get-courses-by-location', [EligibilityCheckingAndRegistrationController::class, 'getCoursesByLocation']);
        Route::get('/get-intakes/{courseId}/{location}', [EligibilityCheckingAndRegistrationController::class, 'getIntakesForCourseAndLocation']);
        Route::post('/get-eligible-students', [EligibilityCheckingAndRegistrationController::class, 'getEligibleStudents']);
        Route::post('/get-student-data', [EligibilityCheckingAndRegistrationController::class, 'getStudentData'])->name('student.data');
        Route::post('/verify-eligibility', [EligibilityCheckingAndRegistrationController::class, 'verifyEligibility'])->name('verify.eligibility');
        Route::post('/check-approval', [EligibilityCheckingAndRegistrationController::class, 'checkApproval'])->name('check.approval');
        Route::get('/get-registered-courses-by-nic', [EligibilityCheckingAndRegistrationController::class, 'getRegisteredCoursesByNic']);
        Route::post('/get-eligible-students-by-nic', [EligibilityCheckingAndRegistrationController::class, 'getEligibleStudentsByNic']);
        Route::post('/get-student-exam-details-by-nic-course', [EligibilityCheckingAndRegistrationController::class, 'getStudentExamDetailsByNicCourse']);
        Route::get('/get-course-entry-qualification', [EligibilityCheckingAndRegistrationController::class, 'getCourseEntryQualification']);
        Route::get('/get-next-course-registration-id', [EligibilityCheckingAndRegistrationController::class, 'getNextCourseRegistrationId'])->name('get.next.course.registration.id');
    });

    // ========================================================================
    // INTAKE CREATION
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::get('/intake-creation', [IntakeCreationController::class, 'create'])->name('intake.create');
        Route::post('/intake-creation', [IntakeCreationController::class, 'store'])->name('intake.store');
        Route::get('/intake-creation/{id}/edit', [IntakeCreationController::class, 'edit'])->name('intake.edit');
        Route::put('/intake-creation/{id}', [IntakeCreationController::class, 'update'])->name('intake.update');
        Route::post('/get-payment-plan-details', [IntakeCreationController::class, 'getPaymentPlanDetails'])->name('get.payment.plan.details');
    });

    // Intake API Routes (Public within auth)
    Route::get('/intakes-by-course/{courseId}', function ($courseId) {
        return Intake::where('course_id', $courseId)->select('intake_id', 'batch', 'location')->orderBy('batch')->get();
    });
    Route::get('/api/intakes-by-course/{courseId}', function ($courseId) {
        return Intake::where('course_id', $courseId)->select('intake_id', 'batch', 'location')->orderBy('batch')->get();
    });

    // ========================================================================
    // MODULE MANAGEMENT & CREATION
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        // Module Management
        Route::get('/module-management', [ModuleManagementController::class, 'showModuleManagement'])->name('module.management');
        Route::post('/module-management/get-intakes', [ModuleManagementController::class, 'getIntakes'])->name('module.management.getIntakes');
        Route::post('/module-management/get-students', [ModuleManagementController::class, 'getStudents'])->name('module.management.getStudents');
        Route::post('/module-management/get-modules', [ModuleManagementController::class, 'getModules'])->name('module.management.getModules');
        Route::post('/module-management/get-assignments', [ModuleManagementController::class, 'getModuleAssignments'])->name('module.management.getAssignments');
        Route::post('/module-management/assign-modules', [ModuleManagementController::class, 'assignModules'])->name('module.management.assignModules');
        Route::post('/module-management/remove-assignment', [ModuleManagementController::class, 'removeAssignment'])->name('module.management.removeAssignment');
        Route::post('/module-management/get-statistics', [ModuleManagementController::class, 'getModuleStatistics'])->name('module.management.getStatistics');
        Route::post('/module-management/get-ongoing-semesters', [ModuleManagementController::class, 'getOngoingSemesters'])->name('module.management.getOngoingSemesters');
        Route::post('/module-management/get-elective-modules', [ModuleManagementController::class, 'getElectiveModules'])->name('module.management.getElectiveModules');
        Route::post('/module-management/get-elective-students', [ModuleManagementController::class, 'getElectiveStudents'])->name('module.management.getElectiveStudents');
        Route::post('/module-management/register-elective-modules', [ModuleManagementController::class, 'registerElectiveModules'])->name('module.management.registerElectiveModules');
        Route::post('/module-management/get-elective-registrations', [ModuleManagementController::class, 'getElectiveRegistrations'])->name('module.management.getElectiveRegistrations');

        // Module Creation
        Route::get('/module-creation', [ModuleCreationController::class, 'create'])->name('module.creation');
        Route::post('/module-store', [ModuleCreationController::class, 'store'])->name('module.store');
        Route::patch('/modules/{id}', [ModuleCreationController::class, 'update']);
        Route::delete('/modules/{id}', [ModuleCreationController::class, 'destroy'])->name('module.destroy');
    });

    // ========================================================================
    // SEMESTER MANAGEMENT
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::get('semesters/create', [SemesterCreationController::class, 'create'])->name('semesters.create');
        Route::post('semesters', [SemesterCreationController::class, 'store'])->name('semesters.store');
        Route::get('semesters', [SemesterCreationController::class, 'index'])->name('semesters.index');
        Route::get('semesters/{semester}/edit', [SemesterCreationController::class, 'edit'])->name('semesters.edit');
        Route::put('semesters/{semester}', [SemesterCreationController::class, 'update'])->name('semesters.update');
        Route::delete('semesters/{semester}', [SemesterCreationController::class, 'destroy'])->name('semesters.destroy');
        Route::post('semesters/bulk-update-status', [SemesterCreationController::class, 'bulkUpdateStatus'])->name('semesters.bulkUpdateStatus');
        Route::post('semesters/bulk-delete', [SemesterCreationController::class, 'bulkDelete'])->name('semesters.bulkDelete');
        Route::post('semesters/{semester}/duplicate', [SemesterCreationController::class, 'duplicateSemester'])->name('semesters.duplicate');
        Route::post('/semester/get-filtered-modules', [SemesterCreationController::class, 'getFilteredModules'])->name('semester.get.filtered.modules');
        Route::get('/courses/by-location', [SemesterCreationController::class, 'getCoursesByLocation'])->name('courses.byLocation');
        Route::get('/semester/get-intakes/{courseId}/{location}', [SemesterCreationController::class, 'getIntakesForCourseAndLocation'])->name('semester.get.intakes.by.course.location');
    });

    // ========================================================================
    // SEMESTER REGISTRATION
    // ========================================================================
    Route::middleware(['role:Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::get('/specialization-registration', [SpecializationRegistrationController::class, 'index'])->name('specialization.registration');
        Route::post('/specialization-registration/courses', [SpecializationRegistrationController::class, 'courses']);
        Route::post('/specialization-registration/intakes', [SpecializationRegistrationController::class, 'intakes']);
        Route::post('/specialization-registration/students', [SpecializationRegistrationController::class, 'students']);
        Route::post('/specialization-registration/store', [SpecializationRegistrationController::class, 'store']);
        Route::get('/semester-registration', [SemesterRegistrationController::class, 'index'])->name('semester.registration');
        Route::post('/semester-registration/store', [SemesterRegistrationController::class, 'store'])->name('semester.registration.store');
        Route::get('/semester-registration/get-courses-by-location', [SemesterRegistrationController::class, 'getCoursesByLocation'])->name('semester.registration.getCoursesByLocation');
        Route::get('/semester-registration/get-ongoing-intakes', [SemesterRegistrationController::class, 'getOngoingIntakes'])->name('semester.registration.getOngoingIntakes');
        Route::get('/semester-registration/get-open-semesters', [SemesterRegistrationController::class, 'getOpenSemesters'])->name('semester.registration.getOpenSemesters');
        Route::get('/semester-registration/get-eligible-students', [SemesterRegistrationController::class, 'getEligibleStudents'])->name('semester.registration.getEligibleStudents');
        Route::get('/semester-registration/get-all-semesters-for-course', [SemesterRegistrationController::class, 'getAllSemestersForCourse'])->name('semester.registration.getAllSemestersForCourse');
        Route::post('/semester-registration/update-status', [SemesterRegistrationController::class, 'updateStatus'])->name('semester.registration.updateStatus');
        Route::post('/semester-registration/check-clearances', [SemesterRegistrationController::class, 'checkStudentClearances'])->name('semester.registration.checkClearances');
        Route::post('/semester-registration/approve-reenroll', [SemesterRegistrationController::class, 'approveReRegister'])->name('semester.registration.approveReenroll');
        Route::post('/semester-registration/reject-reenroll', [SemesterRegistrationController::class, 'rejectReRegister'])->name('semester.registration.rejectReenroll');
        Route::get('/semester-registration/terminated-requests', [SemesterRegistrationController::class, 'terminatedRequests']);
        Route::post('/semester-registration/approve-reregister', [SemesterRegistrationController::class, 'approveReRegister'])->name('semester.registration.approveReRegister');
        Route::post('/semester-registration/reject-reregister', [SemesterRegistrationController::class, 'rejectReRegister'])->name('semester.registration.rejectReRegister');
    });

    // ========================================================================
    // ATTENDANCE MANAGEMENT
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Bursar,Project Tutor,Marketing Manager,Developer'])->group(function () {
        Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
        Route::get('/attendance/get-courses-by-location', [AttendanceController::class, 'getCoursesByLocation'])->name('attendance.courses.by.location');
        Route::get('/attendance/get-intakes/{courseId}/{location}', [AttendanceController::class, 'getIntakesForCourseAndLocation'])->name('attendance.get.intakes.for.course.location');
        Route::get('/attendance/get-semesters', [AttendanceController::class, 'getSemesters'])->name('attendance.get.semesters');
        Route::post('/get-filtered-modules', [AttendanceController::class, 'getFilteredModules'])->name('get.filtered.modules');
        Route::post('/get-students-for-attendance', [AttendanceController::class, 'getStudentsForAttendance'])->name('get.students.for.attendance');
        Route::post('/store-attendance', [AttendanceController::class, 'storeAttendance'])->name('store.attendance');
        Route::get('/attendance/download-template', [AttendanceController::class, 'downloadTemplate'])->name('attendance.download.template');
        Route::post('/attendance/import', [AttendanceController::class, 'importAttendance'])->name('attendance.import');
        Route::post('/get-attendance-history', [AttendanceController::class, 'getAttendanceHistory'])->name('get.attendance.history');
        Route::get('/debug-attendance-data', [AttendanceController::class, 'debugData'])->name('debug.attendance.data');
        Route::redirect('/student-attendance-management', '/attendance');
    });

    // Overall Attendance
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Bursar,Marketing Manager,Developer'])->group(function () {
        Route::get('/overall-attendance', function () {
            $courses = App\Models\Course::all();
            $intakes = App\Models\Intake::all();
            return view('attendance.overall_attendance', compact('courses', 'intakes'));
        })->name('overall.attendance');
        Route::post('/get-overall-attendance', [AttendanceController::class, 'getOverallAttendance'])->name('get.overall.attendance');
        Route::post('/download-attendance-excel', [AttendanceController::class, 'downloadAttendanceExcel'])->name('download.attendance.excel');
    });

    // ========================================================================
    // FILE MANAGEMENT
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::post('/file/upload', [FileManagementController::class, 'uploadFile'])->name('file.upload');
        Route::post('/file/upload-multiple', [FileManagementController::class, 'uploadMultipleFiles'])->name('file.uploadMultiple');
        Route::get('/file/download', [FileManagementController::class, 'downloadFile'])->name('file.download');
        Route::delete('/file/delete', [FileManagementController::class, 'deleteFile'])->name('file.delete');
        Route::delete('/file/delete-multiple', [FileManagementController::class, 'deleteMultipleFiles'])->name('file.deleteMultiple');
        Route::get('/file/info', [FileManagementController::class, 'getFileInfo'])->name('file.info');
        Route::get('/file/list', [FileManagementController::class, 'listFiles'])->name('file.list');
        Route::get('/file/storage-stats', [FileManagementController::class, 'getStorageStats'])->name('file.storageStats');
        Route::post('/file/cleanup', [FileManagementController::class, 'cleanupOrphanedFiles'])->name('file.cleanup');
    });

    // ========================================================================
    // REPORTING SYSTEM
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::get('/reporting', [ReportingController::class, 'showReportingDashboard'])->name('reporting.dashboard');
        Route::post('/reporting/enrollment', [ReportingController::class, 'generateStudentEnrollmentReport'])->name('reporting.enrollment');
        Route::post('/reporting/performance', [ReportingController::class, 'generateCoursePerformanceReport'])->name('reporting.performance');
        Route::post('/reporting/attendance', [ReportingController::class, 'generateAttendanceReport'])->name('reporting.attendance');
        Route::post('/reporting/financial', [ReportingController::class, 'generateFinancialReport'])->name('reporting.financial');
        Route::post('/reporting/module', [ReportingController::class, 'generateModuleAssignmentReport'])->name('reporting.module');
        Route::post('/reporting/export', [ReportingController::class, 'exportReport'])->name('reporting.export');
    });

    // ========================================================================
    // DATA EXPORT/IMPORT
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::get('/data-export-import', [DataExportImportController::class, 'showDashboard'])->name('data.export.import');
        Route::post('/data-export/students', [DataExportImportController::class, 'exportStudents'])->name('data.export.students');
        Route::post('/data-export/courses', [DataExportImportController::class, 'exportCourses'])->name('data.export.courses');
        Route::post('/data-export/attendance', [DataExportImportController::class, 'exportAttendance'])->name('data.export.attendance');
        Route::post('/data-export/exam-results', [DataExportImportController::class, 'exportExamResults'])->name('data.export.examResults');
        Route::post('/data-import/students', [DataExportImportController::class, 'importStudents'])->name('data.import.students');
        Route::post('/data-import/exam-results', [DataExportImportController::class, 'importExamResults'])->name('data.import.examResults');
        Route::get('/data-import/template', [DataExportImportController::class, 'getImportTemplate'])->name('data.import.template');
        Route::get('/data-export/stats', [DataExportImportController::class, 'getExportStats'])->name('data.export.stats');
    });

    // ========================================================================
    // CLEARANCE MANAGEMENT
    // ========================================================================

    // Library Clearance
    Route::middleware(['role:Librarian,DGM,Program Administrator (level 01),Developer'])->group(function () {
        Route::get('/student-clearance-form-management', [StudentClearanceFormManagementController::class, 'showStudentClearanceFormManagement'])->name('student.clearance.form.management');
        Route::post('/student-clearance-form-management', [StudentClearanceFormManagementController::class, 'store'])->name('library.store');
        Route::get('/student-clearance/search', [StudentClearanceFormManagementController::class, 'search'])->name('student.clearance.search');
        Route::get('/student-clearance/get-student-details', [StudentClearanceFormManagementController::class, 'getStudentDetails'])->name('student.clearance.getStudentDetails');
        Route::get('/library/get-student-details', [StudentClearanceFormManagementController::class, 'getStudentDetails'])->name('library.getStudentDetails');
        Route::post('/library/update-received-date', [StudentClearanceFormManagementController::class, 'updateReceivedDate'])->name('library.updateReceivedDate');
        Route::get('/library-clearance', [LibraryClearanceController::class, 'index'])->name('library.clearance');
        Route::get('/library-clearance/{id}/details', [LibraryClearanceController::class, 'details'])->name('library.clearance.details');
        Route::post('/library/approve-clearance', [LibraryClearanceController::class, 'approveClearance'])->name('library.approve.clearance');
        Route::post('/library/reject-clearance', [LibraryClearanceController::class, 'rejectClearance'])->name('library.reject.clearance');
    });

    // Hostel Clearance
    Route::middleware(['role:Hostel Manager,DGM,Program Administrator (level 01),Developer'])->group(function () {
        Route::get('/hostel-clearance', [HostelClearanceController::class, 'showHostelClearanceFormManagement'])->name('hostel.clearance.form.management');
        Route::post('/hostel-clearance', [HostelClearanceController::class, 'store'])->name('hostel.store');
        Route::post('/hostel/update-clearance', [HostelClearanceController::class, 'updateClearance'])->name('hostel.update');
        Route::get('/search/hostel-clearance', [HostelClearanceController::class, 'search'])->name('hostel.clearance.search');
        Route::get('/hostel/get-student-details', [HostelClearanceController::class, 'getStudentDetails'])->name('hostel.clearance.getStudentDetails');
        Route::post('/hostel/approve-clearance', [HostelClearanceController::class, 'approveClearance'])->name('hostel.approve.clearance');
        Route::post('/hostel/reject-clearance', [HostelClearanceController::class, 'rejectClearance'])->name('hostel.reject.clearance');
    });

    // Project Clearance
    Route::middleware(['role:Project Tutor,DGM,Program Administrator (level 01),Developer'])->group(function () {
        Route::get('/project-clearance-form-management', [ProjectClearanceController::class, 'showProjectClearanceFormManagement'])->name('project.clearance.management');
        Route::post('/project-clearance-form-management', [ProjectClearanceController::class, 'store'])->name('project.store');
        Route::post('/project/update-clearance', [ProjectClearanceController::class, 'updateClearance'])->name('project.update');
        Route::get('/search/project-clearance', [ProjectClearanceController::class, 'search'])->name('project.clearance.search');
        Route::get('/project/get-student-details', [ProjectClearanceController::class, 'getStudentDetails'])->name('project.clearance.getStudentDetails');
        Route::post('/project/approve-clearance', [ProjectClearanceController::class, 'approveClearance'])->name('project.approve.clearance');
        Route::post('/project/reject-clearance', [ProjectClearanceController::class, 'rejectClearance'])->name('project.reject.clearance');
    });

    // Payment Clearance
    Route::middleware(['role:Bursar,Developer,Program Administrator (level 01),Program Administrator (level 02)'])->group(function () {
        Route::get('/payment-clearance', [PaymentClearanceController::class, 'index'])->name('payment.clearance');
        Route::post('/payment/approve-clearance', [PaymentClearanceController::class, 'approveClearance'])->name('payment.approve.clearance');
        Route::post('/payment/reject-clearance', [PaymentClearanceController::class, 'rejectClearance'])->name('payment.reject.clearance');
    });

    // All Clearance Management
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::get('/clearance/search', [AllClearanceController::class, 'showAllClearance'])->name('clearance.search');
        Route::get('/all-clearance', [AllClearanceController::class, 'showAllClearance'])->name('all.clearance.management');
        Route::get('/library-clearance/search', [AllClearanceController::class, 'librarysearch'])->name('clearance.library.search');
        Route::get('/hostel-clearance/search', [AllClearanceController::class, 'hostelsearch'])->name('clearance.hostel.search');
        Route::get('/project-clearance/search', [AllClearanceController::class, 'projectsearch'])->name('clearance.project.search');
        Route::get('/all-clearance/library-search', [AllClearanceController::class, 'librarysearch'])->name('all.clearance.library.search');
        Route::get('/all-clearance/hostel-search', [AllClearanceController::class, 'hostelsearch'])->name('all.clearance.hostel.search');
        Route::get('/all-clearance/project-search', [AllClearanceController::class, 'projectsearch'])->name('all.clearance.project.search');
        Route::post('/clearance/send-request', [AllClearanceController::class, 'sendClearanceRequest'])->name('clearance.sendRequest');
        Route::post('/clearance/get-students-for-intake', [AllClearanceController::class, 'getStudentsForIntake'])->name('clearance.getStudentsForIntake');
        Route::post('/clearance/get-intake-details', [AllClearanceController::class, 'getIntakeDetails'])->name('clearance.getIntakeDetails');
    });

    // ========================================================================
    // EXAM RESULTS MANAGEMENT
    // ========================================================================
    Route::middleware(['role:DGM,Program Administrator (level 01),Program Administrator (level 02),Bursar,Marketing Manager,Developer'])->group(function () {
        Route::get('/student-exam-result-management', [ExamResultController::class, 'showStudentExamResultManagement'])->name('student.exam.result.management');
        Route::get('/exam-results-view-edit', [ExamResultController::class, 'showExamResultsViewEdit'])->name('exam.results.view.edit');
        Route::get('/exam-results/get-courses-by-location', [ExamResultController::class, 'getCoursesByLocation'])->name('exam.results.courses.by.location');
        Route::get('/get-course-data/{courseID}', [ExamResultController::class, 'getCourseData']);
        Route::post('/store/result', [ExamResultController::class, 'storeResult'])->name('store.result');
        Route::post('/update/result', [ExamResultController::class, 'updateResult'])->name('update.result');
        Route::post('/get-student-name', [ExamResultController::class, 'getStudentName'])->name('get.student.name');
        Route::get('/exam-results/get-intakes/{courseID}/{location}', [ExamResultController::class, 'getIntakesForCourseAndLocation'])->name('get.intakes.for.course.location');
        Route::post('/exam-results/get-modules', [ExamResultController::class, 'getFilteredModules'])->name('exam.results.get.filtered.modules');
        Route::get('/exam-results/get-semesters', [ExamResultController::class, 'getSemesters'])->name('get.semesters');
        Route::post('/get-students-for-exam-result', [ExamResultController::class, 'getStudentsForExamResult'])->name('get.students.for.exam.result');
        Route::post('/get-existing-exam-results', [ExamResultController::class, 'getExistingExamResults'])->name('get.existing.exam.results');
        Route::post('/auto-calculate-grades', [ExamResultController::class, 'autoCalculateGrades'])->name('auto.calculate.grades');
        Route::post('/download-exam-results-template', [ExamResultController::class, 'downloadTemplate'])->name('download.exam.results.template');
    });

    // ========================================================================
    // REPEAT STUDENTS MANAGEMENT
    // ========================================================================
    Route::middleware(['role:Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::post('/repeat-students/update-semester-registration', [RepeatStudentsController::class, 'updateSemesterRegistration'])
            ->name('repeat.students.updateSemesterRegistration');

        Route::get('/api/repeat-student-by-nic', [RepeatStudentsController::class, 'getRepeatStudentByNic']);

        Route::get('/repeat-students', [RepeatStudentsController::class, 'showRepeatStudentsManagement'])->name('repeat.students.management');
        Route::get('/repeat-students/get-course-data/{courseID}', [RepeatStudentsController::class, 'getCourseData']);
        Route::post('/repeat-students/get-student-name', [RepeatStudentsController::class, 'getStudentName'])->name('repeat.students.get.student.name');
        Route::post('/repeat-students/get-exam-results', [RepeatStudentsController::class, 'getRepeatStudentsForExamResults'])->name('repeat.students.get.exam.results');
        Route::post('/repeat-students/get-payments', [RepeatStudentsController::class, 'getRepeatStudentsForPayments'])->name('repeat.students.get.payments');
        Route::post('/repeat-students/update-exam-results', [RepeatStudentsController::class, 'updateExamResults'])->name('repeat.students.update.exam.results');
        Route::post('/repeat-students/update-payments', [RepeatStudentsController::class, 'updatePaymentDetails'])->name('repeat.students.update.payments');
        Route::get('/repeat-students/get-intakes/{courseID}/{location}', [RepeatStudentsController::class, 'getIntakesForCourseAndLocation'])->name('repeat.students.get.intakes.for.course.location');
        Route::post('/repeat-students/get-modules', [RepeatStudentsController::class, 'getFilteredModules'])->name('repeat.students.get.filtered.modules');
        Route::get('/repeat-students/get-semesters', [RepeatStudentsController::class, 'getSemesters'])->name('repeat.students.get.semesters');

        // Additional API endpoints consumed by the repeat students frontend
        Route::get('/api/courses', [RepeatStudentsController::class, 'apiCourses']);
        Route::get('/api/intakes', [RepeatStudentsController::class, 'apiIntakes']);
        Route::get('/api/semesters', [RepeatStudentsController::class, 'apiSemesters']);

        // Repeat Student Payment Routes
        Route::get('/repeat-student-payment', [RepeatStudentPaymentController::class, 'index'])
            ->name('repeat.payment.index');
        Route::post('/repeat-student-payment/search', [RepeatStudentPaymentController::class, 'searchStudent'])
            ->name('repeat.payment.search');
        Route::get('/api/repeat-payment-plan/{student_id}/{course_id}', [RepeatStudentPaymentController::class, 'getArchivedPaymentPlan'])
            ->name('repeat.payment.plan');
        Route::post('/repeat-student-payment/save', [RepeatStudentPaymentController::class, 'saveNewPaymentPlan'])
            ->name('repeat.payment.save');
        Route::get('/api/repeat-created-plans/{student_id}/{course_id}', [RepeatStudentPaymentController::class, 'getCreatedPaymentPlans'])
            ->name('repeat.payment.created');
    });

    // ========================================================================
    // TIMETABLE MANAGEMENT
    // ========================================================================
    Route::middleware(['auth', 'role:Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::get('/timetable', [TimetableController::class, 'showTimetable'])->name('timetable.show');
        Route::post('/timetable', [TimetableController::class, 'store'])->name('timetable.store');
        Route::get('/timetable/get-intakes/{courseId}/{location}', [TimetableController::class, 'getIntakesForCourseAndLocation'])->name('timetable.get.intakes');
        Route::get('/timetable/get-courses-by-location', [TimetableController::class, 'getCoursesByLocation'])->name('timetable.courses.by.location');
        Route::get('/timetable/get-semesters', [TimetableController::class, 'getSemesters'])->name('timetable.semesters');
        Route::get('/timetable/get-weeks', [TimetableController::class, 'getWeeks'])->name('timetable.weeks');
        Route::get('/timetable/get-semester-dates/{semesterId}', [TimetableController::class, 'getSemesterDates'])->name('timetable.get-semester-dates');
        Route::get('/timetable/get-modules-by-semester', [TimetableController::class, 'getModulesBySemester'])->name('timetable.modules.by.semester');
        Route::get('/timetable/get-intake-modules', [TimetableController::class, 'getModulesByIntake'])->name('timetable.modules.by.intake');
        Route::get('/timetable/get-specializations-for-course', [TimetableController::class, 'getSpecializationsForCourse'])->name('timetable.specializations.for.course');
        Route::post('/timetable/get-existing-timetable', [TimetableController::class, 'getExistingTimetable'])->name('timetable.get.existing');
        Route::get('/timetable/download-timetable-pdf', [TimetableController::class, 'downloadTimetablePDF'])->name('timetable.download.pdf');
        Route::get('/timetable/download-timetable-excel', [TimetableController::class, 'downloadTimetableExcel'])->name('timetable.download.excel');
        Route::get('/timetable/get-timetable-events', [TimetableController::class, 'getTimetableEvents'])->name('timetable.events');
        Route::get('/timetable/get-available-subjects', [TimetableController::class, 'getAvailableSubjects'])->name('timetable.available.subjects');
        Route::post('/timetable/assign-subject-to-timeslot', [TimetableController::class, 'assignSubjectToTimeslot'])->name('timetable.assign.subject.timeslot');
        Route::post('/timetable/assign-subjects', [TimetableController::class, 'assignSubjects'])->name('timetable.assignSubjects');
        Route::post('/timetable/delete-event', [TimetableController::class, 'deleteEvent'])->name('timetable.deleteEvent');
    });

    // ========================================================================
    // SPECIAL APPROVAL
    // ========================================================================

    // Special Approval List
    Route::middleware(['auth', 'role:DGM,Developer,Student Counselor,Program Administrator (level 01)'])->group(function () {
        Route::get('/special-approval-list', function () {
            return view('approvals.Special_approval_list');
        })->name('special.approval.list');
        Route::get('/get-special-approval-list', [EligibilityCheckingAndRegistrationController::class, 'getSpecialApprovalList']);
        Route::post('/register-eligible-student', [EligibilityCheckingAndRegistrationController::class, 'registerEligibleStudent']);
        Route::post('/update-dgm-comment', [EligibilityCheckingAndRegistrationController::class, 'updateDgmComment']);
        Route::get('/get-course-details/{courseId}', [EligibilityCheckingAndRegistrationController::class, 'getCourseDetails']);
        Route::post('/get-next-course-registration-id', [EligibilityCheckingAndRegistrationController::class, 'getNextCourseRegistrationId']);
        Route::get('/debug-special-approval', function () {
            $reg = App\Models\CourseRegistration::where('status', 'Special approval required')->with('student')->first();
            return response()->json([
                'student_id' => $reg->student->student_id,
                'nic' => $reg->student->id_value,
                'name' => $reg->student->full_name,
            ]);
        });
        Route::post('/special-approval/approve', [SpecialApprovalController::class, 'approveWithAttachment'])->name('special.approval.approve');
        Route::post('/reject-special-registration', [SpecialApprovalController::class, 'rejectWithReason'])->name('special.approval.reject');
    });

    // Special Approval Request
    Route::middleware(['auth', 'role:DGM,Student Counselor,Developer,Program Administrator (level 01),Program Administrator (level 02)'])->group(function () {
        Route::post('/send-special-approval-request', [EligibilityCheckingAndRegistrationController::class, 'sendSpecialApprovalRequest']);
        Route::post('/test-special-approval', function (Request $request) {
            return response()->json([
                'success' => true,
                'message' => 'Test route working',
                'user_role' => auth()->user()->user_role ?? 'unknown',
                'request_data' => $request->all()
            ]);
        });
    });

    // Special Approval Rejected List
    Route::middleware(['auth', 'role:DGM,Developer,Student Counselor,Program Administrator (level 01)'])->group(function () {
        Route::get('/get-special-approval-rejected', [EligibilityCheckingAndRegistrationController::class, 'getSpecialApprovalRejectedList'])->name('special.approval.rejected');
    });

    // Special Approval Document & Registration
    Route::middleware(['auth'])->group(function () {
        Route::post('/special-approval-register', [SpecialApprovalController::class, 'register']);
        Route::get('/special-approval-document/{filename}', [SpecialApprovalController::class, 'downloadDocument'])->name('special.approval.document.download');
    });

    // ========================================================================
    // UH INDEX NUMBERS
    // ========================================================================
    Route::middleware(['auth', 'role:DGM,Program Administrator (level 01),Program Administrator (level 02),Developer'])->group(function () {
        Route::get('/uh-index-numbers', [UhIndexController::class, 'showPage'])->name('uh.index.page');
        Route::post('/uh-index/courses', [UhIndexController::class, 'getCoursesByLocation'])->name('uh.index.courses');
        Route::post('/uh-index/intakes', [UhIndexController::class, 'getIntakesByCourse'])->name('uh.index.intakes');
        Route::post('/uh-index/students', [UhIndexController::class, 'getStudentsByIntake'])->name('uh.index.students');
        Route::post('/uh-index/save', [UhIndexController::class, 'saveUhIndexNumbers'])->name('uh.index.save');
        Route::post('/uh-index/terminate', [UhIndexController::class, 'terminateStudent'])->name('uh.index.terminate');
    });

    // ========================================================================
    // PAYMENT PLAN
    // ========================================================================
    Route::middleware(['auth', 'role:Bursar,Marketing Manager,Developer,Program Administrator (level 01),Program Administrator (level 02),Student Counselor,Student Counselor Trainee'])->group(function () {
        Route::get('/payment-plans', [PaymentPlanController::class, 'index'])->name('payment.plan.index');
        Route::get('/payment-plans/export/excel', [PaymentPlanController::class, 'exportExcel'])->name('payment.plan.export.excel');
        Route::get('/payment-plans/export/pdf', [PaymentPlanController::class, 'exportPdf'])->name('payment.plan.export.pdf');
        Route::get('/payment-plan', [PaymentPlanController::class, 'create'])->name('payment.plan');
        Route::get('/payment-plan/create', [PaymentPlanController::class, 'create'])->name('payment.plan.create');
        Route::post('/payment-plan/store', [PaymentPlanController::class, 'store'])->name('payment.plan.store');
        Route::get('/payment-plan/{id}/edit', [PaymentPlanController::class, 'edit'])->name('payment.plan.edit');
        Route::put('/payment-plan/{id}', [PaymentPlanController::class, 'update'])->name('payment.plan.update');
        Route::post('/payment-plan/courses-by-location', [PaymentPlanController::class, 'getCoursesByLocation'])->name('payment.plan.courses.byLocation');
        Route::post('/intakes/by-course', [PaymentPlanController::class, 'getIntakesByCourse'])->name('intakes.byCourse');
        Route::post('/get-intake-fees', [PaymentPlanController::class, 'getIntakeFees'])->name('get.intake.fees');
    });

    // ========================================================================
    // PAYMENT DISCOUNT
    // ========================================================================
    Route::middleware(['auth', 'role:DGM,Bursar,Marketing Manager,Developer,Student Counselor,Program Administrator (level 01),Program Administrator (level 02)'])->group(function () {
        Route::get('/payment-discount', [PaymentDiscountController::class, 'showPage'])->name('payment.discount.page');
        Route::post('/payment-discount/courses', [PaymentDiscountController::class, 'getCoursesByLocation'])->name('payment.discount.courses');
        Route::post('/payment-discount/intakes', [PaymentDiscountController::class, 'getIntakesByCourse'])->name('payment.discount.intakes');
        Route::post('/payment-discount/payment-plan', [PaymentDiscountController::class, 'getPaymentPlan'])->name('payment.discount.paymentplan');
        Route::post('/payment-discount/save-slt-loan', [PaymentDiscountController::class, 'saveSltLoan'])->name('payment.discount.save.sltloan');
        Route::post('/payment-discount/save-discount', [PaymentDiscountController::class, 'saveDiscount'])->name('payment.discount.save.discount');
        Route::get('/payment-discount/get-discounts', [PaymentDiscountController::class, 'getDiscounts'])->name('payment.discount.get.discounts');
        Route::post('/payment-discount/get-discounts-by-category', [PaymentDiscountController::class, 'getDiscountsByCategory'])->name('payment.discount.get.discounts.by.category');
        Route::post('/payment-discount/update-discount', [PaymentDiscountController::class, 'updateDiscount'])->name('payment.discount.update.discount');
        Route::post('/payment-discount/delete-discount', [PaymentDiscountController::class, 'deleteDiscount'])->name('payment.discount.delete.discount');
    });

    // ========================================================================
    // MISCELLANEOUS PAYMENT
    // ========================================================================
    Route::middleware(['auth', 'role:DGM,Bursar,Marketing Manager,Developer,Student Counselor,Program Administrator (level 01),Program Administrator (level 02)'])->group(function () {
        Route::get('/misc-payment', [MiscPaymentController::class, 'index'])->name('misc.payment.index');
        Route::post('/misc-payment/store', [MiscPaymentController::class, 'store'])->name('misc.payment.store');
        Route::get('/misc-payment/fetch/{studentId}', [MiscPaymentController::class, 'fetchByStudent'])->name('misc.payment.fetch');
    });

    // ========================================================================
    // PAYMENT SUMMARY
    // ========================================================================
    Route::middleware(['auth', 'role:DGM,Bursar,Marketing Manager,Developer,Student Counselor,Program Administrator (level 01),Program Administrator (level 02)'])->group(function () {
        Route::get('/payments/summary', [PaymentSummaryController::class, 'index'])->name('payment.summary');
        Route::get('/payments/summary/filter', [PaymentSummaryController::class, 'filter'])->name('payment.summary.filter');
        Route::get('/payments/summary/courses-by-location', [PaymentSummaryController::class, 'getCoursesByLocation'])->name('payment.summary.courses.by.location');
        Route::get('/payments/summary/intakes-by-location-course', [PaymentSummaryController::class, 'getIntakesByLocationAndCourse'])->name('payment.summary.intakes.by.location.course');
        Route::get('/payments/summary/student/{studentId}', [PaymentSummaryController::class, 'studentSummary'])->name('payment.summary.student');
        Route::get('/payments/summary/installment-kpi', [PaymentSummaryController::class, 'getInstallmentKpi'])->name('payment.summary.installment.kpi');
        Route::get('/payments/summary/installment-pdf', [PaymentSummaryController::class, 'exportInstallmentPdf'])->name('payment.summary.installment.pdf');

        Route::get('/payments/analytics', [PaymentSummaryController::class, 'analytics'])->name('payment.analytics');
        Route::get('/payments/analytics/export', [PaymentSummaryController::class, 'exportAnalyticsKpi'])->name('payment.analytics.export');
        Route::get('/payments/comparison', [PaymentSummaryController::class, 'comparison'])->name('payment.comparison');
        Route::get('/payments/export', [PaymentSummaryController::class, 'export'])->name('payment.export');
        Route::get('/payments/live-feed', [PaymentSummaryController::class, 'liveFeed'])->name('payment.live.feed');
    });

    // ========================================================================
    // BADGES
    // ========================================================================
    Route::middleware(['auth', 'role:DGM,Marketing Manager,Developer,Student Counselor,Program Administrator (level 01),Program Administrator (level 02)'])->group(function () {
        Route::get('/badges', [BadgeController::class, 'index'])->name('badges.generate');
        Route::post('/badges/search', [BadgeController::class, 'search'])->name('badges.search');
        Route::post('/badges/search-by-course', [BadgeController::class, 'search'])->name('badges.searchByCourse');
        Route::get('/badges/intakes', [BadgeController::class, 'getCourseIntakes'])->name('badges.intakes');
        Route::post('/badges/complete', [BadgeController::class, 'completeCourse'])->name('badges.complete');
        Route::delete('/badges/cancel', [BadgeController::class, 'cancelBadge'])->name('badges.cancel');
        Route::get('/badges/details/{code}', [BadgeController::class, 'details'])->name('badges.details');
    });

    // ========================================================================
    // PAYMENT MANAGEMENT
    // ========================================================================

    // Main Payment Routes
    Route::middleware(['auth', 'role:Bursar,Developer,Student Counselor'])->group(function () {
        Route::get('/payment', [PaymentController::class, 'index'])->name('payment.index');
        Route::post('/payment/get-plans', [PaymentController::class, 'getPaymentPlans'])->name('payment.get.plans');
        Route::post('/payment/get-student-courses', [PaymentController::class, 'getStudentCourses'])->name('payment.get.student.courses');
        Route::post('/payment/create-payment-plan', [PaymentController::class, 'createPaymentPlan'])->name('payment.create.payment.plan');
        Route::get('/payment/get-discounts', [PaymentController::class, 'getDiscounts'])->name('payment.get.discounts');

        // TEST ROUTE - Adding logging
        Route::post('/payment/get-installments', function(Request $request) {
            \Log::info('TEST ROUTE HIT - /payment/get-installments');
            return app(PaymentController::class)->getPaymentPlanInstallments($request);
        })->name('payment.get.installments');

        Route::post('/payment/get-payment-details', [PaymentController::class, 'getPaymentDetails'])->name('payment.get.payment.details');
        Route::post('/payment/save-plans', [PaymentController::class, 'savePaymentPlans'])->name('payment.save.plans');
        Route::delete('/payment/delete-plan/{id}', [PaymentController::class, 'deletePaymentPlan'])->name('payment.delete.plan');
        Route::post('/payment/existing-plans', [PaymentController::class, 'getExistingPaymentPlans'])->name('payment.existingPlans');
        Route::post('/payment/generate-slip', [PaymentController::class, 'generatePaymentSlip'])->name('payment.generate.slip');
        Route::delete('/payment/delete-slip/{id}', [PaymentController::class, 'deletePaymentSlip'])->name('payment.delete.slip');
        Route::post('/payment/make-payment', [PaymentController::class, 'makePayment'])->name('payment.make');
        Route::post('/payment/download-slip-pdf', [PaymentController::class, 'downloadPaymentSlipPDF'])->name('payment.download.slip.pdf');
        Route::post('/payment/save-record', [PaymentController::class, 'savePaymentRecord'])->name('payment.save.record');
        Route::post('/payment/get-records', [PaymentController::class, 'getPaymentRecords'])->name('payment.get.records');
        Route::post('/payment/update-record', [PaymentController::class, 'updatePaymentRecord'])->name('payment.update.record');
        Route::post('/payment/slt-loan/get-records', [PaymentController::class, 'getSltLoanRecords'])->name('payment.slt.get.records');
        Route::post('/payment/slt-loan/update-record', [PaymentController::class, 'updateSltLoanRecord'])->name('payment.slt.update.record');
        Route::post('/payment/delete-record', [PaymentController::class, 'deletePaymentRecord'])->name('payment.delete.record');
        Route::post('/payment/get-summary', [PaymentController::class, 'getPaymentSummary'])->name('payment.get.summary');
        Route::post('/payment/export-summary', [PaymentController::class, 'exportPaymentSummary'])->name('payment.export.summary');
        Route::get('/payment/get-intakes/{courseID}/{location}', [PaymentController::class, 'getIntakesForCourseAndLocation'])->name('payment.get.intakes.for.course.location');
        Route::post('/payment/save-custom-payments', [PaymentController::class, 'saveCustomPayments'])->name('payment.save.custom.payments');
        Route::post('/payment/get-custom-payments', [PaymentController::class, 'getCustomPayments'])->name('payment.get.custom.payments');
    });

    // Payment Statement Download
    Route::middleware(['auth', 'role:Bursar,Developer,Student Counselor'])->group(function () {
        Route::get('/payment/statement-download', [PaymentController::class, 'showDownloadPage'])->name('payment.showDownloadPage');
        Route::post('/payment/download-statement', [PaymentController::class, 'downloadPaymentStatement'])->name('payment.downloadStatement');
    });

    // ========================================================================
    // LATE PAYMENT & LATE FEE APPROVAL
    // ========================================================================
    Route::middleware(['auth', 'role:Bursar,Developer,Student Counselor,DGM,Program Administrator (level 01),Program Administrator (level 02)'])->group(function () {
        Route::get('/late-payment', [LatePaymentController::class, 'index'])->name('late.payment.index');
        Route::post('/late-payment/get-payment-plan', [LatePaymentController::class, 'getPaymentPlan'])->name('late.payment.get.payment.plan');
        Route::post('/late-payment/get-paid-payments', [LatePaymentController::class, 'getPaidPaymentDetails'])->name('late.payment.get.paid.payments');
        Route::post('/late-payment/get-student-courses', [LatePaymentController::class, 'getStudentCourses'])->name('late.payment.get.student.courses');
        Route::get('/late-fee/approval', [LateFeeApprovalController::class, 'index'])->name('latefee.approval.index');
        Route::post('/late-fee/get-payment-plan', [LateFeeApprovalController::class, 'getApprovalPaymentPlan'])->name('latefee.get.paymentplan');
        Route::post('/late-fee/approve-installment/{installmentId}', [LateFeeApprovalController::class, 'approveLateFeePerInstallment'])->name('latefee.approve.installment');
        Route::post('/late-fee/approve-global/{studentNic}/{courseId}', [LateFeeApprovalController::class, 'approveLateFeeGlobal'])->name('latefee.approve.global');
        Route::post('/late-fee/get-student-courses', [LateFeeApprovalController::class, 'getStudentCourses'])->name('latefee.get.courses');
        Route::get('/late-fee/approval/{studentNic}/{courseId}', [LateFeeApprovalController::class, 'approvalPage'])->name('latefee.approval.page');
    });

    // ========================================================================
    // TEAM PHASE MANAGEMENT
    // ========================================================================
    // Team Nebula IT Page - Accessible to all authenticated users
    Route::get('/team-phase', [TeamPhaseController::class, 'index'])->name('team.phase.index');

    // Team Phase Management Routes - Developer only
    Route::middleware(['auth', 'role:Developer'])->group(function () {
        Route::post('/phase/create', [TeamPhaseController::class, 'createPhase'])->name('phase.create');
        Route::put('/phase/{phase}/update', [TeamPhaseController::class, 'updatePhase'])->name('phase.update');
        Route::delete('/phase/{phase}/delete', [TeamPhaseController::class, 'deletePhase'])->name('phase.delete');
        Route::delete('/phases/{phase}/remove-supervisor', [TeamPhaseController::class, 'removeSupervisor'])->name('phase.remove-supervisor');
        Route::post('/team/assign', [TeamPhaseController::class, 'assignMember'])->name('team.assign');
        Route::put('/team/{team}/update', [TeamPhaseController::class, 'updateMember'])->name('team.update');
        Route::delete('/team/{team}/delete', [TeamPhaseController::class, 'deleteMember'])->name('team.delete');
        Route::post('/team/{team}/add-phase', [TeamPhaseController::class, 'addMemberToPhase'])->name('team.add-phase');
        Route::delete('/team/{team}/remove-phase/{phase}', [TeamPhaseController::class, 'removeMemberFromPhase'])->name('team.remove-phase');
    });

    // ========================================================================
    // DASHBOARDS
    // ========================================================================

    // DGM Dashboard
    Route::middleware(['role:DGM,Developer,Program Administrator (level 01)'])->group(function () {
        Route::get('/dgmdashboard', [DGMDashboardController::class, 'showDashboard'])->name('dgmdashboard');
        Route::get('/api/dashboard/overview', [DGMDashboardController::class, 'getOverviewMetrics'])->name('api.dashboard.overview');
        Route::get('/api/dashboard/monthly-trend', [DGMDashboardController::class, 'getMonthlyRevenueTrend'])->name('api.dashboard.monthly.trend');
        Route::get('/api/dashboard/students-by-location', [DGMDashboardController::class, 'getStudentsByLocation'])->name('api.dashboard.students.location');
        Route::get('/api/dashboard/students-data', [DGMDashboardController::class, 'getStudentsData']);
        Route::get('/api/dashboard/revenue-data', [DGMDashboardController::class, 'getRevenueData']);
        Route::get('/api/dashboard/revenue-by-location', [DGMDashboardController::class, 'getRevenueByLocation'])->name('api.dashboard.revenue.location');
        Route::get('/api/dashboard/revenue-by-year-course', [DGMDashboardController::class, 'getRevenueByYearCourse']);
        Route::get('/api/dashboard/payment-status', [DGMDashboardController::class, 'getPaymentStatus'])->name('api.dashboard.payment.status');
        Route::get('/api/dashboard/future-projections', [DGMDashboardController::class, 'getFutureProjections'])->name('api.dashboard.future.projections');
        Route::get('/api/dashboard/outstanding-by-year-course', [DGMDashboardController::class, 'getOutstandingByYearCourse']);
        Route::post('/bulk-upload/students', [DGMDashboardController::class, 'bulkStudentUpload'])->name('bulk.student.upload');
        Route::post('/bulk-upload/revenues', [DGMDashboardController::class, 'bulkRevenueUpload'])->name('bulk.revenue.upload');
        Route::get('/bulk-upload/student-template', [DGMDashboardController::class, 'downloadStudentTemplate'])->name('bulk.student.template');
        Route::get('/bulk-upload/revenue-template', [DGMDashboardController::class, 'downloadRevenueTemplate'])->name('bulk.revenue.template');
        Route::get('/bulk-upload/export-students', [DGMDashboardController::class, 'exportStudentBulkData'])->name('bulk.student.export');
        Route::get('/bulk-upload/export-revenues', [DGMDashboardController::class, 'exportRevenueBulkData'])->name('bulk.revenue.export');
        Route::get('/api/dashboard/marketing-data', [DGMDashboardController::class, 'getMarketingData']);
    });

    // Student Counselor Dashboard
    Route::middleware(['role:Student Counselor,Student Counselor Trainee,Developer'])->group(function () {
        Route::get('/student-counselor-dashboard', [StudentCounselorDashboardController::class, 'showDashboard'])->name('student.counselor.dashboard');
        Route::get('/api/student-counselor/overview', [StudentCounselorDashboardController::class, 'getOverviewMetrics'])->name('api.student.counselor.overview');
        Route::get('/api/student-counselor/recent-registrations', [StudentCounselorDashboardController::class, 'getRecentRegistrations'])->name('api.student.counselor.recent.registrations');
        Route::get('/api/student-counselor/marketing-survey', [StudentCounselorDashboardController::class, 'getMarketingSurveyData'])->name('api.student.counselor.marketing.survey');
        Route::get('/api/student-counselor/daily-trend', [StudentCounselorDashboardController::class, 'getDailyRegistrationTrend'])->name('api.student.counselor.daily.trend');
        Route::get('/api/student-counselor/location-data', [StudentCounselorDashboardController::class, 'getRegistrationsByLocation'])->name('api.student.counselor.location.data');
        Route::get('/api/student-counselor/course-data', [StudentCounselorDashboardController::class, 'getRegistrationsByCourse'])->name('api.student.counselor.course.data');
        Route::get('/api/student-counselor/slt-employee-data', [StudentCounselorDashboardController::class, 'getSltEmployeeData'])->name('api.student.counselor.slt.employee.data');
        Route::get('/api/student-counselor/counselor-performance', [StudentCounselorDashboardController::class, 'getCounselorPerformanceData'])->name('api.student.counselor.counselor.performance');
    });

    // Marketing Manager Dashboard
    Route::middleware(['role:Marketing Manager,Developer'])->group(function () {
        Route::get('/marketing-manager-dashboard', [MarketingManagerDashboardController::class, 'showDashboard'])->name('marketing.manager.dashboard');
        Route::get('/api/marketing-manager/overview', [MarketingManagerDashboardController::class, 'getOverviewMetrics'])->name('api.marketing.manager.overview');
        Route::get('/api/marketing-manager/recent-registrations', [MarketingManagerDashboardController::class, 'getRecentRegistrations'])->name('api.marketing.manager.recent.registrations');
        Route::get('/api/marketing-manager/marketing-survey', [MarketingManagerDashboardController::class, 'getMarketingSurveyAnalysis'])->name('api.marketing.manager.marketing.survey');
        Route::get('/api/marketing-manager/monthly-trend', [MarketingManagerDashboardController::class, 'getMonthlyRegistrationTrend'])->name('api.marketing.manager.monthly.trend');
        Route::get('/api/marketing-manager/location-data', [MarketingManagerDashboardController::class, 'getRegistrationsByLocation'])->name('api.marketing.manager.location.data');
        Route::get('/api/marketing-manager/top-courses', [MarketingManagerDashboardController::class, 'getTopPerformingCourses'])->name('api.marketing.manager.top.courses');
        Route::get('/api/marketing-manager/conversion-funnel', [MarketingManagerDashboardController::class, 'getConversionFunnelData'])->name('api.marketing.manager.conversion.funnel');
        Route::get('/api/marketing-manager/roi-data', [MarketingManagerDashboardController::class, 'getMarketingROIBySource'])->name('api.marketing.manager.roi.data');
        Route::get('/api/marketing-manager/demographics', [MarketingManagerDashboardController::class, 'getDemographicInsights'])->name('api.marketing.manager.demographics');
    });

    // Hostel Manager Dashboard
    Route::middleware(['role:Hostel Manager,Developer'])->group(function () {
        Route::get('/hostel-manager-dashboard', [HostelManagerDashboardController::class, 'showDashboard'])->name('hostel.manager.dashboard');
        Route::get('/api/hostel-manager/overview', [HostelManagerDashboardController::class, 'getOverviewMetrics'])->name('api.hostel.manager.overview');
        Route::get('/api/hostel-manager/analytics', [HostelManagerDashboardController::class, 'getAnalytics'])->name('api.hostel.manager.analytics');
        Route::get('/api/hostel-manager/action-list', [HostelManagerDashboardController::class, 'getActionList'])->name('api.hostel.manager.action.list');
        Route::get('/api/hostel-manager/recent-clearances', [HostelManagerDashboardController::class, 'getRecentHostelClearances'])->name('api.hostel.manager.recent.clearances');
        Route::get('/api/hostel-manager/filter', [HostelManagerDashboardController::class, 'filterRequests'])->name('api.hostel.manager.filter');
        Route::get('/api/hostel-manager/search', [HostelManagerDashboardController::class, 'searchRequests'])->name('api.hostel.manager.search');
        Route::get('/api/hostel-manager/list-by-status', [HostelManagerDashboardController::class, 'listByStatus'])->name('api.hostel.manager.list.by.status');
        Route::post('/api/hostel-manager/update-status', [HostelManagerDashboardController::class, 'updateRequestStatus'])->name('api.hostel.manager.update.status');
        Route::post('/api/hostel-manager/bulk-update', [HostelManagerDashboardController::class, 'bulkUpdateRequests'])->name('api.hostel.manager.bulk.update');
        Route::post('/api/hostel-manager/export', [HostelManagerDashboardController::class, 'exportData'])->name('api.hostel.manager.export');
    });

    // Admin L1 Dashboard
    Route::middleware(['role:Program Administrator (level 01),Developer'])->group(function () {
        Route::get('/admin-l1-dashboard', [AdminL1DashboardController::class, 'showDashboard'])->name('admin.l1.dashboard');
        Route::get('/api/admin-l1/overview', [AdminL1DashboardController::class, 'getOverviewMetrics'])->name('api.admin.l1.overview');
        Route::get('/api/admin-l1/student-stats', [AdminL1DashboardController::class, 'getStudentStats'])->name('api.admin.l1.student.stats');
        Route::get('/api/admin-l1/course-registration-stats', [AdminL1DashboardController::class, 'getCourseRegistrationStats'])->name('api.admin.l1.course.registration.stats');
        Route::get('/api/admin-l1/clearance-stats', [AdminL1DashboardController::class, 'getClearanceStats'])->name('api.admin.l1.clearance.stats');
        Route::get('/api/admin-l1/financial-stats', [AdminL1DashboardController::class, 'getFinancialStats'])->name('api.admin.l1.financial.stats');
        Route::get('/api/admin-l1/recent-activities', [AdminL1DashboardController::class, 'getRecentActivities'])->name('api.admin.l1.recent.activities');
        Route::get('/api/admin-l1/action-items', [AdminL1DashboardController::class, 'getActionItems'])->name('api.admin.l1.action.items');
    });

    // Admin L2 Dashboard
    Route::middleware(['role:Program Administrator (level 02),Program Administrator (level 02) Trainee,Developer'])->group(function () {
        Route::get('/program-admin-l2-dashboard', [ProgramAdminL2DashboardController::class, 'showDashboard'])->name('program.admin.l2.dashboard');
        Route::get('/api/program-admin-l2/overview', [ProgramAdminL2DashboardController::class, 'getOverviewMetrics'])->name('api.program.admin.l2.overview');
        Route::get('/api/program-admin-l2/pending-approvals', [ProgramAdminL2DashboardController::class, 'getPendingApprovals'])->name('api.program.admin.l2.pending.approvals');
        Route::get('/api/program-admin-l2/active-semesters', [ProgramAdminL2DashboardController::class, 'getActiveSemesters'])->name('api.program.admin.l2.active.semesters');
        Route::get('/api/program-admin-l2/courses-by-location', [ProgramAdminL2DashboardController::class, 'getCoursesByLocation'])->name('api.program.admin.l2.courses.by.location');
        Route::get('/api/program-admin-l2/intakes', [ProgramAdminL2DashboardController::class, 'getIntakes'])->name('api.program.admin.l2.intakes');
        Route::get('/api/program-admin-l2/modules-by-course', [ProgramAdminL2DashboardController::class, 'getModulesByCourse'])->name('api.program.admin.l2.modules.by.course');
        Route::get('/api/program-admin-l2/academic-performance', [ProgramAdminL2DashboardController::class, 'getAcademicPerformance'])->name('api.program.admin.l2.academic.performance');
        Route::get('/api/program-admin-l2/attendance-overview', [ProgramAdminL2DashboardController::class, 'getAttendanceOverview'])->name('api.program.admin.l2.attendance.overview');
        Route::get('/api/program-admin-l2/clearance-status', [ProgramAdminL2DashboardController::class, 'getClearanceStatus'])->name('api.program.admin.l2.clearance.status');
        Route::get('/api/program-admin-l2/payment-overview', [ProgramAdminL2DashboardController::class, 'getPaymentOverview'])->name('api.program.admin.l2.payment.overview');
        Route::post('/api/program-admin-l2/approve-registration/{id}', [ProgramAdminL2DashboardController::class, 'approveRegistration'])->name('api.program.admin.l2.approve.registration');
        Route::post('/api/program-admin-l2/reject-registration/{id}', [ProgramAdminL2DashboardController::class, 'rejectRegistration'])->name('api.program.admin.l2.reject.registration');
        Route::post('/api/program-admin-l2/approve-all', [ProgramAdminL2DashboardController::class, 'approveAllPending'])->name('api.program.admin.l2.approve.all');
        Route::post('/api/program-admin-l2/reject-all', [ProgramAdminL2DashboardController::class, 'rejectAllPending'])->name('api.program.admin.l2.reject.all');
    });

    // Admin L2 Trainee Dashboard
    Route::middleware(['role:Program Administrator (level 02) Trainee,Developer'])->group(function () {
        Route::get('/program-admin-l2-trainee-dashboard', [ProgramAdminL2TraineeDashboardController::class, 'showDashboard'])->name('program.admin.l2.trainee.dashboard');
        Route::get('/api/program-admin-l2-trainee/overview', [ProgramAdminL2TraineeDashboardController::class, 'getOverviewMetrics'])->name('api.program.admin.l2.trainee.overview');
        Route::get('/api/program-admin-l2-trainee/active-semesters', [ProgramAdminL2TraineeDashboardController::class, 'getActiveSemesters'])->name('api.program.admin.l2.trainee.active.semesters');
        Route::get('/api/program-admin-l2-trainee/attendance-overview', [ProgramAdminL2TraineeDashboardController::class, 'getAttendanceOverview'])->name('api.program.admin.l2.trainee.attendance.overview');
        Route::get('/api/program-admin-l2-trainee/academic-performance', [ProgramAdminL2TraineeDashboardController::class, 'getAcademicPerformance'])->name('api.program.admin.l2.trainee.academic.performance');
        Route::get('/api/program-admin-l2-trainee/recent-activities', [ProgramAdminL2TraineeDashboardController::class, 'getRecentActivities'])->name('api.program.admin.l2.trainee.recent.activities');
    });

    // Student Counselor Trainee Dashboard
    Route::middleware(['role:Student Counselor Trainee,Developer'])->group(function () {
        Route::get('/student-counselor-trainee-dashboard', [StudentCounselorTraineeDashboardController::class, 'showDashboard'])->name('student.counselor.trainee.dashboard');
        Route::get('/api/student-counselor-trainee/overview', [StudentCounselorTraineeDashboardController::class, 'getOverviewMetrics'])->name('api.student.counselor.trainee.overview');
        Route::get('/api/student-counselor-trainee/recent-registrations', [StudentCounselorTraineeDashboardController::class, 'getRecentRegistrations'])->name('api.student.counselor.trainee.recent.registrations');
        Route::get('/api/student-counselor-trainee/marketing-survey', [StudentCounselorTraineeDashboardController::class, 'getMarketingSurveyData'])->name('api.student.counselor.trainee.marketing.survey');
        Route::get('/api/student-counselor-trainee/daily-trend', [StudentCounselorTraineeDashboardController::class, 'getDailyRegistrationTrend'])->name('api.student.counselor.trainee.daily.trend');
        Route::get('/api/student-counselor-trainee/location-data', [StudentCounselorTraineeDashboardController::class, 'getRegistrationsByLocation'])->name('api.student.counselor.trainee.location.data');
        Route::get('/api/student-counselor-trainee/course-data', [StudentCounselorTraineeDashboardController::class, 'getRegistrationsByCourse'])->name('api.student.counselor.trainee.course.data');
    });

    // Project Tutor Dashboard
    Route::middleware(['role:Project Tutor,Developer'])->group(function () {
        Route::get('/project-tutor-dashboard', [ProjectTutorDashboardController::class, 'index'])->name('project.tutor.dashboard');
        Route::get('/api/project-tutor/pending-clearances', [ProjectTutorDashboardController::class, 'getPendingClearances'])->name('api.project.tutor.pending.clearances');
        Route::get('/api/project-tutor/recent-updates', [ProjectTutorDashboardController::class, 'getRecentUpdates'])->name('api.project.tutor.recent.updates');
        Route::get('/api/project-tutor/summary', [ProjectTutorDashboardController::class, 'getSummary'])->name('api.project.tutor.summary');
        Route::post('/api/project-tutor/approve/{id}', [ProjectTutorDashboardController::class, 'approveProject'])->name('api.project.tutor.approve');
        Route::post('/api/project-tutor/reject/{id}', [ProjectTutorDashboardController::class, 'rejectProject'])->name('api.project.tutor.reject');
    });

    // Bursar Dashboard
    Route::middleware(['role:Bursar,Developer'])->group(function () {
        Route::get('/bursar-dashboard', [BursarDashboardController::class, 'index'])->name('bursar.dashboard');
    });

    // Librarian Dashboard
    Route::middleware(['role:Librarian,Developer'])->group(function () {
        Route::get('/librarian-dashboard', [LibrarianDashboardController::class, 'index'])->name('librarian.dashboard');
    });

    // Developer Dashboard
    Route::middleware(['role:Developer', 'restrict.debug'])->group(function () {
        Route::get('/developer-dashboard', [DeveloperDashboardController::class, 'index'])->name('developer.dashboard');
    });

    // System Diagnostics (Admin & Developer only)
    Route::middleware(['role:Admin,Developer', 'restrict.debug'])->group(function () {
        Route::get('/diagnostics', [App\Http\Controllers\DiagnosticsController::class, 'index'])->name('diagnostics.index');
        Route::post('/diagnostics/clear-cache', [App\Http\Controllers\DiagnosticsController::class, 'clearCache'])->name('diagnostics.clear-cache');
    });

    // Permission Checker (Admin, Program Administrators & Developer)
    Route::middleware(['role:Program Administrator (level 01),Program Administrator (level 02),Developer,Admin', 'restrict.debug'])->group(function () {
        Route::get('/permission-checker', function() {
            return view('permission_checker');
        })->name('permission.checker');
    });

}); // End of main authenticated routes group

// Utility routes for session management
Route::middleware('web')->group(function () {
    // Refresh CSRF token without full page reload
    Route::get('/refresh-csrf', function () {
        return response()->json([
            'token' => csrf_token(),
            'success' => true
        ]);
    })->name('refresh.csrf');

    // JavaScript error logging endpoint
    Route::post('/log-js-error', function (Request $request) {
        \Log::error('JavaScript Error', [
            'message' => $request->input('message'),
            'file' => $request->input('file'),
            'line' => $request->input('line'),
            'url' => $request->input('url'),
            'user_agent' => $request->userAgent(),
            'user_id' => auth()->id(),
        ]);
        return response()->json(['success' => true]);
    })->name('log.js.error');
});


