<?php

declare(strict_types=1);

namespace App\Enums;

enum MessageStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Processing = 'processing';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Skipped = 'skipped';

    /** Forward-only ordering for delivery progress; terminal states excluded. */
    private const PROGRESS = [
        'pending' => 0,
        'queued' => 1,
        'processing' => 2,
        'sent' => 3,
        'delivered' => 4,
        'read' => 5,
    ];

    public function isTerminal(): bool
    {
        return in_array($this, [self::Read, self::Failed, self::Cancelled, self::Skipped], true);
    }

    /** True when transitioning to $next is a forward move (never regress sent->queued etc). */
    public function canTransitionTo(self $next): bool
    {
        if ($this === $next) {
            return false;
        }

        if ($this->isTerminal()) {
            return false;
        }

        if (in_array($next, [self::Failed, self::Cancelled, self::Skipped], true)) {
            return true;
        }

        $from = self::PROGRESS[$this->value] ?? -1;
        $to = self::PROGRESS[$next->value] ?? -1;

        return $to > $from;
    }
}
