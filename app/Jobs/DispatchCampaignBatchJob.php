<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CampaignRecipientStatus;
use App\Enums\CampaignStatus;
use App\Jobs\Concerns\BindsTenant;
use App\Models\Campaign;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * Pulls the next slice of pending recipients for a processing campaign, hands
 * each to SendCampaignMessageJob (staggered by the configured delay), then
 * re-queues itself for the next slice. Stops as soon as the campaign is no
 * longer `processing` — so pause / cancel take effect between slices.
 *
 * Never enqueues one giant delayed job for the whole campaign.
 */
class DispatchCampaignBatchJob implements ShouldQueue
{
    use BindsTenant, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Recipients claimed per slice. */
    private const SLICE = 250;

    public int $tries = 3;

    public function __construct(public string $campaignId) {}

    public function handle(): void
    {
        /** @var Campaign|null $campaign */
        $campaign = Campaign::query()->withoutGlobalScopes()->find($this->campaignId);

        if ($campaign === null || $campaign->status !== CampaignStatus::Processing) {
            return;
        }

        $this->bindTenant($campaign->organization_id);

        // Atomically claim a slice: pending -> queued.
        $ids = $campaign->recipients()
            ->where('status', CampaignRecipientStatus::Pending->value)
            ->limit(self::SLICE)
            ->pluck('id');

        $delay = max(0, (int) ($campaign->send_delay_seconds ?? 0));

        if ($ids->isEmpty()) {
            if ($this->hasInFlight($campaign)) {
                // Sends still resolving — come back shortly to finish up.
                self::dispatch($campaign->getKey())->onQueue('whatsapp-high')->delay(now()->addSeconds(5));
            } else {
                FinalizeCampaignJob::dispatch($campaign->getKey())->onQueue('whatsapp-reports');
            }

            return;
        }

        $claimed = DB::table('campaign_recipients')
            ->whereIn('id', $ids)
            ->where('status', CampaignRecipientStatus::Pending->value)
            ->update(['status' => CampaignRecipientStatus::Queued->value, 'updated_at' => now()]);

        $i = 0;
        foreach ($ids as $recipientId) {
            SendCampaignMessageJob::dispatch($recipientId)
                ->onQueue('whatsapp-send')
                ->delay(now()->addSeconds($delay * $i));
            $i++;
        }

        // Next slice — small delay so a pause between slices is honoured promptly.
        self::dispatch($campaign->getKey())
            ->onQueue('whatsapp-high')
            ->delay(now()->addSeconds(max(3, $delay * $claimed)));
    }

    private function hasInFlight(Campaign $campaign): bool
    {
        return $campaign->recipients()
            ->whereIn('status', [
                CampaignRecipientStatus::Queued->value,
                CampaignRecipientStatus::Processing->value,
            ])
            ->exists();
    }
}
