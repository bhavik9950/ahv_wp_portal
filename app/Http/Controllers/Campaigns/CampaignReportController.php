<?php

declare(strict_types=1);

namespace App\Http\Controllers\Campaigns;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Services\Audit\AuditLogger;
use App\Support\Csv;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CampaignReportController extends Controller
{
    public function show(Campaign $campaign): View
    {
        $this->authorize('view', $campaign);

        $live = ! in_array($campaign->status->value, ['completed', 'cancelled', 'failed', 'draft'], true);
        $totals = $live ? $campaign->recomputeTotals() : ($campaign->totals ?? $campaign->recomputeTotals());

        $limit = 2000;

        $recipients = $campaign->recipients()
            ->with('contact')
            ->orderBy('status')
            ->limit($limit + 1)
            ->get();

        // Search / filter / sort are handled client-side by DataTables.
        return view('campaigns.report', [
            'campaign' => $campaign->load(['template', 'phoneNumber']),
            'totals' => $totals,
            'recipients' => $recipients->take($limit),
            'recipientsCapped' => $recipients->count() > $limit,
            'recipientLimit' => $limit,
            'live' => $live,
            'rates' => $this->rates($totals),
        ]);
    }

    public function export(Campaign $campaign, AuditLogger $audit): StreamedResponse
    {
        abort_unless((bool) request()->user()?->can(Permission::ReportExport->value), 403);
        $this->authorize('view', $campaign);

        $audit->log('campaign.report_exported', $campaign);

        return response()->streamDownload(function () use ($campaign): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['phone', 'contact', 'status', 'error_code', 'error_message', 'attempts', 'last_attempt_at']);

            $campaign->recipients()->with('contact')->chunk(1000, function ($rows) use ($out): void {
                foreach ($rows as $r) {
                    fputcsv($out, Csv::row([
                        $r->phone_e164,
                        $r->contact?->name,
                        $r->status->value,
                        $r->error_code,
                        $r->error_message,
                        $r->attempts,
                        optional($r->last_attempt_at)->toIso8601String(),
                    ]));
                }
            });

            fclose($out);
        }, 'campaign-'.$campaign->getKey().'-recipients.csv', ['Content-Type' => 'text/csv']);
    }

    /**
     * @param  array<string, int>  $totals
     * @return array<string, float>
     */
    private function rates(array $totals): array
    {
        $sent = ($totals['sent'] ?? 0) + ($totals['delivered'] ?? 0) + ($totals['read'] ?? 0);
        $total = max(1, $totals['total'] ?? 0);

        return [
            'delivery' => round((($totals['delivered'] ?? 0) + ($totals['read'] ?? 0)) / max(1, $sent) * 100, 1),
            'read' => round(($totals['read'] ?? 0) / max(1, $sent) * 100, 1),
            'failure' => round(($totals['failed'] ?? 0) / $total * 100, 1),
        ];
    }
}
