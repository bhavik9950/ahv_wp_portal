<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureActiveSubscription
{
    public function __construct(private readonly CurrentOrganization $current) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        // Platform super admins always bypass subscription checks
        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        $organization = $this->current->resolve();

        if ($organization && $organization->isSubscriptionExpired()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your subscription has ended. Kindly renew to continue performing this action.',
                    'subscription_expired' => true,
                ], 402);
            }

            return redirect()->route('subscription.index')->with('flash_notify', [
                'type' => 'error',
                'message' => 'Your subscription has ended. Kindly renew your subscription to perform this action.',
            ]);
        }

        return $next($request);
    }
}

