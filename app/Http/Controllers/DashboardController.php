<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Services\Reporting\DashboardMetrics;
use App\Support\CurrentOrganization;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardMetrics $metrics, CurrentOrganization $currentOrg): View
    {
        $org = $currentOrg->resolve();
        $tz = $org !== null ? $org->timezone : 'UTC';
        $range = DateRange::fromRequest($request->only(['range', 'from', 'to']), $tz);
        $campaignId = $request->query('campaign_id') ?: null;

        $campaigns = Campaign::query()->latest()->limit(50)->get(['id', 'name', 'status']);
        $selectedCampaign = $campaignId ? Campaign::query()->find($campaignId) : null;

        return view('dashboard', [
            'range' => $range,
            'presets' => DateRange::presets(),
            'campaigns' => $campaigns,
            'selectedCampaignId' => $campaignId,
            'selectedCampaign' => $selectedCampaign,
            'metrics' => $metrics->forRange($range->from, $range->to, $range->key, $campaignId),
        ]);
    }
}
