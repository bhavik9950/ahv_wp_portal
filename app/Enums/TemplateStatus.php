<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Mirrors Meta's message template statuses. Meta may add values; unknown strings
 * from the API are stored verbatim and surfaced as Unknown here.
 */
enum TemplateStatus: string
{
    case Pending = 'PENDING';
    case Approved = 'APPROVED';
    case Rejected = 'REJECTED';
    case Paused = 'PAUSED';
    case Disabled = 'DISABLED';
    case Unknown = 'UNKNOWN';

    public static function fromMeta(?string $value): self
    {
        return self::tryFrom(strtoupper((string) $value)) ?? self::Unknown;
    }

    public function isSendable(): bool
    {
        return $this === self::Approved;
    }
}
