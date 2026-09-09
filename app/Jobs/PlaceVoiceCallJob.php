<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CampaignStatus;
use App\Enums\VoiceCallStatus;
use App\Jobs\Concerns\BindsTenant;
use App\Models\VoiceCallRecipient;
use App\Services\Voice\VoiceBroadcastManager;
use App\Services\Voice\VoiceCampaignService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Places one recorded voice call through the provider. Idempotent: a recipient
 * already past `queued` is left alone. The delivery outcome is filled in later
 * by PollVoiceReportsJob.
 */
class PlaceVoiceCallJob implements ShouldQueue
{
    use BindsTenant, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 120, 300];
    }

    public function __construct(public string $recipientId) {}

    public function handle(VoiceBroadcastManager $manager, VoiceCampaignService $service): void
    {
        $recipient = VoiceCallRecipient::query()->withoutGlobalScopes()->with('campaign')->find($this->recipientId);

        if ($recipient === null || $recipient->status !== VoiceCallStatus::Queued) {
            return;
        }

        $campaign = $recipient->campaign;
        if ($campaign === null || $campaign->status !== CampaignStatus::Processing) {
            return;
        }

        $this->bindTenant($recipient->organization_id);

        // Claim: queued -> processing (atomic, so a retry can't double-dial).
        $claimed = DB::table('voice_call_recipients')
            ->where('id', $recipient->getKey())
            ->where('status', VoiceCallStatus::Queued->value)
            ->update(['status' => VoiceCallStatus::Processing->value, 'attempts' => DB::raw('attempts + 1'), 'updated_at' => now()]);

        if ($claimed === 0) {
            return;
        }

        try {
            $libraryId = $service->ensureAudioUploaded($campaign);

            $uuid = $manager->driver()->placeCall(
                $manager->credentials(),
                $libraryId,
                $recipient->phone_e164,
            );

            $recipient->forceFill([
                'status' => VoiceCallStatus::Placed->value,
                'provider_uuid' => $uuid,
                'called_at' => now(),
                'error_message' => null,
            ])->save();
        } catch (Throwable $e) {
            $recipient->forceFill([
                'status' => VoiceCallStatus::Failed->value,
                'error_message' => mb_substr($e->getMessage(), 0, 500),
            ])->save();

            report($e);
        }
    }
}
