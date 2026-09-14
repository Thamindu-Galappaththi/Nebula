<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\CourseBadge;
use App\Models\CourseRegistration;
use App\Models\Intake;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BadgeGenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'badge-admin@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    private function makeStudent(string $idValue): Student
    {
        return Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'Test Student',
            'full_name'          => 'Test Student Full',
            'id_type'            => 'NIC',
            'id_value'           => $idValue,
            'gender'             => 'Male',
            'email'              => $idValue . '@test.lk',
            'status'             => 'Registered',
            'academic_status'    => 'active',
            'institute_location' => 'Welisara',
        ]);
    }

    private function makeRegistration(int $studentId, int $courseId, int $intakeId, string $location = 'Welisara', array $attrs = []): CourseRegistration
    {
        $course = Course::find($courseId);
        if (!$course) {
            $course = Course::forceCreate([
                'course_id'           => $courseId,
                'course_name'         => 'Course ' . $courseId,
                'course_type'         => 'certificate',
                'duration'            => '3 months',
                'no_of_semesters'     => 1,
                'min_credits'         => 10,
                'conducted_by'        => 1,
                'course_medium'       => 'English',
                'entry_qualification' => 'O/L',
                'location'            => $location,
            ]);
        }

        $intake = Intake::find($intakeId);
        if (!$intake) {
            $intake = Intake::forceCreate([
                'intake_id'         => $intakeId,
                'batch'             => 'Batch ' . $intakeId,
                'course_id'         => $course->course_id,
                'course_name'       => $course->course_name,
                'batch_size'        => 50,
                'intake_mode'       => 'Online',
                'intake_type'       => 'Fulltime',
                'registration_fee'  => '1000',
                'franchise_payment' => '0',
                'course_fee'        => '50000',
                'location'          => $location,
                'start_date'        => now()->toDateString(),
                'end_date'          => now()->addMonths(3)->toDateString(),
            ]);
        }

        return CourseRegistration::forceCreate(array_merge([
            'student_id'        => $studentId,
            'course_id'         => $courseId,
            'intake_id'         => $intakeId,
            'status'            => 'Registered',
            'approval_status'   => 'Approved by manager',
            'location'          => $location,
            'registration_date' => now()->toDateString(),
        ], $attrs));
    }

    public function test_generate_page_renders(): void
    {
        $this->actingAs($this->actor)
            ->get('/badges')
            ->assertOk()
            ->assertSee('Course Completion')
            ->assertSee('Per page');
    }

    public function test_student_id_search_returns_badge_on_the_matching_registration(): void
    {
        $student = $this->makeStudent('199012345678');
        $registration = $this->makeRegistration($student->student_id, 1, 1);

        CourseBadge::forceCreate([
            'student_id'        => $student->student_id,
            'course_id'         => $registration->course_id,
            'intake_id'         => $registration->intake_id,
            'badge_title'       => 'Test Badge',
            'verification_code' => '11111111-1111-1111-1111-111111111111',
            'issued_date'       => now()->toDateString(),
            'status'            => 'active',
        ]);

        $response = $this->actingAs($this->actor)
            ->postJson('/badges/search', ['student_id' => '199012345678']);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $registration->id)
            ->assertJsonPath('data.0.badge.verification_code', '11111111-1111-1111-1111-111111111111')
            ->assertJsonPath('data.0.eligible_for_badge', true);
    }

    public function test_student_id_search_with_mismatched_course_returns_empty(): void
    {
        $student = $this->makeStudent('199099999999');
        $this->makeRegistration($student->student_id, 1, 1);

        $response = $this->actingAs($this->actor)
            ->postJson('/badges/search', [
                'student_id' => '199099999999',
                'course_id'  => 2,
            ]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame([], $response->json('data'));
    }

    public function test_search_paginates_results(): void
    {
        foreach (range(0, 11) as $i) {
            $student = $this->makeStudent('1990000000' . str_pad((string) $i, 2, '0', STR_PAD_LEFT));
            $this->makeRegistration($student->student_id, 10, 100 + $i);
        }

        $page1 = $this->actingAs($this->actor)
            ->postJson('/badges/search', ['course_id' => 10, 'per_page' => 10, 'page' => 1]);

        $page1->assertOk()
            ->assertJsonPath('total', 12)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('from', 1)
            ->assertJsonPath('to', 10);
        $this->assertCount(10, $page1->json('data'));

        $page2 = $this->actingAs($this->actor)
            ->postJson('/badges/search', ['course_id' => 10, 'per_page' => 10, 'page' => 2]);

        $page2->assertOk()->assertJsonPath('from', 11)->assertJsonPath('to', 12);
        $this->assertCount(2, $page2->json('data'));
    }

    public function test_course_intakes_are_limited_to_the_selected_course_location(): void
    {
        $welisaraCourse = Course::forceCreate([
            'course_id'           => 501,
            'course_name'         => 'Certificate IT',
            'course_type'         => 'certificate',
            'duration'            => '3 months',
            'no_of_semesters'     => 1,
            'min_credits'         => 10,
            'conducted_by'        => 1,
            'course_medium'       => 'English',
            'entry_qualification' => 'O/L',
            'location'            => 'Welisara',
        ]);
        Course::forceCreate([
            'course_id'           => 502,
            'course_name'         => 'Certificate IT',
            'course_type'         => 'certificate',
            'duration'            => '3 months',
            'no_of_semesters'     => 1,
            'min_credits'         => 10,
            'conducted_by'        => 1,
            'course_medium'       => 'English',
            'entry_qualification' => 'O/L',
            'location'            => 'Moratuwa',
        ]);

        Intake::forceCreate([
            'intake_id'         => 601,
            'batch'             => '2026-Sep-Welisara',
            'course_id'         => $welisaraCourse->course_id,
            'course_name'       => $welisaraCourse->course_name,
            'batch_size'        => 50,
            'intake_mode'       => 'Online',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '1000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'location'          => 'Welisara',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addMonths(3)->toDateString(),
        ]);
        Intake::forceCreate([
            'intake_id'         => 602,
            'batch'             => '2026-Sep-Moratuwa',
            'course_id'         => null,
            'course_name'       => 'Certificate IT',
            'batch_size'        => 50,
            'intake_mode'       => 'Online',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '1000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'location'          => 'Moratuwa',
            'start_date'        => now()->toDateString(),
            'end_date'          => now()->addMonths(3)->toDateString(),
        ]);

        $response = $this->actingAs($this->actor)
            ->get('/badges/intakes?course_id=' . $welisaraCourse->course_id);

        $response->assertOk()->assertJsonPath('success', true);
        $batches = collect($response->json('intakes'))->pluck('batch');
        $this->assertContains('2026-Sep-Welisara', $batches);
        $this->assertNotContains('2026-Sep-Moratuwa', $batches);
    }
}
