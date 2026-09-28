<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class AdminL1DashboardController extends Controller
{
    /**
     * Show the Level 1 admin dashboard view.
     */
    public function showDashboard()
    {
        return view('dashboards.admin_l1');
    }

    /**
     * Return overview dashboard metrics for the requested period.
     *
     * Available period values: today, week, month, year.
     */
    public function getOverviewMetrics(Request $request)
    {
        // Resolve the date range for the requested period.
        $period = $request->get('period', 'month');
        $dateRange = $this->getDateRange($period);

        $metrics = [
            'total_students' => DB::table('students')->count(),
            'active_students' => DB::table('students')
                ->whereRaw("LOWER(COALESCE(academic_status, '')) = 'active'")
                ->count(),
            'new_students_this_period' => DB::table('students')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),

            'total_registrations' => DB::table('course_registration')->count(),
            'registrations_this_period' => DB::table('course_registration')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'pending_registrations' => $this->pendingRegistrationsQuery()->count(),
            'completed_registrations' => DB::table('course_registration')
                ->whereRaw("LOWER(COALESCE(status, '')) = 'registered'")
                ->count(),

            'pending_clearances' => DB::table('clearance_requests')
                ->whereRaw("LOWER(COALESCE(status, '')) = 'pending'")
                ->count(),
            'clearances_this_period' => DB::table('clearance_requests')
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])->count(),
            'total_courses' => DB::table('courses')->count(),
            'active_intakes' => DB::table('intakes')
                ->where('start_date', '<=', now())
                ->where(function ($q) {
                    $q->whereNull('end_date')->orWhere('end_date', '>=', now());
                })->count(),

            'pending_payments' => DB::table('payment_details')
                ->whereRaw("LOWER(COALESCE(status, '')) IN ('pending', 'overdue', 'unpaid', 'partial')")
                ->count(),
            'total_revenue_this_period' => DB::table('payment_details')
                ->whereRaw("LOWER(COALESCE(status, '')) IN ('paid', 'complete', 'completed')")
                ->whereRaw($this->paymentDateExpression() . ' BETWEEN ? AND ?', [$dateRange['start'], $dateRange['end']])
                ->sum('amount'),

            'attendance_taken_today' => DB::table('attendance')->whereDate('date', today())->count(),
            'attendance_records_this_period' => DB::table('attendance')
                ->whereBetween('date', [
                    $dateRange['start']->toDateString(),
                    $dateRange['end']->toDateString(),
                ])->count(),
            'total_users' => DB::table('users')->count(),
        ];

        return response()->json($metrics);
    }

    /**
     * Return student statistics for dashboard visualizations.
     *
     * Includes academic status counts, institute location counts, and registration trends.
     */
    public function getStudentStats(Request $request)
    {
        $period = $request->get('period', 'month');
        $dateRange = $this->getDateRange($period);

        // Choose grouping and label SQL expressions based on the selected period.
        [$dateKeySql, $labelSql] = match ($period) {
            'today' => ['DATE_FORMAT(created_at, "%Y-%m-%d %H:00:00")', 'DATE_FORMAT(created_at, "%H:00")'],
            'week' => ['DATE(created_at)', 'DATE_FORMAT(created_at, "%a")'],
            'year' => ['DATE_FORMAT(created_at, "%Y-%m-01")', 'DATE_FORMAT(created_at, "%b")'],
            default => ['DATE(created_at)', 'DATE_FORMAT(created_at, "%d %b")'],
        };

        $stats = [
            'by_status' => DB::table('students')
                ->select('academic_status', DB::raw('count(*) as count'))
                ->groupBy('academic_status')->get(),

            'by_location' => DB::table('students')
                ->select('institute_location', DB::raw('count(*) as count'))
                ->groupBy('institute_location')->get(),

            'registration_trend' => DB::table('students')
                ->select(
                    DB::raw("{$dateKeySql} as date_key"),
                    DB::raw("{$labelSql} as month"),
                    DB::raw('count(*) as count')
                )
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->groupBy('date_key', 'month')
                ->orderBy('date_key')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Return course registration statistics for the dashboard.
     *
     * Includes registration status counts and the top courses by registration volume.
     */
    public function getCourseRegistrationStats(Request $request)
    {
        $period = $request->get('period', 'month');
        $dateRange = $this->getDateRange($period);

        $stats = [
            'by_status' => DB::table('course_registration')
                ->select('status', DB::raw('count(*) as count'))
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->groupBy('status')->get(),

            'top_courses' => DB::table('course_registration')
                ->join('courses', 'course_registration.course_id', '=', 'courses.course_id')
                ->select('courses.course_name', DB::raw('count(*) as registrations'))
                ->whereBetween('course_registration.created_at', [$dateRange['start'], $dateRange['end']])
                ->groupBy('courses.course_id', 'courses.course_name')
                ->orderBy('registrations', 'desc')->limit(10)->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Return clearance-related metrics and pending clearance requests.
     */
    public function getClearanceStats()
    {
        $stats = [
            'by_type' => DB::table('clearance_requests')
                ->select('clearance_type', DB::raw('count(*) as count'))
                ->groupBy('clearance_type')->get(),
            
            'pending_list' => DB::table('clearance_requests')
                ->join('students', 'clearance_requests.student_id', '=', 'students.student_id')
                ->leftJoin('courses', 'clearance_requests.course_id', '=', 'courses.course_id')
                ->select(
                    'clearance_requests.id',
                    'clearance_requests.clearance_type',
                    'clearance_requests.status',
                    'clearance_requests.created_at',
                    'students.name_with_initials as student_name',
                    'courses.course_name'
                )
                ->whereRaw("LOWER(COALESCE(clearance_requests.status, '')) = 'pending'")
                ->orderBy('clearance_requests.created_at', 'desc')->limit(20)->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Return financial dashboard data, including revenue summary and overdue payments.
     */
    public function getFinancialStats(Request $request)
    {
        $dateRange = $this->getDateRange($request->get('period', 'month'));

        $dateExpr = $this->paymentDateExpression();

        $latePayments = [];
        try {
            $latePayments = DB::table('payment_installments')
                ->join('student_payment_plans', 'payment_installments.payment_plan_id', '=', 'student_payment_plans.id')
                ->join('students', 'student_payment_plans.student_id', '=', 'students.student_id')
                ->select(
                    'payment_installments.due_date',
                    'payment_installments.amount',
                    'students.name_with_initials as student_name'
                )
                ->whereRaw("LOWER(COALESCE(payment_installments.status, '')) IN ('overdue', 'pending')")
                ->whereDate('payment_installments.due_date', '<', now())
                ->orderBy('payment_installments.due_date')
                ->limit(20)
                ->get();
        } catch (\Throwable $e) {
            $latePayments = [];
        }

        $stats = [
            'revenue_summary' => [
                'total_revenue' => (float) DB::table('payment_details')
                    ->whereRaw("LOWER(COALESCE(status, '')) IN ('paid', 'complete', 'completed')")
                    ->sum('amount'),
                'revenue_this_period' => (float) DB::table('payment_details')
                    ->whereRaw("LOWER(COALESCE(status, '')) IN ('paid', 'complete', 'completed')")
                    ->whereRaw("{$dateExpr} BETWEEN ? AND ?", [$dateRange['start'], $dateRange['end']])
                    ->sum('amount'),
                'pending_amount' => (float) DB::table('payment_details')
                    ->whereRaw("LOWER(COALESCE(status, '')) IN ('pending', 'overdue', 'unpaid', 'partial')")
                    ->sum('amount'),
            ],
            'late_payments' => $latePayments,
        ];

        return response()->json($stats);
    }

    /**
     * Return recent dashboard activity entries.
     *
     * Combines student registrations and course registration activity.
     */
    public function getRecentActivities(Request $request)
    {
        $limit = $request->get('limit', 50);

        $recentStudents = DB::table('students')
            ->select('student_id as id', 'name_with_initials as title', 
                     DB::raw("'Student Registration' as type"), 'created_at',
                     DB::raw("CONCAT('New student: ', name_with_initials) as description"))
            ->orderBy('created_at', 'desc')->limit(10)->get();

        $recentCourseReg = DB::table('course_registration')
            ->join('students', 'course_registration.student_id', '=', 'students.student_id')
            ->join('courses', 'course_registration.course_id', '=', 'courses.course_id')
            ->select('course_registration.id', 'students.name_with_initials as title',
                     DB::raw("'Course Registration' as type"), 'course_registration.created_at',
                     DB::raw("CONCAT(students.name_with_initials, ' - ', courses.course_name) as description"))
            ->orderBy('course_registration.created_at', 'desc')->limit(10)->get();

        $activities = collect($recentStudents)->concat($recentCourseReg)
            ->sortByDesc('created_at')->take($limit)->values();

        return response()->json($activities);
    }

    /**
     * Return actionable items for admin follow-up.
     *
     * Includes pending course registrations and pending clearances.
     */
    public function getActionItems()
    {
        $actions = [
            'pending_registrations' => $this->pendingRegistrationsQuery()
                ->join('students', 'course_registration.student_id', '=', 'students.student_id')
                ->join('courses', 'course_registration.course_id', '=', 'courses.course_id')
                ->select(
                    'course_registration.id',
                    'students.name_with_initials as student_name',
                    'courses.course_name'
                )
                ->orderBy('course_registration.created_at', 'desc')
                ->limit(10)
                ->get(),

            'pending_clearances' => DB::table('clearance_requests')
                ->join('students', 'clearance_requests.student_id', '=', 'students.student_id')
                ->select(
                    'clearance_requests.id',
                    'clearance_requests.clearance_type',
                    'students.name_with_initials as student_name'
                )
                ->whereRaw("LOWER(COALESCE(clearance_requests.status, '')) = 'pending'")
                ->orderBy('clearance_requests.created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json($actions);
    }

    /**
     * Resolve the date range to use for period-based queries.
     *
     * Defaults to the current month for unsupported period values.
     */
    private function getDateRange($period)
    {
        switch ($period) {
            case 'today':
                return ['start' => Carbon::today()->startOfDay(), 'end' => Carbon::today()->endOfDay()];
            case 'week':
                return ['start' => Carbon::now()->startOfWeek()->startOfDay(), 'end' => Carbon::now()->endOfWeek()->endOfDay()];
            case 'month':
                return ['start' => Carbon::now()->startOfMonth()->startOfDay(), 'end' => Carbon::now()->endOfMonth()->endOfDay()];
            case 'year':
                return ['start' => Carbon::now()->startOfYear()->startOfDay(), 'end' => Carbon::now()->endOfYear()->endOfDay()];
            default:
                return ['start' => Carbon::now()->startOfMonth()->startOfDay(), 'end' => Carbon::now()->endOfMonth()->endOfDay()];
        }
    }

    private function pendingRegistrationsQuery()
    {
        return DB::table('course_registration')
            ->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(course_registration.status, '')) = 'pending'")
                    ->orWhereRaw("LOWER(COALESCE(course_registration.approval_status, '')) = 'pending'");
            });
    }

    private function paymentDateExpression(): string
    {
        if (Schema::hasColumn('payment_details', 'payment_effective_date')) {
            return 'COALESCE(payment_effective_date, payment_date, created_at)';
        }

        if (Schema::hasColumn('payment_details', 'payment_date')) {
            return 'COALESCE(payment_date, created_at)';
        }

        return 'created_at';
    }
}