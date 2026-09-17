<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CourseManagementPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'courses@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_is_responsive_and_uses_sweetalert_edit_modal_and_ten_per_page(): void
    {
        $this->makeCourse('Intro Computing');

        $html = $this->actingAs($this->actor)
            ->get(route('course.management'))
            ->assertOk()
            ->assertSee('Create New Courses')
            ->assertSee('Existing Courses')
            ->assertSee('Intro Computing')
            ->assertSee('course-management-page', false)
            ->assertSee('editCourseModal', false)
            ->assertSee('modal-fullscreen-sm-down', false)
            ->assertSee('sweetalert2@11.22.0', false)
            ->assertSee('Swal.fire', false)
            ->assertSee('10 per page')
            ->assertSee('@media (max-width: 991.98px)', false)
            ->assertSee('data-label="Course Name"', false)
            ->assertSee('resetFilterSelect', false)
            ->assertSee(route('course.export'), false)
            ->getContent();

        $this->assertStringContainsString('value="10"', $html);
        $this->assertStringNotContainsString('Show All', $html);
        $this->assertStringNotContainsString("window.location.href = '/course-management?course_id='", $html);
        $this->assertStringNotContainsString('applyCourseFilters', $html);
        $this->assertStringNotContainsString('Table filtered to show only', $html);
    }

    public function test_courses_are_paginated_ten_per_page_without_full_html_on_ajax(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->makeCourse(sprintf('Alpha Course %02d', $i));
        }

        $this->actingAs($this->actor)
            ->get(route('course.management'))
            ->assertOk()
            ->assertSee('Alpha Course 01')
            ->assertSee('Alpha Course 10')
            ->assertDontSee('Alpha Course 11')
            ->assertSee('Showing 1–10 of 11');

        $pageTwo = $this->actingAs($this->actor)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('course.management', ['page' => 2]))
            ->assertOk()
            ->assertJsonStructure(['html', 'pagination']);

        $this->assertStringContainsString('Alpha Course 11', $pageTwo->json('html'));
        $this->assertStringNotContainsString('Alpha Course 01', $pageTwo->json('html'));
        $this->assertStringContainsString('Showing 11–11 of 11', $pageTwo->json('pagination'));
    }

    public function test_course_can_be_updated_from_modal_endpoint(): void
    {
        $course = $this->makeCourse('Old Course Name');

        $this->actingAs($this->actor)
            ->post(route('course.update', $course->course_id), [
                'location'            => 'Welisara',
                'course_type'         => 'degree',
                'semester_format'     => 'alphabetical',
                'course_name'         => 'Updated Course Name',
                'no_of_semesters'     => 6,
                'duration_years'      => 2,
                'duration_months'     => 6,
                'duration_days'       => 0,
                'min_credits'         => 90,
                'entry_qualification' => 'Diploma or equivalent',
                'conducted_by'        => 'Pearson',
                'course_medium'       => 'Sinhala',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('courses', [
            'course_id'       => $course->course_id,
            'course_name'     => 'Updated Course Name',
            'semester_format' => 'alphabetical',
            'course_medium'   => 'Sinhala',
            'duration'        => '2-6-0',
            'min_credits'     => 90,
        ]);
    }

    public function test_export_csv_includes_all_filtered_courses(): void
    {
        for ($i = 1; $i <= 11; $i++) {
            $this->makeCourse(sprintf('Degree Course %02d', $i), 'degree');
        }
        $this->makeCourse('Certificate Course', 'certificate');

        $csv = $this->actingAs($this->actor)
            ->get(route('course.export', ['course_type' => 'degree']))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->streamedContent();

        $this->assertStringContainsString('Degree Course 01', $csv);
        $this->assertStringContainsString('Degree Course 11', $csv);
        $this->assertStringNotContainsString('Certificate Course', $csv);
        $this->assertStringContainsString('Degree Program', $csv);
    }

    private function makeCourse(string $name, string $type = 'degree'): Course
    {
        return Course::forceCreate([
            'course_name'         => $name,
            'course_type'         => $type,
            'location'            => 'Welisara',
            'no_of_semesters'     => $type === 'certificate' ? 0 : 8,
            'duration'            => '3-0-0',
            'min_credits'         => $type === 'certificate' ? 0 : 120,
            'course_medium'       => 'English',
            'entry_qualification' => 'A/L or equivalent',
            'conducted_by'        => 'Pearson',
            'semester_format'     => 'numerical',
        ]);
    }
}
