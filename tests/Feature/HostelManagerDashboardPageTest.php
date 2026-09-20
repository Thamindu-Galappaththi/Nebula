<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class HostelManagerDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_status_is_not_the_same_blue_as_review(): void
    {
        $manager = User::forceCreate([
            'name'          => 'Hostel Manager',
            'email'         => 'hostel-manager-dashboard@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Hostel Manager',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->actingAs($manager)
            ->get(route('hostel.manager.dashboard'))
            ->assertOk()
            ->assertSee('.badge-pending { background: #f59e0b; color: #1f2937; }', false)
            ->assertDontSee('.badge-pending { background: #0d6efd; color: white; }', false)
            ->assertSee('badge bg-warning text-dark', false);
    }
}
