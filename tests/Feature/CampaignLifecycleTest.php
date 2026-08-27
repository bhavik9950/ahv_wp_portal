<?php

declare(strict_types=1);

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Jobs\SendCampaignMessageJob;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Models\WhatsappTemplate;
use App\Services\Campaigns\CampaignLauncher;

/**
 * @return array{0: Campaign, 1: WhatsappPhoneNumber, 2: WhatsappTemplate}
 */
function campaignSetup(string $category = 'UTILITY', string $status = 'APPROVED'): array
{
    $org = makeOrganization();
    bindTenant($org);
    $account = WhatsappBusinessAccount::factory()->for($org)->create();
    $number = WhatsappPhoneNumber::factory()->forAccount($account)->create();
    $template = WhatsappTemplate::factory()->forAccount($account)->create([
        'category' => $category,
        'status' => $status,
        'components' => [['type' => 'BODY', 'text' => 'Hi {{1}}, order {{2}} update.']],
    ]);

    $campaign = Campaign::factory()->for($org)->create([
        'whatsapp_phone_number_id' => $number->getKey(),
        'template_id' => $template->getKey(),
        'variable_map' => [
            '1' => ['type' => 'contact_field', 'value' => 'name'],
            '2' => ['type' => 'static', 'value' => 'ORD-100'],
        ],
        'audience_filter' => ['type' => 'all'],
    ]);

    return [$campaign, $number, $template];
}

it('validates a campaign before launch', function () {
    [$campaign] = campaignSetup(status: 'PENDING');
    Contact::factory()->for($campaign->organization)->count(2)->create();

    $errors = app(CampaignLauncher::class)->validate($campaign);

    expect($errors)->toContain('The selected template is not APPROVED by Meta.');
});

it('freezes only the eligible (opted-in) audience for a marketing campaign', function () {
    [$campaign] = campaignSetup('MARKETING');
    Contact::factory()->for($campaign->organization)->count(3)->create();
    Contact::factory()->for($campaign->organization)->optedOut()->count(2)->create();

    $count = app(CampaignLauncher::class)->materialise($campaign);

    expect($count)->toBe(3)
        ->and($campaign->recipients()->count())->toBe(3)
        ->and($campaign->fresh()->audience_summary['excluded_opted_out'])->toBe(2);

    // Rendered variables are frozen with the contact's name + static value.
    $first = $campaign->recipients()->first();
    expect($first->rendered_variables)->toHaveCount(2)
        ->and($first->rendered_variables[1])->toBe('ORD-100');
});

it('runs a campaign end to end: launch → send → webhooks → completed', function () {
    [$campaign] = campaignSetup();
    Contact::factory()->for($campaign->organization)->count(4)->create();

    app(CampaignLauncher::class)->schedule($campaign->fresh(), null); // launch now

    $campaign->refresh();
    expect($campaign->status)->toBe(CampaignStatus::Completed);

    $totals = $campaign->totals;
    expect($totals['total'])->toBe(4)
        // mock driver: accepted → sent, then inline delivered+read webhooks
        ->and($totals['read'])->toBe(4)
        ->and($campaign->recipients()->where('status', CampaignRecipientStatus::Read->value)->count())->toBe(4);
});

it('does not send to a number that triggers a permanent mock failure', function () {
    [$campaign] = campaignSetup();
    // 0000 suffix → mock invalid-recipient (permanent)
    Contact::factory()->for($campaign->organization)->create(['name' => 'Bad'])->forceFill(['phone_e164' => '919999990000'])->save();
    Contact::factory()->for($campaign->organization)->create(['name' => 'Good']);

    app(CampaignLauncher::class)->schedule($campaign->fresh(), null);

    $campaign->refresh();
    expect($campaign->recipients()->where('status', CampaignRecipientStatus::Failed->value)->count())->toBe(1)
        ->and($campaign->recipients()->where('status', CampaignRecipientStatus::Read->value)->count())->toBe(1)
        ->and($campaign->status)->toBe(CampaignStatus::Completed);
});

it('is idempotent — re-running the send job does not create a second message', function () {
    [$campaign] = campaignSetup();
    $contact = Contact::factory()->for($campaign->organization)->create();

    app(CampaignLauncher::class)->schedule($campaign->fresh(), null);

    $recipient = $campaign->recipients()->first();
    // Re-dispatch the same recipient's job.
    SendCampaignMessageJob::dispatchSync($recipient->getKey());

    expect(Message::where('campaign_id', $campaign->getKey())->count())->toBe(1);
});

it('pause stops new sends and resume continues only the unsent', function () {
    [$campaign] = campaignSetup();
    Contact::factory()->for($campaign->organization)->count(3)->create();

    $launcher = app(CampaignLauncher::class);
    $launcher->materialise($campaign);
    $campaign->forceFill(['status' => CampaignStatus::Processing, 'started_at' => now()])->save();

    // Mark one as already sent, pause before dispatching the rest.
    $campaign->recipients()->limit(1)->update(['status' => CampaignRecipientStatus::Sent->value]);
    $launcher->pause($campaign);

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Paused);

    // A queued send job for a still-pending recipient must not send while paused.
    $pending = $campaign->recipients()->where('status', 'pending')->first();
    SendCampaignMessageJob::dispatchSync($pending->getKey());
    expect($pending->fresh()->status)->toBe(CampaignRecipientStatus::Pending);

    $launcher->resume($campaign->fresh());
    $campaign->refresh();
    expect($campaign->status)->toBe(CampaignStatus::Completed)
        ->and($campaign->recipients()->where('status', CampaignRecipientStatus::Sent->value)->count())->toBe(1) // untouched
        ->and($campaign->recipients()->where('status', CampaignRecipientStatus::Read->value)->count())->toBe(2);
});

it('cancel skips the remaining recipients', function () {
    [$campaign] = campaignSetup();
    Contact::factory()->for($campaign->organization)->count(5)->create();

    $launcher = app(CampaignLauncher::class);
    $launcher->materialise($campaign);
    $campaign->forceFill(['status' => CampaignStatus::Processing])->save();

    $launcher->cancel($campaign);

    $campaign->refresh();
    expect($campaign->status)->toBe(CampaignStatus::Cancelled)
        ->and($campaign->recipients()->where('status', CampaignRecipientStatus::Skipped->value)->count())->toBe(5);
});

it('dispatch-due command starts a scheduled campaign whose time has come', function () {
    [$campaign] = campaignSetup();
    Contact::factory()->for($campaign->organization)->count(2)->create();

    app(CampaignLauncher::class)->schedule($campaign->fresh(), now()->addMinutes(10));
    expect($campaign->fresh()->status)->toBe(CampaignStatus::Scheduled);

    // Time travel past the schedule.
    $campaign->forceFill(['scheduled_at' => now()->subMinute()])->save();

    $this->artisan('campaigns:dispatch-due')->assertSuccessful();

    expect($campaign->fresh()->status)->toBe(CampaignStatus::Completed);
});
