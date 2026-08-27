<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Message;
use App\Models\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Dev/test only. Simulates Meta delivering `delivered` then `read` status
 * webhooks for a message the mock driver accepted, feeding them through the
 * real webhook processing pipeline (so idempotency, status ordering, and the
 * message viewer are all exercised offline).
 */
class EmitMockStatusWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $messageId) {}

    public function handle(): void
    {
        if (config('services.whatsapp.driver') !== 'mock') {
            return;
        }

        /** @var Message|null $message */
        $message = Message::query()->withoutGlobalScopes()->find($this->messageId);

        if ($message === null || $message->wamid === null) {
            return;
        }

        $phoneNumber = $message->phoneNumber()->first();
        $phoneNumberId = $phoneNumber !== null ? $phoneNumber->phone_number_id : 'mock-phone';

        foreach (['delivered', 'read'] as $i => $status) {
            $payload = [
                'object' => 'whatsapp_business_account',
                'entry' => [[
                    'id' => 'mock-waba',
                    'changes' => [[
                        'field' => 'messages',
                        'value' => [
                            'messaging_product' => 'whatsapp',
                            'metadata' => ['phone_number_id' => $phoneNumberId],
                            'statuses' => [[
                                'id' => $message->wamid,
                                'status' => $status,
                                'timestamp' => (string) now()->addSeconds($i + 1)->timestamp,
                                'recipient_id' => $message->to_phone,
                            ]],
                        ],
                    ]],
                ]],
            ];

            $raw = json_encode($payload, JSON_THROW_ON_ERROR);

            $event = WebhookEvent::query()->create([
                'source' => 'mock',
                'event_fingerprint' => hash('sha256', $raw),
                'signature_valid' => true,
                'payload' => $payload,
                'headers' => [],
                'status' => 'received',
                'received_at' => now(),
            ]);

            ProcessWhatsAppWebhookJob::dispatchSync($event->getKey());
        }
    }
}
