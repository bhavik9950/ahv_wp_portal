<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Resolves the active organization for the request from the session and makes
 * it available to the tenant global scope. Redirects to org selection if the
 * user has no usable organization context.
 */
final class EnsureTenantContext
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly PermissionRegistrar $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        $organizationId = $request->session()->get('current_organization_id');

        $organization = $organizationId
            ? $user->organizations()->whereKey($organizationId)->first()
            : null;

        $organization ??= $user->organizations()->orderBy('name')->first();

        if ($organization === null) {
            // Super admins can operate without an org membership (platform admin area).
            if ($user->isSuperAdmin()) {
                return $next($request);
            }

            abort(403, 'Your account is not linked to any organization.');
        }

        $request->session()->put('current_organization_id', $organization->getKey());

        $this->tenant->set($organization);

        // Scope spatie team-based roles/permissions to this organization.
        $this->permissions->setPermissionsTeamId($organization->getKey());

        return $next($request);
    }
}
