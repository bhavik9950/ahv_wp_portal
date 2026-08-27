<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\WhatsappPhoneNumber;
use App\Services\WhatsApp\Data\OutboundMessage;
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

/**
 * Sends one outbound message. Safe under retry/duplication: OutboundMessageService
 * keys the Message row on an idempotency key inserted before the API call.
 */
class SendWhatsAppMessageJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $maxExceptions = 5;

    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public string $phoneNumberId,
        public array $serializedMessage,
        public array $context = [],
    ) {}

    public function uniqueId(): string
    {
        return $this->context['idempotency_key'] ?? $this->phoneNumberId.':'.md5(serialize($this->serializedMessage));
    }

    /** @return list<int> */
    public function backoff(): array
    {
        return config('services.whatsapp.retry_backoff', [5, 30, 120, 600]);
    }

    public function handle(OutboundMessageService $service): void
    {
        /** @var WhatsappPhoneNumber|null $number */
        $number = WhatsappPhoneNumber::query()->withoutGlobalScopes()->find($this->phoneNumberId);

        if ($number === null) {
            $this->fail(new \RuntimeException('Phone number no longer exists.'));

            return;
        }

        try {
            $service->send($number, OutboundMessage::fromArray($this->serializedMessage), $this->context);
        } catch (WhatsAppSendingDisabledException $e) {
            // Kill switch is on — hold the job, retry later without burning attempts.
            $this->release(now()->addMinutes(5));
        } catch (RateLimitedException $e) {
            $this->release($e->retryAfterSeconds);
        } catch (TransientSendException $e) {
            throw $e; // let Laravel retry with backoff()
        }
    }
}
