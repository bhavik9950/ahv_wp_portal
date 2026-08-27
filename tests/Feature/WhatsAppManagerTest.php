<?php

declare(strict_types=1);

use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\Recipient;
use App\Services\WhatsApp\WhatsAppManager;
use App\Services\WhatsApp\WhatsAppSendingDisabledException;

function phoneNumber(): WhatsappPhoneNumber
{
    $org = makeOrganization();
    $account = WhatsappBusinessAccount::factory()->for($org)->create();

    return WhatsappPhoneNumber::factory()->forAccount($account)->create();
}

it('sends through the mock driver and returns a wamid', function () {
    $result = app(WhatsAppManager::class)->send(
        phoneNumber(),
        OutboundMessage::text(new Recipient('919876500123'), 'hello'),
    );

    expect($result->accepted)->toBeTrue()
        ->and($result->wamid)->toStartWith('wamid.MOCK-');
});

it('refuses to send when the global kill switch is off', function () {
    config()->set('services.whatsapp.sending_enabled', false);

    app(WhatsAppManager::class)->send(
        phoneNumber(),
        OutboundMessage::text(new Recipient('919876500123'), 'hello'),
    );
})->throws(WhatsAppSendingDisabledException::class);

it('refuses to send through an inactive WABA', function () {
    $org = makeOrganization();
    $account = WhatsappBusinessAccount::factory()->for($org)->inactive()->create();
    $number = WhatsappPhoneNumber::factory()->forAccount($account)->create();

    app(WhatsAppManager::class)->send(
        $number,
        OutboundMessage::text(new Recipient('919876500123'), 'hello'),
    );
})->throws(RuntimeException::class, 'not active');

it('stores credentials encrypted at rest', function () {
    $org = makeOrganization();
    $account = WhatsappBusinessAccount::factory()->for($org)->create(['access_token' => 'EAAsupersecret']);

    $raw = DB::table('whatsapp_business_accounts')->where('id', $account->getKey())->value('access_token');

    expect($raw)->not->toContain('EAAsupersecret')
        ->and($account->fresh()->access_token)->toBe('EAAsupersecret')
        ->and($account->fresh()->maskedAccessToken())->toBe('••••••••cret');
});
