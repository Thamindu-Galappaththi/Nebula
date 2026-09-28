<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LoginThrottle
{
    private int $maxIpAttempts = 10;
    private int $maxEmailAttempts = 5;
    private int $ipDecaySeconds = 300;
    private int $emailDecaySeconds = 900;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $email = $request->input('email');
        $ip = $request->ip();
        
        // Check if this IP is blocked
        if ($this->isIpBlocked($ip)) {
            Log::warning('Blocked login attempt from IP', [
                'ip' => $ip,
                'email' => $email,
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->failedLoginResponse(
                $request,
                $email,
                'Too many login attempts. Please try again later.',
                429
            );
        }
        
        // Check if this email is blocked
        if ($email && $this->isEmailBlocked($email)) {
            Log::warning('Blocked login attempt for email', [
                'email' => $email,
                'ip' => $ip,
                'user_agent' => $request->userAgent()
            ]);
            
            return $this->failedLoginResponse(
                $request,
                $email,
                'Too many login attempts for this account. Please try again later.',
                429
            );
        }
        
        $response = $next($request);

        if (Auth::check()) {
            $this->clearAttempts($ip, $email);
            return $response;
        }

        if ($this->shouldTrackFailedLogin($request)) {
            $this->incrementFailedAttempts($ip, $email);
        }
        
        return $response;
    }
    
    /**
     * Check if IP is blocked
     */
    private function isIpBlocked(string $ip): bool
    {
        $key = "login_attempts_ip_{$ip}";
        $attempts = Cache::get($key, 0);
        
        return $attempts >= $this->maxIpAttempts;
    }
    
    /**
     * Check if email is blocked
     */
    private function isEmailBlocked(string $email): bool
    {
        $key = "login_attempts_email_{$email}";
        $attempts = Cache::get($key, 0);
        
        return $attempts >= $this->maxEmailAttempts;
    }
    
    /**
     * Increment failed login attempts
     */
    private function incrementFailedAttempts(string $ip, ?string $email): void
    {
        // Increment IP attempts
        $ipKey = "login_attempts_ip_{$ip}";
        $ipAttempts = Cache::get($ipKey, 0) + 1;
        Cache::put($ipKey, $ipAttempts, $this->ipDecaySeconds);
        
        // Increment email attempts
        if ($email) {
            $emailKey = "login_attempts_email_{$email}";
            $emailAttempts = Cache::get($emailKey, 0) + 1;
            Cache::put($emailKey, $emailAttempts, $this->emailDecaySeconds);
        }
        
        Log::warning('Failed login attempt', [
            'ip' => $ip,
            'email' => $email,
            'ip_attempts' => $ipAttempts,
            'email_attempts' => $emailAttempts ?? 0
        ]);
    }

    /**
     * Clear login attempt counters after a successful login.
     */
    private function clearAttempts(string $ip, ?string $email): void
    {
        Cache::forget("login_attempts_ip_{$ip}");

        if ($email) {
            Cache::forget("login_attempts_email_{$email}");
        }
    }

    /**
     * Track only true credential failures to avoid throttling form validation or account-state errors.
     */
    private function shouldTrackFailedLogin(Request $request): bool
    {
        if (!$request->routeIs('login.authenticate') || !$request->session()->has('errors')) {
            return false;
        }

        $errors = $request->session()->get('errors');
        if (!$errors) {
            return false;
        }

        $loginError = strtolower((string) $errors->first('login'));
        $emailError = strtolower((string) $errors->first('email'));

        return str_contains($loginError, 'invalid username or password')
            || str_contains($emailError, 'invalid username or password');
    }

    private function failedLoginResponse(Request $request, ?string $email, string $message, int $status)
    {
        if ($request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
                'errors' => ['login' => [$message]],
            ], $status);
        }

        return back()
            ->withErrors(['login' => $message])
            ->withInput($request->except('password'))
            ->with('popup', true);
    }
} 