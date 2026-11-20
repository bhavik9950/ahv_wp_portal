<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ProcessWhatsAppWebhookJob;
use App\Models\WebhookEvent;
use App\Services\WhatsApp\MessageStatusUpdater;
use Illuminate\Console\Command;

class ReprocessWebhooksCommand extends Command
{
    protected $signature = 'whatsapp:reprocess-webhooks {--force : Force mark signatures valid and process ignored events} {--sync : Process immediately in current process instead of queuing}';

    protected $description = 'Reprocess ignored or un-processed WhatsApp webhook events';

    public function handle(MessageStatusUpdater $statusUpdater): int
    {
        $force = (bool) $this->option('force');
        $sync = (bool) $this->option('sync');

        $query = WebhookEvent::query()->whereIn('status', ['ignored', 'received']);

        $events = $query->get();

        if ($events->isEmpty()) {
            $this->info('No ignored or received webhook events found to reprocess.');
            return self::SUCCESS;
        }

        $this->info("Found {$events->count()} webhook events to reprocess.");

        $processed = 0;

        foreach ($events as $event) {
            $updates = [
                'status' => 'received',
            ];

            if ($force || ! $event->signature_valid) {
                $updates['signature_valid'] = true;
            }

            $event->update($updates);

            if ($sync) {
                (new ProcessWhatsAppWebhookJob($event->getKey()))->handle($statusUpdater);
            } else {
                ProcessWhatsAppWebhookJob::dispatch($event->getKey())->onQueue('whatsapp-webhook');
            }

            $processed++;
        }

        $this->info("Successfully dispatched/processed {$processed} events.");

        return self::SUCCESS;
    }
}
