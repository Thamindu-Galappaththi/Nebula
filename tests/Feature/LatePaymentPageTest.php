<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\PaymentDetail;
use App\Models\PaymentInstallment;
use App\Models\Student;
use App\Models\StudentPaymentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LatePaymentPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name' => 'Bursar',
            'email' => 'late-payment@nebula.lk',
            'password' => Hash::make('password'),
            'user_role' => 'Bursar',
            'status' => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_responsive_layout_and_pagination(): void
    {
        $this->actingAs($this->actor)
            ->get(route('late.payment.index'))
            ->assertOk()
            ->assertSee('Late Payment Management')
            ->assertSee('late-payment-page', false)
            ->assertSee('late-payment-search', false)
            ->assertSee('late-payment-table-scroll', false)
            ->assertSee('Per page')
            ->assertSee('col-12 col-lg-6', false)
            ->assertDontSee('col-sm-2 col-form-label', false)
            ->assertDontSee('alert(', false)
            ->assertDontSee('confirm(', false);
    }

    public function test_unknown_student_returns_not_found(): void
    {
        $this->actingAs($this->actor)
            ->postJson(route('late.payment.get.student.courses'), [
                'student_nic' => '000000000V',
            ])
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_payment_plan_requires_student_and_course(): void
    {
        $this->actingAs($this->actor)
            ->postJson(route('late.payment.get.payment.plan'), [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_days_late_is_a_whole_number_of_calendar_days(): void
    {
        $setup = $this->makeLateSetup(now()->subDays(240)->toDateString());

        $response = $this->actingAs($this->actor)
            ->postJson(route('late.payment.get.payment.plan'), [
                'student_nic' => $setup['student']->id_value,
                'course_id' => $setup['course']->course_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment_plan.installments.0.is_late', true);

        $daysLate = $response->json('payment_plan.installments.0.days_late');

        $this->assertSame(240, $daysLate);
        $this->assertIsInt($daysLate);
        $this->assertDoesNotMatchRegularExpression('/\./', (string) $daysLate);
    }

    public function test_can_lookup_student_by_student_id_and_excludes_misc_payments(): void
    {
        $setup = $this->makeLateSetup(now()->subDays(10)->toDateString());

        PaymentDetail::forceCreate([
            'student_id' => $setup['student']->student_id,
            'course_registration_id' => $setup['registration']->id,
            'amount' => 25000,
            'total_fee' => 25000,
            'remaining_amount' => 0,
            'installment_type' => 'course_fee',
            'installment_number' => 1,
            'status' => 'paid',
            'payment_method' => 'cash',
            'due_date' => now()->subDays(20)->toDateString(),
            'payment_effective_date' => now()->subDays(5)->toDateString(),
        ]);

        PaymentDetail::forceCreate([
            'student_id' => $setup['student']->student_id,
            'course_registration_id' => $setup['registration']->id,
            'misc_category' => 'Library Fine',
            'amount' => 500,
            'total_fee' => 500,
            'remaining_amount' => 0,
            'status' => 'paid',
            'payment_method' => 'cash',
        ]);

        $courses = $this->actingAs($this->actor)
            ->postJson(route('late.payment.get.student.courses'), [
                'student_nic' => (string) $setup['student']->student_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('courses.0.course_id', $setup['course']->course_id);

        $paid = $this->actingAs($this->actor)
            ->postJson(route('late.payment.get.paid.payments'), [
                'student_nic' => (string) $setup['student']->student_id,
                'course_id' => $setup['course']->course_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'paid_payments');

        $this->assertSame(15, $paid->json('paid_payments.0.days_late'));
        $this->assertIsInt($paid->json('paid_payments.0.days_late'));
    }

    private function makeLateSetup(string $dueDate): array
    {
        $student = Student::forceCreate([
            'title' => 'Mr',
            'name_with_initials' => 'T. Student',
            'full_name' => 'Test Student Full',
            'id_type' => 'NIC',
            'id_value' => '199012345V',
            'gender' => 'Male',
            'email' => '199012345V@test.lk',
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

        $registration = CourseRegistration::forceCreate([
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'intake_id' => $intake->intake_id,
            'status' => 'Registered',
            'approval_status' => 'Approved by manager',
            'location' => 'Welisara',
            'registration_date' => now()->toDateString(),
        ]);

        $plan = StudentPaymentPlan::forceCreate([
            'student_id' => $student->student_id,
            'course_id' => $course->course_id,
            'payment_plan_type' => 'installment',
            'total_amount' => 50000,
            'final_amount' => 50000,
            'status' => 'active',
        ]);

        PaymentInstallment::forceCreate([
            'payment_plan_id' => $plan->id,
            'installment_number' => 1,
            'due_date' => $dueDate,
            'amount' => 25000,
            'base_amount' => 25000,
            'final_amount' => 25000,
            'status' => 'pending',
        ]);

        return compact('student', 'course', 'registration', 'plan');
    }
}
