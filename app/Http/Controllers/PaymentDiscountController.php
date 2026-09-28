<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Intake;
use App\Models\CourseRegistration;
use App\Models\PaymentPlan;
use App\Models\Discount;
use App\Models\PaymentInstallment;
use App\Models\Student;
use App\Models\SltLoanReceivableRecord;
use App\Models\StudentPaymentPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class PaymentDiscountController extends Controller
{
    // Show the payment discount page
    public function showPage()
    {
        return view('payments.payment_discount');
    }

    // Fetch courses by location (AJAX)
    public function getCoursesByLocation(Request $request)
    {
        $location = $request->input('location');

        if (!$location) {
            return response()->json([
                'success' => false,
                'courses' => [],
                'message' => 'Location is required.'
            ]);
        }

        $courses = Course::where('location', $location)->get(['course_id', 'course_name', 'local_fee', 'registration_fee']);

        return response()->json([
            'success' => true,
            'courses' => $courses,
            'message' => $courses->isEmpty() ? 'No courses found.' : 'Courses loaded successfully.'
        ]);
    }

    // Fetch intakes by course (AJAX)
    public function getIntakesByCourse(Request $request)
    {
        $courseId = $request->input('course_id');
        $intakes = Intake::where('course_id', $courseId)->get(['intake_id', 'batch']);
        return response()->json(['intakes' => $intakes]);
    }

    // Fetch payment plan for a course/intake (AJAX)
    public function getPaymentPlan(Request $request)
    {
        $courseId = $request->input('course_id');
        $intakeId = $request->input('intake_id');
        // Example: fetch payment plan for the course/intake
        $plans = PaymentPlan::where('course_id', $courseId)
            ->where('intake_id', $intakeId)
            ->orderBy('installment_no')
            ->get(['installment_no', 'type', 'amount', 'due_date']);
        return response()->json(['payment_plan' => $plans]);
    }

    // Save SLT loan data (AJAX)
    public function saveSltLoan(Request $request)
    {
        try {
            $validated = $request->validate([
                'student_identifier' => 'required|string',
                'course_id' => 'required|integer|exists:courses,course_id',
                'slt_loan_amount' => 'required|numeric|min:0',
                'slt_loan_years' => 'required|integer|min:1|max:50',
                'slt_loan_start_installment' => 'nullable|integer|min:1',
                'payment_effective_date' => 'required|date',
            ]);

            $student = Student::where('student_id', $validated['student_identifier'])
                ->orWhere('id_value', $validated['student_identifier'])
                ->first();

            if (!$student) {
                return response()->json(['success' => false, 'message' => 'Student not found.'], 404);
            }

            $registration = CourseRegistration::where('student_id', $student->student_id)
                ->where('course_id', $validated['course_id'])
                ->first();

            if (!$registration) {
                return response()->json(['success' => false, 'message' => 'Student is not registered for the selected course.'], 404);
            }

            $plan = StudentPaymentPlan::where('student_id', $student->student_id)
                ->where('course_id', $validated['course_id'])
                ->where('status', '!=', 'archived')
                ->orderByDesc('id')
                ->first();

            if (!$plan) {
                return response()->json(['success' => false, 'message' => 'Create the student payment plan before updating SLT loan receivables.'], 404);
            }

            $result = DB::transaction(function () use ($plan, $validated) {
                return $this->updateSltLoanReceivable(
                    $plan,
                    (float) $validated['slt_loan_amount'],
                    (int) $validated['slt_loan_years'],
                    (int) ($validated['slt_loan_start_installment'] ?? 1),
                    $validated['payment_effective_date']
                );
            });

            return response()->json([
                'success' => true,
                'message' => 'SLT loan receivable updated successfully.',
                'payment_plan' => $result,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['success' => false, 'message' => collect($e->errors())->flatten()->first()], 422);
        } catch (\Throwable $e) {
            Log::error('Error saving SLT loan receivable: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Error saving SLT loan receivable: ' . $e->getMessage()], 500);
        }
    }

    private function updateSltLoanReceivable(StudentPaymentPlan $plan, float $loanAmount, int $loanYears, int $startInstallment, ?string $effectiveDate = null): array
    {
        $installments = PaymentInstallment::where('payment_plan_id', $plan->id)
            ->orderBy('installment_number')
            ->get();

        if ($installments->isEmpty()) {
            throw new \RuntimeException('No installments found for this student payment plan.');
        }

        $discountedBases = [];
        foreach ($installments as $installment) {
            $base = (float) ($installment->base_amount ?? $installment->amount ?? 0);
            $discount = (float) ($installment->discount_amount ?? 0);
            $registrationDiscount = (float) ($installment->registration_fee_discount_applied ?? 0);
            $discountedBases[] = round(max(0, $base - $discount - $registrationDiscount), 2);
        }

        $planLoanTotal = round(max(0, $loanAmount), 2);
        $loanAmount = min($planLoanTotal, round(array_sum($discountedBases), 2));
        $allocations = $this->allocateSltLoanByStartInstallment($installments->values()->all(), $discountedBases, $loanAmount, $startInstallment);
        $appliedLoan = round(array_sum($allocations), 2);
        $finalTotal = 0.0;

        foreach ($installments as $index => $installment) {
            $finalAmount = round(max(0, $discountedBases[$index] - ($allocations[$index] ?? 0)), 2);
            $finalTotal += $finalAmount;

            $installment->update([
                'slt_loan_amount' => round($allocations[$index] ?? 0, 2),
                'amount' => $finalAmount,
                'final_amount' => $finalAmount,
            ]);
        }

        $plan->update([
            'slt_loan_applied' => $appliedLoan > 0 ? 'yes' : 'no',
            'slt_loan_amount' => $appliedLoan,
            'slt_loan_start_installment' => $appliedLoan > 0 ? $startInstallment : null,
            'slt_loan_years' => $appliedLoan > 0 ? $loanYears : null,
            'slt_receivable_effective_date' => $effectiveDate,
            'final_amount' => round($finalTotal, 2),
        ]);

        $installmentCount = $loanYears > 0 ? $loanYears * 12 : 0;
        $monthlyReceivable = $installmentCount > 0 ? round($planLoanTotal / $installmentCount, 2) : 0;

        if ($installmentCount > 0 && $effectiveDate) {
            try {
                $existingRecords = SltLoanReceivableRecord::where('student_payment_plan_id', $plan->id)->count();
                $nextInstallmentNumber = $existingRecords + 1;

                if ($nextInstallmentNumber <= $installmentCount) {
                    SltLoanReceivableRecord::create([
                        'student_payment_plan_id' => $plan->id,
                        'student_id' => $plan->student_id,
                        'course_id' => $plan->course_id,
                        'loan_installment_number' => $nextInstallmentNumber,
                        'total_loan_amount' => $planLoanTotal,
                        'loan_taken_years' => $loanYears,
                        'loan_installment_count' => $installmentCount,
                        'apply_from_installment' => $startInstallment,
                        'monthly_receivable_amount' => $monthlyReceivable,
                        'payment_effective_date' => $effectiveDate,
                    ]);
                }
            } catch (\Throwable $e) {
                \Log::error("Failed to count or create SltLoanReceivableRecord in PaymentDiscountController: " . $e->getMessage());
            }
        }

        return [
            'id' => $plan->id,
            'slt_loan_amount' => $appliedLoan,
            'slt_loan_years' => $plan->slt_loan_years,
            'loan_installment_count' => $installmentCount,
            'slt_receivable_effective_date' => $plan->slt_receivable_effective_date?->format('Y-m-d'),
            'installment_receivable' => $installmentCount > 0 ? round($planLoanTotal / $installmentCount, 2) : 0,
            'final_amount' => round($finalTotal, 2),
        ];
    }

    private function allocateSltLoanByStartInstallment(array $rows, array $discountedBases, float $loanAmount, int $startInstallment): array
    {
        $allocations = array_fill(0, count($rows), 0.0);
        $remainingLoan = round(max(0, $loanAmount), 2);

        foreach ($rows as $index => $row) {
            $installmentNumber = (int) ($row->installment_number ?? ($index + 1));

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

    private function discountRules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:percentage,amount',
            'discount_category' => 'required|in:local_course_fee,registration_fee',
            'value' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    if ($this->requestTypeIsPercentage() && (float) $value > 100) {
                        $fail('Percentage cannot be greater than 100.');
                    }
                },
            ],
            'description' => 'nullable|string',
        ];
    }

    private function requestTypeIsPercentage(): bool
    {
        return strcasecmp((string) request('type'), 'percentage') === 0;
    }

    private function perPage(Request $request): int
    {
        $perPage = (int) $request->input('per_page', 10);

        return in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
    }

    // Save discount data (AJAX)
    public function saveDiscount(Request $request)
    {
        try {
            $validated = $request->validate($this->discountRules());

            $discount = Discount::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'discount_category' => $validated['discount_category'],
                'value' => $validated['value'],
                'status' => 'active',
                'description' => $validated['description'] ?? null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Discount saved successfully.',
                'discount' => $discount,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error saving discount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error saving discount. Please try again.',
            ], 500);
        }
    }

    // Get all discounts (AJAX)
    public function getDiscounts()
    {
        try {
            $discounts = Discount::where('status', 'active')
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'discounts' => $discounts
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching discounts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching discounts: ' . $e->getMessage()
            ], 500);
        }
    }

    // Get discounts by category (AJAX)
    public function getDiscountsByCategory(Request $request)
    {
        try {
            $validated = $request->validate([
                'category' => 'required|in:local_course_fee,registration_fee',
                'page' => 'nullable|integer|min:1',
                'per_page' => 'nullable|integer|in:10,25,50,100',
            ]);

            $perPage = $this->perPage($request);
            $page = max(1, (int) ($validated['page'] ?? 1));

            $paginator = Discount::where('status', 'active')
                ->where('discount_category', $validated['category'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);

            return response()->json([
                'success' => true,
                'discounts' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error fetching discounts by category: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching discounts. Please try again.',
            ], 500);
        }
    }

    public function updateDiscount(Request $request)
    {
        try {
            $validated = $request->validate(array_merge(
                ['id' => 'required|exists:discounts,id'],
                $this->discountRules()
            ));

            $discount = Discount::findOrFail($validated['id']);
            $discount->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'discount_category' => $validated['discount_category'],
                'value' => $validated['value'],
                'description' => $validated['description'] ?? $discount->description,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Discount updated successfully.',
                'discount' => $discount,
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error updating discount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error updating discount. Please try again.',
            ], 500);
        }
    }

    public function deleteDiscount(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:discounts,id',
            ]);

            $discount = Discount::findOrFail($request->id);
            $discount->update(['status' => 'inactive']);

            return response()->json([
                'success' => true,
                'message' => 'Discount deleted successfully.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error deleting discount: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error deleting discount. Please try again.',
            ], 500);
        }
    }
}
