<?php

declare(strict_types=1);

use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\WebhookEvent;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;

const APP_SECRET = 'test-app-secret-value-1234';

function signedWebhook(array $payload): array
{
    $raw = json_encode($payload, JSON_THROW_ON_ERROR);

    return [$raw, 'sha256='.hash_hmac('sha256', $raw, APP_SECRET)];
}

function configureWaba(): WhatsappPhoneNumber
{
    $org = makeOrganization();
    $account = WhatsappBusinessAccount::factory()->for($org)->create([
        'app_secret' => APP_SECRET,
        'webhook_verify_token' => 'verify-me',
    ]);

    return WhatsappPhoneNumber::factory()->forAccount($account)->create([
        'phone_number_id' => '111222333',
    ]);
}

it('answers the GET verification handshake only with a matching token', function () {
    configureWaba();

    $this->get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=verify-me&hub_challenge=CHALLENGE-123')
        ->assertOk()->assertSee('CHALLENGE-123');

    $this->get('/api/webhooks/whatsapp?hub_mode=subscribe&hub_verify_token=wrong&hub_challenge=x')
        ->assertForbidden();
});

it('rejects a webhook with a bad signature', function () {
    configureWaba();

    $this->call('POST', '/api/webhooks/whatsapp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => 'sha256=deadbeef',
    ], json_encode(['entry' => []]))
        ->assertForbidden();

    expect(WebhookEvent::where('status', 'ignored')->count())->toBe(1);
});

it('processes a valid delivery-status webhook and advances the message forward-only', function () {
    $number = configureWaba();
    $message = Message::factory()->for($number->organization)->create([
        'whatsapp_phone_number_id' => $number->getKey(),
        'wamid' => 'wamid.ABC123',
        'status' => MessageStatus::Sent,
    ]);

    [$raw, $sig] = signedWebhook([
        'entry' => [[
            'changes' => [[
                'field' => 'messages',
                'value' => [
                    'metadata' => ['phone_number_id' => '111222333'],
                    'statuses' => [
                        ['id' => 'wamid.ABC123', 'status' => 'delivered', 'timestamp' => (string) now()->timestamp],
                        ['id' => 'wamid.ABC123', 'status' => 'read', 'timestamp' => (string) now()->addSecond()->timestamp],
                    ],
                ],
            ]],
        ]],
    ]);

    $this->call('POST', '/api/webhooks/whatsapp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => $sig,
    ], $raw)->assertOk();

    $message->refresh();
    expect($message->status)->toBe(MessageStatus::Read)
        ->and($message->delivered_at)->not->toBeNull()
        ->and($message->read_at)->not->toBeNull()
        ->and($message->statusEvents()->pluck('status')->all())->toContain('delivered', 'read');
});

it('does not duplicate work when Meta retries the same webhook', function () {
    $number = configureWaba();
    $message = Message::factory()->for($number->organization)->create([
        'whatsapp_phone_number_id' => $number->getKey(),
        'wamid' => 'wamid.DUP',
        'status' => MessageStatus::Sent,
    ]);

    [$raw, $sig] = signedWebhook([
        'entry' => [['changes' => [['field' => 'messages', 'value' => [
            'metadata' => ['phone_number_id' => '111222333'],
            'statuses' => [['id' => 'wamid.DUP', 'status' => 'delivered', 'timestamp' => '1700000000']],
        ]]]]],
    ]);

    $headers = ['CONTENT_TYPE' => 'application/json', 'HTTP_X_HUB_SIGNATURE_256' => $sig];

    $this->call('POST', '/api/webhooks/whatsapp', [], [], [], $headers, $raw)->assertOk()->assertSee('ok');
    $this->call('POST', '/api/webhooks/whatsapp', [], [], [], $headers, $raw)->assertOk()->assertSee('duplicate');

    expect(WebhookEvent::count())->toBe(1)
        ->and($message->statusEvents()->where('status', 'delivered')->count())->toBe(1);
});

it('records an inbound message from a customer', function () {
    $number = configureWaba();

    [$raw, $sig] = signedWebhook([
        'entry' => [['changes' => [['field' => 'messages', 'value' => [
            'metadata' => ['phone_number_id' => '111222333'],
            'messages' => [[
                'id' => 'wamid.INBOUND1',
                'from' => '919999911111',
                'type' => 'text',
                'text' => ['body' => 'Hello there'],
            ]],
        ]]]]],
    ]);

    $this->call('POST', '/api/webhooks/whatsapp', [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUB_SIGNATURE_256' => $sig,
    ], $raw)->assertOk();

    $inbound = Message::withoutGlobalScopes()->where('wamid', 'wamid.INBOUND1')->first();
    expect($inbound)->not->toBeNull()
        ->and($inbound->direction)->toBe('inbound');
});
