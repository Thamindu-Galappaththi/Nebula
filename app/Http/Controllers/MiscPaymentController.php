<?php

namespace App\Http\Controllers;

use App\Models\PaymentDetail;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class MiscPaymentController extends Controller
{
    public function index()
    {
        return view('payments.misc_payment');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id' => 'required|string',
            'misc_category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:100',
            'transaction_id' => 'nullable|string|max:255',
            'remarks' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $student = $this->findStudent($request->student_id);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'No student found for the provided NIC or Student ID.',
            ], Response::HTTP_NOT_FOUND);
        }

        $remarks = $request->remarks ?: null;

        $payment = PaymentDetail::create([
            'student_id' => $student->student_id,
            'misc_category' => $request->misc_category,
            'misc_reference' => $request->misc_reference ?? null,
            'description' => $remarks,
            'remarks' => $remarks,
            'amount' => $request->amount,
            'payment_method' => $request->payment_method,
            'transaction_id' => $request->transaction_id ?: null,
            'payment_date' => now()->toDateString(),
            'status' => 'paid',
            'late_fee' => 0,
            'approved_late_fee' => 0,
            'total_fee' => $request->amount,
            'remaining_amount' => 0,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Miscellaneous payment recorded successfully.',
            'data' => $payment,
        ]);
    }

    public function fetchByStudent($input)
    {
        $student = $this->findStudent($input);

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found.',
            ], Response::HTTP_NOT_FOUND);
        }

        $payments = PaymentDetail::miscellaneous()
            ->where('student_id', $student->student_id)
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'student' => [
                'student_id' => $student->student_id,
                'full_name' => $student->full_name,
                'id_value' => $student->id_value,
            ],
            'payments' => $payments,
        ]);
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
}
