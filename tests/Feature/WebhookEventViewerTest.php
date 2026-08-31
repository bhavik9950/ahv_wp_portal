<?php

declare(strict_types=1);

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

it('shows the webhook events list to an audit-capable admin', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    makeInboundWebhookEvent();

    $this->actingAs($admin)->get(route('admin.webhook-events.index'))
        ->assertOk()
        ->assertSee('id="webhook-events-table"', false)
        ->assertSee('inbound: text')
        ->assertSee('Kya aap website banate ho?');
});

it('warns when events are received but not processed', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    makeInboundWebhookEvent(['status' => 'received', 'processed_at' => null]);

    $this->actingAs($admin)->get(route('admin.webhook-events.index'))
        ->assertOk()
        ->assertSee('queue:work', false);
});

it('shows a single event with its raw payload', function () {
    $org = makeOrganization();
    $admin = makeMember($org, 'org_admin');
    $event = makeInboundWebhookEvent();

    $this->actingAs($admin)->get(route('admin.webhook-events.show', $event))
        ->assertOk()
        ->assertSee('Payload')
        ->assertSee('wamid.ABC');
});

it('forbids a viewer without the audit permission', function () {
    $org = makeOrganization();
    $viewer = makeMember($org, 'viewer');

    $this->actingAs($viewer)->get(route('admin.webhook-events.index'))->assertForbidden();
});
