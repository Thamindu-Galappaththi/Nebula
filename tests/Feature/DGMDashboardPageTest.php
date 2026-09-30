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

class DGMDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    private User $dgm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dgm = User::forceCreate([
            'name'          => 'DGM User',
            'email'         => 'dgm-dashboard@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'DGM',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_students_revenues_and_outstanding_tabs_have_clear_filters(): void
    {
        $this->actingAs($this->dgm)
            ->get(route('dgmdashboard'))
            ->assertOk()
            ->assertSee('Apply Filters')
            ->assertSee('Clear Filters')
            ->assertSee('id="clearStudentFiltersBtn"', false)
            ->assertSee('id="clearRevenueFiltersBtn"', false)
            ->assertSee('id="clearOutstandingFiltersBtn"', false)
            ->assertSee('clearStudentFilters', false)
            ->assertSee('clearRevenueFilters', false)
            ->assertSee('clearOutstandingFilters', false)
            ->assertSee('dashboard-filter-actions', false)
            ->assertSee('bg-gray-600 text-white', false)
            ->assertSee('rows.map(function (row) { return row.name; })', false)
            ->assertSee('rows.map(function (row) { return row.value; })', false)
            ->assertSee('dgm-dashboard-page', false)
            ->assertSee('dgm-kpi-grid', false)
            ->assertSee('dgm-chart-box', false)
            ->assertSee('body:has(.dgm-dashboard-page)', false)
            ->assertSee('.dgm-dashboard-page .overflow-x-auto table', false)
            ->assertDontSee('width: max-content', false);
    }

    public function test_marketing_survey_labels_match_counts_after_splitting_sources(): void
    {
        $this->makeStudent('199011111V', 'Facebook');
        $this->makeStudent('199022222V', 'LinkedIn, Facebook');
        $this->makeStudent('199033333V', 'Radio Advertisement');
        $this->makeStudent('199044444V', '');

        $response = $this->actingAs($this->dgm)
            ->get('/api/dashboard/marketing-data?year=' . date('Y'))
            ->assertOk()
            ->assertJsonStructure(['labels', 'counts']);

        $labels = $response->json('labels');
        $counts = $response->json('counts');

        $this->assertSame(count($labels), count($counts));
        $this->assertSame($labels, array_values($labels));
        $this->assertSame($counts, array_values($counts));

        $mapped = array_combine($labels, $counts);
        $this->assertSame(2, $mapped['Facebook']);
        $this->assertSame(1, $mapped['LinkedIn']);
        $this->assertSame(1, $mapped['Radio Advertisement']);
        $this->assertArrayNotHasKey('LinkedIn, Facebook', $mapped);
        $this->assertArrayNotHasKey('', $mapped);
    }

    public function test_overview_yearly_revenue_includes_mid_year_payments(): void
    {
        $student = $this->makeStudent('199055555V', 'Facebook');
        $year = (int) date('Y');

        PaymentDetail::forceCreate([
            'student_id'             => $student->student_id,
            'course_registration_id' => null,
            'amount'                 => 12345.50,
            'total_fee'              => 12345.50,
            'remaining_amount'       => 0,
            'status'                 => 'paid',
            'payment_method'         => 'cash',
            'payment_effective_date' => "{$year}-06-15",
            'created_at'             => "{$year}-06-15 10:00:00",
        ]);

        $this->actingAs($this->dgm)
            ->getJson('/api/dashboard/overview?year=' . $year . '&location=all&course=all')
            ->assertOk()
            ->assertJsonPath('yearlyRevenue', '12,345.50');
    }

    public function test_overview_due_this_year_excludes_paid_installments_and_collections(): void
    {
        $student = $this->makeStudent('199066666V', 'Facebook');
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
        $year = (int) date('Y');
        $plan = StudentPaymentPlan::forceCreate([
            'student_id'        => $student->student_id,
            'course_id'         => $course->course_id,
            'payment_plan_type' => 'installments',
            'total_amount'      => 80000,
            'final_amount'      => 80000,
            'status'            => 'active',
        ]);

        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 1,
            'due_date'           => "{$year}-03-01",
            'amount'             => 10000,
            'final_amount'       => 10000,
            'status'             => 'pending',
        ]);
        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 2,
            'due_date'           => "{$year}-06-01",
            'amount'             => 20000,
            'final_amount'       => 20000,
            'status'             => 'paid',
        ]);
        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 3,
            'due_date'           => ($year - 1) . '-11-01',
            'amount'             => 15000,
            'final_amount'       => 15000,
            'status'             => 'pending',
        ]);

        PaymentDetail::forceCreate([
            'student_id'             => $student->student_id,
            'course_registration_id' => null,
            'amount'                 => 50000,
            'total_fee'              => 50000,
            'remaining_amount'       => 0,
            'status'                 => 'paid',
            'payment_method'         => 'cash',
            'payment_effective_date' => "{$year}-06-15",
            'created_at'             => "{$year}-06-15 10:00:00",
        ]);

        $this->actingAs($this->dgm)
            ->getJson('/api/dashboard/overview?year=' . $year . '&location=all&course=all')
            ->assertOk()
            ->assertJsonPath('outstandingCurrentYear', '10,000.00');
    }

    public function test_overview_revenue_summary_matches_campus_payments_and_unpaid_dues(): void
    {
        $year = (int) date('Y');
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

        $welisara = $this->makeStudent('199077777V', 'Facebook', 'Nebula Institute of Technology - Welisara');
        $moratuwa = $this->makeStudent('199088888V', 'Facebook', 'Moratuwa');

        $this->makePaidPayment($welisara, 1000, "{$year}-04-10");
        $this->makePaidPayment($welisara, 400, ($year - 1) . '-08-20');
        $this->makePaidPayment($moratuwa, 2500, "{$year}-05-12");

        \Illuminate\Support\Facades\DB::table('bulk_revenue_uploads')->insert([
            'year'       => $year,
            'location'   => 'Nebula Institute of Technology – Welisara',
            'revenue'    => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $welisaraPlan = StudentPaymentPlan::forceCreate([
            'student_id'        => $welisara->student_id,
            'course_id'         => $course->course_id,
            'payment_plan_type' => 'installments',
            'total_amount'      => 80000,
            'final_amount'      => 80000,
            'status'            => 'active',
        ]);
        $moratuwaPlan = StudentPaymentPlan::forceCreate([
            'student_id'        => $moratuwa->student_id,
            'course_id'         => $course->course_id,
            'payment_plan_type' => 'installments',
            'total_amount'      => 30000,
            'final_amount'      => 30000,
            'status'            => 'active',
        ]);

        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $welisaraPlan->id,
            'installment_number' => 1,
            'due_date'           => "{$year}-03-01",
            'amount'             => 7000,
            'final_amount'       => 7000,
            'status'             => 'pending',
        ]);
        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $welisaraPlan->id,
            'installment_number' => 2,
            'due_date'           => "{$year}-09-01",
            'amount'             => 90000,
            'final_amount'       => 90000,
            'status'             => 'paid',
        ]);
        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $moratuwaPlan->id,
            'installment_number' => 1,
            'due_date'           => ($year - 1) . '-11-01',
            'amount'             => 3000,
            'final_amount'       => 3000,
            'status'             => 'overdue',
        ]);

        $response = $this->actingAs($this->dgm)
            ->getJson('/api/dashboard/overview?year=' . $year . '&location=all&course=all')
            ->assertOk();

        $summary = collect($response->json('locationSummary'))->keyBy('location');

        $this->assertSame('1,500.00', $summary['Welisara']['current_year']);
        $this->assertSame('400.00', $summary['Welisara']['previous_year']);
        $this->assertSame('7,000.00', $summary['Welisara']['outstanding']);
        $this->assertEquals(275, $summary['Welisara']['growth']);

        $this->assertSame('2,500.00', $summary['Moratuwa']['current_year']);
        $this->assertSame('0.00', $summary['Moratuwa']['previous_year']);
        $this->assertSame('3,000.00', $summary['Moratuwa']['outstanding']);

        $this->assertSame('0.00', $summary['Peradeniya']['current_year']);
        $this->assertSame('0.00', $summary['Peradeniya']['outstanding']);

        $response
            ->assertJsonPath('yearlyRevenue', '4,000.00')
            ->assertJsonPath('outstanding', '10,000.00')
            ->assertJsonPath('outstandingCurrentYear', '7,000.00');
    }

    public function test_revenues_tab_includes_campus_variants_course_payments_and_misc(): void
    {
        $year = (int) date('Y');
        $course = $this->makeDashboardCourse();
        $student = $this->makeStudent('199099999V', 'Facebook', 'Nebula Institute of Technology - Welisara');
        $registration = $this->makeDashboardRegistration($student, $course);

        $this->makePaidPayment($student, 1111, "{$year}-04-10", $registration->id);
        $this->makePaidPayment($student, 222, "{$year}-05-01");

        \Illuminate\Support\Facades\DB::table('bulk_revenue_uploads')->insert([
            'year'       => $year,
            'location'   => 'Nebula Institute of Technology – Welisara',
            'course'     => $course->course_name,
            'revenue'    => 333,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $rows = $this->actingAs($this->dgm)
            ->getJson('/api/dashboard/revenue-by-year-course?year=' . $year . '&location=all&course=all')
            ->assertOk()
            ->json();

        $byCourse = collect($rows)->where('location', 'Welisara')->keyBy('course_name');

        $this->assertEquals(1444, $byCourse['BTEC Computing']['revenue']);
        $this->assertEquals(222, $byCourse['Miscellaneous']['revenue']);
    }

    public function test_outstanding_tab_uses_unpaid_student_installments_not_course_templates(): void
    {
        $year = (int) date('Y');
        $course = $this->makeDashboardCourse();
        $student = $this->makeStudent('199101010V', 'Facebook', 'Nebula Institute of Technology - Welisara');
        $plan = StudentPaymentPlan::forceCreate([
            'student_id'        => $student->student_id,
            'course_id'         => $course->course_id,
            'payment_plan_type' => 'installments',
            'total_amount'      => 20000,
            'final_amount'      => 20000,
            'status'            => 'active',
        ]);

        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 1,
            'due_date'           => ($year - 1) . '-11-01',
            'amount'             => 4444,
            'final_amount'       => 4444,
            'status'             => 'overdue',
        ]);
        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 2,
            'due_date'           => "{$year}-09-01",
            'amount'             => 8888,
            'final_amount'       => 8888,
            'status'             => 'paid',
        ]);

        $rows = $this->actingAs($this->dgm)
            ->getJson('/api/dashboard/outstanding-by-year-course?location=all&course=all')
            ->assertOk()
            ->json();

        $welisara = collect($rows)->firstWhere('location', 'Welisara');

        $this->assertNotNull($welisara);
        $this->assertSame('BTEC Computing', $welisara['course_name']);
        $this->assertEquals(4444, $welisara['outstanding']);
    }

    public function test_students_by_location_merges_campus_name_variants(): void
    {
        $this->makeStudent('199111111V', 'Facebook', 'Nebula Institute of Technology - Welisara');
        $this->makeStudent('199122222V', 'Facebook', 'Welisara');
        $this->makeStudent('199133333V', 'Facebook', 'Moratuwa');

        $rows = collect($this->actingAs($this->dgm)
            ->getJson('/api/dashboard/students-by-location')
            ->assertOk()
            ->json())
            ->keyBy('institute_location');

        $this->assertSame(2, $rows['Welisara']['count']);
        $this->assertSame(1, $rows['Moratuwa']['count']);
        $this->assertSame(0, $rows['Peradeniya']['count']);
    }

    public function test_students_tab_counts_registrations_for_campus_name_variants(): void
    {
        $year = (int) date('Y');
        $course = $this->makeDashboardCourse();
        $student = $this->makeStudent('199144444V', 'Facebook', 'Nebula Institute of Technology - Welisara');
        $this->makeDashboardRegistration($student, $course);

        $rows = $this->actingAs($this->dgm)
            ->getJson('/api/dashboard/students-data?year=' . $year . '&location=all&course=all')
            ->assertOk()
            ->json();

        $welisara = collect($rows)
            ->where('institute_location', 'Welisara')
            ->where('course_name', 'BTEC Computing')
            ->sum('count');

        $this->assertSame(1, (int) $welisara);
    }

    private function makePaidPayment(Student $student, float $amount, string $date, ?int $registrationId = null): PaymentDetail
    {
        return PaymentDetail::forceCreate([
            'student_id'             => $student->student_id,
            'course_registration_id' => $registrationId,
            'amount'                 => $amount,
            'total_fee'              => $amount,
            'remaining_amount'       => 0,
            'status'                 => 'paid',
            'payment_method'         => 'cash',
            'payment_effective_date' => $date,
            'created_at'             => $date . ' 10:00:00',
        ]);
    }

    private function makeStudent(string $nic, ?string $survey, string $location = 'Welisara'): Student
    {
        return Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'T. Student',
            'full_name'          => 'Test Student ' . $nic,
            'id_type'            => 'National id',
            'id_value'           => $nic,
            'gender'             => 'Male',
            'email'              => $nic . '@test.lk',
            'status'             => 'Unmarried',
            'institute_location' => $location,
            'marketing_survey'   => $survey,
        ]);
    }

    private function makeDashboardCourse(): Course
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

    private function makeDashboardRegistration(Student $student, Course $course): CourseRegistration
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
