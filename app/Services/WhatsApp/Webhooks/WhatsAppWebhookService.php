<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Webhooks;

use App\Jobs\ProcessWhatsAppWebhookJob;
use App\Models\WebhookEvent;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use Illuminate\Support\Facades\Log;

/**
 * Ingest side of the webhook pipeline: verify authenticity, persist the raw
 * event once (idempotent on a body fingerprint), and hand off to a queued job.
 * Must return quickly — no parsing or DB writes to domain tables here.
 */
final class WhatsAppWebhookService
{
    /**
     * @param  array<string, string>  $headers  header name => value (already lower-cased keys ok)
     * @return array{status: int, body: string}
     */
    public function ingest(string $rawBody, array $headers): array
    {
        $signatureHeader = $headers['x-hub-signature-256'] ?? null;
        $appSecrets = $this->resolveAppSecrets();

        $signatureValid = false;
        foreach ($appSecrets as $secret) {
            if (filled($secret) && WebhookSignature::isValid($rawBody, $signatureHeader, (string) $secret)) {
                $signatureValid = true;
                break;
            }
        }
        $requireSignature = (bool) config('services.whatsapp.webhook.require_signature', true);

        if (! $signatureValid && $requireSignature) {
            // Record the attempt for forensics but do not process it.
            $this->store($rawBody, $headers, signatureValid: false, status: 'ignored');
            Log::channel(config('services.whatsapp.log_channel'))->warning('Rejected WhatsApp webhook: bad signature');

            return ['status' => 403, 'body' => 'invalid signature'];
        }

        $fingerprint = hash('sha256', $rawBody);

        /** @var WebhookEvent|null $existing */
        $existing = WebhookEvent::query()->where('event_fingerprint', $fingerprint)->first();

        if ($existing !== null) {
            // Meta retried a delivery we already have — acknowledge, don't reprocess.
            return ['status' => 200, 'body' => 'duplicate'];
        }

        try {
            $event = $this->store($rawBody, $headers, signatureValid: true, status: 'received', fingerprint: $fingerprint);
        } catch (\Illuminate\Database\UniqueConstraintViolationException|\Illuminate\Database\QueryException $e) {
            // Handle concurrent webhook retries where another thread inserted the same fingerprint
            return ['status' => 200, 'body' => 'duplicate'];
        }

        ProcessWhatsAppWebhookJob::dispatch($event->getKey())->onQueue('whatsapp-webhook');

        return ['status' => 200, 'body' => 'ok'];
    }

    /**
     * @param  array<string, string>  $headers
     */
    private function store(string $rawBody, array $headers, bool $signatureValid, string $status, ?string $fingerprint = null): WebhookEvent
    {
        $payload = json_decode($rawBody, true);
        $organizationId = null;

        if (is_array($payload)) {
            foreach (data_get($payload, 'entry', []) as $entry) {
                $entryWabaId = $entry['id'] ?? null;
                if (filled($entryWabaId)) {
                    $organizationId = WhatsappBusinessAccount::query()->withoutGlobalScopes()
                        ->where('waba_id', (string) $entryWabaId)
                        ->value('organization_id');
                    if ($organizationId) {
                        break;
                    }
                }

                foreach (data_get($entry, 'changes', []) as $change) {
                    $pid = data_get($change, 'value.metadata.phone_number_id');
                    if (filled($pid)) {
                        $organizationId = WhatsappPhoneNumber::query()->withoutGlobalScopes()
                            ->where('phone_number_id', (string) $pid)
                            ->value('organization_id');
                        if ($organizationId) {
                            break 2;
                        }
                    }
                }
            }
        }

        return WebhookEvent::query()->create([
            'organization_id' => $organizationId,
            'source' => 'meta',
            'event_fingerprint' => $fingerprint ?? hash('sha256', $rawBody.'|'.microtime(true).random_bytes(4)),
            'signature_valid' => $signatureValid,
            'payload' => is_array($payload) ? $payload : ['_raw' => mb_substr($rawBody, 0, 5000)],
            'headers' => $this->safeHeaders($headers),
            'status' => $status,
            'received_at' => now(),
        ]);
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private function safeHeaders(array $headers): array
    {
        $drop = ['authorization', 'cookie', 'x-hub-signature', 'x-hub-signature-256'];

        return collect($headers)
            ->reject(fn ($value, $key) => in_array(strtolower((string) $key), $drop, true))
            ->all();
    }

    /**
     * @return list<string>
     */
    private function resolveAppSecrets(): array
    {
        $secrets = WhatsappBusinessAccount::query()->withoutGlobalScopes()
            ->whereNotNull('app_secret')
            ->get()
            ->pluck('app_secret')
            ->filter()
            ->values()
            ->all();

        $bootstrap = config('services.whatsapp.bootstrap.app_secret');
        if (filled($bootstrap)) {
            $secrets[] = (string) $bootstrap;
        }

        return array_values(array_unique(array_filter($secrets)));
    }
}
