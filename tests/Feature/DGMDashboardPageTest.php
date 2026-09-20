<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DGMDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_students_revenues_and_outstanding_tabs_have_clear_filters(): void
    {
        $dgm = User::forceCreate([
            'name'          => 'DGM User',
            'email'         => 'dgm-dashboard@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'DGM',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->actingAs($dgm)
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
            ->assertDontSee('clearStudentFiltersBtn" class="px-4 py-2 bg-white', false);
    }
}
