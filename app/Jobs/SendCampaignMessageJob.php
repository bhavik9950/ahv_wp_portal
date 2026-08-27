<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Enums\MessageStatus;
use App\Jobs\Concerns\BindsTenant;
use App\Models\Campaign;
use App\Models\CampaignRecipient;
use App\Models\WhatsappBusinessAccount;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\Recipient;
use App\Services\WhatsApp\MediaLibrary;
use App\Services\WhatsApp\OutboundMessageService;
use App\Services\WhatsApp\RateLimitedException;
use App\Services\WhatsApp\TransientSendException;
use App\Services\WhatsApp\WhatsAppSendingDisabledException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Sends one campaign recipient's message.
 *
 * Safe under retry / double-dispatch:
 *  - atomic claim (queued -> processing); a lost race just returns.
 *  - OutboundMessageService keys the Message row on
 *    idempotency = sha1(org:campaign:recipient) before the API call.
 *  - if the campaign is no longer processing, the recipient is returned to
 *    pending and nothing is sent.
 */
class SendCampaignMessageJob implements ShouldBeUnique, ShouldQueue
{
    use BindsTenant, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public function __construct(public string $recipientId) {}

    public function uniqueId(): string
    {
        return 'campaign-recipient:'.$this->recipientId;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return config('services.whatsapp.retry_backoff', [5, 30, 120, 600]);
    }

    public function handle(OutboundMessageService $sender): void
    {
        /** @var CampaignRecipient|null $recipient */
        $recipient = CampaignRecipient::query()->withoutGlobalScopes()->find($this->recipientId);
        if ($recipient === null) {
            return;
        }

        $this->bindTenant($recipient->organization_id);

        $campaign = Campaign::query()->find($recipient->campaign_id);

        if ($campaign === null || $campaign->status === CampaignStatus::Cancelled) {
            return;
        }

        if ($campaign->status !== CampaignStatus::Processing) {
            // Paused / scheduled again — hand the recipient back for a later slice.
            $this->resetToPending($recipient);

            return;
        }

        // Atomic claim: queued -> processing.
        $claimed = DB::table('campaign_recipients')
            ->where('id', $recipient->getKey())
            ->whereIn('status', [CampaignRecipientStatus::Queued->value, CampaignRecipientStatus::Pending->value])
            ->update([
                'status' => 'processing',
                'attempts' => DB::raw('attempts + 1'),
                'last_attempt_at' => now(),
                'updated_at' => now(),
            ]);

        if (! $claimed) {
            return; // another worker owns it, or it is already resolved
        }

        $phoneNumber = $campaign->phoneNumber()->first();
        if ($phoneNumber === null) {
            $this->markFailed($recipient, 'no_phone_number', 'Campaign sending number is missing.');

            return;
        }

        $template = $campaign->template()->first();
        if ($template === null || ! $template->isSendable()) {
            $this->markFailed($recipient, 'template_unavailable', 'Template is no longer available.');

            return;
        }

        $outbound = OutboundMessage::templateWithParams(
            new Recipient($recipient->phone_e164),
            $template->name,
            $template->language,
            array_values($recipient->rendered_variables ?? []),
            $this->headerParam($campaign),
        );

        try {
            $message = $sender->send($phoneNumber, $outbound, [
                'campaign_id' => $campaign->getKey(),
                'campaign_recipient_id' => $recipient->getKey(),
                'contact_id' => $recipient->contact_id,
                'template_id' => $template->getKey(),
                'idempotency_key' => 'sha1:'.sha1($campaign->organization_id.':'.$campaign->getKey().':'.$recipient->getKey()),
                'delay_seconds' => 0,
            ]);
        } catch (WhatsAppSendingDisabledException) {
            $this->resetToPending($recipient);
            $this->release(now()->addMinutes(5));

            return;
        } catch (RateLimitedException $e) {
            $this->resetToPending($recipient);
            $this->release($e->retryAfterSeconds);

            return;
        } catch (TransientSendException $e) {
            $recipient->forceFill(['status' => CampaignRecipientStatus::Queued->value])->save();
            throw $e;
        }

        // Webhook status updates (incl. the mock driver's inline ones) may have
        // already advanced this recipient past "sent" — only fill in what's
        // missing and never regress.
        $mapped = match ($message->status) {
            MessageStatus::Read => CampaignRecipientStatus::Read,
            MessageStatus::Delivered => CampaignRecipientStatus::Delivered,
            MessageStatus::Sent => CampaignRecipientStatus::Sent,
            MessageStatus::Skipped => CampaignRecipientStatus::OptedOut,
            MessageStatus::Failed => CampaignRecipientStatus::Failed,
            default => CampaignRecipientStatus::Sent,
        };

        $recipient->refresh();
        $rank = fn (CampaignRecipientStatus $s) => match ($s) {
            CampaignRecipientStatus::Sent => 1,
            CampaignRecipientStatus::Delivered => 2,
            CampaignRecipientStatus::Read => 3,
            default => 0,
        };

        $attrs = ['message_id' => $message->getKey()];

        if ($mapped === CampaignRecipientStatus::Failed || $mapped === CampaignRecipientStatus::OptedOut
            || $rank($mapped) > $rank($recipient->status)) {
            $attrs['status'] = $mapped->value;
        }

        if ($message->status === MessageStatus::Skipped) {
            $attrs['skip_reason'] = $message->error_code ?? 'skipped';
        }
        if ($message->error_code !== null) {
            $attrs['error_code'] = $message->error_code;
            $attrs['error_message'] = $message->error_message;
        }

        $recipient->forceFill($attrs)->save();
    }

    private function resetToPending(CampaignRecipient $recipient): void
    {
        DB::table('campaign_recipients')
            ->where('id', $recipient->getKey())
            ->whereNotIn('status', ['sent', 'delivered', 'read', 'failed', 'skipped', 'opted_out'])
            ->update(['status' => CampaignRecipientStatus::Pending->value, 'updated_at' => now()]);
    }

    private function markFailed(CampaignRecipient $recipient, string $code, string $message): void
    {
        $recipient->forceFill([
            'status' => CampaignRecipientStatus::Failed->value,
            'error_code' => $code,
            'error_message' => $message,
        ])->save();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function headerParam(Campaign $campaign): ?array
    {
        $media = $campaign->media()->first();
        if ($media === null) {
            return null;
        }

        $account = $campaign->phoneNumber()->first()?->businessAccount()->first();
        if (! $account instanceof WhatsappBusinessAccount) {
            return null;
        }

        // Uploads to Meta on first use / after the media id expires.
        $metaId = app(MediaLibrary::class)->ensureMetaId($media, $account);
        $type = $media->category() === 'video' ? 'video' : ($media->category() === 'image' ? 'image' : 'document');

        return ['type' => $type, $type => ['id' => $metaId]];
    }
}
