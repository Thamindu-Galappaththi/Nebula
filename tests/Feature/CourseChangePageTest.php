<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\PaymentDetail;
use App\Models\Student;
use App\Models\StudentPaymentPlan;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CourseChangePageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'coursechange@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_with_mobile_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('course.change.index'))
            ->assertOk()
            ->assertSee('Course / Intake Change')
            ->assertSee('course-change-page', false)
            ->assertSee('course-change-table', false)
            ->assertSee('modal-fullscreen-sm-down', false)
            ->assertSee('modal-fullscreen-md-down', false)
            ->assertSee('Nebula Institute of Technology - Welisara', false)
            ->assertSee('id="cancelledPaymentsModal"', false)
            ->assertSee('cancelled-payments-table', false)
            ->assertSee('data-label="Remarks"', false)
            ->assertSee('getOrCreateInstance', false)
            ->assertSee('cleanupOrphanedModalBackdrops', false)
            ->assertSee('showCancelledPayments({ refreshOnly: true })', false);
    }

    public function test_student_search_matches_nic_and_returns_registration(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $student = $this->makeStudent('199012345V');
        $this->makeCourseRegistration($student, $course, $intake, now()->subMonths(2)->toDateString());

        $this->actingAs($this->actor)
            ->postJson(route('course.change.find.student'), [
                'nic' => '199012345V',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('student.id_value', '199012345V')
            ->assertJsonPath('registrations.0.course.course_name', 'BTEC Computing')
            ->assertJsonPath('registrations.0.is_change_allowed', true);
    }

    public function test_student_search_does_not_match_unrelated_student_id(): void
    {
        $this->makeStudent('199012345V');

        $this->actingAs($this->actor)
            ->postJson(route('course.change.find.student'), [
                'nic' => '999',
            ])
            ->assertNotFound()
            ->assertJsonPath('status', 'error');
    }

    public function test_intakes_load_for_selected_course_location(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course, '2024-JUL-B08');
        $otherCourse = $this->makeCourse('BTEC Engineering', 'Moratuwa');
        $this->makeIntake($otherCourse, '2025-JAN-B01');

        $this->actingAs($this->actor)
            ->postJson(route('course.change.new.intakes'), [
                'course_id' => $course->course_id,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('intakes.0.intake_id', $intake->intake_id)
            ->assertJsonCount(1, 'intakes');
    }

    public function test_registration_older_than_one_year_can_still_be_changed(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $student = $this->makeStudent();
        $this->makeCourseRegistration($student, $course, $intake, now()->subYears(2)->toDateString());

        $this->actingAs($this->actor)
            ->postJson(route('course.change.find.student'), [
                'nic' => '199012345V',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('registrations.0.is_change_allowed', true);
    }

    public function test_submit_change_succeeds_when_payment_audit_table_has_no_tracking_columns(): void
    {
        $this->recreatePaymentAuditTableWithoutTracking();

        $oldCourse = $this->makeCourse('BTEC Computing');
        $newCourse = $this->makeCourse('B.Sc. Data Science');
        $oldIntake = $this->makeIntake($oldCourse, '2024-JUL-B08');
        $newIntake = $this->makeIntake($newCourse, '2026-Sep-B10-DS');
        $student = $this->makeStudent();
        $registration = $this->makeCourseRegistration(
            $student,
            $oldCourse,
            $oldIntake,
            now()->subMonths(2)->toDateString()
        );

        $plan = StudentPaymentPlan::forceCreate([
            'student_id' => $student->student_id,
            'course_id' => $oldCourse->course_id,
            'payment_plan_type' => 'installments',
            'status' => 'active',
            'total_amount' => 100000,
            'final_amount' => 100000,
        ]);

        PaymentDetail::forceCreate([
            'student_id' => $student->student_id,
            'course_registration_id' => $registration->id,
            'amount' => 100000,
            'status' => 'paid',
            'partial_payments' => [
                ['amount' => 100000, 'method' => 'Cash', 'date' => '2026-04-07'],
            ],
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('course.change.submit'), [
                'registration_id' => $registration->id,
                'new_intake_id' => $newIntake->intake_id,
                'new_course_registration_id' => 'UH-DS-15',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('payment_summary.has_payments', true);

        $this->assertDatabaseHas('course_registration', [
            'id' => $registration->id,
            'course_id' => $newCourse->course_id,
            'intake_id' => $newIntake->intake_id,
            'course_registration_id' => 'UH-DS-15',
        ]);

        $this->assertDatabaseHas('course_change_payments', [
            'student_id' => $student->student_id,
            'old_course_id' => $oldCourse->course_id,
            'old_payment_plan_id' => $plan->id,
        ]);
    }

    public function test_cancelled_payment_remarks_can_be_marked_pending(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $student = $this->makeStudent();
        $registration = $this->makeCourseRegistration($student, $course, $intake, now()->toDateString());

        $payment = PaymentDetail::forceCreate([
            'student_id' => $student->student_id,
            'course_registration_id' => $registration->id,
            'amount' => 175000,
            'status' => 'cancelled',
            'remarks' => 'Cancelled due to course change on 2026-10-29 09:32:35 | Paid amount: LKR 175,000.00',
        ]);

        $formattedUpdatedAt = $payment->fresh()->updated_at
            ->timezone(config('app.timezone'))
            ->format('Y-m-d H:i:s');

        $this->actingAs($this->actor)
            ->getJson(route('course.change.cancelled.payments', $student->student_id))
            ->assertOk()
            ->assertJsonPath('status', 'success')
            ->assertJsonPath('payments.0.id', $payment->id)
            ->assertJsonPath('payments.0.updated_at', $formattedUpdatedAt);

        $this->actingAs($this->actor)
            ->postJson(route('course.change.cancelled.payments.status', $payment->id), [
                'status' => 'pending',
            ])
            ->assertOk()
            ->assertJsonPath('status', 'success');

        $this->assertStringContainsString(
            'Pending the Payment Update',
            (string) $payment->fresh()->remarks
        );
    }

    private function makeStudent(string $idValue = '199012345V'): Student
    {
        return Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'T. Student',
            'full_name'          => 'Test Student Full',
            'id_type'            => 'NIC',
            'id_value'           => $idValue,
            'gender'             => 'Male',
            'email'              => $idValue . '@test.lk',
            'status'             => 'Registered',
            'academic_status'    => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);
    }

    private function makeCourse(string $name = 'BTEC Computing', string $location = 'Welisara'): Course
    {
        return Course::forceCreate([
            'course_name'         => $name,
            'course_type'         => 'degree',
            'location'            => $location,
            'no_of_semesters'     => 8,
            'duration'            => '4 years',
            'min_credits'         => 120,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 0,
        ]);
    }

    private function makeIntake(Course $course, string $batch = '2024-JUL-B08'): Intake
    {
        return Intake::forceCreate([
            'location'          => $course->location,
            'course_id'         => $course->course_id,
            'course_name'       => $course->course_name,
            'batch'             => $batch,
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

    private function makeCourseRegistration(Student $student, Course $course, Intake $intake, ?string $startDate): CourseRegistration
    {
        return CourseRegistration::forceCreate([
            'student_id'             => $student->student_id,
            'course_id'              => $course->course_id,
            'intake_id'              => $intake->intake_id,
            'status'                 => 'Registered',
            'approval_status'        => 'Approved by manager',
            'location'               => $course->location,
            'registration_date'      => now()->toDateString(),
            'course_start_date'      => $startDate,
            'course_registration_id' => 'CR-' . $student->id_value,
        ]);
    }

    private function recreatePaymentAuditTableWithoutTracking(): void
    {
        Schema::dropIfExists('course_change_payments');
        Schema::create('course_change_payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('old_course_id');
            $table->unsignedBigInteger('old_intake_id')->nullable();
            $table->unsignedBigInteger('old_payment_plan_id');
            $table->decimal('total_paid_amount', 12, 2)->default(0);
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }
}
