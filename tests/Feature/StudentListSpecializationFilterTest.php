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

    public function test_common_filter_returns_unassigned_students_only(): void
    {
        $response = $this->listStudents('Common');

        $response->assertOk()->assertJson(['success' => true]);
        $ids = collect($response->json('students'))->pluck('student_id');

        $this->assertContains($this->commonStudent->student_id, $ids);
        $this->assertNotContains($this->eeeStudent->student_id, $ids);
        $this->assertSame('', $response->json('students.0.specialization'));
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
}
