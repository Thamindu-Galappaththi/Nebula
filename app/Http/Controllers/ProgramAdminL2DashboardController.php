<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use App\Models\Student;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Course;
use App\Models\Semester;
use App\Models\SemesterRegistration;
use App\Models\ClearanceRequest;
use App\Models\ExamResult;
use App\Models\Attendance;
use App\Models\PaymentDetail;
use App\Models\Module;

class ProgramAdminL2DashboardController extends Controller
{
    /**
     * Display the dashboard
     */
    public function showDashboard()
    {
        return view('dashboards.program_admin_l2');
    }

    /**
     * Get overview metrics (KPIs)
     */
    public function getOverviewMetrics(Request $request)
    {
        $request->validate([
            'location' => 'nullable|in:Welisara,Moratuwa,Peradeniya',
            'period' => 'nullable|in:today,week,month,quarter',
        ]);

        $location = $this->normalizeLocation($request->input('location'));
        $period = $request->input('period', 'month');
        [$startDate, $endDate] = $this->periodRange($period);
        [$prevStart, $prevEnd] = $this->previousPeriodRange($period);

        try {
            $registrations = $this->constrainByLocation(CourseRegistration::query(), $location);

            $totalActiveStudents = (clone $registrations)
                ->where('status', 'Registered')
                ->distinct()
                ->count('student_id');

            $activeBatches = $this->constrainByLocation(Intake::query(), $location)
                ->whereHas('courseRegistrations', function ($query) {
                    $query->where('status', 'Registered');
                })
                ->count();

            $pendingApprovals = $this->pendingApprovalsQuery($location)->count();

            $studentCountByBatch = $this->constrainByLocation(Intake::query(), $location)
                ->withCount([
                    'courseRegistrations' => function ($query) {
                        $query->where('status', 'Registered');
                    }
                ])
                ->orderBy('course_registrations_count', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($intake) {
                    return [
                        'batch' => $intake->batch,
                        'course_name' => $intake->course_name,
                        'count' => $intake->course_registrations_count
                    ];
                });

            $periodRegistrations = (clone $registrations)
                ->where('status', 'Registered');
            $this->applyRegistrationPeriod($periodRegistrations, $startDate, $endDate);
            $periodRegistrations = $periodRegistrations->count();

            $previousPeriodRegistrations = (clone $registrations)
                ->where('status', 'Registered');
            $this->applyRegistrationPeriod($previousPeriodRegistrations, $prevStart, $prevEnd);
            $previousPeriodRegistrations = $previousPeriodRegistrations->count();

            $growthPercentage = $previousPeriodRegistrations > 0
                ? (($periodRegistrations - $previousPeriodRegistrations) / $previousPeriodRegistrations) * 100
                : ($periodRegistrations > 0 ? 100 : 0);

            $pendingClearances = $this->constrainByLocation(ClearanceRequest::query(), $location)
                ->where('status', 'pending');
            $this->applyDatePeriod($pendingClearances, 'COALESCE(requested_at, created_at)', $startDate, $endDate);
            $pendingClearances = $pendingClearances->count();

            $specialApprovalNeeded = (clone $registrations)
                ->where('status', 'Special approval required');
            $this->applyRegistrationPeriod($specialApprovalNeeded, $startDate, $endDate);
            $specialApprovalNeeded = $specialApprovalNeeded->count();

            $avgAttendanceRate = $this->attendanceRateFor($location, $startDate, $endDate);

            $examQuery = $this->constrainByLocation(ExamResult::query(), $location);
            $this->applyDatePeriod($examQuery, 'created_at', $startDate, $endDate);

            $totalExamResults = (clone $examQuery)->count();
            $passResults = (clone $examQuery)
                ->whereRaw($this->examPassedSql())
                ->count();
            $passRate = $totalExamResults > 0 ? round(($passResults / $totalExamResults) * 100, 1) : 0;

            $monthSemesterReg = $this->constrainByLocation(SemesterRegistration::query(), $location);
            $this->applyRegistrationPeriod($monthSemesterReg, $startDate, $endDate, 'semester_registrations');
            $monthSemesterReg = $monthSemesterReg->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_active_students' => $totalActiveStudents,
                    'active_batches' => $activeBatches,
                    'pending_approvals' => $pendingApprovals,
                    'today_registrations' => $periodRegistrations,
                    'growth_percentage' => round($growthPercentage, 2),
                    'pending_clearances' => $pendingClearances,
                    'special_approval_needed' => $specialApprovalNeeded,
                    'avg_attendance_rate' => $avgAttendanceRate,
                    'pass_rate' => $passRate,
                    'month_semester_reg' => $monthSemesterReg,
                    'student_count_by_batch' => $studentCountByBatch,
                    'location' => $location,
                    'period' => $period,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard metrics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending approval registrations
     */
    public function getPendingApprovals(Request $request)
    {
        $location = $this->normalizeLocation($request->input('location'));

        try {
            $pendingApprovals = $this->pendingApprovalsQuery($location)
                ->with(['student', 'course', 'intake'])
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($registration) {
                    return [
                        'id' => $registration->id,
                        'student_id' => $registration->student_id,
                        'student_name' => $registration->student->full_name ?? 'N/A',
                        'course_name' => $registration->course->course_name ?? $registration->course_id,
                        'batch' => $registration->intake->batch ?? 'N/A',
                        'registration_date' => optional($registration->registration_date)->format('Y-m-d') ?? $registration->registration_date,
                        'registration_fee' => $registration->registration_fee,
                        'counselor_name' => $registration->counselor_name,
                        'remarks' => $registration->remarks,
                        'created_at' => optional($registration->created_at)->format('Y-m-d H:i:s'),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $pendingApprovals
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load pending approvals'
            ], 500);
        }
    }

    /**
     * Get active semester data
     */
    public function getActiveSemesters(Request $request)
    {
        $location = $this->normalizeLocation($request->input('location'));
        $locationValues = $this->locationValues($location);

        try {
            $activeSemesters = Semester::where(function ($query) use ($locationValues) {
                $query->whereHas('course', function ($q) use ($locationValues) {
                    $q->whereIn('location', $locationValues);
                })->orWhereHas('intake', function ($q) use ($locationValues) {
                    $q->whereIn('location', $locationValues);
                });
            })
                ->where(function ($query) {
                    $query->whereIn('status', ['active', 'Active', 'ongoing'])
                        ->orWhere(function ($q) {
                            $q->whereDate('start_date', '<=', now())
                                ->whereDate('end_date', '>=', now());
                        });
                })
                ->with(['course', 'semesterRegistrations'])
                ->orderBy('start_date', 'desc')
                ->get()
                ->map(function ($semester) {
                    return [
                        'id' => $semester->id,
                        'name' => $semester->name,
                        'course_name' => $semester->course->course_name ?? 'N/A',
                        'start_date' => optional($semester->start_date)->format('Y-m-d'),
                        'end_date' => optional($semester->end_date)->format('Y-m-d'),
                        'registered_count' => $semester->semesterRegistrations
                            ->whereIn('status', ['registered', 'Registered'])
                            ->count(),
                        'status' => $semester->status
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $activeSemesters
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load active semesters'
            ], 500);
        }
    }

    /**
     * Get courses for the analytics location filter.
     */
    public function getCoursesByLocation(Request $request)
    {
        $request->validate([
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
        ]);

        $courses = Course::select('course_id', 'course_name')
            ->where('location', $request->location)
            ->orderBy('course_name')
            ->get();

        $intakes = Intake::where('location', $request->location)
            ->orderByDesc('start_date')
            ->orderBy('batch')
            ->get(['intake_id', 'batch', 'course_name'])
            ->map(function ($intake) {
                return [
                    'intake_id' => $intake->intake_id,
                    'intake_name' => $intake->batch,
                    'batch' => $intake->batch,
                    'course_name' => $intake->course_name,
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'data' => $courses,
            'intakes' => $intakes,
        ]);
    }

    /**
     * Get intakes for the analytics filter (location required, course optional).
     */
    public function getIntakes(Request $request)
    {
        $request->validate([
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'course_id' => 'nullable|exists:courses,course_id',
        ]);

        $location = $request->location;
        $courseId = $request->course_id;

        try {
            if (!empty($courseId)) {
                $course = Course::find($courseId);
                $intakes = $course
                    ? Intake::forCourse($course, $location)
                        ->orderByDesc('start_date')
                        ->orderBy('batch')
                        ->get(['intake_id', 'batch', 'course_name', 'location'])
                    : collect();

                if ($intakes->isEmpty() && $course) {
                    $intakes = Intake::forCourse($course, null)
                        ->orderByDesc('start_date')
                        ->orderBy('batch')
                        ->get(['intake_id', 'batch', 'course_name', 'location']);
                }
            } else {
                $intakes = Intake::where('location', $location)
                    ->orderByDesc('start_date')
                    ->orderBy('batch')
                    ->get(['intake_id', 'batch', 'course_name', 'location']);
            }

            $data = $intakes->map(function ($intake) {
                return [
                    'intake_id' => $intake->intake_id,
                    'intake_name' => $intake->batch,
                    'batch' => $intake->batch,
                    'course_name' => $intake->course_name,
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load intakes',
                'data' => [],
            ], 500);
        }
    }

    /**
     * Get modules for a selected course
     */


    public function getModulesByCourse(Request $request)
    {
        $request->validate([
            'course_id' => 'nullable|exists:courses,course_id',
            'intake_id' => 'nullable|exists:intakes,intake_id',
        ]);

        try {
            $courseId = $request->filled('course_id') ? (int) $request->get('course_id') : null;
            $intakeId = $request->filled('intake_id') ? (int) $request->get('intake_id') : null;

            if (!$courseId && !$intakeId) {
                return response()->json(['success' => true, 'data' => []]);
            }

            $moduleIds = collect();

            if ($courseId && Schema::hasTable('course_modules')) {
                $moduleIds = $moduleIds->merge(
                    DB::table('course_modules')->where('course_id', $courseId)->pluck('module_id')
                );
            }

            if ($intakeId && Schema::hasTable('intake_modules')) {
                $moduleIds = $moduleIds->merge(
                    DB::table('intake_modules')->where('intake_id', $intakeId)->pluck('module_id')
                );
            }

            if (Schema::hasTable('semesters') && Schema::hasTable('semester_module') && ($courseId || $intakeId)) {
                $semesterQuery = DB::table('semesters');
                if ($courseId) {
                    $semesterQuery->where('course_id', $courseId);
                }
                if ($intakeId) {
                    $semesterQuery->where('intake_id', $intakeId);
                }
                $semesterIds = $semesterQuery->pluck('id');
                if ($semesterIds->isNotEmpty()) {
                    $moduleIds = $moduleIds->merge(
                        DB::table('semester_module')->whereIn('semester_id', $semesterIds)->pluck('module_id')
                    );
                }
            }

            $moduleIds = $moduleIds->filter()->unique()->values();

            $modules = $moduleIds->isEmpty()
                ? collect()
                : Module::whereIn('module_id', $moduleIds)
                    ->orderBy('module_name')
                    ->get(['module_id', 'module_name', 'module_code'])
                    ->map(function ($module) {
                        return [
                            'module_id' => $module->module_id,
                            'module_name' => $module->module_name,
                            'module_code' => $module->module_code,
                            'display_name' => trim(($module->module_code ? $module->module_code . ' - ' : '') . $module->module_name),
                        ];
                    });

            return response()->json([
                'success' => true,
                'data' => $modules,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load modules: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get student academic performance
     */
    public function getAcademicPerformance(Request $request)
    {
        $request->validate([
            'location' => 'nullable|in:Welisara,Moratuwa,Peradeniya',
            'course_id' => 'nullable|exists:courses,course_id',
            'intake_id' => 'nullable|exists:intakes,intake_id',
            'module_id' => 'nullable|exists:modules,module_id',
            'period' => 'nullable|in:today,week,month,quarter',
        ]);

        $location = $this->normalizeLocation($request->input('location'));
        $courseId = $request->get('course_id');
        $intakeId = $request->get('intake_id');

        try {
            $baseQuery = $this->constrainByLocation(ExamResult::query(), $location)
                ->when($courseId, function ($query) use ($courseId) {
                    $query->where('course_id', $courseId);
                })
                ->when($intakeId, function ($query) use ($intakeId) {
                    $query->where('intake_id', $intakeId);
                });

            $performanceData = (clone $baseQuery)
                ->select('grade', DB::raw('COUNT(*) as count'))
                ->whereNotNull('grade')
                ->where('grade', '!=', '')
                ->groupBy('grade')
                ->orderBy('grade')
                ->get();

            if ($performanceData->isEmpty()) {
                $performanceData = collect(['A', 'B', 'C', 'D', 'F'])->map(function ($grade) {
                    return ['grade' => $grade, 'count' => 0];
                });
            } else {
                $performanceData = $performanceData->map(function ($row) {
                    return [
                        'grade' => $row->grade,
                        'count' => (int) $row->count,
                    ];
                })->values();
            }

            $passedSql = $this->examPassedSql();
            $coursePerformance = (clone $baseQuery)
                ->select(
                    'exam_results.course_id',
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM(CASE WHEN {$passedSql} THEN 1 ELSE 0 END) as passed")
                )
                ->groupBy('exam_results.course_id')
                ->get()
                ->map(function ($item) {
                    $total = (int) $item->total;
                    $passed = (int) $item->passed;
                    $courseName = Course::where('course_id', $item->course_id)->value('course_name');
                    return [
                        'course_name' => $courseName ?? 'N/A',
                        'pass_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0,
                        'total' => $total,
                        'passed' => $passed,
                    ];
                })
                ->filter(function ($item) {
                    return $item['total'] > 0;
                })
                ->sortByDesc('pass_rate')
                ->values();

            $repeatStudents = (clone $baseQuery)
                ->where(function ($query) {
                    $query->where('marks', '<', 40)
                        ->orWhereIn('grade', ['F', 'NA', 'E']);
                })
                ->distinct()
                ->count('student_id');

            return response()->json([
                'success' => true,
                'data' => [
                    'grade_distribution' => $performanceData,
                    'course_performance' => $coursePerformance,
                    'repeat_students' => $repeatStudents,
                    'filters' => [
                        'location' => $location,
                        'course_id' => $courseId,
                        'intake_id' => $intakeId,
                        'period' => $request->input('period', 'month'),
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load academic performance data',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get attendance overview
     */
    public function getAttendanceOverview(Request $request)
    {
        $request->validate([
            'location' => 'nullable|in:Welisara,Moratuwa,Peradeniya',
            'course_id' => 'nullable|exists:courses,course_id',
            'intake_id' => 'nullable|exists:intakes,intake_id',
            'module_id' => 'nullable|exists:modules,module_id',
            'period' => 'nullable|in:today,week,month,quarter',
        ]);

        $location = $this->normalizeLocation($request->input('location'));
        $courseId = $request->get('course_id');
        $intakeId = $request->get('intake_id');
        $moduleId = $request->get('module_id');
        $period = $request->get('period', 'month');
        [$startDate, $endDate] = $this->periodRange($period);

        try {
            $presentExpr = $this->presentAttendanceSql();

            $baseQuery = $this->constrainByLocation(Attendance::query(), $location)
                ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
                ->when($courseId, function ($query) use ($courseId) {
                    $query->where('course_id', $courseId);
                })
                ->when($intakeId, function ($query) use ($intakeId) {
                    $query->where('intake_id', $intakeId);
                })
                ->when($moduleId, function ($query) use ($moduleId) {
                    $query->where('module_id', $moduleId);
                });

            $dailyAttendance = (clone $baseQuery)
                ->select(
                    DB::raw('DATE(date) as attendance_date'),
                    DB::raw("ROUND(SUM($presentExpr) * 100.0 / NULLIF(COUNT(*), 0), 1) as attendance_rate"),
                    DB::raw('COUNT(*) as total_records'),
                    DB::raw("SUM($presentExpr) as present_records")
                )
                ->groupBy(DB::raw('DATE(date)'))
                ->orderBy('attendance_date', 'asc')
                ->get()
                ->map(function ($row) {
                    return [
                        'attendance_date' => Carbon::parse($row->attendance_date)->toDateString(),
                        'attendance_rate' => round((float) $row->attendance_rate, 1),
                        'total_records' => (int) $row->total_records,
                    ];
                })
                ->values();

            $courseAttendance = (clone $baseQuery)
                ->select(
                    'course_id',
                    DB::raw("ROUND(SUM($presentExpr) * 100.0 / NULLIF(COUNT(*), 0), 1) as attendance_rate"),
                    DB::raw('COUNT(*) as total_records'),
                    DB::raw("SUM($presentExpr) as present_records")
                )
                ->groupBy('course_id')
                ->get()
                ->map(function ($item) {
                    $courseName = Course::where('course_id', $item->course_id)->value('course_name');
                    return [
                        'course_name' => $courseName ?? 'N/A',
                        'attendance_rate' => round((float) $item->attendance_rate, 1),
                        'total_records' => (int) $item->total_records,
                    ];
                })
                ->sortByDesc('attendance_rate')
                ->values();

            $overallStats = (clone $baseQuery)
                ->select(
                    DB::raw('COUNT(*) as total'),
                    DB::raw("SUM($presentExpr) as present"),
                    DB::raw("ROUND(SUM($presentExpr) * 100.0 / NULLIF(COUNT(*), 0), 1) as overall_rate")
                )
                ->first();

            return response()->json([
                'success' => true,
                'data' => [
                    'daily_attendance' => $dailyAttendance,
                    'course_attendance' => $courseAttendance,
                    'overall_stats' => $overallStats,
                    'period' => $period,
                    'filters' => [
                        'location' => $location,
                        'course_id' => $courseId,
                        'intake_id' => $intakeId,
                        'module_id' => $moduleId,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load attendance data'
            ], 500);
        }
    }

    /**
     * Get clearance status
     */
    public function getClearanceStatus(Request $request)
    {
        $location = $this->normalizeLocation($request->input('location'));

        try {
            $clearanceQuery = $this->constrainByLocation(ClearanceRequest::query(), $location);

            $clearanceStats = (clone $clearanceQuery)
                ->select(
                    'clearance_type',
                    'status',
                    DB::raw('COUNT(*) as count')
                )
                ->groupBy('clearance_type', 'status')
                ->get();

            $clearanceByType = [];
            foreach (ClearanceRequest::getClearanceTypes() as $type => $label) {
                $clearanceByType[$label] = [
                    'pending' => 0,
                    'approved' => 0,
                    'rejected' => 0,
                    'total' => 0,
                ];
            }

            foreach ($clearanceStats as $stat) {
                $label = $this->clearanceTypeLabel($stat->clearance_type);
                $statusKey = $this->clearanceStatusKey($stat->status);

                if (!isset($clearanceByType[$label])) {
                    $clearanceByType[$label] = [
                        'pending' => 0,
                        'approved' => 0,
                        'rejected' => 0,
                        'total' => 0,
                    ];
                }

                if (!in_array($statusKey, ['pending', 'approved', 'rejected'], true)) {
                    $statusKey = 'pending';
                }

                $clearanceByType[$label][$statusKey] += (int) $stat->count;
                $clearanceByType[$label]['total'] += (int) $stat->count;
            }

            $recentRequests = (clone $clearanceQuery)
                ->with(['student', 'course', 'intake'])
                ->orderByRaw('COALESCE(requested_at, created_at) DESC')
                ->limit(10)
                ->get()
                ->map(function ($request) {
                    $status = $this->clearanceStatusKey($request->status);
                    return [
                        'id' => $request->id,
                        'student_name' => $request->student->full_name ?? 'N/A',
                        'clearance_type' => $this->clearanceTypeLabel($request->clearance_type),
                        'course_name' => $request->course->course_name ?? 'N/A',
                        'batch' => $request->intake->batch ?? 'N/A',
                        'status' => $status,
                        'requested_at' => optional($request->requested_at ?? $request->created_at)->format('Y-m-d H:i:s'),
                        'remarks' => $request->remarks,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => [
                    'clearance_by_type' => $clearanceByType,
                    'recent_requests' => $recentRequests,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load clearance data'
            ], 500);
        }
    }

    /**
     * Get payment overview
     */
    public function getPaymentOverview(Request $request)
    {
        $request->validate([
            'location' => 'nullable|in:Welisara,Moratuwa,Peradeniya',
            'course_id' => 'nullable|exists:courses,course_id',
            'period' => 'nullable|in:today,week,month,quarter',
        ]);

        $location = $this->normalizeLocation($request->input('location'));
        $locationValues = $this->locationValues($location);
        $courseId = $request->input('course_id');
        $period = $request->input('period', 'month');
        [$startDate, $endDate] = $this->periodRange($period);
        $paymentDateSql = $this->paymentDateSql();
        $paidAmountSql = $this->paidAmountSql();

        try {
            $paymentQuery = PaymentDetail::whereHas('registration', function ($query) use ($location, $courseId) {
                $this->constrainByLocation($query, $location);
                $query->when($courseId, function ($query) use ($courseId) {
                    $query->where('course_id', (int) $courseId);
                });
            });

            $registrationQuery = $this->constrainByLocation(CourseRegistration::query(), $location)
                ->when($courseId, function ($query) use ($courseId) {
                    $query->where('course_id', (int) $courseId);
                });

            $paidQuery = (clone $paymentQuery)->whereRaw("LOWER(COALESCE(status, '')) IN ('paid', 'complete', 'completed')");
            $pendingQuery = (clone $paymentQuery)->whereRaw("LOWER(COALESCE(status, '')) IN ('pending', 'overdue', 'unpaid', 'partial')");

            $totalRevenue = (float) ((clone $paidQuery)->selectRaw("SUM({$paidAmountSql}) as total")->value('total') ?? 0);
            $pendingPayments = (clone $pendingQuery)->count();

            $periodRevenue = (float) ((clone $paidQuery)
                ->whereRaw("DATE({$paymentDateSql}) BETWEEN ? AND ?", [$startDate->toDateString(), $endDate->toDateString()])
                ->selectRaw("SUM({$paidAmountSql}) as total")
                ->value('total') ?? 0);

            $monthlyRows = (clone $paidQuery)
                ->selectRaw("DATE_FORMAT({$paymentDateSql}, '%Y-%m') as month_key, DATE_FORMAT({$paymentDateSql}, '%b %Y') as month, SUM({$paidAmountSql}) as revenue")
                ->groupByRaw("DATE_FORMAT({$paymentDateSql}, '%Y-%m'), DATE_FORMAT({$paymentDateSql}, '%b %Y')")
                ->get()
                ->keyBy('month_key');

            $monthlyRevenue = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = Carbon::now()->subMonths($i);
                $key = $month->format('Y-m');
                $monthlyRevenue[] = [
                    'month' => $month->format('M Y'),
                    'revenue' => (float) ($monthlyRows->get($key)->revenue ?? 0),
                ];
            }

            $paymentStats = (clone $paymentQuery)
                ->selectRaw("LOWER(COALESCE(status, 'unknown')) as status, COUNT(*) as count")
                ->groupByRaw("LOWER(COALESCE(status, 'unknown'))")
                ->get()
                ->map(function ($item) {
                    return [
                        'status' => $item->status ?? 'unknown',
                        'count' => (int) ($item->count ?? 0),
                    ];
                })
                ->toArray();

            $availableCourses = $this->constrainByLocation(Course::query(), $location)
                ->orderBy('course_name')
                ->get(['course_id', 'course_name'])
                ->map(function ($course) {
                    return [
                        'course_id' => (int) $course->course_id,
                        'course_name' => $course->course_name,
                    ];
                })
                ->values();

            $registrationDateSql = $this->registrationDateSql();
            $registrationSummary = (clone $registrationQuery)
                ->select(
                    'course_id',
                    DB::raw('COUNT(*) as total_registrations'),
                    DB::raw("SUM(CASE WHEN status = 'Registered' THEN 1 ELSE 0 END) as ongoing_courses"),
                    DB::raw("SUM(CASE WHEN {$registrationDateSql} BETWEEN '{$startDate->toDateString()}' AND '{$endDate->toDateString()}' THEN 1 ELSE 0 END) as new_registrations")
                )
                ->groupBy('course_id')
                ->get()
                ->keyBy('course_id');

            $paymentSummary = PaymentDetail::join('course_registration', 'payment_details.course_registration_id', '=', 'course_registration.id')
                ->where(function ($query) use ($locationValues, $location) {
                    $query->whereIn('course_registration.location', $locationValues)
                        ->orWhere('course_registration.location', 'like', '%' . $location . '%');
                })
                ->when($courseId, function ($query) use ($courseId) {
                    $query->where('course_registration.course_id', (int) $courseId);
                })
                ->select(
                    'course_registration.course_id',
                    DB::raw("SUM(CASE WHEN LOWER(COALESCE(payment_details.status, '')) IN ('paid', 'complete', 'completed') THEN {$paidAmountSql} ELSE 0 END) as paid_amount"),
                    DB::raw("SUM(CASE WHEN LOWER(COALESCE(payment_details.status, '')) IN ('pending', 'overdue', 'unpaid', 'partial') THEN {$paidAmountSql} ELSE 0 END) as pending_amount"),
                    DB::raw('COUNT(payment_details.id) as payment_count')
                )
                ->groupBy('course_registration.course_id')
                ->get()
                ->keyBy('course_id');

            $courseWiseSummary = $availableCourses
                ->map(function ($course) use ($registrationSummary, $paymentSummary) {
                    $rowCourseId = (int) $course['course_id'];
                    $registrationRow = $registrationSummary->get($rowCourseId);
                    $paymentRow = $paymentSummary->get($rowCourseId);

                    return [
                        'course_id' => $rowCourseId,
                        'course_name' => $course['course_name'] ?? 'N/A',
                        'total_registrations' => (int) ($registrationRow->total_registrations ?? 0),
                        'new_registrations' => (int) ($registrationRow->new_registrations ?? 0),
                        'ongoing_courses' => (int) ($registrationRow->ongoing_courses ?? 0),
                        'paid_amount' => (float) ($paymentRow->paid_amount ?? 0),
                        'pending_amount' => (float) ($paymentRow->pending_amount ?? 0),
                        'payment_count' => (int) ($paymentRow->payment_count ?? 0),
                    ];
                })
                ->sortByDesc('paid_amount')
                ->values()
                ->toArray();

            $newRegistrationsQuery = (clone $registrationQuery)->with(['student', 'course']);
            $this->applyRegistrationPeriod($newRegistrationsQuery, $startDate, $endDate);
            $newRegistrations = $newRegistrationsQuery
                ->orderByRaw("{$registrationDateSql} DESC")
                ->limit(10)
                ->get()
                ->map(function ($registration) {
                    return [
                        'id' => $registration->id,
                        'student_name' => $registration->student->full_name ?? 'N/A',
                        'course_name' => $registration->course->course_name ?? 'N/A',
                        'registration_date' => $registration->registration_date ? $registration->registration_date->format('Y-m-d') : 'N/A',
                        'status' => $registration->status,
                        'registration_fee' => (float) ($registration->registration_fee ?? 0),
                    ];
                })
                ->toArray();

            $newRegistrationsCountQuery = clone $registrationQuery;
            $this->applyRegistrationPeriod($newRegistrationsCountQuery, $startDate, $endDate);
            $newRegistrationsCount = $newRegistrationsCountQuery->count();

            $ongoingCoursesCount = (clone $registrationQuery)
                ->where('status', 'Registered')
                ->count();

            $selectedCourseName = $courseId
                ? Course::where('course_id', (int) $courseId)->value('course_name')
                : null;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_revenue' => (float) ($totalRevenue ?? 0),
                    'pending_payments' => (int) ($pendingPayments ?? 0),
                    'period_revenue' => (float) ($periodRevenue ?? 0),
                    'monthly_revenue' => $monthlyRevenue,
                    'payment_stats' => $paymentStats ?? [],
                    'available_courses' => $availableCourses,
                    'course_wise_summary' => $courseWiseSummary,
                    'new_registrations' => $newRegistrations,
                    'new_registrations_count' => (int) $newRegistrationsCount,
                    'ongoing_courses_count' => (int) $ongoingCoursesCount,
                    'selected_course_name' => $selectedCourseName,
                    'period' => $period,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('Payment Overview Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to load payment overview',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Approve registration
     */
    public function approveRegistration(Request $request, $id)
    {
        try {
            $registration = CourseRegistration::findOrFail($id);

            if (!$this->canManageRegistration($registration)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to approve registration from this location',
                ], 403);
            }

            $registration->status = 'Registered';
            $registration->approval_status = 'Approved by manager';
            $registration->updated_at = now();
            $registration->save();

            return response()->json([
                'success' => true,
                'message' => 'Registration approved successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve registration',
            ], 500);
        }
    }

    /**
     * Reject registration
     */
    public function rejectRegistration(Request $request, $id)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $registration = CourseRegistration::findOrFail($id);

            if (!$this->canManageRegistration($registration)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to reject registration from this location',
                ], 403);
            }

            $registration->status = 'Not eligible';
            $registration->approval_status = 'Rejected';
            $registration->remarks = $request->reason;
            $registration->updated_at = now();
            $registration->save();

            return response()->json([
                'success' => true,
                'message' => 'Registration rejected successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject registration',
            ], 500);
        }
    }

    /**
     * Approve all pending registrations for this campus
     */
    public function approveAllPending(Request $request)
    {
        try {
            $registrations = $this->pendingApprovalsQuery($request->input('location'))->get();
            $count = 0;

            foreach ($registrations as $registration) {
                if (!$this->canManageRegistration($registration)) {
                    continue;
                }

                $registration->status = 'Registered';
                $registration->approval_status = 'Approved by manager';
                $registration->updated_at = now();
                $registration->save();
                $count++;
            }

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => $count > 0
                    ? "{$count} registration(s) approved successfully"
                    : 'No pending registrations to approve',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to approve registrations',
            ], 500);
        }
    }

    /**
     * Reject all pending registrations for this campus
     */
    public function rejectAllPending(Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        try {
            $registrations = $this->pendingApprovalsQuery($request->input('location'))->get();
            $count = 0;

            foreach ($registrations as $registration) {
                if (!$this->canManageRegistration($registration)) {
                    continue;
                }

                $registration->status = 'Not eligible';
                $registration->approval_status = 'Rejected';
                $registration->remarks = $request->reason;
                $registration->updated_at = now();
                $registration->save();
                $count++;
            }

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => $count > 0
                    ? "{$count} registration(s) rejected"
                    : 'No pending registrations to reject',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject registrations',
            ], 500);
        }
    }

    private function constrainByLocation($query, ?string $location = null, string $column = 'location')
    {
        $short = $this->normalizeLocation($location);
        $values = $this->locationValues($short);

        return $query->where(function ($inner) use ($column, $values, $short) {
            $inner->whereIn($column, $values)
                ->orWhere($column, 'like', '%' . $short . '%');
        });
    }

    private function pendingApprovalsQuery(?string $location = null)
    {
        return $this->constrainByLocation(CourseRegistration::query(), $location)
            ->where(function ($query) {
                $query->whereIn('status', ['Pending', 'Special approval required'])
                    ->orWhere(function ($inner) {
                        $inner->where('approval_status', 'Pending')
                            ->whereNotIn('status', ['Registered', 'Not eligible']);
                    });
            })
            ->where(function ($query) {
                $query->whereNull('approval_status')
                    ->orWhereNotIn('approval_status', ['Approved by manager', 'Rejected', 'Sent to DGM']);
            });
    }

    private function canManageRegistration(CourseRegistration $registration): bool
    {
        $userLocation = $this->normalizeLocation();
        $allowed = $this->locationValues($userLocation);

        return in_array($registration->location, $allowed, true)
            || stripos((string) $registration->location, $userLocation) !== false;
    }

    private function paymentDateSql(): string
    {
        static $sql = null;
        if ($sql !== null) {
            return $sql;
        }

        $columns = [];
        if (Schema::hasColumn('payment_details', 'payment_effective_date')) {
            $columns[] = 'payment_effective_date';
        }
        if (Schema::hasColumn('payment_details', 'payment_date')) {
            $columns[] = 'payment_date';
        }
        $columns[] = 'created_at';

        $sql = 'COALESCE(' . implode(', ', $columns) . ')';

        return $sql;
    }

    private function paidAmountSql(): string
    {
        static $sql = null;
        if ($sql !== null) {
            return $sql;
        }

        $amount = Schema::hasColumn('payment_details', 'amount') ? 'payment_details.amount' : '0';
        $totalFee = Schema::hasColumn('payment_details', 'total_fee') ? 'payment_details.total_fee' : '0';
        $sql = "COALESCE({$amount}, {$totalFee}, 0)";

        return $sql;
    }

    private function normalizeLocation(?string $location = null): string
    {
        $value = trim((string) ($location ?: (auth()->user()->user_location ?? 'Welisara')));
        $value = str_replace(['Nebula Institute of Technology – ', 'Nebula Institute of Technology - '], '', $value);

        if ($value === '' || strcasecmp($value, 'Default Location') === 0) {
            $value = str_replace(
                ['Nebula Institute of Technology – ', 'Nebula Institute of Technology - '],
                '',
                (string) (auth()->user()->user_location ?? 'Welisara')
            );
        }

        foreach (['Welisara', 'Moratuwa', 'Peradeniya'] as $campus) {
            if (stripos($value, $campus) !== false) {
                return $campus;
            }
        }

        return $value !== '' ? $value : 'Welisara';
    }

    private function locationValues(?string $location = null): array
    {
        $short = $this->normalizeLocation($location);

        return array_values(array_unique([
            $short,
            'Nebula Institute of Technology - ' . $short,
            'Nebula Institute of Technology – ' . $short,
        ]));
    }

    private function periodRange(?string $period): array
    {
        $end = Carbon::now()->endOfDay();

        switch ($period) {
            case 'today':
                $start = Carbon::today()->startOfDay();
                break;
            case 'week':
                $start = Carbon::now()->startOfWeek()->startOfDay();
                break;
            case 'quarter':
                $start = Carbon::now()->subMonths(2)->startOfMonth()->startOfDay();
                break;
            case 'month':
            default:
                $start = Carbon::now()->startOfMonth()->startOfDay();
                break;
        }

        return [$start, $end];
    }

    private function previousPeriodRange(?string $period): array
    {
        switch ($period) {
            case 'today':
                return [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()];
            case 'week':
                return [Carbon::now()->subWeek()->startOfWeek()->startOfDay(), Carbon::now()->subWeek()->endOfWeek()->endOfDay()];
            case 'quarter':
                $currentStart = Carbon::now()->subMonths(2)->startOfMonth()->startOfDay();
                return [
                    $currentStart->copy()->subMonths(3)->startOfMonth()->startOfDay(),
                    $currentStart->copy()->subDay()->endOfDay(),
                ];
            case 'month':
            default:
                return [Carbon::now()->subMonth()->startOfMonth()->startOfDay(), Carbon::now()->subMonth()->endOfMonth()->endOfDay()];
        }
    }

    private function registrationDateSql(string $table = 'course_registration'): string
    {
        return "DATE(COALESCE({$table}.registration_date, {$table}.created_at))";
    }

    private function applyDatePeriod($query, string $columnSql, Carbon $start, Carbon $end)
    {
        return $query->whereRaw("DATE({$columnSql}) BETWEEN ? AND ?", [
            $start->toDateString(),
            $end->toDateString(),
        ]);
    }

    private function applyRegistrationPeriod($query, Carbon $start, Carbon $end, string $table = 'course_registration')
    {
        return $query->whereRaw($this->registrationDateSql($table) . ' BETWEEN ? AND ?', [
            $start->toDateString(),
            $end->toDateString(),
        ]);
    }

    private function presentAttendanceSql(): string
    {
        // attendance.status is boolean 1=present, 0=absent.
        // Do not use IN ('true','Present') — MySQL casts those strings to 0 and counts absences as present.
        return "CASE WHEN status = 1 OR LOWER(CAST(status AS CHAR)) IN ('1', 'present') THEN 1 ELSE 0 END";
    }

    private function examPassedSql(): string
    {
        // Production L2 dashboard: a result passes if marks are at least 40,
        // or the letter grade is A-D (including + / -). BTEC P/M/D without marks do not pass here.
        return "(
            (marks IS NOT NULL AND marks >= 40)
            OR UPPER(TRIM(COALESCE(grade, ''))) IN ('A', 'A+', 'A-', 'B', 'B+', 'B-', 'C', 'C+', 'C-', 'D')
        )";
    }

    private function examResultPassed($grade, $marks): bool
    {
        if (is_numeric($marks) && (float) $marks >= 40) {
            return true;
        }

        return in_array(strtoupper(trim((string) $grade)), ['A', 'A+', 'A-', 'B', 'B+', 'B-', 'C', 'C+', 'C-', 'D'], true);
    }

    private function clearanceTypeLabel(?string $type): string
    {
        $value = strtolower(trim((string) $type));
        foreach (ClearanceRequest::getClearanceTypes() as $key => $label) {
            if ($value === strtolower($key) || $value === strtolower($label) || str_contains($value, $key)) {
                return $label;
            }
        }

        return $type ? ucwords(str_replace('_', ' ', $type)) : 'Other';
    }

    private function clearanceStatusKey(?string $status): string
    {
        $value = strtolower(trim((string) $status));

        if (in_array($value, ['approved', 'approve', 'accepted'], true)) {
            return 'approved';
        }
        if (in_array($value, ['rejected', 'reject', 'declined'], true)) {
            return 'rejected';
        }

        return 'pending';
    }

    private function attendanceRateFor(?string $location, ?Carbon $startDate = null, ?Carbon $endDate = null): float
    {
        $presentExpr = $this->presentAttendanceSql();
        $query = Attendance::query()->where(function ($inner) use ($location) {
            $this->constrainByLocation($inner, $location);
            $inner->orWhereHas('course', function ($courseQuery) use ($location) {
                $this->constrainByLocation($courseQuery, $location);
            });
        });

        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
        }

        $row = $query
            ->selectRaw("ROUND(SUM($presentExpr) * 100.0 / NULLIF(COUNT(*), 0), 1) as avg_attendance")
            ->first();

        return $row && $row->avg_attendance !== null ? round((float) $row->avg_attendance, 1) : 0.0;
    }
}