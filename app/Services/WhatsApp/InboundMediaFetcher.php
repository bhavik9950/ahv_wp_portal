<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\Media;
use App\Models\Message;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;

final class InboundMediaFetcher
{
    public function __construct(
        private readonly WhatsAppManager $manager,
        private readonly MediaLibrary $mediaLibrary,
        private readonly TenantContext $tenant,
    ) {}

    public function fetch(Message $message): ?Media
    {
        $org = $message->organization()->first();
        if ($org === null) {
            throw new \RuntimeException("Message {$message->getKey()} has no organization.");
        }

        $this->tenant->set($org);

        if ($message->media_id !== null) {
            $existing = $message->media()->withoutGlobalScopes()->first();
            if ($existing instanceof Media && Storage::disk($existing->disk)->exists($existing->path)) {
                if ((int) $existing->organization_id !== (int) $org->getKey()) {
                    $existing->forceFill(['organization_id' => $org->getKey()])->save();
                }
                $message->setRelation('media', $existing);

                return $existing;
            }
        }

        if (! $message->hasDownloadableMedia()) {
            return null;
        }

        $phoneNumber = $message->phoneNumber()->withoutGlobalScopes()->first()
            ?? WhatsappPhoneNumber::query()->withoutGlobalScopes()
                ->where('organization_id', $org->getKey())
                ->orderByDesc('is_default')
                ->first();

        $account = $phoneNumber?->businessAccount()->withoutGlobalScopes()->first()
            ?? WhatsappBusinessAccount::query()->withoutGlobalScopes()
                ->where('organization_id', $org->getKey())
                ->orderByDesc('is_active')
                ->first();

        if (! $account instanceof WhatsappBusinessAccount) {
            throw new \RuntimeException("No WhatsApp Business Account found for organization {$org->getKey()}.");
        }

        $mediaId = $message->metaMediaId();
        if ($mediaId === null) {
            return null;
        }

        $creds = $this->manager->credentialsFor($account, $phoneNumber);
        $file = $this->manager->driver()->downloadMedia($creds, $mediaId);
        $media = $this->mediaLibrary->storeRaw($file['contents'], $file['mime_type'], "{$mediaId}");

        $message->forceFill(['media_id' => $media->getKey()])->save();
        $message->setRelation('media', $media);

        return $media;
    }
}
