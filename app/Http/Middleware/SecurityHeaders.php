<?php

namespace App\Http\Middleware;

use App\Support\ContentSecurityPolicy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline browser security headers for every web response.
 * Headers a controller already set (e.g. stricter Referrer-Policy on order pages) are kept.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options'        => 'SAMEORIGIN',                         // no clickjacking via other sites' iframes
            'Referrer-Policy'        => 'strict-origin-when-cross-origin',
            'Permissions-Policy'     => 'camera=(), microphone=(), geolocation=(), payment=(), usb=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
        ];

        // Tell browsers to always use HTTPS (only once the live site is on HTTPS)
        if ($request->isSecure() && app()->isProduction()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        // Content-Security-Policy on HTML pages (downloads/JSON keep whatever they set themselves)
        $contentType = (string) $response->headers->get('Content-Type', 'text/html');
        if (config('security.csp_enabled') && str_contains($contentType, 'text/html')) {
            $headers[ContentSecurityPolicy::headerName()] = ContentSecurityPolicy::header();
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        // Don't advertise the PHP version (PHP adds this header itself)
        $response->headers->remove('X-Powered-By');
        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
