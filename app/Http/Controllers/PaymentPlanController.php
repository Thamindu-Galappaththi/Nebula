<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\PaymentPlan;
use App\Models\Intake;
use App\Models\CourseRegistration;
use App\Models\StudentPaymentPlan;
use App\Models\PaymentInstallment;
use App\Models\PaymentPlanDiscount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PaymentPlanExport;

class PaymentPlanController extends Controller
{
    public function index(Request $request)
    {
        $locations = ['Welisara', 'Moratuwa', 'Peradeniya'];
        $perPage = $this->resolvedPerPage($request);

        $plans = $this->filteredPlansQuery($request)
            ->paginate($perPage)
            ->withQueryString();

        $courses = Course::query()
            ->select('course_id', 'course_name')
            ->when($request->filled('location'), fn ($q) => $q->where('location', $request->location))
            ->orderBy('course_name')
            ->get();

        $intakes = collect();
        if ($request->filled('course_id')) {
            $course = Course::find((int) $request->course_id);
            if ($course) {
                $intakes = Intake::forCourse($course, $request->input('location'))
                    ->orderBy('batch')
                    ->get(['intake_id', 'batch']);
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'html' => view('payments.partials.payment_plan_results', compact('plans'))->render(),
            ]);
        }

        return view('payments.payment_plan_index', compact('plans', 'locations', 'courses', 'intakes'));
    }

    public function exportExcel(Request $request)
    {
        $plans = $this->filteredPlansQuery($request)->limit(2000)->get();

        return Excel::download(
            new PaymentPlanExport($this->mapPlansForExport($plans)),
            'payment_plans_' . now()->format('Y-m-d') . '.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $maxRows = 250;
        $query = $this->filteredPlansQuery($request);
        $totalRecords = (clone $query)->count();
        $plans = $query->limit($maxRows)->get();

        $pdf = Pdf::loadView('payments.payment_plan_export_pdf', [
            'rows' => $this->mapPlansForExport($plans),
            'totalRecords' => $totalRecords,
            'maxRows' => $maxRows,
            'generatedAt' => now()->format('Y-m-d H:i'),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('payment_plans_' . now()->format('Y-m-d') . '.pdf');
    }

    public function getCoursesByLocation(Request $request)
    {
        $request->validate([
            'location' => 'nullable|string',
        ]);

        $courses = Course::query()
            ->when($request->filled('location'), fn ($q) => $q->where('location', $request->location))
            ->orderBy('course_name')
            ->get(['course_id','course_name']);

        return response()->json([
            'success' => true,
            'data' => $courses,
            'courses' => $courses,
            'message' => $courses->isEmpty() ? 'No courses found.' : 'Courses loaded successfully.'
        ]);
    }


    // Your original page now lives here, unchanged logic:
    public function create(Request $request)
{
    $locations = ['Welisara','Moratuwa','Peradeniya'];
    $selectedLocation = $request->query('location');

    // Filter courses based on selected location
    $courses = collect();
    if ($selectedLocation) {
        $courses = Course::where('location', $selectedLocation)
            ->orderBy('course_name')
    ->get(['course_id', 'course_name', 'course_type']);
    }

    return view('payments.payment_plan', compact('courses', 'locations', 'selectedLocation'));
}



    public function store(Request $request)
{
    try {
        $validated = $request->validate([
            'location' => 'required|string',
            'course' => 'required|exists:courses,course_id',
            'intake' => 'required|exists:intakes,intake_id',
            'registrationFee' => 'required|numeric|min:0',
            'localFee' => 'required|numeric|min:0',
            'internationalFee' => 'required|numeric|min:0',
            'currency' => 'required|string',
            'ssclTax' => 'required|numeric|min:0',
            'bankCharges' => 'nullable|numeric|min:0',
            'applyDiscount' => 'required|string',
            'fullPaymentDiscount' => 'nullable|numeric|min:0',
            'installmentPlan' => 'nullable|string',
            'installments' => 'nullable',
        ]);

        $installments = $request->input('installments');
        if (is_string($installments)) {
            $installments = json_decode($installments, true);
        }
        if (!is_array($installments)) {
            $installments = [];
        }

        $course = Course::find($validated['course']);
        $courseType = $course->course_type ?? null;
        $supportsCourseType = Schema::hasColumn('payment_plans', 'course_type');
        $installmentTotals = $this->calculateInstallmentTotals($installments);
        $localFee = (float) $validated['localFee'];
        $internationalFee = (float) $validated['internationalFee'];

        if ($request->input('franchisePayment') === 'yes' && !empty($installments)) {
            $localFee = $installmentTotals['local_fee'];
            $internationalFee = $installmentTotals['international_fee'];
        }

        $exists = PaymentPlan::where('location', $validated['location'])
            ->where('course_id', $validated['course'])
            ->where('intake_id', $validated['intake'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'A payment plan already exists for this Location, Course, and Intake.');
        }

        $syncSummary = DB::transaction(function () use ($validated, $request, $installments, $courseType, $supportsCourseType, $localFee, $internationalFee) {
            $planData = [
                'location' => $validated['location'],
                'course_id' => $validated['course'],
                'intake_id' => $validated['intake'],
                'registration_fee' => $validated['registrationFee'],
                'local_fee' => $localFee,
                'international_fee' => $internationalFee,
                'international_currency' => $validated['currency'],
                'sscl_tax' => $validated['ssclTax'],
                'bank_charges' => $validated['bankCharges'] ?? null,
                'apply_discount' => $validated['applyDiscount'] === 'yes',
                'discount' => $validated['fullPaymentDiscount'] ?? null,
                'installment_plan' => $request->input('franchisePayment') === 'yes',
                'installments' => $installments ?: null,
            ];

            if ($supportsCourseType) {
                $planData['course_type'] = $courseType;
            }

            $plan = PaymentPlan::create($planData);

            $this->syncIntakeFeesFromPaymentPlan($plan);

            return $this->syncStudentsForIntakePlan($plan);
        });

        $syncedPlans = ($syncSummary['plans_created'] ?? 0) + ($syncSummary['plans_updated'] ?? 0);

        return redirect()->back()->with(
            'success',
            "Payment plan created successfully! Synced {$syncedPlans} student payment plan(s)."
        );

    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()->back()->withErrors($e->errors())->withInput();
    } catch (\Exception $e) {
        Log::error('PaymentPlan store failed: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()->back()
            ->with('error', 'An error occurred while creating the payment plan. Please try again.')
            ->withInput();
    }
}

    public function edit($id)
    {
        $plan = PaymentPlan::with('course','intake')->findOrFail($id);
        $courses = Course::orderBy('course_name')->get(['course_id','course_name']);
        $courseName = optional($plan->course)->course_name;

        $intakesQuery = Intake::query()
            ->when(!empty($plan->location), function ($query) use ($plan) {
                $query->where('location', $plan->location);
            });

        if (Schema::hasColumn('intakes', 'course_id')) {
            $intakesQuery->where('course_id', $plan->course_id);
        } elseif ($courseName) {
            $intakesQuery->where('course_name', $courseName);
        } else {
            $intakesQuery->where('intake_id', $plan->intake_id);
        }

        $intakes = $intakesQuery
            ->orderBy('batch')
            ->get(['intake_id','batch']);

        // decode installments JSON (safe)
        $installments = is_array($plan->installments)
            ? $plan->installments
            : (json_decode($plan->installments, true) ?? []);

        $installments = collect($installments)
            ->filter(fn ($installment) => is_array($installment))
            ->map(function ($installment, $index) {
                $dueDate = $installment['due_date'] ?? null;
                if (is_array($dueDate)) {
                    $dueDate = null;
                }

                return [
                    'installment_number' => $installment['installment_number'] ?? ($index + 1),
                    'due_date' => is_scalar($dueDate) ? (string) $dueDate : '',
                    'local_amount' => is_numeric($installment['local_amount'] ?? null)
                        ? (string) $installment['local_amount']
                        : (is_numeric($installment['amount'] ?? null) ? (string) $installment['amount'] : ''),
                    'international_amount' => is_numeric($installment['international_amount'] ?? null)
                        ? (string) $installment['international_amount']
                        : '',
                    'apply_tax' => !empty($installment['apply_tax']),
                ];
            })
            ->values()
            ->all();

        return view('payments.payment_plan_edit', compact('plan','courses','intakes','installments'));
    }


public function update(Request $request, $id)
{
    try {
        $plan = PaymentPlan::findOrFail($id);

        Log::info('PaymentPlan update request received', [
            'plan_id' => $id,
            'payload' => $request->all(),
        ]);

        $request->validate([
            'location'               => 'required|string',
            'course_id'              => 'required|exists:courses,course_id',
            'intake_id'              => 'nullable|exists:intakes,intake_id',
            'registration_fee'       => 'required|numeric|min:0',
            'local_fee'              => 'required|numeric|min:0',
            'international_fee'      => 'required|numeric|min:0',
            'international_currency' => 'required|string',
            'sscl_tax'               => 'nullable|numeric|min:0',
            'bank_charges'           => 'nullable|numeric|min:0',
            'apply_discount'         => 'nullable|boolean',
            'discount'               => 'nullable|numeric|min:0',
            'installment_plan'       => 'nullable|boolean',
            'installments'           => 'nullable|array',
        ]);

        $course = Course::find($request->course_id);
        $courseType = $course->course_type ?? null;
        $supportsCourseType = Schema::hasColumn('payment_plans', 'course_type');
        $installments = $this->normalizeTemplateInstallments($request->input('installments', []));
        $installmentTotals = $this->calculateInstallmentTotals($installments);
        $localFee = (float) $request->local_fee;
        $internationalFee = (float) $request->international_fee;

        if ($request->boolean('installment_plan') && !empty($installments)) {
            $localFee = $installmentTotals['local_fee'];
            $internationalFee = $installmentTotals['international_fee'];
        }

        $syncSummary = DB::transaction(function () use ($request, $plan, $courseType, $installments, $supportsCourseType, $localFee, $internationalFee) {
            $plan->location               = $request->location;
            $plan->course_id              = $request->course_id;
            $plan->intake_id              = $request->intake_id;
            $plan->registration_fee       = $request->registration_fee;
            $plan->local_fee              = $localFee;
            $plan->international_fee      = $internationalFee;
            $plan->international_currency = $request->international_currency;
            $plan->sscl_tax               = $request->sscl_tax;
            $plan->bank_charges           = $request->bank_charges;
            $plan->apply_discount         = $request->apply_discount ? 1 : 0;
            $plan->discount               = $request->discount;
            $plan->installment_plan       = $request->installment_plan ? 1 : 0;
            $plan->installments = $installments;

            if ($supportsCourseType) {
                $plan->course_type = $courseType;
            }

            $plan->save();

            $this->syncIntakeFeesFromPaymentPlan($plan);

            return $this->syncStudentsForIntakePlan($plan);
        });

        $syncedPlans = ($syncSummary['plans_created'] ?? 0) + ($syncSummary['plans_updated'] ?? 0);

        return redirect()
            ->route('payment.plan.index')
            ->with('success', "Payment plan updated successfully. Synced {$syncedPlans} student payment plan(s).");

    } catch (\Illuminate\Validation\ValidationException $e) {
        return redirect()
            ->back()
            ->withErrors($e->errors())
            ->withInput();

    } catch (\Exception $e) {
        Log::error('PaymentPlan update failed: ' . $e->getMessage(), [
            'trace' => $e->getTraceAsString()
        ]);

        return redirect()
            ->back()
            ->with('error', 'An unexpected error occurred while updating the payment plan.')
            ->withInput();
    }
}

private function normalizeTemplateInstallments($installments): array
{
    if (is_string($installments)) {
        $decoded = json_decode($installments, true);
        $installments = is_array($decoded) ? $decoded : [];
    }

    if (!is_array($installments)) {
        return [];
    }

    $normalized = [];
    foreach (array_values($installments) as $index => $installment) {
        if (!is_array($installment)) {
            continue;
        }

        $dueDate = $installment['due_date'] ?? null;
        $localAmount = (float) ($installment['local_amount'] ?? $installment['amount'] ?? 0);
        $internationalAmount = (float) ($installment['international_amount'] ?? 0);
        $hasTax = !empty($installment['apply_tax']);

        if (!$dueDate && $localAmount <= 0 && $internationalAmount <= 0 && !$hasTax) {
            continue;
        }

        $normalized[] = [
            'installment_number' => $index + 1,
            'due_date' => $dueDate,
            'local_amount' => $localAmount,
            'international_amount' => $internationalAmount,
            'apply_tax' => $hasTax,
        ];
    }

    return $normalized;
}

    private function calculateInstallmentTotals(array $installments): array
    {
        $localFee = 0.0;
        $internationalFee = 0.0;

        foreach ($installments as $installment) {
            if (!is_array($installment)) {
                continue;
            }

            $localFee += (float) ($installment['local_amount'] ?? $installment['amount'] ?? 0);
            $internationalFee += (float) ($installment['international_amount'] ?? 0);
        }

        return [
            'local_fee' => round($localFee, 2),
            'international_fee' => round($internationalFee, 2),
        ];
    }



    /**
     * Create or refresh student payment plans for the students currently registered in the intake.
     */
    private function syncStudentsForIntakePlan(PaymentPlan $templatePlan): array
    {
        $registrations = CourseRegistration::query()
            ->where('course_id', $templatePlan->course_id)
            ->where('intake_id', $templatePlan->intake_id)
            ->when(!empty($templatePlan->location), function ($query) use ($templatePlan) {
                $query->where('location', $templatePlan->location);
            })
            ->whereIn('status', ['Registered', 'registered'])
            ->get(['id', 'student_id', 'course_id']);

        $summary = [
            'registrations_found' => $registrations->count(),
            'plans_created' => 0,
            'plans_updated' => 0,
            'installments_synced' => 0,
        ];

        foreach ($registrations as $registration) {
            $studentPlan = StudentPaymentPlan::where('student_id', $registration->student_id)
                ->where('course_id', $registration->course_id)
                ->where('status', '!=', 'archived')
                ->orderByDesc('id')
                ->first();

            $isNew = !$studentPlan;

            if (!$studentPlan) {
                $studentPlan = new StudentPaymentPlan([
                    'student_id' => $registration->student_id,
                    'course_id' => $registration->course_id,
                ]);
            }

            $computed = $this->buildStudentPlanFromIntake($templatePlan, $studentPlan);

            $studentPlan->fill($computed['attributes']);
            $studentPlan->status = $studentPlan->status ?: 'active';
            $studentPlan->save();

            $summary[$isNew ? 'plans_created' : 'plans_updated']++;
            $summary['installments_synced'] += $this->syncPlanInstallments($studentPlan, $computed['installments']);

            if (Schema::hasColumn('course_registration', 'payment_plan_id')) {
                DB::table('course_registration')
                    ->where('id', $registration->id)
                    ->update(array_merge([
                        'payment_plan_id' => $studentPlan->id,
                        'updated_at' => now(),
                    ], \App\Support\UserTrackingData::forUpdate()));
            }
        }

        Log::info('Synced intake payment plan to student payment plans', [
            'payment_plan_id' => $templatePlan->id,
            'course_id' => $templatePlan->course_id,
            'intake_id' => $templatePlan->intake_id,
            'summary' => $summary,
        ]);

        return $summary;
    }

    private function buildStudentPlanFromIntake(PaymentPlan $templatePlan, ?StudentPaymentPlan $existingPlan = null): array
    {
        $registrationFee = round((float) ($templatePlan->registration_fee ?? 0), 2);
        $localFee = round((float) ($templatePlan->local_fee ?? 0), 2);
        $totalAmount = round($registrationFee + $localFee, 2);
        $paymentPlanType = $this->resolveStudentPaymentPlanType($templatePlan, $existingPlan);

        $discountSummary = $this->getStudentDiscountSummary($templatePlan, $existingPlan, $registrationFee, $totalAmount, $paymentPlanType);
        $rows = $paymentPlanType === 'full'
            ? $this->getFullPaymentRows($templatePlan, $totalAmount)
            : $this->getTemplateInstallmentRows($templatePlan, $localFee);

        $lastIndex = count($rows) - 1;
        $discountedBases = array_map(function ($row) {
            return round((float) ($row['base_amount'] ?? 0), 2);
        }, $rows);

        $normalDiscountApplied = 0.0;
        $discountApplied = array_fill(0, count($discountedBases), 0.0);
        $remainingNormalDiscount = $discountSummary['normal_discount_total'];

        for ($i = $lastIndex; $i >= 0 && $remainingNormalDiscount > 0; $i--) {
            $available = $discountedBases[$i];
            $deduct = min($available, $remainingNormalDiscount);
            $discountedBases[$i] = round($available - $deduct, 2);
            $discountApplied[$i] = round($deduct, 2);
            $remainingNormalDiscount -= $deduct;
            $normalDiscountApplied += $deduct;
        }

        $registrationDiscountApplied = 0.0;
        if (!empty($discountedBases)) {
            $registrationDiscountApplied = min($discountSummary['registration_discount_excess'], $discountedBases[0]);
            $discountedBases[0] = round($discountedBases[0] - $registrationDiscountApplied, 2);
        }

        $sumAfterDiscounts = round(array_sum($discountedBases), 2);
        $sltLoanApplied = ($existingPlan?->slt_loan_applied ?? 'no') === 'yes' ? 'yes' : 'no';
        $sltLoanAmount = $sltLoanApplied === 'yes' ? round((float) ($existingPlan?->slt_loan_amount ?? 0), 2) : 0.0;
        $sltLoanAmount = min($sltLoanAmount, $sumAfterDiscounts);
        $loanStartInstallment = $sltLoanAmount > 0 ? (int) ($existingPlan?->slt_loan_start_installment ?? 1) : null;
        $loanYears = $sltLoanAmount > 0 ? ($existingPlan?->slt_loan_years ?? null) : null;
        $loanAllocations = $this->allocateSltLoanByStartInstallment($rows, $discountedBases, $sltLoanAmount, $loanStartInstallment);
        $sltLoanAmount = round(array_sum($loanAllocations), 2);

        $installments = [];

        foreach ($rows as $index => $row) {
            $discountedBase = $discountedBases[$index] ?? 0.0;
            $isLast = $index === $lastIndex;
            $loanShare = round($loanAllocations[$index] ?? 0, 2);
            $finalAmount = round(max(0, $discountedBase - $loanShare), 2);

            $installments[] = [
                'installment_number' => (int) ($row['installment_number'] ?? ($index + 1)),
                'due_date' => $row['due_date'] ?? null,
                'status' => $row['status'] ?? 'pending',
                'base_amount' => round((float) ($row['base_amount'] ?? 0), 2),
                'amount' => max(0, $finalAmount),
                'discount_amount' => $isLast ? round($normalDiscountApplied, 2) : 0.0,
                'discount_note' => $isLast && $normalDiscountApplied > 0 ? 'Normal Discounts Applied' : null,
                'slt_loan_amount' => max(0, $loanShare),
                'registration_fee_discount_applied' => $index === 0 ? round($registrationDiscountApplied, 2) : 0.0,
                'registration_fee_discount_note' => $index === 0 && $registrationDiscountApplied > 0 ? 'Reg. Fee Excess' : null,
                'final_amount' => max(0, $finalAmount),
            ];
        }

        return [
            'attributes' => [
                'payment_plan_type' => $paymentPlanType,
                'slt_loan_applied' => $sltLoanApplied,
                'slt_loan_amount' => $sltLoanAmount,
                'slt_loan_start_installment' => $loanStartInstallment,
                'slt_loan_years' => $loanYears,
                'total_amount' => $totalAmount,
                'final_amount' => round(array_sum(array_column($installments, 'final_amount')), 2),
                'remaining_registration_discount' => $discountSummary['remaining_registration_discount'],
                'status' => $existingPlan?->status ?? 'active',
            ],
            'installments' => $installments,
        ];
    }

    private function resolveStudentPaymentPlanType(PaymentPlan $templatePlan, ?StudentPaymentPlan $existingPlan = null): string
    {
        $existingType = strtolower((string) ($existingPlan?->payment_plan_type ?? ''));
        if (in_array($existingType, ['full', 'installments'], true)) {
            return $existingType;
        }

        return $templatePlan->installment_plan ? 'installments' : 'full';
    }

    private function getStudentDiscountSummary(PaymentPlan $templatePlan, ?StudentPaymentPlan $studentPlan, float $registrationFee, float $totalFeeForDiscount, string $paymentPlanType): array
    {
        $isFullPayment = $paymentPlanType === 'full';
        $normalPercentage = $isFullPayment && $templatePlan->apply_discount ? (float) ($templatePlan->discount ?? 0) : 0.0;
        $normalFixed = 0.0;
        $registrationDiscountAmount = 0.0;

        if ($studentPlan && $studentPlan->exists) {
            $discountRows = PaymentPlanDiscount::with('discount')
                ->where('payment_plan_id', $studentPlan->id)
                ->get();

            foreach ($discountRows as $row) {
                $category = $row->discount->discount_category ?? 'local_course_fee';
                $type = strtolower((string) ($row->discount_type ?? ''));
                $value = (float) ($row->discount_value ?? 0);

                if ($category === 'registration_fee' && $isFullPayment) {
                    $registrationDiscountAmount += $type === 'percentage'
                        ? ($registrationFee * ($value / 100))
                        : $value;
                } elseif ($isFullPayment) {
                    if ($type === 'percentage') {
                        $normalPercentage += $value;
                    } elseif ($type === 'amount') {
                        $normalFixed += $value;
                    }
                }
            }
        }

        $normalDiscountTotal = (($totalFeeForDiscount * $normalPercentage) / 100) + $normalFixed;
        $registrationDiscountExcess = max(0, $registrationDiscountAmount - $registrationFee);
        $existingRemaining = $isFullPayment && $studentPlan && $studentPlan->exists
            ? (float) ($studentPlan->remaining_registration_discount ?? 0)
            : 0.0;
        $remainingRegistrationDiscount = $existingRemaining > 0
            ? min($existingRemaining, $registrationDiscountExcess > 0 ? round($registrationDiscountExcess, 2) : $existingRemaining)
            : round($registrationDiscountExcess, 2);

        return [
            'normal_discount_total' => round($normalDiscountTotal, 2),
            'registration_discount_excess' => round($registrationDiscountExcess, 2),
            'remaining_registration_discount' => $remainingRegistrationDiscount,
        ];
    }

    private function getFullPaymentRows(PaymentPlan $templatePlan, float $totalAmount): array
    {
        $templateRows = is_array($templatePlan->installments)
            ? $templatePlan->installments
            : (json_decode($templatePlan->installments ?? '[]', true) ?: []);

        $dueDate = $templateRows[0]['due_date'] ?? null;
        if (!$dueDate) {
            $dueDate = $templatePlan->intake && $templatePlan->intake->start_date
                ? date('Y-m-d', strtotime($templatePlan->intake->start_date))
                : now()->addDays(7)->toDateString();
        }

        return [[
            'installment_number' => 1,
            'due_date' => $dueDate,
            'base_amount' => round($totalAmount, 2),
            'status' => 'pending',
        ]];
    }

    private function allocateSltLoanByStartInstallment(array $rows, array $discountedBases, float $loanAmount, ?int $startInstallment): array
    {
        $allocations = array_fill(0, count($rows), 0.0);
        $remainingLoan = round(max(0, $loanAmount), 2);

        if ($remainingLoan <= 0 || empty($rows)) {
            return $allocations;
        }

        $startInstallment = $startInstallment ?: 1;

        foreach ($rows as $index => $row) {
            $installmentNumber = (int) ($row['installment_number'] ?? ($index + 1));

            if ($installmentNumber < $startInstallment || $remainingLoan <= 0) {
                continue;
            }

            $available = round(max(0, (float) ($discountedBases[$index] ?? 0)), 2);
            $deduct = min($available, $remainingLoan);
            $allocations[$index] = round($deduct, 2);
            $remainingLoan = round($remainingLoan - $deduct, 2);
        }

        return $allocations;
    }

    private function getTemplateInstallmentRows(PaymentPlan $templatePlan, float $localFee): array
    {
        $rows = [];
        $templateRows = is_array($templatePlan->installments)
            ? $templatePlan->installments
            : (json_decode($templatePlan->installments ?? '[]', true) ?: []);

        foreach ($templateRows as $index => $row) {
            $baseAmount = round((float) ($row['local_amount'] ?? $row['amount'] ?? 0), 2);

            if ($baseAmount <= 0) {
                continue;
            }

            $rows[] = [
                'installment_number' => (int) ($row['installment_number'] ?? ($index + 1)),
                'due_date' => $row['due_date'] ?? null,
                'base_amount' => $baseAmount,
                'status' => 'pending',
            ];
        }

        if (empty($rows)) {
            $defaultDueDate = $templatePlan->intake && $templatePlan->intake->start_date
                ? date('Y-m-d', strtotime($templatePlan->intake->start_date))
                : now()->addDays(7)->toDateString();

            $rows[] = [
                'installment_number' => 1,
                'due_date' => $defaultDueDate,
                'base_amount' => $localFee,
                'status' => 'pending',
            ];
        }

        return $rows;
    }

    private function syncPlanInstallments(StudentPaymentPlan $studentPlan, array $installments): int
    {
        $seenNumbers = [];

        foreach ($installments as $installment) {
            $seenNumbers[] = (int) $installment['installment_number'];

            $existing = PaymentInstallment::where('payment_plan_id', $studentPlan->id)
                ->where('installment_number', $installment['installment_number'])
                ->first();

            PaymentInstallment::updateOrCreate(
                [
                    'payment_plan_id' => $studentPlan->id,
                    'installment_number' => $installment['installment_number'],
                ],
                [
                    'due_date' => $installment['due_date'],
                    'amount' => $installment['amount'],
                    'base_amount' => $installment['base_amount'],
                    'discount_amount' => $installment['discount_amount'],
                    'discount_note' => $installment['discount_note'],
                    'slt_loan_amount' => $installment['slt_loan_amount'],
                    'registration_fee_discount_applied' => $installment['registration_fee_discount_applied'],
                    'registration_fee_discount_note' => $installment['registration_fee_discount_note'],
                    'final_amount' => $installment['final_amount'],
                    'installment_type' => 'local',
                    'status' => $existing?->status ?? $installment['status'],
                    'paid_date' => $existing?->paid_date,
                    'approved_late_fee' => $existing?->approved_late_fee ?? 0,
                    'calculated_late_fee' => $existing?->calculated_late_fee ?? 0,
                ]
            );
        }

        if (!empty($seenNumbers)) {
            PaymentInstallment::where('payment_plan_id', $studentPlan->id)
                ->whereNotIn('installment_number', $seenNumbers)
                ->where('status', '!=', 'paid')
                ->delete();
        }

        return count($seenNumbers);
    }

    /**
     * Validate that the sum of installment amounts matches the course fees
     */
    private function validateInstallmentAmounts($installments, $localFee, $internationalFee)
    {
        $totalLocalAmount = 0;
        $totalInternationalAmount = 0;

        foreach ($installments as $installment) {
            $totalLocalAmount += floatval($installment['local_amount'] ?? 0);
            $totalInternationalAmount += floatval($installment['international_amount'] ?? 0);
        }

        $errors = [];

        // Check if local amounts sum equals local course fee
        if (abs($totalLocalAmount - $localFee) > 0.01) { // Using small tolerance for floating point comparison
            $errors[] = "The sum of local installment amounts (Rs. " . number_format($totalLocalAmount, 2) . ") must equal the local course fee (Rs. " . number_format($localFee, 2) . "). Difference: Rs. " . number_format(abs($totalLocalAmount - $localFee), 2);
        }

        // Check if international amounts sum equals franchise payment amount
        if (abs($totalInternationalAmount - $internationalFee) > 0.01) { // Using small tolerance for floating point comparison
            $errors[] = "The sum of international installment amounts (" . number_format($totalInternationalAmount, 2) . ") must equal the franchise payment amount (" . number_format($internationalFee, 2) . "). Difference: " . number_format(abs($totalInternationalAmount - $internationalFee), 2);
        }

        if (!empty($errors)) {
            // Create a custom validation exception with detailed messages
            $validator = validator([], []);
            $validator->errors()->add('installments', $errors);

            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * API endpoint to fetch intake fee details for autofill in payment plan page.
     */
    public function getIntakeFees(Request $request)
    {
        $request->validate([
            'course_id' => 'required|integer',
            'location' => 'required|string',
            'intake_id' => 'required|integer',
        ]);

        $course = \App\Models\Course::find($request->course_id);
        if (!$course) {
            return response()->json(['success' => false, 'message' => 'Course not found.'], 404);
        }

        $intake = \App\Models\Intake::forCourse($course, $request->location)
            ->where('intake_id', $request->intake_id)
            ->first();

        if (!$intake) {
            return response()->json(['success' => false, 'message' => 'No intake found for this course/location.'], 404);
        }

        return response()->json([
            'success' => true,
            'registration_fee' => $intake->registration_fee,
            'course_fee' => $intake->course_fee,
            'franchise_payment' => $intake->franchise_payment,
            'franchise_payment_currency' => $intake->franchise_payment_currency ?? 'LKR',
            'sscl_tax' => $intake->sscl_tax ?? 0.00,
            'bank_charges' => $intake->bank_charges ?? 0.00,
        ]);
    }

    private function syncIntakeFeesFromPaymentPlan(PaymentPlan $plan): void
    {
        if (!$plan->intake_id) {
            return;
        }

        $intake = Intake::find($plan->intake_id);
        if (!$intake) {
            return;
        }

        $intake->update([
            'registration_fee' => $plan->registration_fee,
            'course_fee' => $plan->local_fee,
            'franchise_payment' => $plan->international_fee,
            'franchise_payment_currency' => $plan->international_currency ?: 'LKR',
            'sscl_tax' => $plan->sscl_tax ?? 0,
            'bank_charges' => $plan->bank_charges ?? 0,
        ]);
    }
    public function getIntakesByCourse(Request $request)
{
    $request->validate([
        'course_id' => 'required|integer',
        'location'  => 'nullable|string',
    ]);

    $course = Course::find($request->course_id);
    if (!$course) {
        return response()->json(['success' => false, 'data' => []]);
    }

    $intakes = Intake::forCourse($course, $request->location)
        ->orderBy('batch')
        ->get(['intake_id','batch']);

    return response()->json([
        'success' => true,
        'data' => $intakes
    ]);
}

    private function filteredPlansQuery(Request $request)
    {
        $query = PaymentPlan::query()
            ->with(['course', 'intake'])
            ->when($request->filled('location'), fn ($q) => $q->where('location', $request->location))
            ->when($request->filled('course_id'), fn ($q) => $q->where('course_id', $request->course_id))
            ->when($request->filled('intake_id'), fn ($q) => $q->where('intake_id', $request->intake_id));

        return match ($request->input('sort', 'newest')) {
            'oldest' => $query->orderBy('id'),
            'location_asc' => $query->orderBy('location')->orderByDesc('id'),
            'location_desc' => $query->orderByDesc('location')->orderByDesc('id'),
            default => $query->orderByDesc('id'),
        };
    }

    private function resolvedPerPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }

    private function campusLabel(?string $location): string
    {
        $known = ['Welisara', 'Moratuwa', 'Peradeniya'];
        if (in_array($location, $known, true)) {
            return 'Nebula Institute of Technology - ' . $location;
        }

        return $location ?: '—';
    }

    private function mapPlansForExport($plans): array
    {
        return $plans->map(function ($plan) {
            return [
                'id' => $plan->id,
                'location' => $this->campusLabel($plan->location),
                'course' => optional($plan->course)->course_name ?? '—',
                'intake' => optional($plan->intake)->batch ?? '—',
                'registration_fee' => number_format((float) $plan->registration_fee, 2, '.', ''),
                'local_fee' => number_format((float) $plan->local_fee, 2, '.', ''),
                'international_fee' => number_format((float) $plan->international_fee, 2, '.', ''),
                'currency' => $plan->international_currency ?: '',
                'discount' => $plan->apply_discount ? ((float) $plan->discount) . '%' : '—',
                'installments' => $plan->installment_plan ? 'Yes' : 'No',
                'created_at' => optional($plan->created_at)?->format('Y-m-d H:i') ?? '',
            ];
        })->all();
    }

}
