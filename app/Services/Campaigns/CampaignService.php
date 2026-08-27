<?php

declare(strict_types=1);

namespace App\Services\Campaigns;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Services\Audit\AuditLogger;
use App\Support\CurrentOrganization;
use Illuminate\Support\Facades\Auth;

final class CampaignService
{
    public function __construct(
        private readonly AuditLogger $audit,
        private readonly CurrentOrganization $currentOrg,
    ) {}

    public function createDraft(string $name): Campaign
    {
        $org = $this->currentOrg->resolve();

        $campaign = new Campaign;
        $campaign->fill([
            'name' => $name,
            'timezone' => $org !== null ? $org->timezone : 'UTC',
        ]);
        $campaign->status = CampaignStatus::Draft;
        $campaign->created_by = Auth::id();
        $campaign->save();

        $this->audit->log('campaign.created', $campaign, ['name' => $name]);

        return $campaign;
    }

    /**
     * Update draft fields. Ignored once the campaign has left draft.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateDraft(Campaign $campaign, array $data): Campaign
    {
        if ($campaign->status !== CampaignStatus::Draft) {
            return $campaign;
        }

        $campaign->fill(array_intersect_key($data, array_flip([
            'name', 'whatsapp_phone_number_id', 'template_id', 'media_id',
            'variable_map', 'audience_filter', 'send_delay_seconds', 'timezone',
        ])));
        $campaign->save();

        return $campaign;
    }
}
