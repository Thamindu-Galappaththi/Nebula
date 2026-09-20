<?php

namespace Tests\Feature;

use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DGMDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    private User $dgm;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dgm = User::forceCreate([
            'name'          => 'DGM User',
            'email'         => 'dgm-dashboard@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'DGM',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_students_revenues_and_outstanding_tabs_have_clear_filters(): void
    {
        $this->actingAs($this->dgm)
            ->get(route('dgmdashboard'))
            ->assertOk()
            ->assertSee('Apply Filters')
            ->assertSee('Clear Filters')
            ->assertSee('id="clearStudentFiltersBtn"', false)
            ->assertSee('id="clearRevenueFiltersBtn"', false)
            ->assertSee('id="clearOutstandingFiltersBtn"', false)
            ->assertSee('clearStudentFilters', false)
            ->assertSee('clearRevenueFilters', false)
            ->assertSee('clearOutstandingFilters', false)
            ->assertSee('dashboard-filter-actions', false)
            ->assertSee('bg-gray-600 text-white', false)
            ->assertSee('rows.map(function (row) { return row.name; })', false)
            ->assertSee('rows.map(function (row) { return row.value; })', false)
            ->assertSee('dgm-dashboard-page', false)
            ->assertSee('dgm-kpi-grid', false)
            ->assertSee('dgm-chart-box', false)
            ->assertSee('body:has(.dgm-dashboard-page)', false);
    }

    public function test_marketing_survey_labels_match_counts_after_splitting_sources(): void
    {
        $this->makeStudent('199011111V', 'Facebook');
        $this->makeStudent('199022222V', 'LinkedIn, Facebook');
        $this->makeStudent('199033333V', 'Radio Advertisement');
        $this->makeStudent('199044444V', '');

        $response = $this->actingAs($this->dgm)
            ->get('/api/dashboard/marketing-data?year=' . date('Y'))
            ->assertOk()
            ->assertJsonStructure(['labels', 'counts']);

        $labels = $response->json('labels');
        $counts = $response->json('counts');

        $this->assertSame(count($labels), count($counts));
        $this->assertSame($labels, array_values($labels));
        $this->assertSame($counts, array_values($counts));

        $mapped = array_combine($labels, $counts);
        $this->assertSame(2, $mapped['Facebook']);
        $this->assertSame(1, $mapped['LinkedIn']);
        $this->assertSame(1, $mapped['Radio Advertisement']);
        $this->assertArrayNotHasKey('LinkedIn, Facebook', $mapped);
        $this->assertArrayNotHasKey('', $mapped);
    }

    private function makeStudent(string $nic, ?string $survey): Student
    {
        return Student::forceCreate([
            'title'              => 'Mr',
            'name_with_initials' => 'T. Student',
            'full_name'          => 'Test Student ' . $nic,
            'id_type'            => 'National id',
            'id_value'           => $nic,
            'gender'             => 'Male',
            'email'              => $nic . '@test.lk',
            'status'             => 'Unmarried',
            'institute_location' => 'Welisara',
            'marketing_survey'   => $survey,
        ]);
    }
}
