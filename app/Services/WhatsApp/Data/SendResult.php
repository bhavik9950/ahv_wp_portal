<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Data;

final readonly class SendResult
{
    private function __construct(
        public bool $accepted,
        public ?string $wamid,
        public ?NormalizedError $error,
        public array $raw = [],
    ) {}

    public static function accepted(?string $wamid, array $raw = []): self
    {
        return new self(true, $wamid, null, $raw);
    }

    public static function failure(NormalizedError $error, array $raw = []): self
    {
        return new self(false, null, $error, $raw);
    }

    public function failed(): bool
    {
        return ! $this->accepted;
    }
}
