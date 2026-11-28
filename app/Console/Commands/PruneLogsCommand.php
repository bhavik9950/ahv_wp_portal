<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PruneLogsCommand extends Command
{
    protected $signature = 'logs:prune
                            {--days=3 : Delete dated log files older than N days}
                            {--max-mb=2 : Truncate persistent log files larger than N MB}
                            {--all : Truncate all log files immediately regardless of size/age}';

    protected $description = 'Prune old dated logs and truncate oversized persistent log files (scheduler.log, worker.log, laravel.log).';

    public function handle(): int
    {
        $logDir = storage_path('logs');
        if (! is_dir($logDir)) {
            $this->info('No logs directory found.');

            return self::SUCCESS;
        }

        $all = (bool) $this->option('all');
        $days = max(1, (int) $this->option('days'));
        $maxBytes = max(1, (int) $this->option('max-mb')) * 1024 * 1024;
        $cutoff = now()->subDays($days)->getTimestamp();

        $deleted = 0;
        $truncated = 0;
        $skipped = 0;

        foreach (File::files($logDir) as $file) {
            if ($file->getExtension() !== 'log') {
                continue;
            }

            $name = $file->getFilename();
            $path = $file->getPathname();
            $isDated = (bool) preg_match('/-\d{4}-\d{2}-\d{2}\.log$/', $name);

            if ($isDated && ($all || $file->getMTime() < $cutoff)) {
                if (@unlink($path)) {
                    $deleted++;
                } else {
                    $skipped++;
                }
                continue;
            }

            if ($all || $file->getSize() > $maxBytes) {
                if (@file_put_contents($path, '') !== false) {
                    $truncated++;
                } else {
                    $skipped++;
                }
            }
        }

        $this->info("Logs pruned: {$truncated} truncated, {$deleted} old files deleted, {$skipped} skipped (permission).");

        return self::SUCCESS;
    }
}
