<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\PaymentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentPlanIndexPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Bursar',
            'email'         => 'plans@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Bursar',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_index_respects_per_page_and_uses_bootstrap_pagination(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);

        for ($i = 0; $i < 12; $i++) {
            $this->makePlan($course, $intake);
        }

        $this->actingAs($this->actor)
            ->get(route('payment.plan.index', ['per_page' => 10]))
            ->assertOk()
            ->assertSee('payment-plan-index', false)
            ->assertSee('Showing 1 to 10 of 12')
            ->assertSee('Per page')
            ->assertSee('Excel')
            ->assertSee('Nebula Institute of Technology - Welisara')
            ->assertSee(route('payment.plan.export.excel', ['per_page' => 10], false), false)
            ->assertSee(route('payment.plan.export.pdf', ['per_page' => 10], false), false);

        $this->actingAs($this->actor)
            ->get(route('payment.plan.index', ['per_page' => 25]))
            ->assertOk()
            ->assertSee('Showing 1 to 12 of 12');
    }

    public function test_courses_by_location_return_data_array(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->actor)
            ->postJson(route('payment.plan.courses.byLocation'), [
                'location' => 'Welisara',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.course_id', $course->course_id)
            ->assertJsonPath('courses.0.course_name', 'BTEC Computing');

        $this->actingAs($this->actor)
            ->postJson(route('payment.plan.courses.byLocation'), [
                'location' => '',
            ])
            ->assertOk()
            ->assertJsonPath('data.0.course_name', 'BTEC Computing');
    }

    public function test_intakes_load_without_location(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);

        $this->actingAs($this->actor)
            ->postJson(route('intakes.byCourse'), [
                'course_id' => $course->course_id,
                'location' => '',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.intake_id', $intake->intake_id);
    }

    public function test_sort_oldest_orders_by_id_ascending(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $first = $this->makePlan($course, $intake);
        $second = $this->makePlan($course, $intake);

        $this->actingAs($this->actor)
            ->get(route('payment.plan.index', ['sort' => 'oldest']))
            ->assertOk()
            ->assertSeeInOrder(['#' . $first->id, '#' . $second->id]);
    }

    public function test_excel_and_pdf_export_download(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $this->makePlan($course, $intake);

        $this->actingAs($this->actor)
            ->get(route('payment.plan.export.excel'))
            ->assertOk()
            ->assertHeader('content-disposition');

        $this->actingAs($this->actor)
            ->get(route('payment.plan.export.pdf'))
            ->assertOk();
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

    private function makePlan(Course $course, Intake $intake): PaymentPlan
    {
        return PaymentPlan::forceCreate([
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
        ]);
    }
}
