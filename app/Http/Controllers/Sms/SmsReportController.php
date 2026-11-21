<?php

declare(strict_types=1);

namespace App\Http\Controllers\Sms;

use App\Http\Controllers\Controller;
use App\Models\Sms\SmsCampaign;
use App\Models\Sms\SmsMessage;
use App\Support\CurrentOrganization;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SmsReportController extends Controller
{
    public function index(Request $request, CurrentOrganization $currentOrg): View
    {
        $org = $currentOrg->resolve();
        $tz = $org?->timezone ?? 'UTC';
        $range = DateRange::fromRequest($request->only(['range', 'from', 'to']), $tz);

        $from = $range->from->copy()->startOfDay();
        $to = $range->to->copy()->endOfDay();

        $messagesQuery = SmsMessage::query()->whereBetween('created_at', [$from, $to]);

        $summary = [
            'total' => (clone $messagesQuery)->count(),
            'delivered' => (clone $messagesQuery)->where('status', 'delivered')->count(),
            'failed' => (clone $messagesQuery)->where('status', 'failed')->count(),
            'queued' => (clone $messagesQuery)->where('status', 'queued')->count(),
            'cost' => (clone $messagesQuery)->sum('cost'),
        ];

        $campaigns = SmsCampaign::query()
            ->with(['sender', 'template'])
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(20)
            ->get();

        $recentLogs = (clone $messagesQuery)
            ->with(['contact', 'campaign'])
            ->latest()
            ->limit(50)
            ->get();

        return view('sms.reports.index', [
            'organization' => $org,
            'range' => $range,
            'presets' => DateRange::presets(),
            'summary' => $summary,
            'campaigns' => $campaigns,
            'recentLogs' => $recentLogs,
        ]);
    }
}
