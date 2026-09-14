<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $html = $this->actingAs($this->actor)
            ->get(route('semesters.index'))
            ->assertOk()
            ->assertSee('Semester Management')
            ->assertSee('Semester 1')
            ->assertSee('BTEC Computing')
            ->assertSee('id="semesterPagination"', false)
            ->assertSee('data-label="Semester"', false)
            ->assertSee('semester-modules', false)
            ->assertSee('@media (max-width: 991.98px)', false)
            ->getContent();

        $this->assertStringNotContainsString('fas fa-', $html);
        $this->assertStringContainsString('ti ti-plus', $html);
        $this->assertStringContainsString('modal-fullscreen-sm-down', $html);
        $this->assertStringContainsString('id="clearFilters"', $html);
        $this->assertStringContainsString('resetFilterSelect', $html);
        $this->assertDoesNotMatchRegularExpression('/id="clearFilters"[^>]*>\s*<i class="ti ti-x"/', $html);
        $this->assertStringContainsString('sweetalert2.min.js', $html);
        $this->assertStringContainsString('Swal.fire', $html);
        $this->assertStringContainsString('delete-semester', $html);
        $this->assertStringContainsString('confirmSemesterDelete', $html);
    }
}
