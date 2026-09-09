<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\VoiceCallStatus;
use App\Jobs\PollVoiceReportsJob;
use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Organization;
use App\Models\VoiceCampaign;
use App\Services\Voice\VoiceBroadcastManager;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function voiceGroupWithContacts(Organization $org, int $count = 3): ContactGroup
{
    $group = ContactGroup::factory()->for($org)->create(['name' => 'Councillors']);
    Contact::factory()->for($org)->count($count)->create()->each(fn ($c) => $c->groups()->attach($group));

    return $group;
}

it('starts a voice campaign: uploads audio, materialises recipients, places calls', function () {
    Storage::fake('local');
    config()->set('services.voice.driver', 'mock');

    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $group = voiceGroupWithContacts($org, 3);

    $this->actingAs($manager)->post(route('whatsapp.voice-campaigns.store'), [
        'name' => 'Diwali wishes',
        'audio' => UploadedFile::fake()->create('wish.mp3', 200, 'audio/mpeg'),
        'audience_type' => 'groups',
        'group_ids' => [$group->id],
        'delay_seconds' => 0,
        'mode' => 'now',
        'consent_confirmed' => '1',
    ])->assertRedirect();

    $campaign = VoiceCampaign::sole();

    expect($campaign->provider_library_id)->not->toBeNull()
        ->and($campaign->recipients()->count())->toBe(3)
        ->and($campaign->recipients()->pluck('status')->map->value->unique()->all())->toBe([VoiceCallStatus::Placed->value])
        ->and($campaign->recipients()->pluck('provider_uuid')->filter()->count())->toBe(3)
        ->and($campaign->fresh()->status)->toBe(CampaignStatus::Completed);
});

it('fills in the delivery outcome when the report is polled', function () {
    Storage::fake('local');
    config()->set('services.voice.driver', 'mock');

    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $group = voiceGroupWithContacts($org, 2);

    $this->actingAs($manager)->post(route('whatsapp.voice-campaigns.store'), [
        'name' => 'Poll test',
        'audio' => UploadedFile::fake()->create('wish.mp3', 100, 'audio/mpeg'),
        'audience_type' => 'groups',
        'group_ids' => [$group->id],
        'delay_seconds' => 0,
        'mode' => 'now',
        'consent_confirmed' => '1',
    ])->assertRedirect();

    (new PollVoiceReportsJob)->handle(app(VoiceBroadcastManager::class));

    $campaign = VoiceCampaign::sole();
    expect($campaign->recipients()->pluck('status')->map->value->unique()->all())->toBe([VoiceCallStatus::Answered->value])
        ->and($campaign->fresh()->totals['answered'])->toBe(2);
});

it('places a quick one-off call to a single number', function () {
    Storage::fake('local');
    config()->set('services.voice.driver', 'mock');

    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');

    $this->actingAs($manager)->post(route('whatsapp.voice-campaigns.quick.store'), [
        'audio' => UploadedFile::fake()->create('clip.mp3', 80, 'audio/mpeg'),
        'phone' => '+91 98765 43210',
        'consent_confirmed' => '1',
    ])->assertRedirect();

    $campaign = VoiceCampaign::sole();
    expect($campaign->recipients()->count())->toBe(1)
        ->and($campaign->recipients()->first()->phone_e164)->toBe('919876543210')
        ->and($campaign->recipients()->first()->status->value)->toBe(VoiceCallStatus::Placed->value);
});

it('requires the consent confirmation to start', function () {
    Storage::fake('local');
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $group = voiceGroupWithContacts($org, 1);

    $this->actingAs($manager)->post(route('whatsapp.voice-campaigns.store'), [
        'name' => 'No consent',
        'audio' => UploadedFile::fake()->create('wish.mp3', 50, 'audio/mpeg'),
        'audience_type' => 'groups',
        'group_ids' => [$group->id],
        'delay_seconds' => 5,
        'mode' => 'now',
    ])->assertSessionHasErrors('consent_confirmed');

    expect(VoiceCampaign::count())->toBe(0);
});

it('forbids a support agent from creating a voice campaign', function () {
    $org = makeOrganization();
    $agent = makeMember($org, 'support_agent');

    $this->actingAs($agent)->get(route('whatsapp.voice-campaigns.create'))->assertForbidden();
});

it('does not show another org\'s voice campaign', function () {
    config()->set('tenant.mode', 'multi');

    $orgA = makeOrganization();
    $campaign = VoiceCampaign::factory()->for($orgA)->create();

    $orgB = makeOrganization();
    $viewer = makeMember($orgB, 'viewer');

    $this->actingAs($viewer)
        ->withSession(['current_organization_id' => $orgB->getKey()])
        ->get(route('whatsapp.voice-campaigns.show', $campaign))
        ->assertNotFound();
});

it('can be cancelled, skipping pending calls', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    $campaign = VoiceCampaign::factory()->for($org)->status(CampaignStatus::Processing)->create();
    foreach ([['919999900001', VoiceCallStatus::Pending], ['919999900002', VoiceCallStatus::Answered]] as [$phone, $status]) {
        $campaign->recipients()->create([
            'phone_e164' => $phone,
            'phone_hash' => hash('sha256', $phone),
            'status' => $status->value,
        ]);
    }

    $this->actingAs($manager)->post(route('whatsapp.voice-campaigns.cancel', $campaign))->assertRedirect();

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Cancelled)
        ->and($campaign->recipients()->where('status', VoiceCallStatus::Skipped->value)->count())->toBe(1)
        ->and($campaign->recipients()->where('status', VoiceCallStatus::Answered->value)->count())->toBe(1);
});
