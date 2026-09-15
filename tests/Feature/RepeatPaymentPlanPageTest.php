<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\PaymentInstallment;
use App\Models\Student;
use App\Models\StudentPaymentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RepeatPaymentPlanPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name' => 'Program Admin',
            'email' => 'repeat-payment@nebula.lk',
            'password' => Hash::make('password'),
            'user_role' => 'Program Administrator (level 01)',
            'status' => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_responsive_layout_and_sweetalert(): void
    {
        $this->actingAs($this->actor)
            ->get(route('repeat.payment.index'))
            ->assertOk()
            ->assertSee('Enter NIC or Student ID')
            ->assertSee('Load Plan')
            ->assertSee('Courses load automatically', false)
            ->assertSee('Loading courses...', false)
            ->assertSee('repeat-payment-page', false)
            ->assertSee('repeat-payment-search', false)
            ->assertSee('repeat-payment-table-scroll', false)
            ->assertSee('Per page')
            ->assertSee('sweetalert2', false)
            ->assertDontSee('alert(', false)
            ->assertDontSee('confirm(', false);
    }

    public function test_search_requires_student(): void
    {
        $this->actingAs($this->actor)
            ->postJson(route('repeat.payment.search'), [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_unknown_student_returns_not_found(): void
    {
        $this->actingAs($this->actor)
            ->postJson(route('repeat.payment.search'), [
                'student_nic' => '000000000V',
            ])
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_can_search_by_nic_or_student_id(): void
    {
        $setup = $this->makeRegisteredStudent();

        $this->actingAs($this->actor)
            ->postJson(route('repeat.payment.search'), [
                'student_nic' => $setup['student']->id_value,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('student.full_name', 'Test Student Full')
            ->assertJsonPath('courses.0.course_id', $setup['course']->course_id);

        $this->actingAs($this->actor)
            ->postJson(route('repeat.payment.search'), [
                'student_nic' => (string) $setup['student']->student_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('courses.0.course_id', $setup['course']->course_id);
    }

    public function test_plan_dates_are_plain_sri_lanka_dates(): void
    {
        $setup = $this->makeRegisteredStudent();
        $plan = StudentPaymentPlan::forceCreate([
            'student_id' => $setup['student']->student_id,
            'course_id' => $setup['course']->course_id,
            'payment_plan_type' => 'installments',
            'status' => 'archived',
            'total_amount' => 45000,
            'final_amount' => 45000,
        ]);
        PaymentInstallment::forceCreate([
            'payment_plan_id' => $plan->id,
            'installment_number' => 1,
            'due_date' => '2026-02-03',
            'amount' => 45000,
            'base_amount' => 45000,
            'final_amount' => 45000,
            'status' => 'pending',
        ]);

        $this->actingAs($this->actor)
            ->getJson(route('repeat.payment.plan', [
                'student_id' => $setup['student']->student_id,
                'course_id' => $setup['course']->course_id,
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('archived_installments.0.due_date', '2026-02-03');
    }

    public function test_save_creates_plan_and_archives_previous_active_plan(): void
    {
        $setup = $this->makeRegisteredStudent();
        $existing = StudentPaymentPlan::forceCreate([
            'student_id' => $setup['student']->student_id,
            'course_id' => $setup['course']->course_id,
            'payment_plan_type' => 'installments',
            'status' => 'active',
            'total_amount' => 10000,
            'final_amount' => 10000,
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('repeat.payment.save'), [
                'student_id' => $setup['student']->student_id,
                'course_id' => $setup['course']->course_id,
                'installments' => [
                    [
                        'due_date' => '2026-03-01',
                        'local_amount' => 25000,
                        'international_amount' => 0,
                        'currency' => 'LKR',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('archived', $existing->fresh()->status);
        $this->assertDatabaseHas('student_payment_plans', [
            'student_id' => $setup['student']->student_id,
            'course_id' => $setup['course']->course_id,
            'status' => 'active',
        ]);
    }

    private function makeRegisteredStudent(): array
    {
        $student = Student::forceCreate([
            'title' => 'Mr',
            'name_with_initials' => 'T. Student',
            'full_name' => 'Test Student Full',
            'id_type' => 'NIC',
            'id_value' => '199012345V',
            'gender' => 'Male',
            'email' => 'repeat-payment@test.lk',
            'status' => 'Registered',
            'academic_status' => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);

        $course = Course::forceCreate([
            'course_name' => 'BTEC Computing',
            'course_type' => 'degree',
            'location' => 'Welisara',
            'no_of_semesters' => 8,
            'duration' => '4 years',
            'min_credits' => 120,
            'course_medium' => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by' => 0,
        ]);

        $intake = Intake::forceCreate([
            'location' => 'Welisara',
            'course_id' => $course->course_id,
            'course_name' => $course->course_name,
            'batch' => '2024-JUL-B08',
            'batch_size' => 30,
            'intake_mode' => 'Physical',
            'intake_type' => 'Fulltime',
            'registration_fee' => '5000',
            'franchise_payment' => '0',
            'course_fee' => '50000',
            'start_date' => now()->subMonth()->toDateString(),
            'end_date' => now()->addYears(2)->toDateString(),
        ]);

        CourseRegistration::forceCreate([
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'intake_id' => $intake->intake_id,
            'status' => 'Registered',
            'approval_status' => 'Approved by manager',
            'location' => 'Welisara',
            'registration_date' => now()->toDateString(),
        ]);

        return compact('student', 'course');
    }
}
