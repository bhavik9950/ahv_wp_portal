<?php

declare(strict_types=1);
use App\Models\User;

it('renders the dashboard for an organization member with security headers', function () {
    $org = makeOrganization();
    $user = makeMember($org, 'viewer');

    $response = $this->actingAs($user)->get('/dashboard');

    $response->assertOk()
        ->assertSee('Dashboard')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('Cache-Control', 'no-store, private'); // authenticated HTML is never cached

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain("default-src 'self'")
        ->toContain("frame-ancestors 'none'");
});

it('redirects guests to login', function () {
    $this->get('/dashboard')->assertRedirect(route('login'));
});

it('returns 503 when no organization is configured (single-tenant misconfig)', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertStatus(503);
});

it('hides admin-only nav items from a viewer', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    $this->actingAs($viewer)->get('/dashboard')
        ->assertDontSee('Audit Logs')
        ->assertSee('Dashboard');
});

it('shows system health and emergency controls only to super admins', function () {
    $org = makeOrganization();
    $orgAdmin = makeMember($org, 'org_admin');
    $superAdmin = User::factory()->superAdmin()->create();
    app(\App\Services\Organizations\OrganizationProvisioner::class)->addMember($org, $superAdmin, \App\Enums\OrganizationRole::OrgAdmin);

    // Regular client OrgAdmin sees business modules, but NOT platform emergency controls or system health
    $this->actingAs($orgAdmin)->get('/dashboard')
        ->assertOk()
        ->assertSee('Campaigns')
        ->assertDontSee('Emergency Controls')
        ->assertDontSee('System Health');

    // SuperAdmin sees Emergency Controls and System Health
    $this->actingAs($superAdmin)->get('/dashboard')
        ->assertOk()
        ->assertSee('Emergency Controls')
        ->assertSee('System Health');
});
