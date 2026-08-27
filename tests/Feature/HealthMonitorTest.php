<?php

declare(strict_types=1);
use App\Services\System\SystemSettings;

it('exposes a public /health probe with no secrets', function () {
    $response = $this->getJson('/health');

    $response->assertOk()
        ->assertJsonStructure(['status', 'components', 'time'])
        ->assertJsonPath('status', 'ok');

    expect($response->json('components'))->toHaveKeys(['database', 'cache', 'queue', 'whatsapp_api']);
});

it('renders the admin health page for an org admin', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');

    $this->actingAs($admin)->get(route('admin.health'))
        ->assertOk()
        ->assertSee('Database')
        ->assertSee('WhatsApp API');
});

it('reports the kill switch state on the health page', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');

    app(SystemSettings::class)->setSendingEnabled(false);

    $this->actingAs($admin)->get(route('admin.health'))
        ->assertOk()
        ->assertSee('kill switch');
});
