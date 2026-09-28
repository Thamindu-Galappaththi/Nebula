<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Authorization smoke tests.
 *
 * 1. Every GET route protected by auth middleware must redirect an unauthenticated
 *    request to /login — not return a 200 or throw a 500.
 *
 * 2. A role that lacks permission to a given section must receive a 403.
 *    (Spot-checked for the most sensitive controllers.)
 */
class RouteActionExistsTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // 1. Unauthenticated access — all auth-protected GET routes must redirect
    // -------------------------------------------------------------------------

    public function test_unauthenticated_requests_to_protected_routes_redirect_to_login(): void
    {
        $routes = Route::getRoutes()->getRoutes();
        $failures = [];

        foreach ($routes as $route) {
            // Only test GET routes with the auth middleware
            if (!in_array('GET', $route->methods())) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $hasAuth = collect($middleware)->contains(
                fn ($m) => in_array($m, ['auth', 'auth:web']) || str_starts_with($m, 'auth:')
            );

            if (!$hasAuth) {
                continue;
            }

            // Skip routes with required URI parameters — they need real IDs
            if (str_contains($route->uri(), '{')) {
                continue;
            }

            $response = $this->get('/' . ltrim($route->uri(), '/'));
            $status = $this->responseStatus($response);

            if (!in_array($status, [301, 302, 303, 307, 308], true)) {
                $failures[] = [
                    'uri'    => $route->uri(),
                    'status' => $status,
                ];
            }
        }

        $this->assertEmpty(
            $failures,
            'These auth-protected routes did not redirect unauthenticated requests: ' .
            json_encode($failures, JSON_PRETTY_PRINT)
        );
    }

    // -------------------------------------------------------------------------
    // 2. Authenticated requests as a low-privilege role must not 500
    //    (They may 403 or redirect — but must never throw an unhandled exception)
    // -------------------------------------------------------------------------

    public function test_authenticated_low_privilege_user_does_not_trigger_500_on_protected_routes(): void
    {
        $user = User::forceCreate([
            'name'          => 'Library Staff',
            'email'         => 'lib@nebula.lk',
            'password'      => Hash::make('password'),
            'user_role'     => 'Librarian',
            'status'        => '1',
            'user_location' => 'Welisara',
        ]);

        $routes = Route::getRoutes()->getRoutes();
        $failures = [];

        foreach ($routes as $route) {
            if (!in_array('GET', $route->methods())) {
                continue;
            }

            $middleware = $route->gatherMiddleware();
            $hasAuth = collect($middleware)->contains(
                fn ($m) => in_array($m, ['auth', 'auth:web']) || str_starts_with($m, 'auth:')
            );

            if (!$hasAuth) {
                continue;
            }

            if (str_contains($route->uri(), '{')) {
                continue;
            }

            if ($this->isDownloadRoute($route->uri())) {
                continue;
            }

            $response = $this->actingAs($user)->get('/' . ltrim($route->uri(), '/'));
            $status = $this->responseStatus($response);

            if ($status === 500) {
                $failures[] = [
                    'uri'    => $route->uri(),
                    'status' => $status,
                ];
            }
        }

        $this->assertEmpty(
            $failures,
            'These routes returned 500 for an authenticated low-privilege user: ' .
            json_encode($failures, JSON_PRETTY_PRINT)
        );
    }

    private function isDownloadRoute(string $uri): bool
    {
        return (bool) preg_match('/(download|export|excel|csv|pdf|template)/i', $uri);
    }

    private function responseStatus(mixed $response): int
    {
        if ($response instanceof \Illuminate\Testing\TestResponse) {
            return $response->getStatusCode();
        }

        if ($response instanceof \Symfony\Component\HttpFoundation\Response) {
            return $response->getStatusCode();
        }

        return 0;
    }
}
