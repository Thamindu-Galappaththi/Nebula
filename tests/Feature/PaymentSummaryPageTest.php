<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\PaymentDetail;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentSummaryPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Bursar',
            'email'         => 'summary@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Bursar',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_courses_by_location_return_data_array_for_filters(): void
    {
        $course = Course::forceCreate([
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

        $this->actingAs($this->actor)
            ->get(route('payment.summary.courses.by.location', [
                'location' => 'Welisara',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.course_id', $course->course_id)
            ->assertJsonPath('courses.0.course_name', 'BTEC Computing');
    }

    public function test_summary_script_does_not_contain_broken_try_catch(): void
    {
        $source = file_get_contents(resource_path('views/payments/summary.blade.php'));

        $this->assertStringNotContainsString(
            "courseFilter.appendChild(option);\n                });\n            }\n            courseFilter.disabled = false;",
            $source
        );
        $this->assertStringContainsString('courseListFromPayload(payload).forEach', $source);
        $this->assertStringContainsString("initChart('monthlyChart'", $source);
        $this->assertStringNotContainsString('height: auto !important', $source);
        $this->assertStringContainsString('From Collection Date', $source);
        $this->assertStringNotContainsString('From Due Date', $source);
    }

    public function test_period_totals_use_collection_date_and_converted_franchise_lkr(): void
    {
        $setup = $this->makePaymentStudent();

        // Collected in September: franchise still stored as USD in total_fee.
        PaymentDetail::forceCreate([
            'student_id'              => $setup['student']->student_id,
            'course_registration_id'  => $setup['registration']->id,
            'amount'                  => 100,
            'total_fee'               => 100,
            'remaining_amount'        => 0,
            'installment_type'        => 'franchise_fee',
            'status'                  => 'paid',
            'payment_method'          => 'bank_transfer',
            'foreign_currency_amount' => 100,
            'conversion_rate'         => 300,
            'sscl_tax_amount'         => 500,
            'bank_charges'            => 200,
            'late_fee'                => 0,
            'approved_late_fee'       => 0,
            'due_date'                => '2025-01-15',
            'payment_effective_date'  => '2026-09-05',
        ]);

        PaymentDetail::forceCreate([
            'student_id'             => $setup['student']->student_id,
            'course_registration_id' => $setup['registration']->id,
            'amount'                 => 10000,
            'total_fee'              => 10000,
            'remaining_amount'       => 0,
            'installment_type'       => 'course_fee',
            'status'                 => 'paid',
            'payment_method'         => 'cash',
            'due_date'               => '2025-01-15',
            'payment_effective_date' => '2026-09-12',
        ]);

        // Due in September but actually collected last year — must not count.
        PaymentDetail::forceCreate([
            'student_id'             => $setup['student']->student_id,
            'course_registration_id' => $setup['registration']->id,
            'amount'                 => 99999,
            'total_fee'              => 99999,
            'remaining_amount'       => 0,
            'installment_type'       => 'course_fee',
            'status'                 => 'paid',
            'payment_method'         => 'cash',
            'due_date'               => '2026-09-01',
            'payment_effective_date' => '2025-01-10',
        ]);

        $html = $this->actingAs($this->actor)
            ->get(route('payment.summary', [
                'start_date' => '2026-09-01',
                'end_date'   => '2026-09-30',
            ]))
            ->assertOk()
            ->assertDontSee('99,999.00')
            ->getContent();

        $this->assertStringContainsString('40,700.00', $html);
        $this->assertMatchesRegularExpression('/"type"\s*:\s*"Franchise Fee"[^}]*"total"\s*:\s*"?30700/', $html);
    }

    public function test_installment_kpi_converts_franchise_and_respects_collection_period(): void
    {
        $setup = $this->makePaymentStudent();

        PaymentDetail::forceCreate([
            'student_id'              => $setup['student']->student_id,
            'course_registration_id'  => $setup['registration']->id,
            'amount'                  => 100,
            'total_fee'               => 100,
            'remaining_amount'        => 0,
            'installment_type'        => 'franchise_fee',
            'status'                  => 'paid',
            'payment_method'          => 'bank_transfer',
            'foreign_currency_amount' => 100,
            'conversion_rate'         => 300,
            'sscl_tax_amount'         => 500,
            'bank_charges'            => 200,
            'due_date'                => '2025-01-15',
            'payment_effective_date'  => '2026-09-05',
        ]);

        PaymentDetail::forceCreate([
            'student_id'              => $setup['student']->student_id,
            'course_registration_id'  => $setup['registration']->id,
            'amount'                  => 280,
            'total_fee'               => 280,
            'remaining_amount'        => 0,
            'installment_type'        => 'franchise_fee',
            'status'                  => 'paid',
            'payment_method'          => 'bank_transfer',
            'foreign_currency_amount' => 280,
            'conversion_rate'         => 300,
            'sscl_tax_amount'         => 0,
            'bank_charges'            => 0,
            'due_date'                => '2026-09-20',
            'payment_effective_date'  => '2025-03-01',
        ]);

        $this->actingAs($this->actor)
            ->get(route('payment.summary.installment.kpi', [
                'payment_type' => 'franchise_fee',
                'start_date'   => '2026-09-01',
                'end_date'     => '2026-09-30',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('paid_total', 30700)
            ->assertJsonPath('paid_count', 1);
    }

    public function test_installment_pdf_exports_paid_pending_and_grand_total(): void
    {
        $setup = $this->makePaymentStudent();

        PaymentDetail::forceCreate([
            'student_id'             => $setup['student']->student_id,
            'course_registration_id' => $setup['registration']->id,
            'amount'                 => 15000,
            'total_fee'              => 15000,
            'remaining_amount'       => 0,
            'installment_type'       => 'course_fee',
            'installment_number'     => 1,
            'status'                 => 'paid',
            'payment_method'         => 'cash',
            'payment_effective_date' => '2026-09-10',
        ]);

        PaymentDetail::forceCreate([
            'student_id'             => $setup['student']->student_id,
            'course_registration_id' => $setup['registration']->id,
            'amount'                 => 20000,
            'total_fee'              => 20000,
            'remaining_amount'       => 20000,
            'installment_type'       => 'course_fee',
            'installment_number'     => 2,
            'status'                 => 'pending',
            'due_date'               => '2026-10-01',
        ]);

        foreach (['paid', 'pending', 'all'] as $status) {
            $response = $this->actingAs($this->actor)
                ->get(route('payment.summary.installment.pdf', [
                    'status' => $status,
                    'range' => '10y',
                ]));

            $response->assertOk()->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }

        $source = file_get_contents(resource_path('views/payments/summary.blade.php'));
        $this->assertStringContainsString('data-status="paid"', $source);
        $this->assertStringContainsString('data-status="pending"', $source);
        $this->assertStringContainsString('data-status="all"', $source);
        $this->assertStringContainsString('downloadKpiPdf(', $source);
        $this->assertStringContainsString('Preparing your PDF', $source);
        $this->assertStringContainsString('Swal.showLoading()', $source);
    }

    private function makePaymentStudent(): array
    {
        $student = Student::forceCreate([
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

        $course = Course::forceCreate([
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

        $intake = Intake::forceCreate([
            'location'          => 'Welisara',
            'course_id'         => $course->course_id,
            'course_name'       => $course->course_name,
            'batch'             => '2024-JUL-B08',
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => '280',
            'course_fee'        => '50000',
            'start_date'        => now()->subMonth()->toDateString(),
            'end_date'          => now()->addYears(2)->toDateString(),
        ]);

        $registration = CourseRegistration::forceCreate([
            'student_id'        => $student->student_id,
            'course_id'         => $course->course_id,
            'intake_id'         => $intake->intake_id,
            'status'            => 'Registered',
            'approval_status'   => 'Approved by manager',
            'location'          => 'Welisara',
            'registration_date' => now()->toDateString(),
        ]);

        return compact('student', 'course', 'intake', 'registration');
    }
}
