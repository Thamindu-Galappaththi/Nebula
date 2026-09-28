<?php

namespace Tests\Feature;

use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Student;
use App\Models\StudentStatusHistory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TerminationTrackingTest extends TestCase
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

    public function test_page_renders_terminated_student_without_history(): void
    {
        $student = $this->makeTerminatedStudent('199011111V', 'No History Student');

        $html = $this->actingAs($this->actor)
            ->get(route('termination.tracking'))
            ->assertOk()
            ->assertSee('Termination Tracking')
            ->assertSee('No History Student')
            ->assertSee('Currently Terminated Students')
            ->assertSee('z-index: 1050', false)
            ->assertSee('#main-wrapper.show-sidebar .nebula-select-menu', false)
            ->assertDontSee('Attempt to read property')
            ->getContent();

        $this->assertMatchesRegularExpression('/\.termination-filters\s*\{[^}]*z-index:\s*1;/', $html);
        $this->assertDoesNotMatchRegularExpression('/\.termination-filters\s*\{[^}]*z-index:\s*20;/', $html);
    }

    public function test_page_shows_history_and_latest_course_clearances(): void
    {
        $student = $this->makeTerminatedStudent('199022222V', 'Tracked Student');
        $old = $this->makeRegistration($student->student_id, 11, 21);
        $latest = $this->makeRegistration($student->student_id, 12, 22, [
            'course_name' => 'Latest Course Name',
            'batch'       => 'LATEST/B1',
            'registration_date' => now()->toDateString(),
        ]);

        StudentStatusHistory::forceCreate([
            'student_id'  => $student->student_id,
            'from_status' => 'active',
            'to_status'   => Student::ACADEMIC_TERMINATED,
            'reason'      => 'Attendance issues',
            'changed_by'  => $this->actor->user_id,
        ]);

        ClearanceRequest::forceCreate([
            'clearance_type' => ClearanceRequest::TYPE_LIBRARY,
            'location'       => 'Welisara',
            'course_id'      => $old['course']->course_id,
            'intake_id'      => $old['intake']->intake_id,
            'student_id'     => $student->student_id,
            'status'         => ClearanceRequest::STATUS_APPROVED,
            'requested_at'   => now()->subDays(3),
        ]);
        ClearanceRequest::forceCreate([
            'clearance_type' => ClearanceRequest::TYPE_LIBRARY,
            'location'       => 'Welisara',
            'course_id'      => $latest['course']->course_id,
            'intake_id'      => $latest['intake']->intake_id,
            'student_id'     => $student->student_id,
            'status'         => ClearanceRequest::STATUS_PENDING,
            'requested_at'   => now()->subDay(),
        ]);

        $this->actingAs($this->actor)
            ->get(route('termination.tracking'))
            ->assertOk()
            ->assertSee('Tracked Student')
            ->assertSee('Attendance issues')
            ->assertSee('Latest Course Name')
            ->assertSee('LATEST/B1')
            ->assertSee('Awaiting clearances')
            ->assertSee('View Process');
    }

    public function test_filters_load_full_catalog_and_pagination_is_present(): void
    {
        $this->makeTerminatedStudent('199033333V', 'Filter Student');
        $this->makeCourse(91, 'Catalog Only Course', 'Peradeniya');
        $this->makeIntake(93, 81, 'CATALOG/B1', 'Moratuwa');

        $html = $this->actingAs($this->actor)
            ->get(route('termination.tracking'))
            ->assertOk()
            ->assertSee('id="refreshTerminationBtn"', false)
            ->assertSee('id="terminationPaginationBar"', false)
            ->assertSee('Per page')
            ->assertSee('Welisara')
            ->assertSee('Moratuwa')
            ->assertSee('Peradeniya')
            ->assertSee('Catalog Only Course')
            ->assertSee('CATALOG/B1')
            ->assertDontSee('data-nebula-select="off"', false)
            ->assertDontSee('<a href="' . route('termination.tracking') . '" class="btn btn-outline-primary">', false)
            ->getContent();

        $this->assertSame(1, substr_count($html, '<option value="">All Locations</option>'));
        $this->assertSame(1, substr_count($html, '<option value="">All Courses</option>'));
        $this->assertSame(1, substr_count($html, '<option value="">All Intakes</option>'));
        $this->assertStringContainsString('value="91"', $html);
        $this->assertStringContainsString('value="81"', $html);
    }

    public function test_ajax_refresh_returns_tracking_json_without_full_page(): void
    {
        $this->makeTerminatedStudent('199044444V', 'Json Student');
        $this->makeCourse(92, 'Json Catalog Course', 'Moratuwa');

        $this->actingAs($this->actor)
            ->getJson(route('termination.tracking'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('summary.total', 1)
            ->assertJsonPath('processes.0.student_name', 'Json Student')
            ->assertJsonPath('filters.locations', ['Welisara', 'Moratuwa', 'Peradeniya'])
            ->assertJsonFragment(['id' => 92, 'name' => 'Json Catalog Course', 'location' => 'Moratuwa'])
            ->assertJsonMissingPath('html');
    }

    private function makeTerminatedStudent(string $nic, string $name): Student
    {
        return Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'T. Student',
            'full_name'          => $name,
            'id_type'            => 'NIC',
            'id_value'           => $nic,
            'gender'             => 'Male',
            'email'              => $nic . '@test.lk',
            'status'             => 'Registered',
            'academic_status'    => Student::ACADEMIC_TERMINATED,
            'institute_location' => 'Welisara',
        ]);
    }

    private function makeCourse(int $courseId, string $courseName, string $location = 'Welisara'): Course
    {
        return Course::forceCreate([
            'course_id'           => $courseId,
            'course_name'         => $courseName,
            'course_type'         => 'degree',
            'duration'            => '3 years',
            'no_of_semesters'     => 6,
            'min_credits'         => 120,
            'conducted_by'        => 1,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L',
            'location'            => $location,
        ]);
    }

    private function makeIntake(int $courseId, int $intakeId, string $batch, string $location = 'Welisara'): Intake
    {
        $course = Course::find($courseId) ?? $this->makeCourse($courseId, 'Course ' . $courseId, $location);

        return Intake::forceCreate([
            'intake_id'         => $intakeId,
            'batch'             => $batch,
            'course_id'         => $course->course_id,
            'course_name'       => $course->course_name,
            'batch_size'        => 50,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '1000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'location'          => $location,
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addYear()->toDateString(),
        ]);
    }

    private function makeRegistration(int $studentId, int $courseId, int $intakeId, array $overrides = []): array
    {
        $course = $this->makeCourse(
            $courseId,
            $overrides['course_name'] ?? ('Course ' . $courseId),
            'Welisara'
        );
        $intake = $this->makeIntake(
            $course->course_id,
            $intakeId,
            $overrides['batch'] ?? ('Batch ' . $intakeId),
            'Welisara'
        );
        $registration = CourseRegistration::forceCreate([
            'student_id'        => $studentId,
            'course_id'         => $courseId,
            'intake_id'         => $intakeId,
            'status'            => 'Registered',
            'approval_status'   => 'Approved by manager',
            'location'          => 'Welisara',
            'registration_date' => $overrides['registration_date'] ?? now()->subMonth()->toDateString(),
        ]);

        return compact('course', 'intake', 'registration');
    }
}
