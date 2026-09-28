<?php

namespace App\Http\Controllers;

use App\Models\CourseRegistration;
use App\Models\PaymentDetail;
use App\Models\PaymentPlan;
use App\Models\Student;
use App\Models\StudentPaymentPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class LatePaymentController extends Controller
{
    public function index()
    {
        return view('payments.late_payment');
    }

    public function getPaymentPlan(Request $request)
    {
        try {
            $request->validate([
                'student_nic' => 'required|string',
                'course_id' => 'required|integer|exists:courses,course_id',
            ]);

            $student = $this->findStudent($request->student_nic);

            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found with the provided NIC or Student ID.'], Response::HTTP_NOT_FOUND);
            }

            $registration = CourseRegistration::where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->with(['student', 'course', 'intake'])
                ->first();

            if (!$registration) {
                return response()->json(['success' => false, 'message' => 'Student is not registered for this course.'], Response::HTTP_NOT_FOUND);
            }

            $studentPaymentPlan = StudentPaymentPlan::where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->with(['installments'])
                ->first();

            $paymentPlan = null;
            $installments = collect();

            if ($studentPaymentPlan) {
                $paymentPlan = $studentPaymentPlan;
                $installments = $studentPaymentPlan->installments
                    ->sortBy('installment_number')
                    ->values();
            } else {
                $generalPaymentPlan = PaymentPlan::where('course_id', $registration->course_id)
                    ->where('intake_id', $registration->intake_id)
                    ->first();

                if ($generalPaymentPlan && $generalPaymentPlan->installments) {
                    $installmentsData = $generalPaymentPlan->installments;
                    if (is_string($installmentsData)) {
                        $installmentsData = json_decode($installmentsData, true);
                    }

                    if (is_array($installmentsData)) {
                        $installments = collect($installmentsData)->map(function ($installment, $index) {
                            return (object) [
                                'installment_number' => $installment['installment_number'] ?? ($index + 1),
                                'due_date' => $installment['due_date'] ?? now()->addDays(30 * ($index + 1))->toDateString(),
                                'amount' => $installment['local_amount'] ?? 0,
                                'final_amount' => $installment['final_amount'] ?? ($installment['local_amount'] ?? 0),
                                'status' => 'pending',
                                'approved_late_fee' => 0,
                                'approval_note' => null,
                            ];
                        });
                    }
                }
            }

            if (!$paymentPlan && $installments->isEmpty()) {
                return response()->json(['success' => false, 'message' => 'No payment plan found for this student and course.'], Response::HTTP_NOT_FOUND);
            }

            $courseFee = $paymentPlan
                ? (float) ($paymentPlan->final_amount ?? $paymentPlan->total_amount ?? 0)
                : (float) $installments->sum(function ($installment) {
                    return $installment->final_amount ?? $installment->amount ?? 0;
                });

            $studentData = [
                'student_id' => $registration->student->student_id,
                'student_name' => $registration->student->full_name,
                'student_nic' => $registration->student->id_value,
                'course_id' => $request->course_id,
                'course_name' => $registration->course->course_name,
                'intake_name' => $registration->intake->batch ?? 'N/A',
                'course_fee' => $courseFee,
                'total_amount' => $courseFee,
                'registration_date' => optional($registration->registration_date)->format('Y-m-d'),
                'status' => $registration->status,
            ];

            $processedInstallments = $installments->map(function ($installment) {
                $dueDate = \Carbon\Carbon::parse($installment->due_date)->startOfDay();
                $isLate = $dueDate->lt(now()->startOfDay()) && $installment->status !== 'paid';
                $daysLate = $isLate ? $this->wholeDaysLate($dueDate, now()) : 0;
                $finalAmount = $installment->final_amount ?? $installment->amount;
                $calculatedLateFee = $isLate ? $this->calculateLateFee($finalAmount, $daysLate) : 0;
                $approvedReduction = $installment->approved_late_fee ?? 0;
                $effectiveLateFee = max(0, $calculatedLateFee - $approvedReduction);

                return [
                    'installment_number' => $installment->installment_number,
                    'due_date' => $installment->due_date,
                    'amount' => $finalAmount,
                    'status' => $installment->status,
                    'is_late' => $isLate,
                    'days_late' => $daysLate,
                    'late_fee' => $calculatedLateFee,
                    'approved_late_fee' => $approvedReduction,
                    'effective_late_fee' => $effectiveLateFee,
                    'approval_note' => $installment->approval_note ?? null,
                    'total_due' => $finalAmount + $effectiveLateFee,
                ];
            });

            return response()->json([
                'success' => true,
                'student' => $studentData,
                'payment_plan' => [
                    'plan_id' => $paymentPlan ? $paymentPlan->id : null,
                    'plan_type' => $paymentPlan ? $paymentPlan->payment_plan_type : 'general',
                    'total_amount' => $paymentPlan ? $paymentPlan->total_amount : $courseFee,
                    'final_amount' => $paymentPlan ? $paymentPlan->final_amount : $courseFee,
                    'installments' => $processedInstallments,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPaidPaymentDetails(Request $request)
    {
        try {
            $request->validate([
                'student_nic' => 'required|string',
                'course_id' => 'required|integer|exists:courses,course_id',
            ]);

            $student = $this->findStudent($request->student_nic);

            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found with the provided NIC or Student ID.'], Response::HTTP_NOT_FOUND);
            }

            $registration = CourseRegistration::where('student_id', $student->student_id)
                ->where('course_id', $request->course_id)
                ->first();

            if (!$registration) {
                return response()->json(['success' => false, 'message' => 'Student is not registered for this course.'], Response::HTTP_NOT_FOUND);
            }

            $paidPayments = PaymentDetail::where('student_id', $student->student_id)
                ->where('course_registration_id', $registration->id)
                ->where('status', 'paid')
                ->whereNull('misc_category')
                ->where(function ($query) {
                    $query->whereNull('installment_type')
                        ->orWhere('installment_type', 'course_fee');
                })
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($payment) {
                    $effectiveDateRef = $payment->payment_effective_date
                        ?? $payment->payment_date
                        ?? $payment->created_at;

                    return [
                        'payment_id' => $payment->id,
                        'payment_date' => \Carbon\Carbon::parse($effectiveDateRef)->format('Y-m-d'),
                        'amount' => $payment->amount,
                        'payment_method' => $payment->payment_method,
                        'receipt_no' => $payment->transaction_id,
                        'installment_number' => $payment->installment_number,
                        'due_date' => $payment->due_date ? $payment->due_date->format('Y-m-d') : null,
                        'paid_slip_path' => $payment->paid_slip_path,
                        'remarks' => $payment->remarks,
                        'days_late' => $this->calculateDaysLate($payment->due_date, $effectiveDateRef),
                        'late_fee_paid' => $this->calculateLateFeePaid($payment->amount, $payment->due_date, $effectiveDateRef),
                    ];
                });

            return response()->json([
                'success' => true,
                'paid_payments' => $paidPayments,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getStudentCourses(Request $request)
    {
        try {
            $request->validate([
                'student_nic' => 'required|string',
            ]);

            $student = $this->findStudent($request->student_nic);

            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found with the provided NIC or Student ID.'], Response::HTTP_NOT_FOUND);
            }

            $courses = CourseRegistration::where('student_id', $student->student_id)
                ->with(['course'])
                ->get()
                ->filter(fn ($registration) => $registration->course)
                ->map(function ($registration) {
                    return [
                        'course_id' => $registration->course->course_id,
                        'course_name' => $registration->course->course_name,
                        'registration_date' => optional($registration->registration_date)->format('Y-m-d') ?? 'N/A',
                        'status' => $registration->status,
                    ];
                })
                ->values();

            return response()->json([
                'success' => true,
                'courses' => $courses,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function findStudent($input): ?Student
    {
        $value = trim((string) $input);
        if ($value === '') {
            return null;
        }

        return Student::query()
            ->where(function ($query) use ($value) {
                $query->where('id_value', $value)
                    ->orWhere('student_id', $value);
            })
            ->first();
    }

    private function wholeDaysLate($from, $to): int
    {
        if (!$from || !$to) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($from)->startOfDay();
        $end = \Carbon\Carbon::parse($to)->startOfDay();

        if ($end->lte($start)) {
            return 0;
        }

        return (int) round($start->diffInDays($end));
    }

    private function calculateLateFee($amount, $daysLate)
    {
        $monthlyRate = 0.05;
        $dailyRate = $monthlyRate / 30;
        $lateFee = $amount * $dailyRate * $daysLate;
        $maxLateFee = $amount * 0.25;

        return round(min($lateFee, $maxLateFee), 2);
    }

    private function calculateDaysLate($dueDate, $paymentDate)
    {
        return $this->wholeDaysLate($dueDate, $paymentDate);
    }

    private function calculateLateFeePaid($amount, $dueDate, $paymentDate)
    {
        $daysLate = $this->wholeDaysLate($dueDate, $paymentDate);
        if ($daysLate <= 0) {
            return 0;
        }

        return $this->calculateLateFee($amount, $daysLate);
    }
}
