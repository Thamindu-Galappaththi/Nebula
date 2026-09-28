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

class SpecializationRegistrationPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'specialization@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_with_mobile_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('specialization.registration'))
            ->assertOk()
            ->assertSee('Specialization Registration')
            ->assertSee('specialization-registration-page', false)
            ->assertSee('specialization-students-table', false)
            ->assertSee('id="studentSearch"', false)
            ->assertSee('Nebula Institute of Technology - Welisara')
            ->assertSee('Nebula Institute of Technology - Moratuwa')
            ->assertSee('Nebula Institute of Technology - Peradeniya');
    }

    public function test_courses_load_by_location(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->actor)
            ->postJson('/specialization-registration/courses', [
                'location' => 'Welisara',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('courses.0.course_id', $course->course_id);
    }

    public function test_intakes_load_by_course_id(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);

        $this->actingAs($this->actor)
            ->postJson('/specialization-registration/intakes', [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('intakes.0.intake_id', $intake->intake_id)
            ->assertJsonPath('intakes.0.batch', '2024-JUL-B08');
    }

    public function test_current_specialization_is_per_student(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $networkStudent = $this->makeStudent('199012345V');
        $softwareStudent = $this->makeStudent('199098765V');
        $unassignedStudent = $this->makeStudent('199011122V');

        foreach ([$networkStudent, $softwareStudent, $unassignedStudent] as $student) {
            CourseRegistration::forceCreate([
                'student_id'        => $student->student_id,
                'course_id'         => $course->course_id,
                'intake_id'         => $intake->intake_id,
                'status'            => 'Registered',
                'approval_status'   => 'Approved by manager',
                'location'          => 'Welisara',
                'registration_date' => now()->toDateString(),
            ]);
        }

        \App\Models\SpecializationRegistration::forceCreate([
            'student_id'      => $networkStudent->student_id,
            'course_id'       => $course->course_id,
            'intake_id'       => $intake->intake_id,
            'location'        => 'Welisara',
            'specialization'  => 'Network Engineering',
            'status'          => 'registered',
        ]);
        \App\Models\SpecializationRegistration::forceCreate([
            'student_id'      => $softwareStudent->student_id,
            'course_id'       => $course->course_id,
            'intake_id'       => $intake->intake_id,
            'location'        => 'Welisara',
            'specialization'  => 'Software Engineering',
            'status'          => 'registered',
        ]);

        $students = $this->actingAs($this->actor)
            ->postJson('/specialization-registration/students', [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->json('students');

        $byId = collect($students)->keyBy('student_id');

        $this->assertSame('Network Engineering', $byId[$networkStudent->student_id]['specialization']);
        $this->assertSame('Software Engineering', $byId[$softwareStudent->student_id]['specialization']);
        $this->assertNull($byId[$unassignedStudent->student_id]['specialization']);
        $this->assertNotSame(
            $byId[$networkStudent->student_id]['specialization'],
            $byId[$unassignedStudent->student_id]['specialization']
        );
    }

    public function test_eligible_students_are_returned(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $student = $this->makeStudent();
        CourseRegistration::forceCreate([
            'student_id'        => $student->student_id,
            'course_id'         => $course->course_id,
            'intake_id'         => $intake->intake_id,
            'status'            => 'Registered',
            'approval_status'   => 'Approved by manager',
            'location'          => 'Welisara',
            'registration_date' => now()->toDateString(),
        ]);

        $this->actingAs($this->actor)
            ->postJson('/specialization-registration/students', [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('students.0.student_id', $student->student_id)
            ->assertJsonPath('students.0.nic', '199012345V');
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
            'specializations'     => ['Software Engineering', 'Network Engineering'],
        ]);
    }

    private function makeIntake(Course $course): Intake
    {
        return Intake::forceCreate([
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
    }
}
