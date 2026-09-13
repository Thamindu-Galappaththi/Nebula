<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentSummaryPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Bursar',
            'email'         => 'summary@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Bursar',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_courses_by_location_return_data_array_for_filters(): void
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

        $this->actingAs($this->actor)
            ->get(route('payment.summary.courses.by.location', [
                'location' => 'Welisara',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.course_id', $course->course_id)
            ->assertJsonPath('courses.0.course_name', 'BTEC Computing');
    }

    public function test_summary_script_does_not_contain_broken_try_catch(): void
    {
        $source = file_get_contents(resource_path('views/payments/summary.blade.php'));

        $this->assertStringNotContainsString(
            "courseFilter.appendChild(option);\n                });\n            }\n            courseFilter.disabled = false;",
            $source
        );
        $this->assertStringContainsString('courseListFromPayload(payload).forEach', $source);
        $this->assertStringContainsString("initChart('monthlyChart'", $source);
        $this->assertStringNotContainsString('height: auto !important', $source);
    }
}
