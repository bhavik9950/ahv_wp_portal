<?php

declare(strict_types=1);

use App\Enums\ErrorCategory;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\Recipient;
use App\Services\WhatsApp\Data\WabaCredentials;
use App\Services\WhatsApp\Drivers\MockWhatsAppDriver;

function mockCreds(): WabaCredentials
{
    return new WabaCredentials(
        accessToken: 'EAAtest',
        wabaId: '123',
        phoneNumberId: '456',
        apiVersion: 'v22.0',
        baseUrl: 'https://graph.facebook.com',
        webhookVerifyToken: 'verify',
    );
}

it('accepts a normal number and returns a wamid', function () {
    $result = (new MockWhatsAppDriver)->send(
        mockCreds(),
        OutboundMessage::text(new Recipient('919876500123'), 'hi'),
    );

    expect($result->accepted)->toBeTrue()
        ->and($result->wamid)->toStartWith('wamid.MOCK-');
});

it('simulates deterministic failures by number suffix', function (string $number, ErrorCategory $category, bool $retryable) {
    $result = (new MockWhatsAppDriver)->send(
        mockCreds(),
        OutboundMessage::text(new Recipient($number), 'hi'),
    );

    expect($result->failed())->toBeTrue()
        ->and($result->error->category)->toBe($category)
        ->and($result->error->isRetryable())->toBe($retryable);
})->with([
    ['919000000000', ErrorCategory::InvalidRecipient, false],
    ['919000000429', ErrorCategory::RateLimited, true],
    ['919000000500', ErrorCategory::Temporary, true],
    ['919000000555', ErrorCategory::Template, false],
    ['919000000401', ErrorCategory::Auth, false],
]);

it('returns connection checks covering every validator', function () {
    $checks = (new MockWhatsAppDriver)->runConnectionChecks(mockCreds());

    expect(collect($checks)->pluck('key')->all())
        ->toBe(['connection', 'phone_number', 'waba', 'permissions', 'token', 'webhook']);
});
