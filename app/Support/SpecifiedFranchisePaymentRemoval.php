<?php

namespace App\Support;

use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\PaymentInstallment;
use App\Models\PaymentPlan;
use App\Models\StudentPaymentPlan;
use Illuminate\Support\Facades\Schema;

class SpecifiedFranchisePaymentRemoval
{
    /**
     * Raised 16 Jun 2026: remove franchise fees only from
     * HND Electrical & Electronic Engineering (24/26, 25/27),
     * HND Digital Technology (24/26), Foundation (Batch 7, 8).
     *
     * Live batch labels look like "BTEC EE 2024-2026", "BTECEE2025-2027WE",
     * "BTEC DT 2024-2026 WD (NEW)", "BTEC Foundation B07".
     */
    public static function targets(): array
    {
        return [
            [
                'label' => 'HND Electrical & Electronic Engineering',
                'course_needles' => ['hnd', 'electrical', 'electronic'],
                'cohorts' => [
                    ['24', '26'],
                    ['25', '27'],
                ],
            ],
            [
                'label' => 'HND Digital Technology',
                'course_needles' => ['hnd', 'digital'],
                'cohorts' => [
                    ['24', '26'],
                ],
            ],
            [
                'label' => 'Foundation',
                'course_needles' => ['foundation'],
                'foundation_batches' => [7, 8],
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
            'matched_intake_ids' => $intakes->pluck('intake_id')->all(),
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

        if ($courseName === '' || $batch === '') {
            return false;
        }

        foreach (self::targets() as $target) {
            if (! self::courseNameMatches($courseName, $target['course_needles'])) {
                continue;
            }

            if (! empty($target['foundation_batches'])) {
                if (self::foundationBatchMatches($batch, $target['foundation_batches'])) {
                    return true;
                }
                continue;
            }

            foreach ($target['cohorts'] ?? [] as $cohort) {
                if (self::cohortMatches($batch, $cohort[0], $cohort[1])) {
                    return true;
                }
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

        return true;
    }

    private static function cohortMatches(string $batch, string $startYy, string $endYy): bool
    {
        $fullStart = '20' . $startYy;
        $fullEnd = '20' . $endYy;
        $haystack = strtolower($batch);
        $variants = [
            $startYy . '/' . $endYy,
            $startYy . '-' . $endYy,
            $fullStart . '-' . $fullEnd,
            $fullStart . '/' . $fullEnd,
            $fullStart . '-' . $endYy,
        ];

        foreach ($variants as $variant) {
            if (str_contains($haystack, strtolower($variant))) {
                return true;
            }
        }

        $digits = preg_replace('/\D+/', '', $batch) ?? '';

        return $digits !== '' && str_contains($digits, $fullStart . $fullEnd);
    }

    private static function foundationBatchMatches(string $batch, array $numbers): bool
    {
        $allowed = implode('', array_map('intval', $numbers));
        if ($allowed === '') {
            return false;
        }

        $patterns = [
            '/\bbatch\s*0?([' . $allowed . '])\b/i',
            '/\bb\s*0?([' . $allowed . '])\b/i',
            '/^(?:0)?([' . $allowed . '])$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, trim($batch))) {
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

        $registrations = CourseRegistration::query()
            ->where('intake_id', $intake->intake_id)
            ->get(['student_id', 'course_id']);

        if ($registrations->isEmpty()) {
            return 0;
        }

        $planIds = collect();
        foreach ($registrations->groupBy('course_id') as $courseId => $rows) {
            $query = StudentPaymentPlan::query()
                ->whereIn('student_id', $rows->pluck('student_id'))
                ->where(function ($q) {
                    $q->whereNull('status')->orWhere('status', '!=', 'archived');
                });

            if (! empty($courseId)) {
                $query->where('course_id', $courseId);
            } else {
                continue;
            }

            $planIds = $planIds->merge($query->pluck('id'));
        }

        $planIds = $planIds->unique()->filter()->values();
        if ($planIds->isEmpty()) {
            return 0;
        }

        $updated = 0;
        $installments = PaymentInstallment::query()
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
