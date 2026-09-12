<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Student;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use Carbon\Carbon;

class StudentCounselorDashboardController extends Controller
{
    public function showDashboard()
    {
        $user = Auth::user();
        
        return view('dashboards.student_counselor', compact('user'));
    }

    // Get overview metrics
    public function getOverviewMetrics(Request $request)
    {
        $period = $request->input('period', 'week');
        $customDate = $request->input('date');
        $dateRange = $this->getDateRange($period, $customDate);
        $previousRange = $this->getPreviousDateRange($dateRange['start'], $dateRange['end']);

        // Use all-time registrations for the top KPI so the value does not decrease
        // just because a registration status changes later.
        $totalRegisteredStudents = CourseRegistration::count();
        $activeRegisteredStudents = CourseRegistration::where('status', 'Registered')->count();
        $totalUniqueStudents = CourseRegistration::distinct('student_id')->count('student_id');
        $pendingRegistrations = $this->pendingRegistrationsQuery()->count();
        $todayRegistrations = $this->registrationsInRange(Carbon::today(), Carbon::today()->endOfDay())->count();
        $thisWeekRegistrations = $this->registrationsInRange(
            Carbon::now()->startOfWeek()->startOfDay(),
            Carbon::now()->endOfWeek()->endOfDay()
        )->count();

        $periodRegistrations = $this->registrationsInRange($dateRange['start'], $dateRange['end'])->count();

        $periodPendingRegistrations = $this->pendingRegistrationsQuery()
            ->whereRaw($this->registrationDateSql() . ' BETWEEN ? AND ?', [
                $dateRange['start']->toDateString(),
                $dateRange['end']->toDateString(),
            ])
            ->count();

        $previousPeriodRegistrations = $this->registrationsInRange($previousRange['start'], $previousRange['end'])->count();

        $growthPercentage = 0;
        if ($previousPeriodRegistrations > 0) {
            $growthPercentage = round((($periodRegistrations - $previousPeriodRegistrations) / $previousPeriodRegistrations) * 100, 1);
        } elseif ($periodRegistrations > 0) {
            $growthPercentage = 100;
        }

        return response()->json([
            'total_registered' => $totalRegisteredStudents,
            'active_registered' => $activeRegisteredStudents,
            'total_unique_students' => $totalUniqueStudents,
            'pending_registrations' => $pendingRegistrations,
            'today_registrations' => $todayRegistrations,
            'week_registrations' => $thisWeekRegistrations,
            'period_registrations' => $periodRegistrations,
            'selected_period_registrations' => $periodRegistrations,
            'period_pending_registrations' => $periodPendingRegistrations,
            'today_growth_percentage' => $growthPercentage,
            'period_growth_percentage' => $growthPercentage,
            'selected_period' => $period,
        ]);
    }

    // Get recent registrations
    public function getRecentRegistrations(Request $request)
    {
        $period = $request->input('period', 'week');
        $customDate = $request->input('date');
        $filter = strtolower($request->input('filter', 'all'));
        $search = trim((string) $request->input('search', ''));
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 10;
        $dateRange = $this->getDateRange($period, $customDate);

        $query = CourseRegistration::with(['student', 'course', 'intake']);
        $this->applyRegistrationDateFilter($query, $dateRange);

        if ($filter === 'pending') {
            $query->where(function ($q) {
                $q->whereRaw("LOWER(COALESCE(course_registration.status, '')) = 'pending'")
                    ->orWhereRaw("LOWER(COALESCE(course_registration.approval_status, '')) = 'pending'");
            });
        } elseif ($filter === 'registered') {
            $query->whereRaw("LOWER(COALESCE(course_registration.status, '')) = 'registered'");
        }

        if ($search !== '') {
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('location', 'like', "%{$search}%")
                    ->orWhere('counselor_name', 'like', "%{$search}%")
                    ->orWhereHas('course', function ($courseQuery) use ($search) {
                        $courseQuery->where('course_name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('student', function ($studentQuery) use ($search) {
                        $studentQuery->where('name_with_initials', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('student_id', 'like', "%{$search}%");
                    });
            });
        }

        $paginator = $query->orderByRaw($this->registrationDateSql() . ' DESC')
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        $rows = $paginator->getCollection()->map(function ($registration) {
                return [
                    'id' => $registration->id,
                    'student_id' => $registration->student_id,
                    'student_name' => $registration->student->name_with_initials ?? $registration->student->full_name ?? 'N/A',
                    'email' => $registration->student->email ?? '',
                    'course_name' => $registration->course->course_name ?? 'N/A',
                    'registration_date' => $registration->registration_date ? Carbon::parse($registration->registration_date)->format('Y-m-d') : ($registration->created_at ? Carbon::parse($registration->created_at)->format('Y-m-d') : 'N/A'),
                    'registration_time' => $registration->created_at ? Carbon::parse($registration->created_at)->format('H:i') : '',
                    'status' => $registration->status,
                    'location' => $registration->location ?? 'N/A',
                    'counselor_name' => $registration->counselor_name ?? 'N/A',
                    'marketing_source' => $registration->student->marketing_survey ?? 'Direct',
                    'mobile' => $registration->student->mobile_phone ?? '',
                ];
            })->values();

        return response()->json([
            'data' => $rows,
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
        ]);
    }

    // Get marketing survey data
    public function getMarketingSurveyData(Request $request)
    {
        $period = $request->input('period', 'week');
        $customDate = $request->input('date');
        $dateRange = $this->getDateRange($period, $customDate);

        $surveyData = Student::join('course_registration', 'students.student_id', '=', 'course_registration.student_id')
            ->select('students.marketing_survey', DB::raw('COUNT(*) as count'))
            ->whereNotNull('students.marketing_survey')
            ->where('students.marketing_survey', '!=', '')
            ->whereRaw($this->registrationDateSql() . ' BETWEEN ? AND ?', [
                $dateRange['start']->toDateString(),
                $dateRange['end']->toDateString(),
            ])
            ->groupBy('students.marketing_survey')
            ->get()
            ->map(function ($item) {
                // Handle multiple sources separated by comma
                $sources = array_map('trim', explode(',', $item->marketing_survey));
                return [
                    'sources' => $sources,
                    'count' => $item->count
                ];
            });

        // Flatten the data to count individual sources
        $flattenedData = [];
        foreach ($surveyData as $item) {
            foreach ($item['sources'] as $source) {
                if (isset($flattenedData[$source])) {
                    $flattenedData[$source] += $item['count'];
                } else {
                    $flattenedData[$source] = $item['count'];
                }
            }
        }

        // Convert to array format for chart
        $chartData = [];
        foreach ($flattenedData as $source => $count) {
            $chartData[] = [
                'source' => $source,
                'count' => $count
            ];
        }

        // Sort by count descending
        usort($chartData, function ($a, $b) {
            return $b['count'] - $a['count'];
        });

        return response()->json($chartData);
    }

    // Get daily registration trend
    public function getDailyRegistrationTrend(Request $request)
    {
        $period = $request->input('period', 'week');
        $customDate = $request->input('date');
        $dateRange = $this->getDateRange($period, $customDate);

        [$groupSql, $labelSql] = match ($period) {
            'quarter' => ['DATE_FORMAT(' . $this->registrationDateSql() . ', "%Y-%m-01")', 'DATE_FORMAT(' . $this->registrationDateSql() . ', "%b %Y")'],
            'month' => ['DATE(' . $this->registrationDateSql() . ')', 'DATE_FORMAT(' . $this->registrationDateSql() . ', "%d %b")'],
            'today', 'custom' => ['DATE(' . $this->registrationDateSql() . ')', 'DATE_FORMAT(' . $this->registrationDateSql() . ', "%d %b")'],
            default => ['DATE(' . $this->registrationDateSql() . ')', 'DATE_FORMAT(' . $this->registrationDateSql() . ', "%a %d")'],
        };

        $trendData = CourseRegistration::select(
                DB::raw("{$groupSql} as group_key"),
                DB::raw("{$labelSql} as date"),
                DB::raw('COUNT(*) as count')
            )
            ->whereRaw($this->registrationDateSql() . ' BETWEEN ? AND ?', [
                $dateRange['start']->toDateString(),
                $dateRange['end']->toDateString(),
            ])
            ->groupBy('group_key', 'date')
            ->orderBy('group_key', 'asc')
            ->get();

        return response()->json($trendData);
    }

    // Get registrations by location
    public function getRegistrationsByLocation(Request $request)
    {
        $period = $request->input('period', 'week');
        $customDate = $request->input('date');
        $dateRange = $this->getDateRange($period, $customDate);

        $locationData = CourseRegistration::select('location', DB::raw('COUNT(*) as count'))
            ->whereNotNull('location')
            ->whereRaw($this->registrationDateSql() . ' BETWEEN ? AND ?', [
                $dateRange['start']->toDateString(),
                $dateRange['end']->toDateString(),
            ])
            ->groupBy('location')
            ->get();

        return response()->json($locationData);
    }

    // Get registrations by course
    public function getRegistrationsByCourse()
    {
        $courseData = CourseRegistration::with('course')
            ->select('course_id', DB::raw('COUNT(*) as count'))
            ->groupBy('course_id')
            ->get()
            ->map(function ($item) {
                return [
                    'course_name' => $item->course->course_name ?? 'Unknown',
                    'count' => $item->count
                ];
            });

        return response()->json($courseData);
    }

    // Get SLT employee vs non-employee registrations
    public function getSltEmployeeData()
    {
        $sltEmployees = CourseRegistration::where('slt_employee', 1)->count();
        $nonEmployees = CourseRegistration::where('slt_employee', 0)->count();

        return response()->json([
            'slt_employees' => $sltEmployees,
            'non_employees' => $nonEmployees
        ]);
    }

    // Get counselor performance data
    public function getCounselorPerformanceData(Request $request)
    {
        $period = $request->input('period', 'week');
        $customDate = $request->input('date');
        $dateRange = $this->getDateRange($period, $customDate);

        $performanceData = CourseRegistration::select('counselor_name', DB::raw('COUNT(*) as student_count'))
            ->whereNotNull('counselor_name')
            ->where('counselor_name', '!=', '')
            ->whereRaw($this->registrationDateSql() . ' BETWEEN ? AND ?', [
                $dateRange['start']->toDateString(),
                $dateRange['end']->toDateString(),
            ])
            ->groupBy('counselor_name')
            ->orderBy('student_count', 'desc')
            ->limit(10)
            ->get();

        return response()->json($performanceData);
    }

    private function getDateRange(string $period = 'week', ?string $customDate = null): array
    {
        return match ($period) {
            'today' => [
                'start' => Carbon::today()->startOfDay(),
                'end' => Carbon::today()->endOfDay(),
            ],
            'month' => [
                'start' => Carbon::now()->startOfMonth()->startOfDay(),
                'end' => Carbon::now()->endOfMonth()->endOfDay(),
            ],
            'quarter' => [
                'start' => Carbon::now()->subMonths(2)->startOfMonth()->startOfDay(),
                'end' => Carbon::now()->endOfDay(),
            ],
            'custom' => [
                'start' => $customDate ? Carbon::parse($customDate)->startOfDay() : Carbon::today()->startOfDay(),
                'end' => $customDate ? Carbon::parse($customDate)->endOfDay() : Carbon::today()->endOfDay(),
            ],
            default => [
                'start' => Carbon::now()->startOfWeek()->startOfDay(),
                'end' => Carbon::now()->endOfWeek()->endOfDay(),
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
        return 'COALESCE(course_registration.registration_date, DATE(course_registration.created_at))';
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

    private function pendingRegistrationsQuery()
    {
        return CourseRegistration::query()->where(function ($q) {
            $q->whereRaw("LOWER(COALESCE(course_registration.status, '')) = 'pending'")
                ->orWhereRaw("LOWER(COALESCE(course_registration.approval_status, '')) = 'pending'");
        });
    }
}
