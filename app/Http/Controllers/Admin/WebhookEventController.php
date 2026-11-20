<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
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
        $query = WebhookEvent::query()->orderByDesc('received_at')->limit(500);

        if (! $request->user()?->isSuperAdmin()) {
            $orgId = $tenant->id();
            $wabaId = $tenant->organization()?->whatsappBusinessAccount?->waba_id;
            $phoneIds = $tenant->organization()?->whatsappPhoneNumbers()->pluck('phone_number_id')->filter()->all() ?? [];

            $query->where(function ($q) use ($orgId, $wabaId, $phoneIds) {
                $q->where('organization_id', $orgId);
                if (filled($wabaId)) {
                    $q->orWhere('payload', 'like', "%{$wabaId}%");
                }
                foreach ($phoneIds as $pid) {
                    if (filled($pid)) {
                        $q->orWhere('payload', 'like', "%{$pid}%");
                    }
                }
            });
        }

        $events = $query->get();

        return view('admin.webhook-events.index', [
            'events' => $events,
            'stats' => [
                'total' => $events->count(),
                'inbound' => $events->filter(fn ($e) => str_starts_with($e->kind(), 'inbound'))->count(),
                'statuses' => $events->filter(fn ($e) => str_starts_with($e->kind(), 'status'))->count(),
                'failed' => $events->where('status', 'failed')->count(),
                'bad_signature' => $events->where('signature_valid', false)->count(),
            ],
            // Unprocessed count is just a number (no content) — useful to flag a
            // dead queue worker regardless of org.
            'pending' => WebhookEvent::query()->whereNotIn('status', ['processed', 'ignored'])->count(),
            'lastAt' => $events->max('received_at'),
        ]);
    }

    public function show(Request $request, WebhookEvent $webhookEvent, TenantContext $tenant): View
    {
        $isSuper = $request->user()?->isSuperAdmin() ?? false;
        $orgId = (int) $tenant->id();
        $isOwner = ((int) $webhookEvent->organization_id === $orgId && $webhookEvent->organization_id !== null);

        if (! $isSuper && ! $isOwner) {
            $wabaId = $tenant->organization()?->whatsappBusinessAccount?->waba_id;
            $phoneIds = $tenant->organization()?->whatsappPhoneNumbers()->pluck('phone_number_id')->filter()->all() ?? [];
            $payloadStr = json_encode($webhookEvent->payload);

            if (filled($wabaId) && str_contains((string) $payloadStr, (string) $wabaId)) {
                $isOwner = true;
            } elseif (collect($phoneIds)->contains(fn ($pid) => filled($pid) && str_contains((string) $payloadStr, (string) $pid))) {
                $isOwner = true;
            }
        }

        abort_unless($isSuper || $isOwner, 404);

        return view('admin.webhook-events.show', [
            'event' => $webhookEvent,
        ]);
    }
}
