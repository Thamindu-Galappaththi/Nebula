<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PaymentDetail;
use App\Models\CourseRegistration;
use App\Models\Student;
use App\Models\Course;
use App\Models\Intake;
use App\Models\StudentPaymentPlan;
use App\Models\SltLoanReceivableRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class PaymentSummaryController extends Controller
{
    /**
     * 🔹 Enhanced Global Dashboard with Advanced Metrics
     */
    public function index(Request $request)
    {
        // Get all filter parameters from request
        $range = $request->input('range', '10y');
        $paymentMethod = $request->input('payment_method');
        $status = $request->input('status');
        $studentId = $request->input('student_id');
        $location = $request->input('location');
        $courseId = $request->input('course_id');
        $intakeId = $request->input('intake_id');
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $breakdownScope = $request->input('breakdown_scope', 'paid');

        $startDate = $this->getDateFromRange($range);

        return $this->generateAdvancedSummary(null, $startDate, [
            'payment_method' => $paymentMethod,
            'status' => $status,
            'student_id' => $studentId,
            'location' => $location,
            'course_id' => $courseId,
            'intake_id' => $intakeId,
            'start_date' => $startDateInput,
            'end_date' => $endDateInput,
            'breakdown_scope' => $breakdownScope
        ]);
    }

    /**
     * 🔹 Advanced AJAX Filter
     */
    public function filter(Request $request)
    {
        $studentId = $request->input('student_id');
        $range = $request->input('range', '10y');
        $paymentMethod = $request->input('payment_method');
        $status = $request->input('status');
        $location = $request->input('location');
        $courseId = $request->input('course_id');
        $intakeId = $request->input('intake_id');
        $startDateInput = $request->input('start_date');
        $endDateInput = $request->input('end_date');
        $breakdownScope = $request->input('breakdown_scope', 'paid');

        $startDate = $this->getDateFromRange($range);

        return $this->generateAdvancedSummary($studentId, $startDate, [
            'payment_method' => $paymentMethod,
            'status' => $status,
            'location' => $location,
            'course_id' => $courseId,
            'intake_id' => $intakeId,
            'start_date' => $startDateInput,
            'end_date' => $endDateInput,
            'breakdown_scope' => $breakdownScope
        ]);
    }

    /**
     * Get courses by location for dependent dashboard filters
     */
    public function getCoursesByLocation(Request $request)
    {
        $request->validate([
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
        ]);

        $courses = Course::query()
            ->where('location', $request->input('location'))
            ->select('course_id', 'course_name', 'location')
            ->orderBy('course_name')
            ->get();

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'message' => $courses->isEmpty() ? 'No courses found.' : 'Courses loaded successfully.'
        ]);
    }

    /**
     * Get intakes by location and course for dependent dashboard filters
     */
    public function getIntakesByLocationAndCourse(Request $request)
    {
        $request->validate([
            'location' => 'required|in:Welisara,Moratuwa,Peradeniya',
            'course_id' => 'required|exists:courses,course_id',
        ]);

        $course = Course::findOrFail((int) $request->input('course_id'));

        $intakes = Intake::forCourse($course, $request->input('location'))
            ->select('intake_id', 'batch')
            ->orderBy('batch')
            ->get()
            ->map(function ($intake) {
                return [
                    'intake_id' => $intake->intake_id,
                    'intake_name' => $intake->batch,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $intakes,
        ]);
    }

    /**
     * 🔹 Student-Specific Enhanced Summary
     */
    public function studentSummary($studentId, Request $request)
    {
        $range = $request->input('range', 'all');
        $hasStatus = $this->hasPaymentDetailColumn('status');
        $startDate = $range !== 'all' ? $this->getDateFromRange($range) : null;

        $query = PaymentDetail::query()->where('student_id', $studentId);
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        // Core Metrics
        $totalCollected = $hasStatus
            ? (clone $query)->where('status', 'paid')->sum('total_fee')
            : (clone $query)->sum('total_fee');
        $totalPending = $hasStatus
            ? $this->sumIfColumnExists((clone $query)->where('status', 'pending'), 'remaining_amount')
            : 0;
        $totalLateFee = $this->sumIfColumnExists($query, 'late_fee');
        $totalDiscount = $this->sumIfColumnExists($query, 'registration_fee_discount_applied');

        // New Advanced Metrics
        $approvedLateFees = $this->sumIfColumnExists($query, 'approved_late_fee');
        $foreignCurrencyTotal = $this->sumIfColumnExists($query, 'foreign_currency_amount');
        $ssclTaxTotal = $this->sumIfColumnExists($query, 'sscl_tax_amount');
        $bankChargesTotal = $this->sumIfColumnExists($query, 'bank_charges');

        // Payment Breakdown
        $paymentByMethod = (clone $query)
            ->select('payment_method',
                DB::raw('SUM(total_fee) as total'),
                DB::raw('COUNT(*) as count'))
            ->groupBy('payment_method')
            ->get();

        $paymentByType = (clone $query)
            ->select(
                DB::raw($this->getPaymentTypeCase()),
                DB::raw('SUM(total_fee) as total'),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('type')
            ->get();

        // Status Breakdown
        $paymentByStatus = $hasStatus
            ? (clone $query)
                ->select('status',
                    DB::raw('SUM(total_fee) as total'),
                    DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
            : collect();

        // Monthly Trends
        $monthlyIncome = $hasStatus
            ? (clone $query)
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                    DB::raw('SUM(CASE WHEN status = "paid" THEN total_fee ELSE 0 END) as paid'),
                    DB::raw('SUM(CASE WHEN status = "pending" THEN total_fee ELSE 0 END) as pending'),
                    DB::raw('COUNT(*) as transaction_count')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get()
            : (clone $query)
                ->select(
                    DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
                    DB::raw('SUM(total_fee) as paid'),
                    DB::raw('0 as pending'),
                    DB::raw('COUNT(*) as transaction_count')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get();

        // Recent Payments with Details
        $paymentRecords = (clone $query)
            ->orderByDesc('created_at')
            ->take(50)
            ->get();

        // Payment Method Comparison
        $methodComparison = (clone $query)
            ->select(
                'payment_method',
                DB::raw('AVG(total_fee) as avg_amount'),
                DB::raw('MAX(total_fee) as max_amount'),
                DB::raw('MIN(total_fee) as min_amount')
            )
            ->groupBy('payment_method')
            ->get();

        // Student Info
        $student = Student::where('student_id', $studentId)->first();

        return view('payments.student_summary', compact(
            'studentId', 'student', 'totalCollected', 'totalPending', 'totalLateFee',
            'totalDiscount', 'approvedLateFees', 'foreignCurrencyTotal', 'ssclTaxTotal',
            'bankChargesTotal', 'paymentByMethod', 'paymentByType', 'paymentByStatus',
            'monthlyIncome', 'paymentRecords', 'methodComparison'
        ));
    }

    /**
     * 🔹 Advanced Analytics Dashboard
     */
    public function analytics(Request $request)
    {
        $selectedLocation = $request->input('location') ?? auth()->user()->user_location ?? null;

        // Support an explicit month filter (format: YYYY-MM)
        $monthParam = $request->input('month');
        if (!empty($monthParam)) {
            try {
                $startOfMonth = Carbon::parse($monthParam . '-01')->startOfMonth();
                $endOfMonth = Carbon::parse($monthParam . '-01')->endOfMonth();
            } catch (\Exception $e) {
                $startOfMonth = Carbon::now()->startOfMonth();
                $endOfMonth = Carbon::now()->endOfMonth();
            }
        } else {
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();
        }

        // Every analytics KPI on this page is period based.  Keep one shared
        // month query so the cards, chart, and course summary cannot drift.
        $query = PaymentDetail::query()
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth]);

        // Revenue Analytics
        $revenueByDay = (clone $query)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(CASE WHEN status = "paid" THEN total_fee ELSE 0 END) as revenue')
            )
            ->where('status', 'paid')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $totalRevenue = $revenueByDay->sum('revenue');
        $totalPendingPayments = (clone $query)->where('status', 'pending')->sum('total_fee');
        $averagePaidTransaction = (clone $query)->where('status', 'paid')->avg('total_fee') ?? 0;

        // Keep $currentMonthStart/$currentMonthEnd for backwards compatibility elsewhere
        $currentMonthStart = $startOfMonth->toDateString();
        $currentMonthEnd = $endOfMonth->toDateString();

        $pendingSltLoanRecoveries = $this->fetchPendingSltLoanRecoveries($startOfMonth, $endOfMonth);

        $selectedNewRegistrationCourseId = $request->input('new_registration_course_id');
        $selectedOngoingCourseId = $request->input('ongoing_course_id');

        $totalSltLoanRecoveries = $pendingSltLoanRecoveries->count();
        $totalSltRecoveryAmount = $pendingSltLoanRecoveries->sum('installment_amount');

        $courses = Course::query()
            ->select('course_id', 'course_name')
            ->orderBy('course_name')
            ->get();

        $availableCourses = Course::query()
            ->select('course_id', 'course_name', 'location')
            ->orderBy('course_name')
            ->get();

        $registrationSummary = CourseRegistration::query()
            ->select(
                'course_id',
                DB::raw('COUNT(*) as total_registrations'),
                DB::raw("SUM(CASE WHEN status = 'Registered' THEN 1 ELSE 0 END) as ongoing_courses"),
                DB::raw("SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_registrations"),
                DB::raw("SUM(CASE WHEN registration_date BETWEEN '{$startOfMonth->toDateString()}' AND '{$endOfMonth->toDateString()}' THEN 1 ELSE 0 END) as new_registrations")
            )
            ->whereDate('registration_date', '<=', $endOfMonth->toDateString())
            ->groupBy('course_id')
            ->get()
            ->keyBy('course_id');

        $paymentSummary = PaymentDetail::join('course_registration', 'payment_details.course_registration_id', '=', 'course_registration.id')
            ->select(
                'course_registration.course_id',
                DB::raw("SUM(CASE WHEN payment_details.status = 'paid' THEN payment_details.amount ELSE 0 END) as paid_amount"),
                DB::raw("SUM(CASE WHEN payment_details.status = 'pending' THEN payment_details.amount ELSE 0 END) as pending_amount"),
                DB::raw('COUNT(payment_details.id) as payment_count')
            )
            ->whereBetween('payment_details.created_at', [$startOfMonth, $endOfMonth])
            ->groupBy('course_registration.course_id')
            ->get()
            ->keyBy('course_id');

        $courseWiseSummary = $availableCourses->map(function ($course) use ($registrationSummary, $paymentSummary) {
            $courseId = (int) $course->course_id;
            $registrationRow = $registrationSummary->get($courseId);
            $paymentRow = $paymentSummary->get($courseId);

            return [
                'course_id' => $courseId,
                'course_name' => $course->course_name,
                'location' => $course->location ?? 'N/A',
                'total_registrations' => (int) ($registrationRow->total_registrations ?? 0),
                'new_registrations' => (int) ($registrationRow->new_registrations ?? 0),
                'ongoing_courses' => (int) ($registrationRow->ongoing_courses ?? 0),
                'pending_registrations' => (int) ($registrationRow->pending_registrations ?? 0),
                'paid_amount' => (float) ($paymentRow->paid_amount ?? 0),
                'pending_amount' => (float) ($paymentRow->pending_amount ?? 0),
                'payment_count' => (int) ($paymentRow->payment_count ?? 0),
            ];
        })->sortByDesc('paid_amount')->values();

        $paymentTable = $this->getPaymentDetailsTable();
        $paidDateExpr = $this->getPaidDateSqlExpression($paymentTable);

        // This KPI must represent actual paid registration-fee transactions
        // captured via Payment Management / Update Records, not eligibility
        // registrations.
        $newRegistrationsCurrentMonthQuery = PaymentDetail::query()
            ->whereNotNull('course_registration_id')
            ->where('status', 'paid')
            ->when($selectedNewRegistrationCourseId, function ($query) use ($selectedNewRegistrationCourseId) {
                $query->whereHas('registration', function ($registrationQuery) use ($selectedNewRegistrationCourseId) {
                    $registrationQuery->where('course_id', $selectedNewRegistrationCourseId);
                });
            });

        $this->applyRegistrationFeeTypeFilter($newRegistrationsCurrentMonthQuery, $paymentTable);

        $newRegistrationsCurrentMonthQuery
            ->whereRaw("DATE({$paidDateExpr}) >= ?", [$startOfMonth->toDateString()])
            ->whereRaw("DATE({$paidDateExpr}) <= ?", [$endOfMonth->toDateString()]);

        $newRegistrationsCount = (clone $newRegistrationsCurrentMonthQuery)
            ->distinct()
            ->count('course_registration_id');

        $newRegistrationsAmount = (float) (clone $newRegistrationsCurrentMonthQuery)
            ->sum(DB::raw("COALESCE({$paymentTable}.total_fee, {$paymentTable}.amount, 0)"));

        // Ongoing Courses represents local course-fee collections only.  Use
        // the same effective-payment-date month window as New Registrations.
        $ongoingCoursesCurrentMonthQuery = PaymentDetail::query()
            ->whereNotNull('course_registration_id')
            ->where('status', 'paid')
            ->when($selectedOngoingCourseId, function ($query) use ($selectedOngoingCourseId) {
                $query->whereHas('registration', function ($registrationQuery) use ($selectedOngoingCourseId) {
                    $registrationQuery->where('course_id', $selectedOngoingCourseId);
                });
            });

        $this->applyLocalCourseFeeTypeFilter($ongoingCoursesCurrentMonthQuery, $paymentTable);

        $ongoingCoursesCurrentMonthQuery
            ->whereRaw("DATE({$paidDateExpr}) >= ?", [$startOfMonth->toDateString()])
            ->whereRaw("DATE({$paidDateExpr}) <= ?", [$endOfMonth->toDateString()]);

        $ongoingCoursesCount = (clone $ongoingCoursesCurrentMonthQuery)
            ->distinct()
            ->count('course_registration_id');

        $ongoingCoursesAmount = (float) (clone $ongoingCoursesCurrentMonthQuery)
            ->sum(DB::raw("COALESCE({$paymentTable}.total_fee, {$paymentTable}.amount, 0)"));

        return view('payments.analytics', compact(
            'revenueByDay', 'pendingSltLoanRecoveries', 'totalRevenue',
            'totalPendingPayments', 'averagePaidTransaction',
            'totalSltLoanRecoveries', 'totalSltRecoveryAmount',
            'courseWiseSummary', 'newRegistrationsCount', 'newRegistrationsAmount',
            'ongoingCoursesCount', 'ongoingCoursesAmount', 'courses',
            'selectedNewRegistrationCourseId', 'selectedOngoingCourseId', 'startOfMonth'
        ));
    }

    /** Export the source records that make up an analytics KPI card. */
    public function exportAnalyticsKpi(Request $request)
    {
        $request->validate([
            'metric' => 'required|in:new_registrations,ongoing_courses',
            'month' => 'nullable|date_format:Y-m',
            'course_id' => 'nullable|integer|exists:courses,course_id',
        ]);

        $startOfMonth = $request->filled('month')
            ? Carbon::createFromFormat('Y-m', $request->input('month'))->startOfMonth()
            : Carbon::now()->startOfMonth();
        $endOfMonth = (clone $startOfMonth)->endOfMonth();
        $metric = $request->input('metric');
        $courseId = $request->input('course_id');

        if ($metric === 'new_registrations') {
            $paymentTable = $this->getPaymentDetailsTable();
            $paidDateExpr = $this->getPaidDateSqlExpression($paymentTable);

            $recordsQuery = PaymentDetail::with(['student', 'registration.course', 'registration.intake'])
                ->whereNotNull('course_registration_id')
                ->where('status', 'paid')
                ->when($courseId, function ($query) use ($courseId) {
                    $query->whereHas('registration', function ($registrationQuery) use ($courseId) {
                        $registrationQuery->where('course_id', $courseId);
                    });
                })
                ->whereRaw("DATE({$paidDateExpr}) >= ?", [$startOfMonth->toDateString()])
                ->whereRaw("DATE({$paidDateExpr}) <= ?", [$endOfMonth->toDateString()])
                ->orderByRaw("DATE({$paidDateExpr})");

            $this->applyRegistrationFeeTypeFilter($recordsQuery, $paymentTable);

            $records = $recordsQuery->get();
            $rows = $records->map(function ($record) {
                return [
                    'student_name' => optional($record->student)->full_name ?? 'N/A',
                    'nic' => optional($record->student)->id_value ?? 'N/A',
                    'course' => optional(optional($record->registration)->course)->course_name ?? 'N/A',
                    'intake' => optional(optional($record->registration)->intake)->batch ?? 'N/A',
                    'reference' => $record->transaction_id ?? ('Payment #' . $record->id),
                    'date' => $this->resolveEffectivePaidDateForExport($record),
                    'amount' => (float) ($record->total_fee ?? $record->amount ?? 0),
                ];
            });
            $title = 'Registration Fee Payments Audit';
            $amountLabel = 'Registration Fee Paid';
        } else {
            $paymentTable = $this->getPaymentDetailsTable();
            $paidDateExpr = $this->getPaidDateSqlExpression($paymentTable);

            $recordsQuery = PaymentDetail::with(['student', 'registration.course', 'registration.intake'])
                ->whereNotNull('course_registration_id')
                ->where('status', 'paid')
                ->when($courseId, function ($query) use ($courseId) {
                    $query->whereHas('registration', fn ($registrationQuery) => $registrationQuery->where('course_id', $courseId));
                })
                ->whereRaw("DATE({$paidDateExpr}) >= ?", [$startOfMonth->toDateString()])
                ->whereRaw("DATE({$paidDateExpr}) <= ?", [$endOfMonth->toDateString()])
                ->orderByRaw("DATE({$paidDateExpr})");

            $this->applyLocalCourseFeeTypeFilter($recordsQuery, $paymentTable);

            $records = $recordsQuery->get();
            $rows = $records->map(fn ($record) => [
                'student_name' => optional($record->student)->full_name ?? 'N/A',
                'nic' => optional($record->student)->id_value ?? 'N/A',
                'course' => optional(optional($record->registration)->course)->course_name ?? 'N/A',
                'intake' => optional(optional($record->registration)->intake)->batch ?? 'N/A',
                'reference' => $record->transaction_id ?? ('Payment #' . $record->id),
                'date' => $this->resolveEffectivePaidDateForExport($record),
                'amount' => (float) ($record->total_fee ?? $record->amount ?? 0),
            ]);
            $title = 'Local Course Fee Payments Audit';
            $amountLabel = 'Local Course Fee Paid';
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payments.analytics_kpi_export_pdf', [
            'title' => $title,
            'amountLabel' => $amountLabel,
            'month' => $startOfMonth,
            'rows' => $rows,
            'totalAmount' => $rows->sum('amount'),
            'generatedAt' => Carbon::now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download(str($metric)->replace('_', '-') . '-audit-' . $startOfMonth->format('Y-m') . '.pdf');
    }

    /**
     * 🔹 Export Report (CSV/PDF)
     */
    public function export(Request $request)
    {
        $format = strtolower((string) $request->input('format', 'pdf'));
        if ($format !== 'pdf') {
            return response()->json(['error' => 'Only PDF export is supported'], 400);
        }

        $range = $request->input('range', '1y');
        $startDate = $this->getDateFromRange($range);

        // DomPDF holds the complete document tree in memory.  Rendering every
        // transaction in a long date range can exhaust PHP memory, so keep the
        // report to a practical, readable size and state that in the PDF.
        $maxExportRows = 250;
        $exportQuery = $this->buildExportPaymentsQuery($request, $startDate);
        $totalRecords = (clone $exportQuery)->count();

        $payments = $exportQuery
            ->orderByDesc('payment_effective_date')
            ->orderByDesc('created_at')
            ->limit($maxExportRows)
            ->get();

        return $this->exportPDF($payments, $totalRecords);
    }

    private function buildExportPaymentsQuery(Request $request, Carbon $startDate)
    {
        $table = $this->getPaymentDetailsTable();
        $dashboardDateExpr = $this->getDashboardDateSqlExpression($table);
        $query = PaymentDetail::query()->with(['student', 'registration.course', 'registration.intake']);

        $studentSearch = trim((string) $request->input('student_id', ''));
        if ($studentSearch !== '') {
            $matchingStudentIds = Student::query()
                ->where('id_value', $studentSearch)
                ->orWhere('student_id', $studentSearch)
                ->pluck('student_id')
                ->unique()
                ->values();

            if ($matchingStudentIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn($table . '.student_id', $matchingStudentIds->all());
            }
        }

        $startDateInput = !empty($request->input('start_date')) ? Carbon::parse($request->input('start_date'))->toDateString() : null;
        $endDateInput = !empty($request->input('end_date')) ? Carbon::parse($request->input('end_date'))->toDateString() : null;

        if ($startDateInput || $endDateInput) {
            if ($startDateInput) {
                $query->whereRaw("DATE({$dashboardDateExpr}) >= ?", [$startDateInput]);
            }

            if ($endDateInput) {
                $query->whereRaw("DATE({$dashboardDateExpr}) <= ?", [$endDateInput]);
            }
        } else {
            $query->whereRaw("DATE({$dashboardDateExpr}) >= ?", [$startDate->toDateString()]);
        }

        if (!empty($request->input('location'))) {
            $query->whereHas('registration', function ($q) use ($request) {
                $q->where('location', $request->input('location'));
            });
        }

        if (!empty($request->input('course_id'))) {
            $query->whereHas('registration', function ($q) use ($request) {
                $q->where('course_id', (int) $request->input('course_id'));
            });
        }

        if (!empty($request->input('intake_id'))) {
            $query->whereHas('registration', function ($q) use ($request) {
                $q->where('intake_id', (int) $request->input('intake_id'));
            });
        }

        if (!empty($request->input('payment_method'))) {
            $query->where($table . '.payment_method', $request->input('payment_method'));
        }

        if (!empty($request->input('status')) && $this->hasPaymentDetailColumn('status')) {
            $query->where($table . '.status', $request->input('status'));
        }

        return $query;
    }

    private function exportPDF($payments, int $totalRecords)
    {
        $rows = $payments->map(function ($payment) {
            return [
                'student_name' => optional($payment->student)->full_name ?? 'N/A',
                'nic' => optional($payment->student)->id_value ?? 'N/A',
                'course' => optional(optional($payment->registration)->course)->course_name ?? 'N/A',
                'intake' => optional(optional($payment->registration)->intake)->batch ?? 'N/A',
                'location' => optional($payment->registration)->location ?? optional($payment->student)->institute_location ?? 'N/A',
                'payment_type' => $this->formatPaymentTypeForExport($payment),
                'amount' => (float) ($payment->total_fee ?? $payment->amount ?? 0),
                'effective_paid_date' => $this->resolveEffectivePaidDateForExport($payment),
            ];
        });

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payments.summary_export_pdf', [
            'rows' => $rows,
            'generatedAt' => Carbon::now(),
            'totalRecords' => $totalRecords,
            'isTruncated' => $totalRecords > $payments->count(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('payment_report_' . now()->format('Y-m-d_His') . '.pdf');
    }

    private function formatPaymentTypeForExport($payment): string
    {
        $rawType = $payment->installment_type ?? $payment->payment_type ?? null;

        if (empty($rawType) && !empty($payment->misc_category)) {
            return 'Miscellaneous';
        }

        return match ($rawType) {
            'course_fee' => 'Course Fee',
            'registration_fee' => 'Registration Fee',
            'franchise_fee' => 'Franchise Fee',
            null, '' => 'Unknown',
            default => ucwords(str_replace('_', ' ', (string) $rawType)),
        };
    }

    private function resolveEffectivePaidDateForExport($payment): string
    {
        $candidates = [
            $payment->payment_effective_date ?? null,
            $payment->payment_date ?? null,
            $payment->due_date ?? null,
            $payment->created_at ?? null,
        ];

        foreach ($candidates as $dateValue) {
            if (empty($dateValue)) {
                continue;
            }

            try {
                return Carbon::parse($dateValue)->format('Y-m-d');
            } catch (\Throwable $e) {
                continue;
            }
        }

        return 'N/A';
    }

    /**
     * 🔹 Comparison Dashboard (Year over Year, Month over Month)
     */
    public function comparison(Request $request)
    {
        $currentYear = Carbon::now()->year;
        $previousYear = $currentYear - 1;

        // Year over Year Comparison
        $currentYearData = PaymentDetail::whereYear('created_at', $currentYear)
            ->where('status', 'paid')
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_fee) as revenue')
            )
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        $previousYearData = PaymentDetail::whereYear('created_at', $previousYear)
            ->where('status', 'paid')
            ->select(
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total_fee) as revenue')
            )
            ->groupBy('month')
            ->get()
            ->keyBy('month');

        // Growth Metrics
        $currentYearTotal = PaymentDetail::whereYear('created_at', $currentYear)
            ->where('status', 'paid')
            ->sum('total_fee');

        $previousYearTotal = PaymentDetail::whereYear('created_at', $previousYear)
            ->where('status', 'paid')
            ->sum('total_fee');

        $growthRate = $previousYearTotal > 0
            ? (($currentYearTotal - $previousYearTotal) / $previousYearTotal) * 100
            : 0;

        return view('payments.comparison', compact(
            'currentYearData', 'previousYearData', 'currentYearTotal',
            'previousYearTotal', 'growthRate', 'currentYear', 'previousYear'
        ));
    }

    /**
     * 🔹 Generate Advanced Summary - FIXED VERSION
     */
    private function generateAdvancedSummary($studentId = null, $startDate = null, $filters = [])
    {
        $paymentTable = $this->getPaymentDetailsTable();
        $dashboardDateExpr = $this->getDashboardDateSqlExpression($paymentTable);
        $query = PaymentDetail::query();
        $hasStatus = $this->hasPaymentDetailColumn('status');
        $breakdownScope = ($filters['breakdown_scope'] ?? 'paid') === 'all' ? 'all' : 'paid';
        $matchingStudentIds = collect();

        // Apply student filter (supports internal student_id and NIC/id_value)
        $studentSearch = trim((string) ($filters['student_id'] ?? $studentId ?? ''));
        if ($studentSearch !== '') {
            $matchingStudentIds = Student::query()
                ->where('id_value', $studentSearch)
                ->orWhere('student_id', $studentSearch)
                ->pluck('student_id')
                ->unique()
                ->values();

            if ($matchingStudentIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn($paymentTable . '.student_id', $matchingStudentIds->all());
            }
        }

        // Apply date range filter
        $startDateInput = !empty($filters['start_date']) ? Carbon::parse($filters['start_date'])->toDateString() : null;
        $endDateInput = !empty($filters['end_date']) ? Carbon::parse($filters['end_date'])->toDateString() : null;

        if ($startDateInput || $endDateInput) {
            if ($startDateInput) {
                $query->whereRaw("DATE({$dashboardDateExpr}) >= ?", [$startDateInput]);
            }

            if ($endDateInput) {
                $query->whereRaw("DATE({$dashboardDateExpr}) <= ?", [$endDateInput]);
            }
        } elseif ($startDate) {
            $query->whereRaw("DATE({$dashboardDateExpr}) >= ?", [$startDate->toDateString()]);
        }

        // Apply location filter via registration context
        if (!empty($filters['location'])) {
            $query->whereHas('registration', function ($q) use ($filters) {
                $q->where('location', $filters['location']);
            });
        }

        // Apply course filter via registration context
        if (!empty($filters['course_id'])) {
            $query->whereHas('registration', function ($q) use ($filters) {
                $q->where('course_id', (int) $filters['course_id']);
            });
        }

        // Apply intake filter via registration context
        if (!empty($filters['intake_id'])) {
            $query->whereHas('registration', function ($q) use ($filters) {
                $q->where('intake_id', (int) $filters['intake_id']);
            });
        }

        // Apply payment method filter
        if (!empty($filters['payment_method'])) {
            $query->where($paymentTable . '.payment_method', $filters['payment_method']);
        }

        // Keep a copy before status filtering so methods/types can use their own scope.
        $queryBeforeStatusFilter = clone $query;

        // Apply status filter
        if (!empty($filters['status']) && $hasStatus) {
            $query->where($paymentTable . '.status', $filters['status']);
        }

        // Core KPIs
        $totalCollected = $hasStatus
            ? (clone $query)->where($paymentTable . '.status', 'paid')->sum($paymentTable . '.total_fee')
            : (clone $query)->sum($paymentTable . '.total_fee');
        $totalPending = $hasStatus
            ? $this->sumIfColumnExists((clone $query)->where($paymentTable . '.status', 'pending'), 'remaining_amount')
            : 0;
        $totalLateFee = $this->sumIfColumnExists($query, 'late_fee');
        $totalDiscount = $this->sumIfColumnExists($query, 'registration_fee_discount_applied');

        // Advanced KPIs
        $totalTransactions = (clone $query)->count();
        $averageTransaction = $totalTransactions > 0 ? $totalCollected / $totalTransactions : 0;
        $ssclTaxTotal = $this->sumIfColumnExists($query, 'sscl_tax_amount');
        $bankChargesTotal = $this->sumIfColumnExists($query, 'bank_charges');

        // Payment Breakdowns
        $methodTypeQuery = clone $queryBeforeStatusFilter;
        if ($hasStatus && $breakdownScope === 'paid') {
            $methodTypeQuery->where($paymentTable . '.status', 'paid');
        }

        $paymentByMethod = (clone $methodTypeQuery)
            ->select($paymentTable . '.payment_method',
                DB::raw("SUM({$paymentTable}.total_fee) as total"),
                DB::raw('COUNT(*) as count'))
            ->groupBy($paymentTable . '.payment_method')
            ->get();

        $paymentByType = (clone $methodTypeQuery)
            ->select(
                DB::raw($this->getPaymentTypeCase()),
                DB::raw("SUM({$paymentTable}.total_fee) as total"),
                DB::raw('COUNT(*) as count')
            )
            ->groupBy('type')
            ->get();

        $paymentByStatus = $hasStatus
            ? (clone $query)
                ->select($paymentTable . '.status',
                    DB::raw("SUM({$paymentTable}.total_fee) as total"),
                    DB::raw('COUNT(*) as count'))
                ->groupBy($paymentTable . '.status')
                ->get()
            : collect();

        // Time-based Analytics
        $monthlyIncome = $hasStatus
            ? (clone $query)
                ->select(
                    DB::raw("DATE_FORMAT({$dashboardDateExpr}, '%Y-%m') as month"),
                    DB::raw("SUM(CASE WHEN {$paymentTable}.status = 'paid' THEN {$paymentTable}.total_fee ELSE 0 END) as paid"),
                    DB::raw("SUM(CASE WHEN {$paymentTable}.status = 'pending' THEN {$paymentTable}.total_fee ELSE 0 END) as pending")
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get()
            : (clone $query)
                ->select(
                    DB::raw("DATE_FORMAT({$dashboardDateExpr}, '%Y-%m') as month"),
                    DB::raw("SUM({$paymentTable}.total_fee) as paid"),
                    DB::raw('0 as pending')
                )
                ->groupBy('month')
                ->orderBy('month')
                ->get();

        $weeklyTrend = (clone $query)
            ->select(
                DB::raw("YEARWEEK({$dashboardDateExpr}) as week"),
                DB::raw("SUM({$paymentTable}.total_fee) as total")
            )
            ->when($hasStatus, function ($q) {
                return $q->where($this->getPaymentDetailsTable() . '.status', 'paid');
            })
            ->groupBy('week')
            ->orderBy('week', 'desc')
            ->take(12)
            ->get();

        $districtAnalytics = Student::query()
            ->select(
                DB::raw("COALESCE(NULLIF(TRIM(district), ''), 'Unknown') as district"),
                DB::raw('COUNT(student_id) as student_count')
            )
            ->groupBy('district')
            ->orderByDesc('student_count')
            ->orderBy('district')
            ->get();

        $selectedLocation = !empty($filters['location']) ? $filters['location'] : null;
        $selectedCourseId = !empty($filters['course_id']) ? (int) $filters['course_id'] : null;
        $selectedIntakeId = !empty($filters['intake_id']) ? (int) $filters['intake_id'] : null;
        $selectedNewRegistrationCourseId = !empty($filters['new_registration_course_id']) ? (int) $filters['new_registration_course_id'] : null;
        $selectedOngoingCourseId = !empty($filters['ongoing_course_id']) ? (int) $filters['ongoing_course_id'] : null;
        $currentMonthStart = now()->startOfMonth()->toDateString();
        $currentMonthEnd = now()->toDateString();
        $startOfMonth = !empty($filters['start_date']) ? Carbon::parse($filters['start_date']) : now()->startOfMonth();
        $endOfMonth = !empty($filters['end_date']) ? Carbon::parse($filters['end_date']) : now()->endOfMonth();

        $registrationQuery = CourseRegistration::query();

        if ($studentSearch !== '') {
            if (isset($matchingStudentIds) && $matchingStudentIds->isNotEmpty()) {
                $registrationQuery->whereIn('student_id', $matchingStudentIds->all());
            } else {
                $registrationQuery->whereRaw('1 = 0');
            }
        }

        if ($selectedLocation) {
            $registrationQuery->where('location', $selectedLocation);
        }

        if ($selectedCourseId) {
            $registrationQuery->where('course_id', $selectedCourseId);
        }

        if ($selectedIntakeId) {
            $registrationQuery->where('intake_id', $selectedIntakeId);
        }

        if ($startDateInput || $endDateInput) {
            if ($startDateInput) {
                $registrationQuery->whereDate('registration_date', '>=', $startDateInput);
            }

            if ($endDateInput) {
                $registrationQuery->whereDate('registration_date', '<=', $endDateInput);
            }
        } elseif ($startDate) {
            $registrationQuery->whereDate('registration_date', '>=', $startDate->toDateString());
        }

        $availableCourses = Course::query()
            ->select('course_id', 'course_name', 'location')
            ->when($selectedLocation, function ($q) use ($selectedLocation) {
                $q->where('location', $selectedLocation);
            })
            ->orderBy('course_name')
            ->get();

        $registrationSummary = (clone $registrationQuery)
            ->select(
                'course_id',
                DB::raw('COUNT(*) as total_registrations'),
                DB::raw("SUM(CASE WHEN status = 'Registered' THEN 1 ELSE 0 END) as ongoing_courses"),
                DB::raw("SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_registrations"),
                DB::raw("SUM(CASE WHEN registration_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_registrations")
            )
            ->groupBy('course_id')
            ->get()
            ->keyBy('course_id');

        $paymentSummary = PaymentDetail::join('course_registration', 'payment_details.course_registration_id', '=', 'course_registration.id')
            ->where('course_registration.location', $selectedLocation ?? auth()->user()->user_location ?? 'Welisara')
            ->when($selectedCourseId, function ($query) use ($selectedCourseId) {
                $query->where('course_registration.course_id', $selectedCourseId);
            })
            ->when($selectedIntakeId, function ($query) use ($selectedIntakeId) {
                $query->where('course_registration.intake_id', $selectedIntakeId);
            })
            ->when($studentSearch !== '', function ($query) use ($matchingStudentIds) {
                if (isset($matchingStudentIds) && $matchingStudentIds->isNotEmpty()) {
                    $query->whereIn('course_registration.student_id', $matchingStudentIds->all());
                } else {
                    $query->whereRaw('1 = 0');
                }
            })
            ->when($startDateInput || $endDateInput || $startDate, function ($query) use ($dashboardDateExpr, $startDateInput, $endDateInput, $startDate) {
                if ($startDateInput) {
                    $query->whereRaw("DATE({$dashboardDateExpr}) >= ?", [$startDateInput]);
                } elseif ($startDate) {
                    $query->whereRaw("DATE({$dashboardDateExpr}) >= ?", [$startDate->toDateString()]);
                }

                if ($endDateInput) {
                    $query->whereRaw("DATE({$dashboardDateExpr}) <= ?", [$endDateInput]);
                }
            })
            ->select(
                'course_registration.course_id',
                DB::raw("SUM(CASE WHEN payment_details.status = 'paid' THEN payment_details.amount ELSE 0 END) as paid_amount"),
                DB::raw("SUM(CASE WHEN payment_details.status = 'pending' THEN payment_details.amount ELSE 0 END) as pending_amount"),
                DB::raw('COUNT(payment_details.id) as payment_count')
            )
            ->groupBy('course_registration.course_id')
            ->get()
            ->keyBy('course_id');

        $courseWiseSummary = $availableCourses->map(function ($course) use ($registrationSummary, $paymentSummary) {
            $courseId = (int) $course->course_id;
            $registrationRow = $registrationSummary->get($courseId);
            $paymentRow = $paymentSummary->get($courseId);

            return [
                'course_id' => $courseId,
                'course_name' => $course->course_name,
                'location' => $course->location ?? 'N/A',
                'total_registrations' => (int) ($registrationRow->total_registrations ?? 0),
                'new_registrations' => (int) ($registrationRow->new_registrations ?? 0),
                'ongoing_courses' => (int) ($registrationRow->ongoing_courses ?? 0),
                'pending_registrations' => (int) ($registrationRow->pending_registrations ?? 0),
                'paid_amount' => (float) ($paymentRow->paid_amount ?? 0),
                'pending_amount' => (float) ($paymentRow->pending_amount ?? 0),
                'payment_count' => (int) ($paymentRow->payment_count ?? 0),
            ];
        })->sortByDesc('paid_amount')->values();

        $newRegistrationsCurrentMonthQuery = CourseRegistration::query()
            ->when($selectedLocation, function ($query) use ($selectedLocation) {
                $query->where('location', $selectedLocation);
            })
            ->when($selectedNewRegistrationCourseId, function ($query) use ($selectedNewRegistrationCourseId) {
                $query->where('course_id', $selectedNewRegistrationCourseId);
            })
            ->whereDate('registration_date', '>=', $currentMonthStart)
            ->whereDate('registration_date', '<=', $currentMonthEnd);

        $newRegistrationsCount = (clone $newRegistrationsCurrentMonthQuery)->count();
        $newRegistrationsAmount = (float) (clone $newRegistrationsCurrentMonthQuery)->sum(DB::raw('COALESCE(registration_fee, 0)'));

        $ongoingRegistrationsQuery = CourseRegistration::query()
            ->where('status', 'Registered')
            ->when($selectedLocation, function ($query) use ($selectedLocation) {
                $query->where('location', $selectedLocation);
            })
            ->when($selectedOngoingCourseId, function ($query) use ($selectedOngoingCourseId) {
                $query->where('course_id', $selectedOngoingCourseId);
            });

        $ongoingCoursesCount = (clone $ongoingRegistrationsQuery)->count();

        // Sum paid course_fee + franchise_fee within selected month for ongoing registrations
        $ongoingFeesCollected = (float) PaymentDetail::join('course_registration', 'payment_details.course_registration_id', '=', 'course_registration.id')
            ->where('course_registration.status', 'Registered')
            ->when($selectedLocation, function ($query) use ($selectedLocation) {
                $query->where('course_registration.location', $selectedLocation);
            })
            ->when($selectedOngoingCourseId, function ($query) use ($selectedOngoingCourseId) {
                $query->where('course_registration.course_id', $selectedOngoingCourseId);
            })
            ->whereRaw("DATE({$dashboardDateExpr}) >= ?", [$startOfMonth->toDateString()])
            ->whereRaw("DATE({$dashboardDateExpr}) <= ?", [$endOfMonth->toDateString()])
            ->where('payment_details.status', 'paid')
            ->where(function($q) {
                $q->where('payment_details.installment_type', 'course_fee')
                  ->orWhere('payment_details.installment_type', 'franchise_fee');
                if ($this->hasPaymentDetailColumn('payment_type')) {
                    $q->orWhere('payment_details.payment_type', 'course_fee')
                      ->orWhere('payment_details.payment_type', 'franchise_fee');
                }
            })
            ->sum('payment_details.total_fee');

        // Backwards-compatible variable used by the Blade view
        $ongoingCoursesAmount = $ongoingFeesCollected;

        $newRegistrations = (clone $newRegistrationsCurrentMonthQuery)
            ->with(['student', 'course'])
            ->orderBy('registration_date', 'desc')
            ->limit(10)
            ->get();

        if (request()->ajax()) {
            return response()->json([
                'totalCollected' => $totalCollected,
                'totalPending' => $totalPending,
                'totalLateFee' => $totalLateFee,
                'totalDiscount' => $totalDiscount,
                'totalTransactions' => $totalTransactions,
                'averageTransaction' => $averageTransaction,
                'ssclTaxTotal' => $ssclTaxTotal,
                'bankChargesTotal' => $bankChargesTotal,
                'paymentByMethod' => $paymentByMethod,
                'paymentByType' => $paymentByType,
                'paymentByStatus' => $paymentByStatus,
                'breakdownScope' => $breakdownScope,
                'monthlyIncome' => $monthlyIncome,
                'weeklyTrend' => $weeklyTrend,
                'districtAnalytics' => $districtAnalytics,
                'courseWiseSummary' => $courseWiseSummary,
                'newRegistrations' => $newRegistrations,
                'newRegistrationsCount' => $newRegistrationsCount,
                'newRegistrationsAmount' => $newRegistrationsAmount,
                'ongoingCoursesCount' => $ongoingCoursesCount,
                'ongoingCoursesAmount' => $ongoingCoursesAmount,
            ]);
        }

        $courses = Course::query()
            ->select('course_id', 'course_name')
            ->when($selectedLocation, function ($q) use ($selectedLocation) {
                $q->where('location', $selectedLocation);
            })
            ->orderBy('course_name')
            ->get();

        $intakeQuery = $selectedCourseId
            ? Intake::forCourse(Course::findOrFail($selectedCourseId), $selectedLocation)
            : Intake::query()->when($selectedLocation, function ($q) use ($selectedLocation) {
                $q->where('location', $selectedLocation);
            });

        $intakes = $intakeQuery
            ->select('intake_id', 'batch')
            ->orderBy('batch')
            ->get();

        return view('payments.summary', compact(
            'totalCollected', 'totalPending', 'totalLateFee', 'totalDiscount',
            'totalTransactions', 'averageTransaction', 'ssclTaxTotal', 'bankChargesTotal',
            'paymentByMethod', 'paymentByType', 'paymentByStatus', 'breakdownScope', 'monthlyIncome',
            'weeklyTrend', 'districtAnalytics', 'courses', 'intakes', 'courseWiseSummary',
            'newRegistrations', 'newRegistrationsCount', 'newRegistrationsAmount',
            'ongoingCoursesCount', 'ongoingCoursesAmount'
        ));
    }

    /**
     * Helper: Get date from range string
     */
    private function getDateFromRange($range)
    {
        return match ($range) {
            '10y' => Carbon::now()->subYears(10),
            '5y' => Carbon::now()->subYears(5),
            '2y' => Carbon::now()->subYears(2),
            '1y' => Carbon::now()->subYear(),
            '6m' => Carbon::now()->subMonths(6),
            '3m' => Carbon::now()->subMonths(3),
            '1m' => Carbon::now()->subMonth(),
            '1w' => Carbon::now()->subWeek(),
            default => Carbon::now()->subYear(),
        };
    }

    /**
     * Helper: Payment type case statement
     */
    private function getPaymentTypeCase()
    {
        $table = $this->getPaymentDetailsTable();
        $hasInstallmentType = Schema::hasColumn($table, 'installment_type');
        $hasPaymentType = Schema::hasColumn($table, 'payment_type');
        $hasMiscCategory = Schema::hasColumn($table, 'misc_category');

        // Prefer a unified type column from `installment_type` or `payment_type` if available.
        $typeExpr = null;

        if ($hasInstallmentType && $hasPaymentType) {
            $typeExpr = "COALESCE({$table}.installment_type, {$table}.payment_type)";
        } elseif ($hasInstallmentType) {
            $typeExpr = "{$table}.installment_type";
        } elseif ($hasPaymentType) {
            $typeExpr = "{$table}.payment_type";
        }

        // If neither type column exists, fall back to misc_category or unknown.
        if (is_null($typeExpr)) {
            if ($hasMiscCategory) {
                return "CASE WHEN misc_category IS NOT NULL THEN 'Miscellaneous' ELSE 'Unknown' END as type";
            }
            return "'Unknown' as type";
        }

        // Build CASE mapping for known type values, while preserving misc_category when present.
        $case = "CASE\n";

        if ($hasMiscCategory) {
            $case .= "    WHEN ({$typeExpr}) IS NULL AND misc_category IS NOT NULL THEN 'Miscellaneous'\n";
        }

        $case .= "    WHEN {$typeExpr} = '' THEN 'Unknown'\n";
        $case .= "    WHEN {$typeExpr} IS NULL THEN 'Unknown'\n";
        $case .= "    ELSE CASE\n";
        $case .= "        WHEN {$typeExpr} = 'course_fee' THEN 'Course Fee'\n";
        $case .= "        WHEN {$typeExpr} = 'franchise_fee' THEN 'Franchise Fee'\n";
        $case .= "        WHEN {$typeExpr} = 'registration_fee' THEN 'Registration Fee'\n";
        $case .= "        ELSE {$typeExpr}\n";
        $case .= "    END\nEND as type";

        return $case;
    }

    /**
     * Return the configured payment-details table name in one place.
     */
    private function getPaymentDetailsTable(): string
    {
        return (new PaymentDetail())->getTable();
    }

    /**
     * Safely support installations whose payment_details schema differs.
     */
    private function hasPaymentDetailColumn(string $column): bool
    {
        return Schema::hasColumn($this->getPaymentDetailsTable(), $column);
    }

    /**
     * Sum optional legacy columns without breaking dashboards on installations
     * that do not yet have them.
     */
    private function sumIfColumnExists($query, string $column)
    {
        if (!$this->hasPaymentDetailColumn($column)) {
            return 0;
        }

        return (clone $query)->sum($column);
    }

    private function applyRegistrationFeeTypeFilter($query, string $table): void
    {
        $hasInstallmentType = $this->hasPaymentDetailColumn('installment_type');
        $hasPaymentType = $this->hasPaymentDetailColumn('payment_type');

        if ($hasInstallmentType && $hasPaymentType) {
            $query->where(function ($typeQuery) use ($table) {
                $typeQuery->where($table . '.installment_type', 'registration_fee')
                    ->orWhere($table . '.payment_type', 'registration_fee');
            });
            return;
        }

        if ($hasInstallmentType) {
            $query->where($table . '.installment_type', 'registration_fee');
            return;
        }

        if ($hasPaymentType) {
            $query->where($table . '.payment_type', 'registration_fee');
            return;
        }

        // If the schema has neither type column, force empty results to avoid
        // mixing non-registration payments into this KPI.
        $query->whereRaw('1 = 0');
    }

    private function applyLocalCourseFeeTypeFilter($query, string $table): void
    {
        $hasInstallmentType = $this->hasPaymentDetailColumn('installment_type');
        $hasPaymentType = $this->hasPaymentDetailColumn('payment_type');

        if ($hasInstallmentType && $hasPaymentType) {
            $query->where(function ($typeQuery) use ($table) {
                $typeQuery->where($table . '.installment_type', 'course_fee')
                    ->orWhere($table . '.payment_type', 'course_fee');
            });
            return;
        }

        if ($hasInstallmentType) {
            $query->where($table . '.installment_type', 'course_fee');
            return;
        }

        if ($hasPaymentType) {
            $query->where($table . '.payment_type', 'course_fee');
            return;
        }

        $query->whereRaw('1 = 0');
    }

    private function getPaidDateSqlExpression(string $table): string
    {
        // Analytics month filters must be based on the actual effective date,
        // not the date the row happened to be created.  Retain created_at only
        // for older installations that do not yet have this column.
        if ($this->hasPaymentDetailColumn('payment_effective_date')) {
            return "{$table}.payment_effective_date";
        }

        return "{$table}.created_at";
    }

    private function fetchPendingSltLoanRecoveries(Carbon $startOfMonth, Carbon $endOfMonth)
    {
        $recordsFromReceivables = collect();
        $plansWithReceivableRecords = collect();

        if (Schema::hasTable('slt_loan_receivable_records')) {
            try {
                // Keep track of plans that already have a recovery record for this
                // month, including paid records.  Otherwise a paid record disappears
                // from this query and the fallback below incorrectly shows the plan as
                // pending again.
                $receivableRecords = SltLoanReceivableRecord::with(['studentPaymentPlan.student', 'studentPaymentPlan.course'])
                    ->whereDate('payment_effective_date', '>=', $startOfMonth->toDateString())
                    ->whereDate('payment_effective_date', '<=', $endOfMonth->toDateString())
                    ->orderBy('payment_effective_date')
                    ->get();

                $plansWithReceivableRecords = $receivableRecords
                    ->pluck('student_payment_plan_id')
                    ->filter()
                    ->unique()
                    ->values();

                $recordsFromReceivables = $receivableRecords
                    ->filter(fn ($record) => strtolower($record->status ?? 'pending') !== 'paid')
                    ->map(function ($record) {
                        $plan = $record->studentPaymentPlan;
                        $studentId = optional($plan)->student_id ?? $record->student_id;
                        $courseId = optional($plan)->course_id ?? $record->course_id;
                        $student = optional($plan)->student ?? Student::where('student_id', $studentId)->first();
                        $course = optional($plan)->course ?? Course::where('course_id', $courseId)->first();
                        $registration = CourseRegistration::with('intake')
                            ->where('student_id', $studentId)
                            ->where('course_id', $courseId)
                            ->orderByDesc('id')
                            ->first();

                        return [
                            'record_id' => $record->id,
                            'loan_installment_number' => (int) ($record->loan_installment_number ?? 0),
                            'student_name' => optional($student)->full_name ?? 'N/A',
                            'student_id_value' => optional($student)->id_value ?? null,
                            'course_name' => optional($course)->course_name ?? 'N/A',
                            'intake' => optional($registration?->intake)->batch ?? 'N/A',
                            'loan_amount' => (float) (optional($plan)->slt_loan_amount ?? $record->total_loan_amount ?? 0),
                            'installment_amount' => (float) ($record->monthly_receivable_amount ?? 0),
                            'effective_date' => optional($record->payment_effective_date)->format('Y-m-d'),
                            'course_id' => $courseId,
                        ];
                    });
            } catch (\Throwable $e) {
                \Log::warning('slt_loan_receivable_records query failed, falling back to student_payment_plans: ' . $e->getMessage());
                $recordsFromReceivables = collect();
            }
        }

        $plansWithoutReceivableRecords = StudentPaymentPlan::with(['student', 'course'])
            ->where(function ($query) {
                $query->whereRaw('LOWER(COALESCE(slt_loan_applied, "")) = ?', ['yes'])
                    ->orWhere('slt_loan_applied', '1')
                    ->orWhere('slt_loan_applied', 1)
                    ->orWhere('slt_loan_applied', true);
            })
            ->whereDate('slt_receivable_effective_date', '>=', $startOfMonth->toDateString())
            ->whereDate('slt_receivable_effective_date', '<=', $endOfMonth->toDateString())
            ->when(
                $plansWithReceivableRecords->isNotEmpty(),
                fn ($query) => $query->whereNotIn('id', $plansWithReceivableRecords)
            )
            ->orderBy('slt_receivable_effective_date')
            ->get()
            ->map(function ($plan) {
                $student = optional($plan)->student;
                $course = optional($plan)->course;
                $registration = CourseRegistration::with('intake')
                    ->where('student_id', optional($plan)->student_id)
                    ->where('course_id', optional($plan)->course_id)
                    ->orderByDesc('id')
                    ->first();

                $installmentAmount = 0;
                $loanInstallmentCount = 0;
                if ($plan->slt_loan_amount && $plan->slt_loan_years) {
                    $loanInstallmentCount = (int) $plan->slt_loan_years * 12;
                    $installmentAmount = round($plan->slt_loan_amount / $loanInstallmentCount, 2);
                }

                $existingInstallmentCount = SltLoanReceivableRecord::where('student_payment_plan_id', $plan->id)->count();
                $loanInstallmentNumber = min($existingInstallmentCount + 1, max($loanInstallmentCount, 1));

                return [
                    'record_id' => null,
                    'loan_installment_number' => $loanInstallmentNumber,
                    'student_name' => optional($student)->full_name ?? 'N/A',
                    'student_id_value' => optional($student)->id_value ?? null,
                    'course_name' => optional($course)->course_name ?? 'N/A',
                    'intake' => optional($registration?->intake)->batch ?? 'N/A',
                    'loan_amount' => (float) ($plan->slt_loan_amount ?? 0),
                    'installment_amount' => $installmentAmount,
                    'effective_date' => optional($plan->slt_receivable_effective_date)->format('Y-m-d'),
                    'course_id' => optional($plan)->course_id,
                ];
            });

        return $recordsFromReceivables
            ->concat($plansWithoutReceivableRecords)
            ->sortBy('effective_date')
            ->values();
    }

    /**
     * Use the best available payment date, with created_at as a safe fallback.
     */
    private function getDashboardDateSqlExpression(string $table): string
    {
        $dateColumns = [];

        foreach (['payment_effective_date', 'payment_date', 'due_date'] as $column) {
            if (Schema::hasColumn($table, $column)) {
                $dateColumns[] = "{$table}.{$column}";
            }
        }

        $dateColumns[] = "{$table}.created_at";

        return 'COALESCE(' . implode(', ', $dateColumns) . ')';
    }

    /**
     * 🔹 Installment KPI – returns aggregated totals for a specific
     *     location / course / intake / payment-type / installment-number combo.
     *
     * Called via AJAX from the Payment Dashboard KPI card.
     */
    public function getInstallmentKpi(Request $request)
    {
        $location        = $request->input('location');
        $courseId        = $request->input('course_id') ? (int) $request->input('course_id') : null;
        $intakeId        = $request->input('intake_id') ? (int) $request->input('intake_id') : null;
        $paymentType     = $request->input('payment_type');       // franchise_fee | course_fee | registration_fee
        $installmentNo   = $request->input('installment_no') !== null && $request->input('installment_no') !== ''
                            ? (int) $request->input('installment_no')
                            : null;

        $table = $this->getPaymentDetailsTable();

        // ── Build base query joining payment_details → course_registration ──
        $base = PaymentDetail::query()
            ->join('course_registration', 'payment_details.course_registration_id', '=', 'course_registration.id')
            ->when($location, fn ($q) => $q->where('course_registration.location', $location))
            ->when($courseId, fn ($q) => $q->where('course_registration.course_id', $courseId))
            ->when($intakeId, fn ($q) => $q->where('course_registration.intake_id', $intakeId));

        // ── Payment-type filter ──────────────────────────────────────────────
        if ($paymentType) {
            $hasInstallmentType = $this->hasPaymentDetailColumn('installment_type');
            $hasPaymentType     = $this->hasPaymentDetailColumn('payment_type');

            if ($hasInstallmentType && $hasPaymentType) {
                $base->where(function ($q) use ($table, $paymentType) {
                    $q->where("{$table}.installment_type", $paymentType)
                      ->orWhere("{$table}.payment_type", $paymentType);
                });
            } elseif ($hasInstallmentType) {
                $base->where("{$table}.installment_type", $paymentType);
            } elseif ($hasPaymentType) {
                $base->where("{$table}.payment_type", $paymentType);
            }
        }

        // ── Installment-number filter ────────────────────────────────────────
        if ($installmentNo !== null) {
            $base->where("{$table}.installment_number", $installmentNo);
        }

        // ── Aggregate ───────────────────────────────────────────────────────
        $paidTotal    = (float) (clone $base)->where("{$table}.status", 'paid')->sum("{$table}.total_fee");
        $pendingTotal = (float) (clone $base)->where("{$table}.status", 'pending')->sum("{$table}.total_fee");
        $totalCount   = (clone $base)->count();
        $paidCount    = (clone $base)->where("{$table}.status", 'paid')->count();

        // ── Distinct installment numbers available for this filter combo ─────
        $availableInstallmentNos = (clone $base)
            ->select("{$table}.installment_number")
            ->distinct()
            ->orderBy("{$table}.installment_number")
            ->pluck('installment_number')
            ->filter(fn ($v) => $v !== null)
            ->values();

        return response()->json([
            'success'          => true,
            'paid_total'       => $paidTotal,
            'pending_total'    => $pendingTotal,
            'total_count'      => $totalCount,
            'paid_count'       => $paidCount,
            'available_installment_nos' => $availableInstallmentNos,
            'filters' => [
                'location'       => $location,
                'course_id'      => $courseId,
                'intake_id'      => $intakeId,
                'payment_type'   => $paymentType,
                'installment_no' => $installmentNo,
            ],
        ]);
    }

    /**
     * 🔹 Export Installment KPI transactions to PDF
     */
    public function exportInstallmentPdf(Request $request)
    {
        $location        = $request->input('location');
        $courseId        = $request->input('course_id') ? (int) $request->input('course_id') : null;
        $intakeId        = $request->input('intake_id') ? (int) $request->input('intake_id') : null;
        $paymentType     = $request->input('payment_type');
        $installmentNo   = $request->input('installment_no') !== null && $request->input('installment_no') !== ''
                            ? (int) $request->input('installment_no')
                            : null;
        $statusFilter    = $request->input('status', 'all');

        $table = $this->getPaymentDetailsTable();

        $query = PaymentDetail::query()
            ->with(['student', 'registration.course', 'registration.intake'])
            ->join('course_registration', 'payment_details.course_registration_id', '=', 'course_registration.id')
            ->select('payment_details.*')
            ->when($location, fn ($q) => $q->where('course_registration.location', $location))
            ->when($courseId, fn ($q) => $q->where('course_registration.course_id', $courseId))
            ->when($intakeId, fn ($q) => $q->where('course_registration.intake_id', $intakeId));

        if ($paymentType) {
            $hasInstallmentType = $this->hasPaymentDetailColumn('installment_type');
            $hasPaymentType     = $this->hasPaymentDetailColumn('payment_type');

            if ($hasInstallmentType && $hasPaymentType) {
                $query->where(function ($q) use ($table, $paymentType) {
                    $q->where("{$table}.installment_type", $paymentType)
                      ->orWhere("{$table}.payment_type", $paymentType);
                });
            } elseif ($hasInstallmentType) {
                $query->where("{$table}.installment_type", $paymentType);
            } elseif ($hasPaymentType) {
                $query->where("{$table}.payment_type", $paymentType);
            }
        }

        if ($installmentNo !== null) {
            $query->where("{$table}.installment_number", $installmentNo);
        }

        if ($statusFilter !== 'all') {
            $query->where("{$table}.status", $statusFilter);
        }

        $query->orderBy("{$table}.created_at", 'desc');

        $transactions = $query->get();

        $paidTotal    = $transactions->where('status', 'paid')->sum('total_fee');
        $pendingTotal = $transactions->where('status', 'pending')->sum('total_fee');
        $grandTotal   = $transactions->sum('total_fee');

        $courseName = $courseId ? \App\Models\Course::find($courseId)?->course_name : 'All Courses';
        $intakeName = $intakeId ? \App\Models\Intake::find($intakeId)?->batch : 'All Intakes';

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payments.pdf.installment_report', [
            'transactions' => $transactions,
            'filters'      => [
                'location'       => $location ?: 'All Locations',
                'course'         => $courseName,
                'intake'         => $intakeName,
                'payment_type'   => $paymentType ?: 'All Types',
                'installment_no' => $installmentNo !== null ? $installmentNo : 'All',
                'status'         => ucfirst($statusFilter)
            ],
            'paidTotal'    => $paidTotal,
            'pendingTotal' => $pendingTotal,
            'grandTotal'   => $grandTotal,
        ]);

        return $pdf->download('installment_report_' . strtolower($statusFilter) . '_' . date('Ymd_His') . '.pdf');
    }

    /**
     * 🔹 Live Payment Feed (for real-time updates)
     */
    public function liveFeed(Request $request)
    {
        $lastId = $request->input('last_id', 0);

        $payments = PaymentDetail::where('id', '>', $lastId)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'payments' => $payments,
            'last_id' => $payments->max('id') ?? $lastId,
            'count' => $payments->count()
        ]);
    }

}
