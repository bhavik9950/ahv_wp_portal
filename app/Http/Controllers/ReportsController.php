<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Campaign;
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

        $campaigns = Campaign::query()
            ->whereIn('status', ['completed', 'processing', 'paused', 'failed'])
            ->latest('started_at')
            ->limit(50)
            ->get()
            ->map(function (Campaign $c) {
                $t = $c->totals ?? $c->recomputeTotals();
                $sentish = ($t['sent'] ?? 0) + ($t['delivered'] ?? 0) + ($t['read'] ?? 0);

                return [
                    'campaign' => $c,
                    'total' => $t['total'] ?? 0,
                    'delivery' => round((($t['delivered'] ?? 0) + ($t['read'] ?? 0)) / max(1, $sentish) * 100, 1),
                    'read' => round(($t['read'] ?? 0) / max(1, $sentish) * 100, 1),
                    'failed' => $t['failed'] ?? 0,
                ];
            });

        return view('reports.index', [
            'range' => $range,
            'presets' => DateRange::presets(),
            'metrics' => $metrics->forRange($range->from, $range->to, $range->key),
            'campaigns' => $campaigns,
            'canExport' => $user->can(Permission::ReportExport->value),
        ]);
    }
}
