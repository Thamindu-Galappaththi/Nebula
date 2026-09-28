<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Helpers\RoleHelper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

class UserCreationAndLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_dgm_can_create_new_user()
    {
        // Create a DGM user
        $dgmUser = User::create([
            'name' => 'DGM User',
            'email' => 'dgm@nebula.com',
            'password' => Hash::make('password123'),
            'user_role' => 'DGM',
            'status' => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        // Login as DGM
        $this->actingAs($dgmUser);

        // Test creating a new Librarian user
        $response = $this->post('/user/create', [
            'user_name' => 'New Librarian',
            'email' => 'librarian@nebula.com',
            'employee_id' => 'EMP001',
            'password' => 'password123',
            'user_role' => 'Librarian',
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Verify the user was created
        $this->assertDatabaseHas('users', [
            'email' => 'librarian@nebula.com',
            'user_role' => 'Librarian'
        ]);
    }

    public function test_non_dgm_cannot_create_user()
    {
        // Create a Librarian user
        $librarianUser = User::create([
            'name' => 'Librarian User',
            'email' => 'librarian@nebula.com',
            'password' => Hash::make('password123'),
            'user_role' => 'Librarian',
            'status' => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        // Login as Librarian
        $this->actingAs($librarianUser);

        // Test creating a new user (should fail)
        $response = $this->postJson('/user/create', [
            'user_name' => 'New User',
            'email' => 'newuser@nebula.com',
            'employee_id' => 'EMP002',
            'password' => 'password123',
            'user_role' => 'Student Counselor',
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        $response->assertStatus(403);
    }

    public function test_new_user_can_login_with_role_based_access()
    {
        // Create a new Librarian user
        $librarianUser = User::create([
            'name' => 'Test Librarian',
            'email' => 'testlibrarian@nebula.com',
            'password' => Hash::make('password123'),
            'user_role' => 'Librarian',
            'status' => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        // Test login
        $response = $this->post('/login', [
            'email' => 'testlibrarian@nebula.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect('/dashboard');

        // Verify user is authenticated
        $this->assertAuthenticated();

        // Verify user has correct role
        $this->assertEquals('Librarian', auth()->user()->user_role);

        // Test that Librarian can access library clearance
        $this->assertTrue(RoleHelper::hasPermission(auth()->user()->user_role, 'library.clearance'));

        // Test that Librarian cannot access student registration
        $this->assertFalse(RoleHelper::hasPermission(auth()->user()->user_role, 'student.registration'));
    }

    public function test_inactive_user_cannot_login()
    {
        // Create an inactive user
        $inactiveUser = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@nebula.com',
            'password' => Hash::make('password123'),
            'user_role' => 'Librarian',
            'status' => '0', // Inactive
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        // Test login
        $response = $this->post('/login', [
            'email' => 'inactive@nebula.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_without_role_cannot_login()
    {
        // Create a user without role
        $userWithoutRole = User::create([
            'name' => 'User Without Role',
            'email' => 'norole@nebula.com',
            'password' => Hash::make('password123'),
            'user_role' => null,
            'status' => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        // Test login
        $response = $this->post('/login', [
            'email' => 'norole@nebula.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_user_created_time_is_shown_in_sri_lanka_timezone(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-09-16 10:44:00', 'Asia/Colombo'));

        $user = User::forceCreate([
            'name'          => 'Created Time User',
            'email'         => 'created-time@nebula.lk',
            'password'      => Hash::make('password123'),
            'user_role'     => 'Program Administrator (level 01)',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $this->assertSame('2026-09-16 10:44', $user->createdAtSriLanka()?->format('Y-m-d H:i'));
        $this->assertNotSame('2026-09-16 16:14', $user->createdAtSriLanka()?->format('Y-m-d H:i'));

        Carbon::setTestNow();
    }
} 