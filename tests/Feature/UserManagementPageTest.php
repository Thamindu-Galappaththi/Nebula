<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementPageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Program Admin',
            'email'         => 'users@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara',
        ]);
    }

    public function test_edit_modal_uses_campus_keys_and_shows_location_names(): void
    {
        $this->actingAs($this->actor)
            ->get(route('dgm.user.management'))
            ->assertOk()
            ->assertSee('value="Welisara"', false)
            ->assertSee('Nebula Institute of Technology – Welisara', false)
            ->assertSee('data-user-location=', false)
            ->assertSee('setEditUserLocation', false);
    }

    public function test_user_details_include_campus_key_for_stored_location_name(): void
    {
        $user = User::forceCreate([
            'name'          => 'Campus User',
            'email'         => 'campus-user@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Librarian',
            'status'        => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara',
        ]);

        $this->actingAs($this->actor)
            ->postJson(route('user.getDetails'), ['user_id' => $user->user_id])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('user.user_location', 'Nebula Institute of Technology – Welisara')
            ->assertJsonPath('user.user_location_key', 'Welisara');
    }

    public function test_user_list_shows_full_campus_location_name(): void
    {
        User::forceCreate([
            'name'          => 'Short Location User',
            'email'         => 'short-location@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Student Counselor',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->actingAs($this->actor)
            ->get(route('dgm.user.management'))
            ->assertOk()
            ->assertSee('Short Location User')
            ->assertSee('>Nebula Institute of Technology – Welisara</td>', false)
            ->assertDontSee('>Welisara</td>', false);
    }
}
