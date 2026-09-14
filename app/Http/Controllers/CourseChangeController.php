<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentPaymentPlan;
use App\Models\PaymentPlan;
use App\Models\PaymentDetail;
use App\Models\PaymentInstallment;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CourseChangeController extends Controller
{
    /**
     * Display the course change interface
     */
    public function index()
    {
        return view('registration.course_change');
    }

    /**
     * Find student by NIC
     */
    public function findStudent(Request $request)
    {
        try {
            $request->validate([
                'nic' => 'required|string|max:20'
            ]);

            Log::info('Searching student by NIC: ' . $request->nic);

            $nic = trim((string) $request->nic);
            $student = Student::query()
                ->where(function ($query) use ($nic) {
                    $query->where('id_value', $nic);
                    if (ctype_digit($nic)) {
                        $query->orWhere('student_id', (int) $nic);
                    }
                })
                ->first();

            if (!$student) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Student not found. Please check the NIC and try again.'
                ], 404);
            }

            $today = now()->toDateString();

            $registrations = CourseRegistration::where('student_id', $student->student_id)
                ->where('status', 'Registered')
                ->with(['course' => function($query) {
                    $query->select('course_id', 'course_name', 'location', 'course_type');
                }, 'intake' => function($query) {
                    $query->select('intake_id', 'batch', 'start_date', 'course_name', 'location');
                }])
                ->orderBy('course_start_date', 'desc')
                ->get()
                ->map(function($reg) use ($today) {
                    $startDateRaw = $reg->course_start_date ?? optional($reg->intake)->start_date;
                    if (!$startDateRaw) {
                        $reg->is_change_allowed = false;
                        $reg->change_deadline = null;
                        $reg->is_future = false;
                        $reg->course_start_date = null;
                        return $reg;
                    }

                    $startDate = Carbon::parse($startDateRaw);
                    $deadline = $startDate->copy()->addYear();
                    $now = Carbon::parse($today);

                    $reg->course_start_date = $startDate->toDateString();
                    $reg->is_change_allowed = $now->lt($deadline);
                    $reg->change_deadline = $deadline->toDateString();
                    $reg->is_future = $startDate->toDateString() >= $today;
                    return $reg;
                });

            Log::info('Found ' . $registrations->count() . ' registrations for student: ' . $student->student_id);

            return response()->json([
                'status' => 'success',
                'student' => [
                    'student_id' => $student->student_id,
                    'full_name' => $student->name_with_initials ?: $student->full_name,
                    'id_value' => $student->id_value,
                    'email' => $student->email,
                    'phone' => $student->mobile_phone
                ],
                'registrations' => $registrations,
                'count' => $registrations->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error finding student: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all active courses for dropdown
     */
    public function getCourses()
    {
        try {
            $coursesQuery = Course::select('course_id', 'course_name', 'location', 'course_type');
            if (Schema::hasColumn('courses', 'status')) {
                $coursesQuery->where(function($query) {
                    $query->where('status', 'active')
                          ->orWhereNull('status');
                });
            }

            $courses = $coursesQuery
                ->orderBy('location')
                ->orderBy('course_name')
                ->get();

            Log::info('Retrieved ' . $courses->count() . ' courses');

            return response()->json([
                'status' => 'success',
                'courses' => $courses,
                'count' => $courses->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting courses: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load courses',
                'courses' => []
            ], 500);
        }
    }

    /**
     * Get intakes for selected course
     */
    public function getNewIntakes(Request $request)
    {
        try {
            $request->validate([
                'course_id' => 'required|integer|exists:courses,course_id'
            ]);

            $course = Course::find($request->course_id);
            if (!$course) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Course not found',
                    'intakes' => []
                ], 404);
            }

            $intakesQuery = Intake::forCourse($course, $course->location);
            if (Schema::hasColumn('intakes', 'status')) {
                $intakesQuery->where(function($query) {
                    $query->where('status', 'active')
                          ->orWhereNull('status');
                });
            }

            $intakes = $intakesQuery
                ->orderBy('start_date', 'desc')
                ->get(['intake_id', 'batch', 'start_date', 'location', 'course_id']);

            Log::info('Found ' . $intakes->count() . ' intakes for course: ' . $request->course_id);

            return response()->json([
                'status' => 'success',
                'intakes' => $intakes,
                'count' => $intakes->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting intakes: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load intakes',
                'intakes' => []
            ], 500);
        }
    }

    /**
     * Generate new course registration ID
     */
    public function generateNewCourseRegId(Request $request)
    {
        try {
            $request->validate([
                'intake_id' => 'required|integer|exists:intakes,intake_id'
            ]);

            Log::info('Generating ID for intake: ' . $request->intake_id);

            $intake = Intake::find($request->intake_id);

            if (!$intake) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Intake not found'
                ], 404);
            }

            if (!$intake->course_registration_id_pattern) {
                $course = Course::find($intake->course_id);
                if (!$course) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Course not found for this intake'
                    ], 404);
                }
                $pattern = strtoupper(substr((string) $course->location, 0, 3)) . '/' .
                          date('Y') . '/' .
                          strtoupper(substr((string) $course->course_type, 0, 3)) . '/' .
                          '001';
                
                // Update intake with generated pattern
                $intake->update(['course_registration_id_pattern' => $pattern]);
                Log::info('Generated default pattern: ' . $pattern);
            }

            $pattern = $intake->course_registration_id_pattern;

            // Extract prefix and number
            if (!preg_match('/^(.*?)(\d+)$/', $pattern, $matches)) {
                // If no number at end, add 001
                $prefix = $pattern;
                $digits = 3;
                $next = '001';
                $newId = $prefix . $next;
                
                Log::info('Generated ID (no pattern): ' . $newId);
                
                return response()->json([
                    'status' => 'success',
                    'new_id' => $newId
                ]);
            }

            $prefix = $matches[1];
            $baseNum = $matches[2];
            $digits = strlen($baseNum);

            // Get existing IDs with same prefix
            $existing = CourseRegistration::where('course_registration_id', 'LIKE', $prefix . '%')
                ->pluck('course_registration_id')
                ->toArray();

            if (count($existing) === 0) {
                Log::info('No existing IDs found, using base pattern: ' . $pattern);
                return response()->json([
                    'status' => 'success',
                    'new_id' => $pattern
                ]);
            }

            // Find maximum number
            $max = 0;
            foreach ($existing as $id) {
                if (preg_match('/^' . preg_quote($prefix, '/') . '(\d+)$/', $id, $m)) {
                    $num = intval($m[1]);
                    if ($num > $max) {
                        $max = $num;
                    }
                }
            }

            $next = str_pad($max + 1, $digits, '0', STR_PAD_LEFT);
            $newId = $prefix . $next;

            Log::info('Generated new ID: ' . $newId . ' (prefix: ' . $prefix . ', next: ' . $next . ')');

            return response()->json([
                'status' => 'success',
                'new_id' => $newId
            ]);

        } catch (\Exception $e) {
            Log::error('Error generating ID: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to generate registration ID: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check payment status for a registration
     */
    /**
 * Check payment status for a registration
 */
public function checkPaymentStatus(Request $request)
{
    try {
        Log::info('=== START PAYMENT CHECK ===');
        Log::info('Request data:', $request->all());
        
        $request->validate([
            'registration_id' => 'required|integer'
        ]);

        // First check if registration exists
        $registration = CourseRegistration::find($request->registration_id);
        
        if (!$registration) {
            Log::warning('Registration not found: ' . $request->registration_id);
            return response()->json([
                'status' => 'error',
                'message' => 'Registration not found with ID: ' . $request->registration_id
            ], 404);
        }

        Log::info('Found registration:', [
            'id' => $registration->id,
            'student_id' => $registration->student_id,
            'course_id' => $registration->course_id,
            'status' => $registration->status
        ]);

        // Check payment plan
        $paymentPlan = StudentPaymentPlan::where('student_id', $registration->student_id)
            ->where('course_id', $registration->course_id)
            ->where('status', 'active')
            ->first();

        Log::info('Payment plan search:', [
            'student_id' => $registration->student_id,
            'course_id' => $registration->course_id,
            'found' => $paymentPlan ? 'Yes' : 'No'
        ]);

        if (!$paymentPlan) {
            Log::info('No active payment plan found');

            $paymentDetails = PaymentDetail::where('student_id', $registration->student_id)
                ->where('course_registration_id', $registration->id)
                ->orderByDesc('created_at')
                ->get();

            $paymentHistory = $this->buildPaymentHistoryPreview($paymentDetails);

            return response()->json([
                'status' => 'success',
                'has_payment_plan' => false,
                'has_payments' => false,
                'total_paid_amount' => 0,
                'payment_history_count' => count($paymentHistory),
                'payment_history' => $paymentHistory,
                'message' => 'No active payment plan found for this course.'
            ]);
        }

        Log::info('Payment plan found:', [
            'id' => $paymentPlan->id,
            'total_amount' => $paymentPlan->total_amount,
            'final_amount' => $paymentPlan->final_amount
        ]);

        // Check payment details
        $paymentDetails = PaymentDetail::where('student_id', $registration->student_id)
            ->where('course_registration_id', $registration->id)
            ->orderByDesc('created_at')
            ->get();

        $paymentHistory = $this->buildPaymentHistoryPreview($paymentDetails);

        Log::info('Found ' . $paymentDetails->count() . ' payment details');

        $totalPaidAmount = 0;
        $hasPayments = false;

        foreach ($paymentDetails as $paymentDetail) {
            $partialPreview = is_string($paymentDetail->partial_payments)
                ? substr($paymentDetail->partial_payments, 0, 100)
                : json_encode($paymentDetail->partial_payments);

            Log::info('Processing payment detail:', [
                'id' => $paymentDetail->id,
                'amount' => $paymentDetail->amount,
                'status' => $paymentDetail->status,
                'partial_payments' => $partialPreview
            ]);

            $paidAmount = $this->getPaidAmountFromDetail($paymentDetail);

            if ($paidAmount > 0) {
                $totalPaidAmount += $paidAmount;
                $hasPayments = true;
                Log::info('Accumulated payment amount:', [
                    'amount' => $paidAmount,
                    'total_so_far' => $totalPaidAmount
                ]);
            }
        }

        Log::info('Payment check complete:', [
            'total_paid_amount' => $totalPaidAmount,
            'has_payments' => $hasPayments
        ]);

        $response = [
            'status' => 'success',
            'has_payment_plan' => true,
            'has_payments' => $hasPayments,
            'total_paid_amount' => $totalPaidAmount,
            'payment_plan_id' => $paymentPlan->id,
            'student_id' => $registration->student_id,
            'payment_history_count' => count($paymentHistory),
            'payment_history' => $paymentHistory,
            'debug_info' => [
                'registration_id' => $registration->id,
                'payment_details_count' => $paymentDetails->count()
            ]
        ];

        if ($hasPayments) {
            $response['message'] = "Found payment records with LKR " . number_format($totalPaidAmount, 2) . " paid.";
        } else {
            $response['message'] = "Payment plan exists but no payments have been made yet.";
        }

        Log::info('=== END PAYMENT CHECK ===');
        return response()->json($response);

    } catch (\Exception $e) {
        Log::error('Payment check error: ' . $e->getMessage());
        Log::error('Stack trace: ' . $e->getTraceAsString());
        
        return response()->json([
            'status' => 'error',
            'message' => 'Error checking payment status: ' . $e->getMessage(),
            'error_details' => [
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]
        ], 500);
    }
}

private function buildPaymentHistoryPreview($paymentDetails): array
{
    return $paymentDetails
        ->map(function ($paymentDetail) {
            $paidAmount = $this->getPaidAmountFromDetail($paymentDetail);

            return [
                'payment_id' => (int) $paymentDetail->id,
                'payment_type' => $paymentDetail->payment_type ?? $paymentDetail->installment_type ?? 'course_fee',
                'installment_number' => $paymentDetail->installment_number,
                'receipt_no' => $paymentDetail->transaction_id,
                'payment_method' => $paymentDetail->payment_method,
                'payment_date' => optional($paymentDetail->payment_date ?? $paymentDetail->created_at)->format('Y-m-d'),
                'status' => $paymentDetail->status,
                'amount' => (float) ($paymentDetail->amount ?? 0),
                'paid_amount' => (float) $paidAmount,
                'total_fee' => (float) ($paymentDetail->total_fee ?? 0),
                'remaining_amount' => (float) ($paymentDetail->remaining_amount ?? 0),
                'sscl_tax_amount' => (float) ($paymentDetail->sscl_tax_amount ?? 0),
                'bank_charges' => (float) ($paymentDetail->bank_charges ?? 0),
                'remarks' => $paymentDetail->remarks,
            ];
        })
        ->values()
        ->all();
}

    /**
     * Submit course change request
     */
    public function submitChange(Request $request)
    {
        DB::beginTransaction();

        try {
            $request->validate([
                'registration_id' => 'required|integer|exists:course_registration,id',
                'new_intake_id' => 'required|integer|exists:intakes,intake_id',
                'new_course_registration_id' => 'required|string|max:100'
            ]);

            Log::info('Starting course change process', [
                'registration_id' => $request->registration_id,
                'new_intake_id' => $request->new_intake_id,
                'new_course_registration_id' => $request->new_course_registration_id,
                'user_id' => auth()->id()
            ]);

            $registration = CourseRegistration::with(['course', 'intake'])->find($request->registration_id);
            $newIntake = Intake::with('course')->find($request->new_intake_id);

            if (!$registration || !$newIntake) {
                throw new \Exception('Invalid registration or intake data');
            }

            $oldIntakeId = $registration->intake_id;
            $oldCourseId = $registration->course_id;
            $studentId = $registration->student_id;
            $newLocation = $newIntake->location ?? ($newIntake->course->location ?? $registration->location);
            $isIntakeOnlyChange = (int) $newIntake->course_id === (int) $oldCourseId;

            Log::info('Course change details', [
                'student_id' => $studentId,
                'old_course_id' => $oldCourseId,
                'old_intake_id' => $oldIntakeId,
                'new_course_id' => $newIntake->course_id,
                'new_intake_id' => $newIntake->intake_id,
                'new_location' => $newLocation,
                'change_type' => $isIntakeOnlyChange ? 'intake_only' : 'course_and_intake'
            ]);

            $startDateRaw = $registration->course_start_date ?? optional($registration->intake)->start_date;
            if (!$startDateRaw) {
                throw new \Exception('This registration has no course start date, so it cannot be changed.');
            }

            $startDate = Carbon::parse($startDateRaw);
            $deadline = $startDate->copy()->addYear();

            if (Carbon::now()->greaterThanOrEqualTo($deadline)) {
                throw new \Exception('Course change is only allowed within 1 year from course start date. Start date: ' . $startDate->format('Y-m-d'));
            }

            // Process payment records
            $paymentProcessResult = $this->processPaymentRecords($studentId, $oldCourseId, $registration->id, $oldIntakeId);

            // Update the main course registration
            $registration->course_id = $newIntake->course_id;
            $registration->intake_id = $newIntake->intake_id;
            $registration->location = $newLocation;
            $registration->course_start_date = $newIntake->start_date;
            $registration->course_registration_id = $request->new_course_registration_id;
            $registration->updated_at = now();
            $registration->save();

            // Keep related student records aligned with the newly selected course/intake
            $syncResults = $this->syncRelatedStudentRecords(
                $studentId,
                $oldCourseId,
                $oldIntakeId,
                $newIntake,
                $newLocation
            );

            Log::info('Course registration updated successfully', [
                'sync_results' => $syncResults
            ]);

            // Log the course change
            $logId = $this->logCourseChange(
                $studentId,
                $oldIntakeId,
                $oldCourseId,
                $newIntake->intake_id,
                $newIntake->course_id,
                $paymentProcessResult['old_payment_plan_id'] ?? null,
                $paymentProcessResult['total_paid_amount'] ?? 0
            );

            Log::info('Course change logged with ID: ' . $logId);

            // Create or reuse payment plan for the selected new course/intake and
            // settle carried paid amount from installment 1 onward.
            $newPaymentPlanResult = $this->createNewPaymentPlan(
                $studentId,
                $newIntake->course_id,
                $newIntake->intake_id,
                (float) ($paymentProcessResult['total_paid_amount'] ?? 0)
            );

            DB::commit();

            Log::info('Course change completed successfully');

            return response()->json([
                'status' => 'success',
                'message' => $isIntakeOnlyChange ? 'Intake changed successfully!' : 'Course changed successfully!',
                'change_type' => $isIntakeOnlyChange ? 'intake_only' : 'course_and_intake',
                'payment_summary' => [
                    'total_paid_amount' => $paymentProcessResult['total_paid_amount'] ?? 0,
                    'payment_records_updated' => $paymentProcessResult['records_updated'] ?? 0,
                    'has_payments' => $paymentProcessResult['has_payments'] ?? false,
                    'carry_forward_applied' => (float) ($newPaymentPlanResult['carry_forward_applied'] ?? 0),
                    'carry_forward_remaining' => (float) ($newPaymentPlanResult['carry_forward_remaining'] ?? 0),
                ],
                'new_course_info' => [
                    'course_name' => $newIntake->course->course_name ?? 'N/A',
                    'intake_batch' => $newIntake->batch,
                    'start_date' => $newIntake->start_date,
                    'registration_id' => $request->new_course_registration_id
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Course change error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error changing course: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync related student records so intake/course listings reflect the new assignment.
     */
    private function syncRelatedStudentRecords($studentId, $oldCourseId, $oldIntakeId, Intake $newIntake, $newLocation = null)
    {
        $summary = [];
        $newCourseId = (int) $newIntake->course_id;
        $newIntakeId = (int) $newIntake->intake_id;
        $newLocation = $newLocation ?? ($newIntake->location ?? optional($newIntake->course)->location);

        $tablesToSync = [
            'semester_registrations',
            'module_management',
            'course_badges',
        ];

        foreach ($tablesToSync as $table) {
            if (!Schema::hasTable($table) || !Schema::hasColumn($table, 'student_id')) {
                continue;
            }

            $query = DB::table($table)
                ->where('student_id', $studentId);

            if (Schema::hasColumn($table, 'course_id')) {
                $query->where('course_id', $oldCourseId);
            }

            if (Schema::hasColumn($table, 'intake_id')) {
                $query->where('intake_id', $oldIntakeId);
            }

            $payload = [];

            if (Schema::hasColumn($table, 'course_id')) {
                $payload['course_id'] = $newCourseId;
            }

            if (Schema::hasColumn($table, 'intake_id')) {
                $payload['intake_id'] = $newIntakeId;
            }

            if ($newLocation && Schema::hasColumn($table, 'location')) {
                $payload['location'] = $newLocation;
            }

            if (Schema::hasColumn($table, 'updated_at')) {
                $payload['updated_at'] = now();
            }

            if (Schema::hasColumn($table, 'updated_by')) {
                $payload['updated_by'] = auth()->id();
            }

            if (empty($payload)) {
                continue;
            }

            $summary[$table] = $query->update($payload);
        }

        Log::info('Synced related records after course/intake change', [
            'student_id' => $studentId,
            'old_course_id' => $oldCourseId,
            'old_intake_id' => $oldIntakeId,
            'new_course_id' => $newCourseId,
            'new_intake_id' => $newIntakeId,
            'summary' => $summary,
        ]);

        return $summary;
    }

    /**
     * Process payment records for old course
     */
    private function processPaymentRecords($studentId, $oldCourseId, $registrationId, $oldIntakeId = null)
    {
        $result = [
            'total_paid_amount' => 0,
            'records_updated' => 0,
            'has_payments' => false,
            'old_payment_plan_id' => null
        ];

        Log::info('Processing payment records', [
            'student_id' => $studentId,
            'old_course_id' => $oldCourseId,
            'registration_id' => $registrationId
        ]);

        // Find existing payment plan
        $oldPaymentPlan = StudentPaymentPlan::where('student_id', $studentId)
            ->where('course_id', $oldCourseId)
            ->where('status', 'active')
            ->orderByDesc('id')
            ->first();

        if (!$oldPaymentPlan) {
            Log::info('No active payment plan found for old course');
            return $result;
        }

        $result['old_payment_plan_id'] = $oldPaymentPlan->id;
        Log::info('Found old payment plan: ' . $oldPaymentPlan->id);

        // Find related payment details
        $paymentDetails = PaymentDetail::where('student_id', $studentId)
            ->where('course_registration_id', $registrationId)
            ->get();

        Log::info('Found ' . $paymentDetails->count() . ' payment details');

        // Calculate total partial payments
        foreach ($paymentDetails as $paymentDetail) {
            $paidAmount = $this->getPaidAmountFromDetail($paymentDetail);

            if ($paidAmount > 0) {
                $result['total_paid_amount'] += $paidAmount;
                $result['has_payments'] = true;
            }

            $remarksPrefix = $paymentDetail->remarks ? rtrim($paymentDetail->remarks) . ' | ' : '';
            $paidNote = 'Cancelled due to course change on ' . now()->format('Y-m-d H:i:s');
            if ($paidAmount > 0) {
                $paidNote .= ' | Paid amount: LKR ' . number_format($paidAmount, 2);
            }

            // Update payment detail to cancelled
            $paymentDetail->update([
                'amount' => 0,
                'remaining_amount' => 0,
                'total_fee' => 0,
                'late_fee' => 0,
                'approved_late_fee' => 0,
                'partial_payments' => [],
                'status' => 'cancelled',
                'remarks' => $remarksPrefix . $paidNote,
                'updated_at' => now()
            ]);

            $result['records_updated']++;
            Log::debug('Updated payment detail: ' . $paymentDetail->id);
        }

        // Update payment installments
        $installmentsUpdated = PaymentInstallment::where('payment_plan_id', $oldPaymentPlan->id)
            ->update(array_merge([
                'status' => 'archived',
                'updated_at' => now()
            ], \App\Support\UserTrackingData::forUpdate()));

        Log::info('Updated ' . $installmentsUpdated . ' payment installments');

        // Update old payment plan
        $oldPaymentPlan->update([
            'status' => 'archived',
            'updated_at' => now()
        ]);

        Log::info('Cancelled payment plan: ' . $oldPaymentPlan->id);

        // Store payment summary when the optional audit table is available
        if (Schema::hasTable('course_change_payments')) {
            DB::table('course_change_payments')->insert([
                'student_id' => $studentId,
                'old_course_id' => $oldCourseId,
                'old_intake_id' => $oldIntakeId ?? null,
                'old_payment_plan_id' => $oldPaymentPlan->id,
                'total_paid_amount' => $result['total_paid_amount'],
                'remarks' => 'Payment records cancelled due to course change',
                'created_at' => now(),
                'updated_at' => now(),
                ...\App\Support\UserTrackingData::forCreate(),
            ]);
        } else {
            Log::warning('course_change_payments table is missing; skipping payment audit insert.');
        }

        Log::info('Payment processing complete', $result);

        return $result;
    }

    private function getPaidAmountFromDetail(PaymentDetail $paymentDetail)
    {
        $partialTotal = $this->sumPartialPayments($paymentDetail->partial_payments);
        if ($partialTotal > 0) {
            return $partialTotal;
        }

        if ($paymentDetail->status === 'paid') {
            $totalFee = $paymentDetail->total_fee ?? $paymentDetail->amount ?? 0;
            $remaining = $paymentDetail->remaining_amount ?? null;
            if (is_numeric($remaining)) {
                $totalFee = max(0, (float)$totalFee - (float)$remaining);
            }
            return max(0, (float)$totalFee);
        }

        return 0;
    }

    private function sumPartialPayments($partialPayments)
    {
        $partials = $this->normalizePartialPayments($partialPayments);
        $total = 0;

        foreach ($partials as $partial) {
            if (is_array($partial) && isset($partial['amount']) && is_numeric($partial['amount'])) {
                $total += (float)$partial['amount'];
            }
        }

        return $total;
    }

    private function normalizePartialPayments($partialPayments)
    {
        if (is_array($partialPayments)) {
            return $partialPayments;
        }

        if (!is_string($partialPayments)) {
            return [];
        }

        $trimmed = trim($partialPayments);
        if ($trimmed === '' || $trimmed === '[]' || $trimmed === '""' || strtolower($trimmed) === 'null') {
            return [];
        }

        $decoded = json_decode($trimmed, true);
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Create or reuse payment plan for the new course/intake and settle carry-forward.
     */
    private function createNewPaymentPlan($studentId, $newCourseId, $newIntakeId = null, $carryForwardAmount = 0.0)
    {
        $carryForwardAmount = max(0, (float) $carryForwardAmount);

        try {
            $existingPlan = StudentPaymentPlan::where('student_id', $studentId)
                ->where('course_id', $newCourseId)
                ->where('status', 'active')
                ->orderByDesc('id')
                ->first();

            if ($existingPlan) {
                Log::info('Active payment plan already exists for new course', [
                    'payment_plan_id' => $existingPlan->id,
                    'student_id' => $studentId,
                    'course_id' => $newCourseId,
                ]);

                $settlement = $this->applyCarryForwardToPlanInstallments($existingPlan, $carryForwardAmount);

                return [
                    'payment_plan_id' => $existingPlan->id,
                    'created' => false,
                    'carry_forward_applied' => $settlement['applied'],
                    'carry_forward_remaining' => $settlement['remaining'],
                ];
            }

            $course = Course::find($newCourseId);
            if (!$course) {
                throw new \Exception('Course not found for new payment plan: ' . $newCourseId);
            }

            $intake = null;
            if ($newIntakeId) {
                $intake = Intake::where('course_id', $newCourseId)
                    ->where('intake_id', $newIntakeId)
                    ->first();
            }

            if (!$intake) {
                $intake = Intake::where('course_id', $newCourseId)
                    ->orderBy('start_date', 'desc')
                    ->first();
            }

            $templatePlan = PaymentPlan::where('course_id', $newCourseId)
                ->when($newIntakeId, function ($query) use ($newIntakeId) {
                    $query->where('intake_id', $newIntakeId);
                })
                ->orderByDesc('id')
                ->first();

            if (!$templatePlan && $intake) {
                $templatePlan = PaymentPlan::where('course_id', $newCourseId)
                    ->where('intake_id', $intake->intake_id)
                    ->orderByDesc('id')
                    ->first();
            }

            $localFee = (float) ($templatePlan->local_fee ?? ($intake->course_fee ?? 0));
            $registrationFee = (float) ($templatePlan->registration_fee ?? ($intake->registration_fee ?? 0));
            $totalAmount = round($localFee + $registrationFee, 2);
            $installmentRows = $this->buildInstallmentsFromTemplate($templatePlan, $intake, $localFee);

            $newPaymentPlan = StudentPaymentPlan::create([
                'student_id' => $studentId,
                'course_id' => $newCourseId,
                'payment_plan_type' => ($templatePlan && $templatePlan->installment_plan) ? 'installments' : 'full',
                'slt_loan_applied' => 'no',
                'slt_loan_amount' => 0,
                'total_amount' => $totalAmount,
                'final_amount' => $totalAmount,
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach ($installmentRows as $index => $row) {
                PaymentInstallment::create([
                    'payment_plan_id' => $newPaymentPlan->id,
                    'installment_number' => (int) ($row['installment_number'] ?? ($index + 1)),
                    'due_date' => $row['due_date'] ?? null,
                    'amount' => (float) ($row['amount'] ?? 0),
                    'base_amount' => (float) ($row['base_amount'] ?? $row['amount'] ?? 0),
                    'final_amount' => (float) ($row['final_amount'] ?? $row['amount'] ?? 0),
                    'installment_type' => 'local',
                    'status' => 'pending',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $settlement = $this->applyCarryForwardToPlanInstallments($newPaymentPlan, $carryForwardAmount);

            Log::info('Created new payment plan for course change', [
                'payment_plan_id' => $newPaymentPlan->id,
                'student_id' => $studentId,
                'course_id' => $newCourseId,
                'intake_id' => $intake->intake_id ?? null,
                'installments_created' => count($installmentRows),
                'carry_forward_applied' => $settlement['applied'],
                'carry_forward_remaining' => $settlement['remaining'],
            ]);

            return [
                'payment_plan_id' => $newPaymentPlan->id,
                'created' => true,
                'carry_forward_applied' => $settlement['applied'],
                'carry_forward_remaining' => $settlement['remaining'],
            ];

        } catch (\Exception $e) {
            Log::error('Error creating new payment plan: ' . $e->getMessage());

            return [
                'payment_plan_id' => null,
                'created' => false,
                'carry_forward_applied' => 0.0,
                'carry_forward_remaining' => $carryForwardAmount,
            ];
        }
    }

    private function buildInstallmentsFromTemplate(?PaymentPlan $templatePlan, ?Intake $intake, float $localFee): array
    {
        $rawInstallments = [];

        if ($templatePlan && is_array($templatePlan->installments)) {
            $rawInstallments = $templatePlan->installments;
        } elseif ($templatePlan && is_string($templatePlan->installments)) {
            $decoded = json_decode($templatePlan->installments, true);
            $rawInstallments = is_array($decoded) ? $decoded : [];
        }

        $rows = [];

        foreach ($rawInstallments as $index => $installment) {
            $amount = round((float) ($installment['local_amount'] ?? $installment['amount'] ?? 0), 2);
            if ($amount <= 0) {
                continue;
            }

            $rows[] = [
                'installment_number' => (int) ($installment['installment_number'] ?? ($index + 1)),
                'due_date' => $installment['due_date'] ?? optional($intake?->start_date)->toDateString(),
                'amount' => $amount,
                'base_amount' => $amount,
                'final_amount' => $amount,
            ];
        }

        if (empty($rows)) {
            $defaultAmount = $localFee > 0 ? $localFee : (float) ($intake->course_fee ?? 0);
            $rows[] = [
                'installment_number' => 1,
                'due_date' => $intake && $intake->start_date ? Carbon::parse($intake->start_date)->toDateString() : now()->addDays(7)->toDateString(),
                'amount' => round($defaultAmount, 2),
                'base_amount' => round($defaultAmount, 2),
                'final_amount' => round($defaultAmount, 2),
            ];
        }

        usort($rows, function ($a, $b) {
            return ((int) $a['installment_number']) <=> ((int) $b['installment_number']);
        });

        return $rows;
    }

    private function applyCarryForwardToPlanInstallments(StudentPaymentPlan $plan, float $carryForwardAmount): array
    {
        $remainingCarry = round(max(0, $carryForwardAmount), 2);
        $applied = 0.0;

        if ($remainingCarry <= 0) {
            return ['applied' => 0.0, 'remaining' => 0.0];
        }

        $installments = PaymentInstallment::where('payment_plan_id', $plan->id)
            ->orderBy('installment_number')
            ->get();

        foreach ($installments as $installment) {
            if ($remainingCarry <= 0) {
                break;
            }

            $status = strtolower((string) ($installment->status ?? 'pending'));
            if ($status === 'archived') {
                continue;
            }

            $dueAmount = round((float) ($installment->final_amount ?? $installment->amount ?? 0), 2);
            if ($dueAmount <= 0) {
                continue;
            }

            $settledForInstallment = min($remainingCarry, $dueAmount);
            if ($settledForInstallment <= 0) {
                continue;
            }

            $remainingForInstallment = round($dueAmount - $settledForInstallment, 2);
            $isFullySettled = $remainingForInstallment <= 0;

            $installment->status = $isFullySettled ? 'paid' : 'pending';
            $installment->paid_date = $isFullySettled ? now() : null;

            // Keep original amount for fully settled rows; for partial settlement,
            // store only the outstanding balance in final/amount.
            if (!$isFullySettled) {
                $installment->final_amount = $remainingForInstallment;
                $installment->amount = $remainingForInstallment;
            }

            $installment->updated_at = now();
            $installment->save();

            $remainingCarry = round($remainingCarry - $settledForInstallment, 2);
            $applied = round($applied + $settledForInstallment, 2);
        }

        Log::info('Applied carry-forward amount to new payment plan', [
            'payment_plan_id' => $plan->id,
            'carry_forward_total' => $carryForwardAmount,
            'carry_forward_applied' => $applied,
            'carry_forward_remaining' => $remainingCarry,
        ]);

        return [
            'applied' => $applied,
            'remaining' => $remainingCarry,
        ];
    }

    /**
     * Log course change with payment info
     */
    private function logCourseChange($studentId, $oldIntakeId, $oldCourseId, $newIntakeId, $newCourseId, $oldPaymentPlanId, $totalPaidAmount)
    {
        if (!Schema::hasTable('course_change_logs')) {
            Log::warning('course_change_logs table is missing; skipping course change audit insert.');
            return null;
        }

        $payload = [
            'student_id' => $studentId,
            'old_intake_id' => $oldIntakeId,
            'new_intake_id' => $newIntakeId,
            'changed_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $optionalColumns = [
            'old_course_id' => $oldCourseId,
            'new_course_id' => $newCourseId,
            'old_payment_plan_id' => $oldPaymentPlanId,
            'total_paid_amount' => $totalPaidAmount,
            'changed_by_name' => auth()->user()->name ?? 'System',
            'changed_at' => now(),
            'remarks' => 'Course change processed',
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ];

        foreach ($optionalColumns as $column => $value) {
            if (Schema::hasColumn('course_change_logs', $column)) {
                $payload[$column] = $value;
            }
        }

        return DB::table('course_change_logs')->insertGetId($payload);
    }

    /**
     * Get payment summary for a course change
     */
    public function getPaymentSummary($studentId, $courseId)
    {
        try {
            Log::info('Getting payment summary for student: ' . $studentId . ', course: ' . $courseId);

            if (!Schema::hasTable('course_change_payments')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment audit table is not available yet'
                ], 404);
            }

            $summary = DB::table('course_change_payments')
                ->where('student_id', $studentId)
                ->where('old_course_id', $courseId)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$summary) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No payment summary found'
                ], 404);
            }

            // Get student info
            $student = Student::find($studentId);
            $course = Course::find($courseId);

            return response()->json([
                'status' => 'success',
                'summary' => $summary,
                'student' => $student ? [
                    'name' => $student->full_name,
                    'id' => $student->student_id
                ] : null,
                'course' => $course ? [
                    'name' => $course->course_name
                ] : null
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting payment summary: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get payment summary'
            ], 500);
        }
    }

    /**
     * Get course change logs for student
     */
    public function getChangeLogs($studentId)
    {
        try {
            if (!Schema::hasTable('course_change_logs')) {
                return response()->json([
                    'status' => 'success',
                    'logs' => [],
                    'count' => 0
                ]);
            }

            $logs = DB::table('course_change_logs')
                ->where('student_id', $studentId)
                ->orderBy('changed_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'logs' => $logs,
                'count' => $logs->count()
            ]);

        } catch (\Exception $e) {
            Log::error('Error getting change logs: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get change logs'
            ], 500);
        }
    }

    public function getCancelledPayments($studentId)
    {
        try {
            $payments = PaymentDetail::where('student_id', $studentId)
                ->where('status', 'cancelled')
                ->orderBy('updated_at', 'desc')
                ->get(['id', 'course_registration_id', 'remarks', 'status', 'updated_at']);

            return response()->json([
                'status' => 'success',
                'payments' => $payments
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting cancelled payments: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get cancelled payments'
            ], 500);
        }
    }

    public function updateCancelledPaymentStatus(Request $request, $paymentDetailId)
    {
        try {
            $request->validate([
                'status' => 'required|in:cancelled,pending'
            ]);

            $payment = PaymentDetail::find($paymentDetailId);
            if (!$payment) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment record not found'
                ], 404);
            }

            $today = now()->toDateString();
            $statusNote = $request->status === 'cancelled'
                ? "Payment Updated {$today}"
                : "Pending the Payment Update";

            $remarksBase = $payment->remarks ?? '';
            $remarksBase = preg_replace(
                '/\s*\|\s*(Payment Updated\s\d{4}-\d{2}-\d{2}|Pending the Payment Update)\s*$/',
                '',
                trim($remarksBase)
            );
            $remarksBase = trim($remarksBase, " \t\n\r\0\x0B|");
            $remarksBase = $remarksBase === '' ? '' : $remarksBase;
            $note = $remarksBase === '' ? $statusNote : $remarksBase . ' | ' . $statusNote;

            $payment->update([
                'remarks' => $note,
                'updated_at' => now()
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Payment updated successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating cancelled payment status: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to update payment'
            ], 500);
        }
    }

    public function getCourseChangeHistory($studentId)
    {
        try {
            $logs = Schema::hasTable('course_change_logs')
                ? DB::table('course_change_logs')
                    ->where('student_id', $studentId)
                    ->orderBy('changed_at', 'desc')
                    ->get()
                : collect();

            $payments = Schema::hasTable('course_change_payments')
                ? DB::table('course_change_payments')
                    ->where('student_id', $studentId)
                    ->orderBy('created_at', 'desc')
                    ->get()
                : collect();

            $courseIds = $logs->flatMap(fn ($log) => [$log->old_course_id ?? null, $log->new_course_id ?? null])
                ->merge($payments->pluck('old_course_id'))
                ->filter()
                ->unique()
                ->values();
            $intakeIds = $logs->flatMap(fn ($log) => [$log->old_intake_id ?? null, $log->new_intake_id ?? null])
                ->merge($payments->pluck('old_intake_id'))
                ->filter()
                ->unique()
                ->values();

            $courseNames = $courseIds->isNotEmpty()
                ? Course::whereIn('course_id', $courseIds)->pluck('course_name', 'course_id')
                : collect();
            $intakeBatches = $intakeIds->isNotEmpty()
                ? Intake::whereIn('intake_id', $intakeIds)->pluck('batch', 'intake_id')
                : collect();

            $logs = $logs->map(function ($log) use ($courseNames, $intakeBatches) {
                $log->old_course_name = $courseNames[$log->old_course_id] ?? $log->old_course_id;
                $log->new_course_name = $courseNames[$log->new_course_id] ?? $log->new_course_id;
                $log->old_intake_batch = $intakeBatches[$log->old_intake_id] ?? $log->old_intake_id;
                $log->new_intake_batch = $intakeBatches[$log->new_intake_id] ?? $log->new_intake_id;
                return $log;
            });
            $payments = $payments->map(function ($payment) use ($courseNames, $intakeBatches) {
                $payment->old_course_name = $courseNames[$payment->old_course_id] ?? $payment->old_course_id;
                $payment->old_intake_batch = $intakeBatches[$payment->old_intake_id] ?? $payment->old_intake_id;
                return $payment;
            });

            return response()->json([
                'status' => 'success',
                'logs' => $logs,
                'payments' => $payments
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting course change history: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load course change history'
            ], 500);
        }
    }
}
