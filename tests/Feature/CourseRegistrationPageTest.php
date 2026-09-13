<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CourseRegistrationPageTest extends TestCase
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

    public function test_page_renders_on_mobile_friendly_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('course.registration'))
            ->assertOk()
            ->assertSee('Course Registration')
            ->assertSee('course-registration-page', false)
            ->assertSee('id="searchNicBtn"', false)
            ->assertSee('Choose a location...');
    }

    public function test_student_nic_search_returns_array_exam_subjects(): void
    {
        $student = $this->makeStudent('199012345V');
        StudentExam::forceCreate([
            'student_id'       => $student->student_id,
            'ol_exam_type'     => 'Local',
            'ol_exam_year'     => '2016',
            'ol_exam_subjects' => [
                ['subject' => 'Maths', 'result' => 'A'],
            ],
            'al_exam_type'     => 'Local',
            'al_exam_year'     => '2019',
            'al_exam_stream'   => 'Maths',
            'al_exam_subjects' => [
                ['subject' => 'Combined Maths', 'result' => 'B'],
            ],
        ]);

        $this->actingAs($this->actor)
            ->getJson(route('course.registration.student.by.nic', ['nic' => '199012345V']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('student.id_value', '199012345V')
            ->assertJsonPath('ol_exams.0.subjects.0.subject', 'Maths')
            ->assertJsonPath('al_exams.0.subjects.0.subject', 'Combined Maths')
            ->assertJsonPath('is_terminated', false);
    }

    public function test_intakes_can_be_loaded_by_course_id(): void
    {
        $course = $this->makeCourse();
        $this->makeIntake($course, '2024-JUL-B08');

        $this->actingAs($this->actor)
            ->getJson(route('course.registration.intakes.by.course.location', [
                'courseName' => $course->course_id,
                'location' => 'Welisara',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('intakes.0.batch', '2024-JUL-B08');
    }

    private function makeStudent(string $idValue, array $attrs = []): Student
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
            'academic_status'    => Student::ACADEMIC_ACTIVE,
            'institute_location' => 'Welisara',
        ], $attrs));
    }

    private function makeCourse(): Course
    {
        return Course::forceCreate([
            'course_name'         => 'Diploma in Software Engineering',
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

    private function makeIntake(Course $course, string $batch): Intake
    {
        return Intake::forceCreate([
            'location'         => 'Welisara',
            'course_id'        => $course->course_id,
            'course_name'      => $course->course_name,
            'batch'            => $batch,
            'batch_size'       => 30,
            'intake_mode'      => 'Physical',
            'intake_type'      => 'Fulltime',
            'registration_fee' => '5000',
            'franchise_payment'=> '0',
            'course_fee'       => '50000',
            'start_date'       => now()->toDateString(),
            'end_date'         => now()->addYears(2)->toDateString(),
        ]);
    }
}
