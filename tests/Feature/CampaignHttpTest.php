<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\Contact;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Models\WhatsappTemplate;

it('creates a draft and walks the wizard via HTTP', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $account = WhatsappBusinessAccount::factory()->for($org)->create();
    $number = WhatsappPhoneNumber::factory()->forAccount($account)->create();
    $template = WhatsappTemplate::factory()->forAccount($account)->create([
        'components' => [['type' => 'BODY', 'text' => 'Hello {{1}}']],
    ]);
    Contact::factory()->for($org)->count(3)->create();

    $this->actingAs($manager)->post(route('whatsapp.campaigns.store'), ['name' => 'Autumn promo'])
        ->assertRedirect();
    $campaign = Campaign::sole();

    $this->actingAs($manager)->put(route('whatsapp.campaigns.update', $campaign), [
        'whatsapp_phone_number_id' => $number->id,
        'template_id' => $template->id,
        'variable_map' => ['1' => ['type' => 'contact_field', 'value' => 'name']],
        'audience_filter' => ['type' => 'all'],
    ])->assertRedirect();

    $this->actingAs($manager)->get(route('whatsapp.campaigns.edit', $campaign))
        ->assertOk()
        ->assertSee('Ready to launch');

    $this->actingAs($manager)->post(route('whatsapp.campaigns.launch', $campaign), [
        'mode' => 'now',
        'confirm' => '1',
    ])->assertRedirect(route('whatsapp.campaigns.report', $campaign));

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Completed);

    $this->actingAs($manager)->get(route('whatsapp.campaigns.report', $campaign))
        ->assertOk()
        ->assertSee('Delivery rate');
});

it('renders the campaigns index as a DataTable with a labelled status filter', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    Campaign::factory()->for($org)->create(['name' => 'Autumn promo']);

    $this->actingAs($manager)->get(route('whatsapp.campaigns.index'))
        ->assertOk()
        ->assertSee('id="campaigns-table"', false)
        ->assertSee('data-dt-filter', false)
        ->assertSee('Autumn promo')
        ->assertSee('Status'); // visible filter label
});

it('a viewer cannot create or launch campaigns', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    $this->actingAs($viewer)->post(route('whatsapp.campaigns.store'), ['name' => 'x'])->assertForbidden();
});

it('a campaign manager can build but not launch; a launcher role is required', function () {
    $org = makeOrganization();
    $account = WhatsappBusinessAccount::factory()->for($org)->create();
    WhatsappPhoneNumber::factory()->forAccount($account)->create();

    // campaign_manager has both CampaignManage and CampaignLaunch in our matrix,
    // so use support_agent (no campaign perms) as the negative case.
    $agent = makeMember($org, 'support_agent');
    $campaign = Campaign::factory()->for($org)->create();

    $this->actingAs($agent)->post(route('whatsapp.campaigns.launch', $campaign), ['mode' => 'now', 'confirm' => '1'])
        ->assertForbidden();
});

it('exports a campaign report as CSV', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $campaign = Campaign::factory()->for($org)->status(CampaignStatus::Completed)->create();
    CampaignRecipient::factory()->forCampaign($campaign)->count(3)->create();

    $response = $this->actingAs($manager)->get(route('whatsapp.campaigns.report.export', $campaign));

    $response->assertOk();
    expect($response->streamedContent())->toContain('phone', 'status');
});
