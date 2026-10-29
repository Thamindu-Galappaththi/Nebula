<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Course;
use App\Models\Student;
use App\Models\Intake;
use App\Models\CourseRegistration;
use App\Models\StudentOtherInformation;
use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\StudentClearance;
use App\Models\Timetable;
use App\Models\ModuleManagement;
use Carbon\Carbon;
use App\Helpers\RoleHelper;

class DashboardController extends Controller
{
    public function showDashboard()
    {
        $user = Auth::user();
        $userRoles = $user->getRoleList();
        $userRole = RoleHelper::resolvePrimaryRole($userRoles) ?? $user->user_role;
        
        // Get role-specific welcome message
        $welcomeMessage = $this->getWelcomeMessage($userRole);
        
        // Get role-specific permissions
        $permissions = RoleHelper::getRolePermissions($userRoles);
        
        // Get available features for this role
        $availableFeatures = $this->getAvailableFeatures($userRole);
        
        // Redirect to role-specific dashboard routes
        if ($userRole === 'DGM') {
            return redirect()->route('dgmdashboard');
        }

        if ($userRole === 'Program Administrator (level 01)') {
            return redirect()->route('admin.l1.dashboard');
        }

        if ($userRole === 'Program Administrator (level 02)') {
            return redirect()->route('program.admin.l2.dashboard');
        }

        if ($userRole === 'Program Administrator (level 02) Trainee') {
            return redirect()->route('program.admin.l2.trainee.dashboard');
        }

        if ($userRole === 'Student Counselor') {
            return redirect()->route('student.counselor.dashboard');
        }

        if ($userRole === 'Student Counselor Trainee') {
            return redirect()->route('student.counselor.trainee.dashboard');
        }

        if ($userRole === 'Project Tutor') {
            return redirect()->route('project.tutor.dashboard');
        }

        if ($userRole === 'Bursar') {
            return redirect()->route('bursar.dashboard');
        }

        if ($userRole === 'Librarian') {
            return redirect()->route('librarian.dashboard');
        }

        if ($userRole === 'Marketing Manager') {
            return redirect()->route('marketing.manager.dashboard');
        }

        if ($userRole === 'Hostel Manager') {
            return redirect()->route('hostel.manager.dashboard');
        }

        if ($userRole === 'Developer') {
            return redirect()->route('developer.dashboard');
        }

        if ($userRole === 'Payment Analyst') {
            return redirect()->route('payment.summary');
        }
        
        // Default fallback
        return view('dashboard', compact('user', 'welcomeMessage', 'permissions', 'availableFeatures'));
    }
    
    private function getWelcomeMessage($role)
    {
        $messages = [
            'DGM' => 'Welcome Deputy General Manager! You have access to special approval features.',
            'Program Administrator (level 01)' => 'Welcome Program Administrator (level 01)! You can manage users, modules, courses, attendance, and clearances.',
            'Program Administrator (level 02)' => 'Welcome Program Administrator (level 02)! You can manage intakes, attendance, timetables, semesters, and exam results.',
            'Program Administrator (level 02) Trainee' => 'Welcome Program Administrator (level 02) Trainee! You can manage intakes, attendance, timetables, semesters, and exam results.',
            'Student Counselor' => 'Welcome Student Counselor! Monitor student intake activity and marketing channel effectiveness.',
            'Student Counselor Trainee' => 'Welcome Student Counselor Trainee! Monitor student intake activity and marketing channel effectiveness.',
            'Librarian' => 'Welcome Librarian! You can manage library clearance processes.',
            'Hostel Manager' => 'Welcome Hostel Manager! You can manage hostel clearance processes.',
            'Bursar' => 'Welcome Bursar! You can manage financial and student records.',
            'Project Tutor' => 'Welcome Project Tutor! You can manage project clearance and attendance.',
            'Marketing Manager' => 'Welcome Marketing Manager! Track campaign performance and student acquisition metrics.',
            'Payment Analyst' => 'Welcome Payment Analyst! You can view the Payment Analytics Dashboard.',
            'Developer' => 'Welcome Developer! You have full system access to all features and functionalities.'
        ];
        
        return $messages[$role] ?? 'Welcome to the Nebula Institute Management System!';
    }
    
    private function getAvailableFeatures($role)
    {
        $features = [
            'DGM' => [
                'Student Management' => ['Student Registration', 'Course Registration', 'Eligibility & Registration', 'Student Information', 'Exam Results', 'Student Lists'],
                'Clearance Management' => ['All Clearance', 'Library Clearance', 'Hostel Clearance', 'Project Clearance'],
                'Academic Management' => ['Module Creation', 'Course Management', 'Intake Creation', 'Semester Creation', 'Module Management', 'Timetable'],
                'System Management' => ['User Management', 'Special Approvals', 'Attendance Management']
            ],
            'Manager' => [
                'Student Management' => ['Student Registration', 'Course Registration', 'Eligibility & Registration', 'Student Information', 'Exam Results', 'Student Lists'],
                'Clearance Management' => ['All Clearance', 'Library Clearance', 'Hostel Clearance', 'Project Clearance'],
                'Academic Management' => ['Module Creation', 'Course Management', 'Intake Creation', 'Semester Creation', 'Module Management', 'Timetable'],
                'System Management' => ['User Management', 'Attendance Management']
            ],
            'Program Administrator' => [
                'Student Management' => ['Student Registration', 'Course Registration', 'Eligibility & Registration', 'Student Information', 'Exam Results', 'Student Lists'],
                'Academic Management' => ['Module Creation', 'Course Management', 'Intake Creation', 'Semester Creation', 'Module Management', 'Timetable'],
                'System Management' => ['Attendance Management']
            ],
            'Student Counselor' => [
                'Student Management' => ['Student Registration', 'Course Registration', 'Eligibility & Registration', 'Student Information', 'Student Lists'],
                'Analytics' => ['Registration Monitoring', 'Marketing Channel Analysis', 'Student Intake Tracking']
            ],
            'Marketing Manager' => [
                'Marketing Analytics' => ['Campaign Performance', 'Channel ROI', 'Conversion Metrics', 'Student Acquisition'],
                'Student Overview' => ['Registration Tracking', 'Demographic Insights', 'Enrollment Trends']
            ],
            'Payment Analyst' => [
                'Financial' => ['Payment Analytics Dashboard'],
            ],
            'Librarian' => [
                'Clearance Management' => ['Library Clearance']
            ],
            'Hostel Manager' => [
                'Clearance Management' => ['Hostel Clearance']
            ],
            'Bursar' => [
                'Student Management' => ['Student Registration', 'Course Registration', 'Eligibility & Registration', 'Student Information', 'Exam Results', 'Student Lists'],
                'System Management' => ['Attendance Management']
            ],
            'Project Tutor' => [
                'Clearance Management' => ['Project Clearance'],
                'System Management' => ['Attendance Management']
            ],
            'Developer' => [
                'Student Management' => ['Student Registration', 'Course Registration', 'Eligibility & Registration', 'Student Information', 'Exam Results', 'Student Lists'],
                'Clearance Management' => ['All Clearance', 'Library Clearance', 'Hostel Clearance', 'Project Clearance'],
                'Academic Management' => ['Module Creation', 'Course Management', 'Intake Creation', 'Semester Creation', 'Module Management', 'Timetable'],
                'System Management' => ['User Management', 'Special Approvals', 'Attendance Management', 'File Management', 'Reporting', 'Data Export/Import', 'API Documentation'],
                'Financial Management' => ['Payment Plans', 'Payment Clearance'],
            ]
        ];
        
        return $features[$role] ?? [];
    }

    // API methods for charts
    public function getStudentsPerCourse()
    {
        $studentsPerCourse = CourseRegistration::with('course')
                                              ->selectRaw('course_id, COUNT(*) as count')
                                              ->groupBy('course_id')
                                              ->get()
                                              ->map(function($item) {
                                                  return [
                                                      'course_name' => $item->course->course_name ?? 'Unknown',
                                                      'count' => $item->count
                                                  ];
                                              });
        
        return response()->json($studentsPerCourse);
    }

    public function getCountrySurveyData()
    {
        // This would need to be implemented based on your marketing survey structure
        return response()->json([]);
    }

    public function getDropdownOptions()
    {
        $courses = Course::select('course_id', 'course_name')->get();
        $intakes = Intake::select('intake_id', 'batch as intake_name', 'batch')->get();
        $locations = ['Welisara', 'Moratuwa', 'Peradeniya'];
        
        return response()->json([
            'courses' => $courses,
            'intakes' => $intakes,
            'locations' => $locations
        ]);
    }

    public function getRegistrationData()
    {
        $registrations = CourseRegistration::with(['student', 'course', 'intake'])
                                          ->latest()
                                          ->take(10)
                                          ->get();
        
        return response()->json($registrations);
    }

    public function getCourses()
    {
        $courses = Course::select('course_id', 'course_name')->get();
        return response()->json($courses);
    }

    public function getYearlyRevenue(Request $request)
    {
        $year = (int) ($request->input('year', date('Y')));
        $years = range($year - 4, $year);

        $yearlyData = [];
        foreach ($years as $y) {
            $bulkRevenue = 0;
            if (Schema::hasTable('bulk_revenue_uploads')) {
                $bulkRevenue = (float) DB::table('bulk_revenue_uploads')->where('year', $y)->sum('revenue');
            }
            $paymentRevenue = 0;
            if (Schema::hasTable('payment_details')) {
                $paymentRevenue = (float) DB::table('payment_details')->whereYear('created_at', $y)->sum('amount');
            }

            $yearlyData[] = [
                'year' => $y,
                'revenue' => round($bulkRevenue + $paymentRevenue, 2)
            ];
        }

        return response()->json($yearlyData);
    }

    public function getMonthlyEarnings(Request $request)
    {
        $year = (int) ($request->input('year', date('Y')));
        $monthlyData = [];

        for ($month = 1; $month <= 12; $month++) {
            $bulkRevenue = 0;
            if (Schema::hasTable('bulk_revenue_uploads')) {
                $bulkRevenue = (float) DB::table('bulk_revenue_uploads')
                    ->where('year', $year)
                    ->where('month', $month)
                    ->sum('revenue');
            }
            $paymentRevenue = 0;
            if (Schema::hasTable('payment_details')) {
                $paymentRevenue = (float) DB::table('payment_details')
                    ->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month)
                    ->sum('amount');
            }

            $monthlyData[] = [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->format('M'),
                'earnings' => round($bulkRevenue + $paymentRevenue, 2),
                'revenue' => round($bulkRevenue + $paymentRevenue, 2),
            ];
        }

        return response()->json($monthlyData);
    }

    public function getRevenueByCourse($courseId, Request $request = null)
    {
        $course = Course::find($courseId);
        $courseName = $course?->course_name ?? 'Unknown';

        $totalRevenue = 0;
        if (Schema::hasTable('payment_details')) {
            $totalRevenue = (float) DB::table('payment_details')
                ->join('course_registration', 'payment_details.registration_id', '=', 'course_registration.id')
                ->where('course_registration.course_id', $courseId)
                ->sum('payment_details.amount');
        }

        if ($totalRevenue == 0 && Schema::hasTable('bulk_revenue_uploads')) {
            $totalRevenue = (float) DB::table('bulk_revenue_uploads')
                ->where(function ($q) use ($courseId, $courseName) {
                    $q->where('course', $courseId)
                      ->orWhere('course', $courseName);
                })
                ->sum('revenue');
        }

        return response()->json([
            'course_id' => $courseId,
            'course_name' => $courseName,
            'revenue' => round($totalRevenue, 2),
        ]);
    }

    public function getRevenueData(Request $request)
    {
        $year = (int) ($request->input('year', date('Y')));
        $courses = Course::select('course_id', 'course_name')->get();

        $data = [];
        foreach ($courses as $course) {
            $revenue = 0;
            if (Schema::hasTable('payment_details')) {
                $revenue = (float) DB::table('payment_details')
                    ->join('course_registration', 'payment_details.registration_id', '=', 'course_registration.id')
                    ->where('course_registration.course_id', $course->course_id)
                    ->whereYear('payment_details.created_at', $year)
                    ->sum('payment_details.amount');
            }

            if ($revenue == 0 && Schema::hasTable('bulk_revenue_uploads')) {
                $revenue = (float) DB::table('bulk_revenue_uploads')
                    ->where('year', $year)
                    ->where(function ($q) use ($course) {
                        $q->where('course', $course->course_id)
                          ->orWhere('course', $course->course_name);
                    })
                    ->sum('revenue');
            }

            $data[] = [
                'course_id' => $course->course_id,
                'course_name' => $course->course_name,
                'revenue' => round($revenue, 2),
            ];
        }

        return response()->json($data);
    }
}
