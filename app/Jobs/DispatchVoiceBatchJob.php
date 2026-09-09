<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CampaignStatus;
use App\Enums\VoiceCallStatus;
use App\Jobs\Concerns\BindsTenant;
use App\Models\VoiceCampaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Claims the next slice of pending recipients for a processing voice campaign,
 * hands each to PlaceVoiceCallJob staggered by the campaign's delay, then
 * re-queues itself. Stops as soon as the campaign is no longer `processing`.
 */
class DispatchVoiceBatchJob implements ShouldQueue
{
    use BindsTenant, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const SLICE = 100;

    public int $tries = 3;

    public function __construct(public string $campaignId) {}

    public function handle(): void
    {
        $campaign = VoiceCampaign::query()->withoutGlobalScopes()->find($this->campaignId);

        if ($campaign === null || $campaign->status !== CampaignStatus::Processing) {
            return;
        }

        $this->bindTenant($campaign->organization_id);

        $ids = $campaign->recipients()
            ->where('status', VoiceCallStatus::Pending->value)
            ->limit(self::SLICE)
            ->pluck('id');

        $delay = max(0, (int) $campaign->delay_seconds);

        if ($ids->isEmpty()) {
            if ($this->hasInFlight($campaign)) {
                self::dispatch($campaign->getKey())->onQueue('default')->delay(now()->addSeconds(10));
            } else {
                $campaign->forceFill([
                    'status' => CampaignStatus::Completed->value,
                    'finished_at' => now(),
                    'totals' => $campaign->recomputeTotals(),
                ])->save();
            }

            return;
        }

        $claimed = DB::table('voice_call_recipients')
            ->whereIn('id', $ids)
            ->where('status', VoiceCallStatus::Pending->value)
            ->update(['status' => VoiceCallStatus::Queued->value, 'updated_at' => now()]);

        $i = 0;
        foreach ($ids as $recipientId) {
            PlaceVoiceCallJob::dispatch($recipientId)
                ->onQueue('default')
                ->delay(now()->addSeconds($delay * $i));
            $i++;
        }

        self::dispatch($campaign->getKey())
            ->onQueue('default')
            ->delay(now()->addSeconds(max(5, $delay * $claimed)));
    }

    private function hasInFlight(VoiceCampaign $campaign): bool
    {
        return $campaign->recipients()
            ->whereIn('status', [VoiceCallStatus::Queued->value, VoiceCallStatus::Processing->value])
            ->exists();
    }
}
