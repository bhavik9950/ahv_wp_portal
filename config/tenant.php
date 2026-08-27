<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Tenancy mode
    |--------------------------------------------------------------------------
    |
    | The data model is fully multi-tenant (every business resource carries an
    | organization_id and is scoped). For now the portal runs in "single"
    | mode: exactly one organization, resolved automatically, no org switcher.
    |
    | Switching to "multi" later only requires re-enabling the organization
    | picker / membership resolution in EnsureTenantContext — no schema or
    | scope changes.
    |
    */

    'mode' => env('TENANT_MODE', 'single'),

    /*
    | In single mode: the organization to use. When null, the sole (oldest)
    | organization row is used.
    */
    'organization_id' => env('TENANT_ORGANIZATION_ID'),

];
