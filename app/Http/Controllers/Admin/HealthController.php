<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\System\HealthMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class HealthController extends Controller
{
    public function __construct(private readonly HealthMonitor $health) {}

    public function show(): View
    {
        return view('admin.health', [
            'components' => $this->health->check(),
        ]);
    }

    /**
     * Public liveness/readiness probe. No secrets, no detail — just component
     * status flags and an overall verdict.
     */
    public function ping(): JsonResponse
    {
        $components = collect($this->health->check())
            ->mapWithKeys(fn ($c) => [$c->key => $c->status])
            ->all();

        $healthy = ! in_array('error', $components, true);

        return response()->json([
            'status' => $healthy ? 'ok' : 'degraded',
            'components' => $components,
            'time' => now()->toIso8601String(),
        ], $healthy ? 200 : 503);
    }
}
