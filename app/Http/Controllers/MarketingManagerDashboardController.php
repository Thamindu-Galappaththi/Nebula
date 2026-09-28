<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\CourseRegistration;
use Carbon\Carbon;

class MarketingManagerDashboardController extends Controller
{
    public function showDashboard()
    {
        $user = Auth::user();

        return view('dashboards.marketing_manager', compact('user'));
    }

    public function getOverviewMetrics(Request $request)
    {
        $period = $request->input('period', 'month');
        $customDate = $request->input('date');
        $dateRange = $this->getDateRange($period, $customDate);
        $previousRange = $this->getPreviousDateRange($dateRange['start'], $dateRange['end']);

        $totalRegisteredStudents = CourseRegistration::count();
        $totalStudents = Student::where('academic_status', Student::ACADEMIC_ACTIVE)->count();
        $periodRegistrations = $this->registrationsInRange($dateRange['start'], $dateRange['end'])->count();
        $previousPeriodRegistrations = $this->registrationsInRange($previousRange['start'], $previousRange['end'])->count();
        $thisMonthRegistrations = $this->registrationsInRange(
            Carbon::now()->startOfMonth()->startOfDay(),
            Carbon::now()->endOfMonth()->endOfDay()
        )->count();
        $lastMonthRegistrations = $this->registrationsInRange(
            Carbon::now()->subMonth()->startOfMonth()->startOfDay(),
            Carbon::now()->subMonth()->endOfMonth()->endOfDay()
        )->count();

        $growthPercentage = 0;
        if ($previousPeriodRegistrations > 0) {
            $growthPercentage = round((($periodRegistrations - $previousPeriodRegistrations) / $previousPeriodRegistrations) * 100, 1);
        } elseif ($periodRegistrations > 0) {
            $growthPercentage = 100;
        }

        $daysInPeriod = max(1, $dateRange['start']->copy()->startOfDay()->diffInDays($dateRange['end']->copy()->endOfDay()) + 1);
        $avgDaily = round($periodRegistrations / $daysInPeriod, 1);

        $bestSource = collect($this->surveyCounts($dateRange))->sortByDesc('count')->first();
        $topLocation = $this->locationCounts($dateRange)->sortByDesc('count')->first();

        $uniqueRegisteredStudents = $this->registrationsInRange($dateRange['start'], $dateRange['end'])
            ->distinct('student_id')
            ->count('student_id');
        $periodNewStudents = Student::query()
            ->whereRaw('DATE(created_at) BETWEEN ? AND ?', [
                $dateRange['start']->toDateString(),
                $dateRange['end']->toDateString(),
            ])
            ->count();
        $conversionRate = $periodNewStudents > 0
            ? round(($uniqueRegisteredStudents / $periodNewStudents) * 100, 1)
            : ($uniqueRegisteredStudents > 0 ? 100 : 0);

        return response()->json([
            'total_registered' => $totalRegisteredStudents,
            'total_students' => $totalStudents,
            'this_month_registrations' => $thisMonthRegistrations,
            'last_month_registrations' => $lastMonthRegistrations,
            'period_registrations' => $periodRegistrations,
            'previous_period_registrations' => $previousPeriodRegistrations,
            'growth_percentage' => $growthPercentage,
            'avg_daily' => $avgDaily,
            'best_source' => is_array($bestSource) ? ($bestSource['source'] ?? '-') : '-',
            'top_location' => $topLocation?->location ?? '-',
            'conversion_rate' => $conversionRate,
            'selected_period' => $period,
        ]);
    }

    public function getRecentRegistrations(Request $request)
    {
        $period = $request->input('period', 'month');
        $customDate = $request->input('date');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 10;
        $dateRange = $this->getDateRange($period, $customDate);

        $query = CourseRegistration::with(['student', 'course', 'intake']);
        $this->applyRegistrationDateFilter($query, $dateRange);

        $paginator = $query->orderByRaw($this->registrationDateSql() . ' DESC')
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $rows = $paginator->getCollection()->map(function ($registration) {
            $student = $registration->student;
            $course = $registration->course;

            return [
                'id' => $registration->id,
                'student_id' => $registration->student_id,
                'student_name' => $student?->name_with_initials ?? $student?->full_name ?? 'N/A',
                'email' => $student?->email ?? '',
                'mobile' => $student?->mobile_phone ?? '',
                'course_name' => $course?->course_name ?? 'N/A',
                'course_code' => $course?->course_type ?? '',
                'registration_date' => $registration->registration_date
                    ? Carbon::parse($registration->registration_date)->format('Y-m-d')
                    : ($registration->created_at ? Carbon::parse($registration->created_at)->format('Y-m-d') : 'N/A'),
                'time' => $registration->created_at ? Carbon::parse($registration->created_at)->format('H:i') : '',
                'status' => $registration->status ?: ($registration->approval_status ?: 'Pending'),
                'location' => $registration->location ?? 'N/A',
                'marketing_source' => $student->marketing_survey ?? 'Direct',
            ];
        })->values();

        return response()->json([
            'data' => $rows,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    public function getMarketingSurveyAnalysis(Request $request)
    {
        $dateRange = $this->getDateRange($request->input('period', 'month'), $request->input('date'));

        return response()->json($this->surveyCounts($dateRange));
    }

    public function getMonthlyRegistrationTrend(Request $request)
    {
        $period = $request->input('period', 'month');
        $dateRange = $this->getDateRange($period, $request->input('date'));
        $start = $dateRange['start']->copy()->startOfDay();
        $end = $dateRange['end']->copy()->endOfDay();
        $dateSql = $this->registrationDateSql();
        $useDaily = in_array($period, ['today', 'custom', 'week', 'month'], true);
        $sqlFormat = $useDaily ? '%Y-%m-%d' : '%Y-%m';

        $rows = CourseRegistration::query()
            ->selectRaw("DATE_FORMAT({$dateSql}, '{$sqlFormat}') as bucket, COUNT(*) as count")
            ->whereRaw("{$dateSql} BETWEEN ? AND ?", [$start->toDateString(), $end->toDateString()])
            ->groupByRaw("DATE_FORMAT({$dateSql}, '{$sqlFormat}')")
            ->pluck('count', 'bucket');

        $points = [];
        if ($useDaily) {
            $cursor = $start->copy();
            $last = $end->copy()->startOfDay();
            while ($cursor->lte($last)) {
                $key = $cursor->format('Y-m-d');
                $points[] = [
                    'month' => $cursor->format('d M'),
                    'count' => (int) ($rows[$key] ?? 0),
                ];
                $cursor->addDay();
            }
        } else {
            $cursor = $start->copy()->startOfMonth();
            $last = $end->copy()->startOfMonth();
            while ($cursor->lte($last)) {
                $key = $cursor->format('Y-m');
                $points[] = [
                    'month' => $cursor->format('M Y'),
                    'count' => (int) ($rows[$key] ?? 0),
                ];
                $cursor->addMonth();
            }
        }

        return response()->json($points);
    }

    public function getRegistrationsByLocation(Request $request)
    {
        $dateRange = $this->getDateRange($request->input('period', 'month'), $request->input('date'));

        return response()->json($this->locationCounts($dateRange)->values());
    }

    public function getTopPerformingCourses(Request $request)
    {
        $dateRange = $this->getDateRange($request->input('period', 'month'), $request->input('date'));

        $courseData = CourseRegistration::query()
            ->leftJoin('courses', 'courses.course_id', '=', 'course_registration.course_id')
            ->select('courses.course_name', DB::raw('COUNT(*) as registrations'))
            ->whereRaw($this->registrationDateSql() . ' BETWEEN ? AND ?', [
                $dateRange['start']->toDateString(),
                $dateRange['end']->toDateString(),
            ])
            ->groupBy('courses.course_id', 'courses.course_name')
            ->orderByDesc('registrations')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'course_name' => $item->course_name ?: 'Unknown',
                    'registrations' => (int) $item->registrations,
                ];
            });

        return response()->json($courseData);
    }

    public function getConversionFunnelData()
    {
        $totalStudents = Student::count();
        $registeredStudents = CourseRegistration::query()->distinct('student_id')->count('student_id');
        $completedPayments = CourseRegistration::where('registration_fee', '>', 0)->count();
        $approvedRegistrations = CourseRegistration::whereRaw("LOWER(COALESCE(course_registration.status, '')) = 'registered'")->count();

        return response()->json([
            'total_inquiries' => $totalStudents,
            'registrations' => $registeredStudents,
            'payments' => $completedPayments,
            'approved' => $approvedRegistrations,
        ]);
    }

    public function getMarketingROIBySource(Request $request)
    {
        $dateRange = $this->getDateRange($request->input('period', 'month'), $request->input('date'));

        $sourcePerformance = Student::query()
            ->select(
                'students.marketing_survey',
                DB::raw('COUNT(DISTINCT students.student_id) as student_count'),
                DB::raw('COUNT(DISTINCT CASE WHEN course_registration.id IS NOT NULL THEN students.student_id END) as registration_count')
            )
            ->leftJoin('course_registration', function ($join) use ($dateRange) {
                $join->on('students.student_id', '=', 'course_registration.student_id')
                    ->whereRaw($this->registrationDateSql() . ' BETWEEN ? AND ?', [
                        $dateRange['start']->toDateString(),
                        $dateRange['end']->toDateString(),
                    ]);
            })
            ->whereNotNull('students.marketing_survey')
            ->where('students.marketing_survey', '!=', '')
            ->where(function ($query) use ($dateRange) {
                $query->whereRaw('DATE(students.created_at) BETWEEN ? AND ?', [
                    $dateRange['start']->toDateString(),
                    $dateRange['end']->toDateString(),
                ])->orWhereNotNull('course_registration.id');
            })
            ->groupBy('students.marketing_survey')
            ->get();

        $flattenedData = [];
        foreach ($sourcePerformance as $item) {
            foreach ($this->splitSources($item->marketing_survey) as $source) {
                if (!isset($flattenedData[$source])) {
                    $flattenedData[$source] = [
                        'source' => $source,
                        'students' => 0,
                        'registrations' => 0,
                    ];
                }
                $flattenedData[$source]['students'] += (int) $item->student_count;
                $flattenedData[$source]['registrations'] += (int) $item->registration_count;
            }
        }

        $result = array_map(function ($data) {
            $data['conversion_rate'] = $data['students'] > 0
                ? round(($data['registrations'] / $data['students']) * 100, 1)
                : 0;

            return $data;
        }, $flattenedData);

        usort($result, function ($a, $b) {
            return $b['registrations'] <=> $a['registrations'];
        });

        return response()->json(array_values($result));
    }

    public function getDemographicInsights()
    {
        $genderDistribution = Student::select('gender', DB::raw('COUNT(*) as count'))
            ->groupBy('gender')
            ->get();

        $ageGroups = Student::select(
                DB::raw('CASE
                    WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) < 20 THEN "Under 20"
                    WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 20 AND 25 THEN "20-25"
                    WHEN TIMESTAMPDIFF(YEAR, birthday, CURDATE()) BETWEEN 26 AND 30 THEN "26-30"
                    ELSE "Over 30"
                END as age_group'),
                DB::raw('COUNT(*) as count')
            )
            ->whereNotNull('birthday')
            ->groupBy('age_group')
            ->get();

        return response()->json([
            'gender_distribution' => $genderDistribution,
            'age_groups' => $ageGroups,
        ]);
    }

    private function getDateRange(string $period = 'month', ?string $customDate = null): array
    {
        return match ($period) {
            'today' => [
                'start' => Carbon::today()->startOfDay(),
                'end' => Carbon::today()->endOfDay(),
            ],
            'week' => [
                'start' => Carbon::now()->startOfWeek()->startOfDay(),
                'end' => Carbon::now()->endOfWeek()->endOfDay(),
            ],
            'quarter' => [
                'start' => Carbon::now()->subMonths(2)->startOfMonth()->startOfDay(),
                'end' => Carbon::now()->endOfDay(),
            ],
            'year' => [
                'start' => Carbon::now()->startOfYear()->startOfDay(),
                'end' => Carbon::now()->endOfDay(),
            ],
            'custom' => [
                'start' => $customDate ? Carbon::parse($customDate)->startOfDay() : Carbon::today()->startOfDay(),
                'end' => $customDate ? Carbon::parse($customDate)->endOfDay() : Carbon::today()->endOfDay(),
            ],
            default => [
                'start' => Carbon::now()->startOfMonth()->startOfDay(),
                'end' => Carbon::now()->endOfMonth()->endOfDay(),
            ],
        };
    }

    private function getPreviousDateRange(Carbon $start, Carbon $end): array
    {
        $days = max(1, $start->copy()->startOfDay()->diffInDays($end->copy()->endOfDay()) + 1);

        return [
            'start' => $start->copy()->subDays($days)->startOfDay(),
            'end' => $start->copy()->subDay()->endOfDay(),
        ];
    }

    private function registrationDateSql(): string
    {
        return 'DATE(COALESCE(course_registration.registration_date, course_registration.created_at))';
    }

    private function applyRegistrationDateFilter($query, array $dateRange)
    {
        return $query->whereRaw($this->registrationDateSql() . ' BETWEEN ? AND ?', [
            $dateRange['start']->toDateString(),
            $dateRange['end']->toDateString(),
        ]);
    }

    private function registrationsInRange(Carbon $start, Carbon $end)
    {
        return CourseRegistration::query()->whereRaw($this->registrationDateSql() . ' BETWEEN ? AND ?', [
            $start->toDateString(),
            $end->toDateString(),
        ]);
    }

    private function splitSources(?string $value): array
    {
        return array_values(array_filter(array_map('trim', explode(',', (string) $value)), function ($source) {
            return $source !== '';
        }));
    }

    private function surveyCounts(array $dateRange): array
    {
        $surveyData = Student::query()
            ->join('course_registration', 'students.student_id', '=', 'course_registration.student_id')
            ->select('students.marketing_survey', DB::raw('COUNT(*) as count'))
            ->whereNotNull('students.marketing_survey')
            ->where('students.marketing_survey', '!=', '')
            ->whereRaw($this->registrationDateSql() . ' BETWEEN ? AND ?', [
                $dateRange['start']->toDateString(),
                $dateRange['end']->toDateString(),
            ])
            ->groupBy('students.marketing_survey')
            ->get();

        $flattenedData = [];
        foreach ($surveyData as $item) {
            foreach ($this->splitSources($item->marketing_survey) as $source) {
                $flattenedData[$source] = ($flattenedData[$source] ?? 0) + (int) $item->count;
            }
        }

        $totalCount = array_sum($flattenedData);
        $chartData = [];
        foreach ($flattenedData as $source => $count) {
            $chartData[] = [
                'source' => $source,
                'count' => $count,
                'percentage' => $totalCount > 0 ? round(($count / $totalCount) * 100, 1) : 0,
            ];
        }

        usort($chartData, function ($a, $b) {
            return $b['count'] <=> $a['count'];
        });

        return $chartData;
    }

    private function locationCounts(array $dateRange)
    {
        return CourseRegistration::query()
            ->select('location', DB::raw('COUNT(*) as count'))
            ->whereNotNull('location')
            ->where('location', '!=', '')
            ->whereRaw($this->registrationDateSql() . ' BETWEEN ? AND ?', [
                $dateRange['start']->toDateString(),
                $dateRange['end']->toDateString(),
            ])
            ->groupBy('location')
            ->get()
            ->map(function ($item) {
                return (object) [
                    'location' => $item->location,
                    'count' => (int) $item->count,
                ];
            });
    }
}
