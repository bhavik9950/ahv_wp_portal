<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Drivers;

use App\Services\WhatsApp\Contracts\WhatsAppDriver;
use App\Services\WhatsApp\Data\ConnectionCheck;
use App\Services\WhatsApp\Data\OutboundMessage;
use App\Services\WhatsApp\Data\SendResult;
use App\Services\WhatsApp\Data\WabaCredentials;
use App\Services\WhatsApp\MetaErrorMapper;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use RuntimeException;
use Throwable;

/**
 * Real Meta WhatsApp Cloud API integration. Only documented Graph endpoints.
 */
final class MetaCloudApiDriver implements WhatsAppDriver
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly MetaErrorMapper $errors,
    ) {}

    public function name(): string
    {
        return 'meta_cloud_api';
    }

    public function send(WabaCredentials $creds, OutboundMessage $message): SendResult
    {
        if (blank($creds->phoneNumberId)) {
            throw new RuntimeException('Cannot send: no phone number id resolved for this WABA.');
        }

        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $message->to->e164,
        ] + $message->toGraphPayload();

        try {
            $response = $this->client($creds)
                ->post("/{$creds->phoneNumberId}/messages", $payload);
        } catch (Throwable $e) {
            return SendResult::failure($this->errors->fromTransportException($e));
        }

        if ($response->failed()) {
            return SendResult::failure(
                $this->errors->fromHttp($response->status(), $response->json() ?? [], $response->headers()),
                $response->json() ?? [],
            );
        }

        $wamid = data_get($response->json(), 'messages.0.id');

        return SendResult::accepted(is_string($wamid) ? $wamid : null, $response->json() ?? []);
    }

    public function fetchTemplates(WabaCredentials $creds): array
    {
        $templates = [];
        $url = "/{$creds->wabaId}/message_templates?limit=100";

        do {
            $response = $this->client($creds)->get($url);
            $this->throwUnlessOk($response);
            $json = $response->json();
            $templates = array_merge($templates, $json['data'] ?? []);
            $url = data_get($json, 'paging.next');
        } while (is_string($url) && $url !== '');

        return $templates;
    }

    public function createTemplate(WabaCredentials $creds, array $definition): array
    {
        $response = $this->client($creds)->post("/{$creds->wabaId}/message_templates", $definition);
        $this->throwUnlessOk($response);

        return $response->json() ?? [];
    }

    public function deleteTemplate(WabaCredentials $creds, string $name): void
    {
        $response = $this->client($creds)->delete("/{$creds->wabaId}/message_templates", ['name' => $name]);
        $this->throwUnlessOk($response);
    }

    public function getPhoneNumber(WabaCredentials $creds, string $phoneNumberId): array
    {
        $response = $this->client($creds)->get("/{$phoneNumberId}", [
            'fields' => 'id,display_phone_number,verified_name,quality_rating,messaging_limit_tier,code_verification_status',
        ]);
        $this->throwUnlessOk($response);

        return $response->json() ?? [];
    }

    public function runConnectionChecks(WabaCredentials $creds): array
    {
        $checks = [];

        // Test connection + phone number
        try {
            $phone = $creds->phoneNumberId
                ? $this->getPhoneNumber($creds, $creds->phoneNumberId)
                : null;

            $checks[] = ConnectionCheck::pass('connection', 'Test Connection', 'Graph API reachable');
            $checks[] = $phone
                ? ConnectionCheck::pass('phone_number', 'Validate Phone Number', $phone['display_phone_number'] ?? 'OK', [
                    'quality_rating' => $phone['quality_rating'] ?? null,
                    'messaging_limit_tier' => $phone['messaging_limit_tier'] ?? null,
                ])
                : ConnectionCheck::fail('phone_number', 'Validate Phone Number', 'No phone number id configured');
        } catch (Throwable $e) {
            $checks[] = ConnectionCheck::fail('connection', 'Test Connection', $this->short($e));
        }

        // Validate WABA
        try {
            $response = $this->client($creds)->get("/{$creds->wabaId}", ['fields' => 'id,name,timezone_id,message_template_namespace']);
            $checks[] = $response->ok()
                ? ConnectionCheck::pass('waba', 'Validate WABA', data_get($response->json(), 'name', 'OK'))
                : ConnectionCheck::fail('waba', 'Validate WABA', 'HTTP '.$response->status());
        } catch (Throwable $e) {
            $checks[] = ConnectionCheck::fail('waba', 'Validate WABA', $this->short($e));
        }

        // Permissions (template read)
        try {
            $response = $this->client($creds)->get("/{$creds->wabaId}/message_templates", ['limit' => 1]);
            $checks[] = $response->status() === 403
                ? ConnectionCheck::fail('permissions', 'Check API Permissions', 'Token lacks whatsapp_business_management')
                : ConnectionCheck::pass('permissions', 'Check API Permissions', 'Template read OK');
        } catch (Throwable $e) {
            $checks[] = ConnectionCheck::fail('permissions', 'Check API Permissions', $this->short($e));
        }

        // Token debug (best effort)
        try {
            $response = $this->client($creds)->get('/debug_token', ['input_token' => $creds->accessToken]);
            $data = $response->json('data') ?? [];
            $valid = (bool) ($data['is_valid'] ?? false);
            $expires = $data['expires_at'] ?? 0;
            $checks[] = $valid
                ? ConnectionCheck::pass('token', 'Check Token', $expires ? 'Valid, expires '.date('c', (int) $expires) : 'Valid', ['expires_at' => $expires])
                : ConnectionCheck::fail('token', 'Check Token', 'Token reported invalid');
        } catch (Throwable $e) {
            $checks[] = ConnectionCheck::fail('token', 'Check Token', 'Could not verify: '.$this->short($e));
        }

        // Webhook subscription
        try {
            $response = $this->client($creds)->get("/{$creds->wabaId}/subscribed_apps");
            $subscribed = ! empty($response->json('data'));
            $checks[] = $subscribed
                ? ConnectionCheck::pass('webhook', 'Check Webhook Configuration', 'App subscribed to WABA')
                : ConnectionCheck::fail('webhook', 'Check Webhook Configuration', 'No app subscribed to this WABA');
        } catch (Throwable $e) {
            $checks[] = ConnectionCheck::fail('webhook', 'Check Webhook Configuration', $this->short($e));
        }

        return $checks;
    }

    private function client(WabaCredentials $creds): PendingRequest
    {
        return $this->http
            ->baseUrl($creds->graphBase())
            ->withToken($creds->accessToken)
            ->acceptJson()
            ->connectTimeout((int) config('services.whatsapp.http.connect_timeout', 10))
            ->timeout((int) config('services.whatsapp.http.timeout', 30));
    }

    private function throwUnlessOk(Response $response): void
    {
        if ($response->failed()) {
            $err = $this->errors->fromHttp($response->status(), $response->json() ?? [], $response->headers());
            throw new RuntimeException($err->adminMessage);
        }
    }

    private function short(Throwable $e): string
    {
        return str($e->getMessage())->limit(180)->value();
    }
}
