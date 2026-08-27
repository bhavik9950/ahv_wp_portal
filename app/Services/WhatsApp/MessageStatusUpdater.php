<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\CampaignRecipientStatus;
use App\Enums\MessageStatus;
use App\Models\CampaignRecipient;
use App\Models\Message;
use App\Models\MessageStatusEvent;
use Illuminate\Support\Carbon;

/**
 * Applies a status transition to a Message and records it in the append-only
 * message_status_events log. Transitions are forward-only: a late "sent" after
 * "read" is ignored; "failed" is terminal.
 */
final class MessageStatusUpdater
{
    /**
     * @param  array{error_code?:string|null, error_title?:string|null, error_message?:string|null, occurred_at?:\DateTimeInterface|string|int|null, webhook_event_id?:string|null, wamid?:string|null}  $meta
     */
    public function apply(Message $message, MessageStatus $status, array $meta = []): void
    {
        $occurredAt = $this->parseTimestamp($meta['occurred_at'] ?? null);

        $this->recordEvent($message, $status, $occurredAt, $meta);

        if (! $message->status->canTransitionTo($status)) {
            return;
        }

        $message->status = $status;

        match ($status) {
            MessageStatus::Sent => $message->sent_at ??= $occurredAt,
            MessageStatus::Delivered => $message->delivered_at ??= $occurredAt,
            MessageStatus::Read => $message->read_at ??= $occurredAt,
            MessageStatus::Failed => $this->markFailed($message, $occurredAt, $meta),
            default => null,
        };

        $message->save();

        $this->mirrorToCampaignRecipient($message, $status, $meta);
    }

    /**
     * Keep the campaign report live: reflect delivery status onto the recipient
     * row (forward-only). Sent/failed are handled by the send job; here we add
     * delivered / read / late failures from webhooks.
     *
     * @param  array<string, mixed>  $meta
     */
    private function mirrorToCampaignRecipient(Message $message, MessageStatus $status, array $meta): void
    {
        if ($message->campaign_recipient_id === null) {
            return;
        }

        $target = match ($status) {
            MessageStatus::Delivered => CampaignRecipientStatus::Delivered,
            MessageStatus::Read => CampaignRecipientStatus::Read,
            MessageStatus::Failed => CampaignRecipientStatus::Failed,
            default => null,
        };

        if ($target === null) {
            return;
        }

        /** @var CampaignRecipient|null $recipient */
        $recipient = CampaignRecipient::query()->withoutGlobalScopes()->find($message->campaign_recipient_id);
        if ($recipient === null) {
            return;
        }

        // Never regress a read recipient back to delivered.
        $rank = fn (CampaignRecipientStatus $s) => match ($s) {
            CampaignRecipientStatus::Sent => 1,
            CampaignRecipientStatus::Delivered => 2,
            CampaignRecipientStatus::Read => 3,
            default => 0,
        };

        if ($target === CampaignRecipientStatus::Failed || $rank($target) > $rank($recipient->status)) {
            $recipient->forceFill([
                'status' => $target->value,
                'error_code' => $meta['error_code'] ?? $recipient->error_code,
                'error_message' => $meta['error_message'] ?? $recipient->error_message,
            ])->save();
        }
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function recordEvent(Message $message, MessageStatus $status, Carbon $occurredAt, array $meta): void
    {
        MessageStatusEvent::query()->firstOrCreate(
            [
                'message_id' => $message->getKey(),
                'status' => $status->value,
                'occurred_at' => $occurredAt,
            ],
            [
                'organization_id' => $message->organization_id,
                'wamid' => $meta['wamid'] ?? $message->wamid,
                'error_code' => $meta['error_code'] ?? null,
                'error_title' => $meta['error_title'] ?? null,
                'error_message' => $meta['error_message'] ?? null,
                'webhook_event_id' => $meta['webhook_event_id'] ?? null,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function markFailed(Message $message, Carbon $occurredAt, array $meta): void
    {
        $message->failed_at ??= $occurredAt;
        $message->error_code = $meta['error_code'] ?? $message->error_code;
        $message->error_message = $meta['error_message'] ?? $message->error_message;
        $message->error_category = $meta['error_category'] ?? $message->error_category;
    }

    private function parseTimestamp(mixed $value): Carbon
    {
        if ($value === null) {
            return now();
        }

        if (is_int($value) || ctype_digit((string) $value)) {
            return Carbon::createFromTimestamp((int) $value);
        }

        return Carbon::parse($value);
    }
}
