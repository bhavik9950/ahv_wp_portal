<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Tenant-scoped validation rules. Use `Scoped::exists('table')` instead of a bare
 * `exists:table,id` so a request cannot reference another organization's row
 * (finding M-2). Resolves the current tenant at validation time; with no tenant
 * bound it matches nothing (fail closed, like OrganizationScope).
 */
final class Scoped
{
    public static function exists(string $table, string $column = 'id'): Exists
    {
        return Rule::exists($table, $column)
            ->where('organization_id', app(TenantContext::class)->id());
    }
}
