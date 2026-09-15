<?php

namespace Tests\Feature;

use App\Models\PaymentDetail;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MiscPaymentPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name' => 'Bursar',
            'email' => 'misc-payment@nebula.lk',
            'password' => Hash::make('password'),
            'user_role' => 'Bursar',
            'status' => '1',
            'user_location' => 'Welisara',
        ]);
    }

    private function makeStudent(): Student
    {
        return Student::forceCreate([
            'title' => 'Mr',
            'name_with_initials' => 'T. Student',
            'full_name' => 'Test Student',
            'id_type' => 'NIC',
            'id_value' => '200669502881',
            'gender' => 'Male',
            'email' => 'misc-student@test.lk',
            'status' => 'Registered',
            'academic_status' => 'active',
            'institute_location' => 'Welisara',
            'birthday' => '2006-06-15',
        ]);
    }

    public function test_page_renders_responsive_layout_and_sweetalert(): void
    {
        $this->actingAs($this->actor)
            ->get(route('misc.payment.index'))
            ->assertOk()
            ->assertSee('Miscellaneous Payment Entry')
            ->assertSee('misc-payment-page', false)
            ->assertSee('misc-payment-search', false)
            ->assertSee('Per page')
            ->assertSee('sweetalert2', false)
            ->assertDontSee('alert(', false)
            ->assertDontSee('confirm(', false);
    }

    public function test_fetch_requires_an_existing_student(): void
    {
        $this->actingAs($this->actor)
            ->getJson(route('misc.payment.fetch', ['studentId' => 'missing']))
            ->assertNotFound()
            ->assertJsonPath('success', false);
    }

    public function test_can_fetch_and_store_miscellaneous_payment_by_nic(): void
    {
        $student = $this->makeStudent();

        $this->actingAs($this->actor)
            ->getJson(route('misc.payment.fetch', ['studentId' => $student->id_value]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('student.full_name', 'Test Student')
            ->assertJsonPath('payments', []);

        $this->actingAs($this->actor)
            ->postJson(route('misc.payment.store'), [
                'student_id' => $student->id_value,
                'misc_category' => 'Library Fine',
                'amount' => 1500.50,
                'payment_method' => 'cash',
                'transaction_id' => 'TXN-1',
                'remarks' => 'Late book return',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('payment_details', [
            'student_id' => $student->student_id,
            'misc_category' => 'Library Fine',
            'payment_method' => 'cash',
            'status' => 'paid',
        ]);

        $list = $this->actingAs($this->actor)
            ->getJson(route('misc.payment.fetch', ['studentId' => $student->id_value]));

        $list->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('payments.0.misc_category', 'Library Fine');
    }

    public function test_store_requires_amount_and_category(): void
    {
        $this->actingAs($this->actor)
            ->postJson(route('misc.payment.store'), [])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_fetch_excludes_course_fee_payments(): void
    {
        $student = $this->makeStudent();

        PaymentDetail::forceCreate([
            'student_id' => $student->student_id,
            'course_registration_id' => null,
            'misc_category' => 'Hostel Fee',
            'amount' => 2000,
            'total_fee' => 2000,
            'remaining_amount' => 0,
            'status' => 'paid',
            'payment_method' => 'cash',
        ]);

        PaymentDetail::forceCreate([
            'student_id' => $student->student_id,
            'course_registration_id' => null,
            'amount' => 50000,
            'total_fee' => 50000,
            'remaining_amount' => 0,
            'installment_type' => 'course_fee',
            'status' => 'paid',
            'payment_method' => 'cash',
        ]);

        $this->actingAs($this->actor)
            ->getJson(route('misc.payment.fetch', ['studentId' => $student->student_id]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'payments')
            ->assertJsonPath('payments.0.misc_category', 'Hostel Fee');
    }
}
