<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;

class CaptureAuditContext
{
    public function handle(Request $request, Closure $next)
    {
        $logger = app(AuditLogger::class);
        if (!$logger->enabled()) {
            return $next($request);
        }

        $logger->resetRequestState();
        app()->instance('audit.context', [
            'ip' => $request->ip(),
            'url' => $request->fullUrl(),
            'path' => '/' . ltrim($request->path(), '/'),
            'method' => $request->method(),
            'route' => optional($request->route())->getName(),
            'user_agent' => substr((string) $request->userAgent(), 0, 500),
            'country' => $request->headers->get('CF-IPCountry') ?: $request->headers->get('X-AppEngine-Country'),
        ]);

        return $next($request);
    }

    public function terminate(Request $request, $response): void
    {
        try {
            $logger = app(AuditLogger::class);
            if ($logger->enabled()) {
                $logger->recordRequestFallback($request);
            }
        } catch (\Throwable $e) {
            // Audit must never break the response.
        }
    }
}
