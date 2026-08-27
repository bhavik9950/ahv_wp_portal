<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\OrganizationRole;
use App\Models\Campaign;
use App\Models\User;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Services\Organizations\OrganizationProvisioner;
use App\Services\System\SystemSettings;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\Recipient;
use App\Services\WhatsApp\WhatsAppManager;
use App\Services\WhatsApp\WhatsAppSendingDisabledException;

it('kill switch: disabling sending blocks the WhatsAppManager', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    $account = WhatsappBusinessAccount::factory()->for($org)->create();
    $number = WhatsappPhoneNumber::factory()->forAccount($account)->create();

    // Sending works before.
    expect(app(WhatsAppManager::class)->send($number, OutboundMessage::text(new Recipient('919876500123'), 'hi'))->accepted)
        ->toBeTrue();

    $this->actingAs($admin)->post(route('admin.controls.sending'), ['enable' => 0])->assertRedirect();

    expect(app(SystemSettings::class)->sendingEnabledOverride())->toBeFalse();

    app(WhatsAppManager::class)->send($number, OutboundMessage::text(new Recipient('919876500123'), 'hi'));
})->throws(WhatsAppSendingDisabledException::class);

it('pause all campaigns moves processing campaigns to paused', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    Campaign::factory()->for($org)->count(3)->create(['status' => CampaignStatus::Processing]);
    Campaign::factory()->for($org)->create(['status' => CampaignStatus::Draft]);

    $this->actingAs($admin)->post(route('admin.controls.pause-campaigns'))->assertRedirect();

    expect(Campaign::where('status', CampaignStatus::Paused)->count())->toBe(3)
        ->and(Campaign::where('status', CampaignStatus::Draft)->count())->toBe(1);
});

it('revoking an integration requires super admin', function () {
    $org = makeOrganization();
    $orgAdmin = makeMember($org, 'org_admin');
    $account = WhatsappBusinessAccount::factory()->for($org)->create(['is_active' => true]);

    $this->actingAs($orgAdmin)->post(route('admin.controls.revoke', $account))->assertForbidden();
    expect($account->fresh()->is_active)->toBeTrue();

    $super = User::factory()->superAdmin()->create();
    app(OrganizationProvisioner::class)->addMember($org, $super, OrganizationRole::OrgAdmin);

    $this->actingAs($super)->post(route('admin.controls.revoke', $account))->assertRedirect();
    expect($account->fresh()->is_active)->toBeFalse();
});

it('viewer cannot reach admin controls or health', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    $this->actingAs($viewer)->get(route('admin.controls'))->assertForbidden();
    $this->actingAs($viewer)->get(route('admin.health'))->assertForbidden();
});
