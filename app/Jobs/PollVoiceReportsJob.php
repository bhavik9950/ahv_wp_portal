<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CampaignStatus;
use App\Enums\VoiceCallStatus;
use App\Jobs\Concerns\BindsTenant;
use App\Models\VoiceCallRecipient;
use App\Models\VoiceCampaign;
use App\Services\Voice\VoiceBroadcastManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * The provider has no delivery webhook, so poll it: for every recipient that
 * was placed but has no final outcome yet, fetch the delivery report and set
 * answered / failed + duration. Scheduled every couple of minutes.
 */
class PollVoiceReportsJob implements ShouldQueue
{
    use BindsTenant, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(VoiceBroadcastManager $manager): void
    {
        $pending = VoiceCallRecipient::query()->withoutGlobalScopes()
            ->where('status', VoiceCallStatus::Placed->value)
            ->whereNotNull('provider_uuid')
            ->where('called_at', '>=', now()->subDay())
            ->limit(500)
            ->get();

        if ($pending->isEmpty()) {
            return;
        }

        $creds = $manager->credentials();
        $driver = $manager->driver();
        $touchedCampaigns = [];

        foreach ($pending as $recipient) {
            try {
                $report = $driver->deliveryReport($creds, (string) $recipient->provider_uuid);
            } catch (Throwable $e) {
                report($e);

                continue;
            }

            if ($report['status'] === 'pending') {
                continue;
            }

            $recipient->forceFill([
                'status' => $report['status'] === 'answered' ? VoiceCallStatus::Answered->value : VoiceCallStatus::Failed->value,
                'duration_seconds' => $report['duration'],
            ])->save();

            $touchedCampaigns[$recipient->voice_campaign_id] = $recipient->organization_id;
        }

        foreach ($touchedCampaigns as $campaignId => $orgId) {
            $this->bindTenant($orgId);
            $campaign = VoiceCampaign::query()->withoutGlobalScopes()->find($campaignId);
            $campaign?->forceFill(['totals' => $campaign->recomputeTotals()])->save();

            if ($campaign !== null
                && $campaign->status === CampaignStatus::Processing
                && $campaign->recipients()->whereIn('status', [
                    VoiceCallStatus::Pending->value, VoiceCallStatus::Queued->value,
                    VoiceCallStatus::Processing->value, VoiceCallStatus::Placed->value,
                ])->doesntExist()) {
                $campaign->forceFill(['status' => CampaignStatus::Completed->value, 'finished_at' => now()])->save();
            }
        }
    }
}
