<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\WebhookEvent;
use App\Models\WhatsappBusinessAccount;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

/**
 * Lightweight health probes for the admin System Health page and the public
 * /health endpoint. Never returns secrets.
 */
final class HealthMonitor
{
    public function __construct(private readonly SystemSettings $settings) {}

    /**
     * @return list<ComponentHealth>
     */
    public function check(): array
    {
        return [
            $this->database(),
            $this->cache(),
            $this->queue(),
            $this->redis(),
            $this->whatsappApi(),
            $this->token(),
            $this->webhook(),
            $this->killSwitch(),
        ];
    }

    public function allHealthy(): bool
    {
        foreach ($this->check() as $component) {
            if (! $component->isHealthy()) {
                return false;
            }
        }

        return true;
    }

    private function database(): ComponentHealth
    {
        try {
            DB::connection()->select('select 1');

            return ComponentHealth::ok('database', 'Database');
        } catch (Throwable $e) {
            return ComponentHealth::error('database', 'Database', 'Connection failed');
        }
    }

    private function cache(): ComponentHealth
    {
        try {
            $token = Str::random(8);
            Cache::put('health:probe', $token, 10);

            return Cache::get('health:probe') === $token
                ? ComponentHealth::ok('cache', 'Cache')
                : ComponentHealth::error('cache', 'Cache', 'Read-back mismatch');
        } catch (Throwable $e) {
            return ComponentHealth::error('cache', 'Cache', 'Unavailable');
        }
    }

    private function queue(): ComponentHealth
    {
        try {
            $failed = DB::table('failed_jobs')->count();
            $pending = DB::table('jobs')->count();
            $stale = DB::table('jobs')
                ->where('available_at', '<', now()->subMinutes(15)->getTimestamp())
                ->exists();

            if ($failed > 0) {
                return ComponentHealth::warning('queue', 'Queue', "{$failed} failed job(s)");
            }
            if ($stale) {
                return ComponentHealth::warning('queue', 'Queue', "Backlog building ({$pending} jobs, some >15m old)");
            }

            return ComponentHealth::ok('queue', 'Queue', "{$pending} pending");
        } catch (Throwable $e) {
            return ComponentHealth::warning('queue', 'Queue', 'Cannot inspect queue tables');
        }
    }

    private function redis(): ComponentHealth
    {
        if (! in_array('redis', [config('queue.default'), config('cache.default'), config('session.driver')], true)) {
            return ComponentHealth::na('redis', 'Redis', 'Not in use (database drivers)');
        }

        try {
            app('redis')->connection()->ping();

            return ComponentHealth::ok('redis', 'Redis');
        } catch (Throwable $e) {
            return ComponentHealth::error('redis', 'Redis', 'Ping failed');
        }
    }

    private function whatsappApi(): ComponentHealth
    {
        $driver = (string) config('services.whatsapp.driver');

        if ($driver === 'mock') {
            return ComponentHealth::ok('whatsapp_api', 'WhatsApp API', 'Mock driver');
        }

        $account = WhatsappBusinessAccount::query()->withoutGlobalScopes()->first();

        if ($account === null) {
            return ComponentHealth::warning('whatsapp_api', 'WhatsApp API', 'No WABA configured');
        }

        return match ($account->connection_status) {
            'connected' => ComponentHealth::ok('whatsapp_api', 'WhatsApp API'),
            'error' => ComponentHealth::error('whatsapp_api', 'WhatsApp API', 'Last check failed'),
            default => ComponentHealth::warning('whatsapp_api', 'WhatsApp API', 'Not verified yet'),
        };
    }

    private function token(): ComponentHealth
    {
        $account = WhatsappBusinessAccount::query()->withoutGlobalScopes()->first();

        if ($account === null) {
            return ComponentHealth::na('token', 'Access Token');
        }

        return match ($account->token_status) {
            'valid' => ComponentHealth::ok('token', 'Access Token'),
            'expired' => ComponentHealth::error('token', 'Access Token', 'Expired'),
            'invalid' => ComponentHealth::error('token', 'Access Token', 'Invalid'),
            default => ComponentHealth::warning('token', 'Access Token', 'Not verified'),
        };
    }

    private function webhook(): ComponentHealth
    {
        $last = WebhookEvent::query()->max('received_at');

        if ($last === null) {
            return ComponentHealth::warning('webhook', 'Webhook', 'No events received yet');
        }

        return Carbon::parse($last)->gt(now()->subDay())
            ? ComponentHealth::ok('webhook', 'Webhook', 'Recent events received')
            : ComponentHealth::warning('webhook', 'Webhook', 'No events in the last 24h');
    }

    private function killSwitch(): ComponentHealth
    {
        $configEnabled = (bool) config('services.whatsapp.sending_enabled', true);
        $runtimeEnabled = $this->settings->sendingEnabledOverride();

        if ($configEnabled && $runtimeEnabled) {
            return ComponentHealth::ok('sending', 'Outbound Sending', 'Enabled');
        }

        $why = ! $configEnabled ? 'disabled in config' : 'disabled by admin kill switch';

        return ComponentHealth::warning('sending', 'Outbound Sending', ucfirst($why));
    }
}
