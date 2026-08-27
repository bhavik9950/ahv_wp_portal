<?php

declare(strict_types=1);

namespace App\Enums;

enum CampaignRecipientStatus: string
{
    case Pending = 'pending';
    case Queued = 'queued';
    case Processing = 'processing';
    case Sent = 'sent';
    case Delivered = 'delivered';
    case Read = 'read';
    case Failed = 'failed';
    case Skipped = 'skipped';
    case OptedOut = 'opted_out';

    public function isProcessed(): bool
    {
        return ! in_array($this, [self::Pending, self::Queued, self::Processing], true);
    }

    public function isSuccessful(): bool
    {
        return in_array($this, [self::Sent, self::Delivered, self::Read], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Failed, self::Skipped, self::OptedOut], true) || $this->isSuccessful();
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $c) => $c->value, self::cases());
    }
}
