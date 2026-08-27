<?php

declare(strict_types=1);

use App\Enums\CampaignStatus;
use App\Enums\MessageStatus;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Models\User;
use App\Services\Reporting\DashboardMetrics;
use Illuminate\Support\Carbon;

it('computes message + rate aggregates for a date range', function () {
    $org = makeOrganization();
    bindTenant($org);

    Message::factory()->for($org)->count(10)->create(['direction' => 'outbound', 'status' => MessageStatus::Read, 'created_at' => now()->subDays(2)]);
    Message::factory()->for($org)->count(4)->create(['direction' => 'outbound', 'status' => MessageStatus::Delivered, 'created_at' => now()->subDays(2)]);
    Message::factory()->for($org)->count(3)->create(['direction' => 'outbound', 'status' => MessageStatus::Failed, 'created_at' => now()->subDay()]);
    // outside the range
    Message::factory()->for($org)->count(5)->create(['direction' => 'outbound', 'status' => MessageStatus::Read, 'created_at' => now()->subDays(40)]);

    Contact::factory()->for($org)->count(6)->create();
    Contact::factory()->for($org)->optedOut()->count(2)->create();

    $m = app(DashboardMetrics::class)->forRange(now()->subDays(6), now(), 'last_7_days');

    expect($m['messages']['total'])->toBe(17)
        ->and($m['messages']['read'])->toBe(10)
        ->and($m['messages']['failed'])->toBe(3)
        ->and($m['rates']['delivery'])->toBe(100.0) // 14 delivered+read of 14 sent-ish
        ->and($m['rates']['failure'])->toBe(round(3 / 17 * 100, 1))
        ->and($m['contacts']['total'])->toBe(8)
        ->and($m['contacts']['opted_in'])->toBe(6)
        ->and($m['contacts']['opted_out'])->toBe(2)
        ->and($m['trend'])->toHaveCount(7);
});

it('renders the dashboard with tiles, the trend chart and date presets', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');
    Message::factory()->for($org)->count(3)->create(['direction' => 'outbound', 'status' => MessageStatus::Read]);

    $this->actingAs($viewer)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Delivery rate')
        ->assertSee('Message trend')
        ->assertSee('data-trend-chart', false)
        ->assertSee('Last 30 days');
});

it('honours the date range on the dashboard', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    $this->actingAs($viewer)->get(route('dashboard', ['range' => 'today']))
        ->assertOk()
        ->assertSee(Carbon::now($org->timezone)->toDateString());
});

it('shows the reports overview to a report viewer and gates it otherwise', function () {
    $org = makeOrganization();
    $manager = makeMember($org, 'campaign_manager');
    Campaign::factory()->for($org)->status(CampaignStatus::Completed)->create([
        'totals' => ['total' => 100, 'sent' => 10, 'delivered' => 40, 'read' => 45, 'failed' => 5],
        'started_at' => now()->subHour(),
    ]);

    $this->actingAs($manager)->get(route('whatsapp.reports.index'))
        ->assertOk()
        ->assertSee('Campaign performance')
        ->assertSee('Volume trend');

    // An org member with no role assigned has no report.view permission.
    $noRole = User::factory()->create();
    $org->users()->attach($noRole);
    $this->actingAs($noRole)->get(route('whatsapp.reports.index'))->assertForbidden();
});
