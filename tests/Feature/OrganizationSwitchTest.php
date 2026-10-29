<?php

declare(strict_types=1);

use App\Models\Organization;
use App\Models\User;

it('allows a super admin to switch between any organizations', function () {
    $org1 = makeOrganization(['name' => 'Org One']);
    $org2 = makeOrganization(['name' => 'Org Two']);

    $superAdmin = User::factory()->create(['is_super_admin' => true]);

    $response = $this->actingAs($superAdmin)
        ->from('/dashboard')
        ->post(route('organization.switch'), ['organization_id' => $org2->getKey()]);

    $response->assertRedirect('/dashboard');
    expect(session('current_organization_id'))->toBe($org2->getKey());
});

it('allows a regular user to switch between organizations they belong to', function () {
    $org1 = makeOrganization(['name' => 'Org One']);
    $org2 = makeOrganization(['name' => 'Org Two']);

    $user = makeMember($org1, 'org_admin');
    app(\App\Services\Organizations\OrganizationProvisioner::class)->addMember($org2, $user, \App\Enums\OrganizationRole::OrgAdmin);

    $response = $this->actingAs($user)
        ->from('/dashboard')
        ->post(route('organization.switch'), ['organization_id' => $org2->getKey()]);

    $response->assertRedirect('/dashboard');
    expect(session('current_organization_id'))->toBe($org2->getKey());
});

it('forbids a regular user from switching to an organization they do not belong to', function () {
    $org1 = makeOrganization(['name' => 'Org One']);
    $org2 = makeOrganization(['name' => 'Org Two']);

    $user = makeMember($org1, 'org_admin'); // only member of Org 1

    $this->actingAs($user)
        ->post(route('organization.switch'), ['organization_id' => $org2->getKey()])
        ->assertForbidden();

    expect(session('current_organization_id'))->not->toBe($org2->getKey());
});
