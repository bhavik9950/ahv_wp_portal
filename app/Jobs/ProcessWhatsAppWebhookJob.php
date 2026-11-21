<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Enums\TemplateStatus;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Organization;
use App\Models\WebhookEvent;
use App\Models\WhatsappPhoneNumber;
use App\Models\WhatsappTemplate;
use App\Services\Contacts\OptInService;
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
                $entryWabaId = $entry['id'] ?? null;
                if (filled($entryWabaId)) {
                    $waba = \App\Models\WhatsappBusinessAccount::query()->withoutGlobalScopes()
                        ->where('waba_id', (string) $entryWabaId)
                        ->first();
                    $organizationId ??= $waba?->organization_id;
                }

                foreach (data_get($entry, 'changes', []) as $change) {
                    $value = $change['value'] ?? [];
                    $field = $change['field'] ?? null;

                    $phoneNumberId = data_get($value, 'metadata.phone_number_id');
                    if (filled($phoneNumberId)) {
                        $phone = WhatsappPhoneNumber::query()->withoutGlobalScopes()
                            ->where('phone_number_id', (string) $phoneNumberId)
                            ->first();
                        $organizationId ??= $phone?->organization_id;
                    }

                    $displayNumber = data_get($value, 'metadata.display_phone_number');
                    if (! isset($phone) && filled($displayNumber)) {
                        $cleanDisplay = preg_replace('/\D+/', '', (string) $displayNumber);
                        $phone = WhatsappPhoneNumber::query()->withoutGlobalScopes()
                            ->where(function ($q) use ($cleanDisplay, $displayNumber) {
                                $q->where('display_phone_number', (string) $displayNumber)
                                  ->orWhereRaw("REPLACE(REPLACE(REPLACE(display_phone_number, '+', ''), ' ', ''), '-', '') = ?", [$cleanDisplay]);
                            })->first();
                        $organizationId ??= $phone?->organization_id;
                    }

                    $statusOrgId = $this->handleStatuses($value, $event, $statuses, $organizationId);
                    $inboundOrgId = $this->handleInboundMessages($value, $event, $organizationId);
                    $organizationId = $organizationId ?? $statusOrgId ?? $inboundOrgId;

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
    private function handleStatuses(array $value, WebhookEvent $event, MessageStatusUpdater $statuses, ?int $resolvedOrgId = null): ?int
    {
        $organizationId = $resolvedOrgId ?? $event->organization_id;

        foreach ($value['statuses'] ?? [] as $status) {
            $wamid = $status['id'] ?? null;
            if ($wamid === null) {
                continue;
            }

            /** @var Message|null $message */
            $message = Message::query()->withoutGlobalScopes()->where('wamid', $wamid)->first();

            if ($message === null && ! empty($status['recipient_id'])) {
                $recipientPhone = ltrim((string) $status['recipient_id'], '+');
                $message = Message::query()->withoutGlobalScopes()
                    ->where(function ($q) use ($recipientPhone) {
                        $q->where('to_phone', $recipientPhone)
                            ->orWhere('to_phone', '+'.$recipientPhone);
                    })
                    ->where('direction', 'outbound')
                    ->latest()
                    ->first();

                if ($message && $message->wamid === null) {
                    $message->wamid = $wamid;
                    $message->save();
                }
            }

            if ($message === null) {
                continue;
            }

            $organizationId ??= $message->organization_id;

            if ($message->organization_id) {
                $org = Organization::query()->withoutGlobalScopes()->find($message->organization_id);
                if ($org) {
                    app(TenantContext::class)->set($org);
                }
            }

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
    private function handleInboundMessages(array $value, WebhookEvent $event, ?int $resolvedOrgId = null): ?int
    {
        $organizationId = $resolvedOrgId ?? $event->organization_id;

        foreach ($value['messages'] ?? [] as $incoming) {
            $phoneNumberId = data_get($value, 'metadata.phone_number_id');
            $localNumber = null;
            if (filled($phoneNumberId)) {
                $localNumber = WhatsappPhoneNumber::query()->withoutGlobalScopes()
                    ->where('phone_number_id', (string) $phoneNumberId)->first();
            }

            $organizationId ??= $localNumber?->organization_id;
            $from = (string) ($incoming['from'] ?? '');
            $wamid = $incoming['id'] ?? null;

            // Fallback organization resolution: match customer phone number against outbound messages or contacts
            if (! $organizationId && filled($from)) {
                $cleanFrom = ltrim($from, '+');
                $matchedMsg = Message::query()->withoutGlobalScopes()
                    ->where(function ($q) use ($cleanFrom) {
                        $q->where('to_phone', $cleanFrom)
                            ->orWhere('to_phone', '+'.$cleanFrom);
                    })
                    ->latest()
                    ->first();
                $organizationId = $matchedMsg?->organization_id;

                if (! $organizationId) {
                    $matchedContact = Contact::query()->withoutGlobalScopes()
                        ->where(function ($q) use ($cleanFrom) {
                            $q->where('phone_e164', '+'.$cleanFrom)
                                ->orWhere('phone_e164', $cleanFrom);
                        })
                        ->first();
                    $organizationId = $matchedContact?->organization_id;
                }
            }

            if (! $localNumber && $organizationId) {
                $localNumber = WhatsappPhoneNumber::query()->withoutGlobalScopes()
                    ->where('organization_id', $organizationId)
                    ->orderByDesc('is_default')
                    ->first();
            }

            if (! $organizationId) {
                continue;
            }

            $exists = Message::query()->withoutGlobalScopes()
                ->where('wamid', $wamid)
                ->where('direction', 'inbound')
                ->exists();

            if ($exists) {
                continue;
            }

            $cleanFrom = ltrim($from, '+');
            $contact = Contact::query()->withoutGlobalScopes()
                ->where('organization_id', $organizationId)
                ->where(function ($q) use ($cleanFrom) {
                    $q->where('phone_e164', '+'.$cleanFrom)
                        ->orWhere('phone_e164', $cleanFrom);
                })
                ->first();

            $rawType = (string) ($incoming['type'] ?? 'text');
            $messageType = MessageType::tryFrom($rawType) ?? MessageType::Text;

            $message = (new Message)->forceFill([
                'organization_id' => $organizationId,
                'whatsapp_phone_number_id' => $localNumber?->getKey(),
                'contact_id' => $contact?->getKey(),
                'wamid' => $wamid,
                'direction' => 'inbound',
                'to_phone' => $from,
                'to_phone_hash' => hash('sha256', $from),
                'type' => $messageType,
                'payload' => $incoming,
                'idempotency_key' => 'inbound:'.($wamid ?? Str::uuid()->toString()),
                'status' => MessageStatus::Delivered->value,
                'created_at' => $event->received_at ?? now(),
                'updated_at' => $event->received_at ?? now(),
            ]);
            $message->save();

            if ($message->hasDownloadableMedia()) {
                DownloadInboundMediaJob::dispatch($message->getKey())->onQueue('whatsapp-media');
            }

            if (config('services.bot.enabled')) {
                HandleInboundMessageJob::dispatch($message->getKey())->onQueue('bot');
            }

            $this->maybeHandleStopKeyword($incoming, $from, $organizationId);
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
            Organization::query()->find($organizationId)
        );

        app(OptInService::class)->optOutByPhone($from, [
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
