<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\System\HealthMonitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\View\View;

class HealthController extends Controller
{
    public function __construct(private readonly HealthMonitor $health) {}

    public function show(): View
    {
        $logDir = storage_path('logs');
        $logFiles = is_dir($logDir)
            ? collect(File::files($logDir))
                ->filter(fn ($f) => $f->getExtension() === 'log')
                ->map(fn ($f) => [
                    'name' => $f->getFilename(),
                    'size_kb' => round($f->getSize() / 1024, 1),
                    'updated_at' => date('d M Y, h:i A', $f->getMTime()),
                ])
                ->sortByDesc('size_kb')
                ->values()
                ->all()
            : [];

        return view('admin.health', [
            'components' => $this->health->check(),
            'logFiles' => $logFiles,
        ]);
    }

    public function clearLogs(): RedirectResponse
    {
        Artisan::call('logs:prune', ['--all' => true]);

        return back()->with('flash_notify', [
            'type' => 'success',
            'message' => trim(Artisan::output()) ?: 'Logs cleared.',
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
