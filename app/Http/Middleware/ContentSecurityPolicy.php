<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\View;

class ContentSecurityPolicy
{
    public function handle($request, Closure $next)
    {
        // Use a per-response nonce to avoid nonce reuse across requests.
        $nonce = base64_encode(random_bytes(16));

        // 2. Share it with all Blade views
        View::share('cspNonce', $nonce);

        // 3. Continue the request
        $response = $next($request);

        // 4. Build CSP header
        // Uses nonces for inline <script> and <style> blocks
        // Allows inline attributes for UI framework compatibility
        
        // Trusted CDN hosts for scripts
        $scriptHosts = [
            "'self'",
            "'nonce-$nonce'",
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://cdn-script.com',
            'https://ajax.googleapis.com',
            'https://code.jquery.com',
        ];
        
        // Trusted hosts for styles
        $styleHosts = [
            "'self'",
            "'nonce-$nonce'",
            'https://fonts.googleapis.com',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
        ];
        
        // Trusted hosts for images
        $imgHosts = [
            "'self'",
            'data:',
            'blob:',
            'https://cdn.jsdelivr.net',
            'https://nebulastudentportal.slt.lk',
        ];
        
        // Trusted hosts for fonts
        $fontHosts = [
            "'self'",
            'data:',
            'https://fonts.gstatic.com',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
        ];
        
        // Trusted hosts for connections (AJAX, fetch, WebSocket)
        $connectHosts = [
            "'self'",
            'https://nebulastudentportal.slt.lk',
            'https://fonts.googleapis.com',
            'https://fonts.gstatic.com',
            'https://cdn.tailwindcss.com',
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
        ];

        $scriptList = implode(' ', $scriptHosts);
        $styleList = implode(' ', $styleHosts);
        $imgList = implode(' ', $imgHosts);
        $fontList = implode(' ', $fontHosts);
        $connectList = implode(' ', $connectHosts);

        // Build CSP
        // Note: 'unsafe-inline' in script-src-attr and style-src-attr is required 
        // for Bootstrap and other UI frameworks that use inline event handlers and styles
        $csp = "default-src 'self'; "
            // Scripts: nonce-based for <script> blocks, allow inline event handlers
            . "script-src $scriptList; "
            . "script-src-elem $scriptList; "
            . "script-src-attr 'unsafe-inline'; "
            // Styles: nonce-based for <style> blocks, allow inline style attributes
            . "style-src $styleList; "
            . "style-src-elem $styleList; "
            . "style-src-attr 'unsafe-inline'; "
            // Resources
            . "img-src $imgList; "
            . "connect-src $connectList; "
            . "font-src $fontList; "
            . "frame-src 'self' blob: https://nebulastudentportal.slt.lk; "
            . "media-src 'self'; "
            . "manifest-src 'self'; "
            . "worker-src 'self' blob:; "
            . "form-action 'self'; "
            . "object-src 'none'; "
            . "base-uri 'self'; "
            . "frame-ancestors 'self' https://nebulastudentportal.slt.lk; "
            . "upgrade-insecure-requests; "
            . "block-all-mixed-content;";

        // 5. Add CSP header to response
        $response->headers->set('Content-Security-Policy', $csp);

        // 6. Additional security headers (fixes ZAP Low/Medium findings)

        // Prevent MIME-type sniffing (Low: X-Content-Type-Options missing)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Anti-clickjacking belt-and-suspenders alongside CSP frame-ancestors (Medium)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Control referrer information sent in requests (Low: Referrer-Policy missing)
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Disable browser features not used by this application (Low: Permissions-Policy missing)
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(), payment=(), usb=(), bluetooth=()'
        );

        // HSTS is emitted at web-server layer to avoid duplicate header entries.

        // 7. Strip PHP version disclosure (Low: X-Powered-By / Server information leakage)
        header_remove('X-Powered-By');
        $response->headers->remove('X-Powered-By');
        $response->headers->remove('Server');

        return $response;
    }
}
