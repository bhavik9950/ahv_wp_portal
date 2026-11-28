<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Models\Media;
use App\Models\Message;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Support\TenantContext;

final class InboundMediaFetcher
{
    public function __construct(
        private readonly WhatsAppManager $manager,
        private readonly MediaLibrary $mediaLibrary,
        private readonly TenantContext $tenant,
    ) {}

    public function fetch(Message $message): ?Media
    {
        if ($message->media_id !== null) {
            return $message->media()->first();
        }

        if (! $message->hasDownloadableMedia()) {
            return null;
        }

        $org = $message->organization()->first();
        if ($org === null) {
            return null;
        }

        $this->tenant->set($org);

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
            return null;
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
