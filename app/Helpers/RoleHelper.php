<?php

namespace App\Helpers;

class RoleHelper
{
    // Define all available roles
    const ROLES = [
        'DGM' => 'Deputy General Manager',
        'Program Administrator (level 01)' => 'Program Administrator (level 01)',
        'Program Administrator (level 02)' => 'Program Administrator (level 02)',
        'Program Administrator (level 02) Trainee' => 'Program Administrator (level 02) Trainee',
        'Student Counselor' => 'Student Counselor',
        'Student Counselor Trainee' => 'Student Counselor Trainee',
        'Librarian' => 'Librarian',
        'Hostel Manager' => 'Hostel Manager',
        'Bursar' => 'Bursar',
        'Project Tutor' => 'Project Tutor',
        'Marketing Manager' => 'Marketing Manager',
        'Developer' => 'Developer',
    ];

    // Higher priority roles should appear earlier in this list.
    const ROLE_PRIORITY = [
        'Developer',
        'DGM',
        'Program Administrator (level 01)',
        'Program Administrator (level 02)',
        'Program Administrator (level 02) Trainee',
        'Student Counselor',
        'Student Counselor Trainee',
        'Marketing Manager',
        'Bursar',
        'Project Tutor',
        'Librarian',
        'Hostel Manager',
    ];

    // Define role permissions
    const PERMISSIONS = [
        'DGM' => [

            // HOME
            'dashboard',

            // SPECIAL APPROVALS
            'special.approval',
            'latefee.approval',
            'latefee.approval.index',

            // STUDENT MANAGEMENT
            'student.list',
            'student.profile',
            'student.view',
            'termination.tracking',

            // FINANCIAL            
            'payment.dashboard',
            
            // FOOTER
            'user.profile',
            'team.phase.index'
        ],

        'Program Administrator (level 01)' => [

            // HOME
            'dashboard',

            // USER MANAGEMENT
            'create.user',
            'user.management',

            // STUDENT MANAGEMENT
            'student.registration',
            'course.badge',
            'student.other.information',
            'student.list',
            'student.profile',
            'student.view',
            'termination.tracking',

            // REGISTRATIONS
            'course.registration',
            'eligibility.registration',
            'semester.registration',
            'module.management',
            'uh.index.page',
            'course.change',

            // EXAMS & RESULTS
            'exam.results',
            'student.exam.result.management',
            'exam.results.view.edit',
            'repeat.students.management',

            // ATTENDANCE
            'attendance',
            'overall.attendance',

            // CLEARANCE
            'all.clearance.management',

            // COURSES & MODULES
            'module.creation',
            'course.management',
            'intake.create',
            'semesters.create',
            'semester.registration',
            'timetable',

            // FINANCIAL
            'payment.dashboard',

            // FOOTER
            'user.profile',
            'team.phase.index'
        ],

        'Program Administrator (level 02)' => [

            // HOME
            'dashboard',

            // STUDENT MANAGEMENT
            'student.registration',
            'student.other.information',
            'student.profile',
            'student.list',
            'termination.tracking',
            
            // REGISTRATIONS
            'semester.registration',
            'semester.registration.store',
            'semester.registration.getCoursesByLocation',
            'semester.registration.getOngoingIntakes',
            'semester.registration.getOpenSemesters',
            'semester.registration.getEligibleStudents',
            'semester.registration.getAllSemestersForCourse',
            'semester.registration.updateStatus',
            'semester.registration.checkClearances',
            'semester.registration.approveReenroll',
            'semester.registration.rejectReenroll',
            'semester.registration.approveReRegister',
            'semester.registration.rejectReRegister',
            'module.management',
            'course.change',
            'uh.index.page',
            
            // EXAMS & RESULTS
            'exam.results',
            'student.exam.result.management',
            'exam.results.view.edit',
            'repeat.students.management',

            // ATTENDANCE
            'attendance',
            'overall.attendance',

            // COURSES & MODULES
            'module.creation',
            'course.management',
            'semesters.create',
            'intake.create',
            'timetable',
               
            // FINANCIAL       
            'payment.dashboard',

            // CLEARANCE
            'all.clearance.management',

            // FOOTER
            'user.profile',
            'team.phase.index'
        ],

        'Program Administrator (level 02) Trainee' => [

            // HOME
            'dashboard',

            // STUDENT MANAGEMENT
            'student.other.information',
            'student.list',
            'student.profile',

            // REGISTRATIONS
            'semester.registration',

            //exam results
            'exam.results',
            'student.exam.result.management',
            'exam.results.view.edit',
            'repeat.students.management',

            // ATTENDANCE
            'attendance',
            'overall.attendance',

            // COURSES & MODULES
            'module.creation',
            'course.management',
            'semesters.create',
            'intake.create',
            'semester.management',
            'timetable',

            // FOOTER
            'user.profile',
            'team.phase.index'
        ],

        'Student Counselor' => [
            
            // HOME
            'dashboard',

            // STUDENT MANAGEMENT
            'student.registration',
            'student.list',
            'student.view',
            'student.profile',

            // REGISTRATIONS
            'course.registration',
            'eligibility.registration',
            'course.change',            

            // FINANCIAL
            'payment',
            'late.payment',
            'payment.discounts',
            'payment.plan',
            'payment.plan.edit',
            'payment.plan.index',
            'payment.dashboard',
            'payment.summary',
            'misc.payment',
            'payment.showDownloadPage',
            'payment.discount.page',

            // FOOTER
            'user.profile',
            'team.phase.index'
        ],

        'Student Counselor Trainee' => [
            
            // HOME
            'dashboard',

            // STUDENT MANAGEMENT
            'student.registration',
            'student.list',
            'student.view',

            // REGISTRATIONS
            'course.registration',
            'eligibility.registration',
            'course.change',            

            // FINANCIAL
            'payment',
            'late.payment',
            'payment.discounts',
            'payment.plan',
            'payment.plan.edit',
            'payment.plan.index',
            'payment.dashboard',
            'payment.summary',
            'misc.payment',
            'payment.showDownloadPage',
            'payment.discount.page',

            // FOOTER
            'user.profile',
            'team.phase.index'
        ],

        'Marketing Manager' => [

            // HOME
            'dashboard',
            
            // STUDENT MANAGEMENT
            'student.list',
            'student.view',

            // REGISTRATIONS
            'course.change',

            // FINANCIAL
            'payment.plan',
            'create.payment.plan',
            'payment.summary',
            'payment.dashboard',

            // FOOTER
            'user.profile',
            'team.phase.index'
        ],

        'Librarian' => [

            // HOME
            'dashboard',
            
            // STUDENT MANAGEMENT
            'student.list',

            // CLEARANCE    
            'library.clearance',
            'student.clearance.form.management',

            // FOOTER
            'user.profile',
            'team.phase.index'
        ],

        'Hostel Manager' => [

            // HOME
            'dashboard',
            
            // CLEARANCE
            'hostel.clearance.form.management',
            'hostel.clearance',

            // FOOTER
            'user.profile',
            'team.phase.index'
        ],

        'Bursar' => [

            // HOME
            'dashboard',

            // STUDENT MANAGEMENT
            'student.list',

            // CLEARANCE
            'payment.clearance',

            // FINANCIAL
            'payment.plan',
            'payment.plan.index',
            'payment',
            'late.payment',
            'payment.discounts',
            'payment.dashboard',
            'payment.summary',
            'misc.payment',
            'payment.showDownloadPage',
            'payment.discount.page',

            // FOOTER
            'user.profile',
            'team.phase.index'
        ],

        'Project Tutor' => [

            // HOME
            'dashboard',
            'project.tutor.dashboard',

            // STUDENT MANAGEMENT
            'student.list',

            // CLEARANCE
            'project.clearance.management',

            // FOOTER
            'user.profile',
            'team.phase.index'
        ],

        'Developer' => [

            // HOME
            'dashboard',

            // USER MANAGEMENT
            'create.user',
            'user.management',

            // STUDENT MANAGEMENT
            'student.registration',
            'student.other.information',
            'student.list',
            'student.view',
            'student.profile',         
            'termination.tracking',
            'course.change',
            
            // REGISTRATIONS
            'course.registration',
            'eligibility.registration',
            'course.badge',
            'semester.registration',
            'module.management',
            'uh.index.page',
            'uh.index.save',
            'uh.index.courses',
            'uh.index.intakes',
            'uh.index.students',
            'uh.index.terminate',
            
            // EXAMS & RESULTS
            'exam.results',            
            'repeat.students.management',
            'exam.results.view.edit',
            'repeat.students.payment',

            // ATTENDANCE
            'attendance',
            'overall.attendance',

            // CLEARANCE
            'all.clearance.management',
            'student.clearance.form.management',
            'library.clearance',
            'hostel.clearance.form.management',
            'project.clearance.management',
            'payment.clearance',
            
            // COURSES & MODULES
            'module.creation',
            'course.management',
            'intake.create',
            'semesters.create',
            'semesters.index',
            'timetable', 

            // FINANCIAL MANAGEMENT
            'payment',
            'late.payment',
            'payment.discounts',
            'payment.plan',
            'payment.plan.edit',
            'payment.plan.index',
            'payment.dashboard',
            'payment.summary',
            'misc.payment',
            'payment.showDownloadPage',
            'payment.discount.page',
            
            // APPROVALS
            'special.approval',
            'latefee.approval',
            'latefee.approval.index',
            'reporting.dashboard',       
            'data.export.import',

            // AUDIT
            'audit.log',

            // PROJECT TUTOR
            'project.tutor.dashboard',

            // FOOTER
            'user.profile',
            'team.phase.index'

        ],
    ];

    public static function normalizeRoles($userRoles): array
    {
        if (is_object($userRoles) && method_exists($userRoles, 'getRoleList')) {
            $userRoles = $userRoles->getRoleList();
        }

        $roles = [];

        if (is_array($userRoles)) {
            $roles = $userRoles;
        } elseif (is_string($userRoles)) {
            $raw = trim($userRoles);

            if ($raw === '') {
                return [];
            }

            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $roles = $decoded;
            } elseif (strpos($raw, ',') !== false) {
                $roles = array_map('trim', explode(',', $raw));
            } else {
                $roles = [$raw];
            }
        }

        $roles = array_values(array_filter(array_map(function ($role) {
            return is_string($role) ? trim($role) : '';
        }, $roles)));

        return array_values(array_unique($roles));
    }

    public static function resolvePrimaryRole($userRoles): ?string
    {
        $roles = self::normalizeRoles($userRoles);

        if (empty($roles)) {
            return null;
        }

        foreach (self::ROLE_PRIORITY as $role) {
            if (in_array($role, $roles, true)) {
                return $role;
            }
        }

        return $roles[0];
    }

    public static function hasAnyRole($userRoles, array $requiredRoles): bool
    {
        if (empty($requiredRoles)) {
            return true;
        }

        $assignedRoles = self::normalizeRoles($userRoles);
        $requiredRoles = self::normalizeRoles($requiredRoles);

        if (empty($assignedRoles) || empty($requiredRoles)) {
            return false;
        }

        return count(array_intersect($assignedRoles, $requiredRoles)) > 0;
    }

    // Check if a user has permission to access a specific route
    public static function hasPermission($userRoles, $routeName)
    {
        $roles = self::normalizeRoles($userRoles);

        foreach ($roles as $role) {
            if (isset(self::PERMISSIONS[$role]) && in_array($routeName, self::PERMISSIONS[$role], true)) {
                return true;
            }
        }

        return false;
    }

    // Get all permissions for one or many roles
    public static function getRolePermissions($roles)
    {
        $normalizedRoles = self::normalizeRoles($roles);
        $permissions = [];

        foreach ($normalizedRoles as $role) {
            if (isset(self::PERMISSIONS[$role])) {
                $permissions = array_merge($permissions, self::PERMISSIONS[$role]);
            }
        }

        return array_values(array_unique($permissions));
    }

    // Get all available roles
    public static function getRoles()
    {
        return self::ROLES;
    }

    // Check if user can access student management features
    public static function canAccessStudentManagement($userRole)
    {
        $studentManagementRoutes = [
            'student.registration',
            'course.registration',
            'eligibility.registration',
            'student.other.information',
            'student.exam.result.management',
            'student.list'
        ];

        foreach ($studentManagementRoutes as $route) {
            if (self::hasPermission($userRole, $route)) {
                return true;
            }
        }

        return false;
    }

    // Check if user can access clearance features
    public static function canAccessClearance($userRole)
    {
        $clearanceRoutes = [
            'all.clearance.management',
            'student.clearance.form.management',
            'hostel.clearance.form.management',
            'project.clearance.management'
        ];

        foreach ($clearanceRoutes as $route) {
            if (self::hasPermission($userRole, $route)) {
                return true;
            }
        }

        return false;
    }

    // Check if user can access academic management features
    public static function canAccessAcademicManagement($userRole)
    {
        $academicRoutes = [
            'module.creation',
            'course.management',
            'intake.create',
            'semesters.create',
            'module.management',
            'timetable'
        ];

        foreach ($academicRoutes as $route) {
            if (self::hasPermission($userRole, $route)) {
                return true;
            }
        }

        return false;
    }

    // Check if user can access attendance features
    public static function canAccessAttendance($userRole)
    {
        return self::hasPermission($userRole, 'attendance') || 
               self::hasPermission($userRole, 'overall.attendance');
    }
} 