<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\PaymentPlan;
use App\Support\SpecifiedFranchisePaymentRemoval;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpecifiedFranchisePaymentRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_removes_franchise_only_from_listed_courses_and_batches(): void
    {
        $eee = $this->makeCourse('Pearson BTEC Level 05 HND in Electrical & Electronic Engineering');
        $digital = $this->makeCourse('Pearson BTEC Level 5 HND in Digital Technolagies');
        $foundation = $this->makeCourse('Pearson BTEC International Level 03 Foundation Diploma in Engineering');
        $beng = $this->makeCourse('B.Eng. (Hons) Electrical & Electronic Engineering');
        $computing = $this->makeCourse('Pearson BTEC Level 05 HND in Computing');

        $eee2426 = $this->makeIntake($eee, 'BTEC EE 2024-2026', '280');
        $eee2527 = $this->makeIntake($eee, 'BTECEE2025-2027WE', '294');
        $eeeLater = $this->makeIntake($eee, 'BTEC EE 2026-2028 WE', '294');
        $digital2426 = $this->makeIntake($digital, 'BTEC DT 2024-2026 WD (NEW)', '280');
        $digitalLater = $this->makeIntake($digital, 'BTEC DT 2025-2027 (New)', '295');
        $foundationSeven = $this->makeIntake($foundation, 'BTEC Foundation B07', '150');
        $foundationEight = $this->makeIntake($foundation, 'BTEC Foundation B 08', '150');
        $foundationSix = $this->makeIntake($foundation, 'BTEC Foundation B06', '130');
        $bengSameYears = $this->makeIntake($beng, '2024-JUl-B08-EEE', '3300');
        $computingSameYears = $this->makeIntake($computing, 'BTEC Computing 2024-2026', '280');

        $eeePlan = $this->makePlan($eee, $eee2426, 280);
        $computingPlan = $this->makePlan($computing, $computingSameYears, 280);

        SpecifiedFranchisePaymentRemoval::run();

        $this->assertEquals(0, (float) $eee2426->fresh()->franchise_payment);
        $this->assertEquals(0, (float) $eee2527->fresh()->franchise_payment);
        $this->assertEquals(0, (float) $digital2426->fresh()->franchise_payment);
        $this->assertEquals(0, (float) $foundationSeven->fresh()->franchise_payment);
        $this->assertEquals(0, (float) $foundationEight->fresh()->franchise_payment);

        $this->assertEquals(294, (float) $eeeLater->fresh()->franchise_payment);
        $this->assertEquals(295, (float) $digitalLater->fresh()->franchise_payment);
        $this->assertEquals(130, (float) $foundationSix->fresh()->franchise_payment);
        $this->assertEquals(3300, (float) $bengSameYears->fresh()->franchise_payment);
        $this->assertEquals(280, (float) $computingSameYears->fresh()->franchise_payment);

        $this->assertEquals(0, (float) $eeePlan->fresh()->international_fee);
        $this->assertEquals(280, (float) $computingPlan->fresh()->international_fee);
        $this->assertEquals(0, (float) ($eeePlan->fresh()->installments[0]['international_amount'] ?? 0));
    }

    private function makeCourse(string $name): Course
    {
        return Course::forceCreate([
            'course_name'         => $name,
            'course_type'         => 'diploma',
            'location'            => 'Welisara',
            'no_of_semesters'     => 4,
            'duration'            => '2 years',
            'min_credits'         => 60,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 0,
        ]);
    }

    private function makeIntake(Course $course, string $batch, string $franchise): Intake
    {
        return Intake::forceCreate([
            'location'          => 'Welisara',
            'course_id'         => $course->course_id,
            'course_name'       => $course->course_name,
            'batch'             => $batch,
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => $franchise,
            'course_fee'        => '50000',
            'start_date'        => now()->subMonth()->toDateString(),
            'end_date'          => now()->addYears(2)->toDateString(),
        ]);
    }

    private function makePlan(Course $course, Intake $intake, float $franchise): PaymentPlan
    {
        return PaymentPlan::forceCreate([
            'location'               => 'Welisara',
            'course_id'              => $course->course_id,
            'intake_id'              => $intake->intake_id,
            'registration_fee'       => 10000,
            'local_fee'              => 50000,
            'international_fee'      => $franchise,
            'international_currency' => 'GBP',
            'sscl_tax'               => 2.5,
            'apply_discount'         => false,
            'installment_plan'       => true,
            'installments'           => [
                [
                    'installment_number' => 1,
                    'due_date' => now()->toDateString(),
                    'local_amount' => 50000,
                    'international_amount' => $franchise,
                ],
            ],
        ]);
    }
}
