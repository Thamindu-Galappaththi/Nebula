<?php

namespace Tests\Feature;

use App\Models\ClearanceRequest;
use App\Models\Course;
use App\Models\Intake;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AllClearancePageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;
    private Course $course;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'all-clearance@nebula.lk',
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
    }

    public function test_all_clearance_page_is_mobile_safe(): void
    {
        $this->actingAs($this->actor)
            ->get(route('all.clearance.management'))
            ->assertOk()
            ->assertSee('all-clearance-page', false)
            ->assertSee('col-12 col-md-3', false)
            ->assertSee('all-clearance-table-scroll', false)
            ->assertSee('sweetalert2@11.22.0', false)
            ->assertSee('Request Status', false);

        $source = file_get_contents(resource_path('views/clearance/all_clearance.blade.php'));
        $this->assertStringContainsString('exam-results-tabs', $source);
        $this->assertStringContainsString('Swal.fire', $source);
        $this->assertStringContainsString('loadStatusPage', $source);
        $this->assertStringContainsString('e.preventDefault();', $source);
        $this->assertStringNotContainsString('cloneNode', $source);
        $this->assertStringNotContainsString('col-sm-3 col-form-label', $source);
        $this->assertStringNotContainsString("alert('Please select", $source);
    }

    public function test_request_status_tables_are_paginated_ten_per_page(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $intake = Intake::forceCreate([
                'location'          => 'Welisara',
                'course_id'         => $this->course->course_id,
                'course_name'       => $this->course->course_name,
                'batch'             => sprintf('2024-JUL-B%02d', $i),
                'batch_size'        => 30,
                'intake_mode'       => 'Physical',
                'intake_type'       => 'Fulltime',
                'registration_fee'  => '5000',
                'franchise_payment' => '0',
                'course_fee'        => '50000',
                'start_date'        => now()->subMonth()->toDateString(),
                'end_date'          => now()->addYears(2)->toDateString(),
            ]);

            $student = Student::forceCreate([
                'title'              => 'Mr',
                'name_with_initials' => sprintf('Intake Student %02d', $i),
                'full_name'          => sprintf('Intake Student %02d Full', $i),
                'id_type'            => 'NIC',
                'id_value'           => sprintf('1991%08d', $i),
                'gender'             => 'Male',
                'email'              => sprintf('intake-student-%02d@test.lk', $i),
                'status'             => 'Registered',
                'academic_status'    => Student::ACADEMIC_ACTIVE,
                'institute_location' => 'Welisara',
            ]);

            ClearanceRequest::forceCreate([
                'clearance_type'        => ClearanceRequest::TYPE_LIBRARY,
                'location'              => 'Welisara',
                'course_id'             => $this->course->course_id,
                'intake_id'             => $intake->intake_id,
                'student_id'            => $student->student_id,
                'status'                => ClearanceRequest::STATUS_PENDING,
                'requested_at'          => now()->subMinutes($i),
                'is_individual_request' => false,
            ]);
        }

        $sharedIntake = Intake::forceCreate([
            'location'          => 'Welisara',
            'course_id'         => $this->course->course_id,
            'course_name'       => $this->course->course_name,
            'batch'             => '2025-IND-SHARED',
            'batch_size'        => 30,
            'intake_mode'       => 'Physical',
            'intake_type'       => 'Fulltime',
            'registration_fee'  => '5000',
            'franchise_payment' => '0',
            'course_fee'        => '50000',
            'start_date'        => now()->subMonth()->toDateString(),
            'end_date'          => now()->addYears(2)->toDateString(),
        ]);
        for ($i = 1; $i <= 11; $i++) {
            $student = Student::forceCreate([
                'title'              => 'Ms',
                'name_with_initials' => sprintf('Individual Student %02d', $i),
                'full_name'          => sprintf('Individual Student %02d Full', $i),
                'id_type'            => 'NIC',
                'id_value'           => sprintf('1992%08d', $i),
                'gender'             => 'Female',
                'email'              => sprintf('individual-student-%02d@test.lk', $i),
                'status'             => 'Registered',
                'academic_status'    => Student::ACADEMIC_ACTIVE,
                'institute_location' => 'Welisara',
            ]);

            ClearanceRequest::forceCreate([
                'clearance_type'        => ClearanceRequest::TYPE_PAYMENT,
                'location'              => 'Welisara',
                'course_id'             => $this->course->course_id,
                'intake_id'             => $sharedIntake->intake_id,
                'student_id'            => $student->student_id,
                'status'                => ClearanceRequest::STATUS_PENDING,
                'requested_at'          => now()->subMinutes($i),
                'is_individual_request' => true,
            ]);
        }

        $this->actingAs($this->actor)
            ->get(route('all.clearance.management'))
            ->assertOk()
            ->assertSee('2024-JUL-B01')
            ->assertSee('2024-JUL-B10')
            ->assertDontSee('2024-JUL-B11')
            ->assertSee('INDIVIDUAL STUDENT 01')
            ->assertSee('INDIVIDUAL STUDENT 10')
            ->assertDontSee('INDIVIDUAL STUDENT 11')
            ->assertSee('Showing 1–10 of 11')
            ->assertSee('intake_page=2')
            ->assertSee('individual_page=2');

        $intakePageTwo = $this->actingAs($this->actor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('all.clearance.management', ['intake_page' => 2, 'tab' => 'status']))
            ->assertOk()
            ->assertJsonStructure(['intake_html', 'individual_html']);

        $this->assertStringContainsString('2024-JUL-B11', $intakePageTwo->json('intake_html'));
        $this->assertStringNotContainsString('2024-JUL-B01', $intakePageTwo->json('intake_html'));
        $this->assertStringContainsString('Showing 11–11 of 11', $intakePageTwo->json('intake_html'));

        $individualPageTwo = $this->actingAs($this->actor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('all.clearance.management', ['individual_page' => 2, 'tab' => 'status']))
            ->assertOk();

        $this->assertStringContainsString('INDIVIDUAL STUDENT 11', $individualPageTwo->json('individual_html'));
        $this->assertStringNotContainsString('INDIVIDUAL STUDENT 01', $individualPageTwo->json('individual_html'));
    }
}
