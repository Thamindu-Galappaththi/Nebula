<?php

namespace Tests\Feature;

use App\Models\Discount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentDiscountPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name' => 'Bursar',
            'email' => 'discount@nebula.lk',
            'password' => Hash::make('password'),
            'user_role' => 'Bursar',
            'status' => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_page_renders_edit_modal_pagination_and_sweetalert(): void
    {
        $this->actingAs($this->actor)
            ->get(route('payment.discount.page'))
            ->assertOk()
            ->assertSee('Payment Discount')
            ->assertSee('editDiscountModal')
            ->assertSee('Per page')
            ->assertSee('sweetalert2', false)
            ->assertDontSee('alert(', false)
            ->assertDontSee('confirm(', false);
    }

    public function test_save_requires_name_type_and_value(): void
    {
        $response = $this->actingAs($this->actor)
            ->postJson(route('payment.discount.save.discount'), []);

        $response->assertStatus(422)->assertJson(['success' => false]);
    }

    public function test_percentage_cannot_exceed_one_hundred(): void
    {
        $response = $this->actingAs($this->actor)
            ->postJson(route('payment.discount.save.discount'), [
                'name' => 'Staff',
                'type' => 'percentage',
                'discount_category' => 'local_course_fee',
                'value' => 150,
            ]);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
        $this->assertStringContainsString('100', (string) $response->json('message'));
    }

    public function test_can_save_and_list_discounts_by_category(): void
    {
        $this->actingAs($this->actor)
            ->postJson(route('payment.discount.save.discount'), [
                'name' => 'Early Bird',
                'type' => 'amount',
                'discount_category' => 'local_course_fee',
                'value' => 2500,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $list = $this->actingAs($this->actor)
            ->postJson(route('payment.discount.get.discounts.by.category'), [
                'category' => 'local_course_fee',
                'page' => 1,
                'per_page' => 10,
            ]);

        $list->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('discounts.0.name', 'Early Bird');
    }

    public function test_category_list_paginates(): void
    {
        foreach (range(1, 12) as $i) {
            Discount::create([
                'name' => 'Discount ' . $i,
                'type' => 'amount',
                'discount_category' => 'registration_fee',
                'value' => 100 + $i,
                'status' => 'active',
            ]);
        }

        $page1 = $this->actingAs($this->actor)
            ->postJson(route('payment.discount.get.discounts.by.category'), [
                'category' => 'registration_fee',
                'page' => 1,
                'per_page' => 10,
            ]);

        $page1->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('total', 12)
            ->assertJsonPath('last_page', 2)
            ->assertJsonPath('from', 1)
            ->assertJsonPath('to', 10);
        $this->assertCount(10, $page1->json('discounts'));

        $page2 = $this->actingAs($this->actor)
            ->postJson(route('payment.discount.get.discounts.by.category'), [
                'category' => 'registration_fee',
                'page' => 2,
                'per_page' => 10,
            ]);

        $page2->assertOk()
            ->assertJsonPath('from', 11)
            ->assertJsonPath('to', 12);
        $this->assertCount(2, $page2->json('discounts'));
    }

    public function test_update_and_delete_discount(): void
    {
        $discount = Discount::create([
            'name' => 'Old Name',
            'type' => 'amount',
            'discount_category' => 'local_course_fee',
            'value' => 500,
            'status' => 'active',
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('payment.discount.update.discount'), [
                'id' => $discount->id,
                'name' => 'New Name',
                'type' => 'percentage',
                'discount_category' => 'local_course_fee',
                'value' => 10,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('discount.name', 'New Name');

        $this->actingAs($this->actor)
            ->postJson(route('payment.discount.delete.discount'), [
                'id' => $discount->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame('inactive', $discount->fresh()->status);
    }
}
