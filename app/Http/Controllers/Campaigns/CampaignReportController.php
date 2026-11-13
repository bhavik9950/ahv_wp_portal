<?php

declare(strict_types=1);

namespace App\Http\Controllers\Campaigns;

use App\Enums\CampaignRecipientStatus;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Message;
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

        $type = (string) request()->query('type', 'all');

        $audit->log('campaign.report_exported', $campaign, ['type' => $type]);

        if ($type === 'inbound') {
            return $this->exportInbound($campaign);
        }

        return $this->exportRecipients($campaign, $type);
    }

    private function exportInbound(Campaign $campaign): StreamedResponse
    {
        $filename = 'campaign-'.$campaign->getKey().'-inbound-replies.csv';

        return response()->streamDownload(function () use ($campaign): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['phone', 'contact', 'message', 'type', 'received_at']);

            $campaignPhones = $campaign->recipients()->pluck('phone_e164');

            if ($campaignPhones->isNotEmpty()) {
                $since = $campaign->started_at ?? $campaign->scheduled_at ?? $campaign->created_at;

                Message::query()
                    ->withoutGlobalScopes()
                    ->where('organization_id', $campaign->organization_id)
                    ->where('direction', 'inbound')
                    ->whereIn('to_phone', $campaignPhones)
                    ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
                    ->with('contact')
                    ->orderBy('created_at', 'asc')
                    ->chunk(500, function ($messages) use ($out): void {
                        foreach ($messages as $msg) {
                            fputcsv($out, Csv::row([
                                $msg->to_phone,
                                $msg->contact?->name,
                                $msg->bodyText(),
                                $msg->type?->value ?? 'text',
                                optional($msg->created_at)->toIso8601String(),
                            ]));
                        }
                    });
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportRecipients(Campaign $campaign, string $type): StreamedResponse
    {
        $suffix = match ($type) {
            'success' => 'success',
            'failed' => 'failed',
            default => 'all',
        };

        $filename = 'campaign-'.$campaign->getKey().'-'.$suffix.'-recipients.csv';

        $query = $campaign->recipients()->with('contact');

        if ($type === 'success') {
            $query->whereIn('status', [
                CampaignRecipientStatus::Sent->value,
                CampaignRecipientStatus::Delivered->value,
                CampaignRecipientStatus::Read->value,
            ]);
        } elseif ($type === 'failed') {
            $query->whereIn('status', [
                CampaignRecipientStatus::Failed->value,
                CampaignRecipientStatus::Skipped->value,
                CampaignRecipientStatus::OptedOut->value,
            ]);
        }

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'wb');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['phone', 'contact', 'status', 'error_code', 'error_message', 'attempts', 'last_attempt_at']);

            $query->chunk(1000, function ($rows) use ($out): void {
                foreach ($rows as $r) {
                    fputcsv($out, Csv::row([
                        $r->phone_e164,
                        $r->contact?->name,
                        $r->status->value,
                        $r->error_code,
                        $r->error_message ?? $r->skip_reason,
                        $r->attempts,
                        optional($r->last_attempt_at)->toIso8601String(),
                    ]));
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
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
