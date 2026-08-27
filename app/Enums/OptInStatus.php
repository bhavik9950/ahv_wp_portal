<?php

declare(strict_types=1);

namespace App\Enums;

enum OptInStatus: string
{
    case Unknown = 'unknown';
    case OptedIn = 'opted_in';
    case OptedOut = 'opted_out';

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Unknown',
            self::OptedIn => 'Opted in',
            self::OptedOut => 'Opted out',
        };
    }

    public function canReceiveMarketing(): bool
    {
        return $this === self::OptedIn;
    }
}
