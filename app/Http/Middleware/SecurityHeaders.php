<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        if (app()->environment('local')) {
            // Relaxed CSP for local development to avoid Vite/IPv6 blocking issues
            $csp = "default-src * 'unsafe-inline' 'unsafe-eval' data: blob:;";
        } else {
            // Strict CSP for production
            $csp = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline' fonts.googleapis.com cdn.jsdelivr.net; font-src 'self' fonts.gstatic.com cdn.jsdelivr.net data:; img-src 'self' data: blob: https:; connect-src 'self' wss: https:;";
        }
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }
}
