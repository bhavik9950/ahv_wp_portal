<?php

declare(strict_types=1);

namespace App\Enums;

enum VoiceCallStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Processing = 'processing';
    case Placed = 'placed';       // handed to the provider, awaiting a delivery report
    case Answered = 'answered';
    case Failed = 'failed';
    case Skipped = 'skipped';

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $s) => $s->value, self::cases());
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Answered, self::Failed, self::Skipped], true);
    }
}
