<?php

declare(strict_types=1);

namespace App\Services\Voice\Drivers;

use App\Services\Voice\Contracts\VoiceBroadcastDriver;
use App\Services\Voice\Data\VoiceCredentials;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * SMS Gateway Center Voice API (unify.smsgateway.center/VoiceApi).
 *
 * All calls are form-data POSTs authenticated with userid + password. The
 * portal drives pacing itself (one `quick` send per number, staggered by the
 * campaign's delay) rather than using the provider's bulk/group modes, so it
 * keeps control of the rate and the per-number result.
 */
final class SmsGatewayCenterVoiceDriver implements VoiceBroadcastDriver
{
    public function __construct(private readonly HttpFactory $http) {}

    public function name(): string
    {
        return 'smsgatewaycenter';
    }

    public function uploadAudio(VoiceCredentials $creds, string $contents, string $filename, string $libraryName): string
    {
        $response = $this->client($creds)
            ->attach('audioTrack', $contents, $filename)
            ->post($creds->baseUrl.'/save', $this->auth($creds) + [
                'libraryName' => $libraryName,
                'output' => 'json',
            ]);

        $this->throwUnlessOk($response, 'audio upload');

        $json = (array) $response->json();
        $libraryId = $this->firstScalar($json, ['libraryId', 'data.libraryId', 'data.0.libraryId', 'id', 'data.id']);

        if ($libraryId === null) {
            $this->logRaw('voice audio upload: no library id in response', $response);
            throw new RuntimeException('The voice provider did not return a library id for the uploaded audio.');
        }

        return $libraryId;
    }

    public function placeCall(VoiceCredentials $creds, string $libraryId, string $phone): string
    {
        $response = $this->client($creds)->asForm()->post($creds->baseUrl.'/send', $this->auth($creds) + [
            'audioType' => 'library',
            'sendMethod' => 'quick',
            'duplicateCheck' => 'false',
            'reDial' => '0',
            'redialInterval' => '5',
            'output' => 'json',
            'libraryId' => $libraryId,
            'mobile' => $phone,
        ]);

        $this->throwUnlessOk($response, 'place call');

        $json = (array) $response->json();
        $txn = $this->firstScalar($json, [
            'transactionId', 'data.transactionId', 'data.0.transactionId',
            'uuid', 'data.uuid', 'messageId', 'data.messageId',
        ]);

        if ($txn === null) {
            $this->logRaw('voice place call: no transaction id in response', $response);
            throw new RuntimeException('The voice provider did not return a transaction id for the call.');
        }

        return $txn;
    }

    public function deliveryReport(VoiceCredentials $creds, string $transactionId): array
    {
        $response = $this->client($creds)->asForm()->post($creds->baseUrl.'/report/dlr', $this->auth($creds) + [
            'uuid' => $transactionId,
            'output' => 'json',
        ]);

        $this->throwUnlessOk($response, 'delivery report');

        $json = (array) $response->json();
        $row = $this->firstReportRow($json);

        $rawStatus = strtolower((string) $this->firstScalar($row, ['status', 'deliveryStatus', 'statusCode', 'callStatus']));
        $duration = $this->firstScalar($row, ['duration', 'callDuration', 'talkTime']);

        return [
            'status' => $this->normaliseStatus($rawStatus),
            'duration' => is_numeric($duration) ? (int) $duration : null,
            'raw' => $row ?: $json,
        ];
    }

    private function client(VoiceCredentials $creds): PendingRequest
    {
        $http = (array) config('services.voice.http');

        return $this->http
            ->acceptJson()
            ->connectTimeout((int) ($http['connect_timeout'] ?? 10))
            ->timeout((int) ($http['timeout'] ?? 30));
    }

    /** @return array<string, string> */
    private function auth(VoiceCredentials $creds): array
    {
        $fields = ['userid' => $creds->userId, 'password' => $creds->password];

        if ($creds->apiKey !== null) {
            $fields['apikey'] = $creds->apiKey;
        }

        return $fields;
    }

    private function throwUnlessOk(Response $response, string $action): void
    {
        if ($response->failed()) {
            $this->logRaw("voice provider HTTP error during {$action}", $response);
            throw new RuntimeException("Voice provider request failed ({$action}): HTTP {$response->status()}.");
        }

        // The API returns 200 even for logical errors; surface an explicit failure flag.
        $json = (array) $response->json();
        $status = strtolower((string) ($this->firstScalar($json, ['status', 'responseCode', 'result']) ?? ''));

        if (in_array($status, ['error', 'failed', 'failure'], true)) {
            $message = (string) ($this->firstScalar($json, ['message', 'reason', 'description']) ?? 'unknown error');
            throw new RuntimeException("Voice provider rejected the {$action}: {$message}");
        }
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private function firstScalar(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = data_get($data, $key);
            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * The DLR endpoint may return the row directly, or wrapped in data / an array.
     *
     * @param  array<string, mixed>  $json
     * @return array<string, mixed>
     */
    private function firstReportRow(array $json): array
    {
        foreach (['data.0', 'data', 'report.0', 'report', 'result.0', 'result'] as $path) {
            $candidate = data_get($json, $path);
            if (is_array($candidate) && $candidate !== [] && ! array_is_list($candidate)) {
                return $candidate;
            }
            if (is_array($candidate) && isset($candidate[0]) && is_array($candidate[0])) {
                return $candidate[0];
            }
        }

        return $json;
    }

    private function normaliseStatus(string $raw): string
    {
        return match (true) {
            str_contains($raw, 'answer') && ! str_contains($raw, 'no') => 'answered',
            in_array($raw, ['delivered', 'completed', 'success', 'dl', 'done'], true) => 'answered',
            in_array($raw, ['failed', 'rejected', 'undelivered', 'noanswer', 'no-answer', 'busy', 'na', 'error', 'expired'], true) => 'failed',
            str_contains($raw, 'fail') || str_contains($raw, 'reject') => 'failed',
            default => 'pending',
        };
    }

    private function logRaw(string $message, Response $response): void
    {
        Log::channel((string) config('services.voice.log_channel', 'whatsapp'))->warning($message, [
            'http_status' => $response->status(),
            'body' => mb_substr($response->body(), 0, 2000),
        ]);
    }
}
