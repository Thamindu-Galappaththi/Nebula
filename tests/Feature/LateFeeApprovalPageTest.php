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

class LateFeeApprovalPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'latefee@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_with_mobile_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('latefee.approval.index'))
            ->assertOk()
            ->assertSee('Late Fee Approval')
            ->assertSee('late-fee-approval-page', false)
            ->assertSee('late-fee-table', false)
            ->assertSee('col-md-2 col-form-label', false)
            ->assertDontSee('Feature Coming Soon');
    }

    public function test_index_query_redirects_to_approval_page(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->makeRegistration($student, $course);

        $this->actingAs($this->actor)
            ->get(route('latefee.approval.index', [
                'student_nic' => $student->id_value,
                'course_id' => $course->course_id,
            ]))
            ->assertRedirect(route('latefee.approval.page', [
                'studentNic' => $student->id_value,
                'courseId' => $course->course_id,
            ]));
    }

    public function test_courses_for_nic_include_campus_location_and_skip_missing_courses(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->makeRegistration($student, $course);

        $this->actingAs($this->actor)
            ->postJson(route('latefee.get.courses'), [
                'student_nic' => $student->id_value,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('courses.0.course_id', $course->course_id)
            ->assertJsonPath('courses.0.location', 'Welisara')
            ->assertSee('Nebula Institute of Technology - Welisara', false);
    }

    public function test_approval_page_shows_overdue_installment(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->makeRegistration($student, $course);
        $plan = StudentPaymentPlan::forceCreate([
            'student_id'        => $student->student_id,
            'course_id'         => $course->course_id,
            'payment_plan_type' => 'installment',
            'total_amount'      => 50000,
            'final_amount'      => 50000,
            'status'            => 'active',
        ]);
        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 1,
            'due_date'           => now()->subDays(10)->toDateString(),
            'amount'             => 25000,
            'base_amount'        => 25000,
            'final_amount'       => 25000,
            'status'             => 'pending',
        ]);

        $this->actingAs($this->actor)
            ->get(route('latefee.approval.page', [
                'studentNic' => $student->id_value,
                'courseId' => $course->course_id,
            ]))
            ->assertOk()
            ->assertSee('Installment-wise Approval')
            ->assertSee('Global Reduction')
            ->assertSee('late-fee-action-form', false)
            ->assertSee('Approved fee (LKR)')
            ->assertSee($student->name_with_initials);
    }

    public function test_unknown_student_redirects_with_error(): void
    {
        $this->actingAs($this->actor)
            ->get(route('latefee.approval.page', [
                'studentNic' => '000000000V',
                'courseId' => 1,
            ]))
            ->assertRedirect(route('latefee.approval.index'));
    }

    public function test_paid_installment_cannot_be_approved(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->makeRegistration($student, $course);
        $plan = StudentPaymentPlan::forceCreate([
            'student_id'        => $student->student_id,
            'course_id'         => $course->course_id,
            'payment_plan_type' => 'installment',
            'total_amount'      => 50000,
            'final_amount'      => 50000,
            'status'            => 'active',
        ]);
        $paid = PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 1,
            'due_date'           => now()->subDays(10)->toDateString(),
            'amount'             => 25000,
            'base_amount'        => 25000,
            'final_amount'       => 25000,
            'status'             => 'paid',
        ]);

        $this->actingAs($this->actor)
            ->from(route('latefee.approval.page', [
                'studentNic' => $student->id_value,
                'courseId' => $course->course_id,
            ]))
            ->post(route('latefee.approve.installment', $paid->id), [
                'approved_late_fee' => 10,
                'approval_note' => 'should fail',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertNull($paid->fresh()->approved_late_fee);
    }

    public function test_global_reduction_skips_paid_and_applies_to_overdue(): void
    {
        $student = $this->makeStudent();
        $course = $this->makeCourse();
        $this->makeRegistration($student, $course);
        $plan = StudentPaymentPlan::forceCreate([
            'student_id'        => $student->student_id,
            'course_id'         => $course->course_id,
            'payment_plan_type' => 'installment',
            'total_amount'      => 50000,
            'final_amount'      => 50000,
            'status'            => 'active',
        ]);
        $overdue = PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 1,
            'due_date'           => now()->subDays(20)->toDateString(),
            'amount'             => 25000,
            'base_amount'        => 25000,
            'final_amount'       => 25000,
            'status'             => 'pending',
        ]);
        $paid = PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 2,
            'due_date'           => now()->subDays(10)->toDateString(),
            'amount'             => 25000,
            'base_amount'        => 25000,
            'final_amount'       => 25000,
            'status'             => 'paid',
        ]);

        $this->actingAs($this->actor)
            ->from(route('latefee.approval.page', [
                'studentNic' => $student->id_value,
                'courseId' => $course->course_id,
            ]))
            ->post(route('latefee.approve.global', [$student->id_value, $course->course_id]), [
                'reduction_amount' => 5,
                'approval_note' => 'global',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertEquals(5, (float) $overdue->fresh()->approved_late_fee);
        $this->assertNull($paid->fresh()->approved_late_fee);
    }

    private function makeStudent(): Student
    {
        return Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'T. Student',
            'full_name'          => 'Test Student Full',
            'id_type'            => 'NIC',
            'id_value'           => '199012345V',
            'gender'             => 'Male',
            'email'              => '199012345V@test.lk',
            'status'             => 'Registered',
            'academic_status'    => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
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

    private function makeRegistration(Student $student, Course $course): CourseRegistration
    {
        $intake = Intake::forceCreate([
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

        return CourseRegistration::forceCreate([
            'student_id'        => $student->student_id,
            'course_id'         => $course->course_id,
            'intake_id'         => $intake->intake_id,
            'status'            => 'Registered',
            'approval_status'   => 'Approved by manager',
            'location'          => 'Welisara',
            'registration_date' => now()->toDateString(),
        ]);
    }
}
