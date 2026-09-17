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

class HostelClearancePageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Hostel Manager',
            'email'         => 'hostel@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Hostel Manager',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_pending_requests_are_paginated(): void
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

        for ($i = 1; $i <= 11; $i++) {
            $student = Student::forceCreate([
                'title'              => 'Mr',
                'name_with_initials' => sprintf('Hostel Pending %02d', $i),
                'full_name'          => sprintf('Hostel Pending %02d Full', $i),
                'id_type'            => 'NIC',
                'id_value'           => sprintf('1990%06dV', $i),
                'gender'             => 'Male',
                'email'              => sprintf('hostel-pending-%02d@test.lk', $i),
                'status'             => 'Registered',
                'academic_status'    => Student::ACADEMIC_ACTIVE,
                'institute_location' => 'Welisara',
            ]);

            ClearanceRequest::forceCreate([
                'clearance_type' => ClearanceRequest::TYPE_HOSTEL,
                'location'       => 'Welisara',
                'course_id'      => $course->course_id,
                'intake_id'      => $intake->intake_id,
                'student_id'     => $student->student_id,
                'status'         => ClearanceRequest::STATUS_PENDING,
                'requested_at'   => now()->subMinutes($i),
            ]);
        }

        $this->actingAs($this->actor)
            ->get(route('hostel.clearance.form.management'))
            ->assertOk()
            ->assertSee('HOSTEL PENDING 01')
            ->assertSee('HOSTEL PENDING 10')
            ->assertDontSee('HOSTEL PENDING 11')
            ->assertSee('Showing 1–10 of 11')
            ->assertSee('pending_page=2')
            ->assertSee('pendingRequestsBody')
            ->assertSee('clearance-management-page');

        $this->actingAs($this->actor)
            ->get(route('hostel.clearance.form.management', ['pending_page' => 2]))
            ->assertOk()
            ->assertSee('HOSTEL PENDING 11')
            ->assertDontSee('HOSTEL PENDING 01')
            ->assertSee('Showing 11–11 of 11');

        $pageTwo = $this->actingAs($this->actor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('hostel.clearance.form.management', ['pending_page' => 2]))
            ->assertOk()
            ->assertJsonStructure(['pending_html', 'processed_html']);

        $this->assertStringContainsString('HOSTEL PENDING 11', $pageTwo->json('pending_html'));
        $this->assertStringNotContainsString('HOSTEL PENDING 01', $pageTwo->json('pending_html'));
        $this->assertStringContainsString('Showing 11–11 of 11', $pageTwo->json('pending_html'));
    }

    public function test_location_change_does_not_live_filter_tables(): void
    {
        $files = [
            resource_path('views/clearance/library_clearance.blade.php'),
            resource_path('views/clearance/hostel_clearance.blade.php'),
            resource_path('views/clearance/project_clearance.blade.php'),
            resource_path('views/clearance/payment_clearance.blade.php'),
        ];

        foreach ($files as $file) {
            $source = file_get_contents($file);
            $this->assertStringNotContainsString('#pendingTable tbody tr', $source, $file);
            $this->assertStringNotContainsString('#processedTable tbody tr', $source, $file);
            $this->assertStringContainsString("partials.management_scripts", $source, $file);
        }

        $scripts = file_get_contents(resource_path('views/clearance/partials/management_scripts.blade.php'));
        $this->assertStringContainsString('updateIntakeOptions', $scripts);
        $this->assertStringContainsString('data-course-id', $scripts);
        $this->assertStringNotContainsString('#pendingTable tbody tr', $scripts);
    }
}
