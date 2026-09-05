<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Message;
use App\Models\WhatsappBusinessAccount;
use App\Services\WhatsApp\MediaLibrary;
use App\Services\WhatsApp\WhatsAppManager;
use App\Support\TenantContext;
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

    public function handle(WhatsAppManager $manager, MediaLibrary $mediaLibrary, TenantContext $tenant): void
    {
        $message = Message::query()->withoutGlobalScopes()->find($this->messageId);
        if ($message === null || $message->media_id !== null || ! $message->hasDownloadableMedia()) {
            return;
        }

        $tenant->set($message->organization()->firstOrFail());

        $account = $message->phoneNumber?->businessAccount;
        if (! $account instanceof WhatsappBusinessAccount) {
            return;
        }

        $mediaId = $message->metaMediaId();
        if ($mediaId === null) {
            return;
        }

        try {
            $creds = $manager->credentialsFor($account, $message->phoneNumber);
            $file = $manager->driver()->downloadMedia($creds, $mediaId);
            $media = $mediaLibrary->storeRaw($file['contents'], $file['mime_type'], "{$mediaId}");

            $message->forceFill(['media_id' => $media->getKey()])->save();
        } catch (Throwable $e) {
            // Non-fatal: the message itself is already stored; the chat view
            // just falls back to "media unavailable". Let the retry/backoff
            // above absorb transient Graph API errors.
            report($e);

            throw $e;
        }
    }
}
