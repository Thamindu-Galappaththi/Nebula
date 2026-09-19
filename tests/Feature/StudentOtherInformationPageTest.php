<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StudentOtherInformationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_controls_stack_on_mobile(): void
    {
        $actor = User::forceCreate([
            'name'          => 'Counselor',
            'email'         => 'other-info@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Student Counselor',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->actingAs($actor)
            ->get(route('student_management.other.information'))
            ->assertOk()
            ->assertSee('student-other-info-page', false)
            ->assertSee('student-other-search-controls', false)
            ->assertSee('Enter Student ID (NIC)')
            ->assertSee('col-12 col-md-3', false)
            ->assertDontSee('col-sm-8', false)
            ->assertDontSee('col-sm-2', false);
    }
}
