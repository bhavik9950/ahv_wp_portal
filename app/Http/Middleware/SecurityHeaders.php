<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), interest-cohort=()',
            'Cross-Origin-Opener-Policy' => 'same-origin',
            'X-Permitted-Cross-Domain-Policies' => 'none',
            'Content-Security-Policy' => $this->csp(),
        ];

        if ($request->secure()) {
            $headers['Strict-Transport-Security'] = 'max-age=31536000; includeSubDomains';
        }

        foreach ($headers as $key => $value) {
            if (! $response->headers->has($key)) {
                $response->headers->set($key, $value);
            }
        }

        return $response;
    }

    private function csp(): string
    {
        // Assets are bundled by Vite (same-origin) — no CDN hosts are allowed.
        // 'unsafe-eval' is required by the standard Alpine.js build; everything
        // else is locked to 'self'.
        $script = "script-src 'self' 'unsafe-eval'";
        $connect = "connect-src 'self'";
        $style = "style-src 'self' 'unsafe-inline'"; // Tailwind/daisyUI/DataTables inject some inline styles

        if (app()->environment('local')) {
            // Vite dev server (HMR).
            $script .= ' http://localhost:5173';
            $connect .= ' http://localhost:5173 ws://localhost:5173';
            $style .= ' http://localhost:5173';
        }

        return implode('; ', [
            "default-src 'self'",
            "base-uri 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "object-src 'none'",
            "img-src 'self' data: blob:",
            "font-src 'self' data:",
            $style,
            $script,
            $connect,
        ]);
    }
}
