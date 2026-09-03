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
