<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\Semester;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class FixSemesterNamesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_renames_id_like_names_using_course_format(): void
    {
        $eee = Course::forceCreate([
            'course_name'         => 'B.Eng. EEE',
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
        $btec = Course::forceCreate([
            'course_name'         => 'BTEC EE',
            'course_type'         => 'diploma',
            'location'            => 'Welisara',
            'no_of_semesters'     => 4,
            'semester_format'     => 'numerical',
            'duration'            => '2 years',
            'min_credits'         => 120,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 0,
        ]);

        $eeeIntake = Intake::forceCreate([
            'location'          => 'Welisara',
            'course_id'         => $eee->course_id,
            'course_name'       => $eee->course_name,
            'batch'             => '2025-JUl-B09-EEE',
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->subMonth()->toDateString(),
            'end_date'          => now()->addYears(2)->toDateString(),
        ]);
        $btecIntake = Intake::forceCreate([
            'location'          => 'Welisara',
            'course_id'         => $btec->course_id,
            'course_name'       => $btec->course_name,
            'batch'             => 'BTECEE2025-2027WE',
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->subMonth()->toDateString(),
            'end_date'          => now()->addYears(2)->toDateString(),
        ]);

        $uh = Semester::forceCreate([
            'name'       => '38',
            'course_id'  => $eee->course_id,
            'intake_id'  => $eeeIntake->intake_id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(4)->toDateString(),
            'status'     => 'completed',
        ]);

        $hnd = Semester::forceCreate([
            'name'       => '31',
            'course_id'  => $btec->course_id,
            'intake_id'  => $btecIntake->intake_id,
            'start_date' => now()->subMonths(6)->toDateString(),
            'end_date'   => now()->subMonth()->toDateString(),
            'status'     => 'completed',
        ]);

        Semester::forceCreate([
            'name'       => '2',
            'course_id'  => $btec->course_id,
            'intake_id'  => $btecIntake->intake_id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(4)->toDateString(),
            'status'     => 'active',
        ]);

        $this->artisan('semester:fix-names')
            ->assertSuccessful();

        $this->assertSame('A', $uh->fresh()->name);
        $this->assertSame('1', $hnd->fresh()->name);
    }

    public function test_available_slots_ignore_id_like_names(): void
    {
        $user = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'sem-slots@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $course = Course::forceCreate([
            'course_name'         => 'B.Eng. EEE',
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
            'batch'             => '2025-JUl-B09-EEE',
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->subMonth()->toDateString(),
            'end_date'          => now()->addYears(2)->toDateString(),
        ]);

        $semester = Semester::forceCreate([
            'name'       => '38',
            'course_id'  => $course->course_id,
            'intake_id'  => $intake->intake_id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(4)->toDateString(),
            'status'     => 'completed',
        ]);

        $slots = $this->actingAs($user)
            ->getJson(route('semester.registration.getAllSemestersForCourse', [
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
            ]))
            ->assertOk()
            ->json('semesters');

        $ids = collect($slots)->pluck('semester_id')->all();
        $this->assertNotContains(1, $ids);
        $this->assertContains(2, $ids);
        $this->assertSame('Semester B', collect($slots)->firstWhere('semester_id', 2)['semester_name']);
    }
}
