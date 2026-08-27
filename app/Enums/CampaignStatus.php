<?php

declare(strict_types=1);

namespace App\Enums;

enum CampaignStatus: string
{
    case Draft = 'draft';
    case Scheduled = 'scheduled';
    case Processing = 'processing';
    case Paused = 'paused';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Failed = 'failed';

    public function isActive(): bool
    {
        return in_array($this, [self::Scheduled, self::Processing, self::Paused], true);
    }

    public function acceptsNewSends(): bool
    {
        return $this === self::Processing;
    }
}
