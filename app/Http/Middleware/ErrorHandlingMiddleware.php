<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Illuminate\Session\TokenMismatchException;
use Throwable;

class ErrorHandlingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        try {
            return $next($request);
        } catch (ValidationException $e) {
            Log::warning('Validation error', [
                'url' => $request->fullUrl(),
                'errors' => $e->errors(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $e->errors()
                ], 422);
            }

            return redirect()->back()
                ->withErrors($e->errors())
                ->withInput();
        } catch (AuthenticationException $e) {
            Log::warning('Authentication error', [
                'url' => $request->fullUrl(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ], 401);
            }

            return redirect()->route('login')
                ->with('error', 'Please log in to access this page.');
        } catch (ModelNotFoundException $e) {
            Log::warning('Model not found', [
                'url' => $request->fullUrl(),
                'model' => $e->getModel(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested resource was not found.'
                ], 404);
            }

            return redirect()->back()
                ->with('error', 'The requested resource was not found.');
        } catch (NotFoundHttpException $e) {
            Log::warning('Page not found', [
                'url' => $request->fullUrl(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested page was not found.'
                ], 404);
            }

            return response()->view('errors.404', [], 404);
        } catch (MethodNotAllowedHttpException $e) {
            Log::warning('Method not allowed', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'The requested method is not allowed.'
                ], 405);
            }

            return redirect()->back()
                ->with('error', 'The requested method is not allowed.');
        } catch (AccessDeniedHttpException $e) {
            Log::warning('Access denied', [
                'url' => $request->fullUrl(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied.'
                ], 403);
            }

            return redirect()->back()
                ->with('error', 'You do not have permission to access this resource.');
        } catch (TooManyRequestsHttpException $e) {
            Log::warning('Too many requests', [
                'url' => $request->fullUrl(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many requests. Please try again later.'
                ], 429);
            }

            return redirect()->back()
                ->with('error', 'Too many requests. Please try again later.');
        } catch (TokenMismatchException $e) {
            Log::warning('CSRF token mismatch', [
                'url' => $request->fullUrl(),
                'user_id' => auth()->id()
            ]);

            $message = 'Your session expired. Please try again.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 419);
            }

            $onLogin = $request->is('login') || $request->routeIs('login', 'login.authenticate');

            if ($onLogin || !auth()->check()) {
                return redirect()->route('login')
                    ->withInput($request->except('password', 'password_confirmation', '_token'))
                    ->withErrors(['login' => $message]);
            }

            return redirect()->back()
                ->withInput($request->except('password', 'password_confirmation', '_token'))
                ->with('error', $message);
        } catch (QueryException $e) {
            Log::error('Database error', [
                'url' => $request->fullUrl(),
                'message' => $e->getMessage(),
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'A database error occurred. Please try again.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'A database error occurred. Please try again.');
        } catch (Throwable $e) {
            Log::error('Unexpected error', [
                'url' => $request->fullUrl(),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'user_id' => auth()->id()
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An unexpected error occurred. Please try again.'
                ], 500);
            }

            return redirect()->back()
                ->with('error', 'An unexpected error occurred. Please try again.');
        }
    }
}
