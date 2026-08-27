<?php

declare(strict_types=1);

namespace App\Models\Scopes;

use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Constrains every query on a tenant-owned model to the active organization.
 *
 * If no tenant is set and the scope has not been explicitly bypassed, the query
 * is forced to return nothing (fail closed) rather than leaking all rows.
 */
final class OrganizationScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        if ($context->isBypassed()) {
            return;
        }

        $column = $model->qualifyColumn('organization_id');

        if ($context->hasOrganization()) {
            $builder->where($column, $context->id());

            return;
        }

        // Fail closed: no tenant context => no rows.
        $builder->whereRaw('1 = 0');
    }
}
