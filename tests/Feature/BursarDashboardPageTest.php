<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BursarDashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_status_is_not_the_same_blue_as_review(): void
    {
        $bursar = User::forceCreate([
            'name'          => 'Bursar',
            'email'         => 'bursar-dashboard@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Bursar',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->actingAs($bursar)
            ->get(route('bursar.dashboard'))
            ->assertOk()
            ->assertSee('.badge-pending { background: #f59e0b; color: #1f2937; }', false)
            ->assertDontSee('.badge-pending { background: #0d6efd; color: white; }', false)
            ->assertSee('badge bg-warning text-dark', false);
    }
}
