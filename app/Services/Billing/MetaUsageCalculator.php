<?php

declare(strict_types=1);

namespace App\Services\Billing;

use App\Enums\MessageStatus;
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
     * @return array{
     *     billing_month: string,
     *     period_label: string,
     *     meta_billing_mode: string,
     *     uses_company_card: bool,
     *     rates: array{marketing: float, utility: float, authentication: float, service: float},
     *     counts: array{
     *         total_billable_messages: int,
     *         campaign_messages: int,
     *         single_messages: int,
     *         marketing: int,
     *         utility: int,
     *         authentication: int,
     *         service: int
     *     },
     *     items: array<int, array{description: string, quantity: int, unit_price: float, amount: float, meta: array<string, mixed>}>,
     *     total_amount: float
     * }
     */
    public function calculateForMonth(Organization $organization, string $billingMonth): array
    {
        $start = Carbon::createFromFormat('Y-m', $billingMonth, 'Asia/Kolkata')->startOfMonth()->utc();
        $end = (clone $start)->copy()->setTimezone('Asia/Kolkata')->endOfMonth()->utc();

        $rates = $this->settings->metaMessageRates();
        $usesCompanyCard = $organization->usesCompanyMetaCard();

        $billableStatuses = [
            MessageStatus::Sent->value,
            MessageStatus::Delivered->value,
            MessageStatus::Read->value,
        ];

        $messages = Message::query()
            ->withoutGlobalScopes()
            ->with(['template' => fn ($q) => $q->withoutGlobalScopes()->select('id', 'category')])
            ->where('organization_id', $organization->getKey())
            ->where('direction', 'outbound')
            ->whereIn('status', $billableStatuses)
            ->whereBetween('created_at', [$start, $end])
            ->get(['id', 'campaign_id', 'template_id', 'type', 'status', 'created_at']);

        $campaignCounts = ['marketing' => 0, 'utility' => 0, 'authentication' => 0, 'service' => 0];
        $singleCounts = ['marketing' => 0, 'utility' => 0, 'authentication' => 0, 'service' => 0];

        foreach ($messages as $msg) {
            $category = strtolower((string) ($msg->template?->category ?? 'service'));
            if (! array_key_exists($category, $campaignCounts)) {
                $category = 'service';
            }

            if ($msg->campaign_id !== null) {
                $campaignCounts[$category]++;
            } else {
                $singleCounts[$category]++;
            }
        }

        $totalCampaign = array_sum($campaignCounts);
        $totalSingle = array_sum($singleCounts);
        $totalMessages = $totalCampaign + $totalSingle;

        $items = [];
        $totalAmount = 0.0;

        $categoryLabels = [
            'marketing' => 'Marketing Template Messages',
            'utility' => 'Utility Template Messages',
            'authentication' => 'Authentication Template Messages',
            'service' => 'Service / Free-form Messages',
        ];

        foreach ($categoryLabels as $catKey => $catLabel) {
            // Campaign messages for this category
            if ($campaignCounts[$catKey] > 0) {
                $qty = $campaignCounts[$catKey];
                $unit = $usesCompanyCard ? $rates[$catKey] : 0.0;
                $amt = round($qty * $unit, 2);
                $totalAmount += $amt;

                $items[] = [
                    'description' => "Campaign — {$catLabel} ({$billingMonth})",
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'amount' => $amt,
                    'meta' => ['source' => 'campaign', 'category' => $catKey],
                ];
            }

            // Single / Direct messages for this category
            if ($singleCounts[$catKey] > 0) {
                $qty = $singleCounts[$catKey];
                $unit = $usesCompanyCard ? $rates[$catKey] : 0.0;
                $amt = round($qty * $unit, 2);
                $totalAmount += $amt;

                $items[] = [
                    'description' => "Single / Direct — {$catLabel} ({$billingMonth})",
                    'quantity' => $qty,
                    'unit_price' => $unit,
                    'amount' => $amt,
                    'meta' => ['source' => 'single', 'category' => $catKey],
                ];
            }
        }

        return [
            'billing_month' => $billingMonth,
            'period_label' => Carbon::createFromFormat('Y-m', $billingMonth)->format('F Y'),
            'meta_billing_mode' => $organization->meta_billing_mode ?? 'client_card',
            'uses_company_card' => $usesCompanyCard,
            'rates' => $rates,
            'counts' => [
                'total_billable_messages' => $totalMessages,
                'campaign_messages' => $totalCampaign,
                'single_messages' => $totalSingle,
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
