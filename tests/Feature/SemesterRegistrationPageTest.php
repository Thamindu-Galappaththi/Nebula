<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Semester;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SemesterRegistrationPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'semester@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_with_mobile_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('semester.registration'))
            ->assertOk()
            ->assertSee('Semester Registration')
            ->assertSee('semester-registration-page', false)
            ->assertSee('semester-students-table', false)
            ->assertSee('modal-fullscreen-sm-down', false)
            ->assertSee('id="studentSearch"', false)
            ->assertSee('data-status="holding"', false);
    }

    public function test_courses_load_by_location(): void
    {
        $course = $this->makeCourse();

        $this->actingAs($this->actor)
            ->getJson(route('semester.registration.getCoursesByLocation', [
                'location' => 'Welisara',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('courses.0.course_id', $course->course_id)
            ->assertJsonPath('courses.0.course_name', 'BTEC Computing');
    }

    public function test_intakes_load_by_course_id(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);

        $this->actingAs($this->actor)
            ->getJson(route('semester.registration.getOngoingIntakes', [
                'course_id' => $course->course_id,
                'location' => 'Welisara',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('intakes.0.intake_id', $intake->intake_id)
            ->assertJsonPath('intakes.0.batch', '2024-JUL-B08');
    }

    public function test_eligible_students_skip_missing_records_and_return_status(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = $this->makeSemester($course, $intake);
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
            ->getJson(route('semester.registration.getEligibleStudents', [
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester_id' => $semester->id,
                'location' => 'Welisara',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('students.0.student_id', $student->student_id)
            ->assertJsonPath('students.0.nic', '199012345V')
            ->assertJsonPath('students.0.status', 'pending');
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

    private function makeSemester(Course $course, Intake $intake): Semester
    {
        return Semester::forceCreate([
            'name'      => 'Semester 1',
            'course_id' => $course->course_id,
            'intake_id' => $intake->intake_id,
            'start_date'=> now()->toDateString(),
            'end_date'  => now()->addMonths(6)->toDateString(),
            'status'    => 'active',
        ]);
    }
}
