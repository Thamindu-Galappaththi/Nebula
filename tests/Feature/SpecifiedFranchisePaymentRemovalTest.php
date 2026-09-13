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
        $eee = $this->makeCourse('HND Electrical & Electronic Engineering');
        $digital = $this->makeCourse('HND Digital Technology');
        $foundation = $this->makeCourse('Pearson BTEC Foundation Diploma');
        $computing = $this->makeCourse('HND Computing');

        $eeeOld = $this->makeIntake($eee, '24/26', '280');
        $eeeNew = $this->makeIntake($eee, '25/27', '280');
        $digitalBatch = $this->makeIntake($digital, '24/26', '280');
        $foundationSeven = $this->makeIntake($foundation, 'Batch 7', '150');
        $foundationEight = $this->makeIntake($foundation, '8', '150');
        $foundationOther = $this->makeIntake($foundation, '17', '150');
        $computingSameBatch = $this->makeIntake($computing, '24/26', '280');

        $eeePlan = $this->makePlan($eee, $eeeOld, 280);
        $computingPlan = $this->makePlan($computing, $computingSameBatch, 280);

        SpecifiedFranchisePaymentRemoval::run();

        $this->assertEquals(0, (float) $eeeOld->fresh()->franchise_payment);
        $this->assertEquals(0, (float) $eeeNew->fresh()->franchise_payment);
        $this->assertEquals(0, (float) $digitalBatch->fresh()->franchise_payment);
        $this->assertEquals(0, (float) $foundationSeven->fresh()->franchise_payment);
        $this->assertEquals(0, (float) $foundationEight->fresh()->franchise_payment);
        $this->assertEquals(150, (float) $foundationOther->fresh()->franchise_payment);
        $this->assertEquals(280, (float) $computingSameBatch->fresh()->franchise_payment);

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
