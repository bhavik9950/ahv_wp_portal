<?php

declare(strict_types=1);

use App\Enums\OrganizationRole;
use App\Models\Organization;
use App\Models\User;
use App\Services\Organizations\OrganizationProvisioner;
use App\Support\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

/**
 * Create an organization (with RBAC roles provisioned) and bind it as the
 * active tenant for the current test. Returns the Organization.
 */
function makeOrganization(array $attributes = []): Organization
{
    $org = app(OrganizationProvisioner::class)->create([
        'name' => $attributes['name'] ?? fake()->unique()->company(),
        'timezone' => $attributes['timezone'] ?? 'UTC',
    ]);

    bindTenant($org);

    return $org;
}

/** Bind an organization as the active tenant + permission team for this test. */
function bindTenant(Organization $org): void
{
    app(TenantContext::class)->set($org);
    app(PermissionRegistrar::class)->setPermissionsTeamId($org->getKey());
}

/** Create a user that is a member of $org with the given role. */
function makeMember(Organization $org, string $role = 'org_admin'): User
{
    $user = User::factory()->create();
    app(OrganizationProvisioner::class)->addMember($org, $user, OrganizationRole::from($role));

    return $user;
}
