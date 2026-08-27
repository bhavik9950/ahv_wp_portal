<?php

declare(strict_types=1);

namespace App\Services\Organizations;

use App\Enums\OrganizationRole;
use App\Enums\Permission as PermissionEnum;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates an organization together with its team-scoped RBAC roles and,
 * optionally, an owner assigned as Organization Admin.
 */
final class OrganizationProvisioner
{
    public function __construct(private readonly PermissionRegistrar $permissions) {}

    /**
     * @param  array{name: string, timezone?: string}  $attributes
     */
    public function create(array $attributes, ?User $owner = null): Organization
    {
        return DB::transaction(function () use ($attributes, $owner): Organization {
            $organization = Organization::create([
                'name' => $attributes['name'],
                'timezone' => $attributes['timezone'] ?? 'UTC',
            ]);

            $this->provisionRoles($organization);

            if ($owner !== null) {
                $this->addMember($organization, $owner, OrganizationRole::OrgAdmin);
            }

            return $organization;
        });
    }

    /**
     * Ensure the four organization roles exist for this team with their default
     * permission sets.
     */
    public function provisionRoles(Organization $organization): void
    {
        $this->permissions->forgetCachedPermissions();
        $previousTeam = $this->permissions->getPermissionsTeamId();
        $this->permissions->setPermissionsTeamId($organization->getKey());

        try {
            // Make sure every permission exists (idempotent).
            foreach (PermissionEnum::values() as $name) {
                Permission::findOrCreate($name, 'web');
            }

            foreach (PermissionEnum::matrix() as $roleName => $permissionNames) {
                $role = Role::findOrCreate($roleName, 'web');
                $role->syncPermissions($permissionNames);
            }
        } finally {
            $this->permissions->setPermissionsTeamId($previousTeam);
            $this->permissions->forgetCachedPermissions();
        }
    }

    public function addMember(Organization $organization, User $user, OrganizationRole $role): void
    {
        $organization->users()->syncWithoutDetaching([$user->getKey()]);

        $previousTeam = $this->permissions->getPermissionsTeamId();
        $this->permissions->setPermissionsTeamId($organization->getKey());

        try {
            $user->syncRoles([$role->value]);
        } finally {
            $this->permissions->setPermissionsTeamId($previousTeam);
        }
    }
}
