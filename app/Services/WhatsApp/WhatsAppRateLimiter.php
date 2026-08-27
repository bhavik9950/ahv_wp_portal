<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Carbon;

/**
 * Adaptive, per-phone-number throttle for outbound sends.
 *
 * This is a safety mechanism, NOT a way to hit a guaranteed rate. It only ever
 * slows sending down. Campaign "send delay" is layered on top and cannot make
 * sending faster than what this permits.
 *
 * Backed by the cache (atomic locks under Redis in production, database locally).
 */
final class WhatsAppRateLimiter
{
    /** Never send faster than this many per second per number, regardless of config. */
    private const SAFETY_FLOOR_INTERVAL_MS = 40; // ~25/s hard ceiling

    public function __construct(private readonly Cache $cache) {}

    /**
     * Reserve a send slot. Returns 0 if clear to send now, or the number of
     * seconds the caller should wait/requeue before trying again.
     */
    public function reserve(string $phoneNumberId, int $requestedDelaySeconds = 0): int
    {
        if (($until = $this->cooldownUntil($phoneNumberId)) !== null && $until->isFuture()) {
            return max(1, (int) ceil(now()->diffInSeconds($until, false)));
        }

        $intervalMs = max(self::SAFETY_FLOOR_INTERVAL_MS, $requestedDelaySeconds * 1000, $this->adaptiveIntervalMs($phoneNumberId));

        $key = "waba:rl:{$phoneNumberId}:next";
        $now = (int) (microtime(true) * 1000);
        $nextAllowed = (int) $this->cache->get($key, 0);

        if ($now < $nextAllowed) {
            return max(1, (int) ceil(($nextAllowed - $now) / 1000));
        }

        $this->cache->put($key, $now + $intervalMs, now()->addMinutes(5));

        return 0;
    }

    /** Meta signalled a rate limit — back off this number for a while. */
    public function penalize(string $phoneNumberId, ?int $retryAfterSeconds = null): void
    {
        $retryAfter = $retryAfterSeconds ?? 60;
        $this->cache->put("waba:rl:{$phoneNumberId}:cooldown", now()->addSeconds($retryAfter)->toIso8601String(), now()->addSeconds($retryAfter + 60));

        // Widen the adaptive interval (capped).
        $current = (int) $this->cache->get("waba:rl:{$phoneNumberId}:interval", self::SAFETY_FLOOR_INTERVAL_MS);
        $this->cache->put("waba:rl:{$phoneNumberId}:interval", min(5000, max(self::SAFETY_FLOOR_INTERVAL_MS, $current * 2)), now()->addHours(1));
    }

    /** A clean success — let the adaptive interval decay back toward the floor. */
    public function reward(string $phoneNumberId): void
    {
        $current = (int) $this->cache->get("waba:rl:{$phoneNumberId}:interval", self::SAFETY_FLOOR_INTERVAL_MS);
        if ($current > self::SAFETY_FLOOR_INTERVAL_MS) {
            $this->cache->put("waba:rl:{$phoneNumberId}:interval", max(self::SAFETY_FLOOR_INTERVAL_MS, (int) ($current * 0.9)), now()->addHours(1));
        }
    }

    private function adaptiveIntervalMs(string $phoneNumberId): int
    {
        return (int) $this->cache->get("waba:rl:{$phoneNumberId}:interval", self::SAFETY_FLOOR_INTERVAL_MS);
    }

    private function cooldownUntil(string $phoneNumberId): ?Carbon
    {
        $value = $this->cache->get("waba:rl:{$phoneNumberId}:cooldown");

        return $value ? Carbon::parse($value) : null;
    }
}
