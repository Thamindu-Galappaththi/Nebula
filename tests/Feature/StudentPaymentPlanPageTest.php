<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentPaymentPlanPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name' => 'Bursar',
            'email' => 'student-payment-plan@nebula.lk',
            'password' => Hash::make('password'),
            'user_role' => 'Bursar',
            'status' => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_pagination_sweetalert_and_responsive_layout(): void
    {
        $this->actingAs($this->actor)
            ->get(route('payment.index'))
            ->assertOk()
            ->assertSee('Student Payment Plan')
            ->assertSee('payment-page-tabs', false)
            ->assertSee('payment-rate-group', false)
            ->assertSee('combo-control', false)
            ->assertSee('paymentRecordsPaginationBar', false)
            ->assertSee('Per page')
            ->assertSee('sweetalert2', false)
            ->assertSee('confirmDelete', false)
            ->assertDontSee('alert(', false)
            ->assertDontSee('confirm(', false);
    }
}
