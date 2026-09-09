<?php

declare(strict_types=1);

namespace App\Services\Voice\Drivers;

use App\Services\Voice\Contracts\VoiceBroadcastDriver;
use App\Services\Voice\Data\VoiceCredentials;
use Illuminate\Support\Str;

/**
 * Offline simulator. Delivery outcome is driven by the last 4 digits of the
 * number so tests can exercise both paths deterministically:
 *
 *   xxxx0000  failed (not answered)
 *   anything else  answered
 */
final class MockVoiceBroadcastDriver implements VoiceBroadcastDriver
{
    public function name(): string
    {
        return 'mock';
    }

    public function uploadAudio(VoiceCredentials $creds, string $contents, string $filename, string $libraryName): string
    {
        return 'mock-lib-'.Str::upper(Str::random(16));
    }

    public function placeCall(VoiceCredentials $creds, string $libraryId, string $phone): string
    {
        // Encode the intended outcome into the id so deliveryReport() is stateless.
        $outcome = str_ends_with($phone, '0000') ? 'FAIL' : 'OK';

        return "mock-voxn-{$outcome}-".Str::upper(Str::random(16));
    }

    public function deliveryReport(VoiceCredentials $creds, string $transactionId): array
    {
        $failed = str_contains($transactionId, '-FAIL-');

        return [
            'status' => $failed ? 'failed' : 'answered',
            'duration' => $failed ? 0 : 14,
            'raw' => ['mock' => true, 'transactionId' => $transactionId],
        ];
    }
}
