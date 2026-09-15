<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Intake;
use App\Models\Student;
use App\Models\PaymentDetail;
use App\Models\PaymentPlan;
use App\Models\CourseRegistration;
use App\Models\CustomPayment;
use App\Models\StudentPaymentPlan;
use App\Models\SltLoanReceivableRecord;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentController extends Controller
{
    /**
     * Show the payment management view.
     */
    public function index()
    {
        $courses = Course::orderBy('course_name')->get();
        $intakes = Intake::join('courses', 'intakes.course_name', '=', 'courses.course_name')
            ->select('intakes.*', 'courses.course_name as course_display_name')
            ->get()
            ->map(function ($intake) {
                $intake->intake_display_name = $intake->course_display_name . ' - ' . $intake->intake_no;
                return $intake;
            });

        return view('payments.payment', compact('courses', 'intakes'));
    }

    /**
     * Get available discounts.
     */
    public function getDiscounts(Request $request)
    {
        try {
            $query = \App\Models\Discount::where('status', 'active');
            
            // Filter by discount category if provided
            if ($request->has('category') && $request->category) {
                $query->where('discount_category', $request->category);
            }
            
            $discounts = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'discounts' => $discounts
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get payment details for a student and payment type.
     */
   public function getPaymentDetails(Request $request)
{
    try {
        $request->validate([
            'student_id'   => 'required|string',
            'course_id'    => 'required|integer|exists:courses,course_id',
            'payment_type' => 'required|in:course_fee,franchise_fee,registration_fee,other',
        ]);

        $student = \App\Models\Student::where('student_id', $request->student_id)
            ->orWhere('id_value', $request->student_id)
            ->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        $registration = \App\Models\CourseRegistration::where('student_id', $student->student_id)
            ->where('course_id', $request->course_id)
            ->with(['course','intake'])
            ->first();

        if (!$registration) {
            return response()->json(['success' => false, 'message' => 'Student is not registered for the selected course.'], 404);
        }

        $rows = [];

        switch ($request->payment_type) {
            case 'course_fee':
                $studentPlans = \App\Models\StudentPaymentPlan::where('student_id', $student->student_id)
                    ->where('course_id', $request->course_id)
                    ->orderByDesc('created_at')
                    ->get();

                if ($studentPlans->isEmpty()) {
                    return response()->json([
                        'success' => true,
                        'payment_details' => [],
                        'message' => 'No student payment plans found. Create a plan first.',
                    ]);
                }

                foreach ($studentPlans as $plan) {
                    $installments = \App\Models\PaymentInstallment::where('payment_plan_id', $plan->id)
                        ->orderBy('installment_number')
                        ->get();

                    foreach ($installments as $ins) {
                        if (!is_null($ins->international_amount) && (float)$ins->international_amount > 0) {
                            continue;
                        }

                        $final = $ins->final_amount ?? $ins->amount;

                        $paymentDetail = \App\Models\PaymentDetail::where('student_id', $student->student_id)
                            ->where('course_registration_id', $registration->id)
                            ->where('installment_number', $ins->installment_number)
                            ->where('installment_type', 'course_fee')
                            ->orderByDesc('id')
                            ->first();

                        $status = $paymentDetail ? ($paymentDetail->status ?? 'pending') : ($ins->status ?? 'pending');
                        $paidDate = $paymentDetail ? optional($paymentDetail->payment_date)->toDateString() : optional($ins->paid_date)->toDateString();
                        $receiptNo = $paymentDetail ? $paymentDetail->transaction_id : null;

                        $rows[] = [
                            'plan_id'             => $plan->id,
                            'plan_status'         => $plan->status ?? 'active',
                            'installment_number'  => $ins->installment_number,
                            'due_date'            => optional($ins->due_date)->toDateString(),
                            'amount'              => (float) $final,
                            'base_amount'         => (float) $ins->amount,
                            'status'              => $status,
                            'paid_date'           => $paidDate,
                            'receipt_no'          => $receiptNo,
                            'currency'            => 'LKR',
                            'approved_late_fee'   => (float) ($ins->approved_late_fee ?? 0),
                            'calculated_late_fee' => (float) ($ins->calculated_late_fee ?? 0),
                        ];
                    }
                }

                break;

            case 'franchise_fee':
                $franchisePlan = \App\Models\PaymentPlan::where('course_id', $request->course_id)
                    ->where('intake_id', $registration->intake_id)
                    ->first();

                $franchiseSsclTax = (float) ($franchisePlan->sscl_tax ?? optional($registration->intake)->sscl_tax ?? 0);
                $franchiseBankCharges = (float) ($franchisePlan->bank_charges ?? optional($registration->intake)->bank_charges ?? 0);

                $franchiseInstallments = \App\Models\PaymentInstallment::whereHas('paymentPlan', function($q) use ($student, $request) {
                        $q->where('student_id', $student->student_id)
                          ->where('course_id', $request->course_id);
                    })
                    ->whereNotNull('international_amount')
                    ->where('international_amount', '>', 0)
                    ->orderBy('installment_number')
                    ->get();

                if ($franchiseInstallments->isNotEmpty()) {
                    foreach ($franchiseInstallments as $ins) {
                        $paymentDetail = \App\Models\PaymentDetail::where('student_id', $student->student_id)
                            ->where('course_registration_id', $registration->id)
                            ->where('installment_number', $ins->installment_number)
                            ->where('installment_type', 'franchise_fee')
                            ->orderByDesc('id')
                            ->first();

                        $status = $paymentDetail ? ($paymentDetail->status ?? 'pending') : ($ins->status ?? 'pending');
                        $paidDate = $paymentDetail ? optional($paymentDetail->payment_date)->toDateString() : optional($ins->paid_date)->toDateString();
                        $receiptNo = $paymentDetail ? $paymentDetail->transaction_id : null;

                        $rows[] = [
                            'installment_number' => $ins->installment_number,
                            'due_date'           => optional($ins->due_date)->toDateString(),
                            'amount'             => $paymentDetail && $paymentDetail->foreign_currency_amount !== null
                                ? (float) $paymentDetail->foreign_currency_amount
                                : (float) $ins->international_amount,
                            'base_amount'        => (float) $ins->international_amount,
                            'status'             => $status,
                            'paid_date'          => $paidDate,
                            'receipt_no'         => $receiptNo,
                            'currency'           => $paymentDetail->foreign_currency_code ?? $ins->international_currency ?: 'USD',
                            'conversion_rate'    => $paymentDetail ? (float) $paymentDetail->conversion_rate : null,
                            'lkr_amount'         => $paymentDetail ? (float) $paymentDetail->amount : null,
                            'apply_tax'          => false,
                            'sscl_tax'           => $paymentDetail ? (float) $paymentDetail->sscl_tax_amount : $franchiseSsclTax,
                            'bank_charges'       => $paymentDetail ? (float) $paymentDetail->bank_charges : $franchiseBankCharges,
                        ];
                    }

                    break;
                }

                $plan = $franchisePlan;
                if (!$plan) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No payment plan found for this course/intake.'
                    ], 404);
                }

                $instData = $plan->installments;
                if (is_string($instData)) {
                    $instData = json_decode($instData, true);
                }

                if (is_array($instData)) {
                    foreach ($instData as $item) {
                        $fx = (float) ($item['international_amount'] ?? 0);
                        if ($fx <= 0) {
                            continue;
                        }

                        $paymentDetail = \App\Models\PaymentDetail::where('student_id', $student->student_id)
                            ->where('course_registration_id', $registration->id)
                            ->where('installment_number', $item['installment_number'] ?? null)
                            ->where('installment_type', 'franchise_fee')
                            ->orderByDesc('id')
                            ->first();

                        $status = $paymentDetail ? ($paymentDetail->status ?? 'pending') : 'pending';
                        $paidDate = $paymentDetail ? optional($paymentDetail->payment_date)->toDateString() : null;
                        $receiptNo = $paymentDetail ? $paymentDetail->transaction_id : null;

                        $rows[] = [
                            'installment_number' => $item['installment_number'] ?? null,
                            'due_date'           => $item['due_date'] ?? null,
                            'amount'             => $paymentDetail && $paymentDetail->foreign_currency_amount !== null
                                ? (float) $paymentDetail->foreign_currency_amount
                                : $fx,
                            'base_amount'        => $fx,
                            'status'             => $status,
                            'paid_date'          => $paidDate,
                            'receipt_no'         => $receiptNo,
                            'currency'           => $paymentDetail->foreign_currency_code ?? $plan->international_currency ?: 'USD',
                            'conversion_rate'    => $paymentDetail ? (float) $paymentDetail->conversion_rate : null,
                            'lkr_amount'         => $paymentDetail ? (float) $paymentDetail->amount : null,
                            'apply_tax'          => (bool)($item['apply_tax'] ?? false),
                            'sscl_tax'           => $paymentDetail ? (float) $paymentDetail->sscl_tax_amount : (float)($plan->sscl_tax ?? 0),
                            'bank_charges'       => $paymentDetail ? (float) $paymentDetail->bank_charges : (float)($plan->bank_charges ?? 0),
                        ];
                    }
                }

                break;

            case 'registration_fee':
                $plan = \App\Models\PaymentPlan::where('course_id', $request->course_id)
                    ->where('intake_id', $registration->intake_id)
                    ->first();

                if (!$plan) {
                    return response()->json(['success' => false, 'message' => 'No payment plan found for this course/intake.'], 404);
                }

                $registrationFee = (float) $plan->registration_fee;
                $discountedAmount = $registrationFee;
                $discountText = '';

                $studentPaymentPlan = \App\Models\StudentPaymentPlan::where('student_id', $student->student_id)
                    ->where('course_id', $request->course_id)
                    ->first();

                if ($studentPaymentPlan) {
                    $registrationFeeDiscount = \App\Models\PaymentPlanDiscount::where('payment_plan_id', $studentPaymentPlan->id)
                        ->whereHas('discount', function($query) {
                            $query->where('discount_category', 'registration_fee');
                        })
                        ->with('discount')
                        ->first();

                    if ($registrationFeeDiscount) {
                        $discount = $registrationFeeDiscount->discount;
                        if ($discount->type === 'percentage') {
                            $discountAmount = ($registrationFee * $discount->value) / 100;
                            $discountedAmount = $registrationFee - $discountAmount;
                            $discountText = 'Discount (' . $discount->value . '% on registration fee)';
                        } elseif ($discount->type === 'amount') {
                            $discountAmount = min($discount->value, $registrationFee);
                            $discountedAmount = $registrationFee - $discountAmount;
                            $discountText = 'Discount (LKR ' . $discountAmount . ' on registration fee)';
                        }
                    }
                }

                $paymentDetail = \App\Models\PaymentDetail::where('student_id', $student->student_id)
                    ->where('course_registration_id', $registration->id)
                    ->where('installment_number', 1)
                    ->first();

                $status = $paymentDetail ? ($paymentDetail->status ?? 'pending') : 'pending';
                $paidDate = $paymentDetail ? optional($paymentDetail->payment_date)->toDateString() : null;
                $receiptNo = $paymentDetail ? $paymentDetail->transaction_id : null;

                $rows[] = [
                    'installment_number' => 1,
                    'due_date'           => now()->toDateString(),
                    'amount'             => $discountedAmount,
                    'base_amount'        => $registrationFee,
                    'discount'           => $discountText,
                    'status'             => $status,
                    'paid_date'          => $paidDate,
                    'receipt_no'         => $receiptNo,
                    'currency'           => 'LKR',
                ];

                break;

            case 'other':
                $customPayments = CustomPayment::where('student_id', $student->student_id)
                    ->where('course_id', $request->course_id)
                    ->orderBy('due_date')
                    ->get();

                foreach ($customPayments as $index => $customPayment) {
                    $discountText = null;
                    if ($customPayment->discount_amount > 0) {
                        $discountText = $customPayment->discount_reason
                            ? $customPayment->discount_reason
                            : 'Discount (LKR ' . $customPayment->discount_amount . ')';
                    }

                    $rows[] = [
                        'installment_number' => $index + 1,
                        'due_date'           => $customPayment->due_date->toDateString(),
                        'amount'             => (float) $customPayment->final_amount,
                        'base_amount'        => (float) $customPayment->amount,
                        'discount'           => $discountText,
                        'status'             => $customPayment->status,
                        'paid_date'          => optional($customPayment->paid_date)->toDateString(),
                        'receipt_no'         => $customPayment->receipt_no,
                        'currency'           => 'LKR',
                        'payment_name'       => $customPayment->payment_name,
                        'late_payment_fee'   => (float) $customPayment->late_payment_fee,
                        'discount_amount'    => (float) $customPayment->discount_amount,
                        'notes'              => $customPayment->notes,
                    ];
                }

                break;
        }

        $nextPayableInstallment = null;
        if (in_array($request->payment_type, ['course_fee', 'franchise_fee'], true) && !empty($rows)) {
            $rows = collect($rows)
                ->map(function ($row) {
                    $status = strtolower((string) ($row['status'] ?? 'pending'));
                    $row['is_payable'] = $status !== 'paid';
                    $row['blocked_reason'] = null;
                    return $row;
                })
                ->values()
                ->all();
        }

        return response()->json([
            'success'                  => true,
            'payment_details'          => $rows,
            'next_payable_installment' => $nextPayableInstallment,
        ]);

    } catch (\Throwable $e) {
        \Log::error('getPaymentDetails error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()], 500);
    }
}

    /**
     * Get payment plan installments for a student and course.
     */
    public function getPaymentPlanInstallments(Request $request)
    {
        \Log::info('getPaymentPlanInstallments called', [
            'student_nic' => $request->student_nic,
            'course_id' => $request->course_id
        ]);
        
        try {
            $request->validate([
                'student_nic' => 'required|string',
                'course_id' => 'required|integer|exists:courses,course_id',
            ]);

            // Find student by NIC
            $student = Student::where('id_value', $request->student_nic)->first();
            
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found with the provided NIC.'], Response::HTTP_NOT_FOUND);
            }

            // Get course registration to find the intake
            $registration = CourseRegistration::where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->with(['intake'])
                ->first();

            if (!$registration) {
                return response()->json(['success' => false, 'message' => 'Student is not registered for this course.'], Response::HTTP_NOT_FOUND);
            }

            // Get student payment plan to check for registration fee discount
            $studentPaymentPlan = \App\Models\StudentPaymentPlan::where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->with(['installments', 'discounts.discount'])
                ->first();

            $registrationFeeDiscountInfo = null;
            if ($studentPaymentPlan) {
                $registrationFeeDiscount = \App\Models\PaymentPlanDiscount::where('payment_plan_id', $studentPaymentPlan->id)
                    ->whereHas('discount', function($query) {
                        $query->where('discount_category', 'registration_fee');
                    })
                    ->with('discount')
                    ->first();
                
                if ($registrationFeeDiscount) {
                    $discount = $registrationFeeDiscount->discount;
                    $registrationFee = $registration->registration_fee ?? 0;
                    
                    // Calculate the actual discount amount
                    $discountAmount = 0.0;
                    if ($discount->type === 'percentage') {
                        $discountAmount = ($registrationFee * $discount->value) / 100;
                    } elseif ($discount->type === 'amount') {
                        $discountAmount = $discount->value;
                    }
                    
                    $registrationFeeDiscountInfo = [
                        'discount_amount' => $discountAmount,
                        'registration_fee' => $registrationFee,
                        'excess_discount' => max(0, $discountAmount - $registrationFee),
                        'remaining_discount' => $studentPaymentPlan->remaining_registration_discount ?? 0,
                        'discount_type' => $discount->type,
                        'discount_value' => $discount->value,
                        'discount_name' => $discount->name ?? 'Registration Fee Discount'
                    ];
                }
            }

            // If we have a student payment plan with installments, use those directly
            if ($studentPaymentPlan && $studentPaymentPlan->installments->count() > 0) {
                $installments = [];
                $localFeeTotal = 0;
                
                foreach ($studentPaymentPlan->installments->sortBy('installment_number') as $installment) {
                    $baseAmount = $installment->base_amount ?? $installment->amount ?? 0;
                    $finalAmount = $installment->final_amount ?? $installment->amount ?? 0;
                    $localFeeTotal += $baseAmount;
                    
                    // Build discount text
                    $discountText = '';
                    $totalDiscount = 0;
                    
                    // Add regular discount (from discount_amount field)
                    if ($installment->discount_amount > 0) {
                        $totalDiscount += $installment->discount_amount;
                        $discountText = 'LKR ' . number_format($installment->discount_amount, 2);
                    }
                    
                    // Add registration fee discount excess
                    $registrationFeeDiscountApplied = $installment->registration_fee_discount_applied ?? 0;
                    $registrationFeeDiscountNote = $installment->registration_fee_discount_note ?? '';
                    
                    if ($registrationFeeDiscountApplied > 0) {
                        $totalDiscount += $registrationFeeDiscountApplied;
                        if ($discountText) {
                            $discountText .= ' + LKR ' . number_format($registrationFeeDiscountApplied, 2) . ' (Reg. Fee Excess)';
                        } else {
                            $discountText = 'LKR ' . number_format($registrationFeeDiscountApplied, 2) . ' (Reg. Fee Excess)';
                        }
                    }
                    
                    if (!$discountText) {
                        $discountText = '-';
                    }
                    
                    $installments[] = [
                        'installment_number' => $installment->installment_number,
                        'due_date' => $installment->due_date ? $installment->due_date->format('Y-m-d') : null,
                        'amount' => $baseAmount,
                        'discount' => $discountText,
                        'registration_fee_discount_applied' => $registrationFeeDiscountApplied,
                        'registration_fee_discount_note' => $registrationFeeDiscountNote,
                        'slt_loan' => '', // Will be handled by frontend
                        'final_amount' => $finalAmount,
                        'status' => $installment->status ?? 'pending',
                        'is_last_installment' => false, // Will be determined by frontend
                        'local_fee_total' => $localFeeTotal
                    ];
                }
            } else {
                // Fallback to payment plan template if no student-specific plan exists
                $paymentPlan = \App\Models\PaymentPlan::where('course_id', $request->course_id)
                    ->where('intake_id', $registration->intake_id)
                    ->first();

                // Initialize empty installments array
                $installments = [];
                $localFeeTotal = 0;

                if ($paymentPlan) {
                    // Use the original logic for template-based installments
                    $installmentsData = $paymentPlan->installments;
                    if (is_string($installmentsData)) {
                        $installmentsData = json_decode($installmentsData, true);
                    }
                    
                    if ($installmentsData && is_array($installmentsData)) {
                        foreach ($installmentsData as $index => $installment) {
                            $localAmount = $installment['local_amount'] ?? 0;
                            
                            if ($localAmount > 0) {
                                $localFeeTotal += $localAmount;
                                
                                $installments[] = [
                                    'installment_number' => $installment['installment_number'] ?? ($index + 1),
                                    'due_date' => $installment['due_date'] ?? null,
                                    'amount' => $localAmount,
                                    'discount' => '-',
                                    'registration_fee_discount_applied' => 0,
                                    'registration_fee_discount_note' => '',
                                    'slt_loan' => '',
                                    'final_amount' => $localAmount,
                                    'status' => 'pending',
                                    'is_last_installment' => false,
                                    'local_fee_total' => $localFeeTotal
                                ];
                            }
                        }
                    }
                }
                // If no payment plan exists, installments will be empty array (full payment option)
            }

            \Log::info('Final installments array:', [
                'installments_count' => count($installments),
                'installments' => $installments,
                'student_payment_plan_exists' => $studentPaymentPlan ? 'yes' : 'no',
                'student_payment_plan_id' => $studentPaymentPlan ? $studentPaymentPlan->id : null,
                'registration_fee_discount_info' => $registrationFeeDiscountInfo
            ]);

            return response()->json([
                'success' => true,
                'installments' => $installments,
                'registration_fee_discount_info' => $registrationFeeDiscountInfo,
                'payment_plan' => [
                    'id' => $studentPaymentPlan ? $studentPaymentPlan->id : null,
                    'location' => $studentPaymentPlan ? 'Student Specific' : 'Template',
                    'local_fee' => $localFeeTotal,
                    'registration_fee' => $registration->registration_fee ?? 0,
                    'total_amount' => $localFeeTotal + ($registration->registration_fee ?? 0),
                    'apply_discount' => false,
                    'discount' => 0,
                    'local_fee_total' => $localFeeTotal,
                    'remaining_registration_discount' => $studentPaymentPlan ? $studentPaymentPlan->remaining_registration_discount : 0
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get courses for a specific student based on NIC.
     */
    public function getStudentCourses(Request $request)
    {
        try {
            $studentNic = trim((string) $request->input('student_nic', ''));

            if ($studentNic === '') {
                return response()->json([
                    'success' => false,
                    'message' => 'Student NIC is required.',
                    'courses' => []
                ]);
            }

            $student = Student::query()
                ->where(function ($query) use ($studentNic) {
                    $query->where('id_value', $studentNic)
                        ->orWhere('student_id', $studentNic);
                })
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found with the provided NIC or Student ID.',
                    'courses' => []
                ]);
            }

            // Get courses that the student is registered for and approved by manager or DGM
            $courses = CourseRegistration::where('student_id', $student->student_id)
                ->whereIn('approval_status', ['Approved by manager', 'DGM'])
                ->with('course')
                ->get()
                ->map(function ($registration) {
                    if (!$registration->course) {
                        return null;
                    }

                    return [
                        'course_id' => $registration->course->course_id,
                        'course_name' => $registration->course->course_name,
                        'registration_date' => $this->formatSriLankaDate($registration->registration_date),
                        'status' => $registration->status,
                        'approval_status' => $registration->approval_status,
                    ];
                })
                ->filter()
                ->values();

            return response()->json([
                'success' => true,
                'courses' => $courses
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get payment plans for students.
     */
    public function getPaymentPlans(Request $request)
    {
        try {
            $request->validate([
                'student_nic' => 'required|string',
                'course_id' => 'required|integer|exists:courses,course_id',
            ]);

            // Find student by NIC
            $student = Student::where('id_value', $request->student_nic)->first();
            
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found with the provided NIC.'], Response::HTTP_NOT_FOUND);
            }

            // Get course registration for this student and course
            $registration = CourseRegistration::where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->with(['student', 'course', 'intake'])
                ->first();

            if (!$registration) {
                return response()->json(['success' => false, 'message' => 'Student is not registered for this course.'], Response::HTTP_NOT_FOUND);
            }

            // Use intake-based fees if available, otherwise fall back to course fees
            $courseFee = $registration->intake->course_fee ?? $registration->course->course_fee ?? 0;
            $franchiseFee = $registration->intake->franchise_payment ?? $registration->course->franchise_payment ?? 0;
            $franchiseCurrency = $registration->intake->franchise_payment_currency ?? 'USD';
            $registrationFee = $registration->intake->registration_fee ?? $registration->registration_fee ?? 0;
            $totalAmount = $courseFee + $franchiseFee + $registrationFee;
            
            $studentData = [
                'student_id' => $registration->student->student_id,
                'student_name' => $registration->student->full_name,
                'student_nic' => $registration->student->id_value,
                'course_id' => $request->course_id,
                'course_name' => $registration->course->course_name,
                'intake_name' => $registration->intake->batch ?? 'N/A',
                'course_fee' => $courseFee,
                'international_fee' => $franchiseFee,
                'international_currency' => $franchiseCurrency,
                'registration_fee' => $registrationFee,
                'total_amount' => $totalAmount,
                'registration_date' => $registration->registration_date,
                'status' => $registration->status,
            ];

            return response()->json([
                'success' => true,
                'student' => $studentData
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
 
public function createPaymentPlan(Request $request)
{
    try {
        // Base validation rules
        $rules = [
            'student_id'        => 'required|exists:students,student_id',
            'course_id'         => 'required|exists:courses,course_id',
            'payment_plan_type' => 'required|in:installments,full',

            'discounts'                     => 'nullable|array',
            'discounts.*.discount_id'       => 'required|integer|exists:discounts,id',
            'discounts.*.discount_type'     => 'required|in:percentage,amount',
            'discounts.*.discount_value'    => 'required|numeric|min:0',

            'registration_fee_discount'     => 'nullable|array',
            'registration_fee_discount.discount_id' => 'required_with:registration_fee_discount|integer|exists:discounts,id',
            'registration_fee_discount.discount_type' => 'required_with:registration_fee_discount|in:percentage,amount',
            'registration_fee_discount.discount_value' => 'required_with:registration_fee_discount|numeric|min:0',

            'slt_loan_applied'  => 'nullable|in:yes,no',
            'slt_loan_amount'   => 'nullable|numeric|min:0',
            'slt_loan_start_installment' => 'nullable|integer|min:1',
            'slt_loan_years' => 'nullable|integer|min:1|max:50',
        ];

        // Installments are only required for 'installments' payment plan type
        if ($request->payment_plan_type === 'installments') {
            $rules['installments'] = 'required|array|min:1';
            $rules['installments.*.installment_number'] = 'required|integer|min:1';
            $rules['installments.*.due_date'] = 'required|date';
            $rules['installments.*.amount'] = 'required|numeric|min:0';
            $rules['installments.*.status'] = 'required|in:pending,paid,overdue';
        } else {
            // For full payment, installments are optional
            $rules['installments'] = 'nullable|array';
            $rules['installments.*.installment_number'] = 'nullable|integer|min:1';
            $rules['installments.*.due_date'] = 'nullable|date';
            $rules['installments.*.amount'] = 'nullable|numeric|min:0';
            $rules['installments.*.status'] = 'nullable|in:pending,paid,overdue';
        }

        $request->validate($rules);

        // fetch course registration (eligibility checks)
        $registration = \App\Models\CourseRegistration::where('student_id', $request->student_id)
            ->where('course_id', $request->course_id)
            ->firstOrFail();

        if ($registration->approval_status !== 'Approved by manager' || $registration->status !== 'Registered') {
            return response()->json(['success' => false, 'message' => 'Student must be approved and registered.'], 400);
        }

        if ($registration->remarks !== 'Registered via eligibility page') {
            return response()->json(['success' => false, 'message' => 'Student must be registered through eligibility page.'], 400);
        }

        // prevent duplicate plan
        if (\App\Models\StudentPaymentPlan::where('student_id', $request->student_id)
                ->where('course_id', $request->course_id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Payment plan already exists for this student.'], 400);
        }

        return \DB::transaction(function () use ($request, $registration) {
            $isFullPaymentPlan = $request->payment_plan_type === 'full';

            // ===== 1) Get fee definition =====
            // Prefer payment_plans; if missing (common for some certificate intakes),
            // fall back to intake fee fields so plan creation still works.
            $paymentPlan = \App\Models\PaymentPlan::query()
                ->where('course_id', $request->course_id)
                ->when($registration->intake_id, function ($q) use ($registration) {
                    $q->where('intake_id', $registration->intake_id);
                })
                ->orderByDesc('id')
                ->first();

            // Secondary fallback: intake-only payment plan rows
            if (!$paymentPlan && $registration->intake_id) {
                $paymentPlan = \App\Models\PaymentPlan::where('intake_id', $registration->intake_id)
                    ->orderByDesc('id')
                    ->first();
            }

            if ($paymentPlan) {
                $registrationFee = (float) $paymentPlan->registration_fee;
                $localFee        = (float) $paymentPlan->local_fee;
            } else {
                $intake = \App\Models\Intake::where('intake_id', $registration->intake_id)->first();
                if (!$intake) {
                    throw new \Exception('Payment plan definition not found for this course/intake.');
                }

                $registrationFee = (float) ($intake->registration_fee ?? 0);
                $localFee        = (float) ($intake->course_fee ?? 0);
            }

            $totalFeeForDiscount = $localFee + $registrationFee;
            if ($totalFeeForDiscount <= 0 && $request->filled('total_amount')) {
                $totalFeeForDiscount = (float) $request->total_amount;
            }

            // collect rows - for full payment, create a single installment
            $rows = collect($request->installments ?? [])->sortBy('installment_number')->values()->all();
            
            // If full payment with no installments, create a single installment for the total amount
            if ($request->payment_plan_type === 'full' && empty($rows)) {
                $rows = [[
                    'installment_number' => 1,
                    'due_date' => now()->addDays(7)->format('Y-m-d'), // Default: 7 days from now
                    'amount' => $totalFeeForDiscount,
                    'status' => 'pending'
                ]];
            }
            
            $lastIdx = count($rows) - 1;

            // ===== 2) Normal discounts =====
            // Apply course fee discounts over the combined course fee + registration fee base.
            $pct = 0.0;
            $fixed = 0.0;

            foreach (($isFullPaymentPlan ? ($request->discounts ?? []) : []) as $d) {
                $type = strtolower($d['discount_type'] ?? '');
                $val  = (float)($d['discount_value'] ?? 0);
                if ($type === 'percentage') $pct  += $val;
                if ($type === 'amount')     $fixed += $val;
            }

            $pctAmount    = ($pct > 0) ? ($totalFeeForDiscount * $pct / 100) : 0.0;
            $totalDiscount = $pctAmount + $fixed;

            $discountedBases = [];
            $discountApplied = array_fill(0, max(0, count($rows)), 0.0);
            $remainingDiscount = $totalDiscount;

            foreach ($rows as $i => $r) {
                $discountedBases[$i] = round((float) $r['amount'], 2);
            }

            for ($i = $lastIdx; $i >= 0 && $remainingDiscount > 0; $i--) {
                $available = $discountedBases[$i];
                $deduct = min($available, $remainingDiscount);
                $discountedBases[$i] = round($available - $deduct, 2);
                $discountApplied[$i] = round($deduct, 2);
                $remainingDiscount -= $deduct;
            }

            // ===== 3) Registration fee discount (wipe reg fee first, excess → first installment) =====
            $registrationFeeDiscountApplied = [];
            if ($isFullPaymentPlan && $request->filled('registration_fee_discount')) {
                $regDiscount = $request->registration_fee_discount;

                $discountAmount = $regDiscount['discount_type'] === 'percentage'
                    ? $registrationFee * ($regDiscount['discount_value'] / 100)
                    : $regDiscount['discount_value'];

                if ($discountAmount > $registrationFee) {
                    $excess = $discountAmount - $registrationFee;
                    $deduct = min($excess, $discountedBases[0]);
                    $discountedBases[0] -= $deduct;
                    $registrationFeeDiscountApplied[0] = $deduct;
                }
            }

            // ===== 4) SLT loan allocation =====
            $sumAfterDiscounts = array_sum($discountedBases);
            $S = ($request->slt_loan_applied === 'yes') ? (float)($request->slt_loan_amount ?? 0) : 0.0;
            $S = max(0, min($S, $sumAfterDiscounts));
            $loanStartInstallment = $S > 0 ? (int) ($request->slt_loan_start_installment ?? 1) : null;
            $loanYears = $S > 0 ? (int) ($request->slt_loan_years ?? 0) : null;
            $loanAllocations = $this->allocateSltLoanByStartInstallment($rows, $discountedBases, $S, $loanStartInstallment);
            $S = round(array_sum($loanAllocations), 2);
            $T = $sumAfterDiscounts - $S;

            // ===== 5) Compute installments =====
            $computed = [];

            foreach ($rows as $i => $r) {
                $base = round((float)$r['amount'], 2);
                $Ai   = round($discountedBases[$i], 2);
                $loanPart = round($loanAllocations[$i] ?? 0, 2);
                $Fi = round(max(0, $Ai - $loanPart), 2);

                $computed[] = [
                    'installment_number'               => $r['installment_number'],
                    'due_date'                         => $r['due_date'],
                    'status'                           => $r['status'],
                    'base'                             => $base,
                    'discount_amount'                  => $discountApplied[$i] ?? 0.0,
                    'discount_note'                    => ($discountApplied[$i] ?? 0) > 0 ? "Normal Discounts Applied" : null,
                    'discounted_base'                  => $Ai,
                    'loan_amount'                      => $loanPart,
                    'final'                            => $Fi,
                    'registration_fee_discount_applied'=> $registrationFeeDiscountApplied[$i] ?? 0,
                    'registration_fee_discount_note'   => ($registrationFeeDiscountApplied[$i] ?? 0) > 0 ? 'Reg. Fee Excess' : null,
                ];
            }

            // ===== 6) Save plan =====
            $plan = \App\Models\StudentPaymentPlan::create([
                'student_id'        => $request->student_id,
                'course_id'         => $request->course_id,
                'payment_plan_type' => $request->payment_plan_type,
                'slt_loan_applied'  => $request->slt_loan_applied,
                'slt_loan_amount'   => $S,
                'slt_loan_start_installment' => $loanStartInstallment,
                'slt_loan_years'    => $loanYears,
                'total_amount'      => $totalFeeForDiscount, // local + reg
                'final_amount'      => $T,
                'status'            => 'active',
            ]);

            // save discounts
            foreach (($request->discounts ?? []) as $d) {
                if ($isFullPaymentPlan) {
                    \App\Models\PaymentPlanDiscount::create([
                        'payment_plan_id' => $plan->id,
                        'discount_id'     => $d['discount_id'],
                        'discount_type'   => $d['discount_type'],
                        'discount_value'  => $d['discount_value'],
                    ]);
                }
            }

            if ($isFullPaymentPlan && $request->filled('registration_fee_discount')) {
                $regDiscount = $request->registration_fee_discount;
                \App\Models\PaymentPlanDiscount::create([
                    'payment_plan_id' => $plan->id,
                    'discount_id'     => $regDiscount['discount_id'],
                    'discount_type'   => $regDiscount['discount_type'],
                    'discount_value'  => $regDiscount['discount_value'],
                ]);
            }

            // save installments
            foreach ($computed as $c) {
                \App\Models\PaymentInstallment::create([
                    'payment_plan_id'                   => $plan->id,
                    'installment_number'                => $c['installment_number'],
                    'due_date'                          => $c['due_date'],
                    'amount'                            => $c['final'],
                    'base_amount'                       => $c['base'],
                    'discount_amount'                   => $c['discount_amount'],
                    'discount_note'                     => $c['discount_note'],
                    'slt_loan_amount'                   => $c['loan_amount'],
                    'registration_fee_discount_applied' => $c['registration_fee_discount_applied'],
                    'registration_fee_discount_note'    => $c['registration_fee_discount_note'],
                    'final_amount'                      => $c['final'],
                    'status'                            => $c['status'],
                ]);
            }

            $registration->update(['payment_plan_id' => $plan->id]);

            return response()->json([
                'success'         => true,
                'message'         => 'Payment plan created successfully.',
                'payment_plan_id' => $plan->id,
                'total_amount'    => $totalFeeForDiscount,
                'final_amount'    => $T,
                'installments'    => $computed
            ]);
        });

    } catch (\Illuminate\Validation\ValidationException $ve) {
        $msg = collect($ve->errors())->flatten()->first() ?? 'Validation error';
        return response()->json(['success' => false, 'message' => $msg], 422);
    } catch (\Exception $e) {
        \Log::error('createPaymentPlan error', ['e' => $e]);
        return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()], 500);
    }
}

    /**
     * Save payment plans.
     */
    public function savePaymentPlans(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|exists:students,student_id',
                'course_id' => 'required|exists:courses,course_id',
                'payment_plan' => 'required|string',
            ]);

            // Update course registration with payment plan
            $registration = CourseRegistration::where('student_id', $request->student_id)
                ->where('course_id', $request->course_id)
                ->first();

            if (!$registration) {
                return response()->json(['success' => false, 'message' => 'Student is not registered for this course.'], Response::HTTP_NOT_FOUND);
            }

            $registration->update(['payment_plan' => $request->payment_plan]);

            return response()->json(['success' => true, 'message' => 'Payment plan saved successfully.'], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    // app/Http/Controllers/PaymentController.php

public function getExistingPaymentPlans(\Illuminate\Http\Request $request)
{
    try {
        $request->validate([
            'student_nic' => 'required|string',
            'course_id'   => 'nullable|integer|exists:courses,course_id',
        ]);

        // find the student by NIC or by student_id
        $student = \App\Models\Student::where('id_value', $request->student_nic)
            ->orWhere('student_id', $request->student_nic)
            ->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        // fetch plans for the student (optionally for a specific course)
        $plans = \App\Models\StudentPaymentPlan::with(['installments', 'discounts'])
            ->where('student_id', $student->student_id)
            ->when($request->course_id, fn($q) => $q->where('course_id', $request->course_id))
            ->orderByDesc('created_at')
            ->get();

        // map to a frontend-friendly shape
        $payload = $plans->map(function ($p) use ($student) {
            $courseName = \App\Models\Course::where('course_id', $p->course_id)->value('course_name') ?? 'N/A';

            $inst = $p->installments->map(function ($i) {
                // normalize fields; support schemas with/without extra columns
                $baseAmount  = (float) ($i->base_amount ?? $i->amount ?? 0);
                $finalAmount = (float) ($i->final_amount ?? $i->amount ?? 0);

                return [
                    'installment_number' => $i->installment_number,
                    'due_date'           => optional($i->due_date)->format('Y-m-d'),
                    'amount'             => $baseAmount,
                    'discount_amount'    => (float) ($i->discount_amount ?? 0),
                    'slt_loan_amount'    => (float) ($i->slt_loan_amount ?? 0),
                    'final_amount'       => $finalAmount,
                    'status'             => $i->status ?? 'pending',
                ];
            })->values();

            return [
                'payment_plan_id'   => $p->id,
                'student_id'        => $student->student_id,
                'student_name'      => $student->full_name,
                'student_nic'       => $student->id_value,
                'course_id'         => $p->course_id,
                'course_name'       => $courseName,
                'payment_plan_type' => $p->payment_plan_type,
                'total_amount'      => (float) $p->total_amount,
                'final_amount'      => (float) $p->final_amount,
                'slt_loan_applied'  => $p->slt_loan_applied,
                'slt_loan_amount'   => (float) $p->slt_loan_amount,
                'slt_loan_start_installment' => $p->slt_loan_start_installment,
                'slt_loan_years'    => $p->slt_loan_years,
                'slt_receivable_effective_date' => optional($p->slt_receivable_effective_date)->format('Y-m-d'),
                'status'            => $p->status,
                'installments'      => $inst,
            ];
        });

        return response()->json(['success' => true, 'plans' => $payload], 200);

    } catch (\Illuminate\Validation\ValidationException $e) {
        return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
    } catch (\Throwable $e) {
        return response()->json(['success' => false, 'message' => 'An error occurred: '.$e->getMessage()], 500);
    }
}
public function deletePaymentPlan($id)
{
    try {
        $plan = \App\Models\StudentPaymentPlan::find($id);

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'Payment plan not found'
            ], 404);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment plan deleted successfully'
        ], 200);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error deleting payment plan: '.$e->getMessage()
        ], 500);
    }
}

private function getInstallmentPaymentState(int $studentId, \App\Models\CourseRegistration $registration, string $paymentType): array
{
    if (!in_array($paymentType, ['course_fee', 'franchise_fee'], true)) {
        return ['rows' => [], 'next_installment_number' => null];
    }

    $planQuery = \App\Models\StudentPaymentPlan::where('student_id', $studentId)
        ->where('course_id', $registration->course_id);

    $plans = (clone $planQuery)
        ->where('status', 'active')
        ->orderByDesc('created_at')
        ->get();

    if ($plans->isEmpty()) {
        $plans = $planQuery->orderByDesc('created_at')->get();
    }

    $rows = collect();

    foreach ($plans as $plan) {
        $installments = \App\Models\PaymentInstallment::where('payment_plan_id', $plan->id)
            ->orderBy('installment_number')
            ->get();

        foreach ($installments as $installment) {
            if (
                $paymentType === 'course_fee'
                && !is_null($installment->international_amount)
                && (float) $installment->international_amount > 0
            ) {
                continue;
            }

            $amount = $paymentType === 'franchise_fee'
                ? (float) ($installment->international_amount ?? 0)
                : (float) ($installment->final_amount ?? $installment->amount ?? 0);

            if ($amount <= 0) {
                continue;
            }

            $paymentDetail = \App\Models\PaymentDetail::where('student_id', $studentId)
                ->where('course_registration_id', $registration->id)
                ->where('installment_number', $installment->installment_number)
                ->where('installment_type', $paymentType)
                ->orderByDesc('id')
                ->first();

            $status = strtolower((string) ($paymentDetail->status ?? $installment->status ?? 'pending'));

            $rows->push([
                'installment_number' => (int) $installment->installment_number,
                'status' => $status,
                'amount' => $amount,
            ]);
        }
    }

    $rows = $rows->sortBy('installment_number')->unique('installment_number')->values();
    $nextInstallmentRow = $rows->first(function ($row) {
        return (float) ($row['amount'] ?? 0) > 0 && ($row['status'] ?? 'pending') !== 'paid';
    });

    return [
        'rows' => $rows->all(),
        'next_installment_number' => $nextInstallmentRow['installment_number'] ?? null,
    ];
}

private function enforceInstallmentPaymentOrder(int $studentId, \App\Models\CourseRegistration $registration, string $paymentType, ?int $requestedInstallmentNumber)
{
    if (!in_array($paymentType, ['course_fee', 'franchise_fee'], true) || !$requestedInstallmentNumber) {
        return null;
    }

    $state = $this->getInstallmentPaymentState($studentId, $registration, $paymentType);
    $currentRow = collect($state['rows'])->firstWhere('installment_number', (int) $requestedInstallmentNumber);

    if (!$currentRow) {
        return null;
    }

    if (($currentRow['status'] ?? 'pending') === 'paid') {
        return response()->json([
            'success' => false,
            'message' => "Installment {$requestedInstallmentNumber} has already been paid."
        ], 422);
    }

    $nextInstallmentNumber = $state['next_installment_number'] ?? null;
    if ($nextInstallmentNumber !== null && (int) $requestedInstallmentNumber !== (int) $nextInstallmentNumber) {
        return response()->json([
            'success' => false,
            'message' => "Please complete installment {$nextInstallmentNumber} before paying installment {$requestedInstallmentNumber}."
        ], 422);
    }

    return null;
}

// Teleshop constants (from Teleshop Payment slip)
private const TS_PAYMENT_TYPE = 'Miscellaneous';
private const TS_COST_CENTRE  = '5212';
private const TS_ACCOUNT_CODE = '481.910';

// Map "program/course" to Teleshop Payment Code
private function teleshopPaymentCode(string $program): string
{
    // You can make this array configurable later
    $map = [
        'CAIT'           => '1010',
        'Foundation'     => '1020',
        'BTEC DT'        => '1030',
        'BTEC EE'        => '1040',
        'UH'             => '1050',
        'English'        => '1060',
        'BTEC Computing' => '1070',
        'Other Courses'  => '1080',
        'Hostel'         => '1090',
    ];

    // try exact, then loose contains
    foreach ($map as $key => $code) {
        if (strcasecmp($program, $key) === 0 || stripos($program, $key) !== false) {
            return $code;
        }
    }
    return '1080'; // default: Other Courses
}

// Make a clean "Reference 1" like: UH-B09-L5-1st Installment  OR  UH-B09-L5-Franchise Payment
private function teleshopRef1(string $programShort, ?string $batch, ?string $level, string $paymentType, ?int $instNo): string
{
    $left  = trim($programShort);
    $mid1  = $batch ? trim($batch) : '';
    $mid2  = $level ? trim($level) : '';
    $right = ($paymentType === 'franchise_fee')
        ? 'Franchise Payment'
        : ($instNo ? $this->ordinal($instNo) . ' Installment' : 'Installment');

    return implode('-', array_filter([$left, $mid1, $mid2])) . '-' . $right;
}

// Reference Number (e.g., BTEC/UH Number). Fall back to student_id if not present.
private function teleshopRefNumber(\App\Models\Student $student, \App\Models\CourseRegistration $reg): string
{
    // If you store UH/BTEC number somewhere, plug it here:
    // return $student->uh_no ?? $student->btec_no ?? $student->student_id;
    return $student->student_id;
}

private function ordinal(int $n): string
{
    $suf = 'th';
    if (!in_array($n % 100, [11,12,13])) {
        $suf = [1=>'st',2=>'nd',3=>'rd'][$n % 10] ?? 'th';
    }
    return $n . $suf;
}
//Late Fee 
private function calculateLateFee($amount, $daysLate)
{
    if ($daysLate <= 0) return 0;

    $dailyRate = (0.05 / 30); // 5% monthly → daily
    $lateFee   = $amount * $dailyRate * $daysLate;

    return round(min($lateFee, $amount * 0.25), 2);
}


/**
 * Generate payment slip for pending payments.
 */
public function generatePaymentSlip(Request $request)
{
    try {
        $request->validate([
            'student_id'         => 'required|string',
            'course_id'          => 'required|integer|exists:courses,course_id',
            'payment_type'       => 'required|string', // 'course_fee' | 'franchise_fee' | 'registration_fee'
            'amount'             => 'required|numeric|min:0',
            'installment_number' => 'nullable|integer',
            'due_date'           => 'nullable|date',
            'conversion_rate'    => 'nullable|numeric|min:0',
            'currency_from'      => 'nullable|string',
            'remarks'            => 'nullable|string',
            // Actual date the payment was made; may differ from the system update date when
            // staff enter payments retroactively. Late fees are calculated against this date.
            'payment_effective_date' => 'nullable|date',
        ]);

        // 🔹 Find Student
        $student = \App\Models\Student::where('student_id', $request->student_id)
            ->orWhere('id_value', $request->student_id)
            ->first();

        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
        }

        // 🔹 Check Registration
        $registration = \App\Models\CourseRegistration::where('student_id', $student->student_id)
            ->where('course_id', $request->course_id)
            ->with(['course', 'intake'])
            ->first();

        if (!$registration) {
            return response()->json(['success' => false, 'message' => 'Student is not registered for this course.'], 404);
        }

        $course      = $registration->course;
        $intake      = $registration->intake;
        $paymentType = $request->payment_type;
        $amount      = (float) $request->amount;

        // 🔹 Franchise currency conversion
        $foreignCurrency = null;
        $foreignAmount = null;
        $conversionRate = 1; // default
        //Theres a bug to fix here with franchise fee

        // 🔹 Franchise currency conversion and additional charges
        $franchiseFee = 0;
        $ssclTaxAmount = 0;
        $bankCharges = 0;
        $remainingAmount = 0;

        if ($paymentType === 'franchise_fee') {
            $conversionRate = (float) ($request->conversion_rate ?? 0);
            $foreignCurrency = $request->currency_from ?? 'USD';
            $foreignAmount = $amount;

            if ($conversionRate > 0) {
                $amount = round($amount * $conversionRate, 2); // convert to LKR
            }

            $franchiseFee = $amount; // base franchise fee in LKR

            // 🔹 Get student payment plan
            $studentPlan = \App\Models\StudentPaymentPlan::where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->where('status', 'active')
                ->first();

            if ($studentPlan) {
    // 🔹 Use the $registration fetched at the top of your function
    $paymentPlan = \App\Models\PaymentPlan::where('id', $studentPlan->payment_plan_id)
        ->where('course_id', $request->course_id)
        ->where('intake_id', $registration->intake_id)
        ->first();

    if ($paymentPlan) {
            // Use payment plan values as defaults
            $ssclPercent = $paymentPlan->sscl_tax ?? 0;   // % from plan
            $bankCharges = $paymentPlan->bank_charges ?? 0; // fixed from plan
            $ssclTaxAmount = round($franchiseFee * ($ssclPercent / 100), 2);
        }
    }

    // ✅ Always prefer manually entered frontend values (if provided)
    if ($request->filled('sscl_tax_amount')) {
        $ssclTaxAmount = (float) $request->sscl_tax_amount;
    }

    if ($request->filled('bank_charges')) {
        $bankCharges = (float) $request->bank_charges;
    }

    // ✅ Finally, recalculate total remaining
    $remainingAmount = round($franchiseFee + $ssclTaxAmount + $bankCharges, 2);

    // ✅ Merge values for later usage / DB storage
    $request->merge([
        'sscl_tax_amount' => $ssclTaxAmount,
        'bank_charges'    => $bankCharges,
        'remaining_amount'=> $remainingAmount,
        'conversion_rate' => $conversionRate > 0 ? $conversionRate : null,
    ]);

        }





        

        // 🔹 Breakdown by payment type
        $courseFee       = $paymentType === 'course_fee'       ? $amount : 0;
        $franchiseFee    = $paymentType === 'franchise_fee'    ? $amount : 0;

        //Theres a bug here with registration fee 
        //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        $registrationFee = 0;

        if ($paymentType === 'registration_fee') {
            $baseRegFee = $intake->registration_fee ?? 0;

            // Existing payment
            $existingRegFee = \App\Models\PaymentDetail::where('student_id', $student->student_id)
                ->where('course_registration_id', $registration->id)
                ->where('status', 'pending')
                ->whereNull('installment_number') // important for registration fee
                ->first();

            if ($existingRegFee) {
                $existingRegFee->refresh();
                $partialPayments = $existingRegFee->partial_payments ?? [];
                $paidSoFar = collect($partialPayments)->sum('amount');
                $remainingAmount = max(($existingRegFee->total_fee - $paidSoFar), 0);

                $slipData = $this->buildSlipArray(
                    $existingRegFee,
                    $student,
                    $course,
                    $intake,
                    0,
                    0,
                    $existingRegFee->amount, // ✅ ensure amount is correct
                    $existingRegFee->late_fee ?? 0,
                    $existingRegFee->approved_late_fee ?? 0,
                    $existingRegFee->total_fee
                );

                $slipData['partial_payments'] = $partialPayments;
                $slipData['remaining_amount'] = $remainingAmount;
                $slipData['id'] = $existingRegFee->id;
                $slipData['can_delete'] = true;
                $slipData['remarks'] = $existingRegFee->remarks ?? '';

                return response()->json([
                    'success'           => true,
                    'message'           => 'Registration fee already exists. Showing current payment data.',
                    'slip_data'         => $slipData,
                    'existing_receipt'  => $existingRegFee->transaction_id,
                    'can_delete'        => true
                ]);
            }

            // 🔹 If no existing registration fee, calculate normally
            $planId = $registration->payment_plan_id
                ?? \App\Models\StudentPaymentPlan::where('student_id', $student->student_id)
                    ->where('course_id', $request->course_id)
                    ->where('status', 'active')
                    ->value('id');

            $pctSum = 0.0;
            $fixedSum = 0.0;
            $names = [];

            if ($planId) {
                $ppds = \App\Models\PaymentPlanDiscount::where('payment_plan_id', $planId)
                    ->with('discount')
                    ->get();

                $regDiscounts = $ppds->filter(function ($ppd) {
                    return $ppd->discount
                        && $ppd->discount->discount_category === 'registration_fee'
                        && ($ppd->discount->status ?? 'active') === 'active';
                });

                $pctSum = (float) $regDiscounts->where('discount_type', 'percentage')
                            ->sum('discount_value');
                $fixedSum = (float) $regDiscounts->where('discount_type', 'amount')
                            ->sum('discount_value');

                $names = $regDiscounts->map(function ($ppd) {
                    return $ppd->discount ? $ppd->discount->name : null;
                })->filter()->unique()->values()->all();
            }

            // Calculate effective discount & final registration fee
            $pctAmt = $baseRegFee * ($pctSum / 100);
            $discEff = min($baseRegFee, $pctAmt + $fixedSum);
            $registrationFee = round($baseRegFee - $discEff, 2);

            // Build remarks string
            $discountRemark = "Registration fee LKR {$baseRegFee}";
            if ($pctSum > 0) $discountRemark .= " - {$pctSum}%";
            if ($fixedSum > 0) $discountRemark .= " - LKR {$fixedSum}";
            if ($discEff > 0) $discountRemark .= " = discount LKR {$discEff}";
            $discountRemark .= "; payable LKR {$registrationFee}";
            if ($names) $discountRemark .= " (applied: " . implode(", ", $names) . ")";

            $request->merge([
                'remarks' => trim(($request->remarks ? $request->remarks . ' | ' : '') . $discountRemark)
            ]);
        }

        //!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!


        // 🔹 Calculate late fee (ONLY for course_fee installments)
        $lateFee = 0;
        $approvedLateFee = 0;
        if ($paymentType === 'course_fee' && $request->installment_number) {
            $plan = \App\Models\StudentPaymentPlan::where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->first();

            if ($plan) {
                $inst = \App\Models\PaymentInstallment::where('payment_plan_id', $plan->id)
                    ->where('installment_number', $request->installment_number)
                    ->first();

                if ($inst) {
                    $dueDate = \Carbon\Carbon::parse($inst->due_date)->startOfDay();

                    // Use the supplied effective date (actual payment date) so that
                    // retroactive entries by staff don't generate false late fees for
                    // payments that were actually on time.
                    $effectiveDate = $request->payment_effective_date
                        ? \Carbon\Carbon::parse($request->payment_effective_date)->startOfDay()
                        : now()->startOfDay();

                    $daysLate = $dueDate->lt($effectiveDate)
                        ? $dueDate->diffInDays($effectiveDate)
                        : 0;

                    $baseAmt = $inst->final_amount ?? $inst->amount ?? 0;

                    // 🔹 Calculate late fee and update installment
                    $calcFee = $this->calculateLateFee($baseAmt, $daysLate);
                    $inst->calculated_late_fee = $calcFee;
                    $inst->save();

                    $lateFee         = $calcFee;
                    $approvedLateFee = $inst->approved_late_fee ?? 0;
                }
            }
        }

        // 🔹 Total Fee = base fee + late fee - approved late fee.
        // For franchise fees, include SSCL + bank charges in total fee so
        // amount/total/remaining stay consistent in payment management tables.
        if ($paymentType === 'franchise_fee') {
            $totalFee = round($remainingAmount + $lateFee - $approvedLateFee, 2);
        } else {
            $totalFee = $courseFee + $franchiseFee + $registrationFee + $lateFee - $approvedLateFee;
        }

        // --- Prevent duplicate pending and paid slips ---
$existingPayment = \App\Models\PaymentDetail::where('student_id', $student->student_id)
    ->where('course_registration_id', $registration->id)
    ->where('installment_type', $paymentType) // ✅ Add this
    ->when($request->installment_number, fn($q) => $q->where('installment_number', $request->installment_number))
    ->when($request->due_date, fn($q) => $q->whereDate('due_date', $request->due_date))
    ->whereIn('status', ['pending', 'paid'])

    ->first();

if ($existingPayment) {
    $paidSoFar = collect($existingPayment->partial_payments ?? [])->sum('amount');
    $remaining = max(($totalFee - $paidSoFar), 0);

    $existingPayment->update([
        'amount'                 => $amount,
        'foreign_currency_amount'=> $foreignAmount,
        'foreign_currency_code'  => $foreignCurrency,
        'conversion_rate'        => $paymentType === 'franchise_fee' ? ($conversionRate > 0 ? $conversionRate : $existingPayment->conversion_rate) : null,
        'late_fee'               => $lateFee,
        'approved_late_fee'      => $approvedLateFee,
        'total_fee'              => $totalFee,
        'remaining_amount'       => $remaining,
        'sscl_tax_amount'        => $paymentType === 'franchise_fee' ? $ssclTaxAmount : 0,
        'bank_charges'           => $paymentType === 'franchise_fee' ? $bankCharges : 0,
        'payment_effective_date' => $request->payment_effective_date ?: null,
    ]);

    $slipData = $this->buildSlipArray(
        $existingPayment, $student, $course, $intake,
        $courseFee, $franchiseFee, $registrationFee,
        $lateFee, $approvedLateFee, $totalFee
    );
    $slipData['id'] = $existingPayment->id;
    $slipData['can_delete'] = true;
    $slipData['remarks']    = $request->remarks ?? '';

    session(['generated_slip_' . $existingPayment->transaction_id => $slipData]);

    return response()->json([
        'success'   => true,
        'slip_data' => $slipData,
        'message'   => 'Existing payment slip found. You can delete it if you want to regenerate.',
        'can_delete'=> true,
        'id'        => $existingPayment->id,
    ]);
}




        // --- Generate New Receipt Number ---
        $today      = date('Ymd');
        $latest     = \App\Models\PaymentDetail::where('transaction_id', 'like', "RCP{$today}%")
                        ->orderBy('transaction_id', 'desc')->first();
        $lastNumber = $latest ? (int) substr($latest->transaction_id, -4) : 0;
        $nextNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        $receiptNo  = 'RCP' . $today . $nextNumber;
        $installmentType = $paymentType; // 'course_fee' | 'franchise_fee' | 'registration_fee'


        // --- Create new Payment record ---
        $payment = \App\Models\PaymentDetail::create([
            'student_id'             => $student->student_id,
            'course_registration_id' => $registration->id,
            'amount'                 => $amount,
            'payment_method'         => 'Cash',
            'transaction_id'         => $receiptNo,
            'status'                 => 'pending',
            'remarks'                => $request->remarks,
            'due_date'               => $request->due_date,

            // 👇 Fix: Only course_fee/franchise_fee should store installment_number
            'installment_number'     => in_array($paymentType, ['course_fee','franchise_fee']) 
                                            ? $request->installment_number 
                                            : null,

            // ✅ New fields
            'late_fee'          => $lateFee,
            'approved_late_fee' => $approvedLateFee,
            'total_fee'         => $totalFee,
            'remaining_amount'  => $paymentType === 'franchise_fee' ? $remainingAmount : (float) $totalFee,
            'sscl_tax_amount'   => $paymentType === 'franchise_fee' ? $ssclTaxAmount : 0,
            'bank_charges'      => $paymentType === 'franchise_fee' ? $bankCharges : 0,
            'partial_payments'  => json_encode([]), // ensures proper JSON
            'foreign_currency_code'  => $foreignCurrency,
            'foreign_currency_amount'=> $foreignAmount,
            'conversion_rate'        => $paymentType === 'franchise_fee' ? $conversionRate : null,
            'installment_type'           => $installmentType,
            // Actual payment date supplied by staff; drives late-fee calculations
            'payment_effective_date'     => $request->payment_effective_date ?: null,
        ]);


        $slipData = $this->buildSlipArray(
            $payment, $student, $course, $intake,
            $courseFee, $franchiseFee, $registrationFee,
            $lateFee, $approvedLateFee, $totalFee
        );

        session(['generated_slip_' . $receiptNo => $slipData]);

        return response()->json([
            'success'   => true,
            'slip_data' => $slipData,
            'message'   => 'New payment slip generated.',
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage(),
        ], 500);
    }
}



/**
 * Helper: Build slip array
 */
private function buildSlipArray(\App\Models\PaymentDetail $payment, $student, $course, $intake, $courseFee, $franchiseFee, $registrationFee, $lateFee, $approvedLateFee, $totalFee)
{
    // Normalize partial payments
    $partials = $payment->partial_payments ?? [];
    if (!is_array($partials)) {
        $partials = json_decode($partials, true) ?? [];
    }

    $rawType = $payment->installment_type ?? $payment->payment_type ?? '';
    $typeDisplayMap = [
        'course_fee'       => 'Course Fee',
        'franchise_fee'    => 'Franchise Fee',
        'registration_fee' => 'Registration Fee',
        'other'            => 'Other',
    ];
    $typeDisplay = $typeDisplayMap[$rawType] ?? ucwords(str_replace('_', ' ', $rawType));

    // Use payment_effective_date for the slip date when payment_date is not yet set
    $slipDate = $payment->payment_date
        ?? $payment->payment_effective_date
        ?? now()->toDateString();

    return [
        'receipt_no'          => $payment->transaction_id,
        'student_id'          => $student->student_id,
        'student_name'        => $student->full_name,
        'student_nic'         => $student->id_value,
        'mobile_phone'        => $student->mobile_phone ?? '-',
        'course_name'         => $course->course_name ?? 'N/A',
        'course_code'         => $course->course_code ?? 'N/A',
        'intake'              => $intake->batch ?? 'N/A',
        'intake_id'           => $intake->intake_id ?? null,
        'payment_type'        => $rawType,
        'payment_type_display'=> $typeDisplay,
        'amount'              => (float) $payment->amount,
        'installment_number'  => $payment->installment_number,
        'payment_name'        => $payment->payment_name ?? null,
        'due_date'            => $payment->due_date,
        'payment_date'        => $slipDate,
        'payment_method'      => $payment->payment_method ?? 'Cash',
        'remarks'           => $payment->remarks,
        'status'            => $payment->status,
        'course_fee'        => $courseFee,
        'franchise_fee'     => $franchiseFee,
        'registration_fee'  => $registrationFee,
        'late_fee'          => (float) $payment->late_fee,
        'approved_late_fee' => (float) $payment->approved_late_fee,
        'total_fee'         => (float) $payment->total_fee,
        'remaining_amount'  => (float) $payment->remaining_amount,
        'partial_payments'  => $partials,   // ✅ Always an array
        'foreign_currency_code'   => $payment->foreign_currency_code,     
        'foreign_currency_amount' => (float) $payment->foreign_currency_amount,
        // Keys expected by the JS slip preview (franchise fee display)
        'franchise_fee_currency' => $payment->foreign_currency_code,
        'lkr_amount'             => (float) $payment->total_fee,
        'generated_at'      => now()->format('Y-m-d H:i:s'),
        'valid_until'       => now()->addDays(7)->format('Y-m-d'),
        'sscl_tax_amount'   => $payment->sscl_tax_amount,   // 👈 new
        'bank_charges'      => $payment->bank_charges, 
    ];
}


public function deletePaymentSlip($id)
{
    try {
        $payment = \App\Models\PaymentDetail::findOrFail($id);

        if ($payment->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending slips can be deleted.'
            ], 400);
        }

        $payment->delete();

        return response()->json([
            'success' => true,
            'message' => 'Payment slip deleted successfully. You can now generate a new one.'
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Error deleting slip: ' . $e->getMessage()
        ], 500);
    }
}

public function recordPartialPayment(Request $request, $id)
{
    $request->validate([
        'amount'  => 'required|numeric|gt:0',
        'method'  => 'nullable|string',
        'date'    => 'nullable|date',
    ]);

    $payment = \App\Models\PaymentDetail::findOrFail($id);

    // Load history
    $history = $payment->partial_payments ?? [];

    // Append new payment
    $history[] = [
        'date'   => $request->date ?? now()->toDateString(),
        'amount' => (float) $request->amount,
        'method' => $request->method ?? 'Cash',
    ];

    // Update remaining
    $paidSoFar = collect($history)->sum('amount');
    $remaining = max(($payment->total_fee - $paidSoFar), 0);

    // Save
    $payment->partial_payments = $history;
    $payment->remaining_amount = $remaining;

    if ($remaining <= 0) {
        $payment->status = 'paid';
    }

    $payment->save();

    return back()->with('success', 'Partial payment recorded successfully.');
}





    /**
     * Get payment type display name.
     */
    private function getPaymentTypeDisplay($paymentType)
    {
        $types = [
            'course_fee' => 'Course Fee',
            'franchise_fee' => 'Franchise Fee',
            'registration_fee' => 'Registration Fee',
        ];

        return $types[$paymentType] ?? ucfirst(str_replace('_', ' ', $paymentType));
    }

    /**
     * Download payment slip as PDF.
     */
    public function downloadPaymentSlipPDF(Request $request)
{
    try {
        $request->validate([
            'receipt_no' => 'required|string',
        ]);

        // Try session first
        $slipData = session('generated_slip_' . $request->receipt_no);

        if (!$slipData) {
            // Fallback to DB with correct relations
            $payment = PaymentDetail::with(['student', 'registration.course', 'registration.intake'])
                ->where('transaction_id', $request->receipt_no)
                ->first();

            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment slip not found or expired.'
                ], \Illuminate\Http\Response::HTTP_NOT_FOUND);
            }

            // Build in the exact shape the Blade expects
            $slipData = $this->buildSlipDataFromPaymentDetail($payment);
        }

        // Generate a CSP nonce for inline styles
        $cspNonce = base64_encode(random_bytes(16));

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('payments.payment_slip', compact('slipData', 'cspNonce'))
              ->setPaper('A4', 'portrait')
              ->setOptions([
                  'isHtml5ParserEnabled' => true,
                  'isRemoteEnabled'      => true,
                  'defaultFont'          => 'Arial',
              ]);

        $filename = 'Payment_Slip_' . $slipData['receipt_no'] . '_' . date('Y-m-d') . '.pdf';
        return $pdf->download($filename);

    } catch (\Throwable $e) {
        return response()->json([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage()
        ], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}




    /**
     * Save payment record after payment is made.
     */
    public function savePaymentRecord(Request $request)
{
    try {
        $request->validate([
            'receipt_no' => 'required|string',
            'payment_method' => 'required|string',
            'payment_date' => 'required|date',
            'remarks' => 'nullable|string',
        ]);

        // ✅ Find the existing payment record by receipt number
        $paymentDetail = PaymentDetail::where('transaction_id', $request->receipt_no)->first();

        if (!$paymentDetail) {
            return response()->json([
                'success' => false,
                'message' => 'Payment record not found for this receipt number.'
            ], Response::HTTP_NOT_FOUND);
        }

        // ✅ Update the payment record
        $paymentDetail->update([
            'payment_method' => $request->payment_method,
            'status' => 'paid',
            'remarks' => $request->remarks ?? $paymentDetail->remarks,
            'paid_date' => $request->payment_date,
        ]);

        // ✅ Also update installment status if applicable
        if ($paymentDetail->installment_number) {
            $registration = CourseRegistration::find($paymentDetail->course_registration_id);
            if ($registration) {
                $this->updateInstallmentStatus(
                    $paymentDetail->student_id,
                    $registration->course_id,
                    $paymentDetail->installment_number
                );
            }
        }

        // ✅ Clear the slip from session (optional)
        session()->forget('generated_slip_' . $request->receipt_no);

        return response()->json([
            'success' => true,
            'message' => 'Payment record updated successfully.',
            'payment_id' => $paymentDetail->id
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    /**
     * Update installment status when payment is made.
     */
    private function updateInstallmentStatus($studentId, $courseId, $installmentNumber)
    {
        try {
            $paymentPlan = \App\Models\StudentPaymentPlan::where('student_id', $studentId)
                ->where('course_id', $courseId)
                ->first();

            if ($paymentPlan) {
                $installment = \App\Models\PaymentInstallment::where('payment_plan_id', $paymentPlan->id)
                    ->where('installment_number', $installmentNumber)
                    ->first();

                if ($installment) {
                    $installment->update([
                        'status' => 'paid',
                        'paid_date' => now()
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Error updating installment status: ' . $e->getMessage());
        }
    }



    /**
 * Get payment records for Update Records tab.
 */
public function getPaymentRecords(Request $request)
{
    try {
        $request->validate([
            'student_nic' => 'required|string',
            'course_id'   => 'required|integer|exists:courses,course_id',
        ]);

        // 🔹 Find student by NIC
        $student = Student::where('id_value', $request->student_nic)->first();
        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found with the provided NIC.'
            ], Response::HTTP_NOT_FOUND);
        }

        // 🔹 Verify registration
        $registration = CourseRegistration::where('student_id', $student->student_id)
            ->where('course_id', $request->course_id)
            ->first();

        if (!$registration) {
            return response()->json([
                'success' => false,
                'message' => 'Student is not registered for this course.'
            ], Response::HTTP_NOT_FOUND);
        }

        // 🔹 Fetch payment details
        $records = PaymentDetail::where('student_id', $student->student_id)
            ->where('course_registration_id', $registration->id)
            ->get()
            ->map(function ($payment) use ($student) {
    $paymentType = $payment->payment_type ?? $payment->installment_type ?? 'course_fee';
    $baseAmount = (float) ($payment->amount ?? 0);
    $lateFee = (float) ($payment->late_fee ?? 0);
    $approvedLateFee = (float) ($payment->approved_late_fee ?? 0);
    $storedTotalFee = (float) ($payment->total_fee ?? 0);
    $remainingAmount = (float) ($payment->remaining_amount ?? 0);
    $ssclTaxAmount = (float) ($payment->sscl_tax_amount ?? 0);
    $bankCharges = (float) ($payment->bank_charges ?? 0);

    // Normalize legacy franchise rows where total_fee/amount were saved without
    // SSCL + bank charges while remaining_amount already included them.
    if ($paymentType === 'franchise_fee') {
        $convertedWithCharges = round($baseAmount + $ssclTaxAmount + $bankCharges, 2);
        if ($convertedWithCharges > 0) {
            $baseAmount = $convertedWithCharges;
            $storedTotalFee = round($convertedWithCharges + $lateFee - $approvedLateFee, 2);
        }
    }

    return [
        'payment_id'         => $payment->id,
        'student_id'         => $student->student_id,
        'student_name'       => $student->full_name,
        'payment_type'       => $paymentType,
        'installment_number' => $payment->installment_number,
        'amount'             => $baseAmount,
        'late_fee'           => $lateFee,
        'approved_late_fee'  => $approvedLateFee,
        'total_fee'          => $storedTotalFee,
        'remaining_amount'   => $remainingAmount,

        // ✅ always return array instead of raw JSON string
        'partial_payments' => $payment->partial_payments 
                            ? (is_array($payment->partial_payments) 
                                ? $payment->partial_payments 
                                : json_decode($payment->partial_payments, true)) 
                            : [],


        'payment_method'     => $payment->payment_method,
        'payment_date'       => $payment->payment_date
            ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d')
            : null,
        'receipt_no'         => $payment->transaction_id,
        'status'             => $payment->status,
        'remarks'            => $payment->remarks,
    ];
});



        return response()->json([
            'success' => true,
            'records' => $records
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    /**
 * Update payment record (Update Records tab).
 */
public function updatePaymentRecord(Request $request)
{
    try {
        $updates = $request->has('updates')
            ? $request->input('updates', [])
            : [$request->all()];

        if (!is_array($updates) || empty($updates)) {
            return response()->json([
                'success' => false,
                'message' => 'No update payload provided.'
            ], 422);
        }

        $updatedCount = 0;

        foreach ($updates as $payload) {
            if (!is_array($payload)) {
                continue;
            }

            $validator = validator($payload, [
                'id'                => 'required|exists:payment_details,id',
                'payment_type'      => 'nullable|string',
                'amount'            => 'nullable|numeric|min:0',
                'late_fee'          => 'nullable|numeric|min:0',
                'approved_late_fee' => 'nullable|numeric|min:0',
                'total_fee'         => 'nullable|numeric|min:0',
                'remaining_amount'  => 'nullable|numeric|min:0',
                'payment_method'    => 'nullable|string',
                'payment_date'      => 'nullable|date',
                'receipt_no'        => 'nullable|string',
                'status'            => 'nullable|string',
                'remarks'           => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ], 422);
            }

            $payment = PaymentDetail::findOrFail($payload['id']);

            $amount = array_key_exists('amount', $payload)
                ? (float) $payload['amount']
                : (float) ($payment->amount ?? 0);
            $lateFee = array_key_exists('late_fee', $payload)
                ? (float) $payload['late_fee']
                : (float) ($payment->late_fee ?? 0);
            $approvedLateFee = array_key_exists('approved_late_fee', $payload)
                ? (float) $payload['approved_late_fee']
                : (float) ($payment->approved_late_fee ?? 0);

            $updateData = [];

            if (array_key_exists('payment_type', $payload)) {
                $updateData['payment_type'] = $payload['payment_type'];
            }
            if (array_key_exists('amount', $payload)) {
                $updateData['amount'] = $amount;
            }
            if (array_key_exists('late_fee', $payload)) {
                $updateData['late_fee'] = $lateFee;
            }
            if (array_key_exists('approved_late_fee', $payload)) {
                $updateData['approved_late_fee'] = $approvedLateFee;
            }
            if (array_key_exists('total_fee', $payload)) {
                $updateData['total_fee'] = (float) $payload['total_fee'];
            } elseif (
                array_key_exists('amount', $payload)
                || array_key_exists('late_fee', $payload)
                || array_key_exists('approved_late_fee', $payload)
            ) {
                $updateData['total_fee'] = $amount + $lateFee - $approvedLateFee;
            }
            if (array_key_exists('remaining_amount', $payload)) {
                $updateData['remaining_amount'] = (float) $payload['remaining_amount'];
            }
            if (array_key_exists('payment_method', $payload)) {
                $updateData['payment_method'] = $payload['payment_method'];
            }
            if (array_key_exists('payment_date', $payload)) {
                $updateData['payment_date'] = $payload['payment_date'];
            }
            if (array_key_exists('receipt_no', $payload)) {
                $updateData['transaction_id'] = $payload['receipt_no'];
            }
            if (array_key_exists('status', $payload)) {
                $updateData['status'] = $payload['status'];
            }
            if (array_key_exists('remarks', $payload)) {
                $updateData['remarks'] = $payload['remarks'];
            }

            if (!empty($updateData)) {
                $payment->update($updateData);
                $updatedCount++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => $updatedCount > 0
                ? 'Payment record(s) updated successfully.'
                : 'No changes detected.',
            'updated_count' => $updatedCount,
        ], Response::HTTP_OK);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage()
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

    /**
     * Get SLT Loan Recovery / Receivable records for a student.
     */
    public function getSltLoanRecords(Request $request)
    {
        try {
            $request->validate([
                'student_nic' => 'required|string',
                'course_id'   => 'required|integer|exists:courses,course_id',
                'installment_number' => 'nullable|integer|min:1',
                'effective_date' => 'nullable|date',
            ]);

            $student = Student::where('id_value', $request->student_nic)
                ->orWhere('student_id', $request->student_nic)
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found with the provided NIC.'
                ], Response::HTTP_NOT_FOUND);
            }

            $plan = StudentPaymentPlan::with('course')
                ->where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->where('status', '!=', 'archived')
                ->orderByDesc('id')
                ->first();

            if (!$plan || strtolower($plan->slt_loan_applied ?? 'no') !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'No active SLT loan plan found for this student.'
                ], Response::HTTP_NOT_FOUND);
            }

            $loanAmount = (float) ($plan->slt_loan_amount ?? 0);
            $years = (int) ($plan->slt_loan_years ?? 0);
            $installmentCount = $years > 0 ? $years * 12 : 0;
            $monthlyReceivable = $installmentCount > 0 ? round($loanAmount / $installmentCount, 2) : 0;

            $existingRecords = SltLoanReceivableRecord::where('student_payment_plan_id', $plan->id)
                ->orderBy('loan_installment_number')
                ->get()
                ->keyBy('loan_installment_number');

            $installmentFilter = $request->filled('installment_number')
                ? (int) $request->installment_number
                : null;

            if (!$installmentFilter && $request->filled('effective_date')) {
                $matchingRecord = $existingRecords->first(function ($record) use ($request) {
                    return optional($record->payment_effective_date)->format('Y-m-d') === $request->effective_date;
                });

                if ($matchingRecord) {
                    $installmentFilter = (int) $matchingRecord->loan_installment_number;
                } elseif ($installmentCount > 0) {
                    $installmentFilter = min($existingRecords->count() + 1, $installmentCount);
                }
            }

            if ($installmentCount <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'SLT loan installment schedule is not configured for this student.'
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $startInstallment = $installmentFilter ?: 1;
            $endInstallment = $installmentFilter ?: $installmentCount;

            $records = [];
            for ($i = $startInstallment; $i <= $endInstallment; $i++) {
                $record = $existingRecords->get($i);
                $isPaid = $record && strtolower($record->status ?? '') === 'paid';
                $records[] = [
                    'id'                        => $record?->id,
                    'payment_id'                => $record?->id,
                    'student_payment_plan_id'   => $plan->id,
                    'student_id'                => $student->student_id,
                    'course_id'                 => $request->course_id,
                    'payment_type'              => 'SLT Loan Recovery',
                    'installment_number'        => $i,
                    'total_loan_amount'         => $loanAmount,
                    'monthly_receivable_amount' => $monthlyReceivable,
                    'amount'                    => $monthlyReceivable,
                    'total_fee'                 => $monthlyReceivable,
                    'remaining_amount'          => $isPaid ? 0.00 : $monthlyReceivable,
                    'payment_effective_date'    => $record?->payment_effective_date?->format('Y-m-d') ?? optional($plan->slt_receivable_effective_date)->format('Y-m-d') ?? date('Y-m-d'),
                    'payment_date'              => $record?->payment_effective_date?->format('Y-m-d') ?? optional($plan->slt_receivable_effective_date)->format('Y-m-d') ?? date('Y-m-d'),
                    'payment_method'            => $record?->payment_method ?? 'cash',
                    'receipt_no'                => $record?->receipt_no ?? '',
                    'status'                    => $record?->status ?? 'pending',
                    'remarks'                   => $record?->remarks ?? '',
                ];
            }

            return response()->json([
                'success' => true,
                'student_name' => $student->full_name,
                'student_nic' => $student->id_value,
                'course_name' => optional($plan->course)->course_name ?? '',
                'total_loan_amount' => $loanAmount,
                'monthly_receivable' => $monthlyReceivable,
                'records' => $records
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update SLT Loan Recovery / Receivable record(s).
     */
    public function updateSltLoanRecord(Request $request)
    {
        try {
            $updates = $request->has('updates')
                ? $request->input('updates', [])
                : [$request->all()];

            $validator = Validator::make(['updates' => $updates], [
                'updates'                                => 'required|array|min:1',
                'updates.*.student_payment_plan_id'      => 'required|integer|exists:student_payment_plans,id',
                'updates.*.installment_number'            => 'required|integer|min:1',
                'updates.*.payment_effective_date'        => 'nullable|date',
                'updates.*.payment_date'                  => 'nullable|date',
                'updates.*.payment_method'                => 'nullable|in:cash,cheque,bank_transfer,online,card',
                'updates.*.receipt_no'                    => 'nullable|string|max:255',
                'updates.*.status'                        => 'nullable|in:pending,paid,failed',
                'updates.*.remarks'                       => 'nullable|string|max:5000',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The SLT loan recovery details are invalid.',
                    'errors' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $updatedCount = DB::transaction(function () use ($updates) {
                $updatedCount = 0;
                foreach ($updates as $payload) {
                    $planId = (int) $payload['student_payment_plan_id'];
                    $installmentNo = (int) $payload['installment_number'];

                    $plan = StudentPaymentPlan::lockForUpdate()->findOrFail($planId);
                    if (strtolower($plan->slt_loan_applied ?? 'no') !== 'yes') {
                        throw new \InvalidArgumentException('The selected payment plan does not have an SLT loan applied.');
                    }

                    $loanAmount = (float) ($plan->slt_loan_amount ?? 0);
                    $years = (int) ($plan->slt_loan_years ?? 0);
                    $installmentCount = $years > 0 ? $years * 12 : 0;
                    if ($installmentCount <= 0 || $installmentNo > $installmentCount) {
                        throw new \InvalidArgumentException('The selected SLT loan installment is outside the configured repayment schedule.');
                    }
                    $monthlyReceivable = round($loanAmount / $installmentCount, 2);

                    $effectiveDate = $payload['payment_effective_date'] ?? $payload['payment_date'] ?? date('Y-m-d');
                    $method = $payload['payment_method'] ?? 'cash';
                    $receiptNo = $payload['receipt_no'] ?? null;
                    $status = $payload['status'] ?? 'pending';
                    $remarks = $payload['remarks'] ?? null;

                    SltLoanReceivableRecord::updateOrCreate(
                        [
                            'student_payment_plan_id' => $planId,
                            'loan_installment_number' => $installmentNo,
                        ],
                        [
                            'student_id'                => $plan->student_id,
                            'course_id'                 => $plan->course_id,
                            'total_loan_amount'         => $loanAmount,
                            'loan_taken_years'          => $years,
                            'loan_installment_count'    => $installmentCount,
                            'apply_from_installment'    => $plan->slt_loan_start_installment ?? 1,
                            'monthly_receivable_amount' => $monthlyReceivable,
                            'payment_effective_date'    => $effectiveDate,
                            'payment_method'            => $method,
                            'receipt_no'                => $receiptNo,
                            'status'                    => $status,
                            'remarks'                   => $remarks,
                        ]
                    );
                    $updatedCount++;
                }

                return $updatedCount;
            });

            return response()->json([
                'success' => true,
                'message' => $updatedCount > 0 ? 'SLT loan recovery record(s) updated successfully.' : 'No changes saved.',
                'updated_count' => $updatedCount
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Delete payment record.
     */
    public function deletePaymentRecord(Request $request)
    {
        try {
            $request->validate([
                'payment_id' => 'required|exists:payment_details,id',
            ]);

            PaymentDetail::find($request->payment_id)->delete();

            return response()->json(['success' => true, 'message' => 'Payment record deleted successfully.'], Response::HTTP_OK);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

public function makePayment(Request $request)
{
    try {
        $request->validate([
            'payment_id'     => 'required|exists:payment_details,id',
            'amount'         => 'required|numeric|min:1',
            'payment_method' => 'required|string',
            'payment_date'   => 'required|date',
            'remarks'        => 'nullable|string',
            'slip'           => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        $payment = PaymentDetail::findOrFail($request->payment_id);

        if (strtolower($payment->status ?? '') === 'paid' || ($payment->remaining_amount !== null && (float)$payment->remaining_amount <= 0)) {
            return response()->json([
                'success' => false,
                'message' => 'This installment is already fully paid and cannot accept additional payments.'
            ], 422);
        }

        $paymentType = $payment->installment_type ?? null;

        // Decode existing partial payments (JSON field)
        $partials = $payment->partial_payments ?? [];
        if (!is_array($partials)) {
            $partials = json_decode($partials, true) ?? [];
        }

        // Handle slip upload (optional)
        $slipPath = null;
        if ($request->hasFile('slip')) {
            $slipPath = $request->file('slip')->store('payment_slips', 'public');
        }

        $remainingAmount = $payment->remaining_amount !== null
            ? (float) $payment->remaining_amount
            : max((float)$payment->total_fee - collect($partials)->sum('amount'), 0);

        if (abs($remainingAmount - (float)$request->amount) > 0.009) {
            return response()->json([
                'success' => false,
                'message' => 'Payment amount must equal the remaining installment amount.'
            ], 422);
        }

        // Add new partial payment entry
        $partials[] = [
            'amount'  => (float)$request->amount,
            'method'  => $request->payment_method,
            'date'    => $request->payment_date,
            'remarks' => $request->remarks,
            'slip'    => $slipPath, // ✅ store path in partial payments only
        ];

        // Calculate totals
        $paidSoFar  = collect($partials)->sum('amount');
        $remaining  = max(($payment->total_fee - $paidSoFar), 0);

        // Update payment record
        $payment->update([
            'partial_payments' => $partials,
            'remaining_amount' => $remaining,
            'status'           => $remaining <= 0 ? 'paid' : 'pending',
        ]);

        // If fully paid, mark the matching installment as paid.
        if ($remaining <= 0 && in_array($paymentType, ['course_fee', 'franchise_fee'], true) && $payment->installment_number) {
            $registration = $payment->registration;
            if ($registration) {
                $plan = \App\Models\StudentPaymentPlan::where('student_id', $payment->student_id)
                    ->where('course_id', $registration->course_id)
                    ->where('status', 'active')
                    ->first();

                if (!$plan) {
                    $plan = \App\Models\StudentPaymentPlan::where('student_id', $payment->student_id)
                        ->where('course_id', $registration->course_id)
                        ->first();
                }

                if ($plan) {
                    \App\Models\PaymentInstallment::where('payment_plan_id', $plan->id)
                        ->where('installment_number', $payment->installment_number)
                        ->update(array_merge([
                            'status'    => 'paid',
                            // Use the submitted payment_date (the actual payment date) not
                            // the system clock, so late-fee history is accurate even when
                            // staff enter payments retrospectively.
                            'paid_date' => $request->payment_date ?? now(),
                        ], \App\Support\UserTrackingData::forUpdate()));
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => $remaining <= 0 
                ? 'Payment completed. Status updated to PAID.' 
                : 'Partial payment recorded successfully.',
            'remaining_amount' => $remaining,
            'status' => $payment->status,
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'An error occurred: ' . $e->getMessage()
        ], 500);
    }
}






    /**
     * Get payment summary for a specific student and course.
     */
    public function getPaymentSummary(Request $request)
    {
        try {
            $request->validate([
                'student_nic' => 'required|string',
                'course_id' => 'required|integer|exists:courses,course_id',
            ]);

            // Find student by NIC
            $student = Student::where('id_value', $request->student_nic)->first();
            
            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found with the provided NIC.'], Response::HTTP_NOT_FOUND);
            }

            // Get course registration for this student and course
            $registration = CourseRegistration::where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->with(['course', 'intake'])
                ->first();

            if (!$registration) {
                return response()->json(['success' => false, 'message' => 'Student is not registered for this course.'], Response::HTTP_NOT_FOUND);
            }

            // Get all payment records for this student and course registration
            $payments = PaymentDetail::where('student_id', $student->student_id)
                ->where('course_registration_id', $registration->id)
                ->orderBy('created_at', 'desc')
                ->get();

            // Get student-specific payment plan (with discounts and loans applied)
            $studentPaymentPlan = \App\Models\StudentPaymentPlan::where('student_id', $student->student_id)
                ->where('course_id', $registration->course_id)
                ->with(['installments', 'discounts'])
                ->first();

            // Calculate course fees from student payment plan
            $courseFee = 0;
            $franchiseFee = 0;
            $registrationFee = $registration->intake->registration_fee ?? 0;
            
            if ($studentPaymentPlan) {
                // Use the final amount from student payment plan (after discounts and loans)
                $totalCourseAmount = $studentPaymentPlan->final_amount;
                
                // Calculate individual fees from installments
                foreach ($studentPaymentPlan->installments as $installment) {
                    $courseFee += $installment->amount; // This is the final amount after discounts
                }
            } else {
                // Fallback to general payment plan if no student-specific plan exists
                $paymentPlan = PaymentPlan::where('course_id', $registration->course_id)
                    ->where('intake_id', $registration->intake_id)
                    ->first();
                
                if ($paymentPlan && $paymentPlan->installments) {
                    $installmentsData = $paymentPlan->installments;
                    if (is_string($installmentsData)) {
                        $installmentsData = json_decode($installmentsData, true);
                    }
                    
                    if (is_array($installmentsData)) {
                        foreach ($installmentsData as $installment) {
                            $courseFee += $installment['local_amount'] ?? 0;
                            $franchiseFee += $installment['international_amount'] ?? 0;
                        }
                    }
                }
                
                $totalCourseAmount = $courseFee + $franchiseFee + $registrationFee;
            }

            // Group payments by type
            $paymentTypes = [
                'course_fee' => ['name' => 'Course Fee', 'total' => $courseFee, 'paid' => 0, 'payments' => []],
                'franchise_fee' => ['name' => 'Franchise Fee', 'total' => $franchiseFee, 'paid' => 0, 'payments' => []],
                'registration_fee' => ['name' => 'Registration Fee', 'total' => $registrationFee, 'paid' => 0, 'payments' => []],
                'library_fee' => ['name' => 'Library Fee', 'total' => 0, 'paid' => 0, 'payments' => []],
                'hostel_fee' => ['name' => 'Hostel Fee', 'total' => 0, 'paid' => 0, 'payments' => []],
                'other' => ['name' => 'Other', 'total' => 0, 'paid' => 0, 'payments' => []],
            ];

            // Process payments
            $totalPaid = 0;
            $paymentHistory = [];

            foreach ($payments as $payment) {
                $paymentType = $payment->payment_type ?? 'course_fee';
                $amount = $payment->amount;
                
                // Categorize payment type
                $categorizedType = $this->categorizePaymentType($paymentType);
                
                if (isset($paymentTypes[$categorizedType])) {
                    $paymentTypes[$categorizedType]['paid'] += $amount;
                    $paymentTypes[$categorizedType]['payments'][] = $payment;
                } else {
                    // If unknown type, add to "other"
                    $paymentTypes['other']['paid'] += $amount;
                    $paymentTypes['other']['payments'][] = $payment;
                }
                
                $totalPaid += $amount;
                
                // Add to payment history
                $paymentHistory[] = [
                    'payment_date' => $payment->created_at->format('Y-m-d'),
                    'payment_type' => $this->getPaymentTypeDisplay($paymentType),
                    'amount' => $amount,
                    'payment_method' => $payment->payment_method,
                    'receipt_no' => $payment->transaction_id,
                    'status' => $payment->status === 'paid' ? 'Paid' : 'Pending'
                ];
            }

            // Calculate summary for each payment type
            $paymentDetails = [];
            foreach ($paymentTypes as $type => $data) {
                if ($data['total'] > 0 || $data['paid'] > 0) {
                    $outstanding = $data['total'] - $data['paid'];
                    $paymentRate = $data['total'] > 0 ? round(($data['paid'] / $data['total']) * 100, 2) : 0;
                    
                    // Get installment count and last payment date
                    $installmentCount = count(array_filter($data['payments'], function($p) {
                        return !empty($p->installment_number);
                    }));
                    
                    $lastPayment = collect($data['payments'])->sortByDesc('created_at')->first();
                    $lastPaymentDate = $lastPayment ? $lastPayment->created_at->format('Y-m-d') : null;

                    // Prepare detailed payment records for this type
                    $detailedPayments = [];
                    foreach ($data['payments'] as $payment) {
                        $detailedPayments[] = [
                            'total_amount' => $data['total'],
                            'paid_amount' => $payment->amount,
                            'outstanding' => $data['total'] - $data['paid'],
                            'payment_date' => $payment->created_at->format('Y-m-d'),
                            'due_date' => $payment->due_date ? $payment->due_date->format('Y-m-d') : null,
                            'receipt_no' => $payment->transaction_id,
                            'uploaded_receipt' => $payment->paid_slip_path,
                            'installment_number' => $payment->installment_number,
                            'payment_method' => $payment->payment_method,
                            'status' => $payment->status === 'paid' ? 'Paid' : 'Pending'
                        ];
                    }

                    $paymentDetails[] = [
                        'payment_type' => $data['name'],
                        'total_amount' => $data['total'],
                        'paid_amount' => $data['paid'],
                        'outstanding' => $outstanding,
                        'payment_rate' => $paymentRate,
                        'installment_count' => $installmentCount,
                        'last_payment_date' => $lastPaymentDate,
                        'payments' => $detailedPayments
                    ];
                }
            }

            $totalOutstanding = $totalCourseAmount - $totalPaid;
            $overallPaymentRate = $totalCourseAmount > 0 ? round(($totalPaid / $totalCourseAmount) * 100, 2) : 0;



            $summary = [
                'student' => [
                    'student_id' => $student->student_id,
                    'student_name' => $student->full_name,
                    'course_name' => $registration->course->course_name,
                    'registration_date' => $registration->registration_date->format('Y-m-d'),
                    'total_amount' => $totalCourseAmount
                ],
                'total_amount' => $totalCourseAmount,
                'total_paid' => $totalPaid,
                'total_outstanding' => $totalOutstanding,
                'payment_rate' => $overallPaymentRate,
                'payment_details' => $paymentDetails,
                'payment_history' => $paymentHistory
            ];

            return response()->json([
                'success' => true,
                'summary' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Export payment summary.
     */
    public function exportPaymentSummary(Request $request)
    {
        try {
            $request->validate([
                'format' => 'required|in:pdf,excel,csv',
                'summary_data' => 'required|array',
            ]);

            // This is a placeholder for the actual export functionality
            // You would implement PDF, Excel, or CSV generation here
            
            return response()->json([
                'success' => true,
                'message' => ucfirst($request->format) . ' export generated successfully.',
                'download_url' => '/downloads/payment-summary.' . $request->format
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get intakes for course and location.
     */
    public function getIntakesForCourseAndLocation($courseID, $location)
    {
        try {
            $intakes = Intake::where('course_id', $courseID)
                ->where('location', $location)
                ->get();

            return response()->json(['success' => true, 'intakes' => $intakes]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Categorize payment type for summary.
     */
    private function categorizePaymentType($paymentType)
    {
        $types = [
            'course_fee' => 'course_fee',
            'franchise_fee' => 'franchise_fee',
            'registration_fee' => 'registration_fee',
            'library_fee' => 'library_fee',
            'hostel_fee' => 'hostel_fee',
            'other' => 'other', // Default for unknown types
        ];

        return $types[$paymentType] ?? 'other';
    }

    /**
     * Calculate final amount after applying discounts and loans.
     */
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

    private function calculateFinalAmount($baseAmount, $discounts, $sltLoanAmount, $totalInstallments)
    {
        $finalAmount = $baseAmount;
        
        // Apply percentage discounts
        $totalDiscountPercentage = 0;
        foreach ($discounts as $discount) {
            // Handle both Discount model and PaymentPlanDiscount model
            $discountType = $discount->discount_type ?? $discount->type ?? null;
            $discountValue = $discount->discount_value ?? $discount->value ?? 0;
            
            if ($discountType === 'percentage') {
                $totalDiscountPercentage += $discountValue;
            }
        }
        
        if ($totalDiscountPercentage > 0) {
            $finalAmount = $finalAmount - ($finalAmount * $totalDiscountPercentage / 100);
        }
        
        // Apply fixed amount discounts
        $totalDiscountAmount = 0;
        foreach ($discounts as $discount) {
            // Handle both Discount model and PaymentPlanDiscount model
            $discountType = $discount->discount_type ?? $discount->type ?? null;
            $discountValue = $discount->discount_value ?? $discount->value ?? 0;
            
            if ($discountType === 'fixed') {
                $totalDiscountAmount += $discountValue;
            }
        }
        
        if ($totalDiscountAmount > 0) {
            $finalAmount = $finalAmount - $totalDiscountAmount;
        }
        
        // Apply SLT loan (distributed across installments)
        if ($sltLoanAmount > 0 && $totalInstallments > 0) {
            $sltLoanPerInstallment = $sltLoanAmount / $totalInstallments;
            $finalAmount = $finalAmount - $sltLoanPerInstallment;
        }
        
        // Ensure final amount is not negative
        return max(0, $finalAmount);
    }
  /**
 * Build the array the Blade view expects from a PaymentDetail.
 * $overrides lets you inject values we only know at request-time (e.g., FX rate).
 */
private function buildSlipDataFromPaymentDetail(\App\Models\PaymentDetail $payment, array $overrides = []): array
{
    $student       = $payment->student;
    $registration  = $payment->registration;              // CourseRegistration
    $course        = optional($registration)->course;
    $intake        = optional($registration)->intake;

    // what type was this? if not stored, guess "course_fee" and let UI text override
    $type = $overrides['payment_type'] ?? ($payment->payment_type ?? 'course_fee');

    // FX (for franchise): prefer stored payment conversion rate, otherwise use override.
    $currencyFrom = $overrides['currency_from'] ?? ($payment->foreign_currency_code ?? null);
    $convRate     = isset($overrides['conversion_rate'])
        ? (float)$overrides['conversion_rate']
        : ($payment->conversion_rate ?? null);

    $foreignAmount = $payment->foreign_currency_amount !== null
        ? (float)$payment->foreign_currency_amount
        : (float)$payment->amount;

    $lkrAmount = null;
    if ($type === 'franchise_fee') {
        if ($payment->conversion_rate !== null && $payment->foreign_currency_amount !== null) {
            $lkrAmount = round($payment->foreign_currency_amount * $payment->conversion_rate, 2);
        } elseif ($convRate !== null) {
            $lkrAmount = round($foreignAmount * $convRate, 2);
        }
    }

    // currency to display for franchise (fallback to intake’s currency fields if you have them)
    $fxCurrency   = $overrides['franchise_fee_currency']
        ?? ($intake->franchise_payment_currency
            ?? $intake->international_currency
            ?? 'USD');

    // simple per-type breakdown for the “Payment Breakdown” table
    $courseFee       = $type === 'course_fee'       ? (float)$payment->amount : 0.0;
    $franchiseFee    = $type === 'franchise_fee'    ? (float)$payment->amount : 0.0;
    $registrationFee = $type === 'registration_fee' ? (float)($intake->registration_fee ?? 0) : 0.0;

    return [
        'receipt_no'             => $payment->transaction_id,
        'student_id'             => $payment->student_id,
        'student_name'           => optional($student)->full_name ?? 'N/A',
        'student_nic'            => optional($student)->id_value ?? 'N/A',

        'course_name'            => optional($course)->course_name ?? 'N/A',
        'course_code'            => optional($course)->course_code ?? 'N/A',
        'intake'                 => optional($intake)->batch ?? 'N/A',
        'intake_id'              => optional($intake)->intake_id,

        'payment_type'           => $type,
        'payment_type_display'   => $this->getPaymentTypeDisplay($type),

        // amount is ALWAYS numeric; Blade formats it
        'amount'                 => (float)$payment->amount,

        // franchise extras (null for non-franchise)
        'currency_from'          => $currencyFrom,
        'conversion_rate'        => $convRate,
        'lkr_amount'             => $lkrAmount,
        'franchise_fee_currency' => $fxCurrency,

        'installment_number'     => $payment->installment_number,
        'due_date'               => optional($payment->due_date)->format('Y-m-d'),
        'payment_date'           => optional($payment->updated_at)->format('Y-m-d'),
        'payment_method'         => $payment->payment_method,
        'remarks'                => $payment->remarks,
        'status'                 => $payment->status,

        'location'               => $registration->location ?? 'N/A',
        'registration_date'      => optional($registration->registration_date)->format('Y-m-d'),

        // breakdown
        'course_fee'             => $courseFee,
        'franchise_fee'          => $franchiseFee,
        'registration_fee'       => $registrationFee,

        'generated_at'           => optional($payment->created_at)->format('Y-m-d H:i:s'),
        'valid_until'            => optional($payment->created_at)->copy()->addDays(7)->format('Y-m-d'),
    ];
}

    /**
     * Save custom payments for a student and course.
     */
    public function saveCustomPayments(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|string',
                'course_id' => 'required|integer|exists:courses,course_id',
                'custom_payments' => 'required|array|min:1',
                'custom_payments.*.payment_name' => 'required|string|max:255',
                'custom_payments.*.amount' => 'required|numeric|min:0',
                'custom_payments.*.due_date' => 'required|date',
                'custom_payments.*.late_payment_fee' => 'nullable|numeric|min:0',
                'custom_payments.*.discount_amount' => 'nullable|numeric|min:0',
                'custom_payments.*.discount_reason' => 'nullable|string|max:255',
                'custom_payments.*.final_amount' => 'required|numeric|min:0',
                'custom_payments.*.notes' => 'nullable|string',
            ]);

            // Find student by student_id or NIC
            $student = Student::where('student_id', $request->student_id)
                ->orWhere('id_value', $request->student_id)
                ->first();

            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
            }

            // Verify student is registered for the course
            $registration = CourseRegistration::where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->first();

            if (!$registration) {
                return response()->json(['success' => false, 'message' => 'Student is not registered for this course.'], 404);
            }

            $savedPayments = [];

            DB::beginTransaction();
            try {
                foreach ($request->custom_payments as $paymentData) {
                    $customPayment = CustomPayment::create([
                        'student_id' => $student->student_id,
                        'course_id' => $request->course_id,
                        'payment_name' => $paymentData['payment_name'],
                        'amount' => $paymentData['amount'],
                        'due_date' => $paymentData['due_date'],
                        'late_payment_fee' => $paymentData['late_payment_fee'] ?? 0,
                        'discount_amount' => $paymentData['discount_amount'] ?? 0,
                        'discount_reason' => $paymentData['discount_reason'] ?? null,
                        'final_amount' => $paymentData['final_amount'],
                        'notes' => $paymentData['notes'] ?? null,
                        'status' => 'pending'
                    ]);

                    $savedPayments[] = $customPayment;
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Custom payments saved successfully.',
                    'payments' => $savedPayments
                ]);

            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving custom payments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while saving custom payments.',
            ], 500);
        }
    }

    /**
     * Get custom payments for a student and course.
     */
    public function getCustomPayments(Request $request)
    {
        try {
            $request->validate([
                'student_id' => 'required|string',
                'course_id' => 'required|integer|exists:courses,course_id',
            ]);

            // Find student by student_id or NIC
            $student = Student::where('student_id', $request->student_id)
                ->orWhere('id_value', $request->student_id)
                ->first();

            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
            }

            $customPayments = CustomPayment::where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->orderBy('due_date')
                ->get();

            return response()->json([
                'success' => true,
                'custom_payments' => $customPayments
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting custom payments: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while getting custom payments.',
            ], 500);
        }
    }
  /**
     * Show the standalone Payment Statement Download page.
     */
    public function showDownloadPage()
    {
        return view('payments.statement_download');
    }

    /**
     * Generate and download the Payment Statement as PDF.
     */
    public function downloadPaymentStatement(Request $request)
    {
        try {
            $request->validate([
                'student_nic' => 'required|string',
                'course_id' => 'required|integer|exists:courses,course_id',
            ]);

            $studentNic = trim((string) $request->input('student_nic'));
            $courseId = $request->input('course_id');

            $student = Student::query()
                ->where(function ($query) use ($studentNic) {
                    $query->where('id_value', $studentNic)
                        ->orWhere('student_id', $studentNic);
                })
                ->first();

            if (!$student) {
                return $this->statementDownloadError($request, 'Student not found with the provided NIC or Student ID.', Response::HTTP_NOT_FOUND);
            }

            $registration = CourseRegistration::where('student_id', $student->student_id)
                ->where('course_id', $courseId)
                ->with(['course', 'intake'])
                ->first();

            if (!$registration) {
                return $this->statementDownloadError($request, 'Student is not registered for this course.', Response::HTTP_NOT_FOUND);
            }

        // 🔹 Fetch actual payments
        $payments = \App\Models\PaymentDetail::where('student_id', $student->student_id)
            ->where('course_registration_id', $registration->id)
            ->orderBy('installment_type')
            ->orderBy('installment_number')
            ->orderBy('due_date')
            ->get();

        $totalAmount    = $payments->sum('total_fee');
        $totalPaid      = $payments->sum(fn($p) => max(0, $p->total_fee - $p->remaining_amount));
        $totalRemaining = $payments->sum('remaining_amount');

        // 🔹 Transform into flat payment history
        $paymentDetails = [];
        foreach ($payments as $payment) {
            $partials = [];
            if ($payment->partial_payments) {
                $partials = is_array($payment->partial_payments)
                    ? $payment->partial_payments
                    : json_decode($payment->partial_payments, true);
            }

            if (!empty($partials)) {
                foreach ($partials as $p) {
                    $paymentDetails[] = [
                        'description' => $this->getPaymentDescription($payment),
                        'method'      => $p['method'] ?? $payment->payment_method,
                        'receipt_no'  => $payment->transaction_id,
                        'date'        => $p['date'] ?? $payment->payment_date,
                        'amount'      => $p['amount'],
                        'remarks'     => $p['remarks'] ?? null,
                    ];
                }
            } else {
                $paymentDetails[] = [
                    'description' => $this->getPaymentDescription($payment),
                    'method'      => $payment->payment_method,
                    'receipt_no'  => $payment->transaction_id,
                    'date'        => $payment->payment_date,
                    'amount'      => $payment->total_fee - $payment->remaining_amount,
                    'remarks'     => $payment->remarks,
                ];
            }
        }

        // 🔹 Fetch Student Payment Plan + Installments
        $paymentPlan = \App\Models\StudentPaymentPlan::with('installments')
            ->where('student_id', $student->student_id)
            ->where('course_id', $courseId)
            ->first();

        // 🔹 Fetch Course Payment Plan (master with foreign currency)
        $coursePlan = \App\Models\PaymentPlan::where('course_id', $courseId)
            ->first();

        $courseInstallments = [];
        if ($coursePlan && $coursePlan->installments) {
            $courseInstallments = is_array($coursePlan->installments)
                ? $coursePlan->installments
                : json_decode($coursePlan->installments, true);
        }

            $data = [
            'student' => [
                'name' => $student->full_name,
                'id'   => $student->student_id,
                'nic'  => $student->id_value,
            ],
            'course' => [
                'name'              => optional($registration->course)->course_name,
                'code'              => optional($registration->course)->course_name,
                'intake'            => optional($registration->intake)->batch,
                'registration_date' => optional($registration->registration_date)?->format('Y-m-d'),
            ],
            'payments' => $paymentDetails,
            'totals' => [
                'total_amount'    => $totalAmount,
                'total_paid'      => $totalPaid,
                'total_remaining' => $totalRemaining,
            ],
            'generated_date'     => now()->format('Y-m-d H:i:s'),
            'paymentPlan'        => $paymentPlan,
            'coursePlan'         => $coursePlan,
            'courseInstallments' => $courseInstallments,
            'cspNonce'           => $request->attributes->get('cspNonce') ?? base64_encode(random_bytes(16)),
        ];

            $courseLabel = preg_replace('/[^A-Za-z0-9_\-]+/', '_', optional($registration->course)->course_name ?? 'Course');
            $filename = "Payment_Statement_{$student->student_id}_{$courseLabel}.pdf";

            $pdf = Pdf::loadView('payments.payment_statement', $data)
                ->setPaper('A4', 'portrait');

            return $pdf->download($filename);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Payment Statement PDF Error: ' . $e->getMessage());
            return $this->statementDownloadError($request, 'Error generating statement. Please try again.', Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function statementDownloadError(Request $request, string $message, int $status)
    {
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], $status);
        }

        return back()->with('error', $message)->withInput();
    }


/**
 * Helper: Get Payment Description
 */
private function getPaymentDescription($payment)
{
    if ($payment->installment_type === 'course_fee') {
        return "Course Fee - Installment {$payment->installment_number}";
    } elseif ($payment->installment_type === 'franchise_fee') {
        return "Franchise Fee - Installment {$payment->installment_number}";
    } elseif ($payment->installment_type === 'registration_fee') {
        return "Registration Fee";
    }
    return ucfirst(str_replace('_', ' ', $payment->installment_type ?? 'payment'));
}

    private function formatSriLankaDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        return \Carbon\Carbon::parse($value)
            ->timezone(config('app.timezone', 'Asia/Colombo'))
            ->format('Y-m-d');
    }

}
