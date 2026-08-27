<?php

declare(strict_types=1);

use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use Illuminate\Support\Facades\DB;

it('lets an org admin save WABA settings and never echoes the token', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');

    $response = $this->actingAs($admin)->put(route('whatsapp.settings.update'), [
        'name' => 'Acme WABA',
        'waba_id' => '123456789012345',
        'access_token' => 'EAAtesttokenvalue1234567890',
        'app_secret' => 'shhh-app-secret-value',
        'webhook_verify_token' => 'verify-token-1',
    ]);

    $response->assertRedirect(route('whatsapp.settings.edit'));

    $account = WhatsappBusinessAccount::sole();
    expect($account->waba_id)->toBe('123456789012345')
        ->and($account->access_token)->toBe('EAAtesttokenvalue1234567890');

    // Encrypted at rest.
    $raw = DB::table('whatsapp_business_accounts')->where('id', $account->getKey())->value('access_token');
    expect($raw)->not->toContain('EAAtesttokenvalue');

    // Not present in the rendered settings page.
    $this->actingAs($admin)->get(route('whatsapp.settings.edit'))
        ->assertOk()
        ->assertDontSee('EAAtesttokenvalue1234567890')
        ->assertSee('••••');
});

it('keeps existing secrets when the form submits blank secret fields', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    $account = WhatsappBusinessAccount::factory()->for($org)->create(['access_token' => 'EAAoriginaltoken000000']);

    $this->actingAs($admin)->put(route('whatsapp.settings.update'), [
        'name' => 'Renamed',
        'waba_id' => $account->waba_id,
        'access_token' => '',
        'app_secret' => '',
        'webhook_verify_token' => '',
    ])->assertRedirect();

    expect($account->fresh()->access_token)->toBe('EAAoriginaltoken000000')
        ->and($account->fresh()->name)->toBe('Renamed');
});

it('forbids a viewer from updating WABA settings', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    $this->actingAs($viewer)->put(route('whatsapp.settings.update'), [
        'name' => 'x', 'waba_id' => '999',
    ])->assertForbidden();
});

it('runs connection checks and records the status snapshot', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    $account = WhatsappBusinessAccount::factory()->for($org)->create([
        'access_token' => 'EAAvalidtoken000000000',
        'webhook_verify_token' => 'verify',
    ]);
    WhatsappPhoneNumber::factory()->forAccount($account)->create(['is_default' => true]);

    $this->actingAs($admin)->post(route('whatsapp.settings.check'))->assertRedirect();

    $account->refresh();
    expect($account->connection_status)->toBe('connected')
        ->and($account->token_status)->toBe('valid')
        ->and($account->token_last_checked_at)->not->toBeNull();
});

it('syncs phone numbers from the driver', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    WhatsappBusinessAccount::factory()->for($org)->create(['access_token' => 'EAAx0000000000000000']);

    $this->actingAs($admin)->post(route('whatsapp.phone-numbers.sync'))->assertRedirect();

    $this->actingAs($admin)->get(route('whatsapp.phone-numbers.index'))
        ->assertOk()
        ->assertSee('AH&amp;V Mock Business', false);
});
