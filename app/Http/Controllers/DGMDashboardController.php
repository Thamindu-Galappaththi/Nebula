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
            $this->constrainStudentCampus($studentsQuery, $location);
        }
        $totalStudents = $studentsQuery->count();

        // Calculate Revenue from both sources for current year
        // 1. From bulk_revenue_uploads
        $bulkRevenueQuery = DB::table('bulk_revenue_uploads')
            ->where('year', $year);

        if ($location !== 'all') {
            $this->constrainCampusColumn($bulkRevenueQuery, 'location', $location);
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
            $this->constrainPaymentToCampus($paymentBaseQuery, $location);
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
        $prevBulkRevenueQuery = DB::table('bulk_revenue_uploads')
            ->where('year', $prevYear)
            ->when($course !== 'all', fn($q) => $q->where('course', $course))
            ->when($month, fn($q) => $q->where('month', $month))
            ->when($day, fn($q) => $q->where('day', $day));
        if ($location !== 'all') {
            $this->constrainCampusColumn($prevBulkRevenueQuery, 'location', $location);
        }
        $prevBulkRevenue = $prevBulkRevenueQuery->sum('revenue');

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

        $outstanding = $this->unpaidInstallmentTotal($location, $course);

        $outstandingCurrentYear = 0.0;
        try {
            // Remaining amounts still owed on installments due in the selected year.
            // Do not subtract yearly collections: those include payments for other years
            // and already-paid rows, which made this KPI go negative.
            $dueQuery = PaymentInstallment::query()
                ->whereYear('due_date', $year)
                ->whereIn('status', ['pending', 'overdue']);

            if ($month) {
                $dueQuery->whereMonth('due_date', $month);
            }
            if ($day) {
                $dueQuery->whereDay('due_date', $day);
            }
            if ($location !== 'all') {
                $dueQuery->whereHas('paymentPlan.student', function ($q) use ($location) {
                    $this->constrainStudentCampus($q, $location);
                });
            }
            if ($course !== 'all') {
                $dueQuery->whereHas('paymentPlan', function ($q) use ($course) {
                    $q->where('course_id', $course);
                });
            }

            $outstandingCurrentYear = (float) $dueQuery->sum(DB::raw('COALESCE(final_amount, amount)'));
        } catch (\Throwable $ex) {
            Log::warning('Could not compute outstandingCurrentYear: ' . $ex->getMessage());
            $outstandingCurrentYear = 0.0;
        }

        // Location Summary with both revenue sources
        $locations = ['Welisara', 'Moratuwa', 'Peradeniya'];
        $locationSummary = [];

        foreach ($locations as $loc) {
            $currBulkQuery = DB::table('bulk_revenue_uploads')
                ->where('year', $year)
                ->when($course !== 'all', fn($q) => $q->where('course', $course))
                ->when($month, fn($q) => $q->where('month', $month))
                ->when($day, fn($q) => $q->where('day', $day));
            $this->constrainCampusColumn($currBulkQuery, 'location', $loc);
            $currBulkRev = $currBulkQuery->sum('revenue');

            $locPaymentsQuery = PaymentDetail::query();
            $this->constrainPaymentToCampus($locPaymentsQuery, $loc);
            if ($course !== 'all') {
                $locPaymentsQuery->whereHas('registration.course', fn($qq) => $qq->where('course_id', $course));
            }
            $locPayments = $locPaymentsQuery->get();

            $currPartialRev = 0.0;
            foreach ($locPayments as $p) {
                $currPartialRev += $this->getPaymentContributionForPeriod($p, $dateFilter['start'], $dateFilter['end']);
            }

            $prevBulkQuery = DB::table('bulk_revenue_uploads')
                ->where('year', $prevYear)
                ->when($course !== 'all', fn($q) => $q->where('course', $course))
                ->when($month, fn($q) => $q->where('month', $month))
                ->when($day, fn($q) => $q->where('day', $day));
            $this->constrainCampusColumn($prevBulkQuery, 'location', $loc);
            $prevBulkRev = $prevBulkQuery->sum('revenue');

            $prevPartialRev = 0.0;
            foreach ($locPayments as $p) {
                $prevPartialRev += $this->getPaymentContributionForPeriod($p, $prevDateFilter['start'], $prevDateFilter['end']);
            }

            $currTotal = $currBulkRev + $currPartialRev;
            $prevTotal = $prevBulkRev + $prevPartialRev;
            $growth = $prevTotal > 0 ? round((($currTotal - $prevTotal) / $prevTotal) * 100, 1) : 0;

            $locationSummary[] = [
                'location' => $loc,
                'current_year' => number_format($currTotal, 2),
                'previous_year' => number_format($prevTotal, 2),
                'growth' => $growth,
                'outstanding' => number_format($this->unpaidInstallmentTotal($loc, $course), 2),
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

        $locations = $this->requestedCampuses($location);
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

            foreach ($locations as $loc) {
                $bulkQuery = DB::table('bulk_student_uploads')->where('year', $y);
                $this->constrainCampusColumn($bulkQuery, 'location', $loc);

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
                    $key = "{$bucket['period']}|{$loc}|{$c}";
                    if (!isset($aggregate[$key])) {
                        $aggregate[$key] = [
                            'year' => $y,
                            'month' => $month,
                            'period' => $bucket['period'],
                            'label' => $bucket['label'],
                            'institute_location' => $loc,
                            'course' => $c,
                            'count' => 0
                        ];
                    }
                    $aggregate[$key]['count'] += (int) ($row->student_count ?? 0);
                }
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

                    $regQuery = Student::query();
                    $this->constrainStudentCampus($regQuery, $loc);
                    $regQuery->whereHas('courseRegistrations', function ($q) use ($courseId, $bucket) {
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
        $locations = $this->requestedCampuses($request->input('location', 'all'));
        $courseIds = $this->requestedCourseIds($request->input('course', 'all'));
        $courseIdToName = Course::pluck('course_name', 'course_id')->toArray();
        $periodBuckets = $this->buildPeriodBuckets($request);
        $aggregate = [];

        foreach ($periodBuckets as $bucket) {
            $periodStart = $bucket['periodStart'];
            $periodEnd = $bucket['periodEnd'];

            foreach ($locations as $loc) {
                $bulkQ = DB::table('bulk_revenue_uploads')->where('year', $bucket['year']);
                $this->constrainCampusColumn($bulkQ, 'location', $loc);

                if ($bucket['month']) {
                    $bulkQ->where('month', $bucket['month']);
                }
                if ($bucket['day']) {
                    $bulkQ->where('day', $bucket['day']);
                }
                $this->constrainBulkCourseColumn($bulkQ, $courseIds, $courseIdToName);

                foreach ($bulkQ->get() as $r) {
                    $bulkCourseRaw = $r->course;
                    if (is_numeric($bulkCourseRaw)) {
                        $courseNameOut = $courseIdToName[(int) $bulkCourseRaw] ?? (string) $bulkCourseRaw;
                    } elseif ($bulkCourseRaw) {
                        $courseNameOut = (string) $bulkCourseRaw;
                    } else {
                        $courseNameOut = !empty($courseIds)
                            ? ($courseIdToName[$courseIds[0]] ?? 'all')
                            : 'all';
                    }

                    $this->addPeriodAggregate($aggregate, $bucket, $loc, $courseNameOut, (float) ($r->revenue ?? 0));
                }

                $paymentQ = PaymentDetail::query()->with(['registration.course']);
                $this->constrainPaymentToCampus($paymentQ, $loc);
                if (!empty($courseIds)) {
                    $paymentQ->whereHas('registration', function ($q) use ($courseIds) {
                        $q->whereIn('course_id', $courseIds);
                    });
                }

                foreach ($paymentQ->get() as $p) {
                    $contribution = $this->getPaymentContributionForPeriod($p, $periodStart, $periodEnd);
                    if ($contribution <= 0) {
                        continue;
                    }

                    $courseNameOut = optional(optional($p->registration)->course)->course_name
                        ?: 'Miscellaneous';

                    $this->addPeriodAggregate($aggregate, $bucket, $loc, $courseNameOut, $contribution);
                }
            }
        }

        $result = array_values(array_map(function ($item) {
            $item['revenue'] = round((float) ($item['revenue'] ?? 0), 2);
            return $item;
        }, $aggregate));

        return response()->json($result);
    }

    /**
     * Get students by location breakdown
     */
    public function getStudentsByLocation(Request $request)
    {
        $year = $request->input('year');
        $query = Student::query();

        if (!empty($year) && $year !== 'all' && is_numeric($year)) {
            $query->whereYear('created_at', $year);
        }

        $counts = ['Welisara' => 0, 'Moratuwa' => 0, 'Peradeniya' => 0];
        foreach ($query->select('institute_location', DB::raw('count(*) as count'))
            ->groupBy('institute_location')
            ->get() as $row) {
            $campus = $this->campusShortName((string) $row->institute_location);
            if (isset($counts[$campus])) {
                $counts[$campus] += (int) $row->count;
            }
        }

        $data = [];
        foreach ($counts as $loc => $count) {
            $data[] = [
                'institute_location' => $loc,
                'count' => $count,
            ];
        }

        return response()->json($data);
    }

    /**
     * Get outstanding data by year and location
     */

    public function getOutstandingByYearCourse(Request $request)
    {
        $locations = $this->requestedCampuses($request->input('location', 'all'));
        $courseIds = $this->requestedCourseIds($request->input('course', 'all'));

        $query = PaymentInstallment::query()
            ->whereIn('status', ['pending', 'overdue'])
            ->with(['paymentPlan.student', 'paymentPlan.course']);

        if (!empty($courseIds)) {
            $query->whereHas('paymentPlan', function ($q) use ($courseIds) {
                $q->whereIn('course_id', $courseIds);
            });
        }

        $aggregate = [];
        foreach ($query->get() as $installment) {
            $plan = $installment->paymentPlan;
            if (!$plan) {
                continue;
            }

            $campus = $this->campusShortName((string) optional($plan->student)->institute_location);
            if (!in_array($campus, $locations, true)) {
                continue;
            }

            $courseName = optional($plan->course)->course_name ?: 'Unknown';
            $key = "{$campus}|{$courseName}";
            if (!isset($aggregate[$key])) {
                $aggregate[$key] = [
                    'year' => (int) ($installment->due_date?->format('Y') ?? date('Y')),
                    'location' => $campus,
                    'course_name' => $courseName,
                    'outstanding' => 0.0,
                ];
            }

            $aggregate[$key]['outstanding'] += (float) ($installment->final_amount ?? $installment->amount ?? 0);
        }

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
                    $dateValue = $payment->payment_effective_date
                        ?? $payment->payment_date
                        ?? $payment->created_at;
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

        $referenceDateValue = $payment->payment_effective_date
            ?? $payment->payment_date
            ?? $payment->created_at;
        $referenceDate = $referenceDateValue ? Carbon::parse($referenceDateValue) : null;

        if ($referenceDate && $referenceDate->between($periodStart, $periodEnd)) {
            return (float) ($payment->amount ?? $payment->total_fee ?? 0);
        }

        return 0.0;
    }

    private function buildDateFilter($year, $month = null, $day = null)
    {
        $date = Carbon::create((int) $year, $month ? (int) $month : 1, $day ? (int) $day : 1);

        // Carbon is mutable: startOf*/endOf* would otherwise both point at the
        // same instance, collapsing a year filter to Dec 31 and zeroing revenue.
        if ($day) {
            return [
                'start' => $date->copy()->startOfDay(),
                'end' => $date->copy()->endOfDay(),
            ];
        }

        if ($month) {
            return [
                'start' => $date->copy()->startOfMonth(),
                'end' => $date->copy()->endOfMonth(),
            ];
        }

        return [
            'start' => $date->copy()->startOfYear(),
            'end' => $date->copy()->endOfYear(),
        ];
    }

    private function campusShortName(string $location): string
    {
        $value = trim(str_replace(
            ['Nebula Institute of Technology – ', 'Nebula Institute of Technology - '],
            '',
            $location
        ));

        foreach (['Welisara', 'Moratuwa', 'Peradeniya'] as $campus) {
            if (stripos($value, $campus) !== false) {
                return $campus;
            }
        }

        return $value !== '' ? $value : $location;
    }

    private function campusLocationValues(string $location): array
    {
        $short = $this->campusShortName($location);

        return array_values(array_unique(array_filter([
            $short,
            'Nebula Institute of Technology - ' . $short,
            'Nebula Institute of Technology – ' . $short,
            $location,
        ])));
    }

    private function constrainCampusColumn($query, string $column, string $campus): void
    {
        $values = $this->campusLocationValues($campus);
        $short = $this->campusShortName($campus);

        $query->where(function ($q) use ($column, $values, $short) {
            $q->whereIn($column, $values)
                ->orWhere($column, 'like', '%' . $short . '%');
        });
    }

    private function constrainStudentCampus($query, string $campus): void
    {
        $this->constrainCampusColumn($query, 'institute_location', $campus);
    }

    private function constrainPaymentToCampus($query, string $campus): void
    {
        $values = $this->campusLocationValues($campus);
        $short = $this->campusShortName($campus);

        $query->where(function ($q) use ($values, $short) {
            $q->whereHas('student', function ($s) use ($values, $short) {
                $s->where(function ($inner) use ($values, $short) {
                    $inner->whereIn('institute_location', $values)
                        ->orWhere('institute_location', 'like', '%' . $short . '%');
                });
            })->orWhereHas('registration', function ($r) use ($values, $short) {
                $r->where(function ($inner) use ($values, $short) {
                    $inner->whereIn('location', $values)
                        ->orWhere('location', 'like', '%' . $short . '%');
                });
            });
        });
    }

    private function unpaidInstallmentTotal(string $location = 'all', string $course = 'all'): float
    {
        $query = PaymentInstallment::query()->whereIn('status', ['pending', 'overdue']);

        if ($location !== 'all') {
            $query->whereHas('paymentPlan.student', function ($q) use ($location) {
                $this->constrainStudentCampus($q, $location);
            });
        }

        if ($course !== 'all') {
            $query->whereHas('paymentPlan', function ($q) use ($course) {
                $q->where('course_id', $course);
            });
        }

        return (float) $query->sum(DB::raw('COALESCE(final_amount, amount)'));
    }

    private function requestedCampuses($location): array
    {
        $all = ['Welisara', 'Moratuwa', 'Peradeniya'];
        if ($location === 'all' || $location === null || $location === '') {
            return $all;
        }

        $resolved = [];
        foreach (array_filter(array_map('trim', explode(',', (string) $location))) as $part) {
            if ($part === '' || $part === 'all') {
                continue;
            }
            $short = $this->campusShortName($part);
            if (in_array($short, $all, true)) {
                $resolved[] = $short;
            }
        }

        return empty($resolved) ? $all : array_values(array_unique($resolved));
    }

    private function requestedCourseIds($course): array
    {
        if ($course === 'all' || $course === null || $course === '') {
            return [];
        }

        $ids = [];
        foreach (array_filter(array_map('trim', explode(',', (string) $course))) as $part) {
            if (is_numeric($part)) {
                $ids[] = (int) $part;
                continue;
            }
            $id = Course::where('course_name', $part)->value('course_id');
            if ($id) {
                $ids[] = (int) $id;
            }
        }

        return array_values(array_unique($ids));
    }

    private function constrainBulkCourseColumn($query, array $courseIds, array $courseIdToName): void
    {
        if (empty($courseIds)) {
            return;
        }

        $names = [];
        $idStrings = [];
        foreach ($courseIds as $id) {
            $idStrings[] = (string) $id;
            if (!empty($courseIdToName[$id])) {
                $names[] = $courseIdToName[$id];
            }
        }

        $query->where(function ($q) use ($courseIds, $idStrings, $names) {
            $q->whereIn('course', $courseIds)
                ->orWhereIn('course', $idStrings);
            if ($names) {
                $q->orWhereIn('course', $names);
            }
        });
    }

    private function addPeriodAggregate(array &$aggregate, array $bucket, string $location, string $courseName, float $amount): void
    {
        if ($amount == 0.0) {
            return;
        }

        $key = "{$bucket['period']}|{$location}|{$courseName}";
        if (!isset($aggregate[$key])) {
            $aggregate[$key] = [
                'year' => $bucket['year'],
                'month' => $bucket['month'],
                'period' => $bucket['period'],
                'label' => $bucket['label'],
                'location' => $location,
                'course_name' => $courseName,
                'revenue' => 0.0,
            ];
        }

        $aggregate[$key]['revenue'] += $amount;
    }

    public function getMarketingData(Request $request)
    {
        $year = $request->input('year', date('Y'));

        $data = \App\Models\Student::query()
            ->select('marketing_survey', DB::raw('COUNT(*) as count'))
            ->whereYear('created_at', $year)
            ->whereNotNull('marketing_survey')
            ->where('marketing_survey', '!=', '')
            ->groupBy('marketing_survey')
            ->get();

        $flattened = [];
        foreach ($data as $item) {
            foreach ($this->splitMarketingSources($item->marketing_survey) as $source) {
                $flattened[$source] = ($flattened[$source] ?? 0) + (int) $item->count;
            }
        }

        arsort($flattened);

        return response()->json([
            'labels' => array_values(array_keys($flattened)),
            'counts' => array_values($flattened),
        ]);
    }

    private function splitMarketingSources(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value)), function ($source) {
            return $source !== '';
        }));
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
            $bulkQuery = DB::table('bulk_revenue_uploads')->where('year', $year);
            $this->constrainCampusColumn($bulkQuery, 'location', $location);
            $bulkRevenue = $bulkQuery->sum('revenue');

            $partialRevenue = 0.0;
            $paymentsQuery = PaymentDetail::query();
            $this->constrainPaymentToCampus($paymentsQuery, $location);
            $payments = $paymentsQuery->get();

            $periodStart = Carbon::create($year, 1, 1)->startOfYear();
            $periodEnd = Carbon::create($year, 12, 31)->endOfYear();
            foreach ($payments as $payment) {
                $partialRevenue += $this->getPaymentContributionForPeriod($payment, $periodStart, $periodEnd);
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
            $fromBucket = $makeBucket($fromYearInt, $request->input('from_month') ?: null);
            $toBucket = $makeBucket($toYearInt, $request->input('to_month') ?: null);

            if ($fromBucket['period'] === $toBucket['period']) {
                return [$fromBucket];
            }

            return [$fromBucket, $toBucket];
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