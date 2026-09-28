<?php

namespace Tests\Feature;

use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ExistingStudentWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Course $course;
    private Intake $intake;
    private Student $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'existing-student@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->course = Course::forceCreate([
            'course_name'         => 'B.Eng. (Hons) Electrical & Electronic Engineering',
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 6,
            'duration'            => '3 years',
            'min_credits'         => 360,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 0,
        ]);

        $this->intake = Intake::forceCreate([
            'location'          => 'Welisara',
            'course_id'         => $this->course->course_id,
            'course_name'       => $this->course->course_name,
            'batch'             => '2022-Jan-B06-EEE',
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->subYears(3)->toDateString(),
            'end_date'          => now()->addYear()->toDateString(),
        ]);

        $this->student = Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'O.G.M.V.M.MADUWANTHA',
            'full_name'          => 'Ovite Gedara Mudiyanselage Vishwa Malshan Maduwantha',
            'id_type'            => 'National id',
            'id_value'           => '200224103657',
            'gender'             => 'Male',
            'email'              => 'maduwantha-test@nebula.lk',
            'status'             => 'Unmarried',
            'academic_status'    => Student::ACADEMIC_TERMINATED,
            'institute_location' => 'Welisara',
        ]);

        CourseRegistration::forceCreate([
            'student_id'             => $this->student->student_id,
            'course_id'              => $this->course->course_id,
            'intake_id'              => $this->intake->intake_id,
            'course_registration_id' => 'EEE-2022-007',
            'status'                 => 'Registered',
            'approval_status'        => 'Approved by manager',
            'location'               => 'Welisara',
            'registration_date'      => now()->toDateString(),
        ]);
    }

    public function test_duplicate_nic_points_staff_to_existing_profile(): void
    {
        $response = $this->actingAs($this->actor)->postJson(route('student_management.register'), [
            'identificationType' => 'National id',
            'idValue' => '200224103657',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('existing_student.student_id', $this->student->student_id)
            ->assertJsonPath('existing_student.academic_status', 'terminated');

        $this->assertStringContainsString('already registered', $response->json('existing_student.message'));
        $this->assertStringContainsString(
            (string) $this->student->student_id,
            (string) $response->json('existing_student.profile_url')
        );
    }

    public function test_already_terminated_student_cannot_be_terminated_again(): void
    {
        $response = $this->actingAs($this->actor)->postJson(route('student_management.terminate'), [
            'student_id' => $this->student->student_id,
            'reason' => 'Course completed',
        ]);

        $response->assertStatus(422)->assertJsonPath('success', false);
        $this->assertStringContainsString('already terminated', (string) $response->json('message'));
    }

    public function test_clearance_can_be_sent_for_a_terminated_student(): void
    {
        $response = $this->actingAs($this->actor)->postJson(route('clearance.sendRequest'), [
            'type'       => ClearanceRequest::TYPE_LIBRARY,
            'location'   => 'Welisara',
            'course_id'  => $this->course->course_id,
            'intake_id'  => $this->intake->intake_id,
            'student_id' => $this->student->student_id,
        ]);

        $response->assertOk()->assertJsonPath('success', true);

        $this->assertDatabaseHas('clearance_requests', [
            'student_id'     => $this->student->student_id,
            'clearance_type' => ClearanceRequest::TYPE_LIBRARY,
            'intake_id'      => $this->intake->intake_id,
        ]);
    }
}
