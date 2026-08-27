<?php

declare(strict_types=1);

use App\Logging\RedactSensitiveProcessor;
use Monolog\Level;
use Monolog\LogRecord;

function record(string $message, array $context = []): LogRecord
{
    return new LogRecord(
        datetime: new DateTimeImmutable,
        channel: 'whatsapp',
        level: Level::Info,
        message: $message,
        context: $context,
    );
}

it('redacts access tokens in message text', function () {
    $out = (new RedactSensitiveProcessor)(record('calling with EAAOCuylZCzZA8BRXtokenmaterialxxxxxxxxxxxxxxxxxxxx now'));

    expect($out->message)->not->toContain('EAAOCuylZ')
        ->and($out->message)->toContain('«redacted-token»');
});

it('redacts sensitive context keys', function () {
    $out = (new RedactSensitiveProcessor)(record('req', [
        'access_token' => 'EAAsecret',
        'app_secret' => 'shhh',
        'authorization' => 'Bearer abc.def',
        'order_id' => 'ORD-1',
    ]));

    expect($out->context['access_token'])->toBe('«redacted»')
        ->and($out->context['app_secret'])->toBe('«redacted»')
        ->and($out->context['authorization'])->toBe('«redacted»')
        ->and($out->context['order_id'])->toBe('ORD-1');
});

it('masks phone numbers to the last four digits', function () {
    $out = (new RedactSensitiveProcessor)(record('sending', ['to_phone' => '919876543210']));

    expect($out->context['to_phone'])->toBe('********3210');
});
