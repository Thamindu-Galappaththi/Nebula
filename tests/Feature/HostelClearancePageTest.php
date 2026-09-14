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
            ->assertSee('pending_page=2');

        $this->actingAs($this->actor)
            ->get(route('hostel.clearance.form.management', ['pending_page' => 2]))
            ->assertOk()
            ->assertSee('HOSTEL PENDING 11')
            ->assertDontSee('HOSTEL PENDING 01')
            ->assertSee('Showing 11–11 of 11');
    }
}
