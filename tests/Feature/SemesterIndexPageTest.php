<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\Module;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SemesterIndexPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'semesters@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_index_is_mobile_ready_and_does_not_use_missing_icon_set(): void
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

        Semester::forceCreate([
            'name'       => 'Semester 1',
            'course_id'  => $course->course_id,
            'intake_id'  => $intake->intake_id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(4)->toDateString(),
            'status'     => 'active',
        ]);

        $module = Module::forceCreate([
            'module_name'     => 'Planning A Computing Project (Pearson Set)',
            'module_code'     => 'BTEC_PLANNING_001',
            'module_category' => 'degree',
            'module_type'     => 'core',
            'credits'         => 15,
        ]);
        $semester = Semester::query()->first();
        DB::table('semester_module')->insert([
            'semester_id' => $semester->id,
            'module_id'   => $module->module_id,
        ]);

        $html = $this->actingAs($this->actor)
            ->get(route('semesters.index'))
            ->assertOk()
            ->assertSee('Semester Management')
            ->assertSee('class="semester-name">1</strong>', false)
            ->assertSee('BTEC Computing')
            ->assertSee('id="semesterPagination"', false)
            ->assertSee('data-label="Semester"', false)
            ->assertSee('Planning A Computing Project (pearson Set)')
            ->assertSee('semester-modules-table', false)
            ->assertSee('data-label="Module Name"', false)
            ->assertSee('overflow-wrap: break-word', false)
            ->assertSee('height: auto', false)
            ->assertDontSee('modal-fullscreen-sm-down', false)
            ->getContent();

        $this->assertStringNotContainsString('fas fa-', $html);
        $this->assertStringContainsString('ti ti-plus', $html);
        $this->assertStringContainsString('id="clearFilters"', $html);
        $this->assertStringContainsString('resetFilterSelect', $html);
        $this->assertDoesNotMatchRegularExpression('/id="clearFilters"[^>]*>\s*<i class="ti ti-x"/', $html);
        $this->assertStringContainsString('sweetalert2.min.js', $html);
        $this->assertStringContainsString('Swal.fire', $html);
        $this->assertStringContainsString('delete-semester', $html);
        $this->assertStringContainsString('confirmSemesterDelete', $html);
    }

    public function test_index_shows_alphabetical_slot_and_date_status_not_row_id(): void
    {
        $course = Course::forceCreate([
            'course_name'         => 'B.Eng. (Hons) Electrical & Electronic Engineering',
            'course_type'         => 'degree',
            'location'            => 'Welisara',
            'no_of_semesters'     => 6,
            'semester_format'     => 'alphabetical',
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
            'batch'             => '2025-JUL-B09-EEE',
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->subYear()->toDateString(),
            'end_date'          => now()->addYear()->toDateString(),
        ]);

        $semester = Semester::forceCreate([
            'name'       => '38',
            'course_id'  => $course->course_id,
            'intake_id'  => $intake->intake_id,
            'start_date' => '2026-02-02',
            'end_date'   => '2026-05-29',
            'status'     => 'active',
        ]);

        $html = $this->actingAs($this->actor)
            ->get(route('semesters.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('class="semester-name">A</strong>', $html);
        $this->assertStringContainsString('badge bg-secondary">Completed</span>', $html);
        $this->assertStringNotContainsString('class="semester-name">38</strong>', $html);
        $this->assertStringNotContainsString('badge bg-success">Active</span>', $html);
    }
}
