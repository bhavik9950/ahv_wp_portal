<?php

declare(strict_types=1);

namespace App\Jobs\Concerns;

use App\Models\Organization;
use App\Support\TenantContext;
use Spatie\Permission\PermissionRegistrar;

/**
 * Queued jobs run without the request's tenant context. Any job that touches
 * tenant-scoped models must call bindTenant() with the owning organization id
 * (read from a model loaded via withoutGlobalScopes) before querying scoped
 * relations — otherwise the OrganizationScope fails closed and returns nothing.
 */
trait BindsTenant
{
    protected function bindTenant(int|string|null $organizationId): void
    {
        if ($organizationId === null) {
            return;
        }

        $org = Organization::query()->withoutGlobalScopes()->find($organizationId);
        if ($org === null) {
            return;
        }

        app(TenantContext::class)->set($org);
        app(PermissionRegistrar::class)->setPermissionsTeamId($org->getKey());
    }
}
