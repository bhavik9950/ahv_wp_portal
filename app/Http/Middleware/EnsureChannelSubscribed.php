<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\CurrentOrganization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureChannelSubscribed
{
    public function __construct(private readonly CurrentOrganization $current) {}

    public function handle(Request $request, Closure $next, string $channel): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        // Platform super admins always have access to test and configure all modules
        if ($user?->isSuperAdmin()) {
            return $next($request);
        }

        $organization = $this->current->resolve();

        if ($organization && ! $organization->hasChannel($channel)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => "Your organization is not subscribed to the {$channel} service.",
                    'channel' => $channel,
                    'channel_unsubscribed' => true,
                ], 403);
            }

            return response()->view('errors.channel-unsubscribed', [
                'channel' => $channel,
                'organization' => $organization,
            ], 403);
        }

        return $next($request);
    }
}
