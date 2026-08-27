<?php

declare(strict_types=1);

namespace App\Enums;

enum ErrorCategory: string
{
    case Temporary = 'temporary';
    case Permanent = 'permanent';
    case RateLimited = 'rate_limited';
    case Auth = 'auth';
    case InvalidRecipient = 'invalid_recipient';
    case Template = 'template';
    case Media = 'media';
    case Unknown = 'unknown';

    public function isRetryable(): bool
    {
        return in_array($this, [self::Temporary, self::RateLimited], true);
    }
}
