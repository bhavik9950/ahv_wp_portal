<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\ErrorCategory;
use App\Services\WhatsApp\Data\NormalizedError;

/**
 * Turns a raw Meta Graph API error (or a transport failure) into a
 * NormalizedError with a retry classification and a safe user-facing message.
 *
 * Codes are grouped conservatively; unrecognised codes fall back to Unknown
 * (single retry then fail) rather than being assumed retryable.
 */
final class MetaErrorMapper
{
    /** Meta error codes that are transient and safe to retry with backoff. */
    private const TEMPORARY = [1, 2, 4, 131000, 131016, 131026, 133010];

    private const RATE_LIMITED = [80007, 130429, 131048, 131056, 4];

    private const AUTH = [0, 10, 190, 200, 803];

    private const INVALID_RECIPIENT = [131021, 131026, 131047, 131051, 131052];

    private const TEMPLATE = [132000, 132001, 132005, 132007, 132012, 132015, 132016, 132068, 132069];

    private const MEDIA = [131053, 131057, 130472];

    public function fromHttp(int $httpStatus, array $body, array $headers = []): NormalizedError
    {
        $error = $body['error'] ?? [];
        $code = isset($error['code']) ? (int) $error['code'] : null;
        $subcode = isset($error['error_subcode']) ? (int) $error['error_subcode'] : null;
        $metaMessage = (string) ($error['message'] ?? $error['error_user_msg'] ?? 'Unknown WhatsApp API error');
        $retryAfter = $this->retryAfter($headers);

        $category = match (true) {
            $httpStatus === 429 || $this->in($code, self::RATE_LIMITED) => ErrorCategory::RateLimited,
            $httpStatus >= 500 || $this->in($code, self::TEMPORARY) => ErrorCategory::Temporary,
            $httpStatus === 401 || $httpStatus === 403 || $this->in($code, self::AUTH) => ErrorCategory::Auth,
            $this->in($code, self::TEMPLATE) => ErrorCategory::Template,
            $this->in($code, self::MEDIA) => ErrorCategory::Media,
            $this->in($code, self::INVALID_RECIPIENT) => ErrorCategory::InvalidRecipient,
            default => ErrorCategory::Unknown,
        };

        return new NormalizedError(
            category: $category,
            code: $code !== null ? (string) $code : (string) $httpStatus,
            userMessage: $this->userMessage($category),
            adminMessage: sprintf('[HTTP %d code %s/%s] %s', $httpStatus, $code ?? '-', $subcode ?? '-', $metaMessage),
            retryAfterSeconds: $category === ErrorCategory::RateLimited ? ($retryAfter ?? 60) : $retryAfter,
            raw: $error,
        );
    }

    public function fromTransportException(\Throwable $e): NormalizedError
    {
        return new NormalizedError(
            category: ErrorCategory::Temporary,
            code: 'transport',
            userMessage: $this->userMessage(ErrorCategory::Temporary),
            adminMessage: 'Transport error: '.$e->getMessage(),
            retryAfterSeconds: null,
        );
    }

    private function userMessage(ErrorCategory $category): string
    {
        return match ($category) {
            ErrorCategory::RateLimited => 'WhatsApp is temporarily limiting sending for this account. The message will be retried automatically.',
            ErrorCategory::Temporary => 'WhatsApp had a temporary problem. The message will be retried automatically.',
            ErrorCategory::Auth => 'The WhatsApp connection needs attention. Please contact an administrator.',
            ErrorCategory::InvalidRecipient => 'This message could not be delivered to the recipient on WhatsApp.',
            ErrorCategory::Template => 'WhatsApp rejected this message because the selected template is not available for this recipient.',
            ErrorCategory::Media => 'WhatsApp could not process the attached media.',
            ErrorCategory::Permanent, ErrorCategory::Unknown => 'This message could not be sent via WhatsApp.',
        };
    }

    private function in(?int $code, array $set): bool
    {
        return $code !== null && in_array($code, $set, true);
    }

    private function retryAfter(array $headers): ?int
    {
        foreach (['Retry-After', 'retry-after'] as $key) {
            $value = $headers[$key][0] ?? $headers[$key] ?? null;
            if (is_numeric($value)) {
                return (int) $value;
            }
        }

        return null;
    }
}
