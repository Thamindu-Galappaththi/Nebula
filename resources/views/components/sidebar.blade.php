<div>
    @php
        use App\Helpers\RoleHelper;
        $role = auth()->user() ? auth()->user()->getRoleList() : [];
    @endphp
    <div class="brand-logo d-flex align-items-center justify-content-center py-3 position-relative w-100">
        <!-- Mobile close button (uses the same toggler JS) -->
          <a href="#" role="button" aria-label="Close sidebar"
              class="nav-link sidebartoggler d-xl-none position-absolute top-0 end-0 mt-1 me-3">
            <i class="ti ti-x fs-5"></i>
        </a>
        <a href="/dashboard" class="text-nowrap logo-img">
            <img src="{{ asset('images/logos/nebula.png') }}" alt="Nebula" width="180">
        </a>
    </div>

    <nav class="sidebar-nav scroll-sidebar" data-simplebar="">
        <ul class="metismenu" id="menu">
            {{-- HOME --}}
            <li class="nav-small-cap">
                <span class="nav-small-cap-text">HOME</span>
            </li>
            @if(RoleHelper::hasPermission($role, 'dashboard'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'dashboard' ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span><i class="ti ti-layout-dashboard"></i></span>
                        <span class="hide-menu">Dashboard</span>
                    </a>
                </li>
            @endif

            {{-- USER MANAGEMENT --}}
            @if(RoleHelper::hasAnyRole($role, ['Program Administrator (level 01)', 'Developer']))
            <li class="nav-small-cap">
                <span class="nav-small-cap-text">USER MANAGEMENT</span>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link {{ Route::currentRouteName() == 'create.user' ? 'active' : '' }}" href="{{ route('create.user') }}">
                    <span><i class="ti ti-user"></i></span>
                    <span class="hide-menu">Create User</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link {{ Route::currentRouteName() == 'dgm.user.management' ? 'active' : '' }}" href="{{ route('dgm.user.management') }}">
                    <span><i class="ti ti-users"></i></span>
                    <span class="hide-menu">User Management</span>
                </a>
            </li>
            @endif

            {{-- STUDENT MANAGEMENT --}}
            @if(
                RoleHelper::hasPermission($role, 'student.registration') ||
                RoleHelper::hasPermission($role, 'student.other.information') ||
                RoleHelper::hasPermission($role, 'student.list') ||
                RoleHelper::hasPermission($role, 'student.view') ||
                RoleHelper::hasPermission($role, 'course.badge') ||
                RoleHelper::hasPermission($role, 'student.profile') ||
                RoleHelper::hasPermission($role, 'termination.tracking')
                )
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">STUDENT MANAGEMENT</span>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'student.registration'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'student_management.registration' ? 'active' : '' }}" href="{{ route('student_management.registration') }}">
                        <span><i class="ti ti-user"></i></span>
                        <span class="hide-menu">Student Registration</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'student.other.information'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'student_management.other.information' ? 'active' : '' }}" href="{{ route('student_management.other.information') }}">
                        <span><i class="ti ti-layout"></i></span>
                        <span class="hide-menu">Student Other Info</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'student.list'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'student_management.list' ? 'active' : '' }}" href="{{ route('student_management.list') }}">
                        <span><i class="ti ti-menu"></i></span>
                        <span class="hide-menu">Student Lists</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'student.view'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'student_management.view' ? 'active' : '' }}" href="{{ route('student_management.view') }}">
                        <span><i class="ti ti-users"></i></span>
                        <span class="hide-menu">All Students View</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'course.badge'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ in_array(Route::currentRouteName(), ['badges.index', 'badges.generate'], true) ? 'active' : '' }}" href="{{ route('badges.generate') }}">
                        <span><i class="ti ti-id-badge"></i></span>
                        <span class="hide-menu">Badges Generation</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'student.profile'))
                <li class="sidebar-item">
                    @php
                        $user = auth()->user();
                        $studentProfileUrl = isset($user->student_id) && $user->student_id
                            ? route('student_management.profile', ['studentId' => $user->student_id])
                            : route('student_management.profile', ['studentId' => 0]);
                    @endphp
                    <a class="sidebar-link {{ Route::currentRouteName() == 'student_management.profile' ? 'active' : '' }}" href="{{ $studentProfileUrl }}">
                        <span><i class="ti ti-id"></i></span>
                        <span class="hide-menu">Student Profile</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'termination.tracking'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'termination.tracking' ? 'active' : '' }}" href="{{ route('termination.tracking') }}">
                        <span><i class="ti ti-clipboard"></i></span>
                        <span class="hide-menu">Termination Tracking</span>
                    </a>
                </li>
            @endif

            {{-- 🔹 Registrations --}}
            @if(
                RoleHelper::hasPermission($role, 'course.registration') ||
                RoleHelper::hasPermission($role, 'eligibility.registration') ||
                RoleHelper::hasPermission($role, 'semester.registration') ||
                RoleHelper::hasPermission($role, 'semester.registration.store') ||
                RoleHelper::hasPermission($role, 'semester.registration.getCoursesByLocation') ||
                RoleHelper::hasPermission($role, 'semester.registration.getOngoingIntakes') ||
                RoleHelper::hasPermission($role, 'semester.registration.getOpenSemesters') ||
                RoleHelper::hasPermission($role, 'semester.registration.getEligibleStudents') ||
                RoleHelper::hasPermission($role, 'semester.registration.getAllSemestersForCourse') ||
                RoleHelper::hasPermission($role, 'semester.registration.updateStatus') ||
                RoleHelper::hasPermission($role, 'semester.registration.checkClearances') ||
                RoleHelper::hasPermission($role, 'semester.registration.approveReenroll') ||
                RoleHelper::hasPermission($role, 'semester.registration.rejectReenroll') ||
                RoleHelper::hasPermission($role, 'semester.registration.approveReRegister') ||
                RoleHelper::hasPermission($role, 'semester.registration.rejectReRegister') ||
                RoleHelper::hasPermission($role, 'module.management') ||
                RoleHelper::hasPermission($role, 'uh.index.page') ||
                RoleHelper::hasPermission($role, 'course.change.index')
                )
                
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">REGISTRATIONS</span>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'course.registration'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'course.registration' ? 'active' : '' }}" href="{{ route('course.registration') }}">
                        <span><i class="ti ti-book"></i></span>
                        <span class="hide-menu">Course Registration</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'eligibility.registration'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'eligibility.registration' ? 'active' : '' }}" href="{{ route('eligibility.registration') }}">
                        <span><i class="ti ti-checks"></i></span>
                        <span class="hide-menu">Eligibility & Registration</span>
                    </a>
                </li>
            @endif
            @if(
                RoleHelper::hasPermission($role, 'semester.registration') ||
                RoleHelper::hasPermission($role, 'semester.registration.store') ||
                RoleHelper::hasPermission($role, 'semester.registration.getCoursesByLocation') ||
                RoleHelper::hasPermission($role, 'semester.registration.getOngoingIntakes') ||
                RoleHelper::hasPermission($role, 'semester.registration.getOpenSemesters') ||
                RoleHelper::hasPermission($role, 'semester.registration.getEligibleStudents') ||
                RoleHelper::hasPermission($role, 'semester.registration.getAllSemestersForCourse') ||
                RoleHelper::hasPermission($role, 'semester.registration.updateStatus') ||
                RoleHelper::hasPermission($role, 'semester.registration.checkClearances') ||
                RoleHelper::hasPermission($role, 'semester.registration.approveReenroll') ||
                RoleHelper::hasPermission($role, 'semester.registration.rejectReenroll') ||
                RoleHelper::hasPermission($role, 'semester.registration.approveReRegister') ||
                RoleHelper::hasPermission($role, 'semester.registration.rejectReRegister')
                )
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'semester.registration' ? 'active' : '' }}" href="{{ route('semester.registration') }}">
                        <span><i class="ti ti-calendar-stats"></i></span>
                        <span class="hide-menu">Semester Registration</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'specialization.registration' ? 'active' : '' }}" href="{{ route('specialization.registration') }}">
                        <span><i class="ti ti-user-check"></i></span>
                        <span class="hide-menu">Specialization Registration</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'module.management'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'module.management' ? 'active' : '' }}" href="{{ route('module.management') }}">
                        <span><i class="ti ti-briefcase"></i></span>
                        <span class="hide-menu">Elective Module Registrations</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'uh.index.page'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'uh.index.page' ? 'active' : '' }}" href="{{ route('uh.index.page') }}">
                        <span><i class="ti ti-id-badge"></i></span>
                        <span class="hide-menu">External Institute IDs</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'course.change'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'course.change.index' ? 'active' : '' }}" href="{{ route('course.change.index') }}">
                        <span><i class="ti ti-repeat"></i></span>
                        <span class="hide-menu">Course Change</span>
                    </a>
                </li>
            @endif

            {{-- 🔹 Exam & Results --}}
            @if(
                RoleHelper::hasPermission($role, 'exam.results') || 
                RoleHelper::hasPermission($role, 'student.exam.result.management') ||
                RoleHelper::hasPermission($role, 'exam.results.view.edit') ||
                RoleHelper::hasPermission($role, 'repeat.students.management')
                )
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">EXAMS & RESULTS</span>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'exam.results'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'student.exam.result.management' ? 'active' : '' }}" href="{{ route('student.exam.result.management') }}">
                        <span><i class="ti ti-file"></i></span>
                        <span class="hide-menu">Add Exam Result</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'exam.results.view.edit'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'exam.results.view.edit' ? 'active' : '' }}" href="{{ route('exam.results.view.edit') }}">
                        <span><i class="ti ti-edit"></i></span>
                        <span class="hide-menu">View & Edit Results</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'repeat.students.management'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'repeat.students.management' ? 'active' : '' }}" href="{{ route('repeat.students.management') }}">
                        <span><i class="ti ti-refresh"></i></span>
                        <span class="hide-menu">Repeat Students</span>
                    </a>
                </li>
            @endif

            {{-- 🔹 Attendance --}}
            @if(
                RoleHelper::hasPermission($role, 'attendance') ||
                RoleHelper::hasPermission($role, 'overall.attendance')
                )
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">ATTENDANCE</span>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'attendance'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'attendance' ? 'active' : '' }}" href="{{ route('attendance') }}">
                        <span><i class="ti ti-id"></i></span>
                        <span class="hide-menu">Attendance</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'overall.attendance'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'overall.attendance' ? 'active' : '' }}" href="{{ route('overall.attendance') }}">
                        <span><i class="ti ti-id"></i></span>
                        <span class="hide-menu">Overall Attendance</span>
                    </a>
                </li>
            @endif
           
            {{-- 🔹STUDENT CLEARANCE --}}
            @if(
                RoleHelper::hasPermission($role, 'all.clearance.management') ||
                RoleHelper::hasPermission($role, 'library.clearance') ||
                RoleHelper::hasPermission($role, 'hostel.clearance.form.management') ||
                RoleHelper::hasPermission($role, 'project.clearance.management') ||
                RoleHelper::hasPermission($role, 'payment.clearance')
                )
            <li class="nav-small-cap">
                <span class="nav-small-cap-text">STUDENT CLEARANCE</span>
            </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'all.clearance.management'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'all.clearance.management' ? 'active' : '' }}" href="{{ route('all.clearance.management') }}">
                        <span><i class="ti ti-clipboard"></i></span>
                        <span class="hide-menu">All Clearance</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'library.clearance'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'library.clearance' ? 'active' : '' }}" href="{{ route('library.clearance') }}">
                        <span><i class="ti ti-clipboard"></i></span>
                        <span class="hide-menu">Library Clearance</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'hostel.clearance.form.management'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'hostel.clearance.form.management' ? 'active' : '' }}" href="{{ route('hostel.clearance.form.management') }}">
                        <span><i class="ti ti-note"></i></span>
                        <span class="hide-menu">Hostel Clearance</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'project.clearance.management'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'project.clearance.management' ? 'active' : '' }}" href="{{ route('project.clearance.management') }}">
                        <span><i class="ti ti-briefcase"></i></span>
                        <span class="hide-menu">Project Clearance</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'payment.clearance'))            
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'payment.clearance' ? 'active' : '' }}" href="{{ route('payment.clearance') }}">
                        <span><i class="ti ti-cash"></i></span>
                        <span class="hide-menu">Payment Clearance</span>
                    </a>
                </li>
            @endif

            {{-- 🔹Courses & Modules --}}
            @if(
                RoleHelper::hasPermission($role, 'module.creation') ||
                RoleHelper::hasPermission($role, 'course.management') ||
                RoleHelper::hasPermission($role, 'intake.create') ||             
                RoleHelper::hasPermission($role, 'semesters.create') ||
                RoleHelper::hasPermission($role, 'semester.management') ||
                RoleHelper::hasPermission($role, 'timetable')
                )
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">COURSES & MODULES</span>
                </li>
            @endif
                @if(RoleHelper::hasAnyRole($role, ['Developer', 'Program Administrator (level 02)']) || RoleHelper::hasPermission($role, 'module.creation'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'module.creation' ? 'active' : '' }}" href="{{ route('module.creation') }}">
                        <span><i class="ti ti-plus"></i></span>
                        <span class="hide-menu">Create New Modules</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'course.management'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'course.management' ? 'active' : '' }}" href="{{ route('course.management') }}">
                        <span><i class="ti ti-notebook"></i></span>
                        <span class="hide-menu">Create New Courses</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'intake.create'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'intake.create' ? 'active' : '' }}" href="{{ route('intake.create') }}">
                        <span><i class="ti ti-pencil"></i></span>
                        <span class="hide-menu">Create New Intakes</span>
                    </a>
                </li>
            @endif

            {{-- Divider (light) --}}
            @if(
                RoleHelper::hasPermission($role, 'module.creation') ||
                RoleHelper::hasPermission($role, 'course.management') ||
                RoleHelper::hasPermission($role, 'intake.create') ||             
                RoleHelper::hasPermission($role, 'semesters.create') ||
                RoleHelper::hasPermission($role, 'semester.management') ||
                RoleHelper::hasPermission($role, 'timetable')
                )
                <li><hr class="my-2 border-gray-200 opacity-30"></li>
                @endif

            {{-- 🔹 Semester Lifecycle --}}
            @if(RoleHelper::hasPermission($role, 'semesters.create'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'semesters.create' ? 'active' : '' }}" href="{{ route('semesters.create') }}">
                        <span><i class="ti ti-calendar"></i></span>
                        <span class="hide-menu">Create New Semesters</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'semesters.index' ? 'active' : '' }}" href="{{ route('semesters.index') }}">
                        <span><i class="ti ti-list"></i></span>
                        <span class="hide-menu">Semester Management</span>
                    </a>
                </li>
            @endif
             @if(RoleHelper::hasPermission($role, 'timetable'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'timetable.show' ? 'active' : '' }}" href="{{ route('timetable.show') }}">
                        <span><i class="ti ti-calendar"></i></span>
                        <span class="hide-menu">Timetable</span>
                    </a>
                </li>
            @endif

            {{--  🔹FINANCIAL  --}}
            @if(
                RoleHelper::hasPermission($role, 'payment.dashboard') ||
                RoleHelper::hasPermission($role, 'payment.discounts') ||
                RoleHelper::hasPermission($role, 'payment.plan') ||
                RoleHelper::hasPermission($role, 'payment.plan.index') ||
                RoleHelper::hasPermission($role, 'payment') ||
                RoleHelper::hasPermission($role, 'misc.payment') ||
                RoleHelper::hasPermission($role, 'late.payment') ||
                RoleHelper::hasPermission($role, 'payment.discount.page') ||
                RoleHelper::hasPermission($role, 'repeat.students.payment')                
            )
                <li class="nav-small-cap">
                    <span class="nav-small-cap-text">FINANCIAL</span>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'payment.dashboard'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('payment.summary') ? 'active' : '' }}"href="{{ route('payment.summary') }}">
                        <span><i class="ti ti-chart-pie"></i></span>
                        <span class="hide-menu">Payment Dashboard</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'payment.discounts'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('payment.discount.page') ? 'active' : '' }}" href="{{ route('payment.discount.page') }}">
                        <span><i class="ti ti-discount"></i></span>
                        <span class="hide-menu">Create Discounts</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'payment.plan'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('payment.plan.index') || request()->routeIs('payment.plan.edit') ? 'active' : '' }}" href="{{ route('payment.plan.index') }}">
                        <span><i class="ti ti-cash"></i></span>
                        <span class="hide-menu">Existing Payment Plans</span>
                    </a>
                </li>
            @endif

            {{-- Divider (light) --}}
            @if(
                RoleHelper::hasPermission($role, 'payment.dashboard') ||
                RoleHelper::hasPermission($role, 'payment.discounts') ||
                RoleHelper::hasPermission($role, 'payment.plan') ||
                RoleHelper::hasPermission($role, 'payment.plan.index') ||
                RoleHelper::hasPermission($role, 'payment') ||
                RoleHelper::hasPermission($role, 'misc.payment') ||
                RoleHelper::hasPermission($role, 'late.payment') ||
                RoleHelper::hasPermission($role, 'payment.discount.page') ||
                RoleHelper::hasPermission($role, 'repeat.students.payment')                
            )
            <li><hr class="my-2 border-gray-200 opacity-30"></li>
            @endif

            @if(RoleHelper::hasPermission($role, 'payment.plan.index'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('payment.plan') ? 'active' : '' }}" href="{{ route('payment.plan') }}">
                        <span><i class="ti ti-plus"></i></span>
                        <span class="hide-menu">Intake Payment Plan</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'payment'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('payment.index') ? 'active' : '' }}" href="{{ route('payment.index') }}">
                        <span><i class="ti ti-credit-card"></i></span>
                        <span class="hide-menu">Student Payment Plan</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'misc.payment'))        
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('misc.payment.index') ? 'active' : '' }}" href="{{ route('misc.payment.index') }}">
                        <span><i class="ti ti-wallet"></i></span>
                        <span class="hide-menu">Miscellaneous Payment</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'late.payment'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('late.payment.index') ? 'active' : '' }}" href="{{ route('late.payment.index') }}">
                        <span><i class="ti ti-clock"></i></span>
                        <span class="hide-menu">Late Payment</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'payment.showDownloadPage'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ request()->routeIs('payment.showDownloadPage') ? 'active' : '' }}"
                    href="{{ route('payment.showDownloadPage') }}">
                        <span><i class="ti ti-file-download"></i></span>
                        <span class="hide-menu">Payment Statements</span>
                    </a>
                </li>
            @endif
            @if(RoleHelper::hasPermission($role, 'repeat.students.payment'))
                <li class="sidebar-item">
                    <a class="sidebar-link {{ Route::currentRouteName() == 'repeat.payment.index' ? 'active' : '' }}" href="{{ route('repeat.payment.index') }}">
                        <span><i class="ti ti-currency-dollar"></i></span>
                        <span class="hide-menu">Repeat Payment Plan</span>
                    </a>
                </li>
            @endif

            {{-- APPROVAL --}}
            @if(
                RoleHelper::hasPermission($role, 'special.approval') ||
                RoleHelper::hasPermission($role, 'latefee.approval.index')
                )
            <li class="nav-small-cap">
                <span class="nav-small-cap-text">APPROVALS</span>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link {{ Route::currentRouteName() == 'special.approval.list' ? 'active' : '' }}" href="{{ route('special.approval.list') }}">
                    <span><i class="ti ti-check"></i></span>
                    <span class="hide-menu">Special Approval</span>
                </a>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link {{ request()->routeIs('latefee.approval.index') ? 'active' : '' }}" href="{{ route('latefee.approval.index') }}">
                    <span><i class="ti ti-currency-dollar"></i></span>
                    <span class="hide-menu">Late Fee Approval</span>
                </a>
            </li>
            @endif

            {{-- AUDIT --}}
            @if(RoleHelper::hasPermission($role, 'audit.log') || RoleHelper::hasAnyRole($role, ['Developer']))
            <li class="nav-small-cap">
                <span class="nav-small-cap-text">AUDIT</span>
            </li>
            <li class="sidebar-item">
                <a class="sidebar-link {{ request()->routeIs('audit.log') ? 'active' : '' }}" href="{{ route('audit.log') }}">
                    <span><i class="ti ti-clipboard-list"></i></span>
                    <span class="hide-menu">Audit Log</span>
                </a>
            </li>
            @endif

            {{-- Footer --}}
            <hr>
            <div class="px-3 pb-3">
                <div class="bg-light rounded p-3 d-flex flex-column gap-2 align-items-center">
                    <a href="{{ route('user.profile') }}" class="btn w-100" style="background-color: #6c8cff; color: #fff; font-weight: 500;">My Profile</a>
                    <a href="{{ route('logout') }}" class="btn w-100" style="background-color: #ff8c7a; color: #fff; font-weight: 500;">Logout</a>
                </div>
            </div>

            {{-- 🌐 Team Nebula IT --}}
            <li id="teamNebulaLink" class="text-center mb-3" style="opacity: 0.8; font-size: 13px;">
                <a href="{{ route('team.phase.index') }}"
                class="text-decoration-none d-inline-block py-1 px-2 rounded
                        {{ Route::currentRouteName() == 'team.phase.index'
                                ? 'bg-light text-primary fw-semibold shadow-sm' 
                                : 'text-muted' }}"
                style="transition: all 0.3s;">
                    © Team Nebula IT
                </a>
            </li>

            {{-- 🔽 Auto-scroll script when active --}}
            @if(Route::currentRouteName() == 'team.phase.index')
                <script nonce="{{ $cspNonce }}">
                    document.addEventListener('DOMContentLoaded', function() {
                        const link = document.getElementById('teamNebulaLink');
                        if (link) {
                            // Find the SimpleBar content element and scroll to the footer link
                            const sidebar = document.querySelector('.scroll-sidebar [data-simplebar=""]') || document.querySelector('.scroll-sidebar');
                            link.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        }
                    });
                </script>
            @endif

        </ul>
    </nav>
</div>
