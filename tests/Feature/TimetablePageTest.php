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

class TimetablePageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'timetable@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_with_mobile_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('timetable.show'))
            ->assertOk()
            ->assertSee('Timetable Management')
            ->assertSee('timetable-page', false)
            ->assertSee('timetable-filter-actions', false)
            ->assertSee('timetable-modal', false)
            ->assertSee('btn-close', false)
            ->assertSee('col-md-3 col-form-label', false)
            ->assertSee('Nebula Institute of Technology - Welisara')
            ->assertDontSee('display:none;display:inline-block', false)
            ->assertDontSee("url: '/get-timetable-events'", false)
            ->assertDontSee("url: '/get-modules-by-semester'", false)
            ->assertSee('get-timetable-events', false)
            ->assertSee('get-modules-by-semester', false);
    }

    public function test_courses_by_location_include_degree_and_diploma(): void
    {
        $degree = $this->makeCourse('BTEC Computing', 'degree');
        $this->makeCourse('Short Cert', 'certificate');

        $this->actingAs($this->actor)
            ->get(route('timetable.courses.by.location', [
                'location' => 'Welisara',
                'course_type' => 'Degree',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('courses.0.course_id', $degree->course_id)
            ->assertJsonMissing(['course_name' => 'Short Cert']);
    }

    public function test_events_endpoint_returns_matching_rows(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $module = Module::forceCreate([
            'module_name' => 'Programming Fundamentals',
            'module_code' => 'PF101',
            'module_type' => 'core',
            'credits'     => 15,
        ]);
        DB::table('timetable')->insert([
            'location'   => 'Welisara',
            'course_id'  => $course->course_id,
            'intake_id'  => $intake->intake_id,
            'semester'   => '1',
            'module_id'  => $module->module_id,
            'subject_id' => $module->module_id,
            'date'       => now()->toDateString(),
            'time'       => '09:00:00',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($this->actor)
            ->get(route('timetable.events', [
                'location'  => 'Welisara',
                'course_id' => $course->course_id,
                'intake_id' => $intake->intake_id,
                'semester'  => '1',
            ]))
            ->assertOk()
            ->assertJsonPath('events.0.module_name', 'Programming Fundamentals')
            ->assertJsonPath('events.0.title', 'Programming Fundamentals');
    }

    public function test_legacy_event_url_is_not_registered(): void
    {
        $this->actingAs($this->actor)
            ->get('/get-timetable-events')
            ->assertNotFound();
    }

    public function test_modules_by_semester_require_matching_semester(): void
    {
        $course = $this->makeCourse();
        $intake = $this->makeIntake($course);
        $semester = Semester::forceCreate([
            'name'       => '1',
            'course_id'  => $course->course_id,
            'intake_id'  => $intake->intake_id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addMonths(6)->toDateString(),
            'status'     => 'active',
        ]);
        $module = Module::forceCreate([
            'module_name' => 'Cloud Fundamentals',
            'module_code' => 'CF101',
            'module_type' => 'core',
            'credits'     => 15,
        ]);
        DB::table('semester_module')->insert([
            'semester_id'     => $semester->id,
            'module_id'       => $module->module_id,
            'specialization'  => null,
            'specializations' => null,
        ]);

        $this->actingAs($this->actor)
            ->get(route('timetable.modules.by.semester', [
                'semester_id' => $semester->id,
                'course_id'   => $course->course_id,
            ]))
            ->assertOk()
            ->assertJsonPath('modules.0.module_name', 'Cloud Fundamentals');
    }

    private function makeCourse(string $name = 'BTEC Computing', string $type = 'degree'): Course
    {
        return Course::forceCreate([
            'course_name'         => $name,
            'course_type'         => $type,
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
