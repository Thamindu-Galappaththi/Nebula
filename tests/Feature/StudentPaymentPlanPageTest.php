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

class StudentPaymentPlanPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name' => 'Bursar',
            'email' => 'student-payment-plan@nebula.lk',
            'password' => Hash::make('password'),
            'user_role' => 'Bursar',
            'status' => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_pagination_sweetalert_and_responsive_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('payment.index'))
            ->assertOk()
            ->assertSee('Student Payment Plan')
            ->assertSee('payment-page-tabs', false)
            ->assertSee('payment-rate-group', false)
            ->assertSee('combo-control', false)
            ->assertSee('paymentRecordsPaginationBar', false)
            ->assertSee('Per page')
            ->assertSee('sweetalert2', false)
            ->assertSee('confirmDelete', false)
            ->assertDontSee('alert(', false)
            ->assertDontSee('confirm(', false)
            ->assertSee('id="toastContainer"', false)
            ->assertSee('z-index: 2000', false)
            ->assertSee('top: calc(70px + 16px)', false)
            ->assertSee('Tick an installment in the Payment Details table below', false)
            ->assertSee('Select column', false)
            ->assertSee('Conversion Rate', false)
            ->assertSee('SSCL (LKR)', false)
            ->assertSee('Bank Charges', false)
            ->assertSee('ssclTaxFormulaHint', false)
            ->assertSee('Slip exists - Generate reopens the same receipt', false)
            ->assertSee('franchiseLkrBreakdownHtml', false)
            ->assertSee('Enter Student NIC first', false)
            ->assertSee('Enter Student ID / NIC first', false)
            ->assertSee('scheduleStudentCourseLoad', false)
            ->assertSee('bindStudentNicCourseLoader', false)
            ->assertDontSee('Courses loaded successfully!', false)
            ->assertDontSee("showWarningMessage('Select a course.')", false)
            ->assertDontSee("showWarningMessage('Select a payment type.')", false)
            ->assertDontSee('Please select an installment first.', false)
            ->assertSee('function studentLookupReady', false)
            ->assertDontSee("showErrorMessage(data.message || 'Failed to load courses.')", false)
            ->assertDontSee("showErrorMessage('An error occurred while loading courses.')", false);
    }

    public function test_franchise_details_derive_conversion_rate_when_it_was_not_stored(): void
    {
        $setup = $this->makeFranchiseStudent();

        $this->actingAs($this->actor)
            ->postJson(route('payment.get.payment.details'), [
                'student_id' => $setup['student']->id_value,
                'course_id' => $setup['course']->course_id,
                'payment_type' => 'franchise_fee',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment_details.0.installment_number', 2)
            ->assertJsonPath('payment_details.0.lkr_amount', 452672)
            ->assertJsonPath('payment_details.0.conversion_rate', 411.52)
            ->assertJsonPath('payment_details.0.sscl_tax', 2.56)
            ->assertJsonPath('payment_details.0.sscl_percent', 2.56)
            ->assertJsonPath('payment_details.0.sscl_tax_amount', 11588.4);
    }

    public function test_franchise_details_do_not_treat_stored_sscl_lkr_as_percent(): void
    {
        $setup = $this->makeFranchiseStudent([
            'sscl_tax_amount' => 34668544,
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('payment.get.payment.details'), [
                'student_id' => $setup['student']->id_value,
                'course_id' => $setup['course']->course_id,
                'payment_type' => 'franchise_fee',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payment_details.0.sscl_percent', 2.56)
            ->assertJsonPath('payment_details.0.sscl_tax', 2.56)
            ->assertJsonPath('payment_details.0.sscl_tax_amount', 11588.4);
    }

    public function test_student_course_dropdown_loads_only_registered_courses(): void
    {
        $setup = $this->makeFranchiseStudent();
        Course::forceCreate([
            'course_name' => 'Unrelated Diploma Course',
            'course_type' => 'diploma',
            'location' => 'Welisara',
            'no_of_semesters' => 2,
            'duration' => '1 year',
            'min_credits' => 30,
            'course_medium' => 'English',
            'entry_qualification' => 'O/L',
            'conducted_by' => 0,
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('payment.get.student.courses'), [
                'student_nic' => $setup['student']->id_value,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'courses')
            ->assertJsonPath('courses.0.course_id', $setup['course']->course_id)
            ->assertJsonPath('courses.0.course_name', $setup['course']->course_name);
    }

    private function makeFranchiseStudent(array $paymentAttrs = []): array
    {
        $student = Student::forceCreate([
            'title' => 'Mr',
            'name_with_initials' => 'R. Student',
            'full_name' => 'Robotics Student',
            'id_type' => 'NIC',
            'id_value' => '200459813976',
            'gender' => 'Male',
            'email' => 'franchise-rate@test.lk',
            'status' => 'Registered',
            'academic_status' => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ]);

        $course = Course::forceCreate([
            'course_name' => 'BEng (Hons) in Robotics and AI',
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
            'batch' => '2024-JUL-UH',
            'batch_size' => 30,
            'intake_mode' => 'Physical',
            'intake_type' => 'Fulltime',
            'registration_fee' => '5000',
            'franchise_payment' => '1',
            'course_fee' => '50000',
            'sscl_tax' => 2.56,
            'bank_charges' => 1000,
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
            'payment_plan_type' => 'installments',
            'status' => 'active',
            'total_amount' => 1100,
            'final_amount' => 1100,
        ]);

        PaymentInstallment::forceCreate([
            'payment_plan_id' => $plan->id,
            'installment_number' => 2,
            'due_date' => '2025-07-19',
            'amount' => 0,
            'international_amount' => 1100,
            'international_currency' => 'GBP',
            'status' => 'paid',
        ]);

        PaymentDetail::forceCreate(array_merge([
            'student_id' => $student->student_id,
            'course_registration_id' => $registration->id,
            'amount' => 452672,
            'total_fee' => 465260.40,
            'remaining_amount' => 0,
            'installment_type' => 'franchise_fee',
            'installment_number' => 2,
            'status' => 'paid',
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'RCP202604270027',
            'foreign_currency_code' => 'GBP',
            'foreign_currency_amount' => 1100,
            'conversion_rate' => null,
            'sscl_tax_amount' => 11588.40,
            'bank_charges' => 1000,
        ], $paymentAttrs));

        return compact('student', 'course', 'intake', 'registration');
    }
}
