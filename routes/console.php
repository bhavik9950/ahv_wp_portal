<?php

declare(strict_types=1);

use App\Jobs\SyncTemplatesJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 | Scheduled tasks. Run `php artisan schedule:work` locally or a cron entry
 | `* * * * * php artisan schedule:run` in production.
 */
Schedule::job(new SyncTemplatesJob, 'whatsapp-high')->hourly()->withoutOverlapping();

Schedule::command('queue:prune-failed --hours=168')->daily();
