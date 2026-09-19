<?php

namespace Tests\Feature;

use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\ExamResult;
use App\Models\Intake;
use App\Models\Module;
use App\Models\ParentGuardian;
use App\Models\PaymentDetail;
use App\Models\PaymentInstallment;
use App\Models\Semester;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\StudentPaymentPlan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'admin@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    private function makeStudent(string $idValue, array $studentAttrs = []): Student
    {
        return Student::forceCreate(array_merge([
            'title'              => 'Mr',
            'name_with_initials' => 'Test Student',
            'full_name'          => 'Test Student Full',
            'id_type'            => 'NIC',
            'id_value'           => $idValue,
            'gender'             => 'Male',
            'email'              => $idValue . '@test.lk',
            'status'             => 'Registered',
            'academic_status'    => 'active',
            'institute_location' => 'Welisara',
            'birthday'           => '2000-01-15',
        ], $studentAttrs));
    }

    public function test_blank_profile_page_renders_without_student(): void
    {
        $this->actingAs($this->actor)
            ->get('/student/profile/0')
            ->assertOk()
            ->assertSee('Student Profile')
            ->assertSee('Enter NIC number')
            ->assertSee('statusHistoryCount', false)
            ->assertSee('No document uploaded', false)
            ->assertSee('clearanceDocumentCell', false)
            ->assertSee('/^(?:\\+94|94|0)?[1-9]\\d{8}$/', false)
            ->assertSee('class="form-control bg-danger text-white" id="parentEmergencyContact"', false)
            ->assertDontSee('$(\'#status-history-tab\').addClass(\'bg-danger text-white\')', false)
            ->assertDontSee('Trying to get property');
    }

    public function test_profile_page_renders_student_with_array_exam_subjects(): void
    {
        $student = $this->makeStudent('199012345V');
        StudentExam::forceCreate([
            'student_id'       => $student->student_id,
            'ol_exam_type'     => 'Local',
            'ol_exam_year'     => '2016',
            'ol_exam_subjects' => [
                ['subject' => 'Maths', 'result' => 'A'],
            ],
        ]);

        $this->actingAs($this->actor)
            ->get('/student/profile/' . $student->student_id)
            ->assertOk()
            ->assertSee('Test Student Full')
            ->assertSee('Maths')
            ->assertSee('2000-01-15');
    }

    public function test_profile_search_matches_nic_only(): void
    {
        $student = $this->makeStudent('199088877V');
        ParentGuardian::forceCreate([
            'student_id'               => $student->student_id,
            'guardian_name'            => 'Parent Name',
            'guardian_profession'      => 'Teacher',
            'guardian_contact_number'  => '0771234567',
            'guardian_email'           => 'parent@test.lk',
            'guardian_address'         => 'Colombo',
            'emergency_contact_number' => '0777654321',
        ]);

        $this->actingAs($this->actor)
            ->getJson('/api/student-details-by-nic?nic=' . $student->student_id)
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'No student profile found for this NIC.');

        $this->actingAs($this->actor)
            ->getJson('/api/student-details-by-nic?nic=199088877V')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('student.student_id', $student->student_id)
            ->assertJsonPath('student.parent.guardian_name', 'Parent Name')
            ->assertJsonPath('student.birthday', '2000-01-15');
    }

    public function test_unknown_nic_returns_not_found_message(): void
    {
        $this->actingAs($this->actor)
            ->getJson('/api/student-details-by-nic?nic=999999999V')
            ->assertNotFound()
            ->assertJsonPath('message', 'No student profile found for this NIC.');
    }

    public function test_payment_summary_does_not_add_registration_fee_twice_on_full_plans(): void
    {
        $setup = $this->makePaymentStudent();
        $plan = StudentPaymentPlan::forceCreate([
            'student_id'         => $setup['student']->student_id,
            'course_id'          => $setup['course']->course_id,
            'payment_plan_type'  => 'full',
            'total_amount'       => 45000,
            'final_amount'       => 42750,
            'status'             => 'active',
        ]);
        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 1,
            'due_date'           => '2026-02-18',
            'amount'             => 42750,
            'base_amount'        => 45000,
            'final_amount'       => 42750,
            'status'             => 'paid',
        ]);
        PaymentDetail::forceCreate([
            'student_id'              => $setup['student']->student_id,
            'course_registration_id'  => $setup['registration']->id,
            'amount'                  => 42750,
            'total_fee'               => 42750,
            'remaining_amount'        => 0,
            'installment_type'        => 'course_fee',
            'installment_number'      => 1,
            'status'                  => 'paid',
            'payment_method'          => 'Cash',
        ]);

        $this->actingAs($this->actor)
            ->getJson('/api/student/' . $setup['student']->student_id . '/course/' . $setup['course']->course_id . '/payment-summary')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.course_fee', 22750)
            ->assertJsonPath('summary.registration_fee', 20000)
            ->assertJsonPath('summary.total_local_amount', 42750)
            ->assertJsonPath('summary.local_outstanding', 0)
            ->assertJsonPath('summary.local_paid', 42750);
    }

    public function test_payment_summary_keeps_installment_course_fee_separate_from_registration(): void
    {
        $setup = $this->makePaymentStudent();
        $plan = StudentPaymentPlan::forceCreate([
            'student_id'         => $setup['student']->student_id,
            'course_id'          => $setup['course']->course_id,
            'payment_plan_type'  => 'full',
            'total_amount'       => 45000,
            'final_amount'       => 22750,
            'status'             => 'active',
        ]);
        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 1,
            'due_date'           => '2026-02-18',
            'amount'             => 15000,
            'base_amount'        => 15000,
            'final_amount'       => 15000,
            'status'             => 'paid',
        ]);
        PaymentInstallment::forceCreate([
            'payment_plan_id'    => $plan->id,
            'installment_number' => 2,
            'due_date'           => '2026-02-18',
            'amount'             => 7750,
            'base_amount'        => 10000,
            'final_amount'       => 7750,
            'status'             => 'paid',
        ]);
        PaymentDetail::forceCreate([
            'student_id'             => $setup['student']->student_id,
            'course_registration_id' => $setup['registration']->id,
            'amount'                 => 20000,
            'total_fee'              => 20000,
            'remaining_amount'       => 0,
            'installment_type'       => 'registration_fee',
            'status'                 => 'paid',
            'payment_method'         => 'Cash',
        ]);
        PaymentDetail::forceCreate([
            'student_id'             => $setup['student']->student_id,
            'course_registration_id' => $setup['registration']->id,
            'amount'                 => 15000,
            'total_fee'              => 15000,
            'remaining_amount'       => 0,
            'installment_type'       => 'course_fee',
            'installment_number'     => 1,
            'status'                 => 'paid',
            'payment_method'         => 'Cash',
        ]);
        PaymentDetail::forceCreate([
            'student_id'             => $setup['student']->student_id,
            'course_registration_id' => $setup['registration']->id,
            'amount'                 => 7750,
            'total_fee'              => 7750,
            'remaining_amount'       => 0,
            'installment_type'       => 'course_fee',
            'installment_number'     => 2,
            'status'                 => 'paid',
            'payment_method'         => 'Cash',
        ]);

        $this->actingAs($this->actor)
            ->getJson('/api/student/' . $setup['student']->student_id . '/course/' . $setup['course']->course_id . '/payment-summary')
            ->assertOk()
            ->assertJsonPath('summary.course_fee', 22750)
            ->assertJsonPath('summary.registration_fee', 20000)
            ->assertJsonPath('summary.total_local_amount', 42750)
            ->assertJsonPath('summary.local_outstanding', 0);
    }

    public function test_payment_summary_counts_local_and_franchise_on_the_same_installment(): void
    {
        $setup = $this->makePaymentStudent([
            'franchise_payment' => '1500',
        ]);
        $plan = StudentPaymentPlan::forceCreate([
            'student_id'         => $setup['student']->student_id,
            'course_id'          => $setup['course']->course_id,
            'payment_plan_type'  => 'installments',
            'total_amount'       => 25000,
            'final_amount'       => 25000,
            'status'             => 'active',
        ]);
        PaymentInstallment::forceCreate([
            'payment_plan_id'       => $plan->id,
            'installment_number'    => 1,
            'due_date'              => '2026-02-18',
            'amount'                => 25000,
            'base_amount'           => 25000,
            'final_amount'          => 25000,
            'international_amount'  => 1500,
            'status'                => 'pending',
        ]);

        $this->actingAs($this->actor)
            ->getJson('/api/student/' . $setup['student']->student_id . '/course/' . $setup['course']->course_id . '/payment-summary')
            ->assertOk()
            ->assertJsonPath('summary.course_fee', 25000)
            ->assertJsonPath('summary.total_franchise_amount', 1500)
            ->assertJsonPath('summary.registration_fee', 20000)
            ->assertJsonPath('summary.total_local_amount', 45000);
    }

    public function test_payment_summary_uses_intake_course_fee_instead_of_payment_totals(): void
    {
        $setup = $this->makePaymentStudent();
        PaymentDetail::forceCreate([
            'student_id'             => $setup['student']->student_id,
            'course_registration_id' => $setup['registration']->id,
            'amount'                 => 20000,
            'total_fee'              => 20000,
            'remaining_amount'       => -500,
            'installment_type'       => 'registration_fee',
            'status'                 => 'paid',
            'payment_method'         => 'Cash',
        ]);

        $this->actingAs($this->actor)
            ->getJson('/api/student/' . $setup['student']->student_id . '/course/' . $setup['course']->course_id . '/payment-summary')
            ->assertOk()
            ->assertJsonPath('summary.course_fee', 25000)
            ->assertJsonPath('summary.registration_fee', 20000)
            ->assertJsonPath('summary.total_local_amount', 45000)
            ->assertJsonPath('summary.local_outstanding', 25000);
    }

    public function test_exam_semesters_load_for_certificate_course_without_semester_rows(): void
    {
        $setup = $this->makePaymentStudent();
        $module = Module::forceCreate([
            'module_code'     => 'CAIT-101',
            'module_name'     => 'IT Fundamentals',
            'module_type'     => 'core',
            'module_category' => 'certificate',
            'credits'         => 5,
        ]);
        ExamResult::forceCreate([
            'student_id' => $setup['student']->student_id,
            'course_id'  => $setup['course']->course_id,
            'module_id'  => $module->module_id,
            'intake_id'  => $setup['intake']->intake_id,
            'location'   => 'Welisara',
            'semester'   => '1',
            'marks'      => 72,
            'grade'      => 'B',
        ]);

        $this->actingAs($this->actor)
            ->getJson('/api/student/' . $setup['student']->student_id . '/course/' . $setup['course']->course_id . '/semesters')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('semesters.0', '1');
    }

    public function test_exam_semesters_load_from_semester_names(): void
    {
        $setup = $this->makePaymentStudent();
        Semester::forceCreate([
            'name'       => 'Semester 1',
            'course_id'  => $setup['course']->course_id,
            'intake_id'  => $setup['intake']->intake_id,
            'start_date' => '2026-01-01',
            'end_date'   => '2026-06-30',
            'status'     => 'active',
        ]);
        Semester::forceCreate([
            'name'       => 'Semester 1',
            'course_id'  => $setup['course']->course_id,
            'intake_id'  => $setup['intake']->intake_id,
            'start_date' => '2026-07-01',
            'end_date'   => '2026-12-31',
            'status'     => 'upcoming',
        ]);

        $this->actingAs($this->actor)
            ->getJson('/api/student/' . $setup['student']->student_id . '/course/' . $setup['course']->course_id . '/semesters')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('semesters.0', 'Semester 1')
            ->assertJsonCount(1, 'semesters');
    }

    public function test_clearance_documents_are_returned_only_when_a_file_exists(): void
    {
        $setup = $this->makePaymentStudent();
        $studentId = $setup['student']->student_id;

        ClearanceRequest::forceCreate([
            'clearance_type' => ClearanceRequest::TYPE_HOSTEL,
            'location'       => 'Welisara',
            'course_id'      => $setup['course']->course_id,
            'intake_id'      => $setup['intake']->intake_id,
            'student_id'     => $studentId,
            'status'         => ClearanceRequest::STATUS_APPROVED,
            'remarks'        => 'NA',
            'clearance_slip' => null,
            'approved_at'    => now(),
            'requested_at'   => now(),
        ]);
        ClearanceRequest::forceCreate([
            'clearance_type' => ClearanceRequest::TYPE_LIBRARY,
            'location'       => 'Welisara',
            'course_id'      => $setup['course']->course_id,
            'intake_id'      => $setup['intake']->intake_id,
            'student_id'     => $studentId,
            'status'         => ClearanceRequest::STATUS_APPROVED,
            'remarks'        => 'Returned books',
            'clearance_slip' => 'clearance_slips/library-slip.pdf',
            'approved_at'    => now(),
            'requested_at'   => now(),
        ]);

        $response = $this->actingAs($this->actor)
            ->getJson('/api/student/' . $studentId . '/clearances')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'clearances');

        $clearances = collect($response->json('clearances'))->keyBy('label');

        $hostel = $clearances['Hostel Clearance'];
        $this->assertFalse($hostel['has_document']);
        $this->assertNull($hostel['document_url']);
        $this->assertNull($hostel['clearance_slip']);
        $this->assertNull($hostel['remarks']);

        $library = $clearances['Library Clearance'];
        $this->assertTrue($library['has_document']);
        $this->assertNotEmpty($library['document_url']);
        $this->assertStringContainsString('/storage/clearance_slips/library-slip.pdf', $library['document_url']);
        $this->assertSame('Returned books', $library['remarks']);
    }

    public function test_parent_info_accepts_country_code_numbers_without_plus(): void
    {
        $student = $this->makeStudent('199055544V');
        ParentGuardian::forceCreate([
            'student_id'               => $student->student_id,
            'guardian_name'            => 'W S H Niluka',
            'guardian_profession'      => null,
            'guardian_contact_number'  => '94710165814',
            'guardian_email'           => 'hniluka740@gmail.com',
            'guardian_address'         => 'Ragama',
            'emergency_contact_number' => '94710165814',
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('student_management.update.parent.info'), [
                'student_id'               => $student->student_id,
                'guardian_name'            => 'W S H Niluka',
                'guardian_profession'      => '',
                'guardian_contact_number'  => '94710165814',
                'guardian_email'           => 'hniluka740@gmail.com',
                'guardian_address'         => '628/25,Siyabalaghawaththa Mawatha, Ragama',
                'emergency_contact_number' => '94710165814',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('guardian_details', [
            'student_id'               => $student->student_id,
            'guardian_contact_number'  => '94710165814',
            'emergency_contact_number' => '94710165814',
            'guardian_address'         => '628/25,Siyabalaghawaththa Mawatha, Ragama',
        ]);
    }

    public function test_parent_info_rejects_invalid_phone_numbers(): void
    {
        $student = $this->makeStudent('199066633V');

        $this->actingAs($this->actor)
            ->postJson(route('student_management.update.parent.info'), [
                'student_id'               => $student->student_id,
                'guardian_name'            => 'Parent Name',
                'guardian_contact_number'  => '12345',
                'guardian_email'           => '',
                'guardian_address'         => 'Colombo',
                'emergency_contact_number' => '0000000000',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['guardian_contact_number', 'emergency_contact_number']);
    }

    private function makePaymentStudent(array $intakeAttrs = []): array
    {
        $student = $this->makeStudent('200607002638');
        $course = Course::forceCreate([
            'course_name'         => 'Certificate in Applied Information Technology (CAIT)',
            'course_type'         => 'diploma',
            'duration'            => '6 months',
            'no_of_semesters'     => 1,
            'min_credits'         => 30,
            'conducted_by'        => 1,
            'course_medium'       => 'English',
            'entry_qualification' => 'O/L',
            'location'            => 'Welisara',
        ]);
        $intake = Intake::forceCreate(array_merge([
            'batch'             => 'CAIT/WE/25/B12',
            'course_id'         => $course->course_id,
            'course_name'       => $course->course_name,
            'batch_size'        => 50,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '20000',
            'franchise_payment' => '0',
            'course_fee'        => '25000',
            'location'          => 'Welisara',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addMonths(6)->toDateString(),
        ], $intakeAttrs));
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
