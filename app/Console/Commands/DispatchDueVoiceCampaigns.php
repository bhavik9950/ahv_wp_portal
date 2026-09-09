<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\CampaignStatus;
use App\Jobs\DispatchVoiceBatchJob;
use App\Models\VoiceCampaign;
use Illuminate\Console\Command;

class DispatchDueVoiceCampaigns extends Command
{
    protected $signature = 'voice:dispatch-due';

    protected $description = 'Move scheduled voice campaigns whose time has come into processing and start calling';

    public function handle(): int
    {
        $due = VoiceCampaign::query()
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

            DispatchVoiceBatchJob::dispatch($campaign->getKey())->onQueue('default');
            $this->info("Started voice campaign {$campaign->getKey()} ({$campaign->name})");
        }

        return self::SUCCESS;
    }
}
