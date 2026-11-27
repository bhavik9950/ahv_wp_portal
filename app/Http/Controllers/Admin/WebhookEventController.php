<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use App\Models\WhatsappBusinessAccount;
use App\Models\WhatsappPhoneNumber;
use App\Support\TenantContext;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Read-only viewer for raw Meta webhook deliveries — the fastest way to confirm
 * the callback URL is wired up and to see what a customer actually sent.
 *
 * WebhookEvent is not tenant-scoped (it arrives before the org is resolved), so
 * scoping is enforced here: a non-super-admin only sees their own org's events
 * (finding M-3).
 */
class WebhookEventController extends Controller
{
    public function index(Request $request, TenantContext $tenant): View
    {
        $orgId = $tenant->id();

        $wabaIds = WhatsappBusinessAccount::query()->withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->pluck('waba_id')
            ->filter()
            ->all();
        $phoneIds = WhatsappPhoneNumber::query()->withoutGlobalScopes()
            ->where('organization_id', $orgId)
            ->pluck('phone_number_id')
            ->filter()
            ->all();

        // Auto-assign any unassigned events that match this tenant's WABA or Phone IDs
        if (! empty($wabaIds) || ! empty($phoneIds)) {
            $unassigned = WebhookEvent::query()->whereNull('organization_id')->limit(100)->get();
            foreach ($unassigned as $evt) {
                $payloadStr = json_encode($evt->payload);
                $matched = false;
                foreach ($wabaIds as $wid) {
                    if (filled($wid) && str_contains($payloadStr, (string) $wid)) {
                        $matched = true;
                        break;
                    }
                }
                if (! $matched) {
                    foreach ($phoneIds as $pid) {
                        if (filled($pid) && str_contains($payloadStr, (string) $pid)) {
                            $matched = true;
                            break;
                        }
                    }
                }
                if ($matched) {
                    $evt->update(['organization_id' => $orgId]);
                }
            }
        }

        $search = trim((string) $request->query('q', ''));

        // Strictly scoped to the currently active organization:
        // No client or admin ever sees another tenant's webhook events.
        $eventsQuery = WebhookEvent::query()
            ->where('organization_id', $orgId);

        if ($search !== '') {
            $digits = preg_replace('/\D+/', '', $search) ?: '';
            $eventsQuery->where(function ($q) use ($search, $digits): void {
                $q->where('payload', 'like', "%{$search}%")
                    ->orWhere('status', 'like', "%{$search}%");
                if ($digits !== '' && $digits !== $search) {
                    $q->orWhere('payload', 'like', "%{$digits}%");
                }
                if (strlen($digits) === 12 && str_starts_with($digits, '91')) {
                    $q->orWhere('payload', 'like', '%'.substr($digits, 2).'%');
                }
            });
        }

        $events = $eventsQuery
            ->orderByDesc('received_at')
            ->limit(500)
            ->get();

        return view('admin.webhook-events.index', [
            'events' => $events,
            'search' => $search,
            'stats' => [
                'total' => $events->count(),
                'inbound' => $events->filter(fn ($e) => str_starts_with($e->kind(), 'inbound'))->count(),
                'statuses' => $events->filter(fn ($e) => str_starts_with($e->kind(), 'status'))->count(),
                'failed' => $events->where('status', 'failed')->count(),
                'bad_signature' => $events->where('signature_valid', false)->count(),
            ],
            // Scoped strictly to current organization
            'pending' => WebhookEvent::query()->where('organization_id', $orgId)->whereNotIn('status', ['processed', 'ignored'])->count(),
            'lastAt' => $events->max('received_at'),
        ]);
    }

    public function show(Request $request, WebhookEvent $webhookEvent, TenantContext $tenant): View
    {
        $orgId = (int) $tenant->id();
        abort_unless((int) $webhookEvent->organization_id === $orgId, 404);

        return view('admin.webhook-events.show', [
            'event' => $webhookEvent,
        ]);
    }
}
