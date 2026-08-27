<?php

declare(strict_types=1);

use App\Enums\MessageStatus;
use App\Enums\OptInStatus;
use App\Models\Contact;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\Recipient;
use App\Services\WhatsApp\OutboundMessageService;

it('skips a MARKETING template to an opted-out contact', function () {
    $org = makeOrganization();
    bindTenant($org);
    $account = WhatsappBusinessAccount::factory()->for($org)->create();
    $number = WhatsappPhoneNumber::factory()->forAccount($account)->create();
    $contact = Contact::factory()->for($org)->optedOut()->create();
    $template = WhatsappTemplate::factory()->forAccount($account)->create(['category' => 'MARKETING']);

    $message = app(OutboundMessageService::class)->send(
        $number,
        OutboundMessage::templateWithParams(new Recipient($contact->phone_e164), $template->name, $template->language),
        ['contact_id' => $contact->getKey(), 'template_id' => $template->getKey(), 'idempotency_key' => 'oo-1'],
    );

    expect($message->status)->toBe(MessageStatus::Skipped)
        ->and($message->error_code)->toBe('opted_out')
        ->and($message->wamid)->toBeNull();
});

it('still delivers a UTILITY template to an opted-out contact', function () {
    $org = makeOrganization();
    bindTenant($org);
    $account = WhatsappBusinessAccount::factory()->for($org)->create();
    $number = WhatsappPhoneNumber::factory()->forAccount($account)->create();
    $contact = Contact::factory()->for($org)->optedOut()->create();
    $template = WhatsappTemplate::factory()->forAccount($account)->create(['category' => 'UTILITY']);

    $message = app(OutboundMessageService::class)->send(
        $number,
        OutboundMessage::templateWithParams(new Recipient($contact->phone_e164), $template->name, $template->language),
        ['contact_id' => $contact->getKey(), 'template_id' => $template->getKey(), 'idempotency_key' => 'oo-2'],
    );

    expect($message->isSuccessful())->toBeTrue();
});

it('opts a contact out when they reply STOP', function () {
    $org = makeOrganization();
    bindTenant($org);
    $account = WhatsappBusinessAccount::factory()->for($org)->create([
        'app_secret' => 'secret-value-000000',
    ]);
    $number = WhatsappPhoneNumber::factory()->forAccount($account)->create(['phone_number_id' => '55501']);
    $contact = Contact::factory()->for($org)->create(['opt_in_status' => OptInStatus::OptedIn->value]);

    $payload = [
        'entry' => [['changes' => [['field' => 'messages', 'value' => [
            'metadata' => ['phone_number_id' => '55501'],
            'messages' => [[
                'id' => 'wamid.STOP1',
                'from' => $contact->phone_e164,
                'type' => 'text',
                'text' => ['body' => 'STOP'],
            ]],
        ]]]]],
    ];
    $raw = json_encode($payload, JSON_THROW_ON_ERROR);

    $this->call('POST', '/api/webhooks/whatsapp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256='.hash_hmac('sha256', $raw, 'secret-value-000000'),
    ], $raw)->assertOk();

    expect($contact->fresh()->opt_in_status)->toBe(OptInStatus::OptedOut);
});
