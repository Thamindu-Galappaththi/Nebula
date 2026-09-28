<?php

namespace App\Http\Controllers;

use App\Models\CourseRegistration;
use App\Models\PaymentInstallment;
use App\Models\PaymentPlan;
use App\Models\SemesterRegistration;
use App\Models\Student;
use App\Models\StudentPaymentPlan;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RepeatStudentPaymentController extends Controller
{
    public function index()
    {
        return view('payments.repeat_payment_plan');
    }

    public function searchStudent(Request $request)
    {
        try {
            $request->validate([
                'student_nic' => 'required|string',
            ]);

            $student = $this->findStudent($request->input('student_nic'));
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found with the provided NIC or Student ID.',
                ], Response::HTTP_NOT_FOUND);
            }

            $courses = collect();

            SemesterRegistration::where('student_id', $student->student_id)
                ->where('status', 'holding')
                ->with('course')
                ->orderByDesc('registration_date')
                ->get()
                ->each(function ($registration) use ($courses) {
                    if (!$registration->course) {
                        return;
                    }
                    if ($courses->contains('course_id', $registration->course_id)) {
                        return;
                    }
                    $courses->push([
                        'course_id' => $registration->course_id,
                        'course_name' => $registration->course->course_name,
                    ]);
                });

            CourseRegistration::where('student_id', $student->student_id)
                ->with('course')
                ->orderByDesc('id')
                ->get()
                ->each(function ($registration) use ($courses) {
                    if (!$registration->course) {
                        return;
                    }
                    if ($courses->contains('course_id', $registration->course_id)) {
                        return;
                    }
                    $courses->push([
                        'course_id' => $registration->course_id,
                        'course_name' => $registration->course->course_name,
                    ]);
                });

            return response()->json([
                'success' => true,
                'student' => [
                    'student_id' => $student->student_id,
                    'full_name' => $student->full_name,
                    'id_value' => $student->id_value,
                ],
                'courses' => $courses->values(),
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to search for this student.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getArchivedPaymentPlan($student_id, $course_id)
    {
        try {
            $student = Student::find($student_id);
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            $archivedPlan = StudentPaymentPlan::where('student_id', $student_id)
                ->where('course_id', $course_id)
                ->where('status', 'archived')
                ->latest('updated_at')
                ->first();

            $archivedInstallments = $archivedPlan
                ? PaymentInstallment::where('payment_plan_id', $archivedPlan->id)
                    ->orderBy('installment_number')
                    ->get()
                    ->map(fn ($installment) => $this->formatInstallment($installment))
                    ->values()
                : collect();

            $currentReg = SemesterRegistration::where('student_id', $student_id)
                ->where('course_id', $course_id)
                ->latest('registration_date')
                ->first();

            $intakeId = $currentReg->intake_id ?? null;
            $currentPlan = null;
            if ($intakeId) {
                $currentPlan = PaymentPlan::where('intake_id', $intakeId)
                    ->where('course_id', $course_id)
                    ->latest('updated_at')
                    ->first();
            }

            if (!$currentPlan) {
                $currentPlan = PaymentPlan::where('course_id', $course_id)
                    ->latest('updated_at')
                    ->first();
            }

            $installments = collect();
            if ($currentPlan && $currentPlan->installments) {
                $raw = $currentPlan->installments;
                if (is_string($raw)) {
                    $decoded = json_decode($raw, true);
                    if (is_string($decoded)) {
                        $decoded = json_decode($decoded, true);
                    }
                    $raw = $decoded ?? [];
                }

                $installments = collect($raw)->map(function ($item) use ($currentPlan) {
                    return [
                        'installment_number' => $item['installment_number'] ?? null,
                        'due_date' => $this->formatDate($item['due_date'] ?? null),
                        'base_amount' => $item['local_amount'] ?? 0,
                        'amount' => ($item['local_amount'] ?? 0) + ($item['international_amount'] ?? 0),
                        'international_amount' => $item['international_amount'] ?? 0,
                        'currency' => $currentPlan->international_currency ?? 'USD',
                        'status' => 'active',
                    ];
                })->values();
            }

            return response()->json([
                'success' => true,
                'archived_plan' => $archivedPlan,
                'archived_installments' => $archivedInstallments,
                'current_plan' => [
                    'plan' => $currentPlan,
                    'installments' => $installments,
                    'currency' => $currentPlan->international_currency ?? 'USD',
                ],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching payment plan.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function saveNewPaymentPlan(Request $request)
    {
        try {
            $validated = $request->validate([
                'student_id' => 'required|integer|exists:students,student_id',
                'course_id' => 'required|integer|exists:courses,course_id',
                'installments' => 'required|array|min:1',
                'installments.*.due_date' => 'required|date',
                'installments.*.local_amount' => 'nullable|numeric|min:0',
                'installments.*.international_amount' => 'nullable|numeric|min:0',
                'installments.*.currency' => 'nullable|string|max:10',
            ]);

            DB::beginTransaction();

            StudentPaymentPlan::where('student_id', $validated['student_id'])
                ->where('course_id', $validated['course_id'])
                ->where('status', 'active')
                ->update(['status' => 'archived']);

            $total = collect($validated['installments'])->sum(
                fn ($item) => ($item['local_amount'] ?? 0) + ($item['international_amount'] ?? 0)
            );

            $plan = StudentPaymentPlan::create([
                'student_id' => $validated['student_id'],
                'course_id' => $validated['course_id'],
                'payment_plan_type' => 'installments',
                'status' => 'active',
                'total_amount' => $total,
                'final_amount' => $total,
            ]);

            foreach ($validated['installments'] as $index => $item) {
                $local = (float) ($item['local_amount'] ?? 0);
                $intl = (float) ($item['international_amount'] ?? 0);
                $currency = $item['currency'] ?? null;

                $installmentType = 'local';
                if ($intl > 0 && $local > 0) {
                    $installmentType = 'mixed';
                } elseif ($intl > 0) {
                    $installmentType = 'international';
                }

                PaymentInstallment::create([
                    'payment_plan_id' => $plan->id,
                    'installment_number' => $index + 1,
                    'due_date' => $item['due_date'],
                    'amount' => $local,
                    'base_amount' => $local,
                    'final_amount' => $local + $intl,
                    'international_amount' => $intl > 0 ? $intl : null,
                    'international_currency' => $intl > 0 ? ($currency ?: 'USD') : null,
                    'status' => 'pending',
                    'installment_type' => $installmentType,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'New payment plan created successfully.',
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->validator->errors()->first(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error saving payment plan.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getCreatedPaymentPlans($student_id, $course_id)
    {
        try {
            $plans = StudentPaymentPlan::where('student_id', $student_id)
                ->where('course_id', $course_id)
                ->orderByDesc('created_at')
                ->get();

            if ($plans->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'installments' => [],
                    'message' => 'No created plans found.',
                ]);
            }

            $installments = PaymentInstallment::whereIn('payment_plan_id', $plans->pluck('id'))
                ->orderBy('due_date')
                ->get()
                ->map(fn ($installment) => $this->formatInstallment($installment))
                ->values();

            return response()->json([
                'success' => true,
                'installments' => $installments,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching created payment plans.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
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

    private function formatInstallment(PaymentInstallment $installment): array
    {
        return [
            'installment_number' => $installment->installment_number,
            'due_date' => $this->formatDate($installment->due_date),
            'base_amount' => $installment->base_amount ?? $installment->amount ?? 0,
            'amount' => $installment->final_amount ?? $installment->base_amount ?? $installment->amount ?? 0,
            'international_amount' => $installment->international_amount ?? 0,
            'international_currency' => $installment->international_currency ?? 'LKR',
            'installment_type' => $installment->installment_type ?? '-',
            'status' => $installment->status ?? 'pending',
        ];
    }

    private function formatDate($value): ?string
    {
        if (!$value) {
            return null;
        }

        return \Carbon\Carbon::parse($value)
            ->timezone(config('app.timezone', 'Asia/Colombo'))
            ->format('Y-m-d');
    }
}
