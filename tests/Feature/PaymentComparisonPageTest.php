<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentComparisonPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Bursar',
            'email'         => 'comparison@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Bursar',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_comparison_page_renders_with_mobile_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('payment.comparison'))
            ->assertOk()
            ->assertSee('Year-over-Year Comparison')
            ->assertSee('payment-comparison', false)
            ->assertSee('chart-wrap', false)
            ->assertSee('comparison-table', false)
            ->assertSee('data-label="Month"', false)
            ->assertSee('comparison-back-btn', false);
    }
}
