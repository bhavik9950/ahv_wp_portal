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
use Illuminate\Support\Facades\Log;
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

    /**
     * Discover the WhatsApp Business Account id(s) the access token can manage,
     * via debug_token's granular scopes. Used to auto-configure from just a
     * token + phone number id.
     *
     * @return list<string>
     */
    public function discoverWabaIds(WabaCredentials $creds): array
    {
        $response = $this->http
            ->baseUrl($creds->graphBase())
            ->acceptJson()
            ->timeout(20)
            ->get('/debug_token', ['input_token' => $creds->accessToken, 'access_token' => $creds->accessToken]);

        if ($response->failed()) {
            return [];
        }

        $ids = [];
        foreach ((array) $response->json('data.granular_scopes', []) as $scope) {
            if (in_array($scope['scope'] ?? '', ['whatsapp_business_management', 'whatsapp_business_messaging'], true)) {
                foreach ((array) ($scope['target_ids'] ?? []) as $id) {
                    $ids[] = (string) $id;
                }
            }
        }

        return array_values(array_unique($ids));
    }

    public function uploadMedia(WabaCredentials $creds, string $contents, string $mimeType, string $filename): string
    {
        if (blank($creds->phoneNumberId)) {
            throw new RuntimeException('Cannot upload media: no phone number id for this WABA.');
        }

        $response = $this->client($creds)
            ->attach('file', $contents, $filename, ['Content-Type' => $mimeType])
            ->post("/{$creds->phoneNumberId}/media", ['messaging_product' => 'whatsapp', 'type' => $mimeType]);

        $this->throwUnlessOk($response);

        $id = $response->json('id');
        if (! is_string($id) || $id === '') {
            throw new RuntimeException('Meta media upload returned no id.');
        }

        return $id;
    }

    public function uploadTemplateSample(WabaCredentials $creds, string $appId, string $contents, string $mimeType, string $filename): string
    {
        if ($appId === '') {
            throw new RuntimeException('Cannot upload a template sample: no Meta App ID is configured for this WABA.');
        }

        // 1. Start a resumable upload session (params go on the query string).
        $start = $this->http
            ->baseUrl($creds->graphBase())
            ->withToken($creds->accessToken, 'OAuth')
            ->acceptJson()
            ->timeout(30)
            ->post("/{$appId}/uploads?".http_build_query([
                'file_name' => $filename,
                'file_length' => strlen($contents),
                'file_type' => $mimeType,
            ]));
        $this->throwUnlessOk($start);

        $sessionId = $start->json('id');
        if (! is_string($sessionId) || $sessionId === '') {
            throw new RuntimeException('Meta did not return an upload session id.');
        }

        // 2. Upload the bytes; the response carries the handle in "h".
        $upload = $this->http
            ->baseUrl($creds->graphBase())
            ->withToken($creds->accessToken, 'OAuth')
            ->withHeaders(['file_offset' => '0'])
            ->withBody($contents, $mimeType)
            ->timeout(60)
            ->post("/{$sessionId}");
        $this->throwUnlessOk($upload);

        $handle = $upload->json('h');
        if (! is_string($handle) || $handle === '') {
            throw new RuntimeException('Meta upload did not return a file handle.');
        }

        return $handle;
    }

    public function downloadMedia(WabaCredentials $creds, string $mediaId): array
    {
        $meta = $this->client($creds)->get("/{$mediaId}");
        $this->throwUnlessOk($meta);

        $url = $meta->json('url');
        if (! is_string($url) || $url === '') {
            throw new RuntimeException('Meta did not return a media URL.');
        }

        $maxBytes = (int) config('services.whatsapp.http.max_download_bytes', 20_971_520);
        $declaredSize = (int) $meta->json('file_size', 0);
        if ($declaredSize > 0 && $declaredSize > $maxBytes) {
            throw new RuntimeException('Media file is larger than this app will download.');
        }

        // Meta's CDN URL is short-lived and requires the same bearer token —
        // it comes from Meta's own API response, not user input.
        $file = $this->client($creds)->get($url);
        $this->throwUnlessOk($file);

        $contents = $file->body();
        if (strlen($contents) > $maxBytes) {
            throw new RuntimeException('Media file is larger than this app will download.');
        }

        return [
            'contents' => $contents,
            'mime_type' => (string) ($meta->json('mime_type') ?? $file->header('Content-Type') ?? 'application/octet-stream'),
            'sha256' => is_string($meta->json('sha256')) ? $meta->json('sha256') : null,
        ];
    }

    public function getPhoneNumber(WabaCredentials $creds, string $phoneNumberId): array
    {
        $response = $this->client($creds)->get("/{$phoneNumberId}", [
            'fields' => 'id,display_phone_number,verified_name,quality_rating,messaging_limit_tier,code_verification_status',
        ]);
        $this->throwUnlessOk($response);

        return $response->json() ?? [];
    }

    public function listPhoneNumbers(WabaCredentials $creds): array
    {
        $response = $this->client($creds)->get("/{$creds->wabaId}/phone_numbers", [
            'fields' => 'id,display_phone_number,verified_name,quality_rating,messaging_limit_tier',
        ]);
        $this->throwUnlessOk($response);

        return $response->json('data') ?? [];
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

            Log::channel((string) config('services.whatsapp.log_channel'))
                ->warning('Meta API error', [
                    'status' => $response->status(),
                    'error' => data_get($response->json(), 'error'),
                ]);

            throw new RuntimeException($err->adminMessage);
        }
    }

    private function short(Throwable $e): string
    {
        return str($e->getMessage())->limit(180)->value();
    }
}
