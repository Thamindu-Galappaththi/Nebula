<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Intake;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class IntakeCreationPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'intakes@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_is_responsive_and_uses_sweetalert_edit_modal_and_ten_per_page(): void
    {
        $course = $this->makeCourse('Intro Computing');
        $this->makeIntake($course, '2024-Sep-CS');

        $html = $this->actingAs($this->actor)
            ->get(route('intake.create'))
            ->assertOk()
            ->assertSee('Create New Intake')
            ->assertSee('Existing Intakes')
            ->assertSee('2024-Sep-CS')
            ->assertSee('intake-creation-page', false)
            ->assertSee('editIntakeModal', false)
            ->assertSee('modal-fullscreen-sm-down', false)
            ->assertSee('modal-xl', false)
            ->assertSee('record-edit-modal', false)
            ->assertSee('modal-content > form', false)
            ->assertSee('sweetalert2@11.22.0', false)
            ->assertSee('Swal.fire', false)
            ->assertSee('10 per page')
            ->assertSee('@media (max-width: 991.98px)', false)
            ->assertSee('data-label="Course Name"', false)
            ->assertSee('resetFilterSelect', false)
            ->assertSee(route('intake.export'), false)
            ->getContent();

        $this->assertStringContainsString('value="10"', $html);
        $this->assertStringNotContainsString('Show All', $html);
        $this->assertStringNotContainsString("window.location = '?location='", $html);
        $this->assertStringNotContainsString('applyIntakeFilters', $html);
    }

    public function test_intakes_are_paginated_ten_per_page_without_full_html_on_ajax(): void
    {
        $course = $this->makeCourse('Alpha Program');
        for ($i = 1; $i <= 11; $i++) {
            $this->makeIntake($course, sprintf('Alpha Intake %02d', $i));
        }

        $this->actingAs($this->actor)
            ->get(route('intake.create'))
            ->assertOk()
            ->assertSee('Alpha Intake 01')
            ->assertSee('Alpha Intake 10')
            ->assertDontSee('Alpha Intake 11')
            ->assertSee('Showing 1–10 of 11');

        $pageTwo = $this->actingAs($this->actor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('intake.create', ['page' => 2]))
            ->assertOk()
            ->assertJsonStructure(['html', 'pagination']);

        $this->assertStringContainsString('Alpha Intake 11', $pageTwo->json('html'));
        $this->assertStringNotContainsString('Alpha Intake 01', $pageTwo->json('html'));
        $this->assertStringContainsString('Showing 11–11 of 11', $pageTwo->json('pagination'));
    }

    public function test_intake_can_be_updated_from_modal_endpoint(): void
    {
        $course = $this->makeCourse('Old Program');
        $intake = $this->makeIntake($course, 'OLD-BATCH');

        $this->actingAs($this->actor)
            ->put(route('intake.update', $intake->intake_id), [
                'location'                          => 'Welisara',
                'course_id'                         => $course->course_id,
                'batch'                             => 'NEW-BATCH',
                'batch_size'                        => 40,
                'intake_mode'                       => 'Online',
                'intake_type'                       => 'Parttime',
                'registration_fee'                  => 6000,
                'franchise_payment'                 => 15000,
                'franchise_payment_currency'        => 'USD',
                'course_fee'                        => 300000,
                'sscl_tax'                          => 10,
                'bank_charges'                      => 250,
                'start_date'                        => '2026-02-01',
                'end_date'                          => '2026-11-30',
                'enrollment_end_date'               => '2026-01-15',
                'course_registration_id_pattern'    => 'REG-2026-001',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('intakes', [
            'intake_id'   => $intake->intake_id,
            'batch'       => 'NEW-BATCH',
            'intake_mode' => 'Online',
            'intake_type' => 'Parttime',
            'batch_size'  => 40,
        ]);
    }

    public function test_export_csv_includes_all_filtered_intakes(): void
    {
        $welisara = $this->makeCourse('Welisara Program', 'degree', 'Welisara');
        $moratuwa = $this->makeCourse('Moratuwa Program', 'degree', 'Moratuwa');
        for ($i = 1; $i <= 11; $i++) {
            $this->makeIntake($welisara, sprintf('Welisara Intake %02d', $i), 'Welisara');
        }
        $this->makeIntake($moratuwa, 'Moratuwa Intake', 'Moratuwa', [
            'start_date'           => '2024-02-01',
            'end_date'             => '2024-11-30',
            'enrollment_end_date'  => '2024-01-15',
            'batch_size'           => 12,
        ]);

        $csv = $this->actingAs($this->actor)
            ->get(route('intake.export', ['location' => 'Welisara']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString('Welisara Intake 01', $csv);
        $this->assertStringContainsString('Welisara Intake 11', $csv);
        $this->assertStringNotContainsString('Moratuwa Intake', $csv);
        $this->assertStringContainsString('Course Name', $csv);
        $this->assertStringContainsString('Start Date', $csv);
        $this->assertStringContainsString('End Date', $csv);
        $this->assertStringContainsString('Enrollment End', $csv);
        $this->assertStringContainsString('Capacity', $csv);
        $this->assertStringContainsString('2026-01-01', $csv);
        $this->assertStringContainsString('2026-12-31', $csv);
        $this->assertStringContainsString('2025-12-15', $csv);
        $this->assertStringContainsString('0 / 30', $csv);
        $this->assertStringNotContainsString('2024-02-01', $csv);
        $this->assertStringNotContainsString('0 / 12', $csv);
    }

    public function test_export_csv_includes_date_and_capacity_matches_from_search(): void
    {
        $course = $this->makeCourse('Searchable Program');
        $this->makeIntake($course, 'Date Match Batch', 'Welisara', [
            'start_date'          => '2026-03-15',
            'end_date'            => '2026-09-20',
            'enrollment_end_date' => '2026-03-01',
            'batch_size'          => 45,
        ]);
        $this->makeIntake($course, 'Other Batch', 'Welisara', [
            'start_date'          => '2025-01-10',
            'end_date'            => '2025-06-10',
            'enrollment_end_date' => '2025-01-01',
            'batch_size'          => 8,
        ]);

        $byDate = $this->actingAs($this->actor)
            ->get(route('intake.export', ['search' => '2026-03-15']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Date Match Batch', $byDate);
        $this->assertStringContainsString('2026-03-15', $byDate);
        $this->assertStringContainsString('2026-09-20', $byDate);
        $this->assertStringContainsString('2026-03-01', $byDate);
        $this->assertStringContainsString('0 / 45', $byDate);
        $this->assertStringNotContainsString('Other Batch', $byDate);

        $byCapacity = $this->actingAs($this->actor)
            ->get(route('intake.export', ['search' => '45']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Date Match Batch', $byCapacity);
        $this->assertStringContainsString('0 / 45', $byCapacity);
        $this->assertStringNotContainsString('Other Batch', $byCapacity);
    }

    private function makeCourse(string $name, string $type = 'degree', string $location = 'Welisara'): Course
    {
        return Course::forceCreate([
            'course_name'         => $name,
            'course_type'         => $type,
            'location'            => $location,
            'no_of_semesters'     => 8,
            'duration'            => '3-0-0',
            'min_credits'         => 120,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 'Pearson',
            'semester_format'     => 'numerical',
        ]);
    }

    private function makeIntake(Course $course, string $batch, string $location = 'Welisara', array $overrides = []): Intake
    {
        return Intake::forceCreate(array_merge([
            'location'                       => $location,
            'course_id'                      => $course->course_id,
            'course_name'                    => $course->course_name,
            'batch'                          => $batch,
            'batch_size'                     => 30,
            'intake_mode'                    => 'Physical',
            'intake_type'                    => 'Fulltime',
            'registration_fee'               => 5000,
            'franchise_payment'              => 10000,
            'franchise_payment_currency'     => 'LKR',
            'course_fee'                     => 250000,
            'sscl_tax'                       => 15,
            'bank_charges'                   => 500,
            'start_date'                     => '2026-01-01',
            'end_date'                       => '2026-12-31',
            'enrollment_end_date'            => '2025-12-15',
            'course_registration_id_pattern' => 'REG-2026-001',
        ], $overrides));
    }
}
