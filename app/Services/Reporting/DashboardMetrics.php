<?php

declare(strict_types=1);

namespace App\Services\Reporting;

use App\Enums\CampaignStatus;
use App\Enums\MessageStatus;
use App\Enums\OptInStatus;
use App\Models\Campaign;
use App\Models\Contact;
use App\Models\Message;
use App\Models\ScheduledMessage;
use App\Models\WhatsappTemplate;
use Illuminate\Support\Carbon;

/**
 * Tenant-scoped aggregate metrics for the dashboard and reports overview.
 * All queries run through the models' organization global scope.
 */
final class DashboardMetrics
{
    /**
     * @return array{
     *   range: array{from: string, to: string, key: string},
     *   messages: array<string, int>,
     *   rates: array{delivery: float, read: float, failure: float},
     *   campaigns: array{active: int, completed: int, scheduled: int},
     *   contacts: array{total: int, opted_in: int, opted_out: int},
     *   templates: array{approved: int, pending: int, rejected: int},
     *   scheduled_messages: int,
     *   trend: list<array{date: string, outbound: int, delivered: int, failed: int}>
     * }
     */
    public function forRange(Carbon $from, Carbon $to, string $key = 'custom'): array
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $messages = $this->messageCounts($from, $to);
        $sentish = $messages['sent'] + $messages['delivered'] + $messages['read'];

        return [
            'range' => ['from' => $from->toDateString(), 'to' => $to->toDateString(), 'key' => $key],
            'messages' => $messages,
            'rates' => [
                'delivery' => $this->pct($messages['delivered'] + $messages['read'], max(1, $sentish)),
                'read' => $this->pct($messages['read'], max(1, $sentish)),
                'failure' => $this->pct($messages['failed'], max(1, $messages['total'])),
            ],
            'campaigns' => [
                'active' => Campaign::query()->whereIn('status', [
                    CampaignStatus::Processing->value, CampaignStatus::Scheduled->value, CampaignStatus::Paused->value,
                ])->count(),
                'completed' => Campaign::query()->where('status', CampaignStatus::Completed->value)
                    ->whereBetween('finished_at', [$from, $to])->count(),
                'scheduled' => Campaign::query()->where('status', CampaignStatus::Scheduled->value)->count(),
            ],
            'contacts' => [
                'total' => Contact::query()->count(),
                'opted_in' => Contact::query()->where('opt_in_status', OptInStatus::OptedIn->value)->count(),
                'opted_out' => Contact::query()->where('opt_in_status', OptInStatus::OptedOut->value)->count(),
            ],
            'templates' => [
                'approved' => WhatsappTemplate::query()->where('status', 'APPROVED')->count(),
                'pending' => WhatsappTemplate::query()->where('status', 'PENDING')->count(),
                'rejected' => WhatsappTemplate::query()->where('status', 'REJECTED')->count(),
            ],
            'scheduled_messages' => ScheduledMessage::query()->where('status', 'pending')->count(),
            'trend' => $this->trend($from, $to),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function messageCounts(Carbon $from, Carbon $to): array
    {
        $rows = Message::query()
            ->where('direction', 'outbound')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, count(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status')
            ->all();

        $counts = ['total' => array_sum($rows)];
        foreach (MessageStatus::cases() as $s) {
            $counts[$s->value] = (int) ($rows[$s->value] ?? 0);
        }

        return $counts;
    }

    /**
     * Daily buckets: outbound created, delivered (delivered+read), failed.
     *
     * @return list<array{date: string, outbound: int, delivered: int, failed: int}>
     */
    private function trend(Carbon $from, Carbon $to): array
    {
        $rows = Message::query()
            ->where('direction', 'outbound')
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('DATE(created_at) as d')
            ->selectRaw('count(*) as outbound')
            ->selectRaw("sum(case when status in ('delivered','read') then 1 else 0 end) as delivered")
            ->selectRaw("sum(case when status = 'failed' then 1 else 0 end) as failed")
            ->groupBy('d')
            ->get()
            ->keyBy(fn ($r) => (string) $r->getAttribute('d'));

        $out = [];
        $cursor = $from->copy();
        while ($cursor->lte($to)) {
            $d = $cursor->toDateString();
            $row = $rows->get($d);
            $out[] = [
                'date' => $d,
                'outbound' => (int) ($row?->getAttribute('outbound') ?? 0),
                'delivered' => (int) ($row?->getAttribute('delivered') ?? 0),
                'failed' => (int) ($row?->getAttribute('failed') ?? 0),
            ];
            $cursor->addDay();
        }

        return $out;
    }

    private function pct(int $part, int $whole): float
    {
        return round($part / max(1, $whole) * 100, 1);
    }
}
