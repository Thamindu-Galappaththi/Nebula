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
            ->assertSee('Nebula Institute of Technology - Welisara', false);
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

    public function test_registration_older_than_one_year_is_not_changeable(): void
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
            ->assertJsonPath('registrations.0.is_change_allowed', false);
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
}
