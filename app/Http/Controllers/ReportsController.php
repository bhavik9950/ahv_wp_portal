<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Campaign;
use App\Models\Message;
use App\Services\Reporting\DashboardMetrics;
use App\Support\CurrentOrganization;
use App\Support\DateRange;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function index(Request $request, DashboardMetrics $metrics, CurrentOrganization $currentOrg): View
    {
        $user = $request->user();
        abort_unless($user !== null && $user->can(Permission::ReportView->value), 403);

        $org = $currentOrg->resolve();
        $tz = $org !== null ? $org->timezone : 'UTC';
        $range = DateRange::fromRequest($request->only(['range', 'from', 'to']), $tz);

        $from = $range->from->copy()->startOfDay();
        $to = $range->to->copy()->endOfDay();

        $campaigns = Campaign::query()
            ->whereIn('status', ['completed', 'processing', 'paused', 'failed'])
            ->latest('started_at')
            ->limit(50)
            ->get()
            ->map(function (Campaign $c) {
                $t = $c->recomputeTotals();
                $sentish = ($t['sent'] ?? 0) + ($t['delivered'] ?? 0) + ($t['read'] ?? 0);

                return [
                    'campaign' => $c,
                    'total' => $t['total'] ?? 0,
                    'delivery' => round((($t['delivered'] ?? 0) + ($t['read'] ?? 0)) / max(1, $sentish) * 100, 1),
                    'read' => round(($t['read'] ?? 0) / max(1, $sentish) * 100, 1),
                    'failed' => $t['failed'] ?? 0,
                ];
            });

        // Direct / Normal messages (non-campaign)
        $directMessages = Message::query()
            ->with(['contact', 'template'])
            ->whereNull('campaign_id')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(50)
            ->get();

        $directSummary = [
            'total' => Message::query()->whereNull('campaign_id')->where('direction', 'outbound')->whereBetween('created_at', [$from, $to])->count(),
            'delivered' => Message::query()->whereNull('campaign_id')->where('direction', 'outbound')->whereIn('status', ['delivered', 'read'])->whereBetween('created_at', [$from, $to])->count(),
            'read' => Message::query()->whereNull('campaign_id')->where('direction', 'outbound')->where('status', 'read')->whereBetween('created_at', [$from, $to])->count(),
            'failed' => Message::query()->whereNull('campaign_id')->where('direction', 'outbound')->where('status', 'failed')->whereBetween('created_at', [$from, $to])->count(),
            'inbound' => Message::query()->where('direction', 'inbound')->whereBetween('created_at', [$from, $to])->count(),
        ];

        // Recent Inbound messages
        $inboundMessages = Message::query()
            ->with('contact')
            ->where('direction', 'inbound')
            ->whereBetween('created_at', [$from, $to])
            ->latest()
            ->limit(50)
            ->get();

        return view('reports.index', [
            'range' => $range,
            'presets' => DateRange::presets(),
            'metrics' => $metrics->forRange($range->from, $range->to, $range->key),
            'campaigns' => $campaigns,
            'directMessages' => $directMessages,
            'directSummary' => $directSummary,
            'inboundMessages' => $inboundMessages,
            'canExport' => $user->can(Permission::ReportExport->value),
        ]);
    }
}
