<?php

declare(strict_types=1);

namespace App\Services\WhatsApp\Data;

final readonly class ConnectionCheck
{
    /**
     * @param  array<string, mixed>  $details
     */
    public function __construct(
        public string $key,
        public string $label,
        public bool $passed,
        public string $message,
        public array $details = [],
    ) {}

    public static function pass(string $key, string $label, string $message = 'OK', array $details = []): self
    {
        return new self($key, $label, true, $message, $details);
    }

    public static function fail(string $key, string $label, string $message, array $details = []): self
    {
        return new self($key, $label, false, $message, $details);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'label' => $this->label,
            'passed' => $this->passed,
            'message' => $this->message,
            'details' => $this->details,
        ];
    }
}
