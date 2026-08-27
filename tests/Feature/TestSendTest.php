<?php

declare(strict_types=1);

use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\Recipient;
use App\Services\WhatsApp\OutboundMessageService;

function testSendNumber(): WhatsappPhoneNumber
{
    $org = makeOrganization();
    $account = WhatsappBusinessAccount::factory()->for($org)->create();

    return WhatsappPhoneNumber::factory()->forAccount($account)->create();
}

it('sends a free-text test message and records it with a wamid', function () {
    $number = testSendNumber();
    $agent = makeMember($number->organization, 'support_agent');

    $this->actingAs($agent)->post(route('whatsapp.test-send.store'), [
        'whatsapp_phone_number_id' => $number->id,
        'mode' => 'text',
        'recipients' => '919876500001, 919876500002',
        'body' => 'Hello from a test',
    ])->assertRedirect();

    expect(Message::count())->toBe(2);
    $message = Message::first();
    // Mock driver emits delivered+read webhooks, so the message advances past 'sent'.
    expect($message->wamid)->toStartWith('wamid.MOCK-')
        ->and($message->isSuccessful())->toBeTrue()
        ->and($message->statusEvents()->pluck('status')->all())->toContain('sent');
});

it('caps test sends at five recipients', function () {
    $number = testSendNumber();
    $agent = makeMember($number->organization, 'support_agent');

    $this->actingAs($agent)
        ->from(route('whatsapp.test-send.create'))
        ->post(route('whatsapp.test-send.store'), [
            'whatsapp_phone_number_id' => $number->id,
            'mode' => 'text',
            'recipients' => '911, 912, 913, 914, 915, 916',
            'body' => 'x',
        ])
        ->assertSessionHasErrors('recipients');
});

it('will not send an unapproved template', function () {
    $number = testSendNumber();
    $agent = makeMember($number->organization, 'support_agent');
    $template = WhatsappTemplate::factory()
        ->forAccount($number->businessAccount)
        ->pending()
        ->create();

    $this->actingAs($agent)
        ->from(route('whatsapp.test-send.create'))
        ->post(route('whatsapp.test-send.store'), [
            'whatsapp_phone_number_id' => $number->id,
            'mode' => 'template',
            'template_id' => $template->id,
            'recipients' => '919876500001',
        ])
        ->assertSessionHasErrors('template_id');

    expect(Message::count())->toBe(0);
});

it('renders the template structure + variable examples on the send page', function () {
    $number = testSendNumber();
    $agent = makeMember($number->organization, 'support_agent');
    WhatsappTemplate::factory()->forAccount($number->businessAccount)->create([
        'name' => 'order_ready',
        'components' => [[
            'type' => 'BODY',
            'text' => 'Hi {{1}}, order {{2}} is ready.',
            'example' => ['body_text' => [['Asha', 'ORD-42']]],
        ]],
    ]);

    $this->actingAs($agent)->get(route('whatsapp.test-send.create'))
        ->assertOk()
        ->assertSee('Hi {{1}}, order {{2}} is ready.', false) // body text in the JSON blob
        ->assertSee('ORD-42', false)                          // example value
        ->assertSee('id="test-send-templates"', false);
});

it('rejects a template send with a blank variable', function () {
    $number = testSendNumber();
    $agent = makeMember($number->organization, 'support_agent');
    $template = WhatsappTemplate::factory()->forAccount($number->businessAccount)->create();

    $this->actingAs($agent)
        ->from(route('whatsapp.test-send.create'))
        ->post(route('whatsapp.test-send.store'), [
            'whatsapp_phone_number_id' => $number->id,
            'mode' => 'template',
            'template_id' => $template->id,
            'recipients' => '919876500001',
            'variables' => ['Asha', ''],
        ])
        ->assertSessionHasErrors('variables.1');

    expect(Message::count())->toBe(0);
});

it('is idempotent when the same message is retried by key', function () {
    $number = testSendNumber();
    bindTenant($number->organization);

    $service = app(OutboundMessageService::class);
    $outbound = OutboundMessage::text(
        new Recipient('919876500123'), 'hi',
    );

    $a = $service->send($number, $outbound, ['idempotency_key' => 'dup-key-1']);
    $b = $service->send($number, $outbound, ['idempotency_key' => 'dup-key-1']);

    expect($a->getKey())->toBe($b->getKey())
        ->and(Message::count())->toBe(1);
});

it('emits mock delivery + read webhooks that advance the message', function () {
    $number = testSendNumber();
    bindTenant($number->organization);

    $service = app(OutboundMessageService::class);
    $message = $service->send($number, OutboundMessage::templateWithParams(
        new Recipient('919876500123'), 'order_dispatched_update', 'en', ['Asha', 'ORD-1'],
    ), ['idempotency_key' => 'mock-flow-1']);

    // sync queue → EmitMockStatusWebhookJob ran → statuses applied
    expect($message->fresh()->status)->toBe(MessageStatus::Read);
});
