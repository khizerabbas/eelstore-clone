<?php

namespace App\Support;

/**
 * Builds the Content-Security-Policy header (see config/security.php).
 * Hosts for Cloudflare Turnstile, Google Maps embeds and analytics (when configured) are allowed automatically;
 * the Vite dev server is allowed while it is running locally (public/hot).
 */
class ContentSecurityPolicy
{
    public static function header(): string
    {
        $script  = ["'self'", "'unsafe-inline'", "'unsafe-eval'", 'https://challenges.cloudflare.com'];
        $style   = ["'self'", "'unsafe-inline'"];
        $connect = ["'self'"];
        $img     = ["'self'", 'data:', 'blob:', 'https:'];
        $frame   = ["'self'", 'https://www.google.com', 'https://challenges.cloudflare.com'];

        // Analytics (Website → SEO) – only when an ID is set
        if (filled(Content::get('seo.ga4_id'))) {
            $script[]  = 'https://www.googletagmanager.com';
            $connect[] = 'https://*.google-analytics.com';
            $connect[] = 'https://*.analytics.google.com';
            $connect[] = 'https://www.googletagmanager.com';
        }

        if (filled(Content::get('seo.meta_pixel_id'))) {
            $script[]  = 'https://connect.facebook.net';
            $connect[] = 'https://www.facebook.com';
            $connect[] = 'https://connect.facebook.net';
        }

        $script = array_merge($script, config('security.csp_extra_script_hosts', []));

        // Local development: Vite dev server (never present on the live server)
        if (! app()->isProduction() && is_file(public_path('hot'))) {
            $vite = rtrim(trim((string) file_get_contents(public_path('hot'))), '/');

            // Browsers reject IPv6 hosts like http://[::1]:5173 in CSP – fall back to the schemes (local only)
            $sources = str_contains($vite, '[') ? ['http:', 'ws:'] : [$vite, preg_replace('#^http#', 'ws', $vite)];
            array_push($script, $sources[0]);
            array_push($style, $sources[0]);
            array_push($connect, ...$sources);
        }

        $directives = [
            'default-src'     => ["'self'"],
            'script-src'      => $script,
            'style-src'       => $style,
            'img-src'         => $img,
            'font-src'        => ["'self'", 'data:'],
            'connect-src'     => $connect,
            'frame-src'       => $frame,
            'worker-src'      => ["'self'", 'blob:'],
            'media-src'       => ["'self'", 'blob:'],
            'object-src'      => ["'none'"],
            'base-uri'        => ["'self'"],
            'form-action'     => ["'self'"],
            'frame-ancestors' => ["'self'"],
            'report-uri'      => [route('csp-report')],
        ];

        $policy = collect($directives)
            ->map(fn (array $sources, string $name) => $name . ' ' . implode(' ', array_unique($sources)))
            ->implode('; ');

        if (app()->isProduction() && str_starts_with((string) config('app.url'), 'https://')) {
            $policy .= '; upgrade-insecure-requests';
        }

        return $policy;
    }

    public static function headerName(): string
    {
        return config('security.csp_report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
    }
}
