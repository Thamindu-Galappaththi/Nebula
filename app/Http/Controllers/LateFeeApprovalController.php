<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\CourseRegistration;
use App\Models\StudentPaymentPlan;
use App\Models\PaymentInstallment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LateFeeApprovalController extends Controller
{
    public function index(Request $request)
    {
        if ($request->filled('student_nic') && $request->filled('course_id')) {
            return redirect()->route('latefee.approval.page', [
                'studentNic' => $request->student_nic,
                'courseId' => $request->course_id,
            ]);
        }

        return view('approvals.late_fee_approval');
    }

    public function approvalPage($studentNic, $courseId)
    {
        $student = Student::where('id_value', $studentNic)->first();
        if (!$student) {
            return redirect()->route('latefee.approval.index')->with('error', 'Student not found.');
        }

        $courses = $this->coursesForStudent($student);
        $registration = CourseRegistration::where('student_id', $student->student_id)
            ->where('course_id', $courseId)
            ->first();

        if (!$registration) {
            return view('approvals.late_fee_approval', [
                'student' => $student,
                'courses' => $courses,
                'studentNic' => $studentNic,
                'courseId' => $courseId,
                'installments' => collect(),
                'error' => 'This student is not registered for the selected course.',
            ]);
        }

        $plan = StudentPaymentPlan::where('student_id', $student->student_id)
            ->where('course_id', $courseId)
            ->with('installments')
            ->first();

        if (!$plan) {
            return view('approvals.late_fee_approval', [
                'student' => $student,
                'courses' => $courses,
                'studentNic' => $studentNic,
                'courseId' => $courseId,
                'installments' => collect(),
                'error' => 'No payment plan found for this course.',
            ]);
        }

        $installments = $plan->installments()->orderBy('due_date')->get()->map(function ($inst) {
            return $this->hydrateInstallmentLateFee($inst);
        });

        return view('approvals.late_fee_approval', [
            'student' => $student,
            'courses' => $courses,
            'installments' => $installments,
            'studentNic' => $studentNic,
            'courseId' => $courseId,
        ]);
    }

    public function getApprovalPaymentPlan(Request $request)
    {
        $request->validate([
            'student_nic' => 'required|string',
            'course_id' => 'required|integer|exists:courses,course_id',
        ]);

        $student = Student::where('id_value', $request->student_nic)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.'], Response::HTTP_NOT_FOUND);
        }

        $registration = CourseRegistration::where('student_id', $student->student_id)
            ->where('course_id', $request->course_id)
            ->first();

        if (!$registration) {
            return response()->json(['success' => false, 'message' => 'Not registered for this course.'], Response::HTTP_NOT_FOUND);
        }

        $studentPaymentPlan = StudentPaymentPlan::where('student_id', $student->student_id)
            ->where('course_id', $request->course_id)
            ->with('installments')
            ->first();

        if (!$studentPaymentPlan) {
            return response()->json(['success' => false, 'message' => 'No payment plan found.'], Response::HTTP_NOT_FOUND);
        }

        $installments = $studentPaymentPlan->installments->map(function ($inst) {
            $inst = $this->hydrateInstallmentLateFee($inst);

            return [
                'id' => $inst->id,
                'installment_number' => $inst->installment_number,
                'due_date' => $inst->due_date,
                'amount' => $inst->final_amount ?? $inst->amount ?? 0,
                'status' => $inst->status,
                'is_late' => (bool) $inst->is_late,
                'days_late' => $inst->days_late,
                'calculated_late_fee' => $inst->calculated_late_fee,
                'approved_late_fee' => $inst->approved_late_fee,
                'approval_note' => $inst->approval_note,
            ];
        });

        return response()->json([
            'success' => true,
            'student' => [
                'student_id' => $student->student_id,
                'name' => $student->name_with_initials ?: $student->full_name,
            ],
            'course_id' => $request->course_id,
            'installments' => $installments,
        ]);
    }

    public function approveLateFeePerInstallment(Request $request, $installmentId)
    {
        $request->validate([
            'approved_late_fee' => 'required|numeric|min:0',
            'approval_note' => 'nullable|string|max:1000',
        ]);

        $inst = PaymentInstallment::with('paymentPlan')->findOrFail($installmentId);
        $inst = $this->hydrateInstallmentLateFee($inst);

        if ($inst->status === 'paid') {
            return back()->with('error', 'Late fee cannot be approved for a paid installment.');
        }

        $dueDate = \Carbon\Carbon::parse($inst->due_date);
        if ($dueDate->isFuture()) {
            return back()->with('error', 'You can only approve late fees after the due date has passed.');
        }

        if ((float) $request->approved_late_fee > (float) $inst->calculated_late_fee) {
            return back()->with('error', 'Approved late fee cannot exceed the calculated late fee.');
        }

        $history = is_array($inst->approval_history) ? $inst->approval_history : [];
        $history[] = [
            'calculated_late_fee' => $inst->calculated_late_fee,
            'approved_late_fee' => (float) $request->approved_late_fee,
            'approval_note' => $request->approval_note,
            'approved_by' => auth()->user()->name ?? 'System',
            'approved_at' => now()->toDateTimeString(),
        ];

        $inst->approved_late_fee = $request->approved_late_fee;
        $inst->approval_note = $request->approval_note;
        $inst->approved_by = auth()->id();
        $inst->approval_history = $history;
        $this->persistInstallmentApproval($inst);

        $plan = $inst->paymentPlan;
        if ($plan) {
            $registrationId = CourseRegistration::where('student_id', $plan->student_id)
                ->where('course_id', $plan->course_id)
                ->value('id');

            $paymentDetail = \App\Models\PaymentDetail::where('student_id', $plan->student_id)
                ->where('course_registration_id', $registrationId)
                ->where('installment_number', $inst->installment_number)
                ->where('status', 'pending')
                ->first();

            if ($paymentDetail) {
                $baseAmt = $inst->final_amount ?? $inst->amount ?? 0;
                $lateFee = $inst->calculated_late_fee;
                $approved = $inst->approved_late_fee ?? 0;

                $paymentDetail->late_fee = $lateFee;
                $paymentDetail->approved_late_fee = $approved;
                $paymentDetail->total_fee = $baseAmt + $lateFee - $approved;
                $paymentDetail->save();
            }
        }

        return back()->with('success', 'Late fee approved for installment.');
    }

    public function approveLateFeeGlobal(Request $request, $studentNic, $courseId)
    {
        $request->validate([
            'reduction_amount' => 'required|numeric|gt:0',
            'approval_note' => 'nullable|string|max:1000',
        ]);

        $student = Student::where('id_value', $studentNic)->first();
        if (!$student) {
            return redirect()->route('latefee.approval.index')->with('error', 'Student not found.');
        }

        $installments = PaymentInstallment::whereHas('paymentPlan', function ($q) use ($student, $courseId) {
            $q->where('student_id', $student->student_id)
                ->where('course_id', $courseId);
        })
            ->orderBy('due_date', 'asc')
            ->get();

        $remaining = (float) $request->reduction_amount;

        foreach ($installments as $inst) {
            $inst = $this->hydrateInstallmentLateFee($inst);
            $calcFee = (float) $inst->calculated_late_fee;

            if ($inst->status === 'paid' || $calcFee <= 0 || $remaining <= 0) {
                continue;
            }

            if ($remaining >= $calcFee) {
                $inst->approved_late_fee = $calcFee;
                $remaining -= $calcFee;
            } else {
                $inst->approved_late_fee = $remaining;
                $remaining = 0;
            }

            $history = is_array($inst->approval_history) ? $inst->approval_history : [];
            $history[] = [
                'calculated_late_fee' => $inst->calculated_late_fee,
                'approved_late_fee' => (float) $inst->approved_late_fee,
                'approval_note' => $request->approval_note,
                'approved_by' => auth()->user()->name ?? 'System',
                'approved_at' => now()->toDateTimeString(),
            ];

            $inst->approval_note = $request->approval_note;
            $inst->approved_by = auth()->id();
            $inst->approval_history = $history;
            $this->persistInstallmentApproval($inst);
        }

        return back()->with('success', 'Global late fee approved successfully.');
    }

    public function getStudentCourses(Request $request)
    {
        $request->validate([
            'student_nic' => 'required|string',
        ]);

        $student = Student::where('id_value', $request->student_nic)->first();
        if (!$student) {
            return response()->json(['success' => false, 'message' => 'Student not found.', 'courses' => []]);
        }

        return response()->json([
            'success' => true,
            'courses' => $this->coursesForStudent($student),
        ]);
    }

    private function coursesForStudent(Student $student)
    {
        return CourseRegistration::where('student_id', $student->student_id)
            ->with('course')
            ->get()
            ->filter(fn ($reg) => $reg->course)
            ->unique(fn ($reg) => $reg->course_id . '|' . ($reg->location ?? ''))
            ->map(function ($reg) {
                $location = $reg->location ?: ($reg->course->location ?? '');
                $courseName = $reg->course->course_name;
                $label = $location
                    ? $courseName . ' — Nebula Institute of Technology - ' . $location
                    : $courseName;

                return [
                    'course_id' => $reg->course->course_id,
                    'course_name' => $label,
                    'location' => $location,
                ];
            })
            ->values();
    }

    private function persistInstallmentApproval(PaymentInstallment $inst): void
    {
        $inst->offsetUnset('days_late');
        $inst->offsetUnset('is_late');
        $inst->save();
    }

    private function hydrateInstallmentLateFee(PaymentInstallment $inst): PaymentInstallment
    {
        $dueDate = \Carbon\Carbon::parse($inst->due_date);
        $isLate = $dueDate->isPast() && $inst->status !== 'paid';
        $daysLate = $isLate ? $dueDate->diffInDays(now()) : 0;
        $finalAmt = $inst->final_amount ?? $inst->amount ?? 0;

        $inst->calculated_late_fee = $isLate ? $this->calculateLateFee($finalAmt, $daysLate) : 0;
        $inst->days_late = $daysLate;
        $inst->is_late = $isLate;

        return $inst;
    }

    private function calculateLateFee($amount, $daysLate)
    {
        if ($daysLate <= 0) {
            return 0;
        }

        $dailyRate = (0.05 / 30);
        $lateFee = $amount * $dailyRate * $daysLate;

        return round(min($lateFee, $amount * 0.25), 2);
    }
}
