<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentPlanCreatePageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Bursar',
            'email'         => 'plan-create@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Bursar',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_create_page_renders_campus_labels_and_safe_scripts(): void
    {
        $this->actingAs($this->actor)
            ->get(route('payment.plan'))
            ->assertOk()
            ->assertSee('payment-plan-create', false)
            ->assertSee('Nebula Institute of Technology - Welisara')
            ->assertSee('flex-nowrap', false)
            ->assertSee('new Option', false)
            ->assertSee('syncCustomSelect', false);
    }

    public function test_create_page_lists_courses_for_selected_location(): void
    {
        $this->makeCourse();

        $this->actingAs($this->actor)
            ->get(route('payment.plan', ['location' => 'Welisara']))
            ->assertOk()
            ->assertSee('BTEC Computing');
    }

    public function test_store_creates_plan_without_installments_payload(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);

        $this->actingAs($this->actor)
            ->from(route('payment.plan'))
            ->post(route('payment.plan.store'), [
                'location'          => 'Welisara',
                'course'            => $course->course_id,
                'intake'            => $intake->intake_id,
                'registrationFee'   => 5000,
                'localFee'          => 50000,
                'internationalFee'  => 200,
                'currency'          => 'GBP',
                'ssclTax'           => 2.5,
                'applyDiscount'     => 'no',
                'franchisePayment'  => 'no',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseHas('payment_plans', [
            'course_id' => $course->course_id,
            'intake_id' => $intake->intake_id,
            'location'  => 'Welisara',
        ]);
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
}
