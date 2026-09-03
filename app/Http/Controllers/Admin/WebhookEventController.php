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
            $query->where('organization_id', $tenant->id());
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
        abort_unless(
            $request->user()?->isSuperAdmin()
                || ((int) $webhookEvent->organization_id === (int) $tenant->id() && $webhookEvent->organization_id !== null),
            404,
        );

        return view('admin.webhook-events.show', [
            'event' => $webhookEvent,
        ]);
    }
}
