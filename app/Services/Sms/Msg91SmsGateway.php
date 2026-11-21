<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class Msg91SmsGateway
{
    private ?string $authKey;

    public function __construct()
    {
        $this->authKey = config('services.msg91.auth_key');
    }

    /**
     * Send single or bulk SMS via MSG91 Flow API.
     *
     * @param  array<string, mixed>  $variables
     * @return array{success: bool, message_id: ?string, error: ?string}
     */
    public function send(string $toPhone, string $senderId, string $dltTemplateId, string $messageBody, array $variables = []): array
    {
        $cleanPhone = preg_replace('/[^0-9]/', '', $toPhone);
        if (strlen($cleanPhone) === 10) {
            $cleanPhone = '91' . $cleanPhone;
        }

        // If no auth key is configured yet, simulate successful sandbox send
        if (blank($this->authKey) || $this->authKey === 'dummy_key') {
            Log::info("MSG91 Sandbox Send to {$cleanPhone}: [{$senderId}] {$messageBody}");

            return [
                'success' => true,
                'message_id' => 'sandbox_' . bin2hex(random_bytes(8)),
                'error' => null,
            ];
        }

        try {
            $payload = [
                'template_id' => $dltTemplateId,
                'short_url' => 0,
                'recipients' => [
                    array_merge([
                        'mobiles' => $cleanPhone,
                        'message' => $messageBody,
                    ], $variables),
                ],
            ];

            if (filled($senderId)) {
                $payload['sender'] = $senderId;
            }

            $response = Http::withHeaders([
                'authkey' => $this->authKey,
                'Content-Type' => 'application/json',
                'accept' => 'application/json',
            ])->timeout(10)->post('https://control.msg91.com/api/v5/flow/', $payload);

            $data = $response->json() ?? [];

            if ($response->successful() && (($data['type'] ?? '') === 'success' || isset($data['request_id']))) {
                return [
                    'success' => true,
                    'message_id' => $data['message'] ?? $data['request_id'] ?? null,
                    'error' => null,
                ];
            }

            $errorMsg = $data['message'] ?? $response->body() ?? 'Failed to send SMS';
            Log::error("MSG91 SMS Error: {$errorMsg}", ['payload' => $payload, 'response' => $data]);

            return [
                'success' => false,
                'message_id' => null,
                'error' => is_string($errorMsg) ? $errorMsg : json_encode($errorMsg),
            ];
        } catch (\Throwable $e) {
            Log::error("MSG91 Connection Exception: " . $e->getMessage());

            return [
                'success' => false,
                'message_id' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
