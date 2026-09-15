<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StatementDownloadPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name' => 'Bursar',
            'email' => 'statement-download@nebula.lk',
            'password' => Hash::make('password'),
            'user_role' => 'Bursar',
            'status' => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_responsive_layout_and_same_tab_download(): void
    {
        $this->actingAs($this->actor)
            ->get(route('payment.showDownloadPage'))
            ->assertOk()
            ->assertSee('Payment Statement')
            ->assertSee('statement-download-page', false)
            ->assertSee('statement-download-search', false)
            ->assertSee('Download PDF')
            ->assertSee('sweetalert2', false)
            ->assertDontSee('target="_blank"', false)
            ->assertDontSee('alert(', false)
            ->assertDontSee('window.open', false);
    }

    public function test_download_requires_student_and_course(): void
    {
        $this->actingAs($this->actor)
            ->postJson(route('payment.downloadStatement'), [])
            ->assertStatus(422);
    }

    public function test_unknown_student_returns_not_found(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->actor)
            ->postJson(route('payment.downloadStatement'), [
                'student_nic' => '000000000V',
                'course_id' => $course->course_id,
            ])
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_can_load_courses_by_nic_or_student_id(): void
    {
        $setup = $this->makeRegisteredStudent();

        $this->actingAs($this->actor)
            ->postJson(route('payment.get.student.courses'), [
                'student_nic' => $setup['student']->id_value,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('courses.0.course_id', $setup['course']->course_id)
            ->assertJsonPath('courses.0.registration_date', '2025-12-23');

        $this->actingAs($this->actor)
            ->postJson(route('payment.get.student.courses'), [
                'student_nic' => (string) $setup['student']->student_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('courses.0.course_id', $setup['course']->course_id)
            ->assertJsonPath('courses.0.registration_date', '2025-12-23');
    }

    public function test_download_returns_pdf_attachment_without_redirect(): void
    {
        $setup = $this->makeRegisteredStudent();

        $this->actingAs($this->actor)
            ->postJson(route('payment.downloadStatement'), [
                'student_nic' => $setup['student']->id_value,
                'course_id' => $setup['course']->course_id,
            ])
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_statement_pdf_keeps_amount_values_in_their_columns(): void
    {
        $installment = new \App\Models\PaymentInstallment([
            'installment_number' => 1,
            'due_date' => '2026-02-03',
            'amount' => 45000,
            'base_amount' => 45000,
            'discount_amount' => 2250,
            'slt_loan_amount' => 0,
            'final_amount' => 42750,
        ]);
        $plan = new \App\Models\StudentPaymentPlan();
        $plan->setRelation('installments', collect([$installment]));

        $html = view('payments.payment_statement', [
            'student' => ['name' => 'Test Student', 'id' => 1, 'nic' => '199012345V'],
            'course' => ['name' => 'CAIT', 'code' => 'CAIT', 'intake' => 'B08', 'registration_date' => '2025-12-23'],
            'payments' => [],
            'totals' => ['total_amount' => 0, 'total_paid' => 0, 'total_remaining' => 0],
            'generated_date' => '2026-09-15 22:50:00',
            'paymentPlan' => $plan,
            'coursePlan' => (object) ['international_currency' => 'LKR'],
            'courseInstallments' => [
                [
                    'installment_number' => 1,
                    'due_date' => '2026-02-18',
                    'local_amount' => 15000,
                    'international_amount' => 0,
                ],
            ],
            'cspNonce' => 'test-nonce',
        ])->render();

        $this->assertStringContainsString('table-layout: fixed', $html);
        $this->assertStringContainsString('class="col-amount"', $html);
        $this->assertStringContainsString('45,000.00', $html);
        $this->assertStringContainsString('15,000.00', $html);
        $this->assertStringContainsString('Base Amount', $html);
        $this->assertStringContainsString('Local Amount (LKR)', $html);
        $this->assertStringNotContainsString('display: flex', $html);
        $this->assertStringNotContainsString('display: grid', $html);
    }

    public function test_download_rejects_unregistered_course(): void
    {
        $setup = $this->makeRegisteredStudent();
        $otherCourse = Course::forceCreate([
            'course_name' => 'Other Course',
            'course_type' => 'degree',
            'location' => 'Welisara',
            'no_of_semesters' => 8,
            'duration' => '4 years',
            'min_credits' => 120,
            'course_medium' => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by' => 0,
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('payment.downloadStatement'), [
                'student_nic' => $setup['student']->id_value,
                'course_id' => $otherCourse->course_id,
            ])
            ->assertNotFound()
            ->assertJsonPath('success', false);
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
            'email' => 'statement-student@test.lk',
            'status' => 'Registered',
            'academic_status' => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);

        $course = $this->makeCourse();

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
            'registration_date' => '2025-12-23',
        ]);

        return compact('student', 'course');
    }

    private function makeCourse(): Course
    {
        return Course::forceCreate([
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
    }
}
