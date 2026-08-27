<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Webhooks;

/**
 * Verifies Meta's `X-Hub-Signature-256` header: HMAC-SHA256 of the raw request
 * body keyed with the app secret, hex-encoded, prefixed `sha256=`.
 *
 * All comparisons are constant-time.
 */
final class WebhookSignature
{
    public static function isValid(string $rawBody, ?string $header, string $appSecret): bool
    {
        if ($appSecret === '' || $header === null || ! str_starts_with($header, 'sha256=')) {
            return false;
        }

        $provided = substr($header, 7);
        $expected = hash_hmac('sha256', $rawBody, $appSecret);

        return hash_equals($expected, $provided);
    }

    /** Constant-time verify-token comparison for the GET subscription handshake. */
    public static function verifyTokenMatches(?string $provided, string $expected): bool
    {
        return $expected !== '' && $provided !== null && hash_equals($expected, $provided);
    }
}
