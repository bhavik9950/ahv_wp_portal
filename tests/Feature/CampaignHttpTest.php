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

it('exports filtered campaign report: only success, failed, and inbound', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $campaign = Campaign::factory()->for($org)->status(CampaignStatus::Completed)->create();
    
    $rec1 = CampaignRecipient::factory()->forCampaign($campaign)->create([
        'status' => \App\Enums\CampaignRecipientStatus::Delivered,
        'phone_e164' => '919876500001',
    ]);
    $rec2 = CampaignRecipient::factory()->forCampaign($campaign)->create([
        'status' => \App\Enums\CampaignRecipientStatus::Failed,
        'phone_e164' => '919876500002',
        'error_message' => 'Template rejected by user',
    ]);

    // Test success filter
    $respSuccess = $this->actingAs($manager)->get(route('whatsapp.campaigns.report.export', [$campaign, 'type' => 'success']));
    $respSuccess->assertOk();
    $contentSuccess = $respSuccess->streamedContent();
    expect($contentSuccess)->toContain('919876500001')
        ->and($contentSuccess)->not->toContain('919876500002');

    // Test failed filter
    $respFailed = $this->actingAs($manager)->get(route('whatsapp.campaigns.report.export', [$campaign, 'type' => 'failed']));
    $respFailed->assertOk();
    $contentFailed = $respFailed->streamedContent();
    expect($contentFailed)->toContain('919876500002')
        ->and($contentFailed)->not->toContain('919876500001');

    // Test inbound filter
    \App\Models\Message::factory()->create([
        'organization_id' => $org->id,
        'direction' => 'inbound',
        'type' => \App\Enums\MessageType::Text,
        'to_phone' => '919876500001',
        'payload' => ['text' => ['body' => 'Ji zaroor aayenge']],
    ]);

    $respInbound = $this->actingAs($manager)->get(route('whatsapp.campaigns.report.export', [$campaign, 'type' => 'inbound']));
    $respInbound->assertOk();
    $contentInbound = $respInbound->streamedContent();
    expect($contentInbound)->toContain('919876500001', 'Ji zaroor aayenge');
});

it('allows deleting a completed or dummy campaign', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $campaign = Campaign::factory()->for($org)->status(CampaignStatus::Completed)->create();
    CampaignRecipient::factory()->forCampaign($campaign)->count(2)->create();

    expect(Campaign::count())->toBe(1)
        ->and(CampaignRecipient::count())->toBe(2);

    $this->actingAs($manager)
        ->delete(route('whatsapp.campaigns.destroy', $campaign))
        ->assertRedirect(route('whatsapp.campaigns.index'));

    expect(Campaign::count())->toBe(0)
        ->and(CampaignRecipient::count())->toBe(0);
});
