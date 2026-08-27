<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use RuntimeException;

final class RateLimitedException extends RuntimeException
{
    public function __construct(public readonly int $retryAfterSeconds)
    {
        parent::__construct("Rate limited; retry after {$retryAfterSeconds}s.");
    }
}
