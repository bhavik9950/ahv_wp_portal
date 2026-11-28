<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Message;
use App\Services\WhatsApp\InboundMediaFetcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Fetches an inbound image/video/audio/document from Meta and stores a local
 * copy so the conversation view can render it — Meta's media URLs are
 * short-lived and require the access token, so this must happen promptly
 * after the webhook arrives, not on demand when someone opens the page.
 */
class DownloadInboundMediaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function __construct(public string $messageId) {}

    public function handle(InboundMediaFetcher $fetcher): void
    {
        $message = Message::query()->withoutGlobalScopes()->find($this->messageId);
        if ($message === null || $message->media_id !== null || ! $message->hasDownloadableMedia()) {
            return;
        }

        try {
            $fetcher->fetch($message);
        } catch (Throwable $e) {
            // Non-fatal: the message itself is already stored; the chat view
            // will retry on demand or fall back to "media unavailable".
            report($e);

            throw $e;
        }
    }
}
