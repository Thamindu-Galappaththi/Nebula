<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Exceptions\Handler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;

class AuthenticationValidationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test password constant - should be defined via environment variable in production
     */
    protected const TEST_PASSWORD = 'TestPassword123!@#';

    public function test_login_with_valid_credentials()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@nebula.com',
            'password' => Hash::make(self::TEST_PASSWORD),
            'user_role' => 'Librarian',
            'status' => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        // Attempt login
        $response = $this->post('/login', [
            'email' => 'test@nebula.com',
            'password' => self::TEST_PASSWORD
        ]);

        // Should redirect to dashboard
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_with_invalid_email()
    {
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => self::TEST_PASSWORD
        ]);

        $response->assertSessionHasErrors(['email']);
        $this->assertGuest();
    }

    public function test_login_with_empty_fields()
    {
        $response = $this->post('/login', [
            'email' => '',
            'password' => ''
        ]);

        $response->assertSessionHasErrors(['email', 'password']);
        $this->assertGuest();
    }

    public function test_login_with_nonexistent_user()
    {
        $response = $this->post('/login', [
            'email' => 'nonexistent@nebula.com',
            'password' => self::TEST_PASSWORD
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_login_with_wrong_password()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@nebula.com',
            'password' => Hash::make(self::TEST_PASSWORD),
            'user_role' => 'Librarian',
            'status' => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        $response = $this->post('/login', [
            'email' => 'test@nebula.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_login_with_inactive_user()
    {
        // Create an inactive user
        $user = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@nebula.com',
            'password' => Hash::make(self::TEST_PASSWORD),
            'user_role' => 'Librarian',
            'status' => '0', // Inactive
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        $response = $this->post('/login', [
            'email' => 'inactive@nebula.com',
            'password' => self::TEST_PASSWORD
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_login_with_user_without_role()
    {
        // Create a user without role
        $user = User::create([
            'name' => 'No Role User',
            'email' => 'norole@nebula.com',
            'password' => Hash::make(self::TEST_PASSWORD),
            'user_role' => null,
            'status' => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        $response = $this->post('/login', [
            'email' => 'norole@nebula.com',
            'password' => self::TEST_PASSWORD
        ]);

        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_login_throttling()
    {
        // Clear any existing cache
        Cache::flush();

        // Attempt multiple failed logins
        for ($i = 0; $i < 6; $i++) {
            $response = $this->post('/login', [
                'email' => 'test@nebula.com',
                'password' => 'wrongpassword'
            ]);
        }

        // The 6th attempt should be blocked
        $response->assertSessionHasErrors(['login']);
        $this->assertGuest();
    }

    public function test_wrong_password_shows_alert_without_highlighting_username(): void
    {
        User::create([
            'name' => 'Test User',
            'email' => 'test@nebula.com',
            'password' => Hash::make(self::TEST_PASSWORD),
            'user_role' => 'Librarian',
            'status' => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        $this->from(route('login'))->post('/login', [
            'email' => 'test@nebula.com',
            'password' => 'wrongpassword'
        ])->assertRedirect(route('login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Invalid username or password. Please try again.')
            ->assertSee('invalid-feedback', false)
            ->assertDontSee('alert-danger', false)
            ->assertDontSee('is-invalid', false);
    }

    public function test_login_page_is_mobile_friendly(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('login-page', false)
            ->assertSee('login-shell', false)
            ->assertSee('viewport-fit=cover', false)
            ->assertSee('id="togglePassword"', false)
            ->assertSee('type="button"', false)
            ->assertSee('Sign In');
    }

    public function test_expired_csrf_on_login_returns_to_form_with_friendly_message(): void
    {
        $request = Request::create('/login', 'POST', [
            'email' => 'test@nebula.com',
            'password' => 'secret',
            '_token' => 'stale-token',
        ]);
        $request->headers->set('Accept', 'text/html');
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);

        $response = $this->app->make(Handler::class)
            ->render($request, new TokenMismatchException('CSRF token mismatch.'));

        $this->assertTrue($response->isRedirect(route('login')));
        $this->assertSame(
            'Your session expired. Please try again.',
            $response->getSession()->get('errors')->first('login')
        );
        $this->assertSame('test@nebula.com', $response->getSession()->getOldInput('email'));
        $this->assertNull($response->getSession()->getOldInput('password'));
    }

    public function test_expired_csrf_json_returns_friendly_message(): void
    {
        $request = Request::create('/login', 'POST', [
            'email' => 'test@nebula.com',
            'password' => 'secret',
            '_token' => 'stale-token',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $request->setLaravelSession($this->app['session.store']);
        $this->app->instance('request', $request);

        $response = $this->app->make(Handler::class)
            ->render($request, new TokenMismatchException('CSRF token mismatch.'));

        $this->assertSame(419, $response->getStatusCode());
        $this->assertSame('Your session expired. Please try again.', $response->getData(true)['message']);
    }

    public function test_login_form_displays_validation_errors()
    {
        $response = $this->get('/login');
        
        // Submit form with invalid data
        $response = $this->post('/login', [
            'email' => 'invalid-email',
            'password' => ''
        ]);

        // Should return to login page with errors
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_successful_login_redirects_to_dashboard()
    {
        // Create a test user
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@nebula.com',
            'password' => Hash::make('password123'),
            'user_role' => 'Librarian',
            'status' => '1',
            'user_location' => 'Nebula Institute of Technology – Welisara'
        ]);

        $response = $this->post('/login', [
            'email' => 'test@nebula.com',
            'password' => 'password123'
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
    }

    public function test_login_preserves_old_input_on_failure()
    {
        $response = $this->post('/login', [
            'email' => 'test@nebula.com',
            'password' => 'wrongpassword'
        ]);

        $response->assertSessionHas('_old_input');
        $this->assertGuest();
    }
} 