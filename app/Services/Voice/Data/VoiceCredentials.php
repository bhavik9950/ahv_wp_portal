<?php

declare(strict_types=1);

namespace App\Services\Voice\Data;

/**
 * Resolved credentials for the voice broadcast provider. Kept out of logs.
 */
final readonly class VoiceCredentials
{
    public function __construct(
        public string $userId,
        public string $password,
        public ?string $apiKey,
        public string $baseUrl,
    ) {}

    public static function fromConfig(): self
    {
        $c = (array) config('services.voice');

        return new self(
            userId: (string) ($c['userid'] ?? ''),
            password: (string) ($c['password'] ?? ''),
            apiKey: isset($c['api_key']) && $c['api_key'] !== '' ? (string) $c['api_key'] : null,
            baseUrl: rtrim((string) ($c['base_url'] ?? ''), '/'),
        );
    }

    public function isComplete(): bool
    {
        return $this->userId !== '' && $this->password !== '' && $this->baseUrl !== '';
    }
}
