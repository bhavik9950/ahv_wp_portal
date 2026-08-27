<?php

declare(strict_types=1);

namespace App\Services\System;

use App\Models\SystemSetting;
use Illuminate\Contracts\Cache\Repository as Cache;

/**
 * Platform-wide runtime settings with a cached read path.
 *
 * The WhatsApp "sending enabled" flag is the AND of:
 *   - the env/config default (services.whatsapp.sending_enabled), and
 *   - this runtime override (default true).
 * Either can turn sending OFF; both must be true for it to be ON.
 */
final class SystemSettings
{
    public const SENDING_ENABLED = 'whatsapp.sending_enabled';

    private const CACHE_PREFIX = 'system_setting:';

    public function __construct(private readonly Cache $cache) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->cache->rememberForever(
            self::CACHE_PREFIX.$key,
            fn () => SystemSetting::query()->find($key)?->value ?? $default,
        );
    }

    public function set(string $key, mixed $value): void
    {
        SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        $this->cache->forget(self::CACHE_PREFIX.$key);
    }

    /** Runtime kill-switch state (independent of the config default). */
    public function sendingEnabledOverride(): bool
    {
        return (bool) $this->get(self::SENDING_ENABLED, true);
    }

    public function setSendingEnabled(bool $enabled): void
    {
        $this->set(self::SENDING_ENABLED, $enabled);
    }
}
