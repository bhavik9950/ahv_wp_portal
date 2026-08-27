<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Data;

use App\Enums\ErrorCategory;

final readonly class NormalizedError
{
    public function __construct(
        public ErrorCategory $category,
        public ?string $code,
        public string $userMessage,
        public string $adminMessage,
        public ?int $retryAfterSeconds = null,
        /** Raw Meta error payload, for admin diagnostics only — never shown to end users. */
        public array $raw = [],
    ) {}

    public function isRetryable(): bool
    {
        return $this->category->isRetryable();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'category' => $this->category->value,
            'code' => $this->code,
            'user_message' => $this->userMessage,
            'admin_message' => $this->adminMessage,
            'retry_after' => $this->retryAfterSeconds,
        ];
    }
}
