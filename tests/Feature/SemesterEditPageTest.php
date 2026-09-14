<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SemesterEditPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Course $course;
    private Intake $intake;
    private int $moduleId;
    private Semester $semester;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'semester-edit@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->course = Course::forceCreate([
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

        $this->intake = Intake::forceCreate([
            'location'          => 'Welisara',
            'course_id'         => $this->course->course_id,
            'course_name'       => $this->course->course_name,
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

        $this->moduleId = DB::table('modules')->insertGetId([
            'module_name' => 'Programming',
            'module_code' => 'CS100',
            'module_type' => 'core',
            'credits'     => 3,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        // Ensure the semester under test does not have id === 1.
        Semester::forceCreate([
            'name'       => '9',
            'course_id'  => $this->course->course_id,
            'intake_id'  => $this->intake->intake_id,
            'start_date' => now()->subYear()->toDateString(),
            'end_date'   => now()->subMonths(6)->toDateString(),
            'status'     => 'completed',
        ]);

        $this->semester = Semester::forceCreate([
            'name'       => '1',
            'course_id'  => $this->course->course_id,
            'intake_id'  => $this->intake->intake_id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(4)->toDateString(),
            'status'     => 'active',
        ]);

        DB::table('semester_module')->insert([
            'semester_id' => $this->semester->id,
            'module_id'   => $this->moduleId,
        ]);
    }

    public function test_edit_page_is_mobile_ready_and_posts_semester_number_not_id(): void
    {
        $html = $this->actingAs($this->actor)
            ->get(route('semesters.edit', $this->semester))
            ->assertOk()
            ->assertSee('Edit Semester')
            ->assertSee('Semester 1')
            ->assertSee('semester-edit-header', false)
            ->assertSee('semester-module-picker', false)
            ->assertSee('data-label="Semester"', false)
            ->assertSee('modal-fullscreen-sm-down', false)
            ->assertSee('@media (max-width: 767.98px)', false)
            ->getContent();

        $this->assertStringNotContainsString('fas fa-', $html);
        $this->assertStringContainsString('ti ti-arrow-left', $html);
        $this->assertStringContainsString('ti ti-copy', $html);
        $this->assertStringContainsString('value="1"', $html);
        $this->assertStringNotContainsString(
            'value="'.$this->semester->id.'" selected',
            $html
        );
        $this->assertNotSame(1, (int) $this->semester->id);
    }

    public function test_update_keeps_semester_name_instead_of_replacing_it_with_id(): void
    {
        $response = $this->actingAs($this->actor)->putJson(route('semesters.update', $this->semester), [
            'location'   => 'Welisara',
            'course_id'  => $this->course->course_id,
            'intake_id'  => $this->intake->intake_id,
            'semester'   => 1,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(5)->toDateString(),
            'modules'    => [
                ['module_id' => $this->moduleId],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('semesters', [
            'id'     => $this->semester->id,
            'name'   => '1',
            'status' => 'active',
        ]);
        $this->assertDatabaseMissing('semesters', [
            'id'   => $this->semester->id,
            'name' => (string) $this->semester->id,
        ]);
    }
}
