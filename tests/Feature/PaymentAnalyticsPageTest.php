<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentAnalyticsPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Bursar',
            'email'         => 'analytics@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Bursar',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_analytics_page_renders_with_mobile_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('payment.analytics'))
            ->assertOk()
            ->assertSee('Advanced Analytics')
            ->assertSee('payment-analytics', false)
            ->assertSee('chart-wrap', false)
            ->assertSee('analytics-course-table', false)
            ->assertSee('analytics-back-btn', false)
            ->assertSee('analytics-month-filter', false);
    }
}
