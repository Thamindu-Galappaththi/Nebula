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

class SpecialApprovalListPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'special-approval@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_with_mobile_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('special.approval.list'))
            ->assertOk()
            ->assertSee('Special Approval List')
            ->assertSee('special-approval-page', false)
            ->assertSee('special-approval-table', false)
            ->assertSee('special-approval-tabs', false)
            ->assertSee('id="student-pending"', false)
            ->assertSee('id="student-rejected"', false)
            ->assertSee('modal-fullscreen-sm-down', false);
    }

    public function test_pending_list_excludes_rejected_registrations(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $pending = $this->makeStudent('199012345V');
        $rejected = $this->makeStudent('199098765V');

        CourseRegistration::forceCreate([
            'student_id'             => $pending->student_id,
            'course_id'              => $course->course_id,
            'intake_id'              => $intake->intake_id,
            'status'                 => 'Special approval required',
            'approval_status'        => 'Pending',
            'location'               => 'Welisara',
            'course_registration_id' => 'CR-PENDING',
            'registration_date'      => now()->toDateString(),
        ]);

        CourseRegistration::forceCreate([
            'student_id'             => $rejected->student_id,
            'course_id'              => $course->course_id,
            'intake_id'              => $intake->intake_id,
            'status'                 => 'Special approval required',
            'approval_status'        => 'Rejected',
            'location'               => 'Welisara',
            'course_registration_id' => 'CR-REJECTED',
            'remarks'                => '[Rejected Reason] Incomplete documents',
            'registration_date'      => now()->toDateString(),
        ]);

        $this->actingAs($this->actor)
            ->getJson('/get-special-approval-list')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('students.0.registration_number', 'CR-PENDING')
            ->assertJsonPath('students.0.intake', '2024-JUL-B08')
            ->assertJsonMissing(['registration_number' => 'CR-REJECTED']);
    }

    public function test_rejected_list_returns_rejected_registrations(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $student = $this->makeStudent('199012345V');

        CourseRegistration::forceCreate([
            'student_id'             => $student->student_id,
            'course_id'              => $course->course_id,
            'intake_id'              => $intake->intake_id,
            'status'                 => 'Special approval required',
            'approval_status'        => 'Rejected',
            'location'               => 'Welisara',
            'course_registration_id' => 'CR-REJECTED',
            'remarks'                => '[Rejected Reason] Incomplete documents',
            'registration_date'      => now()->toDateString(),
        ]);

        $this->actingAs($this->actor)
            ->getJson(route('special.approval.rejected'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('students.0.registration_number', 'CR-REJECTED')
            ->assertJsonPath('students.0.reason', '[Rejected Reason] Incomplete documents');
    }

    private function makeStudent(string $idValue): Student
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
