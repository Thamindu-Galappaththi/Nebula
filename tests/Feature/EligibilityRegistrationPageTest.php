<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Student;
use App\Models\StudentExam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EligibilityRegistrationPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'eligibility@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_with_compact_search(): void
    {
        $this->actingAs($this->actor)
            ->get(route('eligibility.registration'))
            ->assertOk()
            ->assertSee('Eligibility &amp; Registration', false)
            ->assertSee('id="searchEligibilityBtn"', false)
            ->assertSee('eligibility-registration-page', false)
            ->assertSee('eligibility-results-table', false)
            ->assertSee('modal-fullscreen-sm-down', false)
            ->assertSee('col-md-2 col-form-label', false);
    }

    public function test_registered_courses_include_location(): void
    {
        [$student, $course] = $this->makeStudentWithCourse();

        $this->actingAs($this->actor)
            ->getJson('/get-registered-courses-by-nic?nic=199012345V')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('courses.0.course_id', $course->course_id)
            ->assertJsonPath('courses.0.location', 'Welisara');
    }

    public function test_intakes_load_for_course_and_location(): void
    {
        [, $course, $intake] = $this->makeStudentWithCourse();

        $this->actingAs($this->actor)
            ->getJson('/get-intakes/' . $course->course_id . '/Welisara')
            ->assertOk()
            ->assertJsonPath('intakes.0.intake_id', $intake->intake_id)
            ->assertJsonPath('intakes.0.batch', '2024-JUL-B08');
    }

    public function test_exam_details_handle_array_subjects(): void
    {
        $student = Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'Test Student',
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
        StudentExam::forceCreate([
            'student_id'       => $student->student_id,
            'ol_exam_type'     => 'Local',
            'ol_exam_year'     => '2016',
            'ol_exam_subjects' => [
                ['subject' => 'Maths', 'result' => 'A'],
            ],
        ]);

        $this->actingAs($this->actor)
            ->postJson('/get-student-exam-details-by-nic-course', [
                'nic' => '199012345V',
                'course_id' => $course->course_id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('student.location', 'Welisara')
            ->assertJsonPath('student.ol.subjects.0.subject', 'Maths');
    }

    /**
     * @return array{0: Student, 1: Course, 2: \App\Models\Intake}
     */
    private function makeStudentWithCourse(): array
    {
        $student = Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'Test Student',
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
        $intake = \App\Models\Intake::forceCreate([
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
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addYears(2)->toDateString(),
        ]);
        \App\Models\CourseRegistration::forceCreate([
            'student_id'        => $student->student_id,
            'course_id'         => $course->course_id,
            'intake_id'         => $intake->intake_id,
            'status'            => 'Pending',
            'approval_status'   => 'Pending',
            'location'          => 'Welisara',
            'registration_date' => now()->toDateString(),
        ]);

        return [$student, $course, $intake];
    }
}
