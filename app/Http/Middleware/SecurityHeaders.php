<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Applies a baseline set of HTTP security response headers.
 *
 * Source: OWASP Secure Headers Project
 * (owasp.org/www-project-secure-headers/). The app renders server-side and
 * loads only same-origin CSS/JS with no inline scripts or styles, so a strict
 * 'self' Content-Security-Policy holds without nonces.
 *
 * HSTS is intentionally omitted here: it only takes effect over HTTPS and
 * belongs on the TLS-terminating proxy in production (see CHECKPOINT.md).
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self'",
            "style-src 'self'",
            "img-src 'self' data:",
            "font-src 'self'",
            "form-action 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
        ]));

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set(
            'Permissions-Policy',
            'accelerometer=(), camera=(), geolocation=(), gyroscope=(), microphone=(), payment=(), usb=()',
        );

        // PHP advertises its version via X-Powered-By when expose_php is on.
        // That header is injected by the SAPI, not Symfony's response bag, so
        // it must be stripped at the PHP level as well. (OWASP A02 — do not
        // reveal the runtime/version.)
        $response->headers->remove('X-Powered-By');
        if (! headers_sent() && function_exists('header_remove')) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
