<?php

declare(strict_types=1);

namespace App\Services\WhatsApp;

/**
 * Normalises phone numbers to E.164 (digits only, no '+').
 *
 * Not tied to any single country — the org's default country code is only used
 * when the input has no discernible country prefix.
 */
final class PhoneNumberNormalizer
{
    public function __construct(private readonly string $defaultCountryCode = '91') {}

    public static function make(?string $defaultCountryCode = null): self
    {
        return new self($defaultCountryCode ?: (string) config('services.whatsapp.default_country_code', '91'));
    }

    /**
     * @return array{e164: string, country_code: string|null}|null  null if it can't be a valid number
     */
    public function parse(?string $input, ?string $countryCode = null): ?array
    {
        $raw = trim((string) $input);
        if ($raw === '') {
            return null;
        }

        $hasPlus = str_starts_with($raw, '+') || str_starts_with($raw, '00');
        $digits = preg_replace('/\D/', '', $raw) ?? '';
        $digits = ltrim($digits, '0'); // drop trunk / 00 prefix

        if ($digits === '') {
            return null;
        }

        $cc = $countryCode !== null ? preg_replace('/\D/', '', $countryCode) : null;

        if (! $hasPlus && $cc === null) {
            // Assume a national number for the org's default country.
            $digits = $this->defaultCountryCode.$digits;
            $cc = $this->defaultCountryCode;
        } elseif (! $hasPlus && $cc !== null && ! str_starts_with($digits, (string) $cc)) {
            $digits = $cc.$digits;
        }

        if (strlen($digits) < 8 || strlen($digits) > 15) {
            return null;
        }

        return [
            'e164' => $digits,
            'country_code' => $cc ?: $this->guessCountryCode($digits),
        ];
    }

    public function normalize(?string $input, ?string $countryCode = null): ?string
    {
        return $this->parse($input, $countryCode)['e164'] ?? null;
    }

    public static function hash(string $e164): string
    {
        return hash('sha256', $e164);
    }

    private function guessCountryCode(string $e164): ?string
    {
        // Common cases; falls back to null (unknown) rather than guessing wildly.
        foreach (['1', '7', '20', '27', '30', '31', '33', '34', '39', '40', '41', '44', '49', '52', '55', '60', '61', '62', '63', '64', '65', '66', '81', '82', '84', '86', '90', '91', '92', '93', '94', '95', '971', '972', '973', '974', '975', '976', '977', '992', '993', '994', '995', '996', '998'] as $cc) {
            if (str_starts_with($e164, $cc)) {
                return $cc;
            }
        }

        return null;
    }
}
