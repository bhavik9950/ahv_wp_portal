<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookEvent;
use Illuminate\View\View;

/**
 * Read-only viewer for raw Meta webhook deliveries — the fastest way to confirm
 * the callback URL is wired up and to see what a customer actually sent.
 */
class WebhookEventController extends Controller
{
    public function index(): View
    {
        $events = WebhookEvent::query()
            ->orderByDesc('received_at')
            ->limit(500)
            ->get();

        return view('admin.webhook-events.index', [
            'events' => $events,
            'pending' => $events->whereNotIn('status', ['processed', 'ignored'])->count(),
            'lastAt' => $events->max('received_at'),
        ]);
    }

    public function show(WebhookEvent $webhookEvent): View
    {
        return view('admin.webhook-events.show', [
            'event' => $webhookEvent,
        ]);
    }
}
