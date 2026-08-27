<?php

declare(strict_types=1);

namespace App\Services\System;

final readonly class ComponentHealth
{
    public function __construct(
        public string $key,
        public string $label,
        public string $status, // ok | warning | error | na
        public string $message = '',
    ) {}

    public static function ok(string $key, string $label, string $message = 'Healthy'): self
    {
        return new self($key, $label, 'ok', $message);
    }

    public static function warning(string $key, string $label, string $message): self
    {
        return new self($key, $label, 'warning', $message);
    }

    public static function error(string $key, string $label, string $message): self
    {
        return new self($key, $label, 'error', $message);
    }

    public static function na(string $key, string $label, string $message = 'Not configured'): self
    {
        return new self($key, $label, 'na', $message);
    }

    public function isHealthy(): bool
    {
        return in_array($this->status, ['ok', 'na'], true);
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'status' => $this->status,
            'message' => $this->message,
        ];
    }
}
