<?php

declare(strict_types=1);

use App\Enums\Permission;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

it('provisions the four organization roles with their permission matrix', function () {
    $org = makeOrganization();

    $matrix = Permission::matrix();

    foreach ($matrix as $roleName => $expected) {
        $role = Role::where('name', $roleName)
            ->where('team_id', $org->getKey())
            ->firstOrFail();

        expect($role->permissions->pluck('name')->sort()->values()->all())
            ->toBe(collect($expected)->sort()->values()->all());
    }
});

it('grants an org admin every permission but a viewer only read permissions', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    $viewer = makeMember($org, 'viewer');

    app(PermissionRegistrar::class)->setPermissionsTeamId($org->getKey());

    expect($admin->can(Permission::WabaManage->value))->toBeTrue()
        ->and($admin->can(Permission::CampaignLaunch->value))->toBeTrue()
        ->and($viewer->can(Permission::WabaManage->value))->toBeFalse()
        ->and($viewer->can(Permission::CampaignLaunch->value))->toBeFalse()
        ->and($viewer->can(Permission::ReportView->value))->toBeTrue();
});

it('lets a super admin pass every gate check regardless of roles', function () {
    makeOrganization();
    $super = User::factory()->superAdmin()->create();

    expect($super->can(Permission::WabaManage->value))->toBeTrue()
        ->and($super->can('anything.at.all'))->toBeTrue();
});

it('does not allow a campaign manager to manage WABA credentials', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');

    app(PermissionRegistrar::class)->setPermissionsTeamId($org->getKey());

    expect($manager->can(Permission::WabaManage->value))->toBeFalse()
        ->and($manager->can(Permission::CampaignManage->value))->toBeTrue();
});
