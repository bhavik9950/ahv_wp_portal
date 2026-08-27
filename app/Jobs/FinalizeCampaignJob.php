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
use Illuminate\Support\Facades\Cache;

class FinalizeCampaignJob implements ShouldQueue
{
    use BindsTenant, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public string $campaignId) {}

    public function handle(): void
    {
        Cache::lock("campaign:{$this->campaignId}:finalize", 15)->block(5, function (): void {
            /** @var Campaign|null $campaign */
            $campaign = Campaign::query()->withoutGlobalScopes()->find($this->campaignId);
            if ($campaign === null || $campaign->status !== CampaignStatus::Processing) {
                return;
            }

            $this->bindTenant($campaign->organization_id);

            // Anything still pending/queued/processing means we're not done.
            $outstanding = $campaign->recipients()
                ->whereIn('status', [
                    CampaignRecipientStatus::Pending->value,
                    CampaignRecipientStatus::Queued->value,
                    CampaignRecipientStatus::Processing->value,
                ])
                ->exists();

            if ($outstanding) {
                return;
            }

            $totals = $campaign->recomputeTotals();
            $allFailed = $totals['total'] > 0
                && ($totals['failed'] ?? 0) === $totals['total'];

            $campaign->forceFill([
                'status' => $allFailed ? CampaignStatus::Failed : CampaignStatus::Completed,
                'finished_at' => now(),
                'totals' => $totals,
            ])->save();
        });
    }
}
