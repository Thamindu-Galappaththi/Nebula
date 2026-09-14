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

class UhIndexNumbersPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'uhindex@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_with_mobile_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('uh.index.page'))
            ->assertOk()
            ->assertSee('Add External Institute Student ID')
            ->assertSee('uh-index-page', false)
            ->assertSee('uh-students-table', false)
            ->assertSee('id="studentSearch"', false)
            ->assertSee('Nebula Institute of Technology - Welisara')
            ->assertSee('Nebula Institute of Technology - Moratuwa')
            ->assertSee('Nebula Institute of Technology - Peradeniya')
            ->assertDontSee('btn-terminate', false);
    }

    public function test_courses_load_by_location(): void
    {
        $course = $this->makeCourse();
        $this->makeCourse('BTEC Computing', 'Moratuwa');

        $this->actingAs($this->actor)
            ->postJson(route('uh.index.courses'), [
                'location' => 'Welisara',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('courses.0.course_id', $course->course_id)
            ->assertJsonCount(1, 'courses');
    }

    public function test_intakes_load_by_course_id_and_location(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $otherCourse = $this->makeCourse('BTEC Engineering');
        $this->makeIntake($otherCourse, '2025-JAN-B01');

        $this->actingAs($this->actor)
            ->postJson(route('uh.index.intakes'), [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('intakes.0.intake_id', $intake->intake_id)
            ->assertJsonPath('intakes.0.batch', '2024-JUL-B08')
            ->assertJsonCount(1, 'intakes');
    }

    public function test_students_come_from_course_registration_without_semester_row(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $student = $this->makeStudent('199012345V');
        $this->makeCourseRegistration($student, $course, $intake, 'UH12345');

        $this->actingAs($this->actor)
            ->postJson(route('uh.index.students'), [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('students.0.student_id', $student->student_id)
            ->assertJsonPath('students.0.course_registration_id', 'CR-199012345V-' . $intake->intake_id)
            ->assertJsonPath('students.0.uh_index_number', 'UH12345')
            ->assertJsonPath('students.0.nic', '199012345V');
    }

    public function test_save_updates_only_the_selected_intake_registration(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $otherIntake = $this->makeIntake($course, '2025-JAN-B01');
        $student = $this->makeStudent();

        $selected = $this->makeCourseRegistration($student, $course, $intake, 'OLD-ID');
        $other = $this->makeCourseRegistration($student, $course, $otherIntake, 'KEEP-ME');

        $this->actingAs($this->actor)
            ->postJson(route('uh.index.save'), [
                'location' => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'students' => [
                    [
                        'student_id' => $student->student_id,
                        'uh_index_number' => 'NEW-PEARSON-ID',
                    ],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('updated_count', 1);

        $this->assertSame('NEW-PEARSON-ID', $selected->fresh()->uh_index_number);
        $this->assertSame('KEEP-ME', $other->fresh()->uh_index_number);
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

    private function makeCourseRegistration(Student $student, Course $course, Intake $intake, ?string $uhIndex = null): CourseRegistration
    {
        return CourseRegistration::forceCreate([
            'student_id'             => $student->student_id,
            'course_id'              => $course->course_id,
            'intake_id'              => $intake->intake_id,
            'status'                 => 'Registered',
            'approval_status'        => 'Approved by manager',
            'location'               => $course->location,
            'registration_date'      => now()->toDateString(),
            'course_registration_id' => 'CR-' . $student->id_value . '-' . $intake->intake_id,
            'uh_index_number'        => $uhIndex,
        ]);
    }
}
