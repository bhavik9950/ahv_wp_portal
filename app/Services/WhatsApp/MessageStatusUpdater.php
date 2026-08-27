<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\MessageStatus;
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
