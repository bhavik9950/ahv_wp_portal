<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Drivers;

use App\Enums\ErrorCategory;
use App\Services\WhatsApp\Contracts\WhatsAppDriver;
use App\Services\WhatsApp\Data\ConnectionCheck;
use App\Services\WhatsApp\Data\NormalizedError;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\SendResult;
use App\Services\WhatsApp\Data\WabaCredentials;
use Illuminate\Support\Str;

/**
 * Offline simulator. Behaviour is driven by the last 4 digits of the recipient
 * so tests and manual QA can deterministically exercise every path without
 * calling Meta:
 *
 *   xxxx0000  invalid recipient (permanent)
 *   xxxx0429  rate limited (retryable, Retry-After 30)
 *   xxxx0500  temporary error (retryable)
 *   xxxx0555  template rejected (permanent)
 *   xxxx0401  auth failure (permanent)
 *   anything else  accepted
 */
final class MockWhatsAppDriver implements WhatsAppDriver
{
    public function name(): string
    {
        return 'mock';
    }

    public function send(WabaCredentials $creds, OutboundMessage $message): SendResult
    {
        $suffix = substr($message->to->e164, -4);

        return match ($suffix) {
            '0000' => SendResult::failure($this->error(ErrorCategory::InvalidRecipient, '131026', 'Recipient is not a valid WhatsApp user (mock).')),
            '0429' => SendResult::failure($this->error(ErrorCategory::RateLimited, '130429', 'Rate limit hit (mock).', 30)),
            '0500' => SendResult::failure($this->error(ErrorCategory::Temporary, '131000', 'Temporary internal error (mock).')),
            '0555' => SendResult::failure($this->error(ErrorCategory::Template, '132001', 'Template does not exist or is not approved (mock).')),
            '0401' => SendResult::failure($this->error(ErrorCategory::Auth, '190', 'Access token is invalid (mock).')),
            default => SendResult::accepted('wamid.MOCK-'.Str::upper(Str::random(24)), ['mock' => true]),
        };
    }

    public function fetchTemplates(WabaCredentials $creds): array
    {
        return [
            [
                'id' => 'mock-tmpl-1',
                'name' => 'order_dispatched_update',
                'language' => 'en',
                'status' => 'APPROVED',
                'category' => 'UTILITY',
                'components' => [
                    ['type' => 'BODY', 'text' => 'Hello {{1}}, your order {{2}} has been dispatched.'],
                ],
            ],
            [
                'id' => 'mock-tmpl-2',
                'name' => 'promo_autumn_sale',
                'language' => 'en',
                'status' => 'PENDING',
                'category' => 'MARKETING',
                'components' => [
                    ['type' => 'BODY', 'text' => 'Hi {{1}}, our autumn sale is on!'],
                ],
            ],
        ];
    }

    public function createTemplate(WabaCredentials $creds, array $definition): array
    {
        return [
            'id' => 'mock-tmpl-'.Str::random(8),
            'status' => 'PENDING',
            'category' => $definition['category'] ?? 'UTILITY',
        ];
    }

    public function deleteTemplate(WabaCredentials $creds, string $name): void
    {
        // no-op
    }

    public function uploadMedia(WabaCredentials $creds, string $contents, string $mimeType, string $filename): string
    {
        return 'mock-media-'.Str::upper(Str::random(20));
    }

    public function uploadTemplateSample(WabaCredentials $creds, string $appId, string $contents, string $mimeType, string $filename): string
    {
        return '4::'.base64_encode('mock-sample-'.Str::random(16));
    }

    public function downloadMedia(WabaCredentials $creds, string $mediaId): array
    {
        // A tiny valid 1x1 PNG so local dev can preview "downloaded" media.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        return ['contents' => $png, 'mime_type' => 'image/png', 'sha256' => hash('sha256', $png)];
    }

    public function getPhoneNumber(WabaCredentials $creds, string $phoneNumberId): array
    {
        return [
            'id' => $phoneNumberId,
            'display_phone_number' => '+1 555 010 0000',
            'verified_name' => 'AH&V Mock Business',
            'quality_rating' => 'GREEN',
            'messaging_limit_tier' => 'TIER_1K',
        ];
    }

    public function listPhoneNumbers(WabaCredentials $creds): array
    {
        return [
            $this->getPhoneNumber($creds, $creds->phoneNumberId ?: 'mock-phone-1'),
        ];
    }

    public function runConnectionChecks(WabaCredentials $creds): array
    {
        $hasToken = $creds->accessToken !== '';

        return [
            ConnectionCheck::pass('connection', 'Test Connection', 'Mock driver reachable'),
            $creds->phoneNumberId
                ? ConnectionCheck::pass('phone_number', 'Validate Phone Number', 'Mock phone number OK')
                : ConnectionCheck::fail('phone_number', 'Validate Phone Number', 'No phone number configured'),
            $creds->wabaId !== ''
                ? ConnectionCheck::pass('waba', 'Validate WABA', 'Mock WABA OK')
                : ConnectionCheck::fail('waba', 'Validate WABA', 'No WABA ID configured'),
            $hasToken
                ? ConnectionCheck::pass('permissions', 'Check API Permissions', 'Mock permissions OK')
                : ConnectionCheck::fail('permissions', 'Check API Permissions', 'No access token configured'),
            $hasToken
                ? ConnectionCheck::pass('token', 'Check Token', 'Mock token valid')
                : ConnectionCheck::fail('token', 'Check Token', 'No access token configured'),
            $creds->webhookVerifyToken
                ? ConnectionCheck::pass('webhook', 'Check Webhook Configuration', 'Verify token present')
                : ConnectionCheck::fail('webhook', 'Check Webhook Configuration', 'No webhook verify token set'),
        ];
    }

    private function error(ErrorCategory $category, string $code, string $message, ?int $retryAfter = null): NormalizedError
    {
        return new NormalizedError(
            category: $category,
            code: $code,
            userMessage: 'Mock: '.$message,
            adminMessage: "[mock code {$code}] {$message}",
            retryAfterSeconds: $retryAfter,
            raw: ['code' => (int) $code, 'message' => $message, 'mock' => true],
        );
    }
}
