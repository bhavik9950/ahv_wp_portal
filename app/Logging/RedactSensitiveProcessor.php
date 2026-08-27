<?php

declare(strict_types=1);

namespace App\Logging;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;

/**
 * Removes credentials from log records and masks phone numbers.
 *
 * Applied to the dedicated "whatsapp" log channel. Belt-and-braces: the
 * application also avoids passing secrets to the logger in the first place.
 */
final class RedactSensitiveProcessor implements ProcessorInterface
{
    /** Context / payload keys whose values must never be written. */
    private const REDACT_KEYS = [
        'access_token', 'token', 'app_secret', 'client_secret', 'secret',
        'authorization', 'auth', 'password', 'api_key', 'apikey',
        'webhook_verify_token', 'verify_token', 'hub_verify_token',
        'x-hub-signature-256', 'signature',
    ];

    /** Patterns scrubbed from any string value. */
    private const VALUE_PATTERNS = [
        // Meta user/system access tokens (EAA... long base64-ish blobs).
        '/\bEAA[0-9A-Za-z]{20,}\b/' => '«redacted-token»',
        // Bearer headers.
        '/[Bb]earer\s+[0-9A-Za-z._\-]+/' => 'Bearer «redacted»',
    ];

    public function __invoke(LogRecord $record): LogRecord
    {
        return $record->with(
            message: $this->scrubString($record->message),
            context: $this->scrub($record->context),
            extra: $this->scrub($record->extra),
        );
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private function scrub(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && in_array(strtolower($key), self::REDACT_KEYS, true)) {
                $data[$key] = '«redacted»';

                continue;
            }

            if (is_array($value)) {
                $data[$key] = $this->scrub($value);
            } elseif (is_string($value)) {
                $data[$key] = $this->maskPhone($this->scrubString($value), $key);
            }
        }

        return $data;
    }

    private function scrubString(string $value): string
    {
        foreach (self::VALUE_PATTERNS as $pattern => $replacement) {
            $value = (string) preg_replace($pattern, $replacement, $value);
        }

        return $value;
    }

    private function maskPhone(string $value, int|string $key): string
    {
        $keyName = is_string($key) ? strtolower($key) : '';

        if (str_contains($keyName, 'phone') || str_contains($keyName, 'msisdn') || str_contains($keyName, 'to')) {
            return $this->maskMsisdn($value);
        }

        // Also mask bare E.164-looking numbers anywhere in the string.
        return (string) preg_replace_callback(
            '/\+?\d{8,15}/',
            fn (array $m): string => $this->maskMsisdn($m[0]),
            $value,
        );
    }

    private function maskMsisdn(string $number): string
    {
        $digits = preg_replace('/\D/', '', $number) ?? '';

        if (strlen($digits) < 4) {
            return $number;
        }

        return str_repeat('*', max(0, strlen($digits) - 4)).substr($digits, -4);
    }
}
