<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentListSpecializationFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Course $course;
    private Intake $intake;
    private Student $commonStudent;
    private Student $eeeStudent;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'student-list@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->course = Course::forceCreate([
            'course_name'         => 'B.Eng. (Hons) Electrical & Electronic Engineering',
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 8,
            'duration'            => '4 years',
            'min_credits'         => 120,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 0,
            'specializations'     => ['Electrical & Electronic Engineering', 'Electronic Engineering'],
        ]);

        $this->intake = Intake::forceCreate([
            'location'          => 'Welisara',
            'course_id'         => $this->course->course_id,
            'course_name'       => $this->course->course_name,
            'batch'             => '2026-Sep-B10-EEE',
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->subMonth()->toDateString(),
            'end_date'          => now()->addYears(3)->toDateString(),
        ]);

        $this->commonStudent = $this->makeStudent('199011110001', 'Common Student');
        $this->eeeStudent = $this->makeStudent('199011110002', 'EEE Student');

        $this->register($this->commonStudent, 'CR-COMMON');
        $this->register($this->eeeStudent, 'CR-EEE');

        DB::table('specialization_registrations')->insert([
            'student_id'     => $this->eeeStudent->student_id,
            'course_id'      => $this->course->course_id,
            'intake_id'      => $this->intake->intake_id,
            'location'       => 'Welisara',
            'specialization' => 'Electrical & Electronic Engineering',
            'status'         => 'registered',
            'created_at'     => now(),
            'updated_at'     => now(),
        ]);
    }

    private function makeStudent(string $idValue, string $name): Student
    {
        return Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => $name,
            'full_name'          => $name,
            'id_type'            => 'NIC',
            'id_value'           => $idValue,
            'gender'             => 'Male',
            'email'              => $idValue . '@test.lk',
            'status'             => 'Registered',
            'academic_status'    => 'active',
            'institute_location' => 'Welisara',
        ]);
    }

    private function register(Student $student, string $registrationId): void
    {
        CourseRegistration::forceCreate([
            'student_id'             => $student->student_id,
            'course_id'              => $this->course->course_id,
            'intake_id'              => $this->intake->intake_id,
            'course_registration_id' => $registrationId,
            'status'                 => 'Registered',
            'approval_status'        => 'Approved by manager',
            'location'               => 'Welisara',
            'registration_date'      => now()->toDateString(),
        ]);
    }

    private function listStudents(?string $specialization)
    {
        return $this->actingAs($this->actor)->postJson(route('student.getListData'), [
            'location'       => 'Welisara',
            'course_id'      => $this->course->course_id,
            'intake_id'      => $this->intake->intake_id,
            'specialization' => $specialization,
        ]);
    }

    public function test_unfiltered_list_labels_unassigned_students_as_common(): void
    {
        $response = $this->listStudents(null);

        $response->assertOk()->assertJson(['success' => true]);
        $students = collect($response->json('students'))->keyBy('student_id');

        $this->assertSame('Common', $students[$this->commonStudent->student_id]['specialization']);
        $this->assertSame(
            'Electrical & Electronic Engineering',
            $students[$this->eeeStudent->student_id]['specialization']
        );
    }

    public function test_common_filter_returns_unassigned_students_only(): void
    {
        $response = $this->listStudents('Common');

        $response->assertOk()->assertJson(['success' => true]);
        $ids = collect($response->json('students'))->pluck('student_id');

        $this->assertContains($this->commonStudent->student_id, $ids);
        $this->assertNotContains($this->eeeStudent->student_id, $ids);
        $this->assertSame('Common', $response->json('students.0.specialization'));
    }

    public function test_named_specialization_filter_does_not_return_common_students(): void
    {
        $response = $this->listStudents('Electrical & Electronic Engineering');

        $response->assertOk()->assertJson(['success' => true]);
        $students = collect($response->json('students'));

        $this->assertEqualsCanonicalizing(
            [$this->eeeStudent->student_id],
            $students->pluck('student_id')->all()
        );
        $this->assertSame('Electrical & Electronic Engineering', $students->first()['specialization']);
    }

    public function test_pdf_omits_specialization_column_for_common(): void
    {
        $html = view('student_management.student_list_pdf', [
            'cspNonce' => 'test',
            'students' => collect([(object) [
                'course_registration_id' => 'CR-COMMON',
                'student_id' => $this->commonStudent->student_id,
                'name' => 'Common Student',
                'specialization' => 'Common',
                'status' => 'registered',
            ]]),
            'locationText' => 'Nebula Institute of Technology - Welisara',
            'courseText' => $this->course->course_name,
            'intakeText' => $this->intake->batch,
            'total_count' => 1,
            'status' => 'all',
            'specializationText' => 'Common',
            'showSpecializationColumn' => false,
        ])->render();

        $this->assertStringNotContainsString('<th>Specialization</th>', $html);
        $this->assertStringNotContainsString('<strong>Specialization:</strong>', $html);
    }

    public function test_pdf_keeps_specialization_column_for_named_track(): void
    {
        $html = view('student_management.student_list_pdf', [
            'cspNonce' => 'test',
            'students' => collect([(object) [
                'course_registration_id' => 'CR-EEE',
                'student_id' => $this->eeeStudent->student_id,
                'name' => 'EEE Student',
                'specialization' => 'Electrical & Electronic Engineering',
                'status' => 'registered',
            ]]),
            'locationText' => 'Nebula Institute of Technology - Welisara',
            'courseText' => $this->course->course_name,
            'intakeText' => $this->intake->batch,
            'total_count' => 1,
            'status' => 'all',
            'specializationText' => 'Electrical & Electronic Engineering',
            'showSpecializationColumn' => true,
        ])->render();

        $this->assertStringContainsString('<th>Specialization</th>', $html);
        $this->assertStringContainsString('<strong>Specialization:</strong> Electrical &amp; Electronic Engineering', $html);
        $this->assertStringContainsString('Electrical &amp; Electronic Engineering', $html);
    }

    public function test_excel_omits_specialization_heading_for_common(): void
    {
        $export = new \App\Exports\StudentListExport(
            [[1, 'CR-COMMON', $this->commonStudent->student_id, 'Common Student', 'Registered']],
            $this->course->course_name,
            'Welisara',
            $this->intake->batch,
            'all',
            false
        );

        $this->assertSame(
            ['No.', 'Course Registration ID', 'Student ID', 'Student Name', 'Status'],
            $export->headings()
        );
    }

    public function test_excel_keeps_specialization_heading_for_named_track(): void
    {
        $export = new \App\Exports\StudentListExport(
            [[1, 'CR-EEE', $this->eeeStudent->student_id, 'EEE Student', 'Electrical & Electronic Engineering', 'Registered']],
            $this->course->course_name,
            'Welisara',
            $this->intake->batch,
            'all',
            true
        );

        $this->assertSame(
            ['No.', 'Course Registration ID', 'Student ID', 'Student Name', 'Specialization', 'Status'],
            $export->headings()
        );
    }

    public function test_named_specialization_with_no_assignments_still_returns_the_intake(): void
    {
        DB::table('specialization_registrations')->delete();

        $response = $this->listStudents('Electrical & Electronic Engineering');

        $response->assertOk()->assertJson(['success' => true]);
        $ids = collect($response->json('students'))->pluck('student_id');

        $this->assertContains($this->commonStudent->student_id, $ids);
        $this->assertContains($this->eeeStudent->student_id, $ids);
    }

    public function test_terminated_profile_status_is_listed_as_not_eligible(): void
    {
        $this->commonStudent->academic_status = 'terminated';
        $this->commonStudent->save();

        $response = $this->listStudents(null);
        $students = collect($response->json('students'))->keyBy('student_id');

        $this->assertSame('terminated', $students[$this->commonStudent->student_id]['status']);
        $this->assertSame('Not Eligible - Termination', $students[$this->commonStudent->student_id]['status_label']);
        $this->assertSame('registered', $students[$this->eeeStudent->student_id]['status']);
    }

    public function test_terminated_status_includes_the_termination_reason(): void
    {
        $this->commonStudent->academic_status = 'terminated';
        $this->commonStudent->academic_status_reason = 'Non-payment of fees';
        $this->commonStudent->save();

        $response = $this->listStudents(null);
        $students = collect($response->json('students'))->keyBy('student_id');

        $this->assertSame(
            'Not Eligible - Termination: Non-payment of fees',
            $students[$this->commonStudent->student_id]['status_label']
        );
        $this->assertSame('Non-payment of fees', $students[$this->commonStudent->student_id]['status_reason']);
    }
}
