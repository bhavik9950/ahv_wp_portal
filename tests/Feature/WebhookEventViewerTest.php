<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\WebhookEvent;

function makeInboundWebhookEvent(array $overrides = []): WebhookEvent
{
    return WebhookEvent::query()->create(array_merge([
        'source' => 'meta',
        'event_fingerprint' => hash('sha256', uniqid('', true)),
        'signature_valid' => true,
        'status' => 'processed',
        'received_at' => now(),
        'processed_at' => now(),
        'payload' => ['entry' => [['changes' => [[
            'field' => 'messages',
            'value' => [
                'metadata' => ['phone_number_id' => '111222333'],
                'messages' => [[
                    'id' => 'wamid.ABC', 'from' => '919999911111', 'type' => 'text',
                    'text' => ['body' => 'Kya aap website banate ho?'],
                ]],
            ],
        ]]]]],
        'headers' => ['content-type' => 'application/json'],
    ], $overrides));
}

it('shows an audit-capable admin only their own org\'s events', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    makeInboundWebhookEvent(['organization_id' => $org->getKey()]);

    // Another org's event must not appear.
    $otherOrg = makeOrganization();
    makeInboundWebhookEvent(['organization_id' => $otherOrg->getKey(), 'payload' => ['entry' => [['changes' => [[
        'field' => 'messages',
        'value' => ['messages' => [['id' => 'wamid.OTHER', 'from' => '910000000000', 'type' => 'text', 'text' => ['body' => 'secret other-org message']]]],
    ]]]]]]);

    $this->actingAs($admin)
        ->withSession(['current_organization_id' => $org->getKey()])
        ->get(route('admin.webhook-events.index'))
        ->assertOk()
        ->assertSee('id="webhook-events-table"', false)
        ->assertSee('Kya aap website banate ho?')
        ->assertDontSee('secret other-org message');
});

it('warns when events are received but not processed', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    makeInboundWebhookEvent(['organization_id' => $org->getKey(), 'status' => 'received', 'processed_at' => null]);

    $this->actingAs($admin)->get(route('admin.webhook-events.index'))
        ->assertOk()
        ->assertSee('queue:work', false);
});

it('shows a single event to its own org, 404s another org\'s event', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    $mine = makeInboundWebhookEvent(['organization_id' => $org->getKey()]);

    $otherOrg = makeOrganization();
    $theirs = makeInboundWebhookEvent(['organization_id' => $otherOrg->getKey()]);

    $this->actingAs($admin)->withSession(['current_organization_id' => $org->getKey()]);

    $this->get(route('admin.webhook-events.show', $mine))->assertOk()->assertSee('wamid.ABC');
    $this->get(route('admin.webhook-events.show', $theirs))->assertNotFound();
});

it('lets a super admin see every org\'s events', function () {
    $org = makeOrganization();
    $superUser = User::factory()->create(['is_super_admin' => true]);
    $org->users()->attach($superUser);

    $otherOrg = makeOrganization();
    makeInboundWebhookEvent(['organization_id' => $otherOrg->getKey()]);

    $this->actingAs($superUser)->get(route('admin.webhook-events.index'))
        ->assertOk()
        ->assertSee('Kya aap website banate ho?');
});

it('forbids a viewer without the audit permission', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    $this->actingAs($viewer)->get(route('admin.webhook-events.index'))->assertForbidden();
});
