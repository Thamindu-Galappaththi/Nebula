<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\PaymentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentPlanEditPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Bursar',
            'email'         => 'plan-edit@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Bursar',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_edit_page_stacks_installments_and_cancels_to_index(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $plan = $this->makePlan($course, $intake, [
            'installment_plan' => true,
            'installments'     => [
                [
                    'installment_number'   => 1,
                    'due_date'             => '2026-10-01',
                    'local_amount'         => 1,
                    'international_amount' => 0,
                    'apply_tax'            => false,
                ],
                [
                    'installment_number'   => 2,
                    'due_date'             => '2026-11-01',
                    'local_amount'         => 1,
                    'international_amount' => 0,
                    'apply_tax'            => false,
                ],
            ],
        ]);

        $this->actingAs($this->actor)
            ->get(route('payment.plan.edit', $plan->id))
            ->assertOk()
            ->assertSee('payment-plan-edit', false)
            ->assertSee('payment-plan-installments', false)
            ->assertSee('data-label="Due Date"', false)
            ->assertSee('data-label="Local (LKR)"', false)
            ->assertSee('data-label="Tax?"', false)
            ->assertSee('display: block', false)
            ->assertSee('2026-10-01')
            ->assertSee('2026-11-01')
            ->assertSee('Nebula Institute of Technology - Welisara')
            ->assertSee('BTEC Computing')
            ->assertSee('Cancel')
            ->assertSee('href="'.e(route('payment.plan.index')).'"', false)
            ->assertSee('class="btn btn-secondary">Cancel</a>', false)
            ->assertSee('Update')
            ->assertSee('id="installmentsEmpty" class="text-center text-muted py-3 d-none"', false);
    }

    public function test_edit_page_shows_empty_installments_state(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $plan = $this->makePlan($course, $intake, [
            'installment_plan' => false,
            'installments'     => [],
        ]);

        $this->actingAs($this->actor)
            ->get(route('payment.plan.edit', $plan->id))
            ->assertOk()
            ->assertSee('No installments defined')
            ->assertSee('btn-add-installment-row', false)
            ->assertSee('btn-remove-last-row', false);
    }

    private function makeCourse(): Course
    {
        return Course::forceCreate([
            'course_name'         => 'BTEC Computing',
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 8,
            'duration'            => '4 years',
            'min_credits'         => 120,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 0,
        ]);
    }

    private function makeIntake(Course $course): Intake
    {
        return Intake::forceCreate([
            'location'          => 'Welisara',
            'course_id'         => $course->course_id,
            'course_name'       => $course->course_name,
            'batch'             => '2024-JUL-B08',
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->subMonth()->toDateString(),
            'end_date'          => now()->addYears(2)->toDateString(),
        ]);
    }

    private function makePlan(Course $course, Intake $intake, array $overrides = []): PaymentPlan
    {
        return PaymentPlan::forceCreate(array_merge([
            'location'               => 'Welisara',
            'course_id'              => $course->course_id,
            'intake_id'              => $intake->intake_id,
            'registration_fee'       => 10000,
            'local_fee'              => 50000,
            'international_fee'      => 200,
            'international_currency' => 'GBP',
            'sscl_tax'               => 2.5,
            'apply_discount'         => false,
            'installment_plan'       => false,
        ], $overrides));
    }
}
