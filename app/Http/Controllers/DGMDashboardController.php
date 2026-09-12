<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Student;
use App\Models\CourseRegistration;
use App\Models\PaymentDetail;
use App\Models\PaymentInstallment;
use App\Models\Course;
use App\Models\Intake;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\PaymentPlan;

class DGMDashboardController extends Controller
{
    public function showDashboard()
    {
        return view('dashboards.dgmdashboard');
    }

    /**
     * Get overview metrics for the dashboard
     */
    public function getOverviewMetrics(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $location = $request->input('location', 'all');
        $course = $request->input('course', 'all');
        $month = $request->input('month');
        $day = $request->input('day');

        // Build date filter
        $dateFilter = $this->buildDateFilter($year, $month, $day);
        $prevYear = $year - 1;
        $prevDateFilter = $this->buildDateFilter($prevYear, $month, $day);

        // Total Students
        $studentsQuery = Student::query();
        if ($location !== 'all') {
            $studentsQuery->where('institute_location', $location);
        }
        $totalStudents = $studentsQuery->count();

        // Calculate Revenue from both sources for current year
        // 1. From bulk_revenue_uploads
        $bulkRevenueQuery = DB::table('bulk_revenue_uploads')
            ->where('year', $year);

        if ($location !== 'all') {
            $bulkRevenueQuery->where('location', $location);
        }
        if ($course !== 'all') {
            $bulkRevenueQuery->where('course', $course);
        }
        if ($month) {
            $bulkRevenueQuery->where('month', $month);
        }
        if ($day) {
            $bulkRevenueQuery->where('day', $day);
        }

        $bulkRevenue = $bulkRevenueQuery->sum('revenue');

        // 2. From payment_details (partial payments)
        $paymentBaseQuery = PaymentDetail::query();

        if ($location !== 'all') {
            $paymentBaseQuery->whereHas('student', function ($q) use ($location) {
                $q->where('institute_location', $location);
            });
        }
        if ($course !== 'all') {
            $paymentBaseQuery->whereHas('registration.course', function ($q) use ($course) {
                $q->where('course_id', $course);
            });
        }

        $payments = $paymentBaseQuery->get();
        $partialPaymentsRevenue = 0.0;

        foreach ($payments as $payment) {
            $partialPaymentsRevenue += $this->getPaymentContributionForPeriod($payment, $dateFilter['start'], $dateFilter['end']);
        }

        // Total current year revenue
        $yearlyRevenue = $bulkRevenue + $partialPaymentsRevenue;

        // Calculate Previous Year Revenue

        // 1. Previous year bulk revenue
        $prevBulkRevenue = DB::table('bulk_revenue_uploads')
            ->where('year', $prevYear)
            ->when($location !== 'all', fn($q) => $q->where('location', $location))
            ->when($course !== 'all', fn($q) => $q->where('course', $course))
            ->when($month, fn($q) => $q->where('month', $month))
            ->when($day, fn($q) => $q->where('day', $day))
            ->sum('revenue');

        // 2. Previous year partial payments
        $prevPartialPaymentsRevenue = 0.0;
        foreach ($payments as $payment) {
            $prevPartialPaymentsRevenue += $this->getPaymentContributionForPeriod($payment, $prevDateFilter['start'], $prevDateFilter['end']);
        }

        $prevYearRevenue = $prevBulkRevenue + $prevPartialPaymentsRevenue;

        // Calculate revenue change percentage
        $revenueChange = $prevYearRevenue > 0
            ? round((($yearlyRevenue - $prevYearRevenue) / $prevYearRevenue) * 100, 1)
            : 0;

        // Outstanding Amount calculations remain the same
        $outstanding = 0;
        $planQuery = PaymentPlan::query();
        if ($location !== 'all') {
            $planQuery->whereHas('student', function ($q) use ($location) {
                $q->where('institute_location', $location);
            });
        }
        if ($course !== 'all') {
            $planQuery->whereHas('registration.course', function ($q) use ($course) {
                $q->where('course_id', $course);
            });
        }

        $plans = $planQuery->get();

        foreach ($plans as $plan) {
            if (is_array($plan->installments)) {
                foreach ($plan->installments as $inst) {
                    $dueDate = Carbon::parse($inst['due_date']);
                    if ($dueDate->isAfter(Carbon::now())) {
                        $outstanding += ($inst['local_amount'] ?? 0);
                    }
                }
            }
        }

        $outstandingCurrentYear = 0.0;
        try {
            // total scheduled for this year (sum of installment amounts whose due_date is in the target year)
            $pendingCurrentYear = PaymentInstallment::when($year, fn($q) => $q->whereYear('due_date', $year))
                ->sum('final_amount');

            $outstandingCurrentYear = $pendingCurrentYear - $partialPaymentsRevenue;

            // sum of partial payments that actually happened in the same year

        } catch (\Throwable $ex) {
            Log::warning('Could not compute outstandingCurrentYear: ' . $ex->getMessage());
            $outstandingCurrentYear = 0.0;
        }

        // Location Summary with both revenue sources
        $locations = ['Welisara', 'Moratuwa', 'Peradeniya'];
        $locationSummary = [];

        foreach ($locations as $loc) {
            // Current year bulk revenue
            $currBulkRev = DB::table('bulk_revenue_uploads')
                ->where('year', $year)
                ->where('location', $loc)
                ->when($course !== 'all', fn($q) => $q->where('course', $course))
                ->sum('revenue');

            // Current year partial payments
            $currPartialRev = 0.0;
            $locPayments = PaymentDetail::whereHas('student', fn($q) => $q->where('institute_location', $loc))
                ->when($course !== 'all', fn($q) => $q->whereHas('registration.course', fn($qq) => $qq->where('course_id', $course)))
                ->get();

            foreach ($locPayments as $p) {
                $currPartialRev += $this->getPaymentContributionForPeriod($p, $dateFilter['start'], $dateFilter['end']);
            }

            // Previous year calculations
            $prevBulkRev = DB::table('bulk_revenue_uploads')
                ->where('year', $prevYear)
                ->where('location', $loc)
                ->when($course !== 'all', fn($q) => $q->where('course', $course))
                ->sum('revenue');

            $prevPartialRev = 0.0;
            foreach ($locPayments as $p) {
                $prevPartialRev += $this->getPaymentContributionForPeriod($p, $prevDateFilter['start'], $prevDateFilter['end']);
            }

            // Outstanding from payment_plans
            $outstandingtable = 0;
            $locPlans = PaymentPlan::where('location', $loc)
                ->when($course !== 'all', fn($q) => $q->where('course_id', $course))
                ->get();

            foreach ($locPlans as $plan) {
                if (is_array($plan->installments)) {
                    foreach ($plan->installments as $inst) {
                        $dueDate = Carbon::parse($inst['due_date']);
                        if ($dueDate->isAfter(Carbon::now())) {
                            $outstandingtable += ($inst['local_amount'] ?? 0);
                        }
                    }
                }
            }

            $currTotal = $currBulkRev + $currPartialRev;
            $prevTotal = $prevBulkRev + $prevPartialRev;
            $growth = $prevTotal > 0 ? round((($currTotal - $prevTotal) / $prevTotal) * 100, 1) : 0;

            $locationSummary[] = [
                'location' => $loc,
                'current_year' => number_format($currTotal, 2),
                'previous_year' => number_format($prevTotal, 2),
                'growth' => $growth,
                'outstanding' => number_format($outstandingtable, 2),
            ];
        }

        return response()->json([
            'totalStudents' => $totalStudents,
            'yearlyRevenue' => number_format($yearlyRevenue, 2),
            'outstanding' => number_format($outstanding, 2),
            'outstandingCurrentYear' => number_format($outstandingCurrentYear, 2),
            'revenueChange' => $revenueChange >= 0 ? "+{$revenueChange}%" : "{$revenueChange}%",
            'outstandingRatio' => $yearlyRevenue > 0 ? round(($outstanding / ($yearlyRevenue + $outstanding)) * 100) : 0,
            'locationSummary' => $locationSummary
        ]);
    }

    /**
     * Get students data by location and course
     */
    public function getStudentsData(Request $request)
    {
        $year = $request->input('year');
        if (empty($year) || !is_numeric($year)) {
            if ($request->input('year') === 'all') {
                $year = 'all';
            } else {
                $year = date('Y');
            }
        } else {
            $year = (int) $year;
        }

        $month = $request->input('month');
        $day = $request->input('date');
        $location = $request->input('location', 'all');
        $course = $request->input('course', 'all');

        $locationsArray = [];
        if ($location !== 'all' && !empty($location)) {
            $locationsArray = array_filter(array_map('trim', explode(',', $location)));
        }

        $periodBuckets = $this->buildPeriodBuckets($request);

        $coursesSelected = [];
        $courseIds = [];
        $courseNames = [];

        if ($course !== 'all' && !empty($course)) {
            $coursesSelected = array_values(array_filter(array_map('trim', explode(',', $course))));
            foreach ($coursesSelected as $c) {
                if (is_numeric($c)) {
                    $courseIds[] = (int) $c;
                } else {
                    $courseNames[] = $c;
                }
            }

            // Resolve any numeric ids to names and merge
            if (!empty($courseIds)) {
                $resolved = Course::whereIn('course_id', $courseIds)->pluck('course_name', 'course_id')->toArray();
                foreach ($resolved as $id => $name) {
                    if (!in_array($name, $courseNames, true)) {
                        $courseNames[] = $name;
                    }
                }
            }
        }

        $locations = empty($locationsArray) ? ['Welisara', 'Moratuwa', 'Peradeniya'] : $locationsArray;
        $aggregate = [];

        // 1) bulk rows + 2) registrations, scoped to each compare/range/single period
        $courseNameForMatch = null;
        if ($course !== 'all' && is_numeric($course)) {
            $courseNameForMatch = Course::where('course_id', $course)->value('course_name');
        }

        foreach ($periodBuckets as $bucket) {
            $y = $bucket['year'];
            $month = $bucket['month'];
            $day = $bucket['day'];

            $bulkQuery = DB::table('bulk_student_uploads')
                ->where('year', $y)
                ->whereIn('location', $locations);

            if ($month) {
                $bulkQuery->where('month', $month);
            }
            if ($day) {
                $bulkQuery->where('day', $day);
            }

            if ($course !== 'all') {
                $bulkQuery->where(function ($q) use ($course, $courseNameForMatch, $courseIds, $courseNames) {
                    if (!empty($courseIds)) {
                        $q->whereIn('course', $courseIds);
                    }
                    if (!empty($courseNames)) {
                        $q->orWhereIn('course', $courseNames);
                    }
                    $q->orWhere('course', $course);
                    if ($courseNameForMatch) {
                        $q->orWhere('course', $courseNameForMatch);
                    }
                });
            }

            foreach ($bulkQuery->get() as $row) {
                $c = $row->course ?? ($course !== 'all' ? $course : 'all');
                if (empty($c)) {
                    $c = 'all';
                }
                $key = "{$bucket['period']}|{$row->location}|{$c}";
                if (!isset($aggregate[$key])) {
                    $aggregate[$key] = [
                        'year' => $y,
                        'month' => $month,
                        'period' => $bucket['period'],
                        'label' => $bucket['label'],
                        'institute_location' => $row->location,
                        'course' => $c,
                        'count' => 0
                    ];
                }
                $aggregate[$key]['count'] += (int) ($row->student_count ?? 0);
            }

            foreach ($locations as $loc) {
                $courseLoop = [];

                if ($course === 'all') {
                    $allCourses = Course::select('course_id', 'course_name')->get();
                    foreach ($allCourses as $cObj) {
                        $courseLoop[] = ['id' => $cObj->course_id, 'name' => $cObj->course_name];
                    }
                } else {
                    if (!empty($courseIds)) {
                        $rows = Course::whereIn('course_id', $courseIds)->get();
                        foreach ($rows as $r) {
                            $courseLoop[] = ['id' => $r->course_id, 'name' => $r->course_name];
                        }
                    }
                    if (!empty($courseNames)) {
                        $rows = Course::whereIn('course_name', $courseNames)->get();
                        foreach ($rows as $r) {
                            $exists = false;
                            foreach ($courseLoop as $cl) {
                                if ($cl['id'] == $r->course_id) {
                                    $exists = true;
                                    break;
                                }
                            }
                            if (!$exists) {
                                $courseLoop[] = ['id' => $r->course_id, 'name' => $r->course_name];
                            }
                        }
                    }
                    if (empty($courseLoop)) {
                        $singleRows = Course::where('course_id', $course)->orWhere('course_name', $course)->get();
                        foreach ($singleRows as $r) {
                            $courseLoop[] = ['id' => $r->course_id, 'name' => $r->course_name];
                        }
                    }
                }

                foreach ($courseLoop as $cInfo) {
                    $courseId = $cInfo['id'];
                    $courseName = $cInfo['name'];

                    $regQuery = Student::where('institute_location', $loc)
                        ->whereHas('courseRegistrations', function ($q) use ($courseId, $bucket) {
                            $q->where('course_id', $courseId)
                                ->whereBetween('created_at', [$bucket['periodStart'], $bucket['periodEnd']]);
                        });

                    $count = $regQuery->distinct()->count('students.student_id');

                    $key = "{$bucket['period']}|{$loc}|{$courseName}";
                    if (!isset($aggregate[$key])) {
                        $aggregate[$key] = [
                            'year' => $y,
                            'month' => $month,
                            'period' => $bucket['period'],
                            'label' => $bucket['label'],
                            'institute_location' => $loc,
                            'course_name' => $courseName,
                            'count' => 0
                        ];
                    }
                    $aggregate[$key]['count'] += (int) $count;
                }
            }
        }

        $data = array_values($aggregate);

        // Normalize keys for frontend: ensure 'course_name' and 'institute_location' exist and are readable
        $courseIdToName = Course::pluck('course_name', 'course_id')->toArray();
        foreach ($data as &$item) {
            // normalize course value (bulk uses 'course', registrations used numeric id or 'course')
            $rawCourse = $item['course'] ?? $item['course_name'] ?? null;
            if ($rawCourse === null || $rawCourse === '') {
                $courseNameOut = 'all';
            } elseif (is_numeric($rawCourse)) {
                $courseNameOut = $courseIdToName[intval($rawCourse)] ?? (string) $rawCourse;
            } else {
                $courseNameOut = (string) $rawCourse;
            }
            $item['course_name'] = $courseNameOut;

            // ensure frontend key exists for location
            if (!isset($item['institute_location']) && isset($item['location'])) {
                $item['institute_location'] = $item['location'];
            }
            if (empty($item['period'])) {
                $item['period'] = isset($item['month']) && $item['month']
                    ? sprintf('%d-%02d', (int) $item['year'], (int) $item['month'])
                    : (string) ($item['year'] ?? '');
            }
            if (empty($item['label'])) {
                $item['label'] = (string) ($item['year'] ?? '');
            }
        }
        unset($item);

        return response()->json($data);
    }

    /**
     * Get revenue data by year and location
     */
    public function getRevenueByYearCourse(Request $request)
    {
        $location = $request->input('location', 'all');
        $course = $request->input('course', 'all');

        $locationsArray = [];
        if ($location !== 'all' && !empty($location)) {
            $locationsArray = array_filter(array_map('trim', explode(',', $location)));
        }

        $courseIds = [];
        if ($course !== 'all' && !empty($course)) {
            $courseIds = array_filter(explode(',', $course));
            $courseIds = array_map('intval', $courseIds);
        }

        $locations = empty($locationsArray) ? ['Welisara', 'Moratuwa', 'Peradeniya'] : $locationsArray;

        // Build courses list to iterate (key => id) where key is course_name, value is course_id
        if ($course === 'all') {
            $courses = Course::pluck('course_id', 'course_name')->toArray();
        } else {
            // If multiple courses passed, fetch all of them
            $coursesQuery = Course::query();
            if (!empty($courseIds)) {
                $coursesQuery->whereIn('course_id', $courseIds);
            } else {
                $coursesQuery->where('course_id', $course);
            }
            $courses = $coursesQuery->pluck('course_id', 'course_name')->toArray();
        }

        $aggregate = [];

        // Pre-resolve numeric course id -> name mapping for bulk matching
        $courseIdToName = Course::pluck('course_name', 'course_id')->toArray();
        $periodBuckets = $this->buildPeriodBuckets($request);

        foreach ($periodBuckets as $bucket) {
            $y = $bucket['year'];
            $periodStart = $bucket['periodStart'];
            $periodEnd = $bucket['periodEnd'];

            foreach ($locations as $loc) {
                // --- 1) Bulk revenue rows for this period/location ---
                $bulkQ = DB::table('bulk_revenue_uploads')
                    ->where('year', $y)
                    ->where('location', $loc);

                if ($bucket['month']) {
                    $bulkQ->where('month', $bucket['month']);
                }
                if ($bucket['day']) {
                    $bulkQ->where('day', $bucket['day']);
                }

                // If frontend requested specific course, match either stored id or stored name
                if ($course !== 'all') {
                    $bulkQ->where(function ($q) use ($course, $courseIdToName) {
                        $q->where('course', $course);
                        $name = $courseIdToName[$course] ?? null;
                        if ($name)
                            $q->orWhere('course', $name);
                    });
                }

                $bulkRows = $bulkQ->get();

                foreach ($bulkRows as $r) {
                    $bulkCourseRaw = $r->course;
                    $courseNameOut = null;

                    if (is_numeric($bulkCourseRaw)) {
                        $courseNameOut = $courseIdToName[intval($bulkCourseRaw)] ?? (string) $bulkCourseRaw;
                    } elseif ($bulkCourseRaw) {
                        $courseNameOut = (string) $bulkCourseRaw;
                    } else {
                        if ($course !== 'all') {
                            $courseNameOut = Course::where('course_id', $course)->value('course_name') ?? (string) $course;
                        } else {
                            $courseNameOut = 'all';
                        }
                    }

                    if ($course !== 'all') {
                        $requestedCourseName = Course::where('course_id', $course)->value('course_name') ?? (string) $course;
                        if ($courseNameOut !== $requestedCourseName && (string) $r->course !== (string) $course) {
                            continue;
                        }
                    }

                    $key = "{$bucket['period']}|{$loc}|{$courseNameOut}";

                    if (!isset($aggregate[$key])) {
                        $aggregate[$key] = [
                            'year' => $y,
                            'month' => $bucket['month'],
                            'period' => $bucket['period'],
                            'label' => $bucket['label'],
                            'location' => $loc,
                            'course_name' => $courseNameOut,
                            'revenue' => 0.0
                        ];
                    }

                    $aggregate[$key]['revenue'] += floatval($r->revenue ?? 0);
                }

                // --- 2) PaymentDetail partials for this period/location/course ---
                foreach ($courses as $courseName => $courseId) {
                    $paymentQ = PaymentDetail::whereHas('student', function ($q) use ($loc) {
                        $q->where('institute_location', $loc);
                    });

                    $paymentQ->whereHas('registration', function ($q) use ($courseId) {
                        $q->where('course_id', $courseId);
                    });

                    $payments = $paymentQ->get();

                    foreach ($payments as $p) {
                        $contribution = $this->getPaymentContributionForPeriod($p, $periodStart, $periodEnd);
                        if ($contribution <= 0) {
                            continue;
                        }

                        $key = "{$bucket['period']}|{$loc}|{$courseName}";
                        if (!isset($aggregate[$key])) {
                            $aggregate[$key] = [
                                'year' => $y,
                                'month' => $bucket['month'],
                                'period' => $bucket['period'],
                                'label' => $bucket['label'],
                                'location' => $loc,
                                'course_name' => $courseName,
                                'revenue' => 0.0
                            ];
                        }
                        $aggregate[$key]['revenue'] += $contribution;
                    }
                }

            }
        }

        // Normalize output: ensure revenue rounded, and include entries for combinations with zero if needed
        $result = array_values(array_map(function ($item) {
            $item['revenue'] = round(floatval($item['revenue'] ?? 0), 2);
            return $item;
        }, $aggregate));

        return response()->json($result);
    }

    /**
     * Get students by location breakdown
     */
    public function getStudentsByLocation(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $data = Student::select('institute_location', DB::raw('count(*) as count'))
            ->whereYear('created_at', $year)
            ->groupBy('institute_location')
            ->get();

        return response()->json($data);
    }

    /**
     * Get outstanding data by year and location
     */

    public function getOutstandingByYearCourse(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');
        $day = $request->input('date');
        $location = $request->input('location', 'all');

        // new: accept course filter (comma separated ids or names)
        $course = $request->input('course', 'all');
        $courseIds = [];
        if ($course !== 'all' && !empty($course)) {
            $parts = array_filter(array_map('trim', explode(',', $course)));
            foreach ($parts as $p) {
                if (is_numeric($p)) {
                    $courseIds[] = (int) $p;
                } else {
                    // attempt to resolve name -> id
                    $id = Course::where('course_name', $p)->value('course_id');
                    if ($id)
                        $courseIds[] = (int) $id;
                }
            }
        }

        $fromYear = $request->input('from_year');
        $toYear = $request->input('to_year');
        $range = $request->input('range');
        $rangeStart = $request->input('range_start_year');
        $rangeEnd = $request->input('range_end_year');

        // Determine years to fetch
        if ($range && $rangeStart && $rangeEnd) {
            $years = range($rangeStart, $rangeEnd);
        } elseif ($fromYear && $toYear) {
            $years = range($fromYear, $toYear);
        } elseif ($year) {
            $years = [$year];
        } else {
            $years = [date('Y')];
        }

        // If range_start provided but not range_end (e.g. "future"), cap a sensible end
        if ($range && $rangeStart && empty($rangeEnd)) {
            $start = (int) $rangeStart;
            $end = $start + 20; // configurable horizon
            $years = range($start, $end);
        }

        // Get all locations
        $locations = $location === 'all'
            ? ['Welisara', 'Moratuwa', 'Peradeniya']
            : array_map('trim', explode(',', $location));

        // We'll aggregate outstanding by year|location|course_name
        $aggregate = [];

        foreach ($years as $y) {
            foreach ($locations as $loc) {
                $query = PaymentPlan::whereYear('created_at', $y)
                    ->where('location', $loc);

                if ($month) {
                    $query->whereMonth('created_at', $month);
                }
                if ($day) {
                    $query->whereDay('created_at', $day);
                }

                // apply course filter if provided
                if (!empty($courseIds)) {
                    $query->whereIn('course_id', $courseIds);
                }

                $plans = $query->get();

                foreach ($plans as $plan) {
                    // determine course name for this plan (via course_id)
                    $courseName = 'Unknown';
                    try {
                        $courseName = Course::where('course_id', $plan->course_id)->value('course_name') ?? (string) $plan->course_id;
                    } catch (\Throwable $ex) {
                        // swallow and keep 'Unknown'
                    }

                    // compute outstanding from future installments
                    $outstanding = 0;
                    if (is_array($plan->installments)) {
                        foreach ($plan->installments as $inst) {
                            $dueDate = Carbon::parse($inst['due_date']);
                            if ($dueDate->isAfter(Carbon::now())) {
                                $outstanding += ($inst['local_amount'] ?? 0);
                            }
                        }
                    }

                    // Respect course filter: if frontend passed course names/ids but course couldn't be resolved skip
                    if (!empty($courseIds)) {
                        if (!in_array((int) $plan->course_id, $courseIds, true)) {
                            continue;
                        }
                    }

                    $key = "{$y}|{$loc}|{$courseName}";
                    if (!isset($aggregate[$key])) {
                        $aggregate[$key] = [
                            'year' => (int) $y,
                            'location' => $loc,
                            'course_name' => $courseName,
                            'outstanding' => 0.0
                        ];
                    }
                    $aggregate[$key]['outstanding'] += $outstanding;
                }
            }
        }

        // Convert aggregate to response array (round outstanding)
        $data = [];
        foreach ($aggregate as $item) {
            $data[] = [
                'year' => $item['year'],
                'location' => $item['location'],
                'course_name' => $item['course_name'],
                'outstanding' => round($item['outstanding'], 2),
            ];
        }

        return response()->json($data);
    }
    /**
     * Helper method to build date filter
     */
    protected function getPaymentContributionForPeriod(PaymentDetail $payment, Carbon $periodStart, Carbon $periodEnd): float
    {
        $partials = $payment->partial_payments ?? [];
        if (is_string($partials)) {
            $partials = json_decode($partials, true) ?? [];
        }

        if (is_array($partials) && !empty($partials)) {
            $contribution = 0.0;
            foreach ($partials as $partial) {
                $dateValue = $partial['date'] ?? $partial['payment_date'] ?? $partial['paid_at'] ?? null;
                if (!$dateValue) {
                    $dateValue = $payment->payment_effective_date ?? $payment->created_at;
                }

                try {
                    $partialDate = Carbon::parse($dateValue);
                } catch (\Throwable $e) {
                    continue;
                }

                if ($partialDate->between($periodStart, $periodEnd)) {
                    $contribution += (float) ($partial['amount'] ?? 0);
                }
            }

            if ($contribution > 0) {
                return $contribution;
            }
        }

        $referenceDate = $payment->payment_effective_date
            ? Carbon::parse($payment->payment_effective_date)
            : ($payment->created_at ? Carbon::parse($payment->created_at) : null);

        if ($referenceDate && $referenceDate->between($periodStart, $periodEnd)) {
            return (float) ($payment->amount ?? $payment->total_fee ?? 0);
        }

        return 0.0;
    }

    private function buildDateFilter($year, $month = null, $day = null)
    {
        $date = Carbon::create($year, $month ?: 1, $day ?: 1);

        if ($day) {
            return [
                'start' => $date->startOfDay(),
                'end' => $date->endOfDay()
            ];
        } elseif ($month) {
            return [
                'start' => $date->startOfMonth(),
                'end' => $date->endOfMonth()
            ];
        } else {
            return [
                'start' => $date->startOfYear(),
                'end' => $date->endOfYear()
            ];
        }
    }

    public function getMarketingData(Request $request)
    {
        $year = $request->input('year', date('Y'));

        // Get counts for each marketing_survey type for the current year
        $data = \App\Models\Student::select('marketing_survey', DB::raw('COUNT(*) as count'))
            ->whereYear('created_at', $year)
            ->whereNotNull('marketing_survey')
            ->groupBy('marketing_survey')
            ->get();

        // Format for chart.js
        $labels = $data->pluck('marketing_survey')->toArray();
        $counts = $data->pluck('count')->toArray();

        return response()->json([
            'labels' => $labels,
            'counts' => $counts,
        ]);
    }

    public function downloadStudentTemplate()
    {
        $filename = 'student_bulk_template.xlsx';
        $path = 'templates/student_bulk_template.xlsx';

        if (Storage::exists($path)) {
            return Storage::download($path, $filename);
        }

        // Fallback: stream a CSV-compatible template if xlsx missing
        $callback = function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Year', 'Month', 'Day', 'Location', 'Course', 'Student_Count']);
            // include example row
            fputcsv($out, [date('Y'), '', '', 'Welisara', '', 0]);
            fclose($out);
        };

        return response()->streamDownload($callback, 'student_bulk_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function downloadRevenueTemplate()
    {
        $filename = 'revenue_bulk_template.xlsx';
        $path = 'templates/revenue_bulk_template.xlsx';

        if (Storage::exists($path)) {
            return Storage::download($path, $filename);
        }

        $callback = function () {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Year', 'Month', 'Day', 'Location', 'Course', 'Revenue']);
            fputcsv($out, [date('Y'), '', '', 'Welisara', '', 0.00]);
            fclose($out);
        };

        return response()->streamDownload($callback, 'revenue_bulk_template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function bulkStudentUpload(Request $request)
    {
        // allow common Excel/CSV variants and text csv; provide JSON-friendly messages for AJAX
        $rules = [
            'student_excel' => ['required', 'file', 'mimes:xlsx,xls,csv,txt,xlsm', 'max:51200']
        ];
        $messages = [
            'student_excel.required' => 'Please choose a file to upload.',
            'student_excel.file' => 'Uploaded item must be a file.',
            'student_excel.mimes' => 'Allowed file types: xlsx, xls, xlsm, csv, txt.',
            'student_excel.max' => 'File too large (max 50MB).'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $file = $request->file('student_excel');
        $inserted = 0;

        try {
            $sheets = Excel::toArray(null, $file);
            if (empty($sheets) || !isset($sheets[0])) {
                throw new \Exception('Uploaded file contains no sheets/rows.');
            }

            $rows = $sheets[0];
            foreach ($rows as $i => $row) {
                if ($i == 0)
                    continue; // skip header
                $year = $row[0] ?? null;
                $location = $row[3] ?? null;
                $count = $row[5] ?? null;
                if (!$year || !$location || !is_numeric($count))
                    continue;

                \DB::table('bulk_student_uploads')->insert(array_merge([
                    'year' => (int) $year,
                    'month' => $row[1] ?? null,
                    'day' => $row[2] ?? null,
                    'location' => $location,
                    'course' => $row[4] ?? null,
                    'student_count' => (int) $count,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], \App\Support\UserTrackingData::forCreate()));
                $inserted++;
            }
        } catch (\Throwable $e) {
            Log::error('bulkStudentUpload error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Upload failed', 'detail' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'inserted' => $inserted]);
        }

        return back()->with('success', 'Student bulk data uploaded! Inserted: ' . $inserted);
    }

    public function bulkRevenueUpload(Request $request)
    {
        $rules = [
            'revenue_excel' => ['required', 'file', 'mimes:xlsx,xls,csv,txt,xlsm', 'max:51200']
        ];
        $messages = [
            'revenue_excel.required' => 'Please choose a file to upload.',
            'revenue_excel.file' => 'Uploaded item must be a file.',
            'revenue_excel.mimes' => 'Allowed file types: xlsx, xls, xlsm, csv, txt.',
            'revenue_excel.max' => 'File too large (max 50MB).'
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Validation failed', 'errors' => $validator->errors()], 422);
            }
            return back()->withErrors($validator)->withInput();
        }

        $file = $request->file('revenue_excel');
        $inserted = 0;

        try {
            $sheets = Excel::toArray(null, $file);
            if (empty($sheets) || !isset($sheets[0])) {
                throw new \Exception('Uploaded file contains no sheets/rows.');
            }

            $rows = $sheets[0];
            foreach ($rows as $i => $row) {
                if ($i == 0)
                    continue;
                $year = $row[0] ?? null;
                $location = $row[3] ?? null;
                $revenue = $row[5] ?? null;
                if (!$year || !$location || !is_numeric($revenue))
                    continue;

                \DB::table('bulk_revenue_uploads')->insert(array_merge([
                    'year' => (int) $year,
                    'month' => $row[1] ?? null,
                    'day' => $row[2] ?? null,
                    'location' => $location,
                    'course' => $row[4] ?? null,
                    'revenue' => floatval($revenue),
                    'created_at' => now(),
                    'updated_at' => now(),
                ], \App\Support\UserTrackingData::forCreate()));
                $inserted++;
            }
        } catch (\Throwable $e) {
            Log::error('bulkRevenueUpload error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            if ($request->expectsJson()) {
                return response()->json(['success' => false, 'message' => 'Upload failed', 'detail' => $e->getMessage()], 500);
            }
            return back()->with('error', 'Upload failed: ' . $e->getMessage());
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'inserted' => $inserted]);
        }

        return back()->with('success', 'Revenue bulk data uploaded! Inserted: ' . $inserted);
    }

    // New: export stored bulk student uploads as CSV
    public function exportStudentBulkData()
    {
        $rows = \DB::table('bulk_student_uploads')->orderBy('year')->get();
        $filename = 'bulk_students_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Year', 'Month', 'Day', 'Location', 'Course', 'Student_Count', 'Created_At']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->year,
                    $r->month,
                    $r->day,
                    $r->location,
                    $r->course,
                    $r->student_count,
                    $r->created_at
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    // New: export stored bulk revenue uploads as CSV
    public function exportRevenueBulkData()
    {
        $rows = \DB::table('bulk_revenue_uploads')->orderBy('year')->get();
        $filename = 'bulk_revenues_' . now()->format('Ymd_His') . '.csv';

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Year', 'Month', 'Day', 'Location', 'Course', 'Revenue', 'Created_At']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->year,
                    $r->month,
                    $r->day,
                    $r->location,
                    $r->course,
                    $r->revenue,
                    $r->created_at
                ]);
            }
            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Get future projections based on historical data
     */
    public function getFutureProjections()
    {
        try {
            // Calculate projection based on recent 3 years average growth
            $currentYear = (int) date('Y');
            $years = [$currentYear - 2, $currentYear - 1, $currentYear];
            
            $yearlyRevenue = [];
            foreach ($years as $year) {
                // Get bulk revenue
                $bulkRev = DB::table('bulk_revenue_uploads')
                    ->where('year', $year)
                    ->sum('revenue');
                
                // Get partial payments revenue
                $partialRev = 0.0;
                $payments = PaymentDetail::whereYear('created_at', $year)->get();

                foreach ($payments as $payment) {
                    $partialRev += $this->getPaymentContributionForPeriod($payment, Carbon::create($year, 1, 1)->startOfYear(), Carbon::create($year, 12, 31)->endOfYear());
                }
                
                $yearlyRevenue[$year] = $bulkRev + $partialRev;
            }
            
            // Calculate average growth rate
            $growthRates = [];
            for ($i = 1; $i < count($years); $i++) {
                $prevYear = $years[$i - 1];
                $currYear = $years[$i];
                if ($yearlyRevenue[$prevYear] > 0) {
                    $growth = (($yearlyRevenue[$currYear] - $yearlyRevenue[$prevYear]) / $yearlyRevenue[$prevYear]);
                    $growthRates[] = $growth;
                }
            }
            
            $avgGrowthRate = !empty($growthRates) ? array_sum($growthRates) / count($growthRates) : 0.05; // default 5%
            
            // Project next 3 years
            $projections = [];
            $baseRevenue = $yearlyRevenue[$currentYear] ?? 0;
            
            for ($i = 1; $i <= 3; $i++) {
                $projectedYear = $currentYear + $i;
                $projectedRevenue = $baseRevenue * pow(1 + $avgGrowthRate, $i);
                
                $projections[] = [
                    'year' => $projectedYear,
                    'projected_revenue' => round($projectedRevenue, 2),
                    'growth_rate' => round($avgGrowthRate * 100, 2)
                ];
            }
            
            return response()->json([
                'success' => true,
                'historical_data' => $yearlyRevenue,
                'projections' => $projections,
                'avg_growth_rate' => round($avgGrowthRate * 100, 2) . '%'
            ]);
            
        } catch (\Exception $e) {
            Log::error('getFutureProjections error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate projections',
                'projections' => []
            ], 500);
        }
    }

    /**
     * Get revenue data (simplified wrapper)
     */
    public function getRevenueData(Request $request)
    {
        return $this->getRevenueByYearCourse($request);
    }

    /**
     * Get revenue by location
     */
    public function getRevenueByLocation(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $locations = ['Welisara', 'Moratuwa', 'Peradeniya'];
        
        $data = [];
        foreach ($locations as $location) {
            // Bulk revenue
            $bulkRevenue = DB::table('bulk_revenue_uploads')
                ->where('year', $year)
                ->where('location', $location)
                ->sum('revenue');
            
            // Partial payments
            $partialRevenue = 0.0;
            $payments = PaymentDetail::whereHas('student', function ($q) use ($location) {
                $q->where('institute_location', $location);
            })
            ->whereYear('created_at', $year)
            ->get();

            foreach ($payments as $payment) {
                $partialRevenue += $this->getPaymentContributionForPeriod($payment, Carbon::create($year, 1, 1)->startOfYear(), Carbon::create($year, 12, 31)->endOfYear());
            }
            
            $data[] = [
                'location' => $location,
                'revenue' => round($bulkRevenue + $partialRevenue, 2)
            ];
        }
        
        return response()->json($data);
    }

    /**
     * Get payment status breakdown
     */
    public function getPaymentStatus(Request $request)
    {
        $year = $request->input('year', date('Y'));
        
        // Get total expected revenue
        $totalPlans = PaymentPlan::whereYear('created_at', $year)->get();
        $totalExpected = 0;
        $totalPaid = 0;
        $totalOutstanding = 0;
        
        foreach ($totalPlans as $plan) {
            if (is_array($plan->installments)) {
                foreach ($plan->installments as $inst) {
                    $amount = $inst['local_amount'] ?? 0;
                    $totalExpected += $amount;
                    
                    $dueDate = Carbon::parse($inst['due_date']);
                    if ($dueDate->isPast()) {
                        if (isset($inst['paid']) && $inst['paid']) {
                            $totalPaid += $amount;
                        } else {
                            $totalOutstanding += $amount;
                        }
                    }
                }
            }
        }
        
        return response()->json([
            'total_expected' => round($totalExpected, 2),
            'total_paid' => round($totalPaid, 2),
            'total_outstanding' => round($totalOutstanding, 2),
            'payment_rate' => $totalExpected > 0 ? round(($totalPaid / $totalExpected) * 100, 2) : 0
        ]);
    }

    /**
     * Get monthly revenue trend
     */
    public function getMonthlyRevenueTrend(Request $request)
    {
        $year = $request->input('year', date('Y'));
        $months = [];
        
        for ($month = 1; $month <= 12; $month++) {
            // Bulk revenue for month
            $bulkRevenue = DB::table('bulk_revenue_uploads')
                ->where('year', $year)
                ->where('month', $month)
                ->sum('revenue');
            
            // Partial payments for month
            $partialRevenue = 0.0;
            $payments = PaymentDetail::whereYear('created_at', $year)
                ->whereMonth('created_at', $month)
                ->get();

            foreach ($payments as $payment) {
                $partialRevenue += $this->getPaymentContributionForPeriod($payment, Carbon::create($year, $month, 1)->startOfMonth(), Carbon::create($year, $month, 1)->endOfMonth());
            }
            
            $months[] = [
                'month' => $month,
                'month_name' => Carbon::create($year, $month, 1)->format('M'),
                'revenue' => round($bulkRevenue + $partialRevenue, 2)
            ];
        }
        
        return response()->json($months);
    }

    /**
     * Build compare / range / single-period buckets so month filters are applied
     * instead of collapsing everything into a full year.
     *
     * Compare: exactly two periods (from and to), each using its own month when set.
     * Range: each month in the inclusive span when any month is set; otherwise each year.
     * Single: the selected year, optionally month and day.
     */
    private function buildPeriodBuckets(Request $request): array
    {
        $monthNames = [1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Aug', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dec'];

        $makeBucket = function ($year, $month = null, $day = null) use ($monthNames) {
            $year = (int) $year;
            $monthInt = ($month !== null && $month !== '' && is_numeric($month)) ? (int) $month : null;
            $dayInt = ($day !== null && $day !== '' && is_numeric($day)) ? (int) $day : null;

            if ($monthInt && $dayInt) {
                $periodStart = Carbon::create($year, $monthInt, $dayInt)->startOfDay();
                $periodEnd = $periodStart->copy()->endOfDay();
                $label = sprintf('%02d %s %d', $dayInt, $monthNames[$monthInt], $year);
                $period = sprintf('%d-%02d-%02d', $year, $monthInt, $dayInt);
            } elseif ($monthInt) {
                $periodStart = Carbon::create($year, $monthInt, 1)->startOfMonth();
                $periodEnd = $periodStart->copy()->endOfMonth();
                $label = $monthNames[$monthInt] . ' ' . $year;
                $period = sprintf('%d-%02d', $year, $monthInt);
            } else {
                $periodStart = Carbon::create($year, 1, 1)->startOfYear();
                $periodEnd = Carbon::create($year, 12, 31)->endOfYear();
                $label = (string) $year;
                $period = (string) $year;
            }

            return [
                'year' => $year,
                'month' => $monthInt,
                'day' => $dayInt,
                'period' => $period,
                'label' => $label,
                'periodStart' => $periodStart,
                'periodEnd' => $periodEnd,
            ];
        };

        $compareMode = $request->boolean('compare');
        $rangeMode = $request->boolean('range');

        $fromYear = $request->input('from_year') ?? $request->input('range_start_year') ?? $request->input('from');
        $toYear = $request->input('to_year') ?? $request->input('range_end_year') ?? $request->input('to');
        $fromYearInt = is_numeric($fromYear) ? (int) $fromYear : null;
        $toYearInt = is_numeric($toYear) ? (int) $toYear : null;

        if ($compareMode && $fromYearInt && $toYearInt) {
            return [
                $makeBucket($fromYearInt, $request->input('from_month') ?: null),
                $makeBucket($toYearInt, $request->input('to_month') ?: null),
            ];
        }

        if ($rangeMode && $fromYearInt && $toYearInt) {
            $startMonth = $request->input('range_start_month') ?: null;
            $endMonth = $request->input('range_end_month') ?: null;

            if ($startMonth || $endMonth) {
                $cursor = Carbon::create($fromYearInt, $startMonth ? (int) $startMonth : 1, 1)->startOfMonth();
                $end = Carbon::create($toYearInt, $endMonth ? (int) $endMonth : 12, 1)->startOfMonth();
                $buckets = [];
                while ($cursor->lte($end)) {
                    $buckets[] = $makeBucket($cursor->year, $cursor->month);
                    $cursor->addMonth();
                }
                return $buckets;
            }

            $buckets = [];
            $startY = min($fromYearInt, $toYearInt);
            $endY = max($fromYearInt, $toYearInt);
            for ($y = $startY; $y <= $endY; $y++) {
                $buckets[] = $makeBucket($y);
            }
            return $buckets;
        }

        $year = $request->input('year');
        $month = $request->input('month');
        $day = $request->input('date');

        if ($year === 'all') {
            $bulkMin = DB::table('bulk_student_uploads')->min('year');
            $bulkMax = DB::table('bulk_student_uploads')->max('year');
            $regMin = CourseRegistration::min(DB::raw('YEAR(created_at)'));
            $regMax = CourseRegistration::max(DB::raw('YEAR(created_at)'));
            $candidates = array_filter([
                $bulkMin ? (int) $bulkMin : null,
                $bulkMax ? (int) $bulkMax : null,
                $regMin ? (int) $regMin : null,
                $regMax ? (int) $regMax : null,
            ]);
            if (empty($candidates)) {
                return [$makeBucket((int) date('Y'))];
            }
            $buckets = [];
            for ($y = min($candidates); $y <= max($candidates); $y++) {
                $buckets[] = $makeBucket($y);
            }
            return $buckets;
        }

        $yearInt = is_numeric($year) ? (int) $year : (int) date('Y');
        return [$makeBucket($yearInt, $month ?: null, $day ?: null)];
    }
}