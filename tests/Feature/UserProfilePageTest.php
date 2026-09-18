<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserProfilePageTest extends TestCase
{
    use RefreshDatabase;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actor = User::forceCreate([
            'name'          => 'Profile User',
            'email'         => 'profile@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);
    }

    public function test_profile_page_is_responsive_and_shows_full_location_name(): void
    {
        $this->actingAs($this->actor)
            ->get(route('user.profile'))
            ->assertOk()
            ->assertSee('User Profile')
            ->assertSee('tab-pane fade show active', false)
            ->assertSee('user-profile-page', false)
            ->assertSee('@media (max-width: 767.98px)', false)
            ->assertSee('nonce=', false)
            ->assertSee('profilePictureForm', false)
            ->assertSee('resetPasswordFields', false)
            ->assertSee('hide.bs.tab', false)
            ->assertSee('Nebula Institute of Technology – Welisara', false)
            ->assertDontSee('onclick="togglePassword', false);
    }

    public function test_profile_picture_can_be_uploaded(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('avatar.jpg', 200, 200);

        $this->actingAs($this->actor)
            ->postJson(route('user.updateProfilePicture'), [
                'profile_picture' => $file,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->actor->refresh();
        $this->assertNotEmpty($this->actor->user_profile);
        Storage::disk('public')->assertExists($this->actor->user_profile);
    }

    public function test_profile_picture_upload_rejects_invalid_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->actor)
            ->postJson(route('user.updateProfilePicture'), [
                'profile_picture' => UploadedFile::fake()->create('notes.pdf', 20, 'application/pdf'),
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
