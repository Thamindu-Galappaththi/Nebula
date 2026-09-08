<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ReportingRouteTest extends TestCase
{
    use RefreshDatabase;

    public function test_reporting_dashboard_route_renders_for_authorized_user(): void
    {
        $user = User::forceCreate([
            'name' => 'Reporting Developer',
            'email' => 'reporting@example.com',
            'password' => Hash::make('password'),
            'user_role' => 'Developer',
            'status' => '1',
            'user_location' => 'Welisara',
        ]);

        $response = $this->actingAs($user)->get(route('reporting.dashboard'));

        $response->assertOk();
        $response->assertSee('Reporting');
        $response->assertViewIs('reporting.dashboard');
    }
}
