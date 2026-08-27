<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use App\Enums\ErrorCategory;
use App\Enums\MessageStatus;
use App\Jobs\EmitMockStatusWebhookJob;
use App\Models\Contact;
use App\Models\Message;
use App\Models\WhatsappPhoneNumber;
use App\Models\WhatsappTemplate;
use App\Services\WhatsApp\Data\OutboundMessage;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Creates and sends a single outbound WhatsApp message with idempotency.
 *
 * Flow:
 *   1. Insert the Message row (status=processing) keyed by a unique
 *      idempotency_key BEFORE calling Meta. A duplicate insert (queue retry,
 *      double dispatch) hits the unique constraint and we reconcile.
 *   2. Reserve a rate-limiter slot for the phone number.
 *   3. Call the driver.
 *   4. Record the outcome + append status events.
 */
final class OutboundMessageService
{
    public function __construct(
        private readonly WhatsAppManager $manager,
        private readonly WhatsAppRateLimiter $rateLimiter,
        private readonly MessageStatusUpdater $statusUpdater,
    ) {}

    /**
     * @param  array{campaign_id?:string, campaign_recipient_id?:string, contact_id?:string, template_id?:string, idempotency_key?:string, is_test?:bool, delay_seconds?:int}  $context
     */
    public function send(WhatsappPhoneNumber $phoneNumber, OutboundMessage $outbound, array $context = []): Message
    {
        $idempotencyKey = $context['idempotency_key'] ?? 'adhoc:'.Str::ulid()->toString();

        [$message, $isReplay] = $this->reserveMessage($phoneNumber, $outbound, $idempotencyKey, $context);

        if ($isReplay && $this->isResolved($message)) {
            return $message;
        }

        if ($this->blockedByOptOut($context)) {
            $message->forceFill([
                'status' => MessageStatus::Skipped,
                'error_code' => 'opted_out',
                'error_category' => 'opted_out',
                'error_message' => 'Recipient has opted out of marketing messages.',
            ])->save();

            $this->statusUpdater->apply($message, MessageStatus::Skipped, [
                'error_code' => 'opted_out',
                'error_message' => 'Recipient has opted out of marketing messages.',
            ]);

            return $message->refresh();
        }

        // Rate limiter — caller (job) should catch RateLimitedException and requeue.
        $wait = $this->rateLimiter->reserve($phoneNumber->phone_number_id, (int) ($context['delay_seconds'] ?? 0));
        if ($wait > 0) {
            $message->status = MessageStatus::Queued;
            $message->save();
            throw new RateLimitedException($wait);
        }

        $this->statusUpdater->apply($message, MessageStatus::Processing);

        $result = $this->manager->send($phoneNumber, $outbound);

        if ($result->accepted) {
            $message->wamid = $result->wamid;
            $message->save();

            $this->statusUpdater->apply($message, MessageStatus::Sent, ['wamid' => $result->wamid]);
            $this->rateLimiter->reward($phoneNumber->phone_number_id);

            $this->maybeEmitMockWebhook($message);

            return $message;
        }

        $error = $result->error;

        if ($error !== null && $error->category === ErrorCategory::RateLimited) {
            $this->rateLimiter->penalize($phoneNumber->phone_number_id, $error->retryAfterSeconds);
            $message->status = MessageStatus::Queued;
            $message->save();
            throw new RateLimitedException($error->retryAfterSeconds ?? 60);
        }

        if ($error !== null && $error->isRetryable()) {
            $message->status = MessageStatus::Queued;
            $message->save();
            throw new TransientSendException($error->adminMessage);
        }

        // Permanent failure — terminal.
        $this->statusUpdater->apply($message, MessageStatus::Failed, [
            'error_code' => $error?->code,
            'error_category' => $error?->category->value,
            'error_message' => $error?->userMessage,
        ]);

        return $message->refresh();
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array{0: Message, 1: bool} [message, isReplay]
     */
    private function reserveMessage(WhatsappPhoneNumber $phoneNumber, OutboundMessage $outbound, string $key, array $context): array
    {
        $attributes = [
            'organization_id' => $phoneNumber->organization_id,
            'whatsapp_phone_number_id' => $phoneNumber->getKey(),
            'campaign_id' => $context['campaign_id'] ?? null,
            'campaign_recipient_id' => $context['campaign_recipient_id'] ?? null,
            'contact_id' => $context['contact_id'] ?? null,
            'template_id' => $context['template_id'] ?? null,
            'direction' => 'outbound',
            'to_phone' => $outbound->to->e164,
            'to_phone_hash' => hash('sha256', $outbound->to->e164),
            'type' => $outbound->type,
            'payload' => $outbound->toGraphPayload(),
            'idempotency_key' => $key,
            'status' => MessageStatus::Processing,
            'created_by' => Auth::id(),
        ];

        try {
            // Trusted server-side construction — bypass the mass-assignment guard
            // (which protects controller input, not the service layer).
            /** @var Message $message */
            $message = Message::query()->withoutGlobalScopes()->forceCreate($attributes);

            return [$message, false];
        } catch (QueryException $e) {
            if (! $this->isUniqueViolation($e)) {
                throw $e;
            }

            /** @var Message $existing */
            $existing = Message::query()->withoutGlobalScopes()->where('idempotency_key', $key)->firstOrFail();

            return [$existing, true];
        }
    }

    /**
     * MARKETING templates may not be sent to a contact who has opted out.
     * UTILITY / AUTHENTICATION templates and free-text service replies are exempt.
     *
     * @param  array<string, mixed>  $context
     */
    private function blockedByOptOut(array $context): bool
    {
        $contactId = $context['contact_id'] ?? null;
        $templateId = $context['template_id'] ?? null;

        if ($contactId === null || $templateId === null) {
            return false;
        }

        $template = WhatsappTemplate::query()->withoutGlobalScopes()->find($templateId);
        if ($template === null || strtoupper((string) $template->category) !== 'MARKETING') {
            return false;
        }

        $contact = Contact::query()->withoutGlobalScopes()->find($contactId);

        return $contact !== null && $contact->isOptedOut();
    }

    private function isResolved(Message $message): bool
    {
        return $message->isSuccessful()
            || $message->status === MessageStatus::Failed
            || $message->status === MessageStatus::Cancelled;
    }

    private function maybeEmitMockWebhook(Message $message): void
    {
        if (config('services.whatsapp.driver') !== 'mock') {
            return;
        }
        if (! config('services.whatsapp.mock.emit_status_webhooks', true)) {
            return;
        }
        if ($message->wamid === null) {
            return;
        }

        // Mock driver + dev only: run inline so the delivered/read progression is
        // visible without a queue worker. The real driver relies on Meta webhooks.
        EmitMockStatusWebhookJob::dispatchSync($message->getKey());
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        $code = $e->errorInfo[1] ?? null;

        return in_array((int) $code, [19, 1062, 2067, 23000], true)
            || str_contains(strtolower($e->getMessage()), 'unique');
    }
}
