<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Enums\TemplateStatus;
use App\Models\Message;
use App\Models\WebhookEvent;
use App\Models\WhatsappPhoneNumber;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\MessageStatusUpdater;
use App\Support\TenantContext;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

/**
 * Parses one stored Meta webhook event and applies it to domain tables.
 * Idempotent: a re-run over the same WebhookEvent is a no-op once processed,
 * and status events de-dupe on (message, status, occurred_at).
 */
class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    /** @return list<int> */
    public function backoff(): array
    {
        return [10, 30, 120, 300];
    }

    public function __construct(public string $webhookEventId) {}

    public function handle(MessageStatusUpdater $statuses): void
    {
        /** @var WebhookEvent|null $event */
        $event = WebhookEvent::query()->find($this->webhookEventId);

        if ($event === null || $event->status === 'processed') {
            return;
        }

        $event->update(['status' => 'processing']);

        try {
            $organizationId = null;

            foreach (data_get($event->payload, 'entry', []) as $entry) {
                foreach (data_get($entry, 'changes', []) as $change) {
                    $value = $change['value'] ?? [];
                    $field = $change['field'] ?? null;

                    $organizationId ??= $this->handleStatuses($value, $event, $statuses);
                    $organizationId ??= $this->handleInboundMessages($value, $event);

                    if ($field === 'message_template_status_update') {
                        $organizationId ??= $this->handleTemplateUpdate($value);
                    }
                }
            }

            $event->update([
                'status' => 'processed',
                'processed_at' => now(),
                'organization_id' => $organizationId ?? $event->organization_id,
            ]);
        } catch (Throwable $e) {
            $event->update(['status' => 'failed', 'error' => mb_substr($e->getMessage(), 0, 1000)]);
            throw $e;
        }
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function handleStatuses(array $value, WebhookEvent $event, MessageStatusUpdater $statuses): ?int
    {
        $organizationId = null;

        foreach ($value['statuses'] ?? [] as $status) {
            $wamid = $status['id'] ?? null;
            if ($wamid === null) {
                continue;
            }

            /** @var Message|null $message */
            $message = Message::query()->withoutGlobalScopes()->where('wamid', $wamid)->first();
            if ($message === null) {
                continue;
            }

            $organizationId ??= $message->organization_id;

            $mapped = match ($status['status'] ?? '') {
                'sent' => MessageStatus::Sent,
                'delivered' => MessageStatus::Delivered,
                'read' => MessageStatus::Read,
                'failed' => MessageStatus::Failed,
                default => null,
            };

            if ($mapped === null) {
                continue;
            }

            $error = $status['errors'][0] ?? null;

            $statuses->apply($message, $mapped, [
                'wamid' => $wamid,
                'occurred_at' => $status['timestamp'] ?? null,
                'webhook_event_id' => $event->getKey(),
                'error_code' => $error['code'] ?? null,
                'error_title' => $error['title'] ?? null,
                'error_message' => $error['message'] ?? ($error['error_data']['details'] ?? null),
            ]);
        }

        return $organizationId;
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function handleInboundMessages(array $value, WebhookEvent $event): ?int
    {
        $organizationId = null;

        foreach ($value['messages'] ?? [] as $incoming) {
            $phoneNumberId = data_get($value, 'metadata.phone_number_id');
            $localNumber = WhatsappPhoneNumber::query()->withoutGlobalScopes()
                ->where('phone_number_id', $phoneNumberId)->first();

            if ($localNumber === null) {
                continue;
            }

            $organizationId ??= $localNumber->organization_id;
            $from = (string) ($incoming['from'] ?? '');
            $wamid = $incoming['id'] ?? null;

            $exists = Message::query()->withoutGlobalScopes()
                ->where('wamid', $wamid)
                ->where('direction', 'inbound')
                ->exists();

            if ($exists) {
                continue;
            }

            (new Message)->forceFill([
                'organization_id' => $localNumber->organization_id,
                'whatsapp_phone_number_id' => $localNumber->getKey(),
                'wamid' => $wamid,
                'direction' => 'inbound',
                'to_phone' => $from,
                'to_phone_hash' => hash('sha256', $from),
                'type' => $incoming['type'] ?? 'text',
                'payload' => $incoming,
                'idempotency_key' => 'inbound:'.($wamid ?? Str::uuid()->toString()),
                'status' => MessageStatus::Delivered->value,
            ])->save();

            $this->maybeHandleStopKeyword($incoming, $from, $localNumber->organization_id);
        }

        return $organizationId;
    }

    /**
     * A customer replying "STOP" / "UNSUBSCRIBE" (etc.) opts them out of
     * marketing immediately.
     *
     * @param  array<string, mixed>  $incoming
     */
    private function maybeHandleStopKeyword(array $incoming, string $from, int $organizationId): void
    {
        $body = strtoupper(trim((string) data_get($incoming, 'text.body', '')));

        if (! in_array($body, ['STOP', 'UNSUBSCRIBE', 'CANCEL', 'OPTOUT', 'OPT OUT', 'STOP PROMOTIONS'], true)) {
            return;
        }

        app(TenantContext::class)->set(
            \App\Models\Organization::query()->find($organizationId)
        );

        app(\App\Services\Contacts\OptInService::class)->optOutByPhone($from, [
            'source' => 'inbound_keyword',
            'reference' => $incoming['id'] ?? null,
            'note' => "Customer replied: {$body}",
        ]);
    }

    /**
     * @param  array<string, mixed>  $value
     */
    private function handleTemplateUpdate(array $value): ?int
    {
        $name = $value['message_template_name'] ?? null;
        if ($name === null) {
            return null;
        }

        $template = WhatsappTemplate::query()->withoutGlobalScopes()
            ->where('name', $name)
            ->when(isset($value['message_template_language']), fn ($q) => $q->where('language', $value['message_template_language']))
            ->first();

        if ($template === null) {
            return null;
        }

        $template->forceFill([
            'status' => TemplateStatus::fromMeta($value['event'] ?? $value['new_certificate'] ?? null)->value,
            'rejection_reason' => $value['reason'] ?? $template->rejection_reason,
            'last_synced_at' => now(),
        ])->save();

        return $template->organization_id;
    }
}
