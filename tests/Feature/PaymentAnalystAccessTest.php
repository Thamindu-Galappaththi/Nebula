<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentAnalystAccessTest extends TestCase
{
    use RefreshDatabase;

    private User $analyst;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->analyst = User::forceCreate([
            'name'          => 'Shanika',
            'email'         => 'shanika23@slt.com.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Payment Analyst',
            'user_roles'    => ['Payment Analyst'],
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->admin = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'admin-analyst@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara',
        ]);
    }

    public function test_payment_analyst_can_open_analytics_dashboard(): void
    {
        $this->actingAs($this->analyst)
            ->get(route('payment.summary'))
            ->assertOk()
            ->assertSee('Payment Analytics Dashboard')
            ->assertSee('Payment Dashboard')
            ->assertDontSee('Create User')
            ->assertDontSee('Student Payment Plan')
            ->assertDontSee('>Dashboard</span>', false);
    }

    public function test_payment_analyst_can_open_advanced_analytics(): void
    {
        $this->actingAs($this->analyst)
            ->get(route('payment.analytics'))
            ->assertOk()
            ->assertSee('payment-analytics', false);
    }

    public function test_dashboard_redirects_payment_analyst_to_analytics(): void
    {
        $this->actingAs($this->analyst)
            ->get(route('dashboard'))
            ->assertRedirect(route('payment.summary'));
    }

    public function test_payment_analyst_cannot_open_other_modules(): void
    {
        $this->actingAs($this->analyst)
            ->get(route('payment.index'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($this->analyst)
            ->get(route('course.management'))
            ->assertRedirect(route('dashboard'));

        $this->actingAs($this->analyst)
            ->get(route('create.user'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_admin_can_create_payment_analyst_user(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('user.create'), [
                'name'          => 'Shanika Perera',
                'email'         => 'shanika.analyst@slt.com.lk',
                'employee_id'   => 'PA-SHANIKA-23',
                'user_roles'    => ['Payment Analyst'],
                'user_location' => 'Nebula Institute of Technology – Welisara',
                'password'      => 'TempPass123',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('users', [
            'email'     => 'shanika.analyst@slt.com.lk',
            'user_role' => 'Payment Analyst',
        ]);
    }
}
