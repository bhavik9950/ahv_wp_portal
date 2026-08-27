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

    /** Forward-only ordering for delivery progress. Non-progress states rank -1. */
    private function rank(): int
    {
        return match ($this) {
            self::Pending => 0,
            self::Queued => 1,
            self::Processing => 2,
            self::Sent => 3,
            self::Delivered => 4,
            self::Read => 5,
            default => -1,
        };
    }

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

        return $next->rank() > $this->rank();
    }
}
