<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\MessageStatus;
use App\Enums\MessageType;
use App\Models\Message;
use App\Models\Organization;
use App\Services\System\SystemSettings;
use Illuminate\Support\Carbon;

final class MetaUsageCalculator
{
    public function __construct(private readonly SystemSettings $settings) {}

    /**
     * Calculate monthly outbound WhatsApp message usage and Meta charges for an organization.
     *
     * @return array<string, mixed>
     */
    public function calculateForMonth(Organization $organization, string $billingMonth, ?bool $forceCompanyCard = null): array
    {
        $start = Carbon::createFromFormat('Y-m', $billingMonth, 'Asia/Kolkata')->startOfMonth()->utc();
        $end = Carbon::createFromFormat('Y-m', $billingMonth, 'Asia/Kolkata')->endOfMonth()->utc();
        $label = Carbon::createFromFormat('Y-m', $billingMonth)->format('F Y');

        return $this->calculateForPeriod($organization, $start, $end, $billingMonth, $label, $forceCompanyCard);
    }

    /**
     * Calculate outbound WhatsApp message usage across an arbitrary date range (or all-time history when null).
     *
     * @return array<string, mixed>
     */
    public function calculateForPeriod(
        Organization $organization,
        ?Carbon $start = null,
        ?Carbon $end = null,
        ?string $billingMonth = null,
        ?string $periodLabel = null,
        ?bool $forceCompanyCard = null,
    ): array {
        $rates = $this->settings->metaMessageRates();
        $usesCompanyCard = $forceCompanyCard ?? $organization->usesCompanyMetaCard();
        $effectiveMode = $usesCompanyCard ? 'company_card' : 'client_card';

        $billableStatuses = [
            MessageStatus::Sent->value,
            MessageStatus::Delivered->value,
            MessageStatus::Read->value,
        ];

        $outboundQuery = Message::query()
            ->withoutGlobalScopes()
            ->with(['template' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'category')])
            ->where('organization_id', $organization->getKey())
            ->where('direction', 'outbound')
            ->whereIn('status', $billableStatuses)
            ->orderBy('created_at');

        if ($start && $end) {
            $outboundQuery->whereBetween('created_at', [$start, $end]);
        } elseif ($start) {
            $outboundQuery->where('created_at', '>=', $start);
        } elseif ($end) {
            $outboundQuery->where('created_at', '<=', $end);
        }

        $messages = $outboundQuery->get(['id', 'campaign_id', 'template_id', 'to_phone_hash', 'type', 'status', 'created_at']);

        // Load inbound messages (starting 24h before $start) to accurately check 24-hour Customer Service Windows.
        $inboundQuery = Message::query()
            ->withoutGlobalScopes()
            ->where('organization_id', $organization->getKey())
            ->where('direction', 'inbound')
            ->orderBy('created_at');

        if ($start) {
            $inboundQuery->where('created_at', '>=', (clone $start)->subHours(24));
        }
        if ($end) {
            $inboundQuery->where('created_at', '<=', $end);
        }

        /** @var array<string, array<int, int>> $inboundTimestampsByPhone */
        $inboundTimestampsByPhone = [];
        foreach ($inboundQuery->get(['to_phone_hash', 'created_at']) as $inMsg) {
            $hash = (string) $inMsg->to_phone_hash;
            if ($hash !== '' && $inMsg->created_at) {
                $inboundTimestampsByPhone[$hash][] = $inMsg->created_at->getTimestamp();
            }
        }

        $campaignCounts = ['marketing' => 0, 'utility' => 0, 'authentication' => 0, 'service' => 0];
        $singleCounts = ['marketing' => 0, 'utility' => 0, 'authentication' => 0, 'service' => 0];
        $freeWindowReplies = 0;

        foreach ($messages as $msg) {
            $isTemplate = ($msg->type === MessageType::Template) || ($msg->template_id !== null);
            $isCampaign = $msg->campaign_id !== null;

            // Check if this outbound message fell inside an active 24h inbound window
            $within24hWindow = false;
            $phoneHash = (string) $msg->to_phone_hash;
            $msgTs = $msg->created_at ? $msg->created_at->getTimestamp() : 0;

            if ($phoneHash !== '' && isset($inboundTimestampsByPhone[$phoneHash]) && $msgTs > 0) {
                $cutoff = $msgTs - 86400; // 24 hours prior
                foreach ($inboundTimestampsByPhone[$phoneHash] as $inTs) {
                    if ($inTs <= $msgTs && $inTs >= $cutoff) {
                        $within24hWindow = true;
                        break;
                    }
                }
            }

            // Free-form (non-template) replies inside the 24h inbound window are free per Meta rules.
            if (! $isTemplate && $within24hWindow) {
                $freeWindowReplies++;
                continue;
            }

            // Template messages (including from deleted campaigns where campaign_id became null)
            // and out-of-window non-template messages are counted in their respective category.
            $category = strtolower((string) ($msg->template?->category ?? 'service'));
            if (! array_key_exists($category, $campaignCounts)) {
                $category = 'service';
            }

            if ($isCampaign) {
                $campaignCounts[$category]++;
            } else {
                $singleCounts[$category]++;
            }
        }

        $totalCampaign = array_sum($campaignCounts);
        $totalSingle = array_sum($singleCounts);
        $totalBillableMessages = $totalCampaign + $totalSingle;

        $items = [];
        $totalAmount = 0.0;
        $periodTag = $billingMonth ?: ($periodLabel ?: 'History');

        $categoryLabels = [
            'marketing' => 'Marketing Template Messages',
            'utility' => 'Utility Template Messages',
            'authentication' => 'Authentication Template Messages',
            'service' => 'Out-of-Window / Service Messages',
        ];

        foreach ($categoryLabels as $catKey => $catLabel) {
            if ($campaignCounts[$catKey] > 0) {
                $qty = $campaignCounts[$catKey];
                $unit = $usesCompanyCard ? $rates[$catKey] : 0.0;
                $amt = round($qty * $unit, 2);
                $totalAmount += $amt;

                $items[] = [
                    'description' => "Campaign — {$catLabel} ({$periodTag})",
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'amount' => $amt,
                    'meta' => ['source' => 'campaign', 'category' => $catKey],
                ];
            }

            if ($singleCounts[$catKey] > 0) {
                $qty = $singleCounts[$catKey];
                $unit = $usesCompanyCard ? $rates[$catKey] : 0.0;
                $amt = round($qty * $unit, 2);
                $totalAmount += $amt;

                $items[] = [
                    'description' => "Single / Out-of-Window — {$catLabel} ({$periodTag})",
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'amount' => $amt,
                    'meta' => ['source' => 'single', 'category' => $catKey],
                ];
            }
        }

        return [
            'billing_month' => $billingMonth,
            'period_label' => $periodLabel ?? 'All-Time History',
            'meta_billing_mode' => $effectiveMode,
            'uses_company_card' => $usesCompanyCard,
            'rates' => $rates,
            'counts' => [
                'total_billable_messages' => $totalBillableMessages,
                'campaign_messages' => $totalCampaign,
                'single_messages' => $totalSingle,
                'free_window_replies' => $freeWindowReplies,
                'marketing' => $campaignCounts['marketing'] + $singleCounts['marketing'],
                'utility' => $campaignCounts['utility'] + $singleCounts['utility'],
                'authentication' => $campaignCounts['authentication'] + $singleCounts['authentication'],
                'service' => $campaignCounts['service'] + $singleCounts['service'],
            ],
            'items' => $items,
            'total_amount' => round($totalAmount, 2),
        ];
    }
}
