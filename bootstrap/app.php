<?php

use App\Http\Middleware\EnsureSuperAdmin;
use App\Http\Middleware\EnsureTenantContext;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            SecurityHeaders::class,
        ]);

        // Behind a reverse proxy / tunnel (Cloudflare Tunnel, ngrok, a prod LB)
        // trust its X-Forwarded-* headers so the app knows the real scheme, host
        // and client IP. TRUSTED_PROXIES: "*" for a dev tunnel, a comma list of
        // proxy IPs/CIDRs for production, or empty to trust none (default).
        $proxies = trim((string) env('TRUSTED_PROXIES', ''));
        $middleware->trustProxies(
            at: $proxies === '*' ? '*' : array_values(array_filter(array_map('trim', explode(',', $proxies)))),
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );

        // Applied explicitly to routes that operate on tenant-scoped data.
        $middleware->alias([
            'tenant' => EnsureTenantContext::class,
            'super-admin' => EnsureSuperAdmin::class,
        ]);

        // The tenant must be bound BEFORE route-model binding runs, otherwise
        // implicit bindings on tenant-scoped models (e.g. /whatsapp/messages/{message})
        // hit the OrganizationScope with no tenant and fail closed → 404.
        $middleware->prependToPriorityList(
            SubstituteBindings::class,
            EnsureTenantContext::class,
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
