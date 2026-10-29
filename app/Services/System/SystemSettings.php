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
            fn () => SystemSetting::query()->where('key', $key)->value('value') ?? $default,
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

    public function billingUpiId(): string
    {
        return (string) $this->get('billing.upi_id', env('BILLING_UPI_ID', 'ahvsoftware@upi'));
    }

    public function billingBankDetails(): array
    {
        return (array) $this->get('billing.bank_details', [
            'bank_name' => env('BILLING_BANK_NAME', 'HDFC Bank'),
            'account_holder' => env('BILLING_ACCOUNT_HOLDER', 'AHV Software Technologies'),
            'account_number' => env('BILLING_ACCOUNT_NUMBER', '50200012345678'),
            'ifsc' => env('BILLING_BANK_IFSC', 'HDFC0001234'),
        ]);
    }

    /** @return array<string, array{label: string, price: int, days: ?int, description: string}> */
    public function billingPlans(): array
    {
        return (array) $this->get('billing.plans', [
            'monthly' => [
                'label' => '1 Month Access',
                'price' => 1999,
                'days' => 30,
                'description' => 'Ideal for monthly campaign testing and ongoing broadcasts.',
            ],
            'half_yearly' => [
                'label' => '6 Months Access',
                'price' => 9999,
                'days' => 180,
                'description' => 'Popular choice for small & medium growing businesses.',
            ],
            'yearly' => [
                'label' => '1 Year Access',
                'price' => 17999,
                'days' => 365,
                'description' => 'Best value for year-round customer engagement.',
            ],
            'lifetime' => [
                'label' => 'Lifetime Access',
                'price' => 20000,
                'days' => null,
                'description' => 'One-time investment. Unlimited dashboard access forever.',
            ],
        ]);
    }
}
