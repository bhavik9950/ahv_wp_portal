<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Data;

use InvalidArgumentException;

/**
 * A validated destination MSISDN in E.164 (digits only, no '+').
 */
final readonly class Recipient
{
    public string $e164;

    public function __construct(string $number)
    {
        $normalized = ltrim(trim($number), '+');
        $normalized = preg_replace('/\D/', '', $normalized) ?? '';

        if (strlen($normalized) < 8 || strlen($normalized) > 15) {
            throw new InvalidArgumentException("Invalid E.164 phone number: {$number}");
        }

        $this->e164 = $normalized;
    }

    public function withPlus(): string
    {
        return '+'.$this->e164;
    }

    public function __toString(): string
    {
        return $this->e164;
    }
}
