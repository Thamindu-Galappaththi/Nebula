<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SemesterCreationPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'semester-create@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_create_page_is_mobile_ready_and_does_not_use_missing_icon_set(): void
    {
        $html = $this->actingAs($this->actor)
            ->get(route('semesters.create'))
            ->assertOk()
            ->assertSee('Create Semester')
            ->assertSee('creating: true', false)
            ->assertSee('semester-create-header', false)
            ->assertSee('semester-module-picker', false)
            ->assertSee('Modules', false)
            ->assertSee('All types')
            ->assertSee('col-12 col-md-3 col-lg-2 col-form-label', false)
            ->assertSee('data-label="Semester"', false)
            ->assertSee('@media (max-width: 991.98px)', false)
            ->assertSee('Status is set automatically from the start and end dates', false)
            ->getContent();

        $this->assertStringNotContainsString('fas fa-', $html);
        $this->assertStringContainsString('ti ti-arrow-left', $html);
        $this->assertStringNotContainsString('name="status"', $html);
        $this->assertStringContainsString('sem.semester_id', $html);
    }

    public function test_store_creates_semester_from_semester_number_and_derives_status(): void
    {
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

        $intake = Intake::forceCreate([
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

        $moduleId = DB::table('modules')->insertGetId([
            'module_name' => 'Programming',
            'module_code' => 'CS100',
            'module_type' => 'core',
            'credits'     => 3,
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $response = $this->actingAs($this->actor)->postJson(route('semesters.store'), [
            'location'   => 'Welisara',
            'course_id'  => $course->course_id,
            'intake_id'  => $intake->intake_id,
            'semester'   => 1,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(4)->toDateString(),
            'modules'    => [
                ['module_id' => $moduleId],
            ],
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('semesters', [
            'name'      => '1',
            'course_id' => $course->course_id,
            'intake_id' => $intake->intake_id,
            'status'    => 'active',
        ]);

        $this->assertDatabaseHas('semester_module', [
            'module_id' => $moduleId,
        ]);
    }
}
