<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CampaignStatus;
use App\Jobs\DispatchCampaignBatchJob;
use App\Models\Campaign;
use Illuminate\Console\Command;

class DispatchDueCampaigns extends Command
{
    protected $signature = 'campaigns:dispatch-due';

    protected $description = 'Move scheduled campaigns whose time has come into processing and start sending';

    public function handle(): int
    {
        $due = Campaign::query()
            ->withoutGlobalScopes()
            ->where('status', CampaignStatus::Scheduled->value)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        foreach ($due as $campaign) {
            $campaign->forceFill([
                'status' => CampaignStatus::Processing->value,
                'started_at' => $campaign->started_at ?? now(),
            ])->save();

            DispatchCampaignBatchJob::dispatch($campaign->getKey())->onQueue('whatsapp-high');
            $this->info("Started campaign {$campaign->getKey()} ({$campaign->name})");
        }

        return self::SUCCESS;
    }
}
