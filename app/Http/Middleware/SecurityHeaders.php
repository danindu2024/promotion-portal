<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Add security headers to every response.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
        $csp = app()->isProduction()
            ? // Production: strict CSP, no localhost
              "default-src 'self'; " .
              "script-src 'self'; " .
              "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; " .
              "font-src 'self' https://fonts.gstatic.com; " .
              "img-src 'self' data:; " .
              "connect-src 'self'; " .
              "frame-ancestors 'none';"
            : // Development: allow Vite dev server (forced to 127.0.0.1 in vite.config.js)
              "default-src 'self'; " .
              "script-src 'self' 'unsafe-inline' http://localhost:5173 http://127.0.0.1:5173; " .
              "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com http://localhost:5173 http://127.0.0.1:5173; " .
              "font-src 'self' https://fonts.gstatic.com; " .
              "img-src 'self' data:; " .
              "connect-src 'self' http://localhost:5173 http://127.0.0.1:5173 ws://localhost:5173 ws://127.0.0.1:5173 wss://localhost:5173 wss://127.0.0.1:5173; " .
              "frame-ancestors 'none';";

        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
