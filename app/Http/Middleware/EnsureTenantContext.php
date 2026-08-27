<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\CurrentOrganization;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Binds the active organization to the tenant scope and the spatie permission
 * team for the request.
 *
 * Single-tenant mode: the one organization is resolved automatically — there is
 * no org switcher. Multi-tenant mode (future): the preferred org id comes from
 * the session and membership is enforced here.
 */
final class EnsureTenantContext
{
    public function __construct(
        private readonly TenantContext $tenant,
        private readonly CurrentOrganization $current,
        private readonly PermissionRegistrar $permissions,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if ($user === null) {
            return $next($request);
        }

        $preferredId = $this->current->isSingleTenant()
            ? null
            : $request->session()->get('current_organization_id');

        $organization = $this->current->resolve($preferredId);

        if ($organization === null) {
            abort(503, 'No organization has been configured for this portal.');
        }

        // In multi-tenant mode, non-super-admins must be members of the org.
        if (! $this->current->isSingleTenant()
            && ! $user->isSuperAdmin()
            && ! $user->belongsToOrganization($organization)) {
            abort(403, 'Your account is not linked to this organization.');
        }

        $request->session()->put('current_organization_id', $organization->getKey());

        $this->tenant->set($organization);
        $this->permissions->setPermissionsTeamId($organization->getKey());

        return $next($request);
    }
}
