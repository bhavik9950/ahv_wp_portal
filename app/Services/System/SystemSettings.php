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
                'price' => 649,
                'days' => 30,
                'description' => 'Affordable monthly access for business broadcasts and customer chat.',
            ],
            'half_yearly' => [
                'label' => '6 Months Access',
                'price' => 3499,
                'days' => 180,
                'description' => 'Popular choice for small & medium growing businesses.',
            ],
            'yearly' => [
                'label' => '1 Year Access',
                'price' => 5999,
                'days' => 365,
                'description' => 'Best value for year-round customer engagement and retention.',
            ],
            'lifetime' => [
                'label' => 'Lifetime Access',
                'price' => 14999,
                'days' => null,
                'description' => 'One-time investment with permanent dashboard validity.',
            ],
        ]);
    }

    /** @return array{name: string, email: string, phone: string, address: string, gstin: string, website: string} */
    public function companyProfile(): array
    {
        return (array) $this->get('billing.company_profile', [
            'name' => env('COMPANY_NAME', 'AH&V Software Technologies'),
            'email' => env('COMPANY_EMAIL', config('mail.from.address', 'billing@ahvsoftware.com')),
            'phone' => env('COMPANY_PHONE', ''),
            'address' => env('COMPANY_ADDRESS', 'Sri Ganganagar, Rajasthan, India'),
            'gstin' => env('COMPANY_GSTIN', ''),
            'website' => env('APP_URL', 'https://wap.ahvsoftware.com'),
        ]);
    }

    /** @return array{marketing: float, utility: float, authentication: float, service: float} */
    public function metaMessageRates(): array
    {
        $rates = (array) $this->get('billing.meta_rates', [
            'marketing' => 0.88,
            'utility' => 0.15,
            'authentication' => 0.15,
            'service' => 0.00,
        ]);

        return [
            'marketing' => (float) ($rates['marketing'] ?? 0.88),
            'utility' => (float) ($rates['utility'] ?? 0.15),
            'authentication' => (float) ($rates['authentication'] ?? 0.15),
            'service' => (float) ($rates['service'] ?? 0.00),
        ];
    }
}
