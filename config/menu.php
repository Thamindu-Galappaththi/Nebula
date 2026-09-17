<?php

return [
    'sections' => [
        [
            'title' => 'HOME',
            'items' => [
                ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'ti ti-layout-dashboard', 'permission' => 'dashboard'],
            ],
        ],

        [
            'title' => 'USER MANAGEMENT',
            'items' => [
                ['label' => 'Create User', 'route' => 'create.user', 'icon' => 'ti ti-user', 'roles' => ['Program Administrator (level 01)', 'Developer']],
                ['label' => 'User Management', 'route' => 'dgm.user.management', 'icon' => 'ti ti-users', 'permission' => 'user.management'],
            ],
        ],

        [
            'title' => 'STUDENT MANAGEMENT',
            'items' => [
                ['label' => 'Student Registration', 'route' => 'student.registration', 'icon' => 'ti ti-user', 'permission' => 'student.registration'],
                ['label' => 'Other Information', 'route' => 'student.other.information', 'icon' => 'ti ti-layout', 'permission' => 'student.other.information'],
                ['label' => 'Student Lists', 'route' => 'student_management.list', 'icon' => 'ti ti-menu', 'permission' => 'student.list'],
                ['label' => 'All Students View', 'route' => 'students.view', 'icon' => 'ti ti-users'],
                ['label' => 'Badges Generation', 'route' => 'badges.generate', 'icon' => 'ti ti-id-badge', 'permission' => 'badges.generate'],
                ['label' => 'Student Profile', 'route' => 'student.profile', 'icon' => 'ti ti-id', 'permission' => 'student.profile', 'is_profile' => true],
                ['label' => 'Termination Tracking', 'route' => 'termination.tracking', 'icon' => 'ti ti-user-exclamation', 'permission' => 'termination.tracking'],
            ],
        ],

        [
            'title' => 'REGISTRATIONS',
            'items' => [
                ['label' => 'Course Registration', 'route' => 'course.registration', 'icon' => 'ti ti-book', 'permission' => 'course.registration'],
                ['label' => 'Eligibility Registration', 'route' => 'eligibility.registration', 'icon' => 'ti ti-checks', 'permission' => 'eligibility.registration'],
                ['label' => 'Semester Registration', 'route' => 'semester.registration', 'icon' => 'ti ti-calendar-stats', 'permission' => 'semester.registration'],
                ['label' => 'Module Management', 'route' => 'module.management', 'icon' => 'ti ti-briefcase', 'permission' => 'module.management'],
                ['label' => 'UH Index Numbers', 'route' => 'uh.index.page', 'icon' => 'ti ti-id-badge', 'permission' => 'uh.index.page'],
                ['label' => 'Course Change', 'route' => 'course.change.index', 'icon' => 'ti ti-repeat', 'permission' => 'course.change.index'],
            ],
        ],

        [
            'title' => 'EXAMS & RESULTS',
            'items' => [
                ['label' => 'Add Exam Result', 'route' => 'student.exam.result.management', 'icon' => 'ti ti-file', 'permission' => 'student.exam.result.management'],
                ['label' => 'View & Edit Results', 'route' => 'exam.results.view.edit', 'icon' => 'ti ti-edit', 'permission' => 'exam.results.view.edit'],
                ['label' => 'Repeat Students', 'route' => 'repeat.students.management', 'icon' => 'ti ti-refresh', 'permission' => 'repeat.students.management'],
            ],
        ],

        [
            'title' => 'ATTENDANCE',
            'items' => [
                ['label' => 'Attendance', 'route' => 'attendance', 'icon' => 'ti ti-id', 'permission' => 'attendance'],
                ['label' => 'Overall Attendance', 'route' => 'overall.attendance', 'icon' => 'ti ti-id', 'permission' => 'overall.attendance'],
            ],
        ],

        [
            'title' => 'STUDENT CLEARANCE',
            'items' => [
                ['label' => 'All Clearance', 'route' => 'all.clearance.management', 'icon' => 'ti ti-clipboard', 'permission' => 'all.clearance.management'],
                ['label' => 'Hostel Clearance', 'route' => 'hostel.clearance.form.management', 'icon' => 'ti ti-note', 'permission' => 'hostel.clearance.form.management'],
                ['label' => 'Library Clearance', 'route' => 'library.clearance', 'icon' => 'ti ti-clipboard', 'permission' => 'library.clearance'],
                ['label' => 'Project Clearance', 'route' => 'project.clearance.management', 'icon' => 'ti ti-briefcase', 'permission' => 'project.clearance.management'],
            ],
        ],

        [
            'title' => 'ACADEMIC MANAGEMENT',
            'items' => [
                ['label' => 'Module Creation', 'route' => 'module.creation', 'icon' => 'ti ti-plus', 'permission' => 'module.creation'],
                ['label' => 'Module Management', 'route' => 'module.management', 'icon' => 'ti ti-briefcase', 'permission' => 'module.management'],
                ['label' => 'Course Management', 'route' => 'course.management', 'icon' => 'ti ti-notebook', 'permission' => 'course.management'],
                ['label' => 'Create Intake', 'route' => 'intake.create', 'icon' => 'ti ti-pencil', 'permission' => 'intake.create'],
                ['label' => 'Semester Creation', 'route' => 'semesters.create', 'icon' => 'ti ti-calendar', 'permission' => 'semesters.create'],
                ['label' => 'Semester Management', 'route' => 'semesters.index', 'icon' => 'ti ti-list', 'permission' => 'semesters.create'],
                ['label' => 'Timetable', 'route' => 'timetable.show', 'icon' => 'ti ti-calendar', 'permission' => 'timetable'],
            ],
        ],

        [
            'title' => 'FINANCIAL',
            'items' => [
                ['label' => 'Create Payment Plans', 'route' => 'payment.plan.index', 'icon' => 'ti ti-cash', 'permission' => 'payment.plan'],
                ['label' => 'Payments', 'route' => 'payment.index', 'icon' => 'ti ti-credit-card', 'permission' => 'payment'],
                ['label' => 'Payment Discount', 'route' => 'payment.discount.page', 'icon' => 'ti ti-discount', 'permission' => 'payment.discounts'],
                ['label' => 'Late Payment', 'route' => 'late.payment.index', 'icon' => 'ti ti-clock', 'permission' => 'late.payment'],
                ['label' => 'Payment Dashboard', 'route' => 'payment.summary', 'icon' => 'ti ti-chart-pie', 'permission' => 'payment.dashboard'],
                ['label' => 'Misc Payments', 'route' => 'misc.payment.index', 'icon' => 'ti ti-wallet', 'permission' => 'misc.payment'],
            ],
        ],

        [
            'title' => 'APPROVALS',
            'items' => [
                ['label' => 'Special Approval', 'route' => 'special.approval.list', 'icon' => 'ti ti-check', 'permission' => 'special.approval'],
                ['label' => 'Late Fee Approval', 'route' => 'latefee.approval.index', 'icon' => 'ti ti-currency-dollar', 'permission' => 'latefee.approval.index'],
            ],
        ],

        [
            'title' => 'AUDIT',
            'items' => [
                ['label' => 'Audit Log', 'route' => 'audit.log', 'icon' => 'ti ti-clipboard-list', 'permission' => 'audit.log', 'roles' => ['Developer']],
            ],
        ],
    ],
];
