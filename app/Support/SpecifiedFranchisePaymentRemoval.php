<?php

namespace App\Support;

use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\PaymentPlan;
use App\Models\StudentPaymentPlan;
use Illuminate\Support\Facades\Schema;

class SpecifiedFranchisePaymentRemoval
{
    /**
     * Courses/batches that should no longer carry a franchise (international) fee.
     * Raised 16 Jun 2026: HND Electrical & Electronic Engineering (24/26, 25/27),
     * HND Digital Technology (24/26), Foundation (Batch 7, 8).
     */
    public static function targets(): array
    {
        return [
            [
                'label' => 'HND Electrical & Electronic Engineering',
                'course_needles' => ['electrical', 'electronic'],
                'batches' => ['24/26', '25/27'],
                'batch_mode' => 'contains',
            ],
            [
                'label' => 'HND Digital Technology',
                'course_needles' => ['digital technolog'],
                'batches' => ['24/26'],
                'batch_mode' => 'contains',
            ],
            [
                'label' => 'Foundation',
                'course_needles' => ['foundation'],
                'batches' => ['7', '8'],
                'batch_mode' => 'foundation',
            ],
        ];
    }

    public static function matchingIntakes()
    {
        return Intake::query()
            ->with('course')
            ->get()
            ->filter(fn (Intake $intake) => self::intakeMatches($intake))
            ->values();
    }

    public static function run(): array
    {
        $intakes = self::matchingIntakes();
        $summary = [
            'intakes' => 0,
            'payment_plans' => 0,
            'student_installments' => 0,
        ];

        foreach ($intakes as $intake) {
            self::zeroIntakeFranchise($intake);
            $summary['intakes']++;

            $plans = PaymentPlan::query()
                ->where('intake_id', $intake->intake_id)
                ->get();

            foreach ($plans as $plan) {
                self::zeroPlanFranchise($plan);
                $summary['payment_plans']++;
            }

            $summary['student_installments'] += self::zeroStudentInstallmentsForIntake($intake);
        }

        return $summary;
    }

    public static function intakeMatches(Intake $intake): bool
    {
        $courseName = strtolower(trim((string) (optional($intake->course)->course_name ?: $intake->course_name)));
        $batch = (string) ($intake->batch ?? '');

        foreach (self::targets() as $target) {
            if (! self::courseNameMatches($courseName, $target['course_needles'])) {
                continue;
            }

            if (self::batchMatches($batch, $target['batches'], $target['batch_mode'] ?? 'contains')) {
                return true;
            }
        }

        return false;
    }

    private static function courseNameMatches(string $courseName, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (! str_contains($courseName, strtolower($needle))) {
                return false;
            }
        }

        return $courseName !== '';
    }

    private static function batchMatches(string $batch, array $tokens, string $mode): bool
    {
        $normalized = strtolower(trim($batch));
        if ($normalized === '') {
            return false;
        }

        if ($mode === 'foundation') {
            return (bool) preg_match('/(?:^|[^0-9])(?:batch[\s\-_]*)?0?([78])(?:[^0-9]|$)/i', $batch);
        }

        foreach ($tokens as $token) {
            if (str_contains($normalized, strtolower($token))) {
                return true;
            }
        }

        return false;
    }

    private static function zeroIntakeFranchise(Intake $intake): void
    {
        $payload = ['franchise_payment' => '0'];
        if (Schema::hasColumn('intakes', 'updated_at')) {
            $payload['updated_at'] = now();
        }

        $intake->forceFill(array_merge($payload, UserTrackingData::forUpdate()))->save();
    }

    private static function zeroPlanFranchise(PaymentPlan $plan): void
    {
        $installments = $plan->installments;
        if (is_string($installments)) {
            $installments = json_decode($installments, true);
        }
        if (is_array($installments)) {
            foreach ($installments as &$item) {
                if (! is_array($item)) {
                    continue;
                }
                $item['international_amount'] = 0;
            }
            unset($item);
        }

        $plan->forceFill(array_merge([
            'international_fee' => 0,
            'installments' => $installments ?: $plan->installments,
        ], UserTrackingData::forUpdate()))->save();
    }

    private static function zeroStudentInstallmentsForIntake(Intake $intake): int
    {
        if (! Schema::hasTable('course_registration') || ! Schema::hasTable('student_payment_plans') || ! Schema::hasTable('payment_installments')) {
            return 0;
        }

        $studentIds = CourseRegistration::query()
            ->where('intake_id', $intake->intake_id)
            ->when($intake->course_id, fn ($q) => $q->where('course_id', $intake->course_id))
            ->pluck('student_id');

        if ($studentIds->isEmpty()) {
            return 0;
        }

        $planIds = StudentPaymentPlan::query()
            ->whereIn('student_id', $studentIds)
            ->when($intake->course_id, fn ($q) => $q->where('course_id', $intake->course_id))
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'archived');
            })
            ->pluck('id');

        if ($planIds->isEmpty()) {
            return 0;
        }

        $updated = 0;
        $installments = \App\Models\PaymentInstallment::query()
            ->whereIn('payment_plan_id', $planIds)
            ->where(function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'paid');
            })
            ->get();

        foreach ($installments as $installment) {
            $changed = false;
            if ((float) ($installment->international_amount ?? 0) > 0) {
                $installment->international_amount = 0;
                $changed = true;
            }

            $type = strtolower((string) ($installment->installment_type ?? ''));
            if ($type === 'international') {
                $installment->amount = 0;
                if (Schema::hasColumn('payment_installments', 'final_amount')) {
                    $installment->final_amount = 0;
                }
                if (Schema::hasColumn('payment_installments', 'base_amount')) {
                    $installment->base_amount = 0;
                }
                $changed = true;
            }

            if ($changed) {
                $installment->forceFill(UserTrackingData::forUpdate())->save();
                $updated++;
            }
        }

        return $updated;
    }
}
