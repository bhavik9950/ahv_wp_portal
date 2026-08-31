<?php

declare(strict_types=1);

namespace App\Logging;

use DateTimeZone;
use Illuminate\Log\Logger;
use Monolog\Logger as Monolog;

/**
 * Log-channel tap that stamps entries in a human-facing timezone (IST by
 * default) while the rest of the app keeps storing timestamps in UTC.
 *
 * Wire it on a channel in config/logging.php:
 *   'tap' => [\App\Logging\UseDisplayTimezone::class],
 */
class UseDisplayTimezone
{
    public function __invoke(Logger $logger): void
    {
        $tz = new DateTimeZone((string) config('logging.display_timezone', 'Asia/Kolkata'));

        $monolog = $logger->getLogger();
        if ($monolog instanceof Monolog) {
            $monolog->setTimezone($tz);
        }
    }
}
