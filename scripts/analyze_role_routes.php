<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Helpers\RoleHelper;

// Extract all route names from RoleHelper permissions
$allPermissionsInRoleHelper = [];
foreach (RoleHelper::PERMISSIONS as $role => $permissions) {
    foreach ($permissions as $permission) {
        $allPermissionsInRoleHelper[$permission] = true;
    }
}

// List of route names found in web.php
$routesInWebPhp = [
    // Authentication & Basic
    'login',
    'login.authenticate',
    'logout',
    'dashboard',
    
    // User Management
    'user.profile',
    'create.user',
    'user.management',
    'dgm.user.management',
    
    // Student Management
    'student.registration',
    'student_management.registration',
    'student_management.other.information',
    'student.other.information',
    'student.list',
    'student_management.list',
    'student.view',
    'student_management.view',
    'student.profile',
    'student_management.profile',
    
    // Registrations
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
    'course.change',
    'course.change.index',
    
    // Exams & Results
    'exam.results',
    'student.exam.result.management',
    'exam.results.view.edit',
    'repeat.students.management',
    'repeat.students.payment',
    'repeat.payment.index',
    
    // Attendance
    'attendance',
    'overall.attendance',
    
    // Clearance
    'all.clearance.management',
    'student.clearance.form.management',
    'library.clearance',
    'hostel.clearance',
    'hostel.clearance.form.management',
    'project.clearance.management',
    'payment.clearance',
    
    // Courses & Modules
    'module.creation',
    'course.management',
    'intake.create',
    'semesters.create',
    'semesters.index',
    'timetable',
    'timetable.show',
    
    // Financial
    'payment',
    'payment.index',
    'late.payment',
    'late.payment.index',
    'payment.discounts',
    'payment.discount.page',
    'payment.plan',
    'payment.plan.index',
    'payment.plan.edit',
    'payment.dashboard',
    'payment.summary',
    'misc.payment',
    'misc.payment.index',
    'payment.showDownloadPage',
    
    // Approvals
    'special.approval',
    'special.approval.list',
    'latefee.approval',
    'latefee.approval.index',
    
    // Reporting & Data
    'reporting.dashboard',
    'data.export.import',
    
    // Dashboards
    'project.tutor.dashboard',
    'dgmdashboard',
    'student.counselor.dashboard',
    'marketing.manager.dashboard',
    'hostel.manager.dashboard',
    'admin.l1.dashboard',
    'program.admin.l2.dashboard',
    'bursar.dashboard',
    'librarian.dashboard',
    'developer.dashboard',
    
    // Badges
    'badges.generate',
    'course.badge',
];

// Check for missing routes in RoleHelper
$missingInRoleHelper = [];
foreach ($routesInWebPhp as $route) {
    if (!isset($allPermissionsInRoleHelper[$route])) {
        $missingInRoleHelper[] = $route;
    }
}

// Check for routes in RoleHelper that might not exist
$possiblyUnusedInRoleHelper = [];
foreach (array_keys($allPermissionsInRoleHelper) as $permission) {
    if (!in_array($permission, $routesInWebPhp)) {
        $possiblyUnusedInRoleHelper[] = $permission;
    }
}

// Generate report
echo "\n===========================================\n";
echo "ROLE HELPER ROUTE ANALYSIS REPORT\n";
echo "===========================================\n\n";

echo "Total unique permissions in RoleHelper: " . count($allPermissionsInRoleHelper) . "\n";
echo "Total routes checked from web.php: " . count($routesInWebPhp) . "\n\n";

echo "===========================================\n";
echo "ROUTES IN WEB.PHP NOT IN ROLEHELPER\n";
echo "===========================================\n";
if (count($missingInRoleHelper) > 0) {
    foreach ($missingInRoleHelper as $route) {
        echo "  ❌ $route\n";
    }
} else {
    echo "  ✅ All checked routes are in RoleHelper\n";
}

echo "\n===========================================\n";
echo "PERMISSIONS IN ROLEHELPER (sample check)\n";
echo "===========================================\n";
if (count($possiblyUnusedInRoleHelper) > 0) {
    echo "Note: These permissions may be valid, or route names may differ:\n";
    foreach (array_slice($possiblyUnusedInRoleHelper, 0, 20) as $permission) {
        echo "  ℹ️  $permission\n";
    }
    if (count($possiblyUnusedInRoleHelper) > 20) {
        echo "  ... and " . (count($possiblyUnusedInRoleHelper) - 20) . " more\n";
    }
}

echo "\n===========================================\n";
echo "ROLE PERMISSION SUMMARY\n";
echo "===========================================\n";
foreach (RoleHelper::ROLES as $roleKey => $roleName) {
    $permCount = count(RoleHelper::PERMISSIONS[$roleKey] ?? []);
    echo sprintf("%-45s: %3d permissions\n", $roleName, $permCount);
}

echo "\n===========================================\n";
echo "ANALYSIS COMPLETE\n";
echo "===========================================\n";
