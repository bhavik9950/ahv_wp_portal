<?php

declare(strict_types=1);

namespace App\Services\Voice\Contracts;

use App\Services\Voice\Data\VoiceCredentials;

/**
 * The only surface through which the app talks to the voice broadcast provider.
 * Two implementations: MockVoiceBroadcastDriver (offline) and
 * SmsGatewayCenterVoiceDriver (real).
 */
interface VoiceBroadcastDriver
{
    public function name(): string;

    /**
     * Upload an audio clip to the provider's library.
     *
     * @return string the provider library id
     */
    public function uploadAudio(VoiceCredentials $creds, string $contents, string $filename, string $libraryName): string;

    /**
     * Place a single voice call that plays a library clip to one number.
     *
     * @return string the provider transaction id (used later to poll delivery)
     */
    public function placeCall(VoiceCredentials $creds, string $libraryId, string $phone): string;

    /**
     * Poll the delivery status for a transaction id.
     *
     * @return array{status: string, duration: int|null, raw: array<string, mixed>}
     *                                                                              status: 'answered' | 'failed' | 'pending'
     */
    public function deliveryReport(VoiceCredentials $creds, string $transactionId): array;
}
